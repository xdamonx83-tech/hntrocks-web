<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSocialTwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_renders_saved_social_links_and_twitch_tab(): void
    {
        config()->set('hunthub.theme.profile_redesign_live', true);

        $user = User::factory()->create();
        $user->profile()->create([
            'profile_visibility' => 'public',
            'steam_url' => 'https://steamcommunity.com/id/example',
            'twitch_url' => 'https://www.twitch.tv/krispiearmy',
            'youtube_url' => 'https://www.youtube.com/@example',
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('profile-social-bar', false)
            ->assertSee('https://steamcommunity.com/id/example', false)
            ->assertSee('https://www.twitch.tv/krispiearmy', false)
            ->assertSee('https://www.youtube.com/@example', false)
            ->assertSee('data-profile-tab="twitch"', false)
            ->assertSee('data-channel="krispiearmy"', false)
            ->assertSee('profileTwitchPlayer', false);
    }

    public function test_invalid_twitch_host_does_not_render_twitch_tab(): void
    {
        config()->set('hunthub.theme.profile_redesign_live', true);

        $user = User::factory()->create();
        $user->profile()->create([
            'profile_visibility' => 'public',
            'twitch_url' => 'https://example.com/not-twitch',
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertDontSee('data-profile-tab="twitch"', false)
            ->assertDontSee('profileTwitchPlayer', false);
    }
}
