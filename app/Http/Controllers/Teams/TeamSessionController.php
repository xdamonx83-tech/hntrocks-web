<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\CompleteTeamSessionRequest;
use App\Http\Requests\Teams\RespondTeamSessionRequest;
use App\Http\Requests\Teams\StoreTeamSessionRequest;
use App\Http\Requests\Teams\UpdateTeamSessionRequest;
use App\Http\Resources\Api\TeamSessionResource;
use App\Models\Team;
use App\Models\TeamSession;
use App\Services\Teams\TeamSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamSessionController extends Controller
{
    public function store(StoreTeamSessionRequest $request, Team $team, TeamSessionService $sessions): JsonResponse|RedirectResponse
    {
        Gate::authorize('createTeamSession', $team);
        $session = $sessions->create($team, $request->user(), $request->validated())->load(['creator', 'responses.user']);

        return $this->respond($request, 'Team-Session wurde erstellt.', $session, 201);
    }

    public function update(UpdateTeamSessionRequest $request, Team $team, TeamSession $teamSession, TeamSessionService $sessions): JsonResponse|RedirectResponse
    {
        Gate::authorize('updateTeamSession', $team);
        $this->assertScoped($team, $teamSession);

        return $this->respond($request, 'Team-Session wurde aktualisiert.', $sessions->update($teamSession, $request->validated())->load(['creator', 'responses.user']));
    }

    public function respondToSession(RespondTeamSessionRequest $request, Team $team, TeamSession $teamSession, TeamSessionService $sessions): JsonResponse|RedirectResponse
    {
        Gate::authorize('respondToTeamSession', $team);
        $this->assertScoped($team, $teamSession);
        $sessions->respond($teamSession, $request->user(), $request->validated('response'));

        return $this->respond($request, 'Session-Antwort wurde gespeichert.', $teamSession->fresh()->load(['creator', 'responses.user']));
    }

    public function cancel(Request $request, Team $team, TeamSession $teamSession, TeamSessionService $sessions): JsonResponse|RedirectResponse
    {
        Gate::authorize('cancelTeamSession', $team);
        $this->assertScoped($team, $teamSession);

        return $this->respond($request, 'Team-Session wurde abgesagt.', $sessions->cancel($teamSession)->load(['creator', 'responses.user']));
    }

    public function complete(CompleteTeamSessionRequest $request, Team $team, TeamSession $teamSession, TeamSessionService $sessions): JsonResponse|RedirectResponse
    {
        Gate::authorize('completeTeamSession', $team);
        $this->assertScoped($team, $teamSession);
        $session = $sessions->complete($team, $teamSession, $request->user(), $request->validated('participant_ids'))->loadMissing(['creator', 'responses.user']);

        return $this->respond($request, 'Team-Session wurde abgeschlossen.', $session);
    }

    private function respond(Request $request, string $message, TeamSession $session, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => ['session' => new TeamSessionResource($session)]], $status);
        }

        return back()->with('status', $message);
    }

    private function assertScoped(Team $team, TeamSession $session): void
    {
        abort_unless((int) $session->team_id === (int) $team->id, 404);
    }
}
