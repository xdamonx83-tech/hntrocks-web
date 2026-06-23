<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\LiveLobby;
use App\Models\User;
use App\Services\LiveLobbyNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveLobbyApiTest extends TestCase
{
    use RefreshDatabase;

    private object $notifications;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifications = new class extends LiveLobbyNotificationService {
            public int $announcements = 0;
            public function __construct() {}
            public function announce(LiveLobby $lobby): void { $this->announcements++; }
            public function joined(LiveLobby $lobby, User $member): void {}
            public function full(LiveLobby $lobby, User $actor): void {}
        };
        $this->app->instance(LiveLobbyNotificationService::class, $this->notifications);
    }

    public function test_authentication_is_required(): void
    {
        $this->getJson('/api/v1/live-lobbies')->assertUnauthorized();
    }

    public function test_user_can_create_duo_lobby_and_creator_becomes_member(): void
    {
        $user = $this->user();

        $response = $this->postAs($user, '/api/v1/live-lobbies', [
            'mode' => 'duo',
            'platform' => 'pc',
            'voice_required' => true,
            'lobby_code' => 'HUNT-123',
        ])->assertCreated()
            ->assertJsonPath('data.mode', 'duo')
            ->assertJsonPath('data.slots_total', 2)
            ->assertJsonPath('data.slots_filled', 1)
            ->assertJsonPath('data.crossplay_pool', 'pc')
            ->assertJsonPath('data.viewer.is_creator', true)
            ->assertJsonPath('data.contact.lobby_code', 'HUNT-123');

        $lobby = LiveLobby::where('public_id', $response->json('data.id'))->firstOrFail();
        $this->assertDatabaseHas('live_lobby_members', [
            'live_lobby_id' => $lobby->id,
            'user_id' => $user->id,
            'role' => 'creator',
        ]);
        $this->assertTrue($lobby->expires_at->between(now()->addMinutes(14), now()->addMinutes(16)));
        $this->assertSame(1, $this->notifications->announcements);
    }

    public function test_trio_and_console_crossplay_pools_are_derived(): void
    {
        $playstation = $this->createLobby($this->user(), ['mode' => 'trio', 'platform' => 'playstation']);
        $this->assertSame(3, $playstation->slots_total);
        $this->assertSame('console', $playstation->crossplay_pool);

        $xbox = $this->createLobby($this->user(), ['platform' => 'xbox']);
        $this->assertSame('console', $xbox->crossplay_pool);
    }

    public function test_pc_lobby_rejects_console_and_accepts_pc(): void
    {
        $lobby = $this->createLobby($this->user(), ['platform' => 'pc']);

        $this->postAs($this->user(), $this->action($lobby, 'join'), ['platform' => 'xbox'])
            ->assertUnprocessable();

        $this->postAs($this->user(), $this->action($lobby, 'join'), ['platform' => 'pc'])
            ->assertOk()
            ->assertJsonPath('data.slots_filled', 2)
            ->assertJsonPath('data.status', 'full');

        $lobby->refresh();
        $this->assertTrue($lobby->expires_at->between(now()->addMinutes(4), now()->addMinutes(6)));
    }

    public function test_playstation_and_xbox_can_join_each_other(): void
    {
        $playstation = $this->createLobby($this->user(), ['platform' => 'playstation']);
        $this->postAs($this->user(), $this->action($playstation, 'join'), ['platform' => 'xbox'])->assertOk();

        $xbox = $this->createLobby($this->user(), ['platform' => 'xbox']);
        $this->postAs($this->user(), $this->action($xbox, 'join'), ['platform' => 'playstation'])->assertOk();
    }

    public function test_contact_fields_are_hidden_until_viewer_joins(): void
    {
        $lobby = $this->createLobby($this->user(), ['mode' => 'trio', 'lobby_code' => 'SECRET']);
        $viewer = $this->user();

        $this->getAs($viewer, '/api/v1/live-lobbies/'.$lobby->public_id)
            ->assertOk()->assertJsonMissingPath('data.contact');

        $this->postAs($viewer, $this->action($lobby, 'join'), ['platform' => 'pc'])
            ->assertOk()->assertJsonPath('data.contact.lobby_code', 'SECRET');
    }

    public function test_links_and_discord_invites_are_rejected(): void
    {
        foreach (['https://example.com', 'www.example.com', 'discord.gg/hunt', 'discord.com/invite/hunt', 'custom://invite'] as $unsafe) {
            $this->postAs($this->user(), '/api/v1/live-lobbies', [
                'mode' => 'duo',
                'platform' => 'pc',
                'note' => $unsafe,
            ])->assertUnprocessable()->assertJsonValidationErrors('note');
        }
    }

    public function test_creator_leave_closes_lobby_and_only_creator_can_close(): void
    {
        $creator = $this->user();
        $lobby = $this->createLobby($creator);

        $this->postAs($this->user(), $this->action($lobby, 'close'))->assertForbidden();
        $this->postAs($creator, $this->action($lobby, 'leave'))->assertOk()->assertJsonPath('data.status', 'closed');
    }

    public function test_user_may_only_have_one_active_membership(): void
    {
        $user = $this->user();
        $this->createLobby($user);

        $this->postAs($user, '/api/v1/live-lobbies', ['mode' => 'duo', 'platform' => 'pc'])
            ->assertUnprocessable();
    }

    public function test_member_leave_reopens_full_lobby_and_recounts_slots(): void
    {
        $lobby = $this->createLobby($this->user());
        $member = $this->user();
        $this->postAs($member, $this->action($lobby, 'join'), ['platform' => 'pc'])->assertOk();

        $this->postAs($member, $this->action($lobby, 'leave'))
            ->assertOk()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.slots_filled', 1);
    }

    public function test_closed_and_expired_lobbies_are_not_listed(): void
    {
        $viewer = $this->user();
        $closed = $this->createLobby($this->user());
        $closed->update(['status' => 'closed', 'closed_at' => now()]);
        $expired = $this->createLobby($this->user());
        $expired->update(['expires_at' => now()->subSecond()]);

        $this->getAs($viewer, '/api/v1/live-lobbies')
            ->assertOk()
            ->assertJsonMissing(['public_id' => $closed->public_id])
            ->assertJsonMissing(['public_id' => $expired->public_id]);

        $this->assertDatabaseHas('live_lobbies', ['id' => $expired->id, 'status' => 'expired']);
    }

    private function createLobby(User $creator, array $overrides = []): LiveLobby
    {
        $response = $this->postAs($creator, '/api/v1/live-lobbies', array_merge([
            'mode' => 'duo',
            'platform' => 'pc',
        ], $overrides))->assertCreated();

        return LiveLobby::where('public_id', $response->json('data.id'))->firstOrFail();
    }

    private function action(LiveLobby $lobby, string $action): string
    {
        return '/api/v1/live-lobbies/'.$lobby->public_id.'/'.$action;
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->getJson($uri);
    }

    private function postAs(User $user, string $uri, array $payload = [])
    {
        return $this->withToken($this->token($user))->postJson($uri, $payload);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Live Lobby API test')['access_token'];
    }

    private function user(): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create([
            'name' => 'Hunter '.$suffix,
            'username' => 'hunter_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
    }
}
