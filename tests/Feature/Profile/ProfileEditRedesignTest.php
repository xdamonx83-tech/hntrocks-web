<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileEditRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_edit_redesign_stays_off_when_kill_switch_is_disabled(): void
    {
        config()->set('hunthub.theme.profile_edit_redesign_live', false);
        config()->set('hunthub.theme.enabled', false);
        config()->set('hunthub.theme.preview_live', false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('data-profile-edit-live', false);
    }

    public function test_profile_edit_redesign_renders_real_user_data_without_demo_content(): void
    {
        config()->set('hunthub.theme.profile_edit_redesign_live', true);

        $user = User::factory()->create([
            'name' => 'Christian Hunter',
            'username' => 'christian-hunter',
        ]);
        $user->profile()->create([
            'headline' => 'Bayou veteran',
            'bio' => 'Real profile biography.',
            'platform' => 'PC',
            'playstyle' => 'Tactical',
            'profile_visibility' => 'public',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('data-profile-edit-live', false)
            ->assertSee('Christian Hunter')
            ->assertSee('Real profile biography.')
            ->assertDontSee('Valentina')
            ->assertDontSee('Katy Fuller');
    }

    public function test_classic_profile_edit_query_bypasses_the_redesign(): void
    {
        config()->set('hunthub.theme.profile_edit_redesign_live', true);
        config()->set('hunthub.theme.enabled', false);
        config()->set('hunthub.theme.preview_live', false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit', ['classic_profile_edit' => 1]))
            ->assertOk()
            ->assertDontSee('data-profile-edit-live', false);
    }

    public function test_profile_edit_header_endpoint_returns_real_viewer_payload(): void
    {
        config()->set('hunthub.theme.profile_edit_redesign_live', true);

        $user = User::factory()->create([
            'name' => 'Header Hunter',
            'username' => 'header-hunter',
        ]);

        $this->actingAs($user)
            ->getJson(route('profile.edit', ['dashboard_header' => 1]))
            ->assertOk()
            ->assertJsonPath('header.profile.name', 'Header Hunter')
            ->assertJsonPath('header.profile.handle', '@header-hunter');
    }

    public function test_existing_profile_update_endpoint_saves_fields_used_by_the_new_editor(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Updated Hunter',
                'headline' => 'Ready for the Bayou',
                'bio' => 'Tactical trio player.',
                'platform' => 'PC',
                'playstyle' => 'Tactical',
                'region' => 'EU',
                'language' => 'Deutsch',
                'hunt_role' => 'Scout',
                'discord_name' => 'updated-hunter',
                'steam_url' => 'https://steamcommunity.com/id/updated-hunter',
                'twitch_url' => 'https://www.twitch.tv/updated-hunter',
                'youtube_url' => 'https://www.youtube.com/@updated-hunter',
                'is_lfg_available' => '1',
                'profile_visibility' => 'registered',
            ])
            ->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Hunter',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'headline' => 'Ready for the Bayou',
            'bio' => 'Tactical trio player.',
            'platform' => 'PC',
            'playstyle' => 'Tactical',
            'region' => 'EU',
            'language' => 'Deutsch',
            'hunt_role' => 'Scout',
            'discord_name' => 'updated-hunter',
            'is_lfg_available' => 1,
            'profile_visibility' => 'registered',
        ]);
    }
}
