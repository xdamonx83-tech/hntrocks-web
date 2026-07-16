<?php

namespace App\Services\Teams;

use App\Models\Team;
use App\Models\TeamSession;
use App\Models\TeamSessionResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamSessionService
{
    public function __construct(
        private readonly TeamProgressionService $progression,
        private readonly TeamParticipationService $participation,
        private readonly TeamContractService $contracts,
    ) {}

    public function create(Team $team, User $creator, array $data): TeamSession
    {
        return $team->sessions()->create([...$data, 'creator_id' => $creator->id, 'status' => TeamSession::STATUS_SCHEDULED]);
    }

    public function update(TeamSession $session, array $data): TeamSession
    {
        if (! in_array($session->status, [TeamSession::STATUS_SCHEDULED, TeamSession::STATUS_ACTIVE], true)) {
            throw ValidationException::withMessages(['session' => 'Diese Session kann nicht mehr bearbeitet werden.']);
        }

        $session->fill($data)->save();

        return $session;
    }

    public function respond(TeamSession $session, User $user, string $response): TeamSessionResponse
    {
        return DB::transaction(function () use ($session, $user, $response): TeamSessionResponse {
            $session = TeamSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if (! in_array($session->status, [TeamSession::STATUS_SCHEDULED, TeamSession::STATUS_ACTIVE], true)) {
                throw ValidationException::withMessages(['session' => 'Für diese Session sind keine Antworten mehr möglich.']);
            }

            if ($response === TeamSessionResponse::RESPONSE_GOING && $session->max_participants) {
                $going = $session->responses()->where('response', TeamSessionResponse::RESPONSE_GOING)->where('user_id', '!=', $user->id)->count();
                if ($going >= (int) $session->max_participants) {
                    throw ValidationException::withMessages(['response' => 'Die Session hat bereits die maximale Teilnehmerzahl erreicht.']);
                }
            }

            return $session->responses()->updateOrCreate(
                ['user_id' => $user->id],
                ['response' => $response, 'attendance_confirmed' => false, 'attendance_confirmed_by' => null, 'attendance_confirmed_at' => null]
            );
        });
    }

    public function cancel(TeamSession $session): TeamSession
    {
        return DB::transaction(function () use ($session): TeamSession {
            $session = TeamSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if (! in_array($session->status, [TeamSession::STATUS_SCHEDULED, TeamSession::STATUS_ACTIVE], true)) {
                throw ValidationException::withMessages(['session' => 'Diese Session kann nicht abgesagt werden.']);
            }

            $session->forceFill(['status' => TeamSession::STATUS_CANCELLED, 'cancelled_at' => now()])->save();

            return $session;
        });
    }

    public function complete(Team $team, TeamSession $session, User $actor, array $participantIds): TeamSession
    {
        return DB::transaction(function () use ($team, $session, $actor, $participantIds): TeamSession {
            Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();
            $session = TeamSession::query()->whereKey($session->id)->where('team_id', $team->id)->lockForUpdate()->firstOrFail();

            if (! in_array($session->status, [TeamSession::STATUS_SCHEDULED, TeamSession::STATUS_ACTIVE], true)) {
                throw ValidationException::withMessages(['session' => 'Diese Session ist bereits abgeschlossen oder abgesagt.']);
            }

            if ($session->starts_at->isFuture()) {
                throw ValidationException::withMessages(['session' => 'Eine Session kann nicht vor ihrer Startzeit abgeschlossen werden.']);
            }

            $participantIds = collect($participantIds)->map(fn ($id): int => (int) $id)->filter()->unique()->values();
            $activeIds = $team->activeMembers()->whereIn('user_id', $participantIds)->pluck('user_id');
            if ($activeIds->count() !== $participantIds->count()) {
                throw ValidationException::withMessages(['participant_ids' => 'Nur aktive Teammitglieder können als teilnehmend bestätigt werden.']);
            }

            $minimum = max(1, (int) config('team_progression.sessions.minimum_confirmed_participants', 2));
            if ($activeIds->count() < $minimum) {
                throw ValidationException::withMessages(['participant_ids' => "Mindestens {$minimum} bestätigte Teilnehmer sind erforderlich."]);
            }

            $session->responses()->update([
                'attendance_confirmed' => false,
                'attendance_confirmed_by' => null,
                'attendance_confirmed_at' => null,
            ]);

            foreach ($activeIds as $userId) {
                $session->responses()->updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'response' => TeamSessionResponse::RESPONSE_GOING,
                        'attendance_confirmed' => true,
                        'attendance_confirmed_by' => $actor->id,
                        'attendance_confirmed_at' => now(),
                    ]
                );
            }

            $session->forceFill(['status' => TeamSession::STATUS_COMPLETED, 'completed_at' => now()])->save();

            $this->progression->award(
                $team,
                (int) config('team_progression.sessions.team_xp_reward', 100),
                'team_session_completed',
                "team-session:{$session->id}",
                $actor,
                ['team_session_id' => $session->id, 'participants' => $activeIds->count()]
            );

            foreach (User::query()->whereIn('id', $activeIds)->get() as $participant) {
                $this->participation->award(
                    $team,
                    $participant,
                    'team_session_attended',
                    "team-session-attendance:{$session->id}:{$participant->id}",
                    metadata: ['team_session_id' => $session->id]
                );
            }

            $creator = User::query()->find($session->creator_id);
            if ($creator) {
                $this->participation->award(
                    $team,
                    $creator,
                    'team_session_organized',
                    "team-session-organized:{$session->id}",
                    metadata: ['team_session_id' => $session->id]
                );
                $contractMetadata = ['confirmed_participants' => $activeIds->count(), 'qualified_user_ids' => $activeIds->all()];
                $this->contracts->recordActivity($team, 'team_session_completed', $creator, "team-session-contract:{$session->id}", metadata: $contractMetadata);

                $activeMemberCount = max(1, $team->activeMembers()->count());
                $requiredForFullRoster = (int) ceil($activeMemberCount * ((int) config('team_progression.sessions.full_roster_percent', 75) / 100));
                if ($activeIds->count() >= $requiredForFullRoster) {
                    $this->contracts->recordActivity($team, 'team_session_full_roster', $creator, "team-session-full-roster:{$session->id}", metadata: [...$contractMetadata, 'active_members' => $activeMemberCount]);
                }
            }

            return $session->fresh(['responses.user']);
        });
    }
}
