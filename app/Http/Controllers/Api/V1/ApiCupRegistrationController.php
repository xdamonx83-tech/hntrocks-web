<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CupLeaderboardEntryResource;
use App\Models\Cup;
use App\Models\CupTeam;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiCupRegistrationController extends Controller
{
    public function __invoke(Request $request, Cup $cup, GamificationService $gamification, NotificationService $notifications): JsonResponse
    {
        abort_unless($cup->visibility === 'public' || $cup->canManage($request->user()), 404);

        $cup->loadMissing(['teams.members', 'owner.profile']);
        $existingTeam = $cup->teamFor($request->user());

        if ($existingTeam) {
            $existingTeam->loadMissing('owner.profile');

            return response()->json([
                'message' => $cup->isSoloLeaderboard()
                    ? __('ui.cup_team_error_already_registered')
                    : __('ui.cup_team_error_already_in_team'),
                'registered' => true,
                'team' => new CupLeaderboardEntryResource($existingTeam),
            ]);
        }

        if (! $cup->isRegistrationOpen()) {
            return response()->json([
                'message' => __('ui.cup_team_error_registration_closed'),
                'errors' => [
                    'cup' => [__('ui.cup_team_error_registration_closed')],
                ],
            ], 422);
        }

        if ($cup->isSoloLeaderboard()) {
            $request->validate([
                'name' => ['nullable', 'string', 'max:100'],
            ]);

            $displayName = $this->uniqueSoloParticipantName(
                $cup,
                $request->user()->username ?: $request->user()->name ?: __('ui.cup_player_fallback', ['id' => $request->user()->id]),
                (int) $request->user()->id
            );
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:100'],
            ]);

            $displayName = trim((string) $validated['name']);

            if ($this->teamNameExists($cup, $displayName)) {
                return $this->teamNameTakenResponse();
            }
        }

        try {
            $team = DB::transaction(function () use ($cup, $request, $displayName): CupTeam {
                $team = CupTeam::create([
                    'cup_id' => $cup->id,
                    'owner_id' => $request->user()->id,
                    'name' => $displayName,
                    'status' => 'active',
                ]);

                $team->members()->create([
                    'user_id' => $request->user()->id,
                    'role' => $cup->isSoloLeaderboard() ? 'participant' : 'captain',
                    'status' => 'active',
                    'joined_at' => now(),
                ]);

                return $team;
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateTeamNameException($exception)) {
                return $this->teamNameTakenResponse();
            }

            throw $exception;
        }

        $team->loadMissing('owner.profile');
        $gamification->award($request->user(), 'cup_team_created', source: $team, description: $cup->isSoloLeaderboard() ? __('ui.cup_participant_gamification_created') : __('ui.cup_team_gamification_created'));

        if ($cup->isSoloLeaderboard()) {
            $notifications->send($cup->owner, $request->user(), 'cup_participant_registered', __('ui.cup_participant_notification_created_title'), __('ui.cup_participant_notification_created_body', ['player' => $team->displayName(), 'cup' => $cup->title]), route('cups.show', $cup));
        } else {
            $notifications->send($cup->owner, $request->user(), 'cup_team_created', __('ui.cup_team_notification_created_title'), __('ui.cup_team_notification_created_body', ['team' => $team->name, 'cup' => $cup->title]), route('cups.show', $cup));
        }

        return response()->json([
            'message' => $cup->isSoloLeaderboard() ? __('ui.cup_participant_created_status') : __('ui.cup_team_created_status'),
            'registered' => true,
            'team' => new CupLeaderboardEntryResource($team),
        ], 201);
    }

    private function uniqueSoloParticipantName(Cup $cup, string $baseName, int $userId): string
    {
        $name = trim($baseName) ?: __('ui.cup_player_fallback', ['id' => $userId]);
        $candidate = $name;
        $counter = 2;

        while (CupTeam::withTrashed()->where('cup_id', $cup->id)->where('name', $candidate)->exists()) {
            $candidate = $name.' #'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function teamNameExists(Cup $cup, string $name): bool
    {
        return CupTeam::withTrashed()
            ->where('cup_id', $cup->id)
            ->where('name', $name)
            ->exists();
    }

    private function isDuplicateTeamNameException(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000'
            && str_contains($exception->getMessage(), 'cup_teams_cup_id_name_unique');
    }

    private function teamNameTakenResponse(): JsonResponse
    {
        $message = 'Dieser Teamname ist für diesen Cup bereits vergeben.';

        return response()->json([
            'message' => $message,
            'errors' => [
                'name' => [$message],
            ],
        ], 422);
    }
}
