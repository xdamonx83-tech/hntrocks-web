<?php

namespace Tests\Feature;

use App\Services\Teams\TeamProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTeamDomain;
use Tests\TestCase;

class TeamProgressTest extends TestCase
{
    use CreatesTeamDomain;
    use RefreshDatabase;

    public function test_new_team_starts_at_level_one_with_zero_xp(): void
    {
        $summary = app(TeamProgressionService::class)->summary($this->team());

        $this->assertSame(1, $summary['level']);
        $this->assertSame(0, $summary['xp_total']);
        $this->assertSame(500, $summary['xp_required_for_next_level']);
    }

    public function test_xp_is_added_and_can_cross_multiple_levels(): void
    {
        $team = $this->team();
        $service = app(TeamProgressionService::class);

        $service->award($team, 2000, 'test', 'multi-level');
        $summary = $service->summary($team);

        $this->assertSame(3, $summary['level']);
        $this->assertSame(2000, $summary['xp_total']);
        $this->assertSame(750, $summary['xp_current_level']);
    }

    public function test_same_xp_source_is_only_counted_once(): void
    {
        $team = $this->team();
        $service = app(TeamProgressionService::class);

        $service->award($team, 100, 'test', 'same-source');
        $service->award($team, 100, 'test', 'same-source');

        $this->assertSame(100, $service->summary($team)['xp_total']);
        $this->assertDatabaseCount('team_xp_events', 1);
    }
}
