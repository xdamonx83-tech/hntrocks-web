<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TeamLfgPostResource;
use App\Http\Resources\Api\TeamResource;
use App\Models\Team;
use App\Models\TeamLfgApplication;
use App\Models\TeamLfgPost;
use App\Services\GamificationService;
use App\Services\MentionService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ApiTeamLfgController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $manageableTeamIds = $this->manageableTeamIds($request);

        $posts = TeamLfgPost::query()
            ->with([
                'user.profile',
                'team.owner.profile',
                'pendingApplications.user.profile',
                'pendingApplications.team.owner.profile',
                'applications' => fn ($query) => $query
                    ->where('user_id', $request->user()->id)
                    ->orWhereIn('team_id', $manageableTeamIds),
            ])
            ->where(function ($query) use ($request, $manageableTeamIds): void {
                $query->where('visibility', 'public')
                    ->orWhere('user_id', $request->user()->id)
                    ->orWhereIn('team_id', $manageableTeamIds);
            })
            ->whereIn('status', ['open', 'full', 'filled', 'closed'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('platform'), fn ($query) => $query->where('platform', $request->string('platform')))
            ->when($request->filled('playstyle'), fn ($query) => $query->where('playstyle', $request->string('playstyle')))
            ->when($request->filled('region'), fn ($query) => $query->where('region', $request->string('region')))
            ->latest()
            ->paginate(20);

        return TeamLfgPostResource::collection($posts);
    }

    public function show(Request $request, TeamLfgPost $post): JsonResponse
    {
        $manageableTeamIds = $this->manageableTeamIds($request);

        abort_unless(
            $post->visibility === 'public'
                || (int) $post->user_id === (int) $request->user()->id
                || in_array((int) $post->team_id, $manageableTeamIds, true),
            403
        );

        $post = $this->preparePostForResponse($request, $post);

        return response()->json([
            'team_lfg' => new TeamLfgPostResource($post),
            'data' => new TeamLfgPostResource($post),
        ]);
    }

    public function manageableTeams(Request $request): AnonymousResourceCollection
    {
        $teams = Team::query()
            ->with('owner.profile')
            ->withCount('activeMembers')
            ->whereHas('members', function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id)
                    ->where('status', 'active')
                    ->whereIn('role', ['owner', 'officer']);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return TeamResource::collection($teams);
    }

    public function store(
        Request $request,
        GamificationService $gamification,
        MentionService $mentions,
        NotificationService $notifications
    ): JsonResponse {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:team_seeks_players,player_seeks_team'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'title' => ['required', 'string', 'max:140'],
            'body' => ['nullable', 'string', 'max:3200'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'preferred_time' => ['nullable', 'string', 'max:80'],
            'experience_level' => ['nullable', 'string', 'max:60'],
            'voice_required' => ['nullable', 'boolean'],
            'slots_total' => ['nullable', 'integer', 'min:1', 'max:50'],
            'visibility' => ['required', 'string', 'in:public,private'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $user = $request->user();
        $team = null;

        if ($validated['type'] === 'team_seeks_players') {
            $request->validate([
                'team_id' => ['required', 'integer', 'exists:teams,id'],
                'slots_total' => ['required', 'integer', 'min:1', 'max:50'],
            ]);

            $team = Team::with('members')->findOrFail($validated['team_id']);
            abort_unless($team->canManage($user), 403);
        } else {
            $validated['team_id'] = null;
            $validated['slots_total'] = null;
        }

        if (blank($validated['title'] ?? null)) {
            $validated['title'] = Str::limit((string) ($validated['body'] ?? 'Team-LFG'), 120);
        }

        $post = TeamLfgPost::query()->create([
            ...$validated,
            'team_id' => $team?->id,
            'user_id' => $user->id,
            'voice_required' => $request->boolean('voice_required'),
            'slots_filled' => 0,
            'slots_total' => $validated['type'] === 'team_seeks_players' ? (int) ($validated['slots_total'] ?? 1) : null,
            'status' => 'open',
        ]);

        $gamification->award($user, 'team_lfg_post_created', source: $post);
        $mentions->syncForTeamLfgPost($post, $user, (string) ($post->body ?? ''), $notifications);

        $post = $this->preparePostForResponse($request, $post->fresh());

        return response()->json([
            'message' => 'Team-LFG post created.',
            'team_lfg' => new TeamLfgPostResource($post),
            'data' => new TeamLfgPostResource($post),
        ], 201);
    }

    public function apply(
        Request $request,
        TeamLfgPost $post,
        NotificationService $notifications,
        GamificationService $gamification
    ): JsonResponse {
        $post->loadMissing(['applications', 'team.members', 'team.owner.profile', 'user.profile']);
        $user = $request->user();

        if (! $post->isOpen()) {
            return response()->json(['message' => __('ui.team_lfg_error_not_open')], 422);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:900'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        if ($post->isTeamSeekingPlayers()) {
            if (! $post->canApplyAsUser($user)) {
                return response()->json(['message' => __('ui.team_lfg_error_cannot_apply')], 422);
            }

            $application = $post->applications()->create([
                'user_id' => $user->id,
                'message' => $validated['message'] ?? null,
                'status' => 'pending',
            ]);

            $gamification->award($user, 'team_lfg_application_sent', source: $application);
            $notifications->send(
                $post->user,
                $user,
                'team_lfg_application',
                __('ui.notification_team_lfg_application_title'),
                __('ui.notification_team_lfg_application_body', ['name' => $user->name]),
                route('team-lfg.show', $post) . '#team-lfg-applications'
            );

            $post = $this->preparePostForResponse($request, $post->fresh());

            return response()->json([
                'message' => __('ui.team_lfg_application_sent_status'),
                'application' => $this->applicationPayload($application),
                'team_lfg' => new TeamLfgPostResource($post),
                'data' => new TeamLfgPostResource($post),
            ], 201);
        }

        if ($post->isOwner($user)) {
            return response()->json(['message' => __('ui.team_lfg_error_own_invite')], 422);
        }

        $teamId = (int) ($validated['team_id'] ?? 0);
        if ($teamId <= 0) {
            return response()->json(['message' => 'Team auswählen.'], 422);
        }

        $team = Team::with('members')->findOrFail($teamId);
        abort_unless($team->canManage($user), 403);

        if ($team->isActiveMember($post->user)) {
            return response()->json(['message' => __('ui.team_lfg_error_already_member')], 422);
        }

        if ($post->hasApplicationFromTeam($team->id)) {
            return response()->json(['message' => __('ui.team_lfg_error_team_already_invited')], 422);
        }

        $application = $post->applications()->create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        $gamification->award($user, 'team_lfg_application_sent', source: $application);
        $notifications->send(
            $post->user,
            $user,
            'team_lfg_application',
            __('ui.notification_team_lfg_application_title'),
            __('ui.team_lfg_invitation_sent_status'),
            route('team-lfg.show', $post) . '#team-lfg-applications'
        );

        $post = $this->preparePostForResponse($request, $post->fresh());

        return response()->json([
            'message' => __('ui.team_lfg_invitation_sent_status'),
            'application' => $this->applicationPayload($application),
            'team_lfg' => new TeamLfgPostResource($post),
            'data' => new TeamLfgPostResource($post),
        ], 201);
    }

    public function acceptApplication(
        Request $request,
        TeamLfgPost $post,
        TeamLfgApplication $application,
        NotificationService $notifications,
        GamificationService $gamification
    ): JsonResponse {
        abort_unless((int) $application->team_lfg_post_id === (int) $post->id, 404);

        if ($application->status !== 'pending') {
            return response()->json(['message' => 'Diese Anfrage ist nicht mehr offen.'], 422);
        }

        $post->loadMissing(['team.members', 'team.owner.profile', 'user.profile']);
        $application->loadMissing(['user.profile', 'team.members', 'team.owner.profile']);
        $actor = $request->user();

        if ($post->isTeamSeekingPlayers()) {
            abort_unless($post->canManage($actor), 403);

            if (! $post->isOpen()) {
                return response()->json(['message' => __('ui.team_lfg_error_no_slots')], 422);
            }

            if (! $post->team) {
                return response()->json(['message' => __('ui.team_lfg_error_no_valid_team')], 422);
            }

            $post->team->members()->updateOrCreate(
                ['user_id' => $application->user_id],
                [
                    'role' => 'member',
                    'status' => 'active',
                    'message' => null,
                    'accepted_by' => $actor->id,
                    'joined_at' => now(),
                ]
            );

            $application->update([
                'status' => 'accepted',
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);

            $post->slots_filled = min((int) $post->slots_total, (int) $post->slots_filled + 1);
            if ($post->slots_filled >= (int) $post->slots_total) {
                $post->status = 'filled';
            }
            $post->save();

            $gamification->award($application->user, 'team_lfg_application_accepted', source: $application);
            $notifications->send(
                $application->user,
                $actor,
                'team_lfg_application_accepted',
                'Team-LFG-Bewerbung angenommen',
                'Deine Team-LFG-Bewerbung wurde angenommen.',
                route('team-lfg.show', $post)
            );
        } else {
            abort_unless($post->isOwner($actor), 403);

            if (! $application->team) {
                return response()->json(['message' => __('ui.team_lfg_error_invitation_no_valid_team')], 422);
            }

            $application->team->members()->updateOrCreate(
                ['user_id' => $post->user_id],
                [
                    'role' => 'member',
                    'status' => 'active',
                    'message' => null,
                    'accepted_by' => $application->user_id,
                    'joined_at' => now(),
                ]
            );

            $application->update([
                'status' => 'accepted',
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);

            $post->update(['status' => 'filled']);
            $gamification->award($post->user, 'team_lfg_application_accepted', source: $application);
            $notifications->send(
                $application->user,
                $actor,
                'team_lfg_invitation_accepted',
                'Team-LFG-Einladung angenommen',
                'Deine Team-LFG-Einladung wurde angenommen.',
                route('team-lfg.show', $post)
            );
        }

        $post = $this->preparePostForResponse($request, $post->fresh());

        return response()->json([
            'message' => 'Team-LFG-Anfrage wurde angenommen.',
            'team_lfg' => new TeamLfgPostResource($post),
            'data' => new TeamLfgPostResource($post),
        ]);
    }

    public function rejectApplication(
        Request $request,
        TeamLfgPost $post,
        TeamLfgApplication $application,
        NotificationService $notifications
    ): JsonResponse {
        abort_unless((int) $application->team_lfg_post_id === (int) $post->id, 404);

        if ($application->status !== 'pending') {
            return response()->json(['message' => 'Diese Anfrage ist nicht mehr offen.'], 422);
        }

        $post->loadMissing(['team.members', 'team.owner.profile', 'user.profile']);
        $application->loadMissing(['user.profile', 'team.owner.profile']);
        $actor = $request->user();

        if ($post->isTeamSeekingPlayers()) {
            abort_unless($post->canManage($actor), 403);
            $recipient = $application->user;
            $title = 'Team-LFG-Bewerbung abgelehnt';
            $body = 'Deine Team-LFG-Bewerbung wurde abgelehnt.';
        } else {
            abort_unless($post->isOwner($actor), 403);
            $recipient = $application->user;
            $title = 'Team-LFG-Einladung abgelehnt';
            $body = 'Deine Team-LFG-Einladung wurde abgelehnt.';
        }

        $application->update([
            'status' => 'rejected',
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ]);

        $notifications->send(
            $recipient,
            $actor,
            'team_lfg_application_rejected',
            $title,
            $body,
            route('team-lfg.show', $post)
        );

        $post = $this->preparePostForResponse($request, $post->fresh());

        return response()->json([
            'message' => 'Team-LFG-Anfrage wurde abgelehnt.',
            'team_lfg' => new TeamLfgPostResource($post),
            'data' => new TeamLfgPostResource($post),
        ]);
    }

    private function preparePostForResponse(Request $request, TeamLfgPost $post): TeamLfgPost
    {
        $manageableTeamIds = $this->manageableTeamIds($request);

        return $post->load([
            'user.profile',
            'team.owner.profile',
            'pendingApplications.user.profile',
            'pendingApplications.team.owner.profile',
            'applications' => fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->orWhereIn('team_id', $manageableTeamIds),
        ]);
    }

    private function manageableTeamIds(Request $request): array
    {
        return Team::query()
            ->whereHas('members', function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id)
                    ->where('status', 'active')
                    ->whereIn('role', ['owner', 'officer']);
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function applicationPayload($application): array
    {
        return [
            'id' => $application->id,
            'team_id' => $application->team_id,
            'status' => $application->status,
            'message' => $application->message,
            'created_at' => $application->created_at?->toISOString(),
        ];
    }
}
