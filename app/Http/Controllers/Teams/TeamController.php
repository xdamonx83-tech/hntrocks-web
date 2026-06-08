<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Support\HntTheme;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Friendship;
use App\Models\Cup;
use App\Models\LfgPost;
use App\Models\User;
use App\Services\MediaService;
use App\Services\GamificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $query = Team::query()
            ->with([
                'owner.profile',
                'activeMembers.user.profile',
            ])
            ->withCount([
                'activeMembers as members_count',
                'pendingMembers as pending_count',
                'feedPosts as posts_count' => function ($postQuery): void {
                    $postQuery->where('status', 'published');
                },
            ])
            ->where('status', 'active')
            ->where(function ($query) use ($request): void {
                $query->where('visibility', 'public')
                    ->orWhere('owner_id', $request->user()->id)
                    ->orWhereHas('members', function ($memberQuery) use ($request): void {
                        $memberQuery
                            ->where('user_id', $request->user()->id)
                            ->where('status', 'active');
                    });
            });

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        foreach (['platform', 'playstyle', 'region', 'language'] as $field) {
            if ($value = $request->query($field)) {
                $query->where($field, $value);
            }
        }

        if ($request->boolean('recruiting')) {
            $query->where('recruitment_status', 'open');
        }

        $sort = $request->query('sort', 'newest');

        match ($sort) {
            'members' => $query->orderByDesc('members_count')->orderByDesc('created_at'),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        $teams = $query->paginate(12)->withQueryString();

        $managedTeams = Team::query()
            ->with(['owner.profile'])
            ->withCount([
                'activeMembers as members_count',
                'pendingMembers as pending_count',
            ])
            ->where('status', 'active')
            ->whereHas('members', function ($memberQuery) use ($request): void {
                $memberQuery
                    ->where('user_id', $request->user()->id)
                    ->where('status', 'active')
                    ->whereIn('role', ['owner', 'officer']);
            })
            ->latest()
            ->take(10)
            ->get();

        $memberSuggestions = User::query()
            ->with(['profile'])
            ->withCount(['activeTeams'])
            ->where('users.id', '!=', $request->user()->id)
            ->where('users.status', 'active')
            ->whereHas('profile', function ($profileQuery) use ($request): void {
                $profileQuery->where(function ($visibilityQuery) use ($request): void {
                    $visibilityQuery
                        ->whereIn('profile_visibility', ['public', 'registered'])
                        ->orWhere('user_id', $request->user()->id);
                });
            })
            ->latest('users.created_at')
            ->take(4)
            ->get();

        $openLfgPosts = LfgPost::query()
            ->with(['user.profile'])
            ->withCount(['pendingApplications as pending_applications_count'])
            ->where('status', 'open')
            ->where('visibility', 'public')
            ->latest()
            ->take(4)
            ->get();

        $featuredCups = Cup::query()
            ->visible()
            ->withCount(['activeTeams as participants_count'])
            ->whereIn('status', ['active', 'planned'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'planned' THEN 1 ELSE 2 END")
            ->orderByDesc('starts_at')
            ->take(4)
            ->get();

        $view = HntTheme::teamsEnabled() && ! $request->boolean('classic_teams')
            ? 'themes.socialite.teams.index'
            : 'teams.index';

        return view($view, [
            'teams' => $teams,
            'filters' => $request->only(['q', 'platform', 'playstyle', 'region', 'language', 'recruiting', 'sort']),
            'managedTeams' => $managedTeams,
            'memberSuggestions' => $memberSuggestions,
            'openLfgPosts' => $openLfgPosts,
            'featuredCups' => $featuredCups,
        ]);
    }


    public function manage(Request $request): View
    {
        $managedTeams = Team::query()
            ->with(['owner.profile'])
            ->withCount([
                'activeMembers as members_count',
                'pendingMembers as pending_count',
            ])
            ->where('status', 'active')
            ->whereHas('members', function ($query) use ($request): void {
                $query
                    ->where('user_id', $request->user()->id)
                    ->where('status', 'active')
                    ->whereIn('role', ['owner', 'officer']);
            })
            ->latest()
            ->get();

        return view('teams.manage', [
            'managedTeams' => $managedTeams,
        ]);
    }

    public function invitations(Request $request): View
    {
        $requests = TeamMember::query()
            ->with(['team.owner.profile', 'team.activeMembers', 'user.profile'])
            ->where('status', 'pending')
            ->whereHas('team', function ($teamQuery) use ($request): void {
                $teamQuery
                    ->where('status', 'active')
                    ->whereHas('members', function ($memberQuery) use ($request): void {
                        $memberQuery
                            ->where('user_id', $request->user()->id)
                            ->where('status', 'active')
                            ->whereIn('role', ['owner', 'officer']);
                    });
            })
            ->latest()
            ->get();

        return view('teams.invitations', [
            'requests' => $requests,
        ]);
    }

    public function create(Request $request): View
    {
        $view = HntTheme::teamsEnabled() && ! $request->boolean('classic_teams')
            ? 'themes.socialite.teams.create'
            : 'teams.create';

        return view($view);
    }

    public function store(Request $request, MediaService $mediaService, GamificationService $gamification): RedirectResponse
    {
        $validated = $this->validatedTeamData($request);

        if ($request->hasFile('avatar')) {
            $mediaService->assertAllowed($request->file('avatar'), $request->user(), 'team_avatar');
        }

        if ($request->hasFile('cover')) {
            $mediaService->assertAllowed($request->file('cover'), $request->user(), 'team_cover');
        }

        $slug = $this->uniqueSlug($validated['name']);

        $team = Team::create([
            ...$validated,
            'owner_id' => $request->user()->id,
            'slug' => $slug,
            'status' => 'active',
        ]);

        if ($request->hasFile('avatar')) {
            $avatarAsset = $mediaService->store($request->file('avatar'), $request->user(), 'team_avatar', [
                'attachable' => $team,
                'visibility' => $team->visibility,
            ]);
            $team->avatar_path = $avatarAsset->path;
        }

        if ($request->hasFile('cover')) {
            $coverAsset = $mediaService->store($request->file('cover'), $request->user(), 'team_cover', [
                'attachable' => $team,
                'visibility' => $team->visibility,
            ]);
            $team->cover_path = $coverAsset->path;
        }

        $team->save();

        $team->members()->create([
            'user_id' => $request->user()->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $gamification->award($request->user(), 'team_created', source: $team);

        return redirect()->route('teams.show', $team)->with('status', __('ui.team_created_status'));
    }

    public function show(Request $request, Team $team): View
    {
        return $this->renderTeamPage($request, $team, 'timeline');
    }

    public function info(Request $request, Team $team): View
    {
        return $this->renderTeamPage($request, $team, 'info');
    }

    public function teamLfg(Request $request, Team $team): View
    {
        $team->loadMissing(['members']);

        if ($team->visibility === 'private' && ! $team->isActiveMember($request->user())) {
            abort(404);
        }

        $teamPageLfgPosts = $team->teamLfgPosts()
            ->with(['user.profile'])
            ->withCount(['pendingApplications as pending_applications_count'])
            ->where('status', 'open')
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return $this->renderTeamPage($request, $team, 'team-lfg', [
            'teamPageLfgPosts' => $teamPageLfgPosts,
        ]);
    }

    public function members(Request $request, Team $team): View
    {
        $team->loadMissing([
            'owner.profile',
            'members',
        ]);
        $team->loadCount([
            'activeMembers as members_count',
            'pendingMembers as pending_count',
        ]);

        if ($team->visibility === 'private' && ! $team->isActiveMember($request->user())) {
            abort(404);
        }

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'role' => ['nullable', 'in:all,owner,officer,member'],
        ]);

        $members = $team->members()
            ->with([
                'user' => function ($userQuery): void {
                    $userQuery
                        ->with([
                            'profile',
                            'badges' => fn ($badgeQuery) => $badgeQuery->orderBy('badges.sort_order'),
                        ])
                        ->withCount([
                            'badges',
                            'activeTeams',
                            'feedPosts as visible_feed_posts_count' => function ($postQuery): void {
                                $postQuery->where('status', 'published')->where('visibility', '!=', 'private');
                            },
                        ]);
                },
            ])
            ->where('status', 'active')
            ->when(filled($filters['q'] ?? null), function ($memberQuery) use ($filters): void {
                $term = trim((string) $filters['q']);

                $memberQuery->whereHas('user', function ($userQuery) use ($term): void {
                    $userQuery
                        ->where('name', 'like', '%' . $term . '%')
                        ->orWhere('username', 'like', '%' . $term . '%')
                        ->orWhereHas('profile', function ($profileQuery) use ($term): void {
                            $profileQuery
                                ->where('headline', 'like', '%' . $term . '%')
                                ->orWhere('bio', 'like', '%' . $term . '%')
                                ->orWhere('discord_name', 'like', '%' . $term . '%');
                        });
                });
            })
            ->when(($filters['role'] ?? 'all') !== 'all', function ($memberQuery) use ($filters): void {
                $memberQuery->where('role', $filters['role']);
            })
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'officer' THEN 1 ELSE 2 END")
            ->orderByDesc('joined_at')
            ->paginate(12)
            ->withQueryString();

        $memberUserIds = $members->getCollection()
            ->pluck('user_id')
            ->map(fn ($userId) => (int) $userId)
            ->filter(fn ($userId) => $userId > 0)
            ->values()
            ->all();

        $friendshipMap = empty($memberUserIds)
            ? collect()
            : Friendship::query()
                ->forUser($request->user())
                ->where(function ($friendshipQuery) use ($memberUserIds): void {
                    $friendshipQuery
                        ->whereIn('user_one_id', $memberUserIds)
                        ->orWhereIn('user_two_id', $memberUserIds);
                })
                ->get()
                ->mapWithKeys(function (Friendship $friendship) use ($request): array {
                    $otherUserId = (int) $friendship->user_one_id === (int) $request->user()->id
                        ? (int) $friendship->user_two_id
                        : (int) $friendship->user_one_id;

                    return [$otherUserId => $friendship];
                });

        $friendCounts = array_fill_keys($memberUserIds, 0);

        if (! empty($memberUserIds)) {
            Friendship::query()
                ->where('status', Friendship::STATUS_ACCEPTED)
                ->where(function ($friendshipQuery) use ($memberUserIds): void {
                    $friendshipQuery
                        ->whereIn('user_one_id', $memberUserIds)
                        ->orWhereIn('user_two_id', $memberUserIds);
                })
                ->get(['user_one_id', 'user_two_id'])
                ->each(function (Friendship $friendship) use (&$friendCounts): void {
                    if (array_key_exists((int) $friendship->user_one_id, $friendCounts)) {
                        $friendCounts[(int) $friendship->user_one_id]++;
                    }

                    if (array_key_exists((int) $friendship->user_two_id, $friendCounts)) {
                        $friendCounts[(int) $friendship->user_two_id]++;
                    }
                });
        }

        if (HntTheme::teamsEnabled() && ! $request->boolean('classic_teams')) {
            return $this->renderTeamPage($request, $team, 'members', [
                'teamPageMembers' => $members,
                'filters' => $filters,
                'friendshipMap' => $friendshipMap,
                'friendCounts' => $friendCounts,
            ]);
        }

        return view('teams.members', [
            'team' => $team,
            'members' => $members,
            'filters' => $filters,
            'viewerMembership' => $team->membershipFor($request->user()),
            'canManage' => $team->canManage($request->user()),
            'friendshipMap' => $friendshipMap,
            'friendCounts' => $friendCounts,
        ]);
    }

    public function edit(Request $request, Team $team): View
    {
        $team->loadMissing('members');
        abort_unless($team->canManage($request->user()), 403);

        $view = HntTheme::teamsEnabled() && ! $request->boolean('classic_teams')
            ? 'themes.socialite.teams.edit'
            : 'teams.edit';

        return view($view, [
            'team' => $team,
        ]);
    }

    public function update(Request $request, Team $team, MediaService $mediaService): RedirectResponse
    {
        $team->loadMissing('members');
        abort_unless($team->canManage($request->user()), 403);

        $validated = $this->validatedTeamData($request);

        if ($request->hasFile('avatar')) {
            $mediaService->assertAllowed($request->file('avatar'), $request->user(), 'team_avatar');
        }

        if ($request->hasFile('cover')) {
            $mediaService->assertAllowed($request->file('cover'), $request->user(), 'team_cover');
        }

        $team->fill($validated);

        if ($request->hasFile('avatar')) {
            if ($team->avatar_path) {
                Storage::disk('public')->delete($team->avatar_path);
            }
            $avatarAsset = $mediaService->store($request->file('avatar'), $request->user(), 'team_avatar', [
                'attachable' => $team,
                'visibility' => $team->visibility,
            ]);
            $team->avatar_path = $avatarAsset->path;
        }

        if ($request->hasFile('cover')) {
            if ($team->cover_path) {
                Storage::disk('public')->delete($team->cover_path);
            }
            $coverAsset = $mediaService->store($request->file('cover'), $request->user(), 'team_cover', [
                'attachable' => $team,
                'visibility' => $team->visibility,
            ]);
            $team->cover_path = $coverAsset->path;
        }

        $team->save();

        return redirect()->route('teams.show', $team)->with('status', __('ui.team_saved_status'));
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        $team->loadMissing('members');
        abort_unless($team->isOwner($request->user()), 403);

        $team->update(['status' => 'archived']);
        $team->delete();

        return redirect()->route('teams.index')->with('status', __('ui.team_archived_status'));
    }

    private function renderTeamPage(Request $request, Team $team, string $activeTeamSection = 'timeline', array $extra = []): View
    {
        $team->loadMissing([
            'owner.profile',
            'members.user.profile',
        ]);
        $team->loadCount([
            'activeMembers as members_count',
            'pendingMembers as pending_count',
        ]);

        if ($team->visibility === 'private' && ! $team->isActiveMember($request->user())) {
            abort(404);
        }

        $teamLfgPosts = $team->teamLfgPosts()
            ->with(['user.profile'])
            ->withCount(['pendingApplications as pending_applications_count'])
            ->where('status', 'open')
            ->latest()
            ->take(3)
            ->get();

        $teamLfgOpenCount = $team->teamLfgPosts()
            ->where('status', 'open')
            ->count();

        $teamFeedPosts = $team->feedPosts()
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
            ])
            ->withCount(['comments', 'reactions', 'bookmarks'])
            ->where('status', 'published')
            ->latest()
            ->take(10)
            ->get();

        $activeMemberUserIds = $team->members
            ->where('status', 'active')
            ->pluck('user_id')
            ->filter(fn ($userId) => (int) $userId !== (int) $request->user()->id)
            ->values();

        $teamFriendshipMap = $activeMemberUserIds->isEmpty()
            ? collect()
            : Friendship::query()
                ->forUser($request->user())
                ->where(function ($query) use ($activeMemberUserIds): void {
                    $query
                        ->whereIn('user_one_id', $activeMemberUserIds)
                        ->orWhereIn('user_two_id', $activeMemberUserIds);
                })
                ->get()
                ->mapWithKeys(function (Friendship $friendship) use ($request): array {
                    $otherUserId = (int) $friendship->user_one_id === (int) $request->user()->id
                        ? (int) $friendship->user_two_id
                        : (int) $friendship->user_one_id;

                    return [$otherUserId => $friendship];
                });

        $view = HntTheme::teamsEnabled() && ! $request->boolean('classic_teams')
            ? 'themes.socialite.teams.show'
            : 'teams.show';

        return view($view, array_merge([
            'team' => $team,
            'activeTeamSection' => $activeTeamSection,
            'viewerMembership' => $team->membershipFor($request->user()),
            'canManage' => $team->canManage($request->user()),
            'canPostToTeam' => $team->isActiveMember($request->user()),
            'teamLfgPosts' => $teamLfgPosts,
            'teamLfgOpenCount' => $teamLfgOpenCount,
            'teamFeedPosts' => $teamFeedPosts,
            'teamFriendshipMap' => $teamFriendshipMap,
        ], $extra));
    }

    private function validatedTeamData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:2500'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'visibility' => ['required', 'string', 'in:public,private'],
            'recruitment_status' => ['required', 'string', 'in:open,closed'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('hunthub.upload_limits.team_avatar_kb', 2048)],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('hunthub.upload_limits.team_cover_kb', 4096)],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'team';
        $slug = $base;
        $counter = 2;

        while (Team::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
