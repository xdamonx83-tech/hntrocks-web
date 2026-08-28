<?php

namespace App\Services\Guides;

use App\Models\Guide;
use App\Models\GuideMedia;
use App\Models\GuideModerationEvent;
use App\Models\GuideRevision;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuideWorkflowService
{
    public function __construct(
        private readonly GuideContentService $content,
        private readonly GuideReputationService $reputation,
        private readonly NotificationService $notifications,
    ) {
    }

    public function create(User $author): Guide
    {
        return DB::transaction(function () use ($author): Guide {
            $guide = Guide::query()->create([
                'author_id' => $author->id,
                'slug' => 'guide-'.Str::uuid(),
                'status' => 'draft',
            ]);

            $revision = $guide->revisions()->create([
                'version' => 1,
                'author_id' => $author->id,
                'language' => in_array(app()->getLocale(), ['de', 'en'], true) ? app()->getLocale() : 'de',
                'difficulty' => 'beginner',
                'platform' => 'all',
                'content_blocks' => [],
                'status' => 'draft',
            ]);

            $guide->update(['working_revision_id' => $revision->id]);

            return $guide->fresh(['workingRevision']);
        });
    }

    public function ensureWorkingRevision(Guide $guide, User $author): GuideRevision
    {
        return DB::transaction(function () use ($guide, $author): GuideRevision {
            $locked = Guide::query()->lockForUpdate()->findOrFail($guide->id);
            $working = $locked->workingRevision;

            if ($working && $working->status !== 'rejected') {
                return $working;
            }

            $published = $working ?: $locked->publishedRevision;
            $nextVersion = ((int) $locked->revisions()->max('version')) + 1;
            $revision = $locked->revisions()->create([
                'version' => $nextVersion,
                'author_id' => $author->id,
                'category_id' => $published?->category_id,
                'cover_media_id' => $published?->cover_media_id,
                'title' => $published?->title ?? '',
                'summary' => $published?->summary ?? '',
                'tags' => $published?->tags ?? [],
                'language' => $published?->language ?? 'de',
                'difficulty' => $published?->difficulty ?? 'beginner',
                'platform' => $published?->platform ?? 'all',
                'content_blocks' => $published?->content_blocks ?? [],
                'reading_time_minutes' => $published?->reading_time_minutes ?? 1,
                'status' => 'draft',
            ]);

            $locked->update([
                'working_revision_id' => $revision->id,
                'status' => 'draft',
            ]);

            return $revision;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Guide $guide, User $author, array $data): GuideRevision
    {
        return DB::transaction(function () use ($guide, $author, $data): GuideRevision {
            $revision = $this->ensureWorkingRevision($guide, $author);

            if (! $revision->isEditable()) {
                throw ValidationException::withMessages([
                    'guide' => __('guides.validation.review_locked'),
                ]);
            }

            $blocks = $this->content->sanitizeBlocks((array) ($data['content_blocks'] ?? $revision->content_blocks ?? []));
            $revision->fill([
                'title' => trim((string) ($data['title'] ?? $revision->title)),
                'summary' => trim((string) ($data['summary'] ?? $revision->summary)),
                'category_id' => $data['category_id'] ?? $revision->category_id,
                'cover_media_id' => $data['cover_media_id'] ?? $revision->cover_media_id,
                'tags' => $this->content->sanitizeTags((array) ($data['tags'] ?? $revision->tags ?? [])),
                'language' => $data['language'] ?? $revision->language,
                'difficulty' => $data['difficulty'] ?? $revision->difficulty,
                'platform' => $data['platform'] ?? $revision->platform,
                'content_blocks' => $blocks,
                'reading_time_minutes' => $this->content->readingTime($blocks),
                'moderation_reason' => $revision->status === 'changes_requested' ? $revision->moderation_reason : null,
            ])->save();

            if (array_key_exists('show_in_profile', $data)) {
                $guide->forceFill([
                    'show_in_profile' => (bool) $data['show_in_profile'],
                ])->save();
            }

            $referencedMediaIds = collect($blocks)
                ->where('type', 'image')
                ->pluck('media_id')
                ->push($revision->cover_media_id)
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            GuideMedia::query()
                ->where('guide_id', $guide->id)
                ->where('revision_id', $revision->id)
                ->whereIn('id', $referencedMediaIds)
                ->update(['orphaned_at' => null]);

            GuideMedia::query()
                ->where('guide_id', $guide->id)
                ->where('revision_id', $revision->id)
                ->when(
                    $referencedMediaIds->isNotEmpty(),
                    fn ($query) => $query->whereNotIn('id', $referencedMediaIds)
                )
                ->whereNull('orphaned_at')
                ->update(['orphaned_at' => now()]);

            return $revision->fresh(['category', 'coverMedia']);
        });
    }

    public function submit(Guide $guide, User $author): GuideRevision
    {
        return DB::transaction(function () use ($guide, $author): GuideRevision {
            $locked = Guide::query()->lockForUpdate()->findOrFail($guide->id);
            $revision = $locked->workingRevision;

            if (! $revision || ! $revision->isEditable()) {
                throw ValidationException::withMessages(['guide' => __('guides.validation.review_locked')]);
            }

            $this->content->assertReadyForSubmission($revision);
            $fromStatus = $locked->status;

            if (! $locked->current_published_revision_id && str_starts_with($locked->slug, 'guide-')) {
                $locked->slug = $this->uniqueSlug($revision->title, $locked);
            }

            $revision->update([
                'status' => 'pending_review',
                'submitted_at' => now(),
                'reviewed_at' => null,
                'moderator_id' => null,
                'moderation_reason' => null,
            ]);
            $locked->fill(['status' => 'pending_review'])->save();

            $this->event($locked, $revision, $author, 'submitted', $fromStatus, 'pending_review');
            $this->notifications->send(
                $author,
                null,
                'guide_submitted',
                'guides.notifications.submitted_title',
                'guides.notifications.submitted_body',
                url('/guides/mine')
            );

            return $revision->fresh();
        });
    }

    public function withdraw(Guide $guide, User $author): GuideRevision
    {
        return DB::transaction(function () use ($guide, $author): GuideRevision {
            $locked = Guide::query()->lockForUpdate()->findOrFail($guide->id);
            $revision = $locked->workingRevision;

            if (! $revision || $revision->status !== 'pending_review') {
                throw ValidationException::withMessages(['guide' => __('guides.validation.withdraw_invalid')]);
            }

            $revision->update([
                'status' => 'draft',
                'submitted_at' => null,
                'reviewed_at' => null,
                'moderator_id' => null,
                'moderation_reason' => null,
            ]);
            $locked->update(['status' => 'draft']);
            $this->event($locked, $revision, $author, 'withdrawn', 'pending_review', 'draft');

            return $revision->fresh();
        });
    }

    public function moderate(GuideRevision $revision, User $moderator, string $action, ?string $reason = null): Guide
    {
        return DB::transaction(function () use ($revision, $moderator, $action, $reason): Guide {
            $lockedRevision = GuideRevision::query()->lockForUpdate()->findOrFail($revision->id);
            $guide = Guide::query()->lockForUpdate()->findOrFail($lockedRevision->guide_id);

            if ($action !== 'archive' && $lockedRevision->status !== 'pending_review') {
                throw ValidationException::withMessages(['guide' => __('guides.validation.moderation_stale')]);
            }

            if (in_array($action, ['changes', 'reject', 'archive'], true) && mb_strlen(trim((string) $reason)) < 10) {
                throw ValidationException::withMessages(['reason' => __('guides.validation.reason_required')]);
            }

            return match ($action) {
                'approve' => $this->approve($guide, $lockedRevision, $moderator),
                'changes' => $this->requestChanges($guide, $lockedRevision, $moderator, (string) $reason),
                'reject' => $this->reject($guide, $lockedRevision, $moderator, (string) $reason),
                default => throw ValidationException::withMessages(['action' => __('guides.validation.action_invalid')]),
            };
        });
    }

    public function archive(Guide $guide, User $moderator, string $reason): Guide
    {
        return DB::transaction(function () use ($guide, $moderator, $reason): Guide {
            if (mb_strlen(trim($reason)) < 10) {
                throw ValidationException::withMessages(['reason' => __('guides.validation.reason_required')]);
            }

            $locked = Guide::query()->lockForUpdate()->findOrFail($guide->id);
            $fromStatus = $locked->status;
            $locked->update(['status' => 'archived', 'archived_at' => now()]);
            $this->reputation->reverseForArchive($locked);
            $this->event($locked, $locked->publishedRevision, $moderator, 'archived', $fromStatus, 'archived', $reason);
            $this->notifyAuthor($locked, $moderator, 'guide_archived', 'archived', url('/guides/mine'));

            return $locked->fresh();
        });
    }

    public function restore(Guide $guide, User $moderator): Guide
    {
        return DB::transaction(function () use ($guide, $moderator): Guide {
            $locked = Guide::query()->lockForUpdate()->findOrFail($guide->id);
            $status = $locked->current_published_revision_id ? 'published' : 'draft';
            $locked->update(['status' => $status, 'archived_at' => null]);
            $this->reputation->restoreAfterArchive($locked);
            $this->event($locked, $locked->publishedRevision, $moderator, 'restored', 'archived', $status);
            $url = $locked->isPublished() ? url('/guides/'.$locked->slug) : url('/guides/mine');
            $this->notifyAuthor($locked, $moderator, 'guide_restored', 'restored', $url);

            return $locked->fresh();
        });
    }

    public function setFeatured(Guide $guide, User $moderator, bool $featured): Guide
    {
        return DB::transaction(function () use ($guide, $moderator, $featured): Guide {
            $locked = Guide::query()->lockForUpdate()->findOrFail($guide->id);
            $locked->update(['is_featured' => $featured]);
            $this->event(
                $locked,
                $locked->publishedRevision,
                $moderator,
                $featured ? 'featured' : 'unfeatured',
                $locked->status,
                $locked->status
            );

            return $locked->fresh();
        });
    }

    private function approve(Guide $guide, GuideRevision $revision, User $moderator): Guide
    {
        $wasPublished = $guide->current_published_revision_id !== null;
        $revision->update([
            'status' => 'published',
            'reviewed_at' => now(),
            'moderator_id' => $moderator->id,
            'moderation_reason' => null,
        ]);
        $guide->update([
            'status' => 'published',
            'current_published_revision_id' => $revision->id,
            'working_revision_id' => null,
            'published_at' => $guide->published_at ?: now(),
            'archived_at' => null,
        ]);
        $this->reputation->awardPublication($guide);
        $this->event($guide, $revision, $moderator, $wasPublished ? 'revision_approved' : 'approved', 'pending_review', 'published');
        $this->notifyAuthor($guide, $moderator, 'guide_approved', 'approved', url('/guides/'.$guide->slug));

        return $guide->fresh(['publishedRevision']);
    }

    private function requestChanges(Guide $guide, GuideRevision $revision, User $moderator, string $reason): Guide
    {
        $revision->update([
            'status' => 'changes_requested',
            'reviewed_at' => now(),
            'moderator_id' => $moderator->id,
            'moderation_reason' => trim($reason),
        ]);
        $guide->update(['status' => 'changes_requested']);
        $this->event($guide, $revision, $moderator, 'changes_requested', 'pending_review', 'changes_requested', $reason);
        $this->notifyAuthor($guide, $moderator, 'guide_changes_requested', 'changes_requested', url('/guides/'.$guide->slug.'/edit'));

        return $guide->fresh(['workingRevision']);
    }

    private function reject(Guide $guide, GuideRevision $revision, User $moderator, string $reason): Guide
    {
        $revision->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'moderator_id' => $moderator->id,
            'moderation_reason' => trim($reason),
        ]);
        $guide->update(['status' => 'rejected']);
        $this->event($guide, $revision, $moderator, 'rejected', 'pending_review', 'rejected', $reason);
        $this->notifyAuthor($guide, $moderator, 'guide_rejected', 'rejected', url('/guides/mine'));

        return $guide->fresh(['workingRevision']);
    }

    private function notifyAuthor(Guide $guide, ?User $actor, string $type, string $translationStem, string $url): void
    {
        $guide->loadMissing('author');
        $this->notifications->send(
            $guide->author,
            $actor,
            $type,
            "guides.notifications.{$translationStem}_title",
            "guides.notifications.{$translationStem}_body",
            $url
        );
    }

    private function event(
        Guide $guide,
        ?GuideRevision $revision,
        ?User $actor,
        string $action,
        ?string $from,
        ?string $to,
        ?string $reason = null,
    ): void {
        GuideModerationEvent::query()->create([
            'guide_id' => $guide->id,
            'revision_id' => $revision?->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason ? trim($reason) : null,
        ]);
    }

    private function uniqueSlug(string $title, Guide $guide): string
    {
        $base = Str::slug($title);
        $base = $base !== '' ? Str::limit($base, 140, '') : 'community-guide';
        $slug = $base;
        $suffix = 2;

        while (Guide::query()->whereKeyNot($guide->id)->where('slug', $slug)->exists()) {
            $slug = Str::limit($base, 135, '').'-'.$suffix++;
        }

        return $slug;
    }
}
