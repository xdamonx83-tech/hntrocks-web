<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TeamResource;
use App\Http\Resources\Api\UserResource;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\GamificationService;
use App\Services\NotificationService;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ApiTeamsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sort = (string) $request->query('sort', 'newest');

        if (! in_array($sort, ['newest', 'members', 'name'], true)) {
            $sort = 'newest';
        }

        $teams = Team::query()
            ->with('owner.profile')
            ->withCount('activeMembers')
            ->where('status', 'active')
            ->where(function ($query) use ($request): void {
                $query->where('visibility', 'public')
                    ->orWhere('owner_id', $request->user()->id)
                    ->orWhereHas('members', function ($memberQuery) use ($request): void {
                        $memberQuery
                            ->where('user_id', $request->user()->id)
                            ->where('status', 'active');
                    });
            })
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = trim((string) $request->query('q'));

                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('tagline', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('platform'), fn ($query) => $query->where('platform', $request->string('platform')))
            ->when($request->filled('playstyle'), fn ($query) => $query->where('playstyle', $request->string('playstyle')))
            ->when($request->filled('region'), fn ($query) => $query->where('region', $request->string('region')))
            ->when($request->filled('language'), fn ($query) => $query->where('language', $request->string('language')))
            ->when(
                in_array($request->query('recruitment_status'), ['open', 'closed'], true),
                fn ($query) => $query->where('recruitment_status', $request->query('recruitment_status'))
            )
            ->when(
                $sort === 'members',
                fn ($query) => $query->orderByDesc('active_members_count')->orderByDesc('created_at')
            )
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when($sort === 'newest', fn ($query) => $query->latest())
            ->paginate(24)
            ->withQueryString();

        return TeamResource::collection($teams);
    }

    public function store(Request $request, GamificationService $gamification): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:2500'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'visibility' => ['required', 'string', 'in:public,private'],
            'recruitment_status' => ['required', 'string', 'in:open,closed'],
        ]);

        $team = Team::create([
            ...$validated,
            'owner_id' => $request->user()->id,
            'slug' => $this->uniqueSlug($validated['name']),
            'status' => 'active',
        ]);

        $team->members()->create([
            'user_id' => $request->user()->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $gamification->award($request->user(), 'team_created', source: $team);

        return response()->json(array_merge(
            ['message' => __('ui.team_created_status')],
            $this->teamPayload($request, $team->fresh())
        ), 201);
    }

    public function show(Request $request, Team $team): JsonResponse
    {
        return response()->json($this->teamPayload($request, $team));
    }

    public function update(Request $request, Team $team): JsonResponse
    {
        $team->loadMissing('members');
        abort_unless($team->canManage($request->user()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:2500'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'visibility' => ['required', 'string', 'in:public,private'],
            'recruitment_status' => ['required', 'string', 'in:open,closed'],
        ]);

        $team->fill($validated)->save();

        return response()->json(array_merge(
            ['message' => 'Team wurde gespeichert.'],
            $this->teamPayload($request, $team->fresh())
        ));
    }

    public function updateAvatar(Request $request, Team $team, MediaService $mediaService): JsonResponse
    {
        $team->loadMissing('members');
        abort_unless($team->canManage($request->user()), 403);

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('hunthub.upload_limits.team_avatar_kb', 2048)],
        ]);

        if ($team->avatar_path) {
            Storage::disk('public')->delete($team->avatar_path);
        }

        $asset = $mediaService->store($request->file('avatar'), $request->user(), 'team_avatar', [
            'attachable' => $team,
            'visibility' => $team->visibility,
        ]);

        $team->forceFill(['avatar_path' => $asset->path])->save();

        return response()->json(array_merge(
            ['message' => 'Team-Avatar wurde aktualisiert.'],
            $this->teamPayload($request, $team->fresh())
        ));
    }

    public function updateCover(Request $request, Team $team, MediaService $mediaService): JsonResponse
    {
        $team->loadMissing('members');
        abort_unless($team->canManage($request->user()), 403);

        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('hunthub.upload_limits.team_cover_kb', 4096)],
        ]);

        if ($team->cover_path) {
            Storage::disk('public')->delete($team->cover_path);
        }

        $asset = $mediaService->store($request->file('cover'), $request->user(), 'team_cover', [
            'attachable' => $team,
            'visibility' => $team->visibility,
        ]);

        $team->forceFill(['cover_path' => $asset->path])->save();

        return response()->json(array_merge(
            ['message' => 'Team-Titelbild wurde aktualisiert.'],
            $this->teamPayload($request, $team->fresh())
        ));
    }

    public function join(
        Request $request,
        Team $team,
        NotificationService $notifications,
        GamificationService $gamification
    ): JsonResponse {
        $team->loadMissing(['members', 'owner']);

        if ($team->visibility !== 'public') {
            abort(404);
        }

        if ($team->recruitment_status !== 'open') {
            return response()->json([
                'message' => __('ui.team_not_recruiting'),
            ], 422);
        }

        if ($team->isActiveMember($request->user())) {
            return response()->json(array_merge(
                ['message' => __('ui.team_already_member')],
                $this->teamPayload($request, $team)
            ));
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $membership = $team->members()->where('user_id', $request->user()->id)->first();

        if ($membership && $membership->status === 'pending') {
            return response()->json(array_merge(
                ['message' => __('ui.team_join_pending')],
                $this->teamPayload($request, $team)
            ));
        }

        if ($membership) {
            $membership->update([
                'role' => 'member',
                'status' => 'pending',
                'message' => $validated['message'] ?? null,
                'accepted_by' => null,
                'joined_at' => null,
            ]);
        } else {
            $membership = $team->members()->create([
                'user_id' => $request->user()->id,
                'role' => 'member',
                'status' => 'pending',
                'message' => $validated['message'] ?? null,
            ]);
        }

        $gamification->award($request->user(), 'team_join_requested', source: $membership);
        $notifications->send(
            $team->owner,
            $request->user(),
            'team_join_request',
            __('ui.team_join_request_title'),
            __('ui.team_join_request_body', ['name' => $request->user()->name]),
            route('teams.show', $team)
        );

        return response()->json(array_merge(
            ['message' => __('ui.team_join_sent')],
            $this->teamPayload($request, $team->fresh())
        ), 201);
    }

    public function acceptJoinRequest(
        Request $request,
        Team $team,
        TeamMember $member,
        NotificationService $notifications,
        GamificationService $gamification
    ): JsonResponse {
        $team->loadMissing('members');
        abort_unless($team->canManage($request->user()), 403);
        abort_unless((int) $member->team_id === (int) $team->id, 404);

        if ($member->status !== 'pending') {
            return response()->json([
                'message' => 'Diese Anfrage ist nicht mehr offen.',
            ], 422);
        }

        $member->update([
            'status' => 'active',
            'role' => 'member',
            'accepted_by' => $request->user()->id,
            'joined_at' => now(),
        ]);

        $member->loadMissing('user');
        if ($member->user) {
            $gamification->award($member->user, 'team_joined', source: $member);
            $notifications->send(
                $member->user,
                $request->user(),
                'team_join_accepted',
                __('ui.team_join_accepted_title'),
                __('ui.team_join_accepted_body', ['team' => $team->name]),
                route('teams.show', $team)
            );
        }

        return response()->json(array_merge(
            ['message' => __('ui.team_join_accepted_status')],
            $this->teamPayload($request, $team->fresh())
        ));
    }

    public function rejectJoinRequest(
        Request $request,
        Team $team,
        TeamMember $member,
        NotificationService $notifications
    ): JsonResponse {
        $team->loadMissing('members');
        abort_unless($team->canManage($request->user()), 403);
        abort_unless((int) $member->team_id === (int) $team->id, 404);

        if ($member->status !== 'pending') {
            return response()->json([
                'message' => 'Diese Anfrage ist nicht mehr offen.',
            ], 422);
        }

        $member->update([
            'status' => 'declined',
            'accepted_by' => $request->user()->id,
        ]);

        $member->loadMissing('user');
        if ($member->user) {
            $notifications->send(
                $member->user,
                $request->user(),
                'team_join_rejected',
                __('ui.team_join_rejected_title'),
                __('ui.team_join_rejected_body', ['team' => $team->name]),
                route('teams.show', $team)
            );
        }

        return response()->json(array_merge(
            ['message' => __('ui.team_join_rejected_status')],
            $this->teamPayload($request, $team->fresh())
        ));
    }

    public function leave(Request $request, Team $team): JsonResponse
    {
        $team->loadMissing('members');
        $membership = $team->membershipFor($request->user());

        if (! $membership || $membership->status !== 'active') {
            return response()->json([
                'message' => __('ui.team_not_member'),
            ], 422);
        }

        if ($membership->role === 'owner') {
            return response()->json([
                'message' => __('ui.team_owner_leave_blocked'),
            ], 422);
        }

        $membership->delete();

        return response()->json([
            'message' => __('ui.team_left'),
            'left' => true,
            'team_id' => $team->id,
            'team_slug' => $team->slug,
        ]);
    }

    public function archive(Request $request, Team $team): JsonResponse
    {
        $team->loadMissing('members');
        abort_unless($team->isOwner($request->user()), 403);

        if ($team->status === 'archived') {
            return response()->json([
                'message' => __('ui.team_archived_status'),
                'archived' => true,
                'team_id' => $team->id,
                'team_slug' => $team->slug,
            ]);
        }

        $team->update(['status' => 'archived']);
        $team->delete();

        return response()->json([
            'message' => __('ui.team_archived_status'),
            'archived' => true,
            'team_id' => $team->id,
            'team_slug' => $team->slug,
        ]);
    }

    public function promoteMember(Request $request, Team $team, TeamMember $member): JsonResponse
    {
        $team->loadMissing('members');
        abort_unless($team->isOwner($request->user()), 403);
        abort_unless((int) $member->team_id === (int) $team->id, 404);

        if ($member->status !== 'active') {
            return response()->json([
                'message' => __('ui.team_member_required_officer'),
            ], 422);
        }

        if ($member->role === 'owner') {
            return response()->json([
                'message' => __('ui.team_owner_top_role'),
            ], 422);
        }

        if ($member->role === 'officer') {
            return response()->json(array_merge(
                ['message' => __('ui.team_already_officer')],
                $this->teamPayload($request, $team->fresh())
            ));
        }

        $member->update([
            'role' => 'officer',
            'accepted_by' => $request->user()->id,
        ]);

        return response()->json(array_merge(
            ['message' => __('ui.team_promoted_officer')],
            $this->teamPayload($request, $team->fresh())
        ));
    }

    public function demoteMember(Request $request, Team $team, TeamMember $member): JsonResponse
    {
        $team->loadMissing('members');
        abort_unless($team->isOwner($request->user()), 403);
        abort_unless((int) $member->team_id === (int) $team->id, 404);

        if ($member->status !== 'active') {
            return response()->json([
                'message' => __('ui.team_member_required_manage'),
            ], 422);
        }

        if ($member->role === 'owner') {
            return response()->json([
                'message' => __('ui.team_owner_cannot_demote'),
            ], 422);
        }

        if ($member->role !== 'officer') {
            return response()->json(array_merge(
                ['message' => __('ui.team_not_officer')],
                $this->teamPayload($request, $team->fresh())
            ));
        }

        $member->update([
            'role' => 'member',
            'accepted_by' => $request->user()->id,
        ]);

        return response()->json(array_merge(
            ['message' => __('ui.team_demoted_member')],
            $this->teamPayload($request, $team->fresh())
        ));
    }

    public function removeMember(
        Request $request,
        Team $team,
        TeamMember $member,
        NotificationService $notifications
    ): JsonResponse {
        $team->loadMissing(['members', 'owner']);
        $viewerMembership = $team->membershipFor($request->user());
        abort_unless($viewerMembership !== null && $viewerMembership->status === 'active', 403);
        abort_unless(in_array($viewerMembership->role, ['owner', 'officer'], true), 403);
        abort_unless((int) $member->team_id === (int) $team->id, 404);

        if ($member->status !== 'active') {
            return response()->json([
                'message' => __('ui.team_member_required_manage'),
            ], 422);
        }

        if ((int) $member->user_id === (int) $request->user()->id) {
            return response()->json([
                'message' => 'Nutze „Team verlassen“ für deine eigene Mitgliedschaft.',
            ], 422);
        }

        if ($member->role === 'owner') {
            return response()->json([
                'message' => __('ui.team_owner_top_role'),
            ], 422);
        }

        if ($viewerMembership->role !== 'owner' && $member->role !== 'member') {
            return response()->json([
                'message' => 'Officer können nur normale Mitglieder entfernen.',
            ], 403);
        }

        $member->loadMissing('user');
        $removedUser = $member->user;
        $member->delete();

        if ($removedUser) {
            $notifications->send(
                $removedUser,
                $request->user(),
                'team_member_removed',
                'Aus Team entfernt',
                'Du wurdest aus dem Team „'.$team->name.'“ entfernt.',
                route('teams.show', $team)
            );
        }

        return response()->json(array_merge(
            ['message' => 'Mitglied wurde entfernt.'],
            $this->teamPayload($request, $team->fresh())
        ));
    }

    private function teamPayload(Request $request, Team $team): array
    {
        $team->loadMissing([
            'owner.profile',
            'members',
            'activeMembers.user.profile',
            'pendingMembers.user.profile',
        ]);

        $team->loadCount([
            'activeMembers as active_members_count',
            'pendingMembers as pending_members_count',
        ]);

        if ($team->visibility === 'private' && ! $team->isActiveMember($request->user())) {
            abort(404);
        }

        $viewerMembership = $team->membershipFor($request->user());

        $members = $team->activeMembers
            ->sortBy(fn (TeamMember $member): int => match ($member->role) {
                'owner' => 0,
                'officer' => 1,
                default => 2,
            })
            ->values()
            ->map(fn (TeamMember $member): array => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'role' => $member->role,
                'status' => $member->status,
                'joined_at' => $member->joined_at?->toISOString(),
                'user' => $member->user ? (new UserResource($member->user))->resolve($request) : null,
            ]);

        $pendingMembers = collect();
        if ($team->canManage($request->user())) {
            $pendingMembers = $team->pendingMembers
                ->sortByDesc('created_at')
                ->values()
                ->map(fn (TeamMember $member): array => [
                    'id' => $member->id,
                    'role' => $member->role,
                    'status' => $member->status,
                    'message' => $member->message,
                    'created_at' => $member->created_at?->toISOString(),
                    'user' => $member->user ? (new UserResource($member->user))->resolve($request) : null,
                ]);
        }

        return [
            'data' => (new TeamResource($team))->resolve($request),
            'members' => $members,
            'pending_members' => $pendingMembers,
            'viewer' => [
                'membership' => $viewerMembership ? [
                    'id' => $viewerMembership->id,
                    'user_id' => $viewerMembership->user_id,
                    'role' => $viewerMembership->role,
                    'status' => $viewerMembership->status,
                    'joined_at' => $viewerMembership->joined_at?->toISOString(),
                ] : null,
                'is_member' => $team->isActiveMember($request->user()),
                'is_owner' => $team->isOwner($request->user()),
                'can_manage' => $team->canManage($request->user()),
                'can_leave' => $viewerMembership !== null
                    && $viewerMembership->status === 'active'
                    && $viewerMembership->role !== 'owner',
                'can_archive' => $team->isOwner($request->user()),
                'can_join' => $team->visibility === 'public'
                    && $team->recruitment_status === 'open'
                    && ($viewerMembership === null || $viewerMembership->status === 'declined'),
            ],
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'team';
        $slug = $base;
        $counter = 2;

        while (Team::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
