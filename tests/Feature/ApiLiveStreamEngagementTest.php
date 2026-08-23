<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiLiveStreamEngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_viewers_can_chat_and_send_multiple_hearts(): void
    {
        $viewer = $this->createActiveUser('viewer');
        $streamer = $this->createActiveUser('streamer');
        $streamer->profile()->create([
            'profile_visibility' => 'public',
            'twitch_url' => 'https://www.twitch.tv/hunterlive',
        ]);
        $this->fakeLiveTwitch();
        $token = ApiAccessToken::createForUser($viewer, 'Live engagement test')['access_token'];

        $this->withToken($token)
            ->postJson('/api/v1/users/'.$streamer->username.'/live/comments', [
                'body' => 'Waidmannsheil aus dem Livechat!',
            ])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Waidmannsheil aus dem Livechat!')
            ->assertJsonPath('data.author.username', $viewer->username);

        $this->withToken($token)
            ->postJson('/api/v1/users/'.$streamer->username.'/live/hearts', ['count' => 7])
            ->assertOk()
            ->assertJsonPath('hearts_count', 7)
            ->assertJsonPath('viewer_hearts_count', 7);

        $this->withToken($token)
            ->postJson('/api/v1/users/'.$streamer->username.'/live/hearts', ['count' => 3])
            ->assertOk()
            ->assertJsonPath('hearts_count', 10)
            ->assertJsonPath('viewer_hearts_count', 10);

        $this->withToken($token)
            ->getJson('/api/v1/users/'.$streamer->username.'/live')
            ->assertOk()
            ->assertJsonPath('stream.is_live', true)
            ->assertJsonCount(1, 'comments')
            ->assertJsonPath('comments.0.body', 'Waidmannsheil aus dem Livechat!')
            ->assertJsonPath('hearts_count', 10);
    }

    public function test_offline_stream_rejects_comments_and_hearts(): void
    {
        $viewer = $this->createActiveUser('offline-viewer');
        $streamer = $this->createActiveUser('offline-streamer');
        $streamer->profile()->create([
            'profile_visibility' => 'public',
            'twitch_url' => 'https://www.twitch.tv/hunteroffline',
        ]);
        $this->fakeOfflineTwitch();
        $token = ApiAccessToken::createForUser($viewer, 'Offline live test')['access_token'];

        $this->withToken($token)
            ->getJson('/api/v1/users/'.$streamer->username.'/live')
            ->assertOk()
            ->assertJsonPath('stream.is_live', false)
            ->assertJsonPath('session_key', null)
            ->assertJsonCount(0, 'comments');

        $this->withToken($token)
            ->postJson('/api/v1/users/'.$streamer->username.'/live/comments', ['body' => 'Test'])
            ->assertStatus(409);

        $this->withToken($token)
            ->postJson('/api/v1/users/'.$streamer->username.'/live/hearts', ['count' => 1])
            ->assertStatus(409);
    }

    private function fakeLiveTwitch(): void
    {
        $this->configureTwitch();
        Http::fake([
            'https://twitch.test/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ]),
            'https://twitch.test/streams*' => Http::response([
                'data' => [[
                    'user_name' => 'HunterLive',
                    'title' => 'Live aus dem Bayou',
                    'game_name' => 'Hunt: Showdown 1896',
                    'viewer_count' => 42,
                    'started_at' => '2026-08-22T18:00:00Z',
                ]],
            ]),
        ]);
    }

    private function fakeOfflineTwitch(): void
    {
        $this->configureTwitch();
        Http::fake([
            'https://twitch.test/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ]),
            'https://twitch.test/streams*' => Http::response(['data' => []]),
        ]);
    }

    private function configureTwitch(): void
    {
        config()->set('social.providers.twitch.client_id', 'test-client');
        config()->set('social.providers.twitch.client_secret', 'test-secret');
        config()->set('social.providers.twitch.token_url', 'https://twitch.test/token');
        config()->set('social.providers.twitch.streams_url', 'https://twitch.test/streams');
    }

    private function createActiveUser(string $prefix): User
    {
        $nonce = $prefix.'_'.bin2hex(random_bytes(5));

        return User::query()->create([
            'name' => ucfirst($prefix),
            'username' => $nonce,
            'email' => $nonce.'@example.invalid',
            'password' => 'Patch-live-stream-test-2026!',
            'status' => 'active',
        ]);
    }
}
