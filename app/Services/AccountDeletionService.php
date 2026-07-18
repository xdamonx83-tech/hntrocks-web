<?php

namespace App\Services;

use App\Models\AccountDeletionRequest;
use App\Models\Guide;
use App\Models\GuideComment;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AccountDeletionService
{
    /**
     * @return array{user_id:int, username:string, email:string, counts:array<string,int>, files:int, deleted_files:int, failed_files:int, dry_run:bool, skipped?:string}
     */
    public function process(AccountDeletionRequest $deletionRequest, bool $dryRun = true): array
    {
        $deletionRequest->loadMissing('user');
        $user = $deletionRequest->user;

        if (! $user) {
            if (! $dryRun) {
                $deletionRequest->forceFill([
                    'status' => 'processed',
                    'processed_at' => now(),
                ])->save();
            }

            return [
                'user_id' => (int) $deletionRequest->user_id,
                'username' => 'missing-user',
                'email' => '',
                'counts' => [],
                'files' => 0,
                'deleted_files' => 0,
                'failed_files' => 0,
                'dry_run' => $dryRun,
                'skipped' => 'user_missing',
            ];
        }

        $counts = $this->countsFor($user);
        $files = $this->fileReferencesFor($user);

        $result = [
            'user_id' => (int) $user->id,
            'username' => (string) $user->username,
            'email' => (string) $user->email,
            'counts' => $counts,
            'files' => $files->count(),
            'deleted_files' => 0,
            'failed_files' => 0,
            'dry_run' => $dryRun,
        ];

        if ($user->isAdmin()) {
            if (! $dryRun) {
                $deletionRequest->forceFill([
                    'status' => 'blocked_admin',
                    'processed_at' => now(),
                ])->save();
            }

            $result['skipped'] = 'admin_account';

            return $result;
        }

        if ($dryRun) {
            return $result;
        }

        DB::transaction(function () use ($user, $deletionRequest): void {
            $guideContext = $this->guideDeletionContext($user);
            $this->writeFinalSecurityEvent($user, $deletionRequest);
            $this->deleteNonCascadingRows($user);
            $this->deleteGuideData($user, $guideContext);

            $user->delete();

            $this->recalculateGuideCounters($guideContext['foreign_guide_ids']);
            $this->deleteOrphanConversations();
        });

        [$deletedFiles, $failedFiles] = $this->deleteFiles($files);

        $result['deleted_files'] = $deletedFiles;
        $result['failed_files'] = $failedFiles;

        return $result;
    }

    /** @return array<string,int> */
    public function countsFor(User $user): array
    {
        $userId = (int) $user->id;
        $email = (string) $user->email;
        $ownedGuideIds = $this->ownedGuideIds($userId);

        return array_filter([
            'users' => 1,
            'user_profiles' => $this->countWhere('user_profiles', 'user_id', $userId),
            'user_privacy_settings' => $this->countWhere('user_privacy_settings', 'user_id', $userId),
            'user_notification_settings' => $this->countWhere('user_notification_settings', 'user_id', $userId),
            'social_accounts' => $this->countWhere('social_accounts', 'user_id', $userId),
            'sessions' => $this->countWhere('sessions', 'user_id', $userId),
            'password_reset_tokens' => $this->countWhere('password_reset_tokens', 'email', $email),
            'feed_posts' => $this->countWhere('feed_posts', 'user_id', $userId),
            'feed_comments' => $this->countWhere('feed_comments', 'user_id', $userId),
            'feed_reactions' => $this->countWhere('feed_reactions', 'user_id', $userId),
            'feed_comment_reactions' => $this->countWhere('feed_comment_reactions', 'user_id', $userId),
            'feed_bookmarks' => $this->countWhere('feed_bookmarks', 'user_id', $userId),
            'media_assets' => $this->countWhere('media_assets', 'user_id', $userId),
            'lfg_posts' => $this->countWhere('lfg_posts', 'user_id', $userId),
            'lfg_applications' => $this->countWhere('lfg_applications', 'user_id', $userId),
            'team_lfg_posts' => $this->countWhere('team_lfg_posts', 'user_id', $userId),
            'team_lfg_applications' => $this->countWhere('team_lfg_applications', 'user_id', $userId),
            'teams_owned' => $this->countWhere('teams', 'owner_id', $userId),
            'team_members' => $this->countWhere('team_members', 'user_id', $userId),
            'messages' => $this->countWhere('messages', 'user_id', $userId),
            'conversation_participants' => $this->countWhere('conversation_participants', 'user_id', $userId),
            'user_notifications' => $this->countWhere('user_notifications', 'user_id', $userId),
            'user_notifications_actor' => $this->countWhere('user_notifications', 'actor_id', $userId),
            'moments' => $this->countWhere('moments', 'user_id', $userId),
            'moment_comments' => $this->countWhere('moment_comments', 'user_id', $userId),
            'moment_reactions' => $this->countWhere('moment_reactions', 'user_id', $userId),
            'moment_bookmarks' => $this->countWhere('moment_bookmarks', 'user_id', $userId),
            'cup_teams' => $this->countWhere('cup_teams', 'owner_id', $userId),
            'cup_team_members' => $this->countWhere('cup_team_members', 'user_id', $userId),
            'cup_submissions' => $this->countWhere('cup_submissions', 'submitted_by', $userId),
            'reports_reporter' => $this->countWhere('reports', 'reporter_id', $userId),
            'reports_assigned' => $this->countWhere('reports', 'assigned_to', $userId),
            'reports_resolved' => $this->countWhere('reports', 'resolved_by', $userId),
            'xp_events' => $this->countWhere('xp_events', 'user_id', $userId),
            'badge_user' => $this->countWhere('badge_user', 'user_id', $userId),
            'quest_user' => $this->countWhere('quest_user', 'user_id', $userId),
            'referral_links' => $this->countWhere('referral_links', 'user_id', $userId),
            'referral_signups_referrer' => $this->countWhere('referral_signups', 'referrer_id', $userId),
            'referral_signups_referred' => $this->countWhere('referral_signups', 'referred_user_id', $userId),
            'giveaway_entries' => $this->countWhere('giveaway_entries', 'user_id', $userId),
            'api_access_tokens' => $this->countWhere('api_access_tokens', 'user_id', $userId),
            'friendships_user_one' => $this->countWhere('friendships', 'user_one_id', $userId),
            'friendships_user_two' => $this->countWhere('friendships', 'user_two_id', $userId),
            'mentions_mentioner' => $this->countWhere('mentions', 'mentioner_id', $userId),
            'mentions_mentioned' => $this->countWhere('mentions', 'mentioned_user_id', $userId),
            'visitor_events' => $this->countWhere('visitor_events', 'user_id', $userId),
            'guides' => count($ownedGuideIds),
            'guide_revisions' => $this->countGuideRows('guide_revisions', 'author_id', $userId, $ownedGuideIds),
            'guide_media' => $this->countGuideRows('guide_media', 'uploaded_by', $userId, $ownedGuideIds),
            'guide_moderation_events' => $this->countGuideRows('guide_moderation_events', 'actor_id', $userId, $ownedGuideIds),
            'guide_comments' => $this->countGuideRows('guide_comments', 'user_id', $userId, $ownedGuideIds),
            'guide_helpful_votes' => $this->countGuideRows('guide_helpful_votes', 'user_id', $userId, $ownedGuideIds),
            'guide_bookmarks' => $this->countGuideRows('guide_bookmarks', 'user_id', $userId, $ownedGuideIds),
            'guide_reputation_entries' => $this->countGuideRows(
                'guide_reputation_entries',
                'user_id',
                $userId,
                $ownedGuideIds,
                includeHelpfulVoterEvents: true
            ),
        ], static fn (int $count): bool => $count > 0);
    }

    /** @return Collection<int,array{disk:string,path:string}> */
    private function fileReferencesFor(User $user): Collection
    {
        $files = collect();

        foreach ([$user->avatar_path, $user->cover_path] as $path) {
            $this->pushFile($files, 'public', $path);
        }

        if (Schema::hasTable('teams')) {
            DB::table('teams')
                ->where('owner_id', $user->id)
                ->select(['id', 'avatar_path', 'cover_path'])
                ->orderBy('id')
                ->chunkById(100, function ($teams) use ($files): void {
                    foreach ($teams as $team) {
                        $this->pushFile($files, 'public', $team->avatar_path ?? null);
                        $this->pushFile($files, 'public', $team->cover_path ?? null);
                    }
                });
        }

        if (Schema::hasTable('cups')) {
            DB::table('cups')
                ->where('owner_id', $user->id)
                ->select(['id', 'cover_path'])
                ->orderBy('id')
                ->chunkById(100, function ($cups) use ($files): void {
                    foreach ($cups as $cup) {
                        $this->pushFile($files, 'public', $cup->cover_path ?? null);
                    }
                });
        }

        if (Schema::hasTable('media_assets')) {
            DB::table('media_assets')
                ->where('user_id', $user->id)
                ->select(['id', 'disk', 'path', 'thumbnail_path'])
                ->orderBy('id')
                ->chunkById(100, function ($assets) use ($files): void {
                    foreach ($assets as $asset) {
                        $disk = filled($asset->disk ?? null) ? (string) $asset->disk : 'public';
                        $this->pushFile($files, $disk, $asset->path ?? null);
                        $this->pushFile($files, $disk, $asset->thumbnail_path ?? null);
                    }
                });
        }

        if (Schema::hasTable('feed_post_media')) {
            DB::table('feed_post_media')
                ->where('user_id', $user->id)
                ->select(['id', 'disk', 'path'])
                ->orderBy('id')
                ->chunkById(100, function ($mediaRows) use ($files): void {
                    foreach ($mediaRows as $mediaRow) {
                        $disk = filled($mediaRow->disk ?? null) ? (string) $mediaRow->disk : 'public';
                        $this->pushFile($files, $disk, $mediaRow->path ?? null);
                    }
                });
        }

        if (Schema::hasTable('guide_media')) {
            $ownedGuideIds = $this->ownedGuideIds((int) $user->id);
            DB::table('guide_media')
                ->where(function ($query) use ($user, $ownedGuideIds): void {
                    $query->where('uploaded_by', $user->id);

                    if ($ownedGuideIds !== []) {
                        $query->orWhereIn('guide_id', $ownedGuideIds);
                    }
                })
                ->select(['id', 'disk', 'path'])
                ->orderBy('id')
                ->chunkById(100, function ($mediaRows) use ($files): void {
                    foreach ($mediaRows as $mediaRow) {
                        $disk = filled($mediaRow->disk ?? null) ? (string) $mediaRow->disk : 'local';
                        $this->pushFile($files, $disk, $mediaRow->path ?? null);
                    }
                });
        }

        return $files
            ->filter(fn (array $file): bool => $file['path'] !== '')
            ->unique(fn (array $file): string => $file['disk'].'|'.$file['path'])
            ->values();
    }

    private function pushFile(Collection $files, string $disk, ?string $path): void
    {
        $path = trim((string) $path);

        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $files->push([
            'disk' => $disk ?: 'public',
            'path' => ltrim($path, '/'),
        ]);
    }

    /** @param Collection<int,array{disk:string,path:string}> $files */
    private function deleteFiles(Collection $files): array
    {
        $deleted = 0;
        $failed = 0;

        foreach ($files as $file) {
            try {
                Storage::disk($file['disk'])->delete($file['path']);
                $deleted++;
            } catch (Throwable) {
                $failed++;
            }
        }

        return [$deleted, $failed];
    }

    private function writeFinalSecurityEvent(User $user, AccountDeletionRequest $deletionRequest): void
    {
        if (! Schema::hasTable('user_security_events')) {
            return;
        }

        DB::table('user_security_events')->insert([
            'user_id' => $user->id,
            'event' => 'account_deleted_processed',
            'ip_address' => null,
            'user_agent' => null,
            'meta' => json_encode([
                'deletion_request_id' => $deletionRequest->id,
                'requested_at' => $deletionRequest->requested_at?->toIso8601String(),
                'scheduled_for' => $deletionRequest->scheduled_for?->toIso8601String(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function deleteNonCascadingRows(User $user): void
    {
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $user->id)
                ->delete();
        }

        if (Schema::hasTable('reports')) {
            DB::table('reports')
                ->where('reportable_type', User::class)
                ->where('reportable_id', $user->id)
                ->update([
                    'reportable_type' => null,
                    'reportable_id' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * @return array{
     *   owned_guide_ids:array<int,int>,
     *   authored_revision_ids:array<int,int>,
     *   foreign_guide_ids:array<int,int>,
     *   helpful_event_keys:array<int,string>,
     *   deleted_comment_ids:array<int,int>,
     *   guide_media_asset_ids:array<int,int>
     * }
     */
    private function guideDeletionContext(User $user): array
    {
        $empty = [
            'owned_guide_ids' => [],
            'authored_revision_ids' => [],
            'foreign_guide_ids' => [],
            'helpful_event_keys' => [],
            'deleted_comment_ids' => [],
            'guide_media_asset_ids' => [],
        ];

        if (! Schema::hasTable('guides')) {
            return $empty;
        }

        $userId = (int) $user->id;
        $ownedGuideIds = $this->ownedGuideIds($userId);
        $authoredRevisionIds = Schema::hasTable('guide_revisions')
            ? DB::table('guide_revisions')
                ->where('author_id', $userId)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all()
            : [];
        $interactionGuideIds = collect();
        $helpfulEventKeys = collect();
        $deletedCommentIds = collect();
        $guideMediaAssetIds = collect();

        if (Schema::hasTable('guide_comments')) {
            $userComments = DB::table('guide_comments')
                ->where('user_id', $userId)
                ->get(['id', 'guide_id']);
            $interactionGuideIds->push(...$userComments->pluck('guide_id'));
            $deletedCommentIds->push(...$userComments->pluck('id'));

            if ($ownedGuideIds !== []) {
                $deletedCommentIds->push(
                    ...DB::table('guide_comments')->whereIn('guide_id', $ownedGuideIds)->pluck('id')
                );
            }
        }

        if (Schema::hasTable('guide_helpful_votes')) {
            $votes = DB::table('guide_helpful_votes')
                ->where('user_id', $userId)
                ->get(['guide_id']);
            $interactionGuideIds->push(...$votes->pluck('guide_id'));
            $helpfulEventKeys->push(
                ...$votes->pluck('guide_id')->map(
                    fn ($guideId): string => "guide:{$guideId}:helpful:{$userId}"
                )
            );
        }

        if (Schema::hasTable('guide_bookmarks')) {
            $interactionGuideIds->push(
                ...DB::table('guide_bookmarks')->where('user_id', $userId)->pluck('guide_id')
            );
        }

        if (Schema::hasTable('guide_media') && Schema::hasColumn('guide_media', 'media_asset_id')) {
            $guideMediaAssetIds->push(
                ...DB::table('guide_media')
                    ->where(function ($query) use ($userId, $ownedGuideIds): void {
                        $query->where('uploaded_by', $userId);

                        if ($ownedGuideIds !== []) {
                            $query->orWhereIn('guide_id', $ownedGuideIds);
                        }
                    })
                    ->whereNotNull('media_asset_id')
                    ->pluck('media_asset_id')
            );
        }

        $ownedLookup = array_flip($ownedGuideIds);
        $foreignGuideIds = $interactionGuideIds
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0 && ! isset($ownedLookup[$id]))
            ->unique()
            ->values()
            ->all();

        return [
            'owned_guide_ids' => $ownedGuideIds,
            'authored_revision_ids' => $authoredRevisionIds,
            'foreign_guide_ids' => $foreignGuideIds,
            'helpful_event_keys' => $helpfulEventKeys->filter()->unique()->values()->all(),
            'deleted_comment_ids' => $deletedCommentIds->map(fn ($id): int => (int) $id)->filter()->unique()->values()->all(),
            'guide_media_asset_ids' => $guideMediaAssetIds->map(fn ($id): int => (int) $id)->filter()->unique()->values()->all(),
        ];
    }

    /**
     * @param array{
     *   owned_guide_ids:array<int,int>,
     *   authored_revision_ids:array<int,int>,
     *   foreign_guide_ids:array<int,int>,
     *   helpful_event_keys:array<int,string>,
     *   deleted_comment_ids:array<int,int>,
     *   guide_media_asset_ids:array<int,int>
     * } $context
     */
    private function deleteGuideData(User $user, array $context): void
    {
        $userId = (int) $user->id;

        $this->clearReportables(Guide::class, $context['owned_guide_ids']);
        $this->clearReportables(GuideComment::class, $context['deleted_comment_ids']);
        $this->clearReportables(MediaAsset::class, $context['guide_media_asset_ids']);

        if (Schema::hasTable('guide_reputation_entries') && $context['helpful_event_keys'] !== []) {
            DB::table('guide_reputation_entries')
                ->whereIn('event_key', $context['helpful_event_keys'])
                ->delete();
        }

        foreach ([
            ['guide_comments', 'user_id'],
            ['guide_helpful_votes', 'user_id'],
            ['guide_bookmarks', 'user_id'],
        ] as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, $userId)->delete();
            }
        }

        if ($context['authored_revision_ids'] !== [] && Schema::hasTable('guide_revisions')) {
            DB::table('guides')
                ->whereIn('current_published_revision_id', $context['authored_revision_ids'])
                ->update([
                    'current_published_revision_id' => null,
                    'status' => 'draft',
                    'published_at' => null,
                    'updated_at' => now(),
                ]);
            DB::table('guides')
                ->whereIn('working_revision_id', $context['authored_revision_ids'])
                ->update([
                    'working_revision_id' => null,
                    'updated_at' => now(),
                ]);
            DB::table('guide_revisions')
                ->whereIn('id', $context['authored_revision_ids'])
                ->update(['cover_media_id' => null]);

            if (Schema::hasTable('guide_media')) {
                DB::table('guide_media')
                    ->whereIn('revision_id', $context['authored_revision_ids'])
                    ->delete();
            }

            DB::table('guide_revisions')->whereIn('id', $context['authored_revision_ids'])->delete();
        }

        if ($context['owned_guide_ids'] !== []) {
            DB::table('guides')->whereIn('id', $context['owned_guide_ids'])->delete();
        }
    }

    /** @param array<int,int> $guideIds */
    private function recalculateGuideCounters(array $guideIds): void
    {
        if ($guideIds === [] || ! Schema::hasTable('guides')) {
            return;
        }

        foreach ($guideIds as $guideId) {
            if (! DB::table('guides')->where('id', $guideId)->exists()) {
                continue;
            }

            $comments = Schema::hasTable('guide_comments')
                ? DB::table('guide_comments')
                    ->where('guide_id', $guideId)
                    ->when(
                        Schema::hasColumn('guide_comments', 'deleted_at'),
                        fn ($query) => $query->whereNull('deleted_at')
                    )
                    ->count()
                : 0;
            $helpful = Schema::hasTable('guide_helpful_votes')
                ? DB::table('guide_helpful_votes')->where('guide_id', $guideId)->count()
                : 0;
            $bookmarks = Schema::hasTable('guide_bookmarks')
                ? DB::table('guide_bookmarks')->where('guide_id', $guideId)->count()
                : 0;

            DB::table('guides')->where('id', $guideId)->update([
                'comments_count' => $comments,
                'helpful_count' => $helpful,
                'bookmarks_count' => $bookmarks,
                'updated_at' => now(),
            ]);
        }
    }

    /** @param array<int,int> $ids */
    private function clearReportables(string $type, array $ids): void
    {
        if (
            $ids === []
            || ! Schema::hasTable('reports')
            || ! Schema::hasColumn('reports', 'reportable_type')
            || ! Schema::hasColumn('reports', 'reportable_id')
        ) {
            return;
        }

        DB::table('reports')
            ->where('reportable_type', $type)
            ->whereIn('reportable_id', $ids)
            ->update([
                'reportable_type' => null,
                'reportable_id' => null,
                'updated_at' => now(),
            ]);
    }

    private function deleteOrphanConversations(): void
    {
        if (! Schema::hasTable('conversations') || ! Schema::hasTable('conversation_participants')) {
            return;
        }

        DB::table('conversations')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('conversation_participants')
                    ->whereColumn('conversation_participants.conversation_id', 'conversations.id');
            })
            ->delete();
    }

    private function countWhere(string $table, string $column, mixed $value): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table)->where($column, $value)->count();
    }

    /** @return array<int,int> */
    private function ownedGuideIds(int $userId): array
    {
        if (! Schema::hasTable('guides') || ! Schema::hasColumn('guides', 'author_id')) {
            return [];
        }

        return DB::table('guides')
            ->where('author_id', $userId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /** @param array<int,int> $ownedGuideIds */
    private function countGuideRows(
        string $table,
        string $userColumn,
        int $userId,
        array $ownedGuideIds,
        bool $includeHelpfulVoterEvents = false,
    ): int {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $hasUserColumn = Schema::hasColumn($table, $userColumn);
        $hasGuideColumn = Schema::hasColumn($table, 'guide_id');
        $hasEventKey = $includeHelpfulVoterEvents && Schema::hasColumn($table, 'event_key');

        if (! $hasUserColumn && (! $hasGuideColumn || $ownedGuideIds === []) && ! $hasEventKey) {
            return 0;
        }

        return (int) DB::table($table)
            ->where(function ($query) use ($userColumn, $userId, $ownedGuideIds, $hasUserColumn, $hasGuideColumn, $hasEventKey): void {
                if ($hasUserColumn) {
                    $query->where($userColumn, $userId);
                }

                if ($ownedGuideIds !== [] && $hasGuideColumn) {
                    $query->orWhereIn('guide_id', $ownedGuideIds);
                }

                if ($hasEventKey) {
                    $query->orWhere('event_key', 'like', "guide:%:helpful:{$userId}");
                }
            })
            ->count();
    }
}
