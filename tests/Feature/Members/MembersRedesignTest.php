<?php

namespace Tests\Feature\Members;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembersRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_redesign_renders_the_uploaded_static_demo_exactly_before_live_data_is_reconnected(): void
    {
        config()->set('members.redesign_live', true);

        $viewer = User::factory()->create();
        $viewer->profile()->create([
            'profile_visibility' => 'registered',
        ]);

        $this->actingAs($viewer)
            ->get(route('members.index'))
            ->assertOk()
            ->assertSee('data-hnt-shared-header', false)
            ->assertSee('members-stage', false)
            ->assertSee('members-scroll', false)
            ->assertSee('members-overview', false)
            ->assertSee('members-progress-row', false)
            ->assertSee('members-directory-card', false)
            ->assertSee('members-filter-strip', false)
            ->assertSee('members-table-head', false)
            ->assertSee('members-table-row', false)
            ->assertSee('Valentina')
            ->assertSee('Katy Fuller')
            ->assertSee('Jonathan Kelly')
            ->assertSee('1.284')
            ->assertSee('128')
            ->assertSee('24')
            ->assertDontSee('members-directory-sticky', false)
            ->assertDontSee('members-live-card', false)
            ->assertSee('dashboard-members/members-live.css', false)
            ->assertSee('dashboard-members/members-live.js', false);
    }

    public function test_members_fragment_still_keeps_the_existing_real_data_endpoint_available_for_the_next_stage(): void
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

        $response = $this->actingAs($viewer)
            ->getJson(route('members.index', ['fragment' => 1]))
            ->assertOk()
            ->assertJsonPath('hasMorePages', false)
            ->assertJsonPath('nextPageUrl', null);

        $this->assertStringContainsString('members-table-row', $response->json('html'));
        $this->assertStringContainsString('Fragment Hunter', $response->json('html'));
    }

    public function test_members_csv_export_keeps_the_existing_real_member_query_available(): void
    {
        config()->set('members.redesign_live', true);

        $viewer = User::factory()->create();
        $viewer->profile()->create([
            'profile_visibility' => 'registered',
        ]);

        $member = User::factory()->create([
            'name' => 'CSV Hunter',
            'username' => 'csv-hunter',
        ]);
        $member->profile()->create([
            'profile_visibility' => 'registered',
            'platform' => 'PC',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('members.index', ['export' => 'csv']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        $this->assertStringContainsString('CSV Hunter', $csv);
        $this->assertStringContainsString('csv-hunter', $csv);
        $this->assertStringContainsString('PC', $csv);
    }

    public function test_members_uses_the_feed_scroll_shell_without_sticky_layout_hacks(): void
    {
        $liveCss = file_get_contents(public_path('assets/themes/hnt_preview/dashboard-members/members-live.css'));
        $javascript = file_get_contents(public_path('assets/themes/hnt_preview/dashboard-members/members-live.js'));

        $this->assertIsString($liveCss);
        $this->assertIsString($javascript);
        $this->assertStringContainsString('grid-template-rows: auto minmax(0, 1fr)', $liveCss);
        $this->assertStringContainsString('.members-stage', $liveCss);
        $this->assertStringContainsString('.members-scroll', $liveCss);
        $this->assertStringContainsString('scrollbar-width: none', $liveCss);
        $this->assertStringContainsString('.members-scroll::-webkit-scrollbar', $liveCss);
        $this->assertStringNotContainsString('.members-directory-sticky', $liveCss);
        $this->assertStringNotContainsString("stage.className = 'members-stage'", $javascript);
        $this->assertStringContainsString('applyMembersFilters', $javascript);
    }
}
