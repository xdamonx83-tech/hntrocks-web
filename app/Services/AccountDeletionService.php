<?php

namespace App\Services;

use App\Models\AccountDeletionRequest;
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
            $this->writeFinalSecurityEvent($user, $deletionRequest);
            $this->deleteNonCascadingRows($user);

            $user->delete();

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
}
