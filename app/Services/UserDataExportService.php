<?php

namespace App\Services;

use App\Models\Cup;
use App\Models\CupSubmission;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\LfgPost;
use App\Models\MediaAsset;
use App\Models\Moment;
use App\Models\MomentComment;
use App\Models\Team;
use App\Models\TeamLfgPost;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserDataExportService
{
    private const EXPORT_VERSION = 2;

    /** @var array<string,array<int,string>> */
    private array $columnCache = [];

    /**
     * Build a broad machine-readable account export from the tables that exist in the current installation.
     *
     * The export intentionally avoids secrets such as password hashes, remember tokens, session payloads and
     * API token hashes. It does include stored profile/content/activity rows and security metadata belonging
     * to the authenticated account.
     *
     * @return array<string,mixed>
     */
    public function build(User $user, ?Request $request = null): array
    {
        $userId = (int) $user->id;
        $email = (string) $user->email;

        $account = $this->singleRow('users', 'id', $userId);
        $feedPosts = $this->rowsWhere('feed_posts', 'user_id', $userId);
        $feedPostIds = $this->ids($feedPosts);
        $feedComments = $this->rowsWhere('feed_comments', 'user_id', $userId);
        $feedCommentIds = $this->ids($feedComments);

        $lfgPosts = $this->rowsWhere('lfg_posts', 'user_id', $userId);
        $lfgPostIds = $this->ids($lfgPosts);
        $teamLfgPosts = $this->rowsWhere('team_lfg_posts', 'user_id', $userId);
        $teamLfgPostIds = $this->ids($teamLfgPosts);

        $ownedTeams = $this->rowsWhere('teams', 'owner_id', $userId);
        $ownedTeamIds = $this->ids($ownedTeams);
        $teamMemberships = $this->rowsWhere('team_members', 'user_id', $userId);
        $memberTeamIds = $this->values($teamMemberships, 'team_id');
        $teamIds = $this->uniqueIds(array_merge($ownedTeamIds, $memberTeamIds));

        $mediaAssets = $this->rowsWhere('media_assets', 'user_id', $userId);
        $mediaAssetIds = $this->ids($mediaAssets);

        $moments = $this->rowsWhere('moments', 'user_id', $userId);
        $momentIds = $this->ids($moments);
        $momentComments = $this->rowsWhere('moment_comments', 'user_id', $userId);
        $momentCommentIds = $this->ids($momentComments);

        $ownedCups = $this->rowsWhere('cups', 'owner_id', $userId);
        $ownedCupIds = $this->ids($ownedCups);
        $ownedCupTeams = $this->rowsWhere('cup_teams', 'owner_id', $userId);
        $ownedCupTeamIds = $this->ids($ownedCupTeams);
        $cupTeamMemberships = $this->rowsWhere('cup_team_members', 'user_id', $userId);
        $memberCupTeamIds = $this->values($cupTeamMemberships, 'cup_team_id');
        $cupTeamIds = $this->uniqueIds(array_merge($ownedCupTeamIds, $memberCupTeamIds));
        $cupTeamsJoinedOrOwned = $this->rowsWhereIn('cup_teams', 'id', $cupTeamIds);
        $cupSubmissions = $this->rowsWhere('cup_submissions', 'submitted_by', $userId);
        $cupSubmissionIds = $this->ids($cupSubmissions);
        $participatedCupIds = $this->uniqueIds(array_merge(
            $this->values($ownedCupTeams, 'cup_id'),
            $this->values($cupTeamsJoinedOrOwned, 'cup_id'),
            $this->values($cupSubmissions, 'cup_id')
        ));

        $conversationParticipants = $this->rowsWhere('conversation_participants', 'user_id', $userId);
        $conversationIds = $this->values($conversationParticipants, 'conversation_id');

        $friendshipRows = $this->rowsForFriendships($userId);
        $blockRows = $this->rowsForBlocks($userId);

        $mentionsCreated = $this->rowsWhere('mentions', 'mentioner_id', $userId);
        $mentionsReceived = $this->rowsWhere('mentions', 'mentioned_user_id', $userId);

        $payload = [
            'meta' => [
                'export_version' => self::EXPORT_VERSION,
                'generated_at' => now()->toIso8601String(),
                'application' => config('app.name', 'hnt.rocks'),
                'subject_user_id' => $userId,
                'subject_username' => (string) $user->username,
                'format' => 'json',
                'contains_binary_files' => false,
                'note' => 'Dieser Export enthält die im System strukturiert gespeicherten Account-, Profil-, Inhalts-, Aktivitäts-, Sicherheits- und Moderationsdaten. Hochgeladene Binärdateien werden als Datei-Referenzen exportiert, nicht als Base64-Dateien.',
            ],
            'account' => [
                'user' => $account,
                'profile' => $this->singleRow('user_profiles', 'user_id', $userId),
                'privacy_settings' => $this->singleRow('user_privacy_settings', 'user_id', $userId),
                'notification_settings' => $this->singleRow('user_notification_settings', 'user_id', $userId),
                'social_accounts' => $this->rowsWhere('social_accounts', 'user_id', $userId),
                'api_access_tokens' => $this->rowsWhere('api_access_tokens', 'user_id', $userId),
                'push_devices' => $this->rowsWhere('user_push_devices', 'user_id', $userId),
                'account_deletion_request' => $this->singleRow('account_deletion_requests', 'user_id', $userId),
            ],
            'security_and_sessions' => [
                'security_events' => $this->rowsWhere('user_security_events', 'user_id', $userId),
                'sessions' => $this->rowsWhere('sessions', 'user_id', $userId),
                'password_reset_token_present' => $this->existsWhere('password_reset_tokens', 'email', $email),
            ],
            'profile_and_media' => [
                'media_assets' => $mediaAssets,
                'file_references' => $this->fileReferences($account, $ownedTeams, $ownedCups, $mediaAssets, $feedPosts),
            ],
            'feed' => [
                'posts' => $feedPosts,
                'post_media' => $this->feedPostMediaRows($userId, $feedPostIds),
                'comments' => $feedComments,
                'reactions_by_user' => $this->rowsWhere('feed_reactions', 'user_id', $userId),
                'bookmarks' => $this->rowsWhere('feed_bookmarks', 'user_id', $userId),
                'comment_reactions_by_user' => $this->rowsWhere('feed_comment_reactions', 'user_id', $userId),
                'comment_reactions_on_user_comments' => $this->rowsWhereIn('feed_comment_reactions', 'feed_comment_id', $feedCommentIds),
            ],
            'mentions' => [
                'created_by_user' => $mentionsCreated,
                'received_by_user' => $mentionsReceived,
            ],
            'lfg' => [
                'posts' => $lfgPosts,
                'applications_by_user' => $this->rowsWhere('lfg_applications', 'user_id', $userId),
                'applications_to_user_posts' => $this->rowsWhereIn('lfg_applications', 'lfg_post_id', $lfgPostIds),
            ],
            'teams' => [
                'owned_teams' => $ownedTeams,
                'team_memberships' => $teamMemberships,
                'teams_joined_or_owned' => $this->rowsWhereIn('teams', 'id', $teamIds),
            ],
            'team_lfg' => [
                'posts' => $teamLfgPosts,
                'applications_by_user' => $this->rowsWhere('team_lfg_applications', 'user_id', $userId),
                'applications_to_user_posts' => $this->rowsWhereIn('team_lfg_applications', 'team_lfg_post_id', $teamLfgPostIds),
                'applications_for_user_teams' => $this->rowsWhereIn('team_lfg_applications', 'team_id', $ownedTeamIds),
            ],
            'messages' => [
                'conversation_participations' => $conversationParticipants,
                'conversations' => $this->rowsWhereIn('conversations', 'id', $conversationIds),
                'participants_in_those_conversations' => $this->rowsWhereIn('conversation_participants', 'conversation_id', $conversationIds),
                'messages_in_those_conversations' => $this->rowsWhereIn('messages', 'conversation_id', $conversationIds),
                'messages_written_by_user' => $this->rowsWhere('messages', 'user_id', $userId),
            ],
            'notifications' => [
                'received' => $this->rowsWhere('user_notifications', 'user_id', $userId),
                'created_with_user_as_actor' => $this->rowsWhere('user_notifications', 'actor_id', $userId),
                'laravel_notification_compat_rows' => $this->laravelNotificationRows($userId),
            ],
            'friends_and_blocks' => [
                'friendships' => $friendshipRows,
                'blocked_users' => $blockRows['blocked_users'],
                'blocked_by_users' => $blockRows['blocked_by_users'],
            ],
            'moments' => [
                'moments' => $moments,
                'comments' => $momentComments,
                'reactions_by_user' => $this->rowsWhere('moment_reactions', 'user_id', $userId),
                'bookmarks' => $this->rowsWhere('moment_bookmarks', 'user_id', $userId),
            ],
            'cups' => [
                'owned_cups' => $ownedCups,
                'participated_cups' => $this->rowsWhereIn('cups', 'id', $participatedCupIds),
                'owned_cup_teams' => $ownedCupTeams,
                'cup_team_memberships' => $cupTeamMemberships,
                'cup_teams_joined_or_owned' => $cupTeamsJoinedOrOwned,
                'submissions' => $cupSubmissions,
            ],
            'gamification' => [
                'xp_events' => $this->rowsWhere('xp_events', 'user_id', $userId),
                'badges' => $this->badgeRows($userId),
                'quest_progress' => $this->questProgressRows($userId),
            ],
            'referrals_and_giveaways' => [
                'referral_link' => $this->singleRow('referral_links', 'user_id', $userId),
                'referral_signups_as_referrer' => $this->rowsWhere('referral_signups', 'referrer_id', $userId),
                'referral_signup_as_referred_user' => $this->singleRow('referral_signups', 'referred_user_id', $userId),
                'giveaway_entries' => $this->rowsWhere('giveaway_entries', 'user_id', $userId),
            ],
            'reports_and_moderation' => [
                'reports_submitted_by_user' => $this->rowsWhere('reports', 'reporter_id', $userId),
                'reports_about_user_or_user_content' => $this->reportsAboutUserContent($userId, [
                    User::class => [$userId],
                    FeedPost::class => $feedPostIds,
                    FeedComment::class => $feedCommentIds,
                    Team::class => $ownedTeamIds,
                    LfgPost::class => $lfgPostIds,
                    TeamLfgPost::class => $teamLfgPostIds,
                    MediaAsset::class => $mediaAssetIds,
                    Moment::class => $momentIds,
                    MomentComment::class => $momentCommentIds,
                    Cup::class => $ownedCupIds,
                    CupSubmission::class => $cupSubmissionIds,
                ]),
                'reports_assigned_to_user' => $user->isAdmin() ? $this->rowsWhere('reports', 'assigned_to', $userId) : [],
                'reports_resolved_by_user' => $user->isAdmin() ? $this->rowsWhere('reports', 'resolved_by', $userId) : [],
            ],
            'tracking_and_analytics' => [
                'visitor_events_linked_to_user' => $this->rowsWhere('visitor_events', 'user_id', $userId),
            ],
            'counts' => [],
        ];

        $payload['counts'] = $this->sectionCounts($payload);

        return $payload;
    }

    public function filenameFor(User $user): string
    {
        $username = Str::slug((string) ($user->username ?: 'user')) ?: 'user';

        return 'hnt-rocks-datenexport-'.$username.'-'.now()->format('Y-m-d').'.json';
    }

    /** @return array<string,mixed>|null */
    private function singleRow(string $table, string $column, mixed $value): ?array
    {
        $rows = $this->rowsWhere($table, $column, $value);

        return $rows[0] ?? null;
    }

    /** @return array<int,array<string,mixed>> */
    private function rowsWhere(string $table, string $column, mixed $value): array
    {
        if (! $this->hasTable($table) || ! $this->hasColumn($table, $column)) {
            return [];
        }

        return $this->rows($table, function (Builder $query) use ($column, $value): void {
            $query->where($column, $value);
        });
    }

    /** @return array<int,array<string,mixed>> */
    private function rowsWhereIn(string $table, string $column, array $values): array
    {
        $values = $this->uniqueIds($values);

        if ($values === [] || ! $this->hasTable($table) || ! $this->hasColumn($table, $column)) {
            return [];
        }

        return $this->rows($table, function (Builder $query) use ($column, $values): void {
            $query->whereIn($column, $values);
        });
    }

    /** @return array<int,array<string,mixed>> */
    private function rows(string $table, callable $scope): array
    {
        if (! $this->hasTable($table)) {
            return [];
        }

        $query = DB::table($table);
        $scope($query);
        $this->applyOrder($query, $table);

        return $query->get()->map(function (object $row) use ($table): array {
            return $this->sanitizeRow($table, (array) $row);
        })->values()->all();
    }

    private function existsWhere(string $table, string $column, mixed $value): bool
    {
        if (! $this->hasTable($table) || ! $this->hasColumn($table, $column)) {
            return false;
        }

        return DB::table($table)->where($column, $value)->exists();
    }

    private function applyOrder(Builder $query, string $table): void
    {
        if ($this->hasColumn($table, 'id')) {
            $query->orderBy('id');

            return;
        }

        if ($this->hasColumn($table, 'created_at')) {
            $query->orderBy('created_at');
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function feedPostMediaRows(int $userId, array $feedPostIds): array
    {
        if (! $this->hasTable('feed_post_media')) {
            return [];
        }

        return $this->rows('feed_post_media', function (Builder $query) use ($userId, $feedPostIds): void {
            $query->where('user_id', $userId);

            if ($feedPostIds !== [] && $this->hasColumn('feed_post_media', 'feed_post_id')) {
                $query->orWhereIn('feed_post_id', $feedPostIds);
            }
        });
    }

    /** @return array<int,array<string,mixed>> */
    private function rowsForFriendships(int $userId): array
    {
        if (! $this->hasTable('friendships')) {
            return [];
        }

        return $this->rows('friendships', function (Builder $query) use ($userId): void {
            $query->where('user_one_id', $userId)
                ->orWhere('user_two_id', $userId)
                ->orWhere('requester_id', $userId)
                ->orWhere('recipient_id', $userId);
        });
    }

    /** @return array{blocked_users:array<int,array<string,mixed>>,blocked_by_users:array<int,array<string,mixed>>} */
    private function rowsForBlocks(int $userId): array
    {
        return [
            'blocked_users' => $this->rowsWhere('user_blocks', 'user_id', $userId),
            'blocked_by_users' => $this->rowsWhere('user_blocks', 'blocked_user_id', $userId),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function laravelNotificationRows(int $userId): array
    {
        if (! $this->hasTable('notifications') || ! $this->hasColumn('notifications', 'notifiable_type') || ! $this->hasColumn('notifications', 'notifiable_id')) {
            return [];
        }

        return $this->rows('notifications', function (Builder $query) use ($userId): void {
            $query->where('notifiable_type', User::class)->where('notifiable_id', $userId);
        });
    }

    /** @return array{pivot_rows:array<int,array<string,mixed>>,badge_definitions:array<int,array<string,mixed>>} */
    private function badgeRows(int $userId): array
    {
        if (! $this->hasTable('badge_user')) {
            return [];
        }

        $rows = $this->rowsWhere('badge_user', 'user_id', $userId);
        $badgeIds = $this->values($rows, 'badge_id');

        return [
            'pivot_rows' => $rows,
            'badge_definitions' => $this->rowsWhereIn('badges', 'id', $badgeIds),
        ];
    }

    /** @return array<string,array<int,array<string,mixed>>> */
    private function questProgressRows(int $userId): array
    {
        if (! $this->hasTable('quest_user')) {
            return [
                'pivot_rows' => [],
                'quest_definitions' => [],
            ];
        }

        $rows = $this->rowsWhere('quest_user', 'user_id', $userId);
        $questIds = $this->values($rows, 'quest_id');

        return [
            'pivot_rows' => $rows,
            'quest_definitions' => $this->rowsWhereIn('quests', 'id', $questIds),
        ];
    }

    /**
     * @param array<class-string,array<int,int>> $idsByClass
     * @return array<int,array<string,mixed>>
     */
    private function reportsAboutUserContent(int $userId, array $idsByClass): array
    {
        if (! $this->hasTable('reports') || ! $this->hasColumn('reports', 'reportable_type') || ! $this->hasColumn('reports', 'reportable_id')) {
            return [];
        }

        $idsByClass = array_filter($idsByClass, static fn (array $ids): bool => $ids !== []);

        if ($idsByClass === []) {
            return [];
        }

        return $this->rows('reports', function (Builder $query) use ($idsByClass): void {
            $query->where(function (Builder $outer) use ($idsByClass): void {
                foreach ($idsByClass as $class => $ids) {
                    $outer->orWhere(function (Builder $inner) use ($class, $ids): void {
                        $inner->where('reportable_type', $class)
                            ->whereIn('reportable_id', $this->uniqueIds($ids));
                    });
                }
            });
        });
    }

    /**
     * @param array<string,mixed>|null $account
     * @param array<int,array<string,mixed>> $ownedTeams
     * @param array<int,array<string,mixed>> $ownedCups
     * @param array<int,array<string,mixed>> $mediaAssets
     * @param array<int,array<string,mixed>> $feedPosts
     * @return array<int,array{source:string,disk:string,path:string|null,thumbnail_path?:string|null,visibility?:string|null,status?:string|null,type?:string|null,mime_type?:string|null,size_bytes?:mixed,original_name?:mixed}>
     */
    private function fileReferences(?array $account, array $ownedTeams, array $ownedCups, array $mediaAssets, array $feedPosts): array
    {
        $files = [];

        foreach ([['avatar_path', 'user.avatar'], ['cover_path', 'user.cover']] as [$column, $source]) {
            if (! empty($account[$column])) {
                $files[] = [
                    'source' => $source,
                    'disk' => 'public',
                    'path' => (string) $account[$column],
                ];
            }
        }

        foreach ($ownedTeams as $team) {
            foreach ([['avatar_path', 'team.avatar'], ['cover_path', 'team.cover']] as [$column, $source]) {
                if (! empty($team[$column])) {
                    $files[] = [
                        'source' => $source,
                        'disk' => 'public',
                        'path' => (string) $team[$column],
                    ];
                }
            }
        }

        foreach ($ownedCups as $cup) {
            if (! empty($cup['cover_path'])) {
                $files[] = [
                    'source' => 'cup.cover',
                    'disk' => 'public',
                    'path' => (string) $cup['cover_path'],
                ];
            }
        }

        foreach ($mediaAssets as $asset) {
            $disk = (string) ($asset['disk'] ?? 'public') ?: 'public';
            if (! empty($asset['path'])) {
                $files[] = [
                    'source' => 'media_asset.path',
                    'disk' => $disk,
                    'path' => (string) $asset['path'],
                    'thumbnail_path' => $asset['thumbnail_path'] ?? null,
                    'visibility' => $asset['visibility'] ?? null,
                    'status' => $asset['status'] ?? null,
                    'type' => $asset['type'] ?? null,
                    'mime_type' => $asset['mime_type'] ?? null,
                    'size_bytes' => $asset['size_bytes'] ?? null,
                    'original_name' => $asset['original_name'] ?? null,
                ];
            }
        }

        foreach ($feedPosts as $post) {
            if (! empty($post['path'])) {
                $files[] = [
                    'source' => 'feed_post.path',
                    'disk' => (string) ($post['disk'] ?? 'public') ?: 'public',
                    'path' => (string) $post['path'],
                    'mime_type' => $post['mime_type'] ?? null,
                    'size_bytes' => $post['size_bytes'] ?? null,
                    'original_name' => $post['original_name'] ?? null,
                ];
            }
        }

        $seen = [];

        return collect($files)
            ->filter(fn (array $file): bool => ! empty($file['path']) && ! Str::startsWith((string) $file['path'], ['http://', 'https://']))
            ->filter(function (array $file) use (&$seen): bool {
                $key = ($file['disk'] ?? 'public').'|'.($file['path'] ?? '');
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;

                return true;
            })
            ->values()
            ->all();
    }

    /** @return array<int,int> */
    private function ids(array $rows): array
    {
        return $this->values($rows, 'id');
    }

    /** @return array<int,int> */
    private function values(array $rows, string $column): array
    {
        return $this->uniqueIds(array_map(static fn (array $row): mixed => $row[$column] ?? null, $rows));
    }

    /** @return array<int,int> */
    private function uniqueIds(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(static function (mixed $value): int {
            return is_numeric($value) ? (int) $value : 0;
        }, $values), static fn (int $value): bool => $value > 0)));
    }

    /** @return array<string,mixed> */
    private function sanitizeRow(string $table, array $row): array
    {
        foreach ($this->sensitiveColumnsFor($table) as $column) {
            unset($row[$column]);
        }

        foreach ($row as $column => $value) {
            if (is_string($value) && $this->looksLikeJsonColumn($column, $value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row[$column] = $decoded;
                }
            }
        }

        return $row;
    }

    /** @return array<int,string> */
    private function sensitiveColumnsFor(string $table): array
    {
        $global = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

        $byTable = [
            'sessions' => ['payload'],
            'password_reset_tokens' => ['token'],
            'api_access_tokens' => ['token_hash'],
            'user_push_devices' => ['token'],
            'user_two_factor_challenges' => ['token_hash', 'secret'],
        ];

        return array_values(array_unique(array_merge($global, $byTable[$table] ?? [])));
    }

    private function looksLikeJsonColumn(string $column, string $value): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '' || ! in_array($trimmed[0], ['{', '['], true)) {
            return false;
        }

        return in_array($column, [
            'metadata',
            'meta',
            'settings',
            'rules',
            'data',
            'raw_profile',
            'ai_raw_result',
            'abilities',
        ], true);
    }

    /** @return array<string,int> */
    private function sectionCounts(array $payload): array
    {
        $counts = [];

        foreach ($payload as $section => $value) {
            if ($section === 'counts') {
                continue;
            }

            $counts[$section] = $this->countRowsRecursively($value);
        }

        return $counts;
    }

    private function countRowsRecursively(mixed $value): int
    {
        if (! is_array($value)) {
            return 0;
        }

        if ($value !== [] && array_is_list($value)) {
            return count($value);
        }

        $count = 0;
        foreach ($value as $child) {
            $count += $this->countRowsRecursively($child);
        }

        return $count;
    }

    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    /** @return array<int,string> */
    private function columns(string $table): array
    {
        if (! isset($this->columnCache[$table])) {
            $this->columnCache[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        return $this->columnCache[$table];
    }
}
