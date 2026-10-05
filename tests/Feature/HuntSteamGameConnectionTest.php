<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\HuntGameConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class HuntSteamGameConnectionTest extends TestCase
{
    use RefreshDatabase;

    // Only migrate tables needed by this isolated feature. The full HNT
    // migration history contains MySQL-only FK operations incompatible with
    // SQLite, so it cannot run in memory.
    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/2026_04_29_000001_create_users_table.php',
            'database/migrations/2026_04_29_000018_create_api_access_tokens_table.php',
            'database/migrations/2026_10_05_120000_create_hunt_game_connections.php',
        ]];
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('status', 24)->default('active');
            });
        }
        config()->set('hunt_platform.steam.enabled', true);
        config()->set('hunt_platform.steam.api_key', 'test-key');

        Http::preventStrayRequests();
        Http::fake([
            'https://steamcommunity.com/openid/login' => Http::response("ns:http://specs.openid.net/auth/2.0\nis_valid:true\n"),
            'https://api.steampowered.com/IPlayerService/GetOwnedGames/*' => Http::response([
                'response' => [
                    'games' => [
                        ['appid' => 594650, 'playtime_forever' => 1234],
                    ],
                ],
            ]),
            'https://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/*' => Http::response([
                'playerstats' => [
                    'success' => true,
                    'achievements' => [
                        ['apiname' => 'ACH_ONE', 'achieved' => 1],
                        ['apiname' => 'ACH_TWO', 'achieved' => 0],
                    ],
                ],
            ]),
        ]);
    }

    public function test_authenticated_account_can_be_linked_without_logging_into_hnt_with_steam(): void
    {
        $user = $this->user();
        $callback = $this->start($user);

        $this->get($callback)->assertRedirect('/profile/edit?steam_connection=connected');

        $account = HuntGameConnection::query()->sole();
        $this->assertSame($user->id, $account->user_id);
        $this->assertSame('steam', $account->provider);
        $this->assertSame('76561198000000001', $account->provider_user_id);
        $this->assertSame(1234, $account->hunt_stats['playtime_minutes']);
        $this->assertSame(1, $account->hunt_stats['achievements_unlocked']);
        $this->assertSame(2, $account->hunt_stats['achievements_total']);
        $this->assertSame(50.0, (float) $account->hunt_stats['achievements_percent']);

        // Callback links to the initiating HNT user; it never calls Auth::login.

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'GetOwnedGames')) return false;
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $params);

            return ($params['appids_filter'][0] ?? null) === '594650'
                && ($params['include_appinfo'] ?? null) === '0';
        });
    }

    public function test_state_is_one_time_and_replay_is_rejected(): void
    {
        $callback = $this->start($this->user());
        $this->get($callback)->assertRedirect('/profile/edit?steam_connection=connected');
        $this->get($callback)->assertRedirect('/profile/edit?steam_connection=expired');
        $this->assertSame(1, HuntGameConnection::count());
    }

    public function test_forged_steam_identity_is_rejected_even_if_provider_returns_valid(): void
    {
        $callback = $this->start($this->user());
        $forged = str_replace(
            'steamcommunity.com%2Fopenid%2Fid%2F76561198000000001',
            'evil.example%2Fopenid%2Fid%2F76561198000000001',
            $callback,
        );

        $this->get($forged)->assertRedirect('/profile/edit?steam_connection=invalid_identity');
        $this->assertSame(0, HuntGameConnection::count());
    }

    public function test_one_steam_identity_cannot_be_linked_to_two_hnt_accounts(): void
    {
        $first = $this->user();
        $second = $this->user();

        $this->get($this->start($first))->assertRedirect('/profile/edit?steam_connection=connected');
        $this->get($this->start($second))->assertRedirect('/profile/edit?steam_connection=already_linked');

        $this->assertSame($first->id, HuntGameConnection::query()->sole()->user_id);
    }

    public function test_unauthenticated_calls_are_denied(): void
    {
        $this->postJson('/api/v1/me/game-accounts/steam/start')->assertUnauthorized();
        $this->getJson('/api/v1/me/game-accounts')->assertUnauthorized();
        $this->deleteJson('/api/v1/me/game-accounts/steam')->assertUnauthorized();
    }

    public function test_user_can_disconnect_without_affecting_hnt_login(): void
    {
        $user = $this->user();
        $this->get($this->start($user))->assertRedirect('/profile/edit?steam_connection=connected');

        $this->withToken($this->token($user))
            ->deleteJson('/api/v1/me/game-accounts/steam')
            ->assertOk()->assertJsonPath('disconnected', true);

        $this->assertSame(0, HuntGameConnection::count());
        $this->assertNotNull(User::find($user->id));
    }

    private function start(User $user): string
    {
        $response = $this->withToken($this->token($user))
            ->postJson('/api/v1/me/game-accounts/steam/start')
            ->assertOk();

        $authorize = $response->json('authorize_url');
        parse_str((string) parse_url($authorize, PHP_URL_QUERY), $params);
        $returnTo = $params['openid.return_to'];

        parse_str((string) parse_url($returnTo, PHP_URL_QUERY), $returnQuery);

        return '/game-accounts/steam/callback?'.http_build_query([
            ...$returnQuery,
            'openid_ns' => 'http://specs.openid.net/auth/2.0',
            'openid_mode' => 'id_res',
            'openid_op_endpoint' => 'https://steamcommunity.com/openid/login',
            'openid_claimed_id' => 'https://steamcommunity.com/openid/id/76561198000000001',
            'openid_identity' => 'https://steamcommunity.com/openid/id/76561198000000001',
            'openid_return_to' => $returnTo,
            'openid_signed' => 'op_endpoint,claimed_id,identity,return_to,response_nonce',
            'openid_response_nonce' => '2026-10-05T10:00:00Zmocknonce',
            'openid_sig' => 'mock_signature_verified_by_fake_steam',
        ]);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Hunt game test')['access_token'];
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
