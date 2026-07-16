<?php

namespace Tests\Feature;

use App\Models\TeamSession;
use App\Models\TeamSessionResponse;
use App\Services\Teams\TeamProgressionService;
use App\Services\Teams\TeamSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\CreatesTeamDomain;
use Tests\TestCase;

class TeamSessionTest extends TestCase
{
    use CreatesTeamDomain;
    use RefreshDatabase;

    public function test_owner_and_officer_can_create_but_member_cannot(): void
    {
        $officer = $this->user('officer');
        $member = $this->user('member');
        $team = $this->team(null, [[$officer, 'officer'], [$member]]);

        $this->assertTrue(Gate::forUser($team->owner)->allows('createTeamSession', $team));
        $this->assertTrue(Gate::forUser($officer)->allows('createTeamSession', $team));
        $this->assertFalse(Gate::forUser($member)->allows('createTeamSession', $team));
    }

    public function test_active_member_can_respond_but_pending_and_stranger_cannot(): void
    {
        $member = $this->user('member');
        $pending = $this->user('pending');
        $stranger = $this->user('stranger');
        $team = $this->team(null, [[$member], [$pending, 'member', 'pending']]);
        $session = app(TeamSessionService::class)->create($team, $team->owner, $this->sessionData());

        app(TeamSessionService::class)->respond($session, $member, TeamSessionResponse::RESPONSE_GOING);
        $this->assertDatabaseHas('team_session_responses', ['team_session_id' => $session->id, 'user_id' => $member->id, 'response' => 'going']);
        $this->assertTrue(Gate::forUser($member)->allows('respondToTeamSession', $team));
        $this->assertFalse(Gate::forUser($pending)->allows('respondToTeamSession', $team));
        $this->assertFalse(Gate::forUser($stranger)->allows('respondToTeamSession', $team));
    }

    public function test_session_cannot_be_completed_early_or_after_cancellation(): void
    {
        $member = $this->user('member');
        $team = $this->team(null, [[$member]]);
        $service = app(TeamSessionService::class);
        $session = $service->create($team, $team->owner, $this->sessionData(['starts_at' => now()->addHour()]));

        try {
            $service->complete($team, $session, $team->owner, [$team->owner_id, $member->id]);
            $this->fail('Early completion should fail.');
        } catch (ValidationException) {
            $this->assertSame(TeamSession::STATUS_SCHEDULED, $session->fresh()->status);
        }

        $service->cancel($session);
        $this->expectException(ValidationException::class);
        $service->complete($team, $session, $team->owner, [$team->owner_id, $member->id]);
    }

    public function test_completion_confirms_actual_attendance_and_awards_only_once(): void
    {
        $member = $this->user('member');
        $team = $this->team(null, [[$member]]);
        $service = app(TeamSessionService::class);
        $session = $service->create($team, $team->owner, $this->sessionData(['starts_at' => now()->subHour()]));

        $service->complete($team, $session, $team->owner, [$team->owner_id, $member->id]);
        $this->assertSame(TeamSession::STATUS_COMPLETED, $session->fresh()->status);
        $this->assertDatabaseCount('team_session_responses', 2);
        $this->assertDatabaseHas('team_session_responses', ['team_session_id' => $session->id, 'user_id' => $member->id, 'attendance_confirmed' => true]);
        $this->assertSame(100, app(TeamProgressionService::class)->summary($team)['xp_total']);
        $this->assertDatabaseHas('team_participations', ['team_id' => $team->id, 'user_id' => $member->id, 'points_total' => 50]);
        $this->assertDatabaseHas('team_participations', ['team_id' => $team->id, 'user_id' => $team->owner_id, 'points_total' => 80]);

        try {
            $service->complete($team, $session, $team->owner, [$team->owner_id, $member->id]);
        } catch (ValidationException) {
            // Expected; the assertions below prove no second award was written.
        }

        $this->assertDatabaseCount('team_xp_events', 1);
        $this->assertDatabaseCount('team_participation_events', 3);
    }

    private function sessionData(array $overrides = []): array
    {
        return [
            'title' => 'Bounty Hunt Abend',
            'description' => null,
            'starts_at' => $overrides['starts_at'] ?? now()->addHour(),
            'ends_at' => null,
            'timezone' => 'UTC',
            'platform' => 'PC',
            'region' => 'EU',
            'game_mode' => 'Bounty Hunt',
            'max_participants' => 6,
            'voice_required' => true,
        ];
    }
}
