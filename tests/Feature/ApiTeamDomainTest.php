<?php

namespace Tests\Feature;

use App\Models\TeamSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTeamDomain;
use Tests\TestCase;

class ApiTeamDomainTest extends TestCase
{
    use CreatesTeamDomain;
    use RefreshDatabase;

    public function test_authentication_is_required(): void
    {
        $team = $this->team();
        $this->getJson("/api/v1/teams/{$team->slug}/progress")->assertUnauthorized();
    }

    public function test_public_progress_exposes_summary_but_not_internal_data_to_strangers(): void
    {
        $stranger = $this->user('stranger');
        $team = $this->team();

        $this->withToken($this->token($stranger))->getJson("/api/v1/teams/{$team->slug}/progress")
            ->assertOk()
            ->assertJsonPath('data.level', 1)
            ->assertJsonPath('data.xp_total', 0)
            ->assertJsonPath('data.viewer_permissions.view_contracts', false);

        $this->withToken($this->token($stranger))->getJson("/api/v1/teams/{$team->slug}/contracts")->assertForbidden();
        $this->withToken($this->token($stranger))->getJson("/api/v1/teams/{$team->slug}/sessions")->assertForbidden();
        $this->withToken($this->token($stranger))->getJson("/api/v1/teams/{$team->slug}/participation")->assertForbidden();
    }

    public function test_member_permissions_and_contract_response_shape_are_consistent(): void
    {
        $member = $this->user('member');
        $team = $this->team(null, [[$member]]);

        $this->withToken($this->token($member))->getJson("/api/v1/teams/{$team->slug}/contracts")
            ->assertOk()
            ->assertJsonStructure(['data' => ['active_contract', 'available_templates', 'viewer_permissions']])
            ->assertJsonPath('data.viewer_permissions.view_contracts', true)
            ->assertJsonPath('data.viewer_permissions.manage_contracts', false);
    }

    public function test_nested_session_from_another_team_is_not_reachable(): void
    {
        $owner = $this->user('owner');
        $first = $this->team($owner);
        $second = $this->team($owner);
        $session = TeamSession::query()->create([
            'team_id' => $second->id,
            'creator_id' => $owner->id,
            'title' => 'Other team',
            'starts_at' => now()->addHour(),
            'timezone' => 'UTC',
            'status' => TeamSession::STATUS_SCHEDULED,
        ]);

        $this->withToken($this->token($owner))->getJson("/api/v1/teams/{$first->slug}/sessions/{$session->id}")->assertNotFound();
        $this->withToken($this->token($owner))->postJson("/api/v1/teams/{$first->slug}/sessions/{$session->id}/cancel")->assertNotFound();
    }

    public function test_pending_member_cannot_see_or_respond_to_sessions(): void
    {
        $pending = $this->user('pending');
        $team = $this->team(null, [[$pending, 'member', 'pending']]);
        $session = TeamSession::query()->create([
            'team_id' => $team->id,
            'creator_id' => $team->owner_id,
            'title' => 'Internal session',
            'starts_at' => now()->addHour(),
            'timezone' => 'UTC',
            'status' => TeamSession::STATUS_SCHEDULED,
        ]);

        $token = $this->token($pending);
        $this->withToken($token)->getJson("/api/v1/teams/{$team->slug}/sessions")->assertForbidden();
        $this->withToken($token)->postJson("/api/v1/teams/{$team->slug}/sessions/{$session->id}/respond", ['response' => 'going'])->assertForbidden();
    }
}
