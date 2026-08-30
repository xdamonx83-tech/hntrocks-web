<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiUserProfileTwitchUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_api_returns_saved_twitch_url(): void
    {
        $viewer = $this->createActiveUser();
        $target = $this->createActiveUser();

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

    public function test_me_profile_api_saves_twitch_url(): void
    {
        $user = $this->createActiveUser();
        $user->profile()->create(['profile_visibility' => 'public']);
        $token = ApiAccessToken::createForUser($user, 'Twitch profile update test')['access_token'];

        $this->withToken($token)
            ->postJson('/api/v1/me/profile', [
                'twitch_url' => 'https://www.twitch.tv/krispiearmy',
            ])
            ->assertOk()
            ->assertJsonPath('user.profile.twitch_url', 'https://www.twitch.tv/krispiearmy');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'twitch_url' => 'https://www.twitch.tv/krispiearmy',
        ]);
    }

    public function test_user_profile_api_returns_twitch_live_status(): void
    {
        config()->set('social.providers.twitch.client_id', 'test-client');
        config()->set('social.providers.twitch.client_secret', 'test-secret');
        config()->set('social.providers.twitch.token_url', 'https://twitch.test/token');
        config()->set('social.providers.twitch.streams_url', 'https://twitch.test/streams');

        Http::fake([
            'https://twitch.test/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ]),
            'https://twitch.test/streams*' => Http::response([
                'data' => [[
                    'user_name' => 'KrispieArmy',
                    'title' => 'Bayou live',
                    'game_name' => 'Hunt: Showdown 1896',
                    'viewer_count' => 42,
                    'started_at' => '2026-08-21T18:00:00Z',
                ]],
            ]),
        ]);

        $viewer = $this->createActiveUser();
        $target = $this->createActiveUser();
        $target->profile()->create([
            'profile_visibility' => 'public',
            'twitch_url' => 'https://www.twitch.tv/krispiearmy',
        ]);
        $token = ApiAccessToken::createForUser($viewer, 'Twitch live status test')['access_token'];

        $this->withToken($token)
            ->getJson('/api/v1/users/'.$target->username)
            ->assertOk()
            ->assertJsonPath('twitch.state', 'live')
            ->assertJsonPath('twitch.is_live', true)
            ->assertJsonPath('twitch.channel', 'krispiearmy')
            ->assertJsonPath('twitch.viewer_count', 42);
    }

    private function createActiveUser(): User
    {
        $nonce = 'twitch_'.bin2hex(random_bytes(6));

        return User::query()->create([
            'name' => 'Twitch API Test',
            'username' => $nonce,
            'email' => $nonce.'@example.invalid',
            'password' => 'Patch335-Twitch-Test-2026!',
            'status' => 'active',
        ]);
    }
}
