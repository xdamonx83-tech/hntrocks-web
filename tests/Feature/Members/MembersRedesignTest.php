<?php

namespace Tests\Feature\Members;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembersRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_redesign_uses_shared_dashboard_shell_and_real_member_data(): void
    {
        config()->set('members.redesign_live', true);

        $viewer = User::factory()->create();
        $viewer->profile()->create([
            'profile_visibility' => 'registered',
        ]);

        $member = User::factory()->create([
            'name' => 'Bayou Hunter',
            'username' => 'bayou-hunter',
        ]);
        $member->profile()->create([
            'profile_visibility' => 'registered',
            'platform' => 'Xbox',
            'region' => 'EU',
            'language' => 'Deutsch',
            'playstyle' => 'Casual',
            'is_lfg_available' => true,
        ]);

        $this->actingAs($viewer)
            ->get(route('members.index'))
            ->assertOk()
            ->assertSee('data-hnt-shared-header', false)
            ->assertSee('members-page-content', false)
            ->assertSee('data-members-stream', false)
            ->assertSee('Bayou Hunter')
            ->assertSee('bayou-hunter')
            ->assertSee('dashboard-members/members-live.css', false)
            ->assertSee('dashboard-members/members-live.js', false);
    }

    public function test_members_fragment_uses_the_live_member_card_partial(): void
    {
        config()->set('members.redesign_live', true);

        $viewer = User::factory()->create();
        $viewer->profile()->create([
            'profile_visibility' => 'registered',
        ]);

        $member = User::factory()->create([
            'name' => 'Fragment Hunter',
            'username' => 'fragment-hunter',
        ]);
        $member->profile()->create([
            'profile_visibility' => 'registered',
        ]);

        $this->actingAs($viewer)
            ->getJson(route('members.index', ['fragment' => 1]))
            ->assertOk()
            ->assertJsonPath('hasMorePages', false)
            ->assertJsonPath('nextPageUrl', null)
            ->assertJsonFragment([
                'hasMorePages' => false,
            ])
            ->assertJson(fn ($json) => $json
                ->whereType('html', 'string')
                ->etc());
    }
}
