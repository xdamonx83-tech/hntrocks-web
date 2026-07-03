<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\Cup;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\FeedReaction;
use App\Models\Friendship;
use App\Models\LfgPost;
use App\Models\Moment;
use App\Models\User;

class ReworkFeedSidebar
{
    public static function forViewer(?User $viewer): array
    {
        if (! $viewer) {
            return [
                'members' => collect(),
                'profileStats' => [],
                'crownsSummary' => ['balance' => 0, 'enabled' => false],
                'highlightTopPost' => null,
                'highlightLfg' => null,
                'highlightCup' => null,
            ];
        }

        $viewerId = (int) $viewer->id;

        $friendshipExcludedIds = Friendship::query()
            ->forUser($viewer)
            ->whereIn('status', [Friendship::STATUS_ACCEPTED, Friendship::STATUS_PENDING])
            ->get(['user_one_id', 'user_two_id'])
            ->map(fn (Friendship $friendship): int => (int) ($friendship->user_one_id === $viewerId ? $friendship->user_two_id : $friendship->user_one_id))
            ->push($viewerId)
            ->unique()
            ->values();

        $members = User::query()
            ->with('profile')
            ->where('status', 'active')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->whereNotIn('id', $friendshipExcludedIds->all())
            ->whereHas('profile', function ($profileQuery) use ($viewerId): void {
                $profileQuery->where(function ($visibilityQuery) use ($viewerId): void {
                    $visibilityQuery
                        ->whereIn('profile_visibility', ['public', 'registered'])
                        ->orWhere('user_id', $viewerId);
                });
            })
            ->whereDoesntHave('blockedUsers', fn ($query) => $query->where('blocked_user_id', $viewerId))
            ->whereDoesntHave('blockedByUsers', fn ($query) => $query->where('user_id', $viewerId))
            ->inRandomOrder()
            ->limit(3)
            ->get();

        $viewer->loadMissing('crownWallet');

        return [
            'members' => $members,
            'profileStats' => [
                'xp' => (int) ($viewer->xp_total ?? 0),
                'posts' => FeedPost::query()
                    ->where('user_id', $viewerId)
                    ->where('status', 'published')
                    ->count(),
                'reactions' => FeedReaction::query()
                    ->whereHas('post', fn ($query) => $query->where('user_id', $viewerId))
                    ->count(),
                'comments' => FeedComment::query()
                    ->where('user_id', $viewerId)
                    ->count(),
                'moments' => Moment::query()
                    ->where('user_id', $viewerId)
                    ->published()
                    ->count(),
                'friends' => Friendship::query()
                    ->forUser($viewer)
                    ->where('status', Friendship::STATUS_ACCEPTED)
                    ->count(),
                'lfg' => LfgPost::query()
                    ->where('user_id', $viewerId)
                    ->count(),
            ],
            'crownsSummary' => [
                'balance' => (int) ($viewer->crownWallet?->balance ?? 0),
                'enabled' => $viewer->crownWallet !== null,
            ],
            'highlightTopPost' => self::highlightTopPost($viewerId, true) ?: self::highlightTopPost($viewerId, false),
            'highlightLfg' => LfgPost::query()
                ->with('user.profile')
                ->where('status', 'open')
                ->where('visibility', 'public')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->first(),
            'highlightCup' => Cup::query()
                ->withCount('activeTeams')
                ->where('visibility', 'public')
                ->whereIn('status', ['active', 'planned'])
                ->where(function ($query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->orderByRaw("case when status = 'active' then 0 else 1 end")
                ->orderBy('starts_at')
                ->latest('id')
                ->first(),
        ];
    }

    public static function headerData(?User $viewer): array
    {
        if (! $viewer) {
            return [
                'notificationsUnread' => 0,
                'notifications' => collect(),
                'friendRequests' => collect(),
                'friendRequestCount' => 0,
                'messageConversations' => collect(),
                'messagesUnread' => 0,
            ];
        }

        return [
            'notificationsUnread' => $viewer->notificationItems()->standard()->unread()->count(),
            'notifications' => $viewer->notificationItems()
                ->standard()
                ->with('actor.profile')
                ->orderByRaw('read_at is not null')
                ->latest()
                ->limit(5)
                ->get(),
            'friendRequests' => Friendship::query()
                ->where('recipient_id', $viewer->id)
                ->where('status', Friendship::STATUS_PENDING)
                ->with('requester.profile')
                ->latest()
                ->limit(5)
                ->get(),
            'friendRequestCount' => Friendship::query()
                ->where('recipient_id', $viewer->id)
                ->where('status', Friendship::STATUS_PENDING)
                ->count(),
            'messageConversations' => Conversation::query()
                ->forUser($viewer)
                ->where('type', 'private')
                ->with(['users.profile', 'users.privacySettings', 'latestMessage.user'])
                ->latest('updated_at')
                ->limit(5)
                ->get(),
            'messagesUnread' => method_exists($viewer, 'unreadMessagesCount') ? $viewer->unreadMessagesCount() : 0,
        ];
    }

    private static function highlightTopPost(int $viewerId, bool $recentOnly): ?FeedPost
    {
        return FeedPost::query()
            ->with(['user.profile'])
            ->withCount(['comments', 'reactions'])
            ->withCount('sharedByPosts as shares_count')
            ->where('status', 'published')
            ->where(function ($query) use ($viewerId): void {
                $query->where(function ($normalPosts) use ($viewerId): void {
                    $normalPosts->whereNull('team_id')
                        ->where(function ($visibility) use ($viewerId): void {
                            $visibility->where('visibility', '!=', 'private')
                                ->orWhere('user_id', $viewerId);
                        });
                })->orWhere(function ($teamPosts) use ($viewerId): void {
                    $teamPosts->whereNotNull('team_id')
                        ->whereHas('team', function ($teamQuery) use ($viewerId): void {
                            $teamQuery->where('visibility', '!=', 'private')
                                ->orWhereHas('activeMembers', fn ($memberQuery) => $memberQuery->where('user_id', $viewerId));
                        });
                });
            })
            ->when($recentOnly, fn ($query) => $query->where('created_at', '>=', now()->subDays(7)))
            ->orderByRaw('(comments_count + reactions_count + shares_count) desc')
            ->latest()
            ->first();
    }
}
