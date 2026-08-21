<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiUserProfileTwitchUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_api_returns_saved_twitch_url(): void
    {
        $viewer = User::factory()->create([
            'status' => 'active',
        ]);
        $target = User::factory()->create([
            'status' => 'active',
        ]);

        $target->profile()->create([
            'profile_visibility' => 'public',
            'discord_name' => 'Krispie#1234',
            'twitch_url' => 'https://www.twitch.tv/krispiearmy',
        ]);

        $token = ApiAccessToken::createForUser($viewer, 'User profile Twitch API test')['access_token'];

        $this->withToken($token)
            ->getJson('/api/v1/users/'.$target->username)
            ->assertOk()
            ->assertJsonPath('user.profile.discord_name', 'Krispie#1234')
            ->assertJsonPath('user.profile.twitch_url', 'https://www.twitch.tv/krispiearmy');
    }
}
