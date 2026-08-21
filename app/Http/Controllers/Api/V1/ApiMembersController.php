<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LfgPostResource;
use App\Http\Resources\Api\MomentResource;
use App\Http\Resources\Api\TeamLfgPostResource;
use App\Http\Resources\Api\TeamResource;
use App\Http\Resources\Api\UserLoadoutResource;
use App\Http\Resources\Api\UserResource;
use App\Http\Resources\Api\FeedPostResource;
use App\Models\Friendship;
use App\Models\LiveLobbyFeedback;
use App\Models\Quest;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Services\SecurityLogService;
use App\Services\Twitch\TwitchLiveStatusService;
use App\Services\Search\PlayerSearchQuery;
use Illuminate\Support\Facades\Storage;

class ApiMembersController extends Controller
{
    private const HUNTER_TRUST_TAGS = [
        'reliable' => ['de' => 'Zuverlässig', 'en' => 'Reliable'],
        'chill' => ['de' => 'Chill', 'en' => 'Chill'],
        'teamplayer' => ['de' => 'Teamplayer', 'en' => 'Teamplayer'],
        'good_communication' => ['de' => 'Gute Kommunikation', 'en' => 'Good communication'],
        'helpful' => ['de' => 'Hilfsbereit', 'en' => 'Helpful'],
        'beginner_friendly' => ['de' => 'Anfängerfreundlich', 'en' => 'Beginner-friendly'],
        'would_play_again' => ['de' => 'Würde wieder spielen', 'en' => 'Would play again'],
    ];

    public function index(Request $request, PlayerSearchQuery $playerSearch): JsonResponse
    {
        $viewer = $request->user();
        $search = trim((string) $request->input('q', ''));
        $platform = trim((string) $request->input('platform', ''));
        $playstyle = trim((string) $request->input('playstyle', ''));

        $members = $playerSearch->build($viewer, $search, $platform, $playstyle)
            ->orderByRaw('id = ? desc', [$viewer->id])
            ->latest()
            ->paginate(24);

        return response()->json([
            'data' => $members->getCollection()
                ->map(fn (User $member): array => $this->memberSearchItem($request, $member, $viewer))
                ->values(),
            'meta' => [
                'current_page' => $members->currentPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
                'last_page' => $members->lastPage(),
                'has_more' => $members->hasMorePages(),
            ],
            'filters' => [
                'q' => $search,
                'platform' => $platform,
                'playstyle' => $playstyle,
            ],
        ]);
    }



    private function memberSearchItem(Request $request, User $member, User $viewer): array
    {
        $member->loadMissing(['profile', 'privacySettings']);
        $isOwnProfile = (int) $viewer->id === (int) $member->id;

        return [
            'user' => (new UserResource($member))->resolve($request),
            'viewer' => $this->viewerState($viewer, $member, $isOwnProfile),
        ];
    }

    public function friends(Request $request): JsonResponse
    {
        $viewer = $request->user();

        $accepted = Friendship::query()
            ->forUser($viewer)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->with(['userOne.profile', 'userTwo.profile'])
            ->latest('accepted_at')
            ->get()
            ->map(fn (Friendship $friendship): ?array => $this->friendshipListItem($friendship, $viewer, 'friend'))
            ->filter()
            ->values();

        $incoming = Friendship::query()
            ->where('recipient_id', $viewer->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->with(['requester.profile', 'recipient.profile'])
            ->latest('updated_at')
            ->get()
            ->map(fn (Friendship $friendship): ?array => $this->friendshipListItem($friendship, $viewer, 'incoming'))
            ->filter()
            ->values();

        $outgoing = Friendship::query()
            ->where('requester_id', $viewer->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->with(['requester.profile', 'recipient.profile'])
            ->latest('updated_at')
            ->get()
            ->map(fn (Friendship $friendship): ?array => $this->friendshipListItem($friendship, $viewer, 'outgoing'))
            ->filter()
            ->values();

        return response()->json([
            'friends' => $accepted,
            'incoming_requests' => $incoming,
            'outgoing_requests' => $outgoing,
            'counts' => [
                'friends' => $accepted->count(),
                'incoming_requests' => $incoming->count(),
                'outgoing_requests' => $outgoing->count(),
            ],
        ]);
    }

    public function blockedUsers(Request $request): JsonResponse
    {
        $viewer = $request->user();

        $blocks = UserBlock::query()
            ->with(['blockedUser.profile', 'blockedUser.privacySettings'])
            ->where('user_id', $viewer->id)
            ->whereHas('blockedUser', fn ($query) => $query->where('status', 'active'))
            ->latest()
            ->paginate(50);

        return response()->json([
            'data' => $blocks->getCollection()
                ->map(function (UserBlock $block) use ($request): ?array {
                    $blockedUser = $block->blockedUser;

                    if (! $blockedUser || $blockedUser->status !== 'active') {
                        return null;
                    }

                    return [
                        'id' => (int) $block->id,
                        'reason' => (string) ($block->reason ?? ''),
                        'blocked_at' => $block->created_at?->toISOString(),
                        'user' => (new UserResource($blockedUser))->resolve($request),
                    ];
                })
                ->filter()
                ->values(),
            'meta' => [
                'current_page' => $blocks->currentPage(),
                'per_page' => $blocks->perPage(),
                'total' => $blocks->total(),
                'last_page' => $blocks->lastPage(),
                'has_more' => $blocks->hasMorePages(),
            ],
        ]);
    }


    public function show(Request $request, User $user, TwitchLiveStatusService $twitch): JsonResponse
    {
        abort_unless($user->status === 'active', 404);

        $viewer = $request->user();
        $user->loadMissing(['profile', 'privacySettings']);

        if (! $user->profile) {
            $user->profile()->create([
                'profile_visibility' => 'public',
            ]);
            $user->load('profile');
        }

        $isOwnProfile = (int) $viewer->id === (int) $user->id;

        if (! $isOwnProfile && $user->profile?->profile_visibility === 'private') {
            abort(404);
        }

        return response()->json([
            'user' => new UserResource($user),
            'profile_summary' => $this->publicProfileSummary($request, $user, $isOwnProfile),
            'viewer' => $this->viewerState($viewer, $user, $isOwnProfile),
            'twitch' => $twitch->statusForUrl($user->profile?->twitch_url),
        ]);
    }


    public function meSection(Request $request, string $section): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('profile');

        return response()->json($this->profileSectionPayload($request, $user, $section, true));
    }

    public function userSection(Request $request, User $user, string $section): JsonResponse
    {
        abort_unless($user->status === 'active', 404);

        $viewer = $request->user();
        $user->loadMissing('profile');

        if (! $user->profile) {
            $user->profile()->create([
                'profile_visibility' => 'public',
            ]);
            $user->load('profile');
        }

        $isOwnProfile = (int) $viewer->id === (int) $user->id;

        if (! $isOwnProfile && $user->profile?->profile_visibility === 'private') {
            abort(404);
        }

        return response()->json($this->profileSectionPayload($request, $user, $section, $isOwnProfile));
    }


    public function requestFriend(Request $request, User $user, NotificationService $notifications): JsonResponse
    {
        $viewer = $request->user();
        abort_if((int) $viewer->id === (int) $user->id, 422, 'Du kannst dir nicht selbst eine Freundschaftsanfrage senden.');
        abort_unless($user->status === 'active', 404);

        if ($viewer->hasBlocked($user) || $user->hasBlocked($viewer)) {
            abort(403);
        }

        $friendship = Friendship::query()->between($viewer, $user)->first();

        if ($friendship && $friendship->isAccepted()) {
            return $this->profileActionResponse($request, $user, __('ui.friend_already_connected'));
        }

        if ($friendship && $friendship->isPending()) {
            $message = $friendship->isRequester($viewer)
                ? __('ui.friend_request_already_sent')
                : __('ui.friend_request_waiting_answer');

            return $this->profileActionResponse($request, $user, $message);
        }

        [$one, $two] = Friendship::pairIds($viewer, $user);

        Friendship::updateOrCreate(
            ['user_one_id' => $one, 'user_two_id' => $two],
            [
                'requester_id' => $viewer->id,
                'recipient_id' => $user->id,
                'status' => Friendship::STATUS_PENDING,
                'accepted_at' => null,
                'declined_at' => null,
            ]
        );

        $notifications->send(
            $user,
            $viewer,
            'friend_request_received',
            __('ui.friend_requests'),
            __('ui.friend_request_wants_connect'),
            url('/u/'.$viewer->username)
        );

        return $this->profileActionResponse($request, $user, __('ui.friend_request_sent_status'));
    }


    public function blockUser(Request $request, User $user, SecurityLogService $securityLog): JsonResponse
    {
        $viewer = $request->user();
        abort_if((int) $viewer->id === (int) $user->id, 422, 'Du kannst dich nicht selbst blockieren.');
        abort_unless($user->status === 'active', 404);

        $reason = trim((string) $request->input('reason', ''));
        if (mb_strlen($reason) > 120) {
            $reason = mb_substr($reason, 0, 120);
        }

        UserBlock::updateOrCreate(
            [
                'user_id' => $viewer->id,
                'blocked_user_id' => $user->id,
            ],
            [
                'reason' => $reason !== '' ? $reason : null,
            ]
        );

        Friendship::query()->between($viewer, $user)->delete();

        $securityLog->record($viewer, 'user_blocked_api', $request, [
            'blocked_user_id' => $user->id,
            'blocked_username' => $user->username,
        ]);

        return $this->profileActionResponse($request, $user, __('ui.user_blocked_status'));
    }


    public function unblockUser(Request $request, User $user, SecurityLogService $securityLog): JsonResponse
    {
        $viewer = $request->user();
        abort_if((int) $viewer->id === (int) $user->id, 422, 'Du kannst dich nicht selbst entblocken.');
        abort_unless($user->status === 'active', 404);

        UserBlock::query()
            ->where('user_id', $viewer->id)
            ->where('blocked_user_id', $user->id)
            ->delete();

        $securityLog->record($viewer, 'user_unblocked_api', $request, [
            'unblocked_user_id' => $user->id,
            'unblocked_username' => $user->username,
        ]);

        return $this->profileActionResponse($request, $user, __('ui.user_unblocked_status'));
    }

    public function acceptFriend(Request $request, Friendship $friendship, NotificationService $notifications): JsonResponse
    {
        $viewer = $request->user();
        abort_unless($friendship->isPending() && $friendship->isRecipient($viewer), 403);

        $friendship->update([
            'status' => Friendship::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'declined_at' => null,
        ]);

        $otherUser = $friendship->fresh(['requester.profile', 'recipient.profile'])->requester;

        $notifications->send(
            $otherUser,
            $viewer,
            'friend_request_accepted',
            __('ui.friend_request_accepted_title'),
            __('ui.friend_request_accepted_body', ['name' => $viewer->name]),
            url('/u/'.$viewer->username)
        );

        return $this->profileActionResponse($request, $otherUser, __('ui.friend_request_accepted_status'));
    }

    public function declineFriend(Request $request, Friendship $friendship): JsonResponse
    {
        $viewer = $request->user();
        abort_unless($friendship->isPending() && $friendship->isRecipient($viewer), 403);

        $otherUser = $friendship->requester;

        $friendship->update([
            'status' => Friendship::STATUS_DECLINED,
            'accepted_at' => null,
            'declined_at' => now(),
        ]);

        return $this->profileActionResponse($request, $otherUser, __('ui.friend_request_declined_status'));
    }

    public function removeFriend(Request $request, Friendship $friendship): JsonResponse
    {
        $viewer = $request->user();
        abort_unless($friendship->isParticipant($viewer), 403);

        if ($friendship->isPending() && ! $friendship->isRequester($viewer)) {
            abort(403);
        }

        $otherUser = $friendship->otherUser($viewer);
        $message = $friendship->isAccepted()
            ? __('ui.friend_removed_status')
            : __('ui.friend_request_withdrawn_status');

        $friendship->delete();

        return $this->profileActionResponse($request, $otherUser, $message);
    }


    private function friendshipListItem(Friendship $friendship, User $viewer, string $kind): ?array
    {
        $other = $friendship->otherUser($viewer);

        if (! $other || $other->status !== 'active') {
            return null;
        }

        $other->loadMissing('profile');

        return [
            'friendship_id' => (int) $friendship->id,
            'kind' => $kind,
            'status' => (string) $friendship->status,
            'direction' => $friendship->isRequester($viewer) ? 'outgoing' : 'incoming',
            'requested_by_me' => $friendship->isRequester($viewer),
            'waiting_for_me' => $friendship->isPending() && $friendship->isRecipient($viewer),
            'accepted_at' => $friendship->accepted_at?->toISOString(),
            'requested_at' => $friendship->created_at?->toISOString(),
            'user' => [
                'id' => (int) $other->id,
                'name' => (string) $other->name,
                'username' => (string) $other->username,
                'avatar_url' => $other->avatar_path ? Storage::disk('public')->url($other->avatar_path) : asset('assets/vikinger/img/default-avatar.svg'),
                'cover_url' => $other->cover_path ? Storage::disk('public')->url($other->cover_path) : asset('assets/vikinger/img/default-cover.svg'),
                'level' => (int) ($other->level ?? 1),
                'xp_total' => (int) ($other->xp_total ?? 0),
                'trust_score' => (int) ($other->trust_score ?? 0),
                'profile' => [
                    'headline' => (string) ($other->profile?->headline ?? ''),
                    'platform' => (string) ($other->profile?->platform ?? ''),
                    'playstyle' => (string) ($other->profile?->playstyle ?? ''),
                    'region' => (string) ($other->profile?->region ?? ''),
                    'is_lfg_available' => (bool) ($other->profile?->is_lfg_available ?? false),
                ],
            ],
        ];
    }

    private function profileSectionPayload(Request $request, User $user, string $section, bool $isOwnProfile): array
    {
        $section = strtolower(str_replace('_', '-', trim($section)));
        $limit = max(1, min(100, (int) $request->integer('limit', 100)));

        return match ($section) {
            'badges' => $this->profileBadgesPayload($request, $user, $limit),
            'friends' => $this->profileFriendsPayload($request, $user, $limit),
            'quests' => $this->profileQuestsPayload($request, $user, $limit),
            'posts' => $this->profilePostsPayload($request, $user, $isOwnProfile, $limit),
            'moments' => $this->profileMomentsPayload($request, $user, $isOwnProfile, $limit),
            'teams' => $this->profileTeamsPayload($request, $user, $isOwnProfile, $limit),
            'lfg' => $this->profileLfgPayload($request, $user, $isOwnProfile, $limit),
            'loadouts' => $this->profileLoadoutsPayload($user),
            default => abort(404),
        };
    }

    private function profileLoadoutsPayload(User $user): array
    {
        $query = $user->loadouts()
            ->where('is_active', true)
            ->where('visibility', 'public');
        $total = (clone $query)->count();
        $items = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(3)
            ->get()
            ->map(fn ($loadout): UserLoadoutResource => new UserLoadoutResource($loadout));

        return $this->sectionResponse('loadouts', $items, $total, 3);
    }

    private function profileBadgesPayload(Request $request, User $user, int $limit): array
    {
        $locale = $this->resolveApiLocale($request);
        $total = $user->badges()->count();
        $items = $user->badges()
            ->orderByPivot('awarded_at', 'desc')
            ->orderBy('badges.sort_order')
            ->limit($limit)
            ->get()
            ->map(fn ($badge): array => $this->badgePayload($badge, $locale))
            ->values();

        return $this->sectionResponse('badges', $items, $total, $limit);
    }

    private function profileFriendsPayload(Request $request, User $user, int $limit): array
    {
        $query = Friendship::query()
            ->forUser($user)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->with(['userOne.profile', 'userTwo.profile'])
            ->latest('accepted_at');

        $total = (clone $query)->count();
        $viewerFriendIds = $request->user() ? $this->acceptedFriendIdsFor($request->user()) : collect();
        $items = $query
            ->limit($limit)
            ->get()
            ->map(function (Friendship $friendship) use ($user, $viewerFriendIds): ?array {
                $friend = $friendship->otherUser($user);

                if (! $friend || $friend->status !== 'active') {
                    return null;
                }

                $commonCount = $viewerFriendIds->isEmpty()
                    ? 0
                    : $viewerFriendIds->intersect($this->acceptedFriendIdsFor($friend))->count();

                return [
                    'id' => (int) $friend->id,
                    'name' => (string) $friend->name,
                    'username' => (string) $friend->username,
                    'avatar_url' => $friend->avatar_path ? Storage::disk('public')->url($friend->avatar_path) : asset('assets/vikinger/img/default-avatar.svg'),
                    'headline' => (string) ($friend->profile?->headline ?? ''),
                    'level' => (int) ($friend->level ?? 1),
                    'common_friends_count' => $commonCount,
                    'accepted_at' => $friendship->accepted_at?->toISOString(),
                ];
            })
            ->filter()
            ->values();

        return $this->sectionResponse('friends', $items, $total, $limit);
    }

    private function profileQuestsPayload(Request $request, User $user, int $limit): array
    {
        $locale = $this->resolveApiLocale($request);
        $query = Quest::query()->where('is_active', true);
        $total = (clone $query)->count();
        $items = $query
            ->with(['progress' => fn ($progressQuery) => $progressQuery->where('user_id', $user->id)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Quest $quest): array => $this->questPayload($quest, $locale))
            ->values();

        return $this->sectionResponse('quests', $items, $total, $limit);
    }

    private function profilePostsPayload(Request $request, User $user, bool $isOwnProfile, int $limit): array
    {
        $postsQuery = $user->feedPosts()
            ->with([
                'user.profile',
                'media.mediaAsset',
                'poll.options.votes',
                'poll.votes',
                'sharedPost.user.profile',
                'sharedPost.media.mediaAsset',
            ])
            ->when($request->user(), function ($postQuery) use ($request): void {
                $viewerId = (int) $request->user()->id;

                $postQuery->with([
                    'viewerReaction' => fn ($reactionQuery) => $reactionQuery->where('user_id', $viewerId),
                    'viewerBookmark' => fn ($bookmarkQuery) => $bookmarkQuery->where('user_id', $viewerId),
                ]);
            })
            ->withCount(['comments', 'reactions'])
            ->where('status', 'published')
            ->whereNull('team_id')
            ->when(! $isOwnProfile, fn ($query) => $query->whereIn('visibility', ['public', 'followers']))
            ->latest();

        $commentsQuery = $user->feedComments()
            ->with(['post.user.profile'])
            ->whereHas('post', function ($postQuery) use ($isOwnProfile): void {
                $postQuery
                    ->where('status', 'published')
                    ->whereNull('team_id')
                    ->when(! $isOwnProfile, fn ($query) => $query->whereIn('visibility', ['public', 'followers']));
            })
            ->latest();

        $postsTotal = (clone $postsQuery)->count();
        $commentsTotal = (clone $commentsQuery)->count();

        $items = $postsQuery
            ->limit($limit)
            ->get()
            ->map(fn ($post): array => $this->profilePostActivityItem($request, $post))
            ->values();

        $comments = $commentsQuery
            ->limit($limit)
            ->get()
            ->map(fn ($comment): array => [
                'id' => (int) $comment->id,
                'post_id' => (int) $comment->feed_post_id,
                'body' => str((string) $comment->body)->stripTags()->limit(180)->toString(),
                'post_excerpt' => $comment->post ? $comment->post->excerpt(120) : '',
                'post_author' => $comment->post?->user ? [
                    'id' => (int) $comment->post->user->id,
                    'name' => (string) $comment->post->user->name,
                    'username' => (string) $comment->post->user->username,
                ] : null,
                'created_at' => $comment->created_at?->toISOString(),
            ])
            ->values();

        return [
            'section' => 'posts',
            'items' => $items,
            'comments' => $comments,
            'meta' => [
                'total' => $postsTotal,
                'comments_total' => $commentsTotal,
                'limit' => $limit,
                'truncated' => $postsTotal > $items->count() || $commentsTotal > $comments->count(),
            ],
        ];
    }

    private function profileMomentsPayload(Request $request, User $user, bool $isOwnProfile, int $limit): array
    {
        $query = $user->moments()
            ->with(['user.profile', 'media', 'cover'])
            ->where('status', 'published')
            ->where(function ($publishedQuery): void {
                $publishedQuery->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->when(! $isOwnProfile, fn ($momentQuery) => $momentQuery->whereIn('visibility', ['public', 'registered']))
            ->when($request->user(), function ($momentQuery) use ($request): void {
                $viewerId = (int) $request->user()->id;

                $momentQuery->with([
                    'reactions' => fn ($reactionQuery) => $reactionQuery->where('user_id', $viewerId),
                    'bookmarks' => fn ($bookmarkQuery) => $bookmarkQuery->where('user_id', $viewerId),
                ]);
            })
            ->latest('published_at')
            ->latest();

        $total = (clone $query)->count();
        $items = $query->limit($limit)->get();

        return $this->sectionResponse('moments', MomentResource::collection($items)->resolve($request), $total, $limit);
    }

    private function profileTeamsPayload(Request $request, User $user, bool $isOwnProfile, int $limit): array
    {
        $query = $user->activeTeams()
            ->with('owner.profile')
            ->withCount('activeMembers')
            ->where('teams.status', 'active')
            ->when(! $isOwnProfile, function ($teamQuery) use ($request): void {
                $viewerId = $request->user()?->id;

                $teamQuery->where(function ($visibilityQuery) use ($viewerId): void {
                    $visibilityQuery->where('teams.visibility', 'public');

                    if ($viewerId) {
                        $visibilityQuery->orWhereHas('members', function ($memberQuery) use ($viewerId): void {
                            $memberQuery
                                ->where('user_id', $viewerId)
                                ->where('status', 'active');
                        });
                    }
                });
            })
            ->latest('teams.updated_at');

        $total = (clone $query)->count();
        $items = $query->limit($limit)->get();

        return $this->sectionResponse('teams', TeamResource::collection($items)->resolve($request), $total, $limit);
    }

    private function profileLfgPayload(Request $request, User $user, bool $isOwnProfile, int $limit): array
    {
        $lfgQuery = $user->lfgPosts()
            ->with([
                'user.profile',
                'applications' => fn ($query) => $query->where('user_id', $request->user()->id),
                'pendingApplications.user.profile',
            ])
            ->whereIn('status', ['open', 'full', 'closed'])
            ->when(! $isOwnProfile, fn ($query) => $query->where('visibility', 'public'))
            ->latest();

        $teamLfgQuery = $user->teamLfgPosts()
            ->with([
                'user.profile',
                'team.owner.profile',
                'pendingApplications.user.profile',
                'pendingApplications.team.owner.profile',
                'applications' => fn ($query) => $query->where('user_id', $request->user()->id),
            ])
            ->whereIn('status', ['open', 'full', 'filled', 'closed'])
            ->when(! $isOwnProfile, fn ($query) => $query->where('visibility', 'public'))
            ->latest();

        $lfgTotal = (clone $lfgQuery)->count();
        $teamLfgTotal = (clone $teamLfgQuery)->count();
        $lfgPosts = $lfgQuery->limit($limit)->get();
        $teamLfgPosts = $teamLfgQuery->limit($limit)->get();

        return [
            'section' => 'lfg',
            'items' => LfgPostResource::collection($lfgPosts)->resolve($request),
            'lfg_posts' => LfgPostResource::collection($lfgPosts)->resolve($request),
            'team_lfg_posts' => TeamLfgPostResource::collection($teamLfgPosts)->resolve($request),
            'meta' => [
                'total' => $lfgTotal + $teamLfgTotal,
                'lfg_total' => $lfgTotal,
                'team_lfg_total' => $teamLfgTotal,
                'limit' => $limit,
                'truncated' => $lfgTotal > $lfgPosts->count() || $teamLfgTotal > $teamLfgPosts->count(),
            ],
        ];
    }

    private function sectionResponse(string $section, mixed $items, int $total, int $limit): array
    {
        $count = is_object($items) && method_exists($items, 'count') ? $items->count() : (is_countable($items) ? count($items) : 0);

        return [
            'section' => $section,
            'items' => $items,
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'truncated' => $total > $count,
            ],
        ];
    }

    private function badgePayload($badge, string $locale = 'de'): array
    {
        return [
            'id' => (int) $badge->id,
            'slug' => (string) $badge->slug,
            'name' => method_exists($badge, 'displayName') ? $badge->displayName($locale) : (string) $badge->name,
            'name_de' => (string) ($badge->name_de ?: $badge->name),
            'name_en' => (string) ($badge->name_en ?: $badge->name),
            'category' => (string) ($badge->category ?? ''),
            'rarity' => (string) ($badge->rarity ?? 'common'),
            'rarity_label' => method_exists($badge, 'rarityLabel') ? $badge->rarityLabel() : ucfirst((string) ($badge->rarity ?? 'common')),
            'icon' => (string) ($badge->icon ?? ''),
            'icon_url' => $badge->iconUrl(),
            'description' => method_exists($badge, 'displayDescription') ? $badge->displayDescription($locale) : (string) ($badge->description ?? ''),
            'description_de' => (string) ($badge->description_de ?: $badge->description),
            'description_en' => (string) ($badge->description_en ?: $badge->description),
            'xp_reward' => (int) ($badge->xp_reward ?? 0),
            'awarded_at' => $badge->pivot?->awarded_at ? (string) $badge->pivot->awarded_at : null,
            'award_reason' => $badge->pivot?->award_reason,
        ];
    }

    private function questPayload(Quest $quest, string $locale = 'de'): array
    {
        $progress = $quest->progress->first();
        $target = max(1, (int) $quest->target_count);
        $current = min($target, max(0, (int) ($progress?->progress_count ?? 0)));
        $completed = $progress?->completed_at !== null;

        return [
            'id' => (int) $quest->id,
            'slug' => (string) $quest->slug,
            'name' => $quest->displayName($locale),
            'name_de' => (string) ($quest->name_de ?: $quest->name),
            'name_en' => (string) ($quest->name_en ?: $quest->name),
            'category' => (string) ($quest->category ?? ''),
            'description' => $quest->displayDescription($locale),
            'description_de' => (string) ($quest->description_de ?: $quest->description),
            'description_en' => (string) ($quest->description_en ?: $quest->description),
            'period' => (string) ($quest->period ?? ''),
            'period_label' => method_exists($quest, 'periodLabel') ? $quest->periodLabel() : ucfirst((string) ($quest->period ?? 'once')),
            'target_count' => $target,
            'progress_count' => $current,
            'progress_percent' => min(100, (int) floor(($current / $target) * 100)),
            'completed' => $completed,
            'completed_at' => $progress?->completed_at?->toISOString(),
            'xp_reward' => (int) ($quest->xp_reward ?? 0),
            'badge_slug' => $quest->badge_slug,
            'icon' => (string) ($quest->icon ?? ''),
            'icon_url' => $quest->iconUrl(),
        ];
    }

    private function resolveApiLocale(Request $request): string
    {
        $queryLocale = $this->validApiLocale($request->query('locale'));
        if ($queryLocale !== null) {
            return $queryLocale;
        }

        $headerLocale = $this->validApiLocale($request->header('X-HNT-Locale'));
        if ($headerLocale !== null) {
            return $headerLocale;
        }

        return $this->localeFromAcceptLanguage((string) $request->headers->get('Accept-Language', '')) ?? 'de';
    }

    private function validApiLocale(mixed $locale, bool $allowLanguageTag = false): ?string
    {
        if (! is_string($locale)) {
            return null;
        }

        $locale = strtolower(trim($locale));
        $locale = str_replace('_', '-', $locale);

        if (! $allowLanguageTag && str_contains($locale, '-')) {
            return null;
        }

        $locale = explode('-', $locale, 2)[0] ?? $locale;

        return in_array($locale, ['de', 'en'], true) ? $locale : null;
    }

    private function localeFromAcceptLanguage(string $acceptLanguage): ?string
    {
        foreach (explode(',', strtolower($acceptLanguage)) as $part) {
            $language = trim(explode(';', $part, 2)[0] ?? '');
            $locale = $this->validApiLocale($language, true);

            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }

    private function acceptedFriendIdsFor(User $targetUser)
    {
        return Friendship::query()
            ->forUser($targetUser)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->get()
            ->map(fn (Friendship $friendship): int => (int) $friendship->user_one_id === (int) $targetUser->id
                ? (int) $friendship->user_two_id
                : (int) $friendship->user_one_id)
            ->values();
    }


    private function publicProfileSummary(Request $request, User $user, bool $isOwnProfile): array
    {
        $locale = $this->resolveApiLocale($request);
        $level = max(1, (int) ($user->level ?: 1));
        $xpTotal = max(0, (int) ($user->xp_total ?: 0));
        $nextLevelXp = max(250, $level * 250);
        $progressPercent = $nextLevelXp > 0 ? min(100, (int) floor(($xpTotal / $nextLevelXp) * 100)) : 0;

        $latestBadges = $user->badges()
            ->orderByPivot('awarded_at', 'desc')
            ->orderBy('badges.sort_order')
            ->limit(12)
            ->get()
            ->map(fn ($badge): array => $this->badgePayload($badge, $locale))
            ->values();

        $quests = Quest::query()
            ->where('is_active', true)
            ->with(['progress' => fn ($query) => $query->where('user_id', $user->id)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Quest $quest): array => $this->questPayload($quest, $locale))
            ->values();

        $acceptedFriendIdsFor = static function (User $targetUser) {
            return Friendship::query()
                ->forUser($targetUser)
                ->where('status', Friendship::STATUS_ACCEPTED)
                ->get()
                ->map(fn (Friendship $friendship): int => (int) $friendship->user_one_id === (int) $targetUser->id
                    ? (int) $friendship->user_two_id
                    : (int) $friendship->user_one_id)
                ->values();
        };

        $viewerFriendIds = $request->user() ? $acceptedFriendIdsFor($request->user()) : collect();

        $friendsPreview = Friendship::query()
            ->forUser($user)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->with(['userOne.profile', 'userTwo.profile'])
            ->latest('accepted_at')
            ->limit(6)
            ->get()
            ->map(function (Friendship $friendship) use ($user, $viewerFriendIds, $acceptedFriendIdsFor): ?array {
                $friend = $friendship->otherUser($user);

                if (! $friend) {
                    return null;
                }

                $commonCount = $viewerFriendIds->isEmpty()
                    ? 0
                    : $viewerFriendIds->intersect($acceptedFriendIdsFor($friend))->count();

                return [
                    'id' => (int) $friend->id,
                    'name' => (string) $friend->name,
                    'username' => (string) $friend->username,
                    'avatar_url' => $friend->avatar_path ? Storage::disk('public')->url($friend->avatar_path) : asset('assets/vikinger/img/default-avatar.svg'),
                    'headline' => (string) ($friend->profile?->headline ?? ''),
                    'level' => (int) ($friend->level ?? 1),
                    'common_friends_count' => $commonCount,
                    'accepted_at' => $friendship->accepted_at?->toISOString(),
                ];
            })
            ->filter()
            ->values();

        $teams = $user->activeTeams()
            ->with('owner.profile')
            ->withCount('activeMembers')
            ->where('teams.status', 'active')
            ->when(! $isOwnProfile, function ($query) use ($request): void {
                $viewerId = $request->user()?->id;

                $query->where(function ($visibilityQuery) use ($viewerId): void {
                    $visibilityQuery->where('teams.visibility', 'public');

                    if ($viewerId) {
                        $visibilityQuery->orWhereHas('members', function ($memberQuery) use ($viewerId): void {
                            $memberQuery
                                ->where('user_id', $viewerId)
                                ->where('status', 'active');
                        });
                    }
                });
            })
            ->latest('teams.updated_at')
            ->limit(5)
            ->get();

        $recentMoments = $user->moments()
            ->with(['user.profile', 'media', 'cover'])
            ->where('status', 'published')
            ->where(function ($publishedQuery): void {
                $publishedQuery->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->when(! $isOwnProfile, fn ($query) => $query->whereIn('visibility', ['public', 'registered']))
            ->when($request->user(), function ($momentQuery) use ($request): void {
                $viewerId = (int) $request->user()->id;

                $momentQuery->with([
                    'reactions' => fn ($reactionQuery) => $reactionQuery->where('user_id', $viewerId),
                    'bookmarks' => fn ($bookmarkQuery) => $bookmarkQuery->where('user_id', $viewerId),
                ]);
            })
            ->latest('published_at')
            ->latest()
            ->limit(6)
            ->get();

        $recentPosts = $user->feedPosts()
            ->with([
                'user.profile',
                'media.mediaAsset',
                'poll.options.votes',
                'poll.votes',
                'sharedPost.user.profile',
                'sharedPost.media.mediaAsset',
            ])
            ->when($request->user(), function ($postQuery) use ($request): void {
                $viewerId = (int) $request->user()->id;

                $postQuery->with([
                    'viewerReaction' => fn ($reactionQuery) => $reactionQuery->where('user_id', $viewerId),
                    'viewerBookmark' => fn ($bookmarkQuery) => $bookmarkQuery->where('user_id', $viewerId),
                ]);
            })
            ->withCount(['comments', 'reactions'])
            ->where('status', 'published')
            ->whereNull('team_id')
            ->when(! $isOwnProfile, fn ($query) => $query->whereIn('visibility', ['public', 'followers']))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($post): array => $this->profilePostActivityItem($request, $post))
            ->values();

        $recentComments = $user->feedComments()
            ->with(['post.user.profile'])
            ->whereHas('post', function ($postQuery) use ($isOwnProfile): void {
                $postQuery
                    ->where('status', 'published')
                    ->whereNull('team_id')
                    ->when(! $isOwnProfile, fn ($query) => $query->whereIn('visibility', ['public', 'followers']));
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($comment): array => [
                'id' => (int) $comment->id,
                'post_id' => (int) $comment->feed_post_id,
                'body' => str((string) $comment->body)->stripTags()->limit(180)->toString(),
                'post_excerpt' => $comment->post ? $comment->post->excerpt(120) : '',
                'post_author' => $comment->post?->user ? [
                    'id' => (int) $comment->post->user->id,
                    'name' => (string) $comment->post->user->name,
                    'username' => (string) $comment->post->user->username,
                ] : null,
                'created_at' => $comment->created_at?->toISOString(),
            ])
            ->values();

        return [
            'hunter_trust' => $this->hunterTrustSummary($user),
            'progress' => [
                'level' => $level,
                'xp_total' => $xpTotal,
                'next_level' => $level + 1,
                'next_level_xp' => $nextLevelXp,
                'xp_to_next_level' => max(0, $nextLevelXp - $xpTotal),
                'progress_percent' => $progressPercent,
                'trust_score' => (int) ($user->trust_score ?? 0),
                'last_xp_at' => $user->last_xp_at?->toISOString(),
            ],
            'counts' => [
                'badges' => $user->badges()->count(),
                'active_quests' => Quest::query()->where('is_active', true)->count(),
                'completed_quests' => $user->questProgress()->whereNotNull('completed_at')->count(),
                'friends' => $user->friendsCount(),
                'teams' => $user->activeTeams()->count(),
                'posts' => $user->feedPosts()
                    ->where('status', 'published')
                    ->when(! $isOwnProfile, fn ($query) => $query->where('visibility', '!=', 'private'))
                    ->count(),
                'comments' => $user->feedComments()->count(),
                'moments' => $user->moments()->count(),
            ],
            'latest_badges' => $latestBadges,
            'quests' => $quests,
            'friends_preview' => $friendsPreview,
            'teams_preview' => TeamResource::collection($teams)->resolve($request),
            'recent_posts' => $recentPosts,
            'recent_moments' => MomentResource::collection($recentMoments)->resolve($request),
            'recent_comments' => $recentComments,
        ];
    }

    private function hunterTrustSummary(User $user): array
    {
        $feedbackRows = LiveLobbyFeedback::query()
            ->where('target_user_id', $user->id)
            ->get(['positive_tags']);

        $tagCounts = array_fill_keys(array_keys(self::HUNTER_TRUST_TAGS), 0);

        foreach ($feedbackRows as $feedback) {
            $positiveTags = is_array($feedback->positive_tags) ? $feedback->positive_tags : [];
            $uniqueStringTags = array_unique(array_filter($positiveTags, 'is_string'));

            foreach ($uniqueStringTags as $tag) {
                if (array_key_exists($tag, $tagCounts)) {
                    $tagCounts[$tag]++;
                }
            }
        }

        $tags = collect(self::HUNTER_TRUST_TAGS)
            ->map(fn (array $labels, string $key): array => [
                'key' => $key,
                'label' => $labels['en'],
                'label_de' => $labels['de'],
                'label_en' => $labels['en'],
                'count' => $tagCounts[$key],
            ])
            ->filter(fn (array $tag): bool => $tag['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->all();

        return [
            'total_feedback' => $feedbackRows->count(),
            'positive_tags_total' => array_sum($tagCounts),
            'tags' => $tags,
        ];
    }


    private function profilePostActivityItem(Request $request, $post): array
    {
        return (new FeedPostResource($post))->resolve($request);
    }


    private function profileActionResponse(Request $request, ?User $profileUser, string $message): JsonResponse
    {
        abort_unless($profileUser && $profileUser->status === 'active', 404);

        $profileUser->loadMissing(['profile', 'privacySettings']);

        $isOwnProfile = (int) $request->user()->id === (int) $profileUser->id;

        return response()->json([
            'ok' => true,
            'message' => $message,
            'user' => new UserResource($profileUser),
            'profile_summary' => $this->publicProfileSummary($request, $profileUser, $isOwnProfile),
            'viewer' => $this->viewerState($request->user(), $profileUser, $isOwnProfile),
        ]);
    }

    private function viewerState(User $viewer, User $profileUser, bool $isOwnProfile): array
    {
        $friendship = $isOwnProfile ? null : Friendship::query()->between($viewer, $profileUser)->first();
        $hasBlocked = ! $isOwnProfile && $viewer->hasBlocked($profileUser);
        $isBlockedBy = ! $isOwnProfile && $profileUser->hasBlocked($viewer);

        return [
            'is_self' => $isOwnProfile,
            'friendship' => $friendship ? [
                'id' => (int) $friendship->id,
                'status' => (string) $friendship->status,
                'direction' => $friendship->isRequester($viewer) ? 'outgoing' : 'incoming',
                'requested_by_me' => $friendship->isRequester($viewer),
                'waiting_for_me' => $friendship->isPending() && $friendship->isRecipient($viewer),
            ] : null,
            'has_blocked' => $hasBlocked,
            'is_blocked_by' => $isBlockedBy,
            'can_request_friend' => ! $isOwnProfile
                && ! $friendship
                && ! $hasBlocked
                && ! $isBlockedBy,
            'can_message' => ! $isOwnProfile && ! $hasBlocked && ! $isBlockedBy,
        ];
    }
}
