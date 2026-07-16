<?php

namespace Tests\Feature;

use App\Models\TeamContract;
use App\Services\Teams\TeamContractService;
use App\Services\Teams\TeamProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\CreatesTeamDomain;
use Tests\TestCase;

class TeamContractTest extends TestCase
{
    use CreatesTeamDomain;
    use RefreshDatabase;

    public function test_owner_and_officer_can_activate_but_member_pending_and_stranger_cannot(): void
    {
        $officer = $this->user('officer');
        $member = $this->user('member');
        $pending = $this->user('pending');
        $stranger = $this->user('stranger');
        $team = $this->team(null, [[$officer, 'officer'], [$member], [$pending, 'member', 'pending']]);

        $this->assertTrue(Gate::forUser($team->owner)->allows('manageTeamContracts', $team));
        $this->assertTrue(Gate::forUser($officer)->allows('manageTeamContracts', $team));
        $this->assertFalse(Gate::forUser($member)->allows('manageTeamContracts', $team));
        $this->assertFalse(Gate::forUser($pending)->allows('manageTeamContracts', $team));
        $this->assertFalse(Gate::forUser($stranger)->allows('manageTeamContracts', $team));
    }

    public function test_only_one_contract_can_be_active(): void
    {
        $team = $this->team();
        $service = app(TeamContractService::class);
        $service->activate($team, $this->template(), $team->owner);

        $this->expectException(ValidationException::class);
        $service->activate($team, $this->template(), $team->owner);
    }

    public function test_real_progress_requires_distinct_contributors_and_completion_rewards_once(): void
    {
        $first = $this->user('first');
        $second = $this->user('second');
        $idle = $this->user('idle');
        $team = $this->team(null, [[$first], [$second], [$idle]]);
        $service = app(TeamContractService::class);
        $contract = $service->activate($team, $this->template(['target_value' => 2, 'minimum_contributors' => 2]), $team->owner);

        $service->recordActivity($team, 'team_post_created', $first, 'post-1');
        $service->recordActivity($team, 'team_post_created', $first, 'post-1');
        $this->assertSame(TeamContract::STATUS_ACTIVE, $contract->fresh()->status);
        $service->recordActivity($team, 'team_post_created', $second, 'post-2');
        $service->recordActivity($team, 'team_post_created', $second, 'post-2');

        $this->assertSame(TeamContract::STATUS_COMPLETED, $contract->fresh()->status);
        $this->assertSame(250, app(TeamProgressionService::class)->summary($team)['xp_total']);
        $this->assertDatabaseCount('team_reward_grants', 2);
        $this->assertDatabaseHas('crown_wallets', ['user_id' => $first->id, 'balance' => 20]);
        $this->assertDatabaseHas('crown_wallets', ['user_id' => $second->id, 'balance' => 20]);
        $this->assertDatabaseMissing('crown_wallets', ['user_id' => $idle->id]);
        $this->assertDatabaseCount('team_xp_events', 1);
    }

    public function test_cancel_and_cooldown_rules_are_enforced(): void
    {
        $team = $this->team();
        $service = app(TeamContractService::class);
        $template = $this->template(['target_value' => 1, 'cooldown_days' => 7]);
        $contract = $service->activate($team, $template, $team->owner);
        $service->cancel($team, $contract);
        $this->assertSame(TeamContract::STATUS_CANCELLED, $contract->fresh()->status);

        $completed = $service->activate($team, $template, $team->owner);
        $service->recordActivity($team, 'team_post_created', $team->owner, 'finish');
        $this->expectException(ValidationException::class);
        $service->activate($team, $template, $team->owner);
    }
}
