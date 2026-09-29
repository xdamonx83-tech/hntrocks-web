<?php

namespace App\Services\Translation;

use App\Models\NewsArticle;
use App\Models\NewsArticleTranslation;
use App\Models\User;
use App\Services\NewsArticleEditorService;
use App\Support\NewsContentDocument;
use Illuminate\Validation\ValidationException;

class NewsArticleTranslationService
{
    private const TRANSLATABLE_KEYS = [
        'text',
        'caption',
        'alt',
        'attribution',
        'before_alt',
        'after_alt',
        'before_label',
        'after_label',
        'beforeAlt',
        'afterAlt',
        'beforeLabel',
        'afterLabel',
        'author',
        'title',
    ];

    public function __construct(
        private readonly FeedTranslationService $translator,
        private readonly NewsArticleEditorService $editor,
    ) {
    }

    /**
     * @param array<int, string> $targetLocales
     * @return array{article: NewsArticle, translated_locales: array<int, string>, skipped_locales: array<int, string>}
     */
    public function translateArticle(
        NewsArticle $article,
        User $editor,
        int $expectedVersion,
        string $sourceLocale,
        array $targetLocales,
        bool $overwrite = false,
    ): array {
        $sourceLocale = strtolower($sourceLocale);
        $targets = collect($targetLocales)
            ->map(fn ($locale) => strtolower(trim((string) $locale)))
            ->filter()
            ->unique()
            ->reject(fn ($locale) => $locale === $sourceLocale)
            ->values();

        if (! in_array($sourceLocale, NewsArticle::LOCALES, true)) {
            throw ValidationException::withMessages(['source_locale' => 'Choose a supported source locale.']);
        }

        if ($targets->isEmpty() || $targets->contains(fn ($locale) => ! in_array($locale, NewsArticle::LOCALES, true))) {
            throw ValidationException::withMessages(['target_locales' => 'Choose one or more supported target locales.']);
        }

        $article->loadMissing('translations');
        $source = $article->translations->firstWhere('locale', $sourceLocale);

        if (! $source instanceof NewsArticleTranslation || ! $source->isPublishable()) {
            throw ValidationException::withMessages([
                'source_locale' => 'Complete the source language with title, slug and content before translating.',
            ]);
        }

        $payload = [];
        $skipped = [];

        foreach ($targets as $targetLocale) {
            $existing = $article->translations->firstWhere('locale', $targetLocale);

            if (! $overwrite && $this->hasAnyTranslationContent($existing)) {
                $skipped[] = $targetLocale;
                continue;
            }

            $payload[$targetLocale] = $this->translateOne($source, $sourceLocale, $targetLocale);
        }

        if ($payload === []) {
            return [
                'article' => $article->fresh()->load(['translations', 'heroMedia'])->loadCount('revisions'),
                'translated_locales' => [],
                'skipped_locales' => $skipped,
            ];
        }

        $updated = $this->editor->autosave(
            $article,
            $editor,
            $expectedVersion,
            ['translations' => $payload],
        )->load(['translations', 'heroMedia'])->loadCount('revisions');

        return [
            'article' => $updated,
            'translated_locales' => array_keys($payload),
            'skipped_locales' => $skipped,
        ];
    }

    private function translateOne(
        NewsArticleTranslation $source,
        string $sourceLocale,
        string $targetLocale,
    ): array {
        $content = is_array($source->content_json) ? $source->content_json : [];
        $texts = [];
        $paths = [];
        $counter = 0;

        $add = function (string $value, array $path) use (&$texts, &$paths, &$counter): void {
            $value = trim($value);
            if ($value === '') {
                return;
            }

            $key = 's'.(++$counter);
            $texts[$key] = $value;
            $paths[$key] = $path;
        };

        foreach ([
            'title' => $source->title,
            'excerpt' => $source->excerpt,
            'seo_title' => $source->seo_title,
            'seo_description' => $source->seo_description,
        ] as $field => $value) {
            if (is_string($value) && trim($value) !== '') {
                $add($value, ['meta', $field]);
            }
        }

        $this->collectContentTexts($content, [], $add);

        $translated = $this->translator->translateMap($texts, $sourceLocale, $targetLocale);

        $meta = [
            'title' => null,
            'excerpt' => null,
            'seo_title' => null,
            'seo_description' => null,
        ];

        foreach ($translated as $key => $value) {
            $path = $paths[$key] ?? null;
            if (! is_array($path) || $path === []) {
                continue;
            }

            if (($path[0] ?? null) === 'meta') {
                $field = $path[1] ?? null;
                if (is_string($field) && array_key_exists($field, $meta)) {
                    $meta[$field] = $value;
                }
                continue;
            }

            $this->setByPath($content, $path, $value);
        }

        $this->normalizeLegacyRichParagraphs($content);

        return [
            'title' => $meta['title'],
            'excerpt' => $meta['excerpt'],
            'content_json' => $content,
            'seo_title' => $meta['seo_title'],
            'seo_description' => $meta['seo_description'],
            // Omit slug intentionally: NewsArticleEditorService generates a localized slug from the translated title.
            // Canonical URL is also omitted so a DE canonical is never copied into another locale.
        ];
    }

    /**
     * @param callable(string, array<int, string|int>): void $add
     */
    private function collectContentTexts(mixed $value, array $path, callable $add, ?string $parentKey = null): void
    {
        if (! is_array($value)) {
            return;
        }

        $legacyRichParagraph = ($value['type'] ?? null) === 'paragraph'
            && isset($value['runs'])
            && is_array($value['runs'])
            && array_is_list($value['runs']);

        foreach ($value as $key => $item) {
            $currentPath = [...$path, $key];

            if (is_string($item)) {
                if ($legacyRichParagraph && $key === 'text') {
                    continue;
                }

                if (
                    in_array((string) $key, self::TRANSLATABLE_KEYS, true)
                    || in_array($parentKey, ['items', 'textContent'], true)
                ) {
                    $add($item, $currentPath);
                }

                continue;
            }

            if (is_array($item)) {
                $this->collectContentTexts(
                    $item,
                    $currentPath,
                    $add,
                    is_string($key) ? $key : $parentKey,
                );
            }
        }
    }

    private function setByPath(array &$root, array $path, string $value): void
    {
        $cursor = &$root;

        foreach ($path as $index => $segment) {
            if ($index === array_key_last($path)) {
                $cursor[$segment] = $value;
                return;
            }

            if (! isset($cursor[$segment]) || ! is_array($cursor[$segment])) {
                return;
            }

            $cursor = &$cursor[$segment];
        }
    }

    private function normalizeLegacyRichParagraphs(array &$value): void
    {
        if (($value['type'] ?? null) === 'paragraph'
            && isset($value['runs'])
            && is_array($value['runs'])
            && array_is_list($value['runs'])) {
            $value['text'] = collect($value['runs'])
                ->map(fn ($run) => is_array($run) ? (string) ($run['text'] ?? '') : '')
                ->implode('');
        }

        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->normalizeLegacyRichParagraphs($item);
            }
        }
        unset($item);
    }

    private function hasAnyTranslationContent(?NewsArticleTranslation $translation): bool
    {
        if (! $translation) {
            return false;
        }

        return collect([
            $translation->title,
            $translation->slug,
            $translation->excerpt,
            $translation->seo_title,
            $translation->seo_description,
            $translation->canonical_url,
        ])->contains(fn ($value) => is_string($value) && trim($value) !== '')
            || (is_array($translation->content_json) && NewsContentDocument::hasContent($translation->content_json));
    }
}
