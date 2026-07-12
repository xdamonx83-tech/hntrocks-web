<?php

namespace Tests\Feature\Members;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembersRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_redesign_uses_the_uploaded_table_layout_with_real_data(): void
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
            ->assertSee('members-overview', false)
            ->assertSee('members-progress-row', false)
            ->assertSee('members-directory-card', false)
            ->assertSee('members-table-head', false)
            ->assertSee('members-table-row', false)
            ->assertSee('Bayou Hunter')
            ->assertSee('bayou-hunter')
            ->assertSee('Xbox')
            ->assertDontSee('members-live-card', false)
            ->assertSee('dashboard-members/members-live.css', false)
            ->assertSee('dashboard-members/members-live.js', false);
    }

    public function test_members_fragment_uses_the_live_table_row_partial(): void
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

    public function test_members_csv_export_uses_the_current_real_member_query(): void
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

    public function test_members_css_uses_full_page_scroll_instead_of_inline_table_scroll(): void
    {
        $css = file_get_contents(public_path('assets/themes/hnt_preview/dashboard-members/members-prototype.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('body[data-page="members"]', $css);
        $this->assertStringContainsString('overflow-y:auto!important', $css);
        $this->assertStringContainsString('.members-table-body{overflow:visible!important', $css);
    }
}
