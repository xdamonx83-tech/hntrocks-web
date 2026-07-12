<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileCoverDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_always_mode_renders_the_cover_open_and_avatar_edit_targets_media_tab(): void
    {
        config()->set('hunthub.theme.profile_redesign_live', true);

        $user = User::factory()->create();
        $user->profile()->create([
            'profile_visibility' => 'public',
            'cover_display_mode' => UserProfile::COVER_DISPLAY_ALWAYS,
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('data-cover-display-mode="always"', false)
            ->assertSee('profile-summary-card is-cover-peek', false)
            ->assertSee(route('profile.edit', ['tab' => 'media']), false);
    }

    public function test_hidden_mode_omits_the_cover_layer_and_toggle(): void
    {
        config()->set('hunthub.theme.profile_redesign_live', true);

        $user = User::factory()->create();
        $user->profile()->create([
            'profile_visibility' => 'public',
            'cover_display_mode' => UserProfile::COVER_DISPLAY_HIDDEN,
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('data-cover-display-mode="hidden"', false)
            ->assertDontSee('profile-cover-peek-layer', false)
            ->assertDontSee('data-profile-cover-toggle', false);
    }
}
