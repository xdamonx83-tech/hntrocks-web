<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\CupSubmission;
use App\Models\CupTeam;
use App\Models\Friendship;
use App\Models\LoadoutChallengeSubmission;
use App\Models\MomentSpotlight;
use App\Models\Quest;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Services\ReferralService;
use App\Services\Auth\TwoFactorService;
use App\Support\CrownCosmetics;
use App\Support\HntTheme;
use App\Support\NotificationSettingsGroups;
use App\Support\ReworkFeedSidebar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request, ?User $user = null): View
    {
        $profileUser = $user ?? $request->user();
        $profileUser->loadMissing(['profile', 'crownWallet']);

        if (! $profileUser->profile) {
            $profileUser->profile()->create([
                'profile_visibility' => 'public',
            ]);
            $profileUser->load('profile');
        }

        $isOwnProfile = $request->user()?->is($profileUser) ?? false;
        $activeSection = $this->profileSectionFromRoute($request);

        $this->guardProfileVisibility($request, $profileUser, $isOwnProfile);

        $profileUser->loadCount([
            'badges',
            'activeTeams',
            'moments',
            'feedComments',
            'feedPosts as visible_feed_posts_count' => function ($query) use ($isOwnProfile, $request): void {
                $query->where('status', 'published')
                    ->when(! $isOwnProfile, fn ($innerQuery) => $request->user() ? $innerQuery->where('visibility', '!=', 'private') : $innerQuery->where('visibility', 'public'));
            },
        ]);

        $friendship = null;

        if (! $isOwnProfile && $request->user()) {
            $friendship = Friendship::query()
                ->between($request->user(), $profileUser)
                ->first();
        }

        $profilePostPage = max(1, (int) $request->query('profile_posts_page', 1));
        $profilePostsPerPage = 5;
        $profilePostsShown = $profilePostPage * $profilePostsPerPage;

        $profilePostsQuery = $profileUser->feedPosts()
            ->with([
                'user.profile',
                'team',
                'media.mediaAsset',
                'comments.user.profile',
                'comments.reactions',
                'comments.viewerReaction',
                'reactions',
                'viewerReaction',
                'viewerBookmark',
                'poll.options.votes',
                'poll.votes',
                'sharedPost.user.profile',
                'sharedPost.team',
                'sharedPost.media.mediaAsset',
            ])
            ->withCount(['comments', 'reactions', 'bookmarks', 'sharedByPosts as shares_count'])
            ->where('status', 'published')
            ->when(! $isOwnProfile, fn ($query) => $request->user() ? $query->where('visibility', '!=', 'private') : $query->where('visibility', 'public'))
            ->latest();

        $profilePostsTotal = (clone $profilePostsQuery)->count();
        $profileNextPostId = $profilePostsShown < $profilePostsTotal
            ? (clone $profilePostsQuery)->skip($profilePostsShown)->value('id')
            : null;

        $profilePosts = (clone $profilePostsQuery)
            ->limit($profilePostsShown)
            ->get();

        $latestBadges = $profileUser->badges()
            ->orderByPivot('awarded_at', 'desc')
            ->orderBy('badges.sort_order')
            ->limit(30)
            ->get();

        $profileMomentsPreview = $profileUser->moments()
            ->published()
            ->with(['cover', 'media'])
            ->latest('published_at')
            ->latest()
            ->limit(12)
            ->get();

        $profileQuestPreview = Quest::query()
            ->where('is_active', true)
            ->with([
                'progress' => fn ($query) => $query->where('user_id', $profileUser->id),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (Quest $quest): bool => blank($quest->progress->first()?->completed_at))
            ->take(5)
            ->values();

        $profileCompletedQuestCount = $profileUser->questProgress()
            ->whereNotNull('completed_at')
            ->count();

        $trophyCabinet = $this->trophyCabinetFor($profileUser);
        $hunterCard = $this->hunterCardFor($profileUser, $trophyCabinet);

        $profileTeams = $profileUser->activeTeams()
            ->with(['activeMembers.user.profile'])
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
            ->limit(12)
            ->get();

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

        $profileFriendsPreview = Friendship::query()
            ->forUser($profileUser)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->with(['userOne.profile', 'userTwo.profile'])
            ->latest('accepted_at')
            ->limit(18)
            ->get()
            ->map(function (Friendship $friendship) use ($profileUser, $viewerFriendIds, $acceptedFriendIdsFor) {
                $friend = $friendship->otherUser($profileUser);

                if (! $friend) {
                    return null;
                }

                $commonCount = $viewerFriendIds->isEmpty()
                    ? 0
                    : $viewerFriendIds->intersect($acceptedFriendIdsFor($friend))->count();

                $friend->setAttribute('common_friends_count', $commonCount);

                return $friend;
            })
            ->filter()
            ->values();

        $profileView = $this->profileThemeView($request, 'profile.show', 'themes.socialite.profile.show');
        $viewer = $request->user();
        $sidebarData = str_starts_with($profileView, 'themes.rework.')
            ? ReworkFeedSidebar::forViewer($viewer)
            : [];
        $headerData = str_starts_with($profileView, 'themes.rework.')
            ? ReworkFeedSidebar::headerData($viewer)
            : [];
        $reworkSettingsData = $this->reworkSettingsData($viewer);
        $profileIsBlocked = $viewer && ! $isOwnProfile
            ? ($viewer->hasBlocked($profileUser) || $profileUser->hasBlocked($viewer))
            : false;

        return view($profileView, [
            'profileUser' => $profileUser,
            'isOwnProfile' => $isOwnProfile,
            'activeSection' => $activeSection,
            'profilePosts' => $profilePosts,
            'profilePostPage' => $profilePostPage,
            'profilePostsPerPage' => $profilePostsPerPage,
            'profilePostsShown' => min($profilePostsShown, $profilePostsTotal),
            'profilePostsTotal' => $profilePostsTotal,
            'profileNextPostId' => $profileNextPostId,
            'latestBadges' => $latestBadges,
            'profileMomentsPreview' => $profileMomentsPreview,
            'profileQuestPreview' => $profileQuestPreview,
            'profileCompletedQuestCount' => $profileCompletedQuestCount,
            'trophyCabinet' => $trophyCabinet,
            'hunterCard' => $hunterCard,
            'profileTeams' => $profileTeams,
            'profileFriendsPreview' => $profileFriendsPreview,
            'friendship' => $friendship,
            'profileFriendsCount' => $profileUser->friendsCount(),
            'profileCosmetics' => CrownCosmetics::forUser($profileUser),
            'profileCanRequestFriend' => $this->canRequestFriend($viewer, $profileUser, $friendship, $isOwnProfile, $profileIsBlocked),
            'profileCanMessage' => $this->canMessageProfile($viewer, $profileUser, $friendship, $isOwnProfile, $profileIsBlocked),
            'profileIsBlocked' => $profileIsBlocked,
            'socialiteMembers' => $sidebarData['members'] ?? collect(),
            'socialiteProfileStats' => $sidebarData['profileStats'] ?? [],
            'socialiteCrownsSummary' => $sidebarData['crownsSummary'] ?? ['balance' => 0, 'enabled' => false],
            'socialiteHighlightTopPost' => $sidebarData['highlightTopPost'] ?? null,
            'socialiteHighlightLfg' => $sidebarData['highlightLfg'] ?? null,
            'socialiteHighlightCup' => $sidebarData['highlightCup'] ?? null,
            'headerNotificationsUnread' => $headerData['notificationsUnread'] ?? 0,
            'headerNotifications' => $headerData['notifications'] ?? collect(),
            'headerFriendRequests' => $headerData['friendRequests'] ?? collect(),
            'headerFriendRequestCount' => $headerData['friendRequestCount'] ?? 0,
            'headerMessageConversations' => $headerData['messageConversations'] ?? collect(),
            'headerMessagesUnread' => $headerData['messagesUnread'] ?? 0,
            ...$reworkSettingsData,
        ]);
    }


    public function about(Request $request, ?User $user = null): View
    {
        if ($this->useSocialiteProfileView($request)) {
            return $this->show($request, $user);
        }

        $profileUser = $user ?? $request->user();
        $profileUser->loadMissing('profile');

        if (! $profileUser->profile) {
            $profileUser->profile()->create([
                'profile_visibility' => 'public',
            ]);
            $profileUser->load('profile');
        }

        $isOwnProfile = $request->user()?->is($profileUser) ?? false;

        $this->guardProfileVisibility($request, $profileUser, $isOwnProfile);

        $profileUser->loadCount([
            'badges',
            'activeTeams',
            'moments',
            'feedComments',
            'feedPosts as visible_feed_posts_count' => function ($query) use ($isOwnProfile, $request): void {
                $query->where('status', 'published')
                    ->when(! $isOwnProfile, fn ($innerQuery) => $request->user() ? $innerQuery->where('visibility', '!=', 'private') : $innerQuery->where('visibility', 'public'));
            },
        ]);

        $friendship = null;

        if (! $isOwnProfile && $request->user()) {
            $friendship = Friendship::query()
                ->between($request->user(), $profileUser)
                ->first();
        }

        $lastPost = $profileUser->feedPosts()
            ->where('status', 'published')
            ->when(! $isOwnProfile, fn ($query) => $request->user() ? $query->where('visibility', '!=', 'private') : $query->where('visibility', 'public'))
            ->latest()
            ->first();

        $lastFriendship = Friendship::query()
            ->forUser($profileUser)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->latest('accepted_at')
            ->first();

        $latestBadge = $profileUser->badges()
            ->orderByPivot('awarded_at', 'desc')
            ->orderBy('badges.sort_order')
            ->first();

        $profileCompletedQuestCount = $profileUser->questProgress()
            ->whereNotNull('completed_at')
            ->count();

        $activeQuestCount = Quest::query()
            ->where('is_active', true)
            ->count();

        return view('profile.about', [
            'profileUser' => $profileUser,
            'isOwnProfile' => $isOwnProfile,
            'friendship' => $friendship,
            'profileFriendsCount' => $profileUser->friendsCount(),
            'profileCosmetics' => CrownCosmetics::forUser($profileUser),
            'lastPost' => $lastPost,
            'lastFriendship' => $lastFriendship,
            'latestBadge' => $latestBadge,
            'profileCompletedQuestCount' => $profileCompletedQuestCount,
            'activeQuestCount' => $activeQuestCount,
        ]);
    }

    public function friends(Request $request, ?User $user = null): View
    {
        if ($this->useSocialiteProfileView($request)) {
            return $this->show($request, $user);
        }

        $profileUser = $user ?? $request->user();
        $profileUser->loadMissing('profile');

        if (! $profileUser->profile) {
            $profileUser->profile()->create([
                'profile_visibility' => 'public',
            ]);
            $profileUser->load('profile');
        }

        $isOwnProfile = $request->user()?->is($profileUser) ?? false;

        $this->guardProfileVisibility($request, $profileUser, $isOwnProfile);

        $profileUser->loadCount([
            'badges',
            'activeTeams',
            'moments',
            'feedComments',
            'feedPosts as visible_feed_posts_count' => function ($query) use ($isOwnProfile, $request): void {
                $query->where('status', 'published')
                    ->when(! $isOwnProfile, fn ($innerQuery) => $request->user() ? $innerQuery->where('visibility', '!=', 'private') : $innerQuery->where('visibility', 'public'));
            },
        ]);

        $friendship = null;

        if (! $isOwnProfile && $request->user()) {
            $friendship = Friendship::query()
                ->between($request->user(), $profileUser)
                ->first();
        }

        $friendshipsQuery = Friendship::query()
            ->forUser($profileUser)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->with(['userOne.profile', 'userTwo.profile'])
            ->latest('accepted_at');

        if (! $isOwnProfile) {
            $viewerId = (int) $request->user()->id;
            $profileUserId = (int) $profileUser->id;

            $friendshipsQuery->where(function ($visibilityQuery) use ($profileUserId, $viewerId): void {
                $visibilityQuery
                    ->where(function ($sideQuery) use ($profileUserId, $viewerId): void {
                        $sideQuery
                            ->where('user_one_id', $profileUserId)
                            ->whereHas('userTwo.profile', function ($profileQuery) use ($viewerId): void {
                                $profileQuery
                                    ->whereIn('profile_visibility', ['public', 'registered'])
                                    ->orWhere('user_id', $viewerId);
                            });
                    })
                    ->orWhere(function ($sideQuery) use ($profileUserId, $viewerId): void {
                        $sideQuery
                            ->where('user_two_id', $profileUserId)
                            ->whereHas('userOne.profile', function ($profileQuery) use ($viewerId): void {
                                $profileQuery
                                    ->whereIn('profile_visibility', ['public', 'registered'])
                                    ->orWhere('user_id', $viewerId);
                            });
                    });
            });
        }

        $profileFriends = $friendshipsQuery
            ->paginate(12)
            ->withQueryString();

        $friendModels = new \Illuminate\Database\Eloquent\Collection(
            $profileFriends->getCollection()
                ->map(fn (Friendship $friendship) => $friendship->otherUser($profileUser))
                ->filter()
                ->values()
                ->all()
        );

        if ($friendModels->isNotEmpty()) {
            $friendModels->loadMissing('profile');
            $friendModels->loadCount([
                'activeTeams',
                'feedPosts as visible_feed_posts_count' => function ($query): void {
                    $query->where('status', 'published')->where('visibility', '!=', 'private');
                },
            ]);
        }

        $acceptedFriendIdsFor = static function (User $targetUser) {
            return Friendship::query()
                ->forUser($targetUser)
                ->where('status', Friendship::STATUS_ACCEPTED)
                ->get(['user_one_id', 'user_two_id'])
                ->map(fn (Friendship $friendship): int => (int) $friendship->user_one_id === (int) $targetUser->id
                    ? (int) $friendship->user_two_id
                    : (int) $friendship->user_one_id)
                ->values();
        };

        $viewerFriendIds = $request->user() ? $acceptedFriendIdsFor($request->user()) : collect();

        $friendModels->each(function (User $friend) use ($viewerFriendIds, $acceptedFriendIdsFor): void {
            $commonCount = $viewerFriendIds->isEmpty()
                ? 0
                : $viewerFriendIds->intersect($acceptedFriendIdsFor($friend))->count();

            $friend->setAttribute('common_friends_count', $commonCount);
        });

        $friendIds = $friendModels->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $viewerFriendships = collect();

        if ($request->user() && $friendIds->isNotEmpty()) {
            $viewer = $request->user();

            $viewerFriendships = Friendship::query()
                ->forUser($viewer)
                ->where(function ($friendshipQuery) use ($friendIds): void {
                    $ids = $friendIds->all();
                    $friendshipQuery->whereIn('user_one_id', $ids)->orWhereIn('user_two_id', $ids);
                })
                ->with(['userOne', 'userTwo'])
                ->get()
                ->mapWithKeys(function (Friendship $friendship) use ($viewer): array {
                    $otherUser = $friendship->otherUser($viewer);

                    return $otherUser ? [(int) $otherUser->id => $friendship] : [];
                });
        }

        $profileFriends->setCollection($friendModels);

        return view('profile.friends', [
            'profileUser' => $profileUser,
            'isOwnProfile' => $isOwnProfile,
            'profileFriends' => $profileFriends,
            'viewerFriendships' => $viewerFriendships,
            'friendship' => $friendship,
            'profileFriendsCount' => $profileUser->friendsCount(),
            'profileCosmetics' => CrownCosmetics::forUser($profileUser),
        ]);
    }


    public function badges(Request $request, ?User $user = null): View
    {
        if ($this->useSocialiteProfileView($request)) {
            return $this->show($request, $user);
        }

        $profileUser = $user ?? $request->user();
        $profileUser->loadMissing('profile');

        if (! $profileUser->profile) {
            $profileUser->profile()->create([
                'profile_visibility' => 'public',
            ]);
            $profileUser->load('profile');
        }

        $isOwnProfile = $request->user()?->is($profileUser) ?? false;

        $this->guardProfileVisibility($request, $profileUser, $isOwnProfile);

        $profileUser->loadCount([
            'badges',
            'activeTeams',
            'moments',
            'feedComments',
            'feedPosts as visible_feed_posts_count' => function ($query) use ($isOwnProfile, $request): void {
                $query->where('status', 'published')
                    ->when(! $isOwnProfile, fn ($innerQuery) => $request->user() ? $innerQuery->where('visibility', '!=', 'private') : $innerQuery->where('visibility', 'public'));
            },
        ]);

        $friendship = null;

        if (! $isOwnProfile && $request->user()) {
            $friendship = Friendship::query()
                ->between($request->user(), $profileUser)
                ->first();
        }

        $profileBadges = $profileUser->badges()
            ->orderByPivot('awarded_at', 'desc')
            ->orderBy('badges.sort_order')
            ->paginate(12)
            ->withQueryString();

        return view('profile.badges', [
            'profileUser' => $profileUser,
            'isOwnProfile' => $isOwnProfile,
            'profileBadges' => $profileBadges,
            'friendship' => $friendship,
            'profileFriendsCount' => $profileUser->friendsCount(),
            'profileCosmetics' => CrownCosmetics::forUser($profileUser),
        ]);
    }


    public function teams(Request $request, ?User $user = null): View
    {
        if ($this->useSocialiteProfileView($request)) {
            return $this->show($request, $user);
        }

        $profileUser = $user ?? $request->user();
        $profileUser->loadMissing('profile');

        if (! $profileUser->profile) {
            $profileUser->profile()->create([
                'profile_visibility' => 'public',
            ]);
            $profileUser->load('profile');
        }

        $isOwnProfile = $request->user()?->is($profileUser) ?? false;

        $this->guardProfileVisibility($request, $profileUser, $isOwnProfile);

        $profileUser->loadCount([
            'badges',
            'activeTeams',
            'moments',
            'feedComments',
            'feedPosts as visible_feed_posts_count' => function ($query) use ($isOwnProfile, $request): void {
                $query->where('status', 'published')
                    ->when(! $isOwnProfile, fn ($innerQuery) => $request->user() ? $innerQuery->where('visibility', '!=', 'private') : $innerQuery->where('visibility', 'public'));
            },
        ]);

        $friendship = null;

        if (! $isOwnProfile && $request->user()) {
            $friendship = Friendship::query()
                ->between($request->user(), $profileUser)
                ->first();
        }

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'sort' => $request->query('sort', 'newest'),
        ];

        if (! in_array($filters['sort'], ['newest', 'members', 'name'], true)) {
            $filters['sort'] = 'newest';
        }

        $profileTeamsQuery = $profileUser->activeTeams()
            ->with([
                'owner.profile',
                'activeMembers.user.profile',
            ])
            ->withCount([
                'activeMembers as members_count',
                'feedPosts as posts_count' => function ($postQuery): void {
                    $postQuery->where('status', 'published');
                },
                'teamLfgPosts as open_lfg_posts_count' => function ($lfgQuery): void {
                    $lfgQuery->where('status', 'open');
                },
            ])
            ->where('teams.status', 'active')
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $search = $filters['q'];

                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('teams.name', 'like', "%{$search}%")
                        ->orWhere('teams.tagline', 'like', "%{$search}%")
                        ->orWhere('teams.description', 'like', "%{$search}%");
                });
            })
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
            });

        match ($filters['sort']) {
            'members' => $profileTeamsQuery->orderByDesc('members_count')->orderBy('teams.name'),
            'name' => $profileTeamsQuery->orderBy('teams.name'),
            default => $profileTeamsQuery->orderByDesc('team_members.joined_at')->orderByDesc('teams.created_at'),
        };

        $profileTeams = $profileTeamsQuery
            ->paginate(12)
            ->withQueryString();

        $visibleTeamsCount = $profileTeams->total();
        $profileUser->setAttribute('active_teams_count', $visibleTeamsCount);

        $viewerTeamMemberships = collect();
        $teamIds = $profileTeams->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->values();

        if ($request->user() && $teamIds->isNotEmpty()) {
            $viewerTeamMemberships = TeamMember::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('team_id', $teamIds->all())
                ->get()
                ->keyBy(fn (TeamMember $membership): int => (int) $membership->team_id);
        }

        return view('profile.teams', [
            'profileUser' => $profileUser,
            'isOwnProfile' => $isOwnProfile,
            'profileTeams' => $profileTeams,
            'viewerTeamMemberships' => $viewerTeamMemberships,
            'filters' => $filters,
            'friendship' => $friendship,
            'profileFriendsCount' => $profileUser->friendsCount(),
            'profileCosmetics' => CrownCosmetics::forUser($profileUser),
        ]);
    }


    public function trophies(Request $request, ?User $user = null): View
    {
        return $this->show($request, $user);
    }


    public function contact(Request $request, ?User $user = null): View
    {
        return $this->show($request, $user);
    }

    private function guardProfileVisibility(Request $request, User $profileUser, bool $isOwnProfile): void
    {
        if ($isOwnProfile) {
            return;
        }

        $visibility = $profileUser->profile?->profile_visibility ?? 'public';

        if ($visibility === 'private') {
            abort(404);
        }

        if ($visibility === 'registered' && ! $request->user()) {
            abort(404);
        }
    }


    private function trophyCabinetFor(User $profileUser): array
    {
        $scoredSubmissionQuery = $profileUser->cupSubmissions()
            ->whereIn('status', CupSubmission::scoredStatuses());

        $bestSubmission = (clone $scoredSubmissionQuery)
            ->with('cup')
            ->orderByDesc('points')
            ->orderByDesc('bounty_tokens')
            ->orderByDesc('kills')
            ->first();

        $cupTeams = CupTeam::query()
            ->with('cup')
            ->where('status', 'active')
            ->where(function ($query) use ($profileUser): void {
                $query->where('owner_id', $profileUser->id)
                    ->orWhereHas('members', function ($memberQuery) use ($profileUser): void {
                        $memberQuery
                            ->where('user_id', $profileUser->id)
                            ->where('status', 'active');
                    });
            })
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->unique('id')
            ->values();

        $cupPlacements = $cupTeams
            ->map(function (CupTeam $team): ?array {
                $cup = $team->cup;

                if (! $cup) {
                    return null;
                }

                $leaderboard = $cup->teams()
                    ->where('status', 'active')
                    ->orderByDesc('points_total')
                    ->orderByDesc('bounty_tokens_total')
                    ->orderByDesc('kills_total')
                    ->orderBy('name')
                    ->get(['id', 'cup_id', 'name', 'points_total', 'bounty_tokens_total', 'kills_total']);

                $rankIndex = $leaderboard->search(fn (CupTeam $entry): bool => (int) $entry->id === (int) $team->id);

                if ($rankIndex === false) {
                    return null;
                }

                return [
                    'rank' => ((int) $rankIndex) + 1,
                    'cup' => $cup,
                    'team' => $team,
                    'points' => (int) $team->points_total,
                    'bounty_tokens' => (int) $team->bounty_tokens_total,
                    'kills' => (int) $team->kills_total,
                    'url' => route('cups.show', $cup),
                ];
            })
            ->filter()
            ->sortBy([
                ['rank', 'asc'],
                ['points', 'desc'],
            ])
            ->take(6)
            ->values();

        $momentSpotlights = MomentSpotlight::query()
            ->with(['moment.cover', 'moment.media'])
            ->whereIn('status', [MomentSpotlight::STATUS_ACTIVE, MomentSpotlight::STATUS_ARCHIVED])
            ->whereHas('moment', function ($query) use ($profileUser): void {
                $query->where('user_id', $profileUser->id);
            })
            ->latest('published_at')
            ->latest('created_at')
            ->limit(4)
            ->get();

        $bestMoment = $profileUser->moments()
            ->published()
            ->orderByDesc('likes_count')
            ->orderByDesc('views_count')
            ->orderByDesc('comments_count')
            ->first();

        return [
            'stats' => [
                'cup_submissions' => (clone $scoredSubmissionQuery)->count(),
                'cup_points' => (int) (clone $scoredSubmissionQuery)->sum('points'),
                'cup_kills' => (int) (clone $scoredSubmissionQuery)->sum('kills'),
                'cup_bounty_tokens' => (int) (clone $scoredSubmissionQuery)->sum('bounty_tokens'),
                'badges' => (int) ($profileUser->badges_count ?? $profileUser->badges()->count()),
                'moments' => (int) ($profileUser->moments_count ?? $profileUser->moments()->published()->count()),
            ],
            'best_submission' => $bestSubmission,
            'cup_placements' => $cupPlacements,
            'moment_spotlights' => $momentSpotlights,
            'best_moment' => $bestMoment,
        ];
    }


    private function hunterCardFor(User $profileUser, array $trophyCabinet): array
    {
        $profileUser->loadMissing('profile');

        $level = max(1, (int) ($profileUser->level ?? 1));
        $xpTotal = max(0, (int) ($profileUser->xp_total ?? 0));
        $nextLevelXp = max(250, $level * 250);
        $levelProgress = min(100, (int) round(($xpTotal % $nextLevelXp) / $nextLevelXp * 100));
        $tierNumber = match (true) {
            $level >= 75 => 5,
            $level >= 50 => 4,
            $level >= 25 => 3,
            $level >= 10 => 2,
            default => 1,
        };

        $acceptedLoadoutRuns = 0;
        $pendingLoadoutRuns = 0;

        if (Schema::hasTable('loadout_challenge_submissions')) {
            $acceptedLoadoutRuns = $profileUser->loadoutChallengeSubmissions()
                ->where('status', LoadoutChallengeSubmission::STATUS_ACCEPTED)
                ->count();

            $pendingLoadoutRuns = $profileUser->loadoutChallengeSubmissions()
                ->where('status', LoadoutChallengeSubmission::STATUS_PENDING)
                ->count();
        }

        $trophyStats = $trophyCabinet['stats'] ?? [];
        $cupPlacements = collect($trophyCabinet['cup_placements'] ?? []);
        $momentSpotlights = collect($trophyCabinet['moment_spotlights'] ?? []);
        $bestPlacement = $cupPlacements->first();
        $bestMoment = $trophyCabinet['best_moment'] ?? null;

        return [
            'tier' => $tierNumber,
            'tier_roman' => $this->romanTier($tierNumber),
            'level' => $level,
            'xp_total' => $xpTotal,
            'level_progress' => $levelProgress,
            'role_label' => $profileUser->profile?->hunt_role
                ?: $profileUser->profile?->playstyle
                ?: null,
            'stats' => [
                'cup_points' => (int) ($trophyStats['cup_points'] ?? 0),
                'bounties' => (int) ($trophyStats['cup_bounty_tokens'] ?? 0),
                'kills' => (int) ($trophyStats['cup_kills'] ?? 0),
                'accepted_loadout_runs' => (int) $acceptedLoadoutRuns,
                'pending_loadout_runs' => (int) $pendingLoadoutRuns,
                'badges' => (int) ($trophyStats['badges'] ?? 0),
                'moments' => (int) ($trophyStats['moments'] ?? 0),
                'moment_spotlights' => $momentSpotlights->count(),
            ],
            'best_placement' => $bestPlacement,
            'best_moment' => $bestMoment,
        ];
    }

    private function romanTier(int $tier): string
    {
        return match (max(1, min(5, $tier))) {
            5 => 'V',
            4 => 'IV',
            3 => 'III',
            2 => 'II',
            default => 'I',
        };
    }


    private function useSocialiteProfileView(Request $request): bool
    {
        if ($request->boolean('classic_profile')) {
            return false;
        }

        return $request->routeIs('design.socialite.profile*')
            || (HntTheme::profileEnabled() && HntTheme::hasResolvedOverride('profile.show'));
    }

    private function profileThemeView(Request $request, string $baseView, string $forcedSocialiteView): string
    {
        if ($request->boolean('classic_profile')) {
            return $baseView;
        }

        if ($request->routeIs('design.socialite.profile*')) {
            return $forcedSocialiteView;
        }

        return HntTheme::profileEnabled() ? HntTheme::resolve($baseView) : $baseView;
    }

    private function profileSectionFromRoute(Request $request): string
    {
        $activeSection = (string) $request->route('socialite_section', '');

        if ($activeSection === '') {
            $routeName = (string) $request->route()?->getName();
            $activeSection = match (true) {
                str_contains($routeName, '.about') => 'about',
                str_contains($routeName, '.friends') => 'friends',
                str_contains($routeName, '.badges') => 'badges',
                str_contains($routeName, '.trophies') => 'trophies',
                str_contains($routeName, '.teams') => 'teams',
                str_contains($routeName, '.contact') => 'contact',
                default => 'timeline',
            };
        }

        return in_array($activeSection, ['timeline', 'about', 'friends', 'badges', 'trophies', 'teams', 'contact'], true)
            ? $activeSection
            : 'timeline';
    }

    private function canRequestFriend(?User $viewer, User $profileUser, ?Friendship $friendship, bool $isOwnProfile, bool $profileIsBlocked): bool
    {
        return $viewer !== null
            && ! $isOwnProfile
            && ! $profileIsBlocked
            && ! $friendship
            && $profileUser->status === 'active';
    }

    private function canMessageProfile(?User $viewer, User $profileUser, ?Friendship $friendship, bool $isOwnProfile, bool $profileIsBlocked): bool
    {
        if (! $viewer || $isOwnProfile || $profileIsBlocked || $profileUser->status !== 'active') {
            return false;
        }

        $profileUser->loadMissing('privacySettings');
        $allowMessagesFrom = (string) ($profileUser->privacySettings?->allow_messages_from ?? 'registered');

        return match ($allowMessagesFrom) {
            'everyone', 'registered' => true,
            'following' => $friendship?->isAccepted() ?? false,
            'nobody' => false,
            default => false,
        };
    }

    private function reworkSettingsData(?User $viewer): array
    {
        if (! $viewer) {
            return [
                'reworkNotificationSettings' => null,
                'reworkNotificationGroups' => NotificationSettingsGroups::all(),
                'reworkPrivacySettings' => null,
                'reworkBlockedUsers' => collect(),
                'reworkTwoFactorEnabled' => false,
                'reworkTwoFactorRecoveryCount' => 0,
                'reworkDeletionRequest' => null,
            ];
        }

        $viewer->loadMissing(['privacySettings', 'accountDeletionRequest']);
        $twoFactor = app(TwoFactorService::class);

        return [
            'reworkNotificationSettings' => $viewer->notificationSettings()->firstOrCreate([]),
            'reworkNotificationGroups' => NotificationSettingsGroups::all(),
            'reworkPrivacySettings' => $viewer->privacySettings ?: $viewer->privacySettings()->create(),
            'reworkBlockedUsers' => $viewer->blockedUsers()
                ->with('blockedUser')
                ->latest()
                ->limit(10)
                ->get(),
            'reworkTwoFactorEnabled' => $viewer->hasTwoFactorEnabled(),
            'reworkTwoFactorRecoveryCount' => $twoFactor->recoveryCodeCount($viewer),
            'reworkDeletionRequest' => $viewer->accountDeletionRequest,
        ];
    }

    public function edit(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing('profile');

        if (! $user->profile) {
            $user->profile()->create([
                'profile_visibility' => 'public',
            ]);
            $user->load('profile');
        }

        $profileEditView = $this->profileThemeView($request, 'profile.edit', 'themes.socialite.profile.edit');

        return view($profileEditView, [
            'user' => $user,
        ]);
    }

    public function update(Request $request, MediaService $mediaService, GamificationService $gamification, ReferralService $referrals): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1200'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'hunt_role' => ['nullable', 'string', 'max:60'],
            'discord_name' => ['nullable', 'string', 'max:80'],
            'steam_url' => ['nullable', 'url', 'max:255'],
            'twitch_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'is_lfg_available' => ['nullable', 'boolean'],
            'profile_visibility' => ['required', 'in:public,registered,private'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('hunthub.upload_limits.profile_avatar_kb', 2048)],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('hunthub.upload_limits.profile_cover_kb', 4096)],
        ]);

        if ($request->hasFile('avatar')) {
            $mediaService->assertAllowed($request->file('avatar'), $user, 'profile_avatar');
        }

        if ($request->hasFile('cover')) {
            $mediaService->assertAllowed($request->file('cover'), $user, 'profile_cover');
        }

        $user->name = trim($validated['name']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $avatarAsset = $mediaService->store($request->file('avatar'), $user, 'profile_avatar', [
                'attachable' => $user,
                'visibility' => 'public',
            ]);
            $user->avatar_path = $avatarAsset->path;
        }

        if ($request->hasFile('cover')) {
            if ($user->cover_path) {
                Storage::disk('public')->delete($user->cover_path);
            }
            $coverAsset = $mediaService->store($request->file('cover'), $user, 'profile_cover', [
                'attachable' => $user,
                'visibility' => 'public',
            ]);
            $user->cover_path = $coverAsset->path;
        }

        $user->save();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'headline' => $validated['headline'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'platform' => $validated['platform'] ?? null,
                'playstyle' => $validated['playstyle'] ?? null,
                'region' => $validated['region'] ?? null,
                'language' => $validated['language'] ?? null,
                'hunt_role' => $validated['hunt_role'] ?? null,
                'discord_name' => $validated['discord_name'] ?? null,
                'steam_url' => $validated['steam_url'] ?? null,
                'twitch_url' => $validated['twitch_url'] ?? null,
                'youtube_url' => $validated['youtube_url'] ?? null,
                'is_lfg_available' => (bool) ($validated['is_lfg_available'] ?? false),
                'profile_visibility' => $validated['profile_visibility'],
            ]
        );

        $gamification->evaluateProfile($user);
        $freshUser = $user->fresh(['profile']) ?? $user;
        $referrals->syncProfileCompletion($freshUser);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('ui.profile_saved'),
                'profile' => [
                    'name' => $freshUser->name,
                    'headline' => $freshUser->profile?->headline,
                    'avatar_url' => $freshUser->avatarUrl(),
                    'cover_url' => $freshUser->coverUrl(),
                    'completion' => \App\Support\ProfileCompletion::score($freshUser),
                    'profile_url' => route('profile.show'),
                ],
            ]);
        }

        return redirect()
            ->route('profile.show')
            ->with('status', __('ui.profile_saved'));
    }

    public function updateMedia(Request $request, MediaService $mediaService): JsonResponse
    {
        $type = (string) $request->input('type');
        $maxKb = $type === 'cover'
            ? config('hunthub.upload_limits.profile_cover_kb', 4096)
            : config('hunthub.upload_limits.profile_avatar_kb', 2048);

        $validated = $request->validate([
            'type' => ['required', 'in:avatar,cover'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
        ]);

        $user = $request->user();
        $file = $request->file('image');
        $mediaType = $validated['type'];
        $context = $mediaType === 'cover' ? 'profile_cover' : 'profile_avatar';
        $pathColumn = $mediaType === 'cover' ? 'cover_path' : 'avatar_path';

        $mediaService->assertAllowed($file, $user, $context);

        if ($user->{$pathColumn}) {
            Storage::disk('public')->delete($user->{$pathColumn});
        }

        $mediaAsset = $mediaService->store($file, $user, $context, [
            'attachable' => $user,
            'visibility' => 'public',
        ]);

        $user->{$pathColumn} = $mediaAsset->path;
        $user->save();

        $freshUser = $user->fresh(['profile']) ?? $user;

        return response()->json([
            'message' => $mediaType === 'cover' ? 'Cover updated.' : 'Avatar updated.',
            'type' => $mediaType,
            'avatar_url' => $freshUser->avatarUrl(),
            'cover_url' => $freshUser->coverUrl(),
        ]);
    }

}
