<?php

namespace App\Services;

use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\LfgPost;
use App\Models\Friendship;
use App\Models\TeamLfgPost;
use App\Models\Mention;
use App\Models\Team;
use App\Models\User;
use App\Support\MentionRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MentionService
{
    public function __construct(private readonly UserBlockService $blocks)
    {
    }

    public function searchableUsers(User $actor, string $query = '', string $context = 'feed', int $limit = 8, ?Team $team = null): Collection
    {
        if ($this->isStrictTeamContext($context)) {
            return $this->searchableTeamUsers($actor, $team, $query, $limit);
        }

        if ($context === 'team_lfg' && $team) {
            return $this->searchableTeamUsers($actor, $team, $query, $limit);
        }

        $friendIds = $this->acceptedFriendIds($actor);

        if ($friendIds->isEmpty()) {
            return collect();
        }

        $query = trim($query);

        return $this->blocks->applyToUserQuery(
            User::query()->with('profile')->whereIn('id', $friendIds->all()),
            $actor
        )
            ->when($query !== '', function ($userQuery) use ($query): void {
                $like = str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';
                $contains = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';

                $userQuery->where(function ($inner) use ($like, $contains): void {
                    $inner->where('username', 'like', $like)
                        ->orWhere('name', 'like', $contains);
                });
            })
            ->orderBy('username')
            ->limit(max(1, min(20, $limit)))
            ->get(['id', 'name', 'username', 'avatar_path', 'level']);
    }

    public function syncForFeedPost(FeedPost $post, User $actor, ?string $body, NotificationService $notifications): void
    {
        $post->loadMissing('team.members');

        $context = $post->isTeamPost() ? 'team_feed' : 'feed_post';
        $team = $post->isTeamPost() ? $post->team : null;

        $this->sync($post, $actor, $body, $notifications, $context, $post, $team);
    }

    public function syncForFeedComment(FeedComment $comment, User $actor, ?string $body, NotificationService $notifications): void
    {
        $comment->loadMissing('post.team.members');

        $post = $comment->post;
        $context = $post?->isTeamPost() ? 'team_feed_comment' : 'feed_comment';
        $team = $post?->isTeamPost() ? $post->team : null;

        $this->sync($comment, $actor, $body, $notifications, $context, $post, $team);
    }

    public function syncForLfgPost(LfgPost $post, User $actor, ?string $body, NotificationService $notifications): void
    {
        $this->sync($post, $actor, $post->visibility === 'public' ? $body : null, $notifications, 'lfg', null, null);
    }

    public function syncForTeamLfgPost(TeamLfgPost $post, User $actor, ?string $body, NotificationService $notifications): void
    {
        $post->loadMissing('team.members');

        $team = $post->visibility === 'public' && $post->isTeamSeekingPlayers() ? $post->team : null;

        $this->sync($post, $actor, $post->visibility === 'public' ? $body : null, $notifications, 'team_lfg', null, $team);
    }

    private function sync(Model $mentionable, User $actor, ?string $body, NotificationService $notifications, string $context, ?FeedPost $post = null, ?Team $team = null): void
    {
        $allowedUsers = $this->mentionedAllowedUsers($actor, $body, $context, $team);
        $newUserIds = $allowedUsers->pluck('id')->map(fn ($id): int => (int) $id)->unique()->values();

        $existingMentionedIds = Mention::query()
            ->where('mentionable_type', $mentionable->getMorphClass())
            ->where('mentionable_id', $mentionable->getKey())
            ->pluck('mentioned_user_id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $toDelete = $existingMentionedIds->diff($newUserIds)->values();
        $toCreate = $newUserIds->diff($existingMentionedIds)->values();

        DB::transaction(function () use ($mentionable, $actor, $context, $toDelete, $toCreate): void {
            if ($toDelete->isNotEmpty()) {
                Mention::query()
                    ->where('mentionable_type', $mentionable->getMorphClass())
                    ->where('mentionable_id', $mentionable->getKey())
                    ->whereIn('mentioned_user_id', $toDelete->all())
                    ->delete();
            }

            foreach ($toCreate as $mentionedUserId) {
                Mention::query()->firstOrCreate([
                    'mentionable_type' => $mentionable->getMorphClass(),
                    'mentionable_id' => $mentionable->getKey(),
                    'mentioned_user_id' => $mentionedUserId,
                ], [
                    'mentioner_id' => $actor->id,
                    'context' => $context,
                ]);
            }
        });

        if ($toCreate->isEmpty()) {
            return;
        }

        $post = $post ?: ($mentionable instanceof FeedPost ? $mentionable : null);
        $url = $this->actionUrl($mentionable, $post);
        $usersById = $allowedUsers->keyBy('id');

        foreach ($toCreate as $mentionedUserId) {
            $recipient = $usersById->get($mentionedUserId);

            if (! $recipient) {
                continue;
            }

            [$type, $bodyText] = $this->notificationCopy($context, $actor);

            $notifications->send(
                $recipient,
                $actor,
                $type,
                'Neue Erwähnung',
                $bodyText,
                $url
            );
        }
    }

    private function mentionedAllowedUsers(User $actor, ?string $body, string $context = 'feed', ?Team $team = null): Collection
    {
        $usernames = MentionRenderer::extractUsernames($body);

        if ($usernames === []) {
            return collect();
        }

        if ($this->isStrictTeamContext($context)) {
            return $this->allowedTeamMentionUsers($actor, $team, $usernames);
        }

        if ($context === 'team_lfg' && $team) {
            return $this->allowedTeamMentionUsers($actor, $team, $usernames);
        }

        $friendIds = $this->acceptedFriendIds($actor);

        if ($friendIds->isEmpty()) {
            return collect();
        }

        return $this->blocks->applyToUserQuery(
            User::query()->whereIn('id', $friendIds->all()),
            $actor
        )
            ->whereIn(DB::raw('LOWER(username)'), $usernames)
            ->get(['id', 'name', 'username', 'avatar_path', 'level'])
            ->filter(fn (User $user): bool => in_array(Str::lower($user->username), $usernames, true))
            ->values();
    }

    private function searchableTeamUsers(User $actor, ?Team $team, string $query, int $limit): Collection
    {
        if (! $team) {
            return collect();
        }

        $team->loadMissing('members');

        if (! $team->isActiveMember($actor)) {
            return collect();
        }

        $memberIds = $team->members
            ->where('status', 'active')
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => $id === (int) $actor->id)
            ->unique()
            ->values();

        if ($memberIds->isEmpty()) {
            return collect();
        }

        $query = trim($query);

        return $this->blocks->applyToUserQuery(
            User::query()->with('profile')->whereIn('id', $memberIds->all()),
            $actor
        )
            ->when($query !== '', function ($userQuery) use ($query): void {
                $like = str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';
                $contains = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';

                $userQuery->where(function ($inner) use ($like, $contains): void {
                    $inner->where('username', 'like', $like)
                        ->orWhere('name', 'like', $contains);
                });
            })
            ->orderBy('username')
            ->limit(max(1, min(20, $limit)))
            ->get(['id', 'name', 'username', 'avatar_path', 'level']);
    }

    private function allowedTeamMentionUsers(User $actor, ?Team $team, array $usernames): Collection
    {
        if (! $team) {
            return collect();
        }

        $team->loadMissing('members');

        if (! $team->isActiveMember($actor)) {
            return collect();
        }

        $memberIds = $team->members
            ->where('status', 'active')
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => $id === (int) $actor->id)
            ->unique()
            ->values();

        if ($memberIds->isEmpty()) {
            return collect();
        }

        return $this->blocks->applyToUserQuery(
            User::query()->whereIn('id', $memberIds->all()),
            $actor
        )
            ->whereIn(DB::raw('LOWER(username)'), $usernames)
            ->get(['id', 'name', 'username', 'avatar_path', 'level'])
            ->filter(fn (User $user): bool => in_array(Str::lower($user->username), $usernames, true))
            ->values();
    }

    private function acceptedFriendIds(User $actor): Collection
    {
        $actorId = (int) $actor->id;

        return Friendship::query()
            ->forUser($actor)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->get(['user_one_id', 'user_two_id'])
            ->map(fn (Friendship $friendship): int => (int) ($friendship->user_one_id === $actorId ? $friendship->user_two_id : $friendship->user_one_id))
            ->filter()
            ->unique()
            ->values();
    }

    private function isStrictTeamContext(string $context): bool
    {
        return in_array($context, ['team_feed', 'team_feed_comment'], true);
    }

    private function notificationCopy(string $context, User $actor): array
    {
        return match ($context) {
            'team_feed' => ['team_feed_mention', $actor->name.' hat dich in einem Team-Beitrag erwähnt.'],
            'team_feed_comment' => ['team_feed_comment_mention', $actor->name.' hat dich in einem Team-Kommentar erwähnt.'],
            'team_lfg' => ['team_lfg_mention', $actor->name.' hat dich in einem Team-LFG erwähnt.'],
            'lfg' => ['lfg_mention', $actor->name.' hat dich in einem LFG erwähnt.'],
            'feed_comment' => ['feed_comment_mention', $actor->name.' hat dich in einem Kommentar erwähnt.'],
            default => ['feed_mention', $actor->name.' hat dich in einem Beitrag erwähnt.'],
        };
    }

    private function actionUrl(Model $mentionable, ?FeedPost $post): ?string
    {
        if ($mentionable instanceof FeedComment) {
            $post = $post ?: $mentionable->post;

            return $post ? $post->permalink($mentionable) : null;
        }

        if ($mentionable instanceof FeedPost) {
            return $mentionable->permalink();
        }

        if ($mentionable instanceof LfgPost) {
            return route('lfg.show', $mentionable);
        }

        if ($mentionable instanceof TeamLfgPost) {
            return route('team-lfg.show', $mentionable);
        }

        return null;
    }
}
