<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\NewsArticle;
use App\Models\NewsArticleRevision;
use App\Models\NewsArticleTranslation;
use App\Models\User;
use App\Rules\NewsContentBlocks;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewsArticleEditorService
{
    private const EDITABLE_FIELDS = [
        'category_key',
        'tags',
        'hero_media_asset_id',
        'featured',
        'comments_enabled',
    ];

    private const TRANSLATION_FIELDS = [
        'slug',
        'title',
        'excerpt',
        'content_json',
        'seo_title',
        'seo_description',
        'canonical_url',
    ];

    public function create(User $editor, array $data): NewsArticle
    {
        return DB::transaction(function () use ($editor, $data): NewsArticle {
            $article = NewsArticle::create([
                'status' => NewsArticle::STATUS_DRAFT,
                'lock_version' => 1,
                'created_by_user_id' => $editor->id,
                'updated_by_user_id' => $editor->id,
            ]);

            $this->applyEditorData($article, $data);
            $article->save();
            $article->refresh()->load('translations', 'heroMedia');
            $this->recordRevision($article, $editor, 'create');

            return $article;
        });
    }

    public function autosave(NewsArticle $routeArticle, User $editor, int $expectedVersion, array $data): NewsArticle
    {
        return DB::transaction(function () use ($routeArticle, $editor, $expectedVersion, $data): NewsArticle {
            $article = NewsArticle::query()->lockForUpdate()->findOrFail($routeArticle->id);
            $this->assertVersion($article, $expectedVersion);
            $before = $this->snapshot($article);
            $this->applyEditorData($article, $data);
            $article->unsetRelation('translations');
            $after = $this->snapshot($article);

            if ($before !== $after) {
                $article->forceFill([
                    'lock_version' => $article->lock_version + 1,
                    'updated_by_user_id' => $editor->id,
                ])->save();
                $article->refresh()->load('translations', 'heroMedia');
                $this->recordRevision($article, $editor, 'autosave');
            }

            return $article->fresh()->load('translations', 'heroMedia');
        });
    }

    public function transition(
        NewsArticle $routeArticle,
        ?User $editor,
        int $expectedVersion,
        string $action,
        ?string $scheduledAt = null,
    ): NewsArticle {
        return DB::transaction(function () use ($routeArticle, $editor, $expectedVersion, $action, $scheduledAt): NewsArticle {
            $article = NewsArticle::query()->lockForUpdate()->findOrFail($routeArticle->id);
            $this->assertVersion($article, $expectedVersion);

            $attributes = ['updated_by_user_id' => $editor?->id];

            switch ($action) {
                case 'draft':
                    $attributes += [
                        'status' => NewsArticle::STATUS_DRAFT,
                        'scheduled_at' => null,
                        'published_at' => null,
                        'archived_at' => null,
                        'published_by_user_id' => null,
                    ];
                    $revisionType = 'workflow';
                    break;

                case 'schedule':
                    $this->assertPublishable($article);
                    $runAt = $scheduledAt ? Carbon::parse($scheduledAt) : null;
                    if (! $runAt || $runAt->isPast()) {
                        throw ValidationException::withMessages(['scheduled_at' => 'Choose a future publication time.']);
                    }

                    $attributes += [
                        'status' => NewsArticle::STATUS_SCHEDULED,
                        'scheduled_at' => $runAt,
                        'published_at' => null,
                        'archived_at' => null,
                        'published_by_user_id' => null,
                    ];
                    $revisionType = 'workflow';
                    break;

                case 'publish':
                    $this->assertPublishable($article);
                    $attributes += [
                        'status' => NewsArticle::STATUS_PUBLISHED,
                        'scheduled_at' => null,
                        'published_at' => now(),
                        'archived_at' => null,
                        'published_by_user_id' => $editor?->id,
                    ];
                    $revisionType = 'publish';
                    break;

                case 'archive':
                    $attributes += [
                        'status' => NewsArticle::STATUS_ARCHIVED,
                        'scheduled_at' => null,
                        'archived_at' => now(),
                    ];
                    $revisionType = 'workflow';
                    break;

                default:
                    throw ValidationException::withMessages(['action' => 'Choose a supported workflow action.']);
            }

            $article->forceFill($attributes + ['lock_version' => $article->lock_version + 1])->save();
            $article->refresh()->load('translations', 'heroMedia');
            $this->recordRevision($article, $editor, $revisionType);

            return $article;
        });
    }

    public function publishDue(int $limit = 100): int
    {
        $ids = NewsArticle::query()
            ->where('status', NewsArticle::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit(max(1, min(500, $limit)))
            ->pluck('id');

        $published = 0;

        foreach ($ids as $id) {
            $didPublish = DB::transaction(function () use ($id): bool {
                $article = NewsArticle::query()->lockForUpdate()->find($id);
                if (! $article || $article->status !== NewsArticle::STATUS_SCHEDULED || ! $article->scheduled_at?->lte(now())) {
                    return false;
                }

                try {
                    $this->assertPublishable($article);
                } catch (ValidationException) {
                    report(new \RuntimeException('Scheduled news article '.$article->id.' is no longer publishable.'));

                    return false;
                }

                $article->forceFill([
                    'status' => NewsArticle::STATUS_PUBLISHED,
                    'published_at' => $article->scheduled_at,
                    'published_by_user_id' => null,
                    'updated_by_user_id' => null,
                    'scheduled_at' => null,
                    'archived_at' => null,
                    'lock_version' => $article->lock_version + 1,
                ])->save();
                $article->refresh()->load('translations');
                $this->recordRevision($article, null, 'scheduled_publish');

                return true;
            });

            $published += $didPublish ? 1 : 0;
        }

        return $published;
    }

    public function restoreRevision(NewsArticle $routeArticle, NewsArticleRevision $revision, User $editor, int $expectedVersion): NewsArticle
    {
        return DB::transaction(function () use ($routeArticle, $revision, $editor, $expectedVersion): NewsArticle {
            $article = NewsArticle::query()->lockForUpdate()->findOrFail($routeArticle->id);
            $this->assertVersion($article, $expectedVersion);

            $revision = $article->revisions()->whereKey($revision->id)->firstOrFail();
            $snapshot = $revision->snapshot_json;
            $attributes = Arr::only($snapshot, self::EDITABLE_FIELDS);
            if (! empty($attributes['hero_media_asset_id']) && ! MediaAsset::query()
                ->whereKey($attributes['hero_media_asset_id'])
                ->where('context', 'news')
                ->where('visibility', 'private')
                ->where('status', 'ready')
                ->exists()) {
                $attributes['hero_media_asset_id'] = null;
            }
            $article->forceFill($attributes);
            $article->forceFill([
                'updated_by_user_id' => $editor->id,
                'lock_version' => $article->lock_version + 1,
            ])->save();

            $restoredLocales = [];
            foreach (($snapshot['translations'] ?? []) as $locale => $translationData) {
                if (! in_array($locale, NewsArticle::LOCALES, true) || ! is_array($translationData)) {
                    continue;
                }

                $this->assertRevisionTranslationCanBeRestored($article, $locale, $translationData);

                $restoredLocales[] = $locale;
                $article->translations()->updateOrCreate(
                    ['locale' => $locale],
                    Arr::only($translationData, self::TRANSLATION_FIELDS),
                );
            }
            $article->translations()->whereNotIn('locale', $restoredLocales)->delete();

            $article->refresh()->load('translations', 'heroMedia');
            $this->recordRevision($article, $editor, 'restore');

            return $article;
        });
    }

    public function snapshot(NewsArticle $article): array
    {
        $article->loadMissing('translations');

        return [
            'status' => $article->status,
            'category_key' => $article->category_key,
            'tags' => array_values($article->tags ?? []),
            'hero_media_asset_id' => $article->hero_media_asset_id ? (int) $article->hero_media_asset_id : null,
            'featured' => (bool) $article->featured,
            'comments_enabled' => (bool) $article->comments_enabled,
            'scheduled_at' => $article->scheduled_at?->toIso8601String(),
            'published_at' => $article->published_at?->toIso8601String(),
            'archived_at' => $article->archived_at?->toIso8601String(),
            'translations' => $article->translations
                ->sortBy('locale')
                ->mapWithKeys(function (NewsArticleTranslation $translation): array {
                    $fields = [];
                    foreach (self::TRANSLATION_FIELDS as $field) {
                        $fields[$field] = $translation->getAttribute($field);
                    }

                    return [$translation->locale => $fields];
                })
                ->all(),
        ];
    }

    private function applyEditorData(NewsArticle $article, array $data): void
    {
        $attributes = Arr::only($data, self::EDITABLE_FIELDS);
        if (array_key_exists('tags', $attributes)) {
            $attributes['tags'] = array_values(array_unique(array_map('strval', $attributes['tags'] ?? [])));
        }

        if (array_key_exists('hero_media_asset_id', $attributes) && $attributes['hero_media_asset_id'] !== null) {
            $asset = MediaAsset::query()
                ->whereKey($attributes['hero_media_asset_id'])
                ->where('context', 'news')
                ->where('visibility', 'private')
                ->where('status', 'ready')
                ->whereIn('type', ['image', 'video'])
                ->first();

            if (! $asset) {
                throw ValidationException::withMessages(['hero_media_asset_id' => 'Choose an uploaded news image or video.']);
            }
        }

        $article->fill($attributes);

        foreach (($data['translations'] ?? []) as $locale => $translationData) {
            if (! in_array($locale, NewsArticle::LOCALES, true)) {
                throw ValidationException::withMessages(['translations' => 'Only de, en, es, and ru translations are supported.']);
            }

            $values = Arr::only($translationData, self::TRANSLATION_FIELDS);
            if (filled($values['slug'] ?? null)) {
                $values['slug'] = Str::slug($values['slug']);
            } elseif (isset($values['title']) && filled($values['title'])) {
                $values['slug'] = Str::slug($values['title']);
            } elseif (array_key_exists('slug', $values)) {
                $values['slug'] = null;
            }

            if ($values === []) {
                continue;
            }

            $existing = $article->translations()->where('locale', $locale)->first();
            if (! empty($values['slug'])) {
                $slugExists = NewsArticleTranslation::query()
                    ->where('locale', $locale)
                    ->where('slug', $values['slug'])
                    ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                    ->exists();

                if ($slugExists) {
                    throw ValidationException::withMessages(["translations.{$locale}.slug" => 'This slug is already used for the selected locale.']);
                }
            }

            if ($existing) {
                $existing->fill($values);
                if ($existing->isDirty()) {
                    $existing->save();
                }
            } else {
                $article->translations()->create(['locale' => $locale] + $values);
            }
        }
    }

    private function assertVersion(NewsArticle $article, int $expectedVersion): void
    {
        abort_if($article->lock_version !== $expectedVersion, 409, 'This article changed since it was loaded. Refresh before saving.');
    }

    private function assertPublishable(NewsArticle $article): void
    {
        $translations = $article->translations()->get()->keyBy('locale');
        $missing = [];

        foreach (NewsArticle::LOCALES as $locale) {
            $translation = $translations->get($locale);
            if (! $translation
                || trim((string) $translation->title) === ''
                || trim((string) $translation->slug) === ''
                || ! is_array($translation->content_json)
                || $translation->content_json === []
                || $this->hasInvalidContent($locale, $translation->content_json)) {
                $missing[] = $locale;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'translations' => 'Complete all locales and use valid news media before publishing: '.implode(', ', $missing).'.',
            ]);
        }
    }

    private function recordRevision(NewsArticle $article, ?User $editor, string $type): void
    {
        $number = ((int) $article->revisions()->max('revision_number')) + 1;

        NewsArticleRevision::create([
            'news_article_id' => $article->id,
            'revision_number' => $number,
            'lock_version' => $article->lock_version,
            'revision_type' => $type,
            'snapshot_json' => $this->snapshot($article),
            'editor_user_id' => $editor?->id,
            'created_at' => now(),
        ]);
    }

    private function assertRevisionTranslationCanBeRestored(NewsArticle $article, string $locale, array $translationData): void
    {
        $slug = $translationData['slug'] ?? null;
        if (filled($slug) && NewsArticleTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('news_article_id', '!=', $article->id)
            ->exists()) {
            throw ValidationException::withMessages(["revision.translations.{$locale}.slug" => 'This revision slug is now used by another article.']);
        }

        if (array_key_exists('content_json', $translationData) && $this->hasInvalidContent($locale, $translationData['content_json'])) {
            throw ValidationException::withMessages(["revision.translations.{$locale}.content_json" => 'This revision references missing or incompatible news media.']);
        }
    }

    private function hasInvalidContent(string $locale, mixed $content): bool
    {
        $invalid = false;
        (new NewsContentBlocks())->validate("translations.{$locale}.content_json", $content, function (string $message) use (&$invalid): void {
            $invalid = true;
        });

        return $invalid;
    }
}
