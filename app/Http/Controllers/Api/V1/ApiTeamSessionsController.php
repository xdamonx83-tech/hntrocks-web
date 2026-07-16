<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\CompleteTeamSessionRequest;
use App\Http\Requests\Teams\RespondTeamSessionRequest;
use App\Http\Requests\Teams\StoreTeamSessionRequest;
use App\Http\Requests\Teams\UpdateTeamSessionRequest;
use App\Http\Resources\Api\TeamSessionResource;
use App\Models\Team;
use App\Models\TeamSession;
use App\Services\Teams\TeamPermissionService;
use App\Services\Teams\TeamSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiTeamSessionsController extends Controller
{
    public function index(Request $request, Team $team, TeamPermissionService $permissions): JsonResponse
    {
        Gate::authorize('viewTeamSessions', $team);
        $sessions = $team->sessions()->with(['creator', 'responses.user'])->orderByDesc('starts_at')->paginate(25);

        return response()->json(['data' => [
            'sessions' => TeamSessionResource::collection($sessions->getCollection())->resolve($request),
            'pagination' => ['current_page' => $sessions->currentPage(), 'last_page' => $sessions->lastPage(), 'per_page' => $sessions->perPage(), 'total' => $sessions->total()],
            'viewer_permissions' => $permissions->for($request->user(), $team),
        ]]);
    }

    public function store(StoreTeamSessionRequest $request, Team $team, TeamSessionService $sessions, TeamPermissionService $permissions): JsonResponse
    {
        Gate::authorize('createTeamSession', $team);
        $session = $sessions->create($team, $request->user(), $request->validated())->load(['creator', 'responses.user']);

        return response()->json(['message' => 'Team-Session wurde erstellt.', 'data' => [
            'session' => new TeamSessionResource($session),
            'viewer_permissions' => $permissions->for($request->user(), $team),
        ]], 201);
    }

    public function show(Request $request, Team $team, TeamSession $teamSession, TeamPermissionService $permissions): JsonResponse
    {
        Gate::authorize('viewTeamSessions', $team);
        $this->assertScoped($team, $teamSession);

        return response()->json(['data' => [
            'session' => new TeamSessionResource($teamSession->load(['creator', 'responses.user'])),
            'viewer_permissions' => $permissions->for($request->user(), $team),
        ]]);
    }

    public function update(UpdateTeamSessionRequest $request, Team $team, TeamSession $teamSession, TeamSessionService $sessions): JsonResponse
    {
        Gate::authorize('updateTeamSession', $team);
        $this->assertScoped($team, $teamSession);

        return response()->json(['message' => 'Team-Session wurde aktualisiert.', 'data' => [
            'session' => new TeamSessionResource($sessions->update($teamSession, $request->validated())->load(['creator', 'responses.user'])),
        ]]);
    }

    public function respond(RespondTeamSessionRequest $request, Team $team, TeamSession $teamSession, TeamSessionService $sessions): JsonResponse
    {
        Gate::authorize('respondToTeamSession', $team);
        $this->assertScoped($team, $teamSession);
        $sessions->respond($teamSession, $request->user(), $request->validated('response'));

        return response()->json(['message' => 'Session-Antwort wurde gespeichert.', 'data' => [
            'session' => new TeamSessionResource($teamSession->fresh()->load(['creator', 'responses.user'])),
        ]]);
    }

    public function cancel(Request $request, Team $team, TeamSession $teamSession, TeamSessionService $sessions): JsonResponse
    {
        Gate::authorize('cancelTeamSession', $team);
        $this->assertScoped($team, $teamSession);

        return response()->json(['message' => 'Team-Session wurde abgesagt.', 'data' => [
            'session' => new TeamSessionResource($sessions->cancel($teamSession)->load(['creator', 'responses.user'])),
        ]]);
    }

    public function complete(CompleteTeamSessionRequest $request, Team $team, TeamSession $teamSession, TeamSessionService $sessions): JsonResponse
    {
        Gate::authorize('completeTeamSession', $team);
        $this->assertScoped($team, $teamSession);
        $session = $sessions->complete($team, $teamSession, $request->user(), $request->validated('participant_ids'));

        return response()->json(['message' => 'Team-Session wurde abgeschlossen.', 'data' => ['session' => new TeamSessionResource($session->loadMissing(['creator', 'responses.user']))]]);
    }

    private function assertScoped(Team $team, TeamSession $session): void
    {
        abort_unless((int) $session->team_id === (int) $team->id, 404);
    }
}
