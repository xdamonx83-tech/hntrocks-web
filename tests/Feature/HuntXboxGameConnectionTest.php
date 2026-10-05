<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\HuntGameConnection;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HuntXboxGameConnectionTest extends TestCase
{
    use RefreshDatabase;

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

        config()->set('hunt_platform.xbox.enabled', true);
        config()->set('hunt_platform.xbox.client_id', 'test-client-id');
        config()->set('hunt_platform.xbox.client_secret', 'fake-client-secret');
        config()->set('hunt_platform.xbox.xuid_storage_authorized', true);
        config()->set('hunt_platform.xbox.hunt_stats_authorized', false);

        Http::preventStrayRequests();
        Http::fake([
            'https://login.microsoftonline.com/consumers/oauth2/v2.0/token' => Http::response([
                'access_token' => 'fake-msa-access',
                'token_type' => 'Bearer',
            ]),
            'https://user.auth.xboxlive.com/user/authenticate' => Http::response([
                'Token' => 'fake-u-token',
                'DisplayClaims' => ['xui' => [['uhs' => 'fake-user-hash']]],
            ]),
            'https://xsts.auth.xboxlive.com/xsts/authorize' => Http::response([
                'Token' => 'fake-x-token',
                'DisplayClaims' => ['xui' => [[
                    'xid' => '2533274800000000',
                    'gtg' => 'HunterExample',
                    'uhs' => 'fake-user-hash',
                ]]],
            ]),
        ]);
    }

    public function test_disabled_without_explicit_permission_to_store_xbox_id(): void
    {
        config()->set('hunt_platform.xbox.xuid_storage_authorized', false);
        $this->withToken($this->token($this->user()))
            ->postJson('/api/v1/me/game-accounts/xbox/start')
            ->assertStatus(503);

        $this->assertSame(0, HuntGameConnection::count());
        Http::assertNothingSent();
    }

    public function test_xbox_verification_is_independent_of_hnt_login(): void
    {
        $user = $this->user();
        $callback = $this->start($user);

        $this->get($callback)->assertRedirect('/profile/edit?xbox_connection=connected');

        $account = HuntGameConnection::query()->sole();
        $this->assertSame($user->id, $account->user_id);
        $this->assertSame('xbox', $account->provider);
        $this->assertSame('2533274800000000', $account->provider_user_id);
        $this->assertSame('HunterExample', $account->provider_name);
        $this->assertNull($account->hunt_stats);
        $this->assertSame('hunt_stats_api_not_approved', $account->sync_error);

        Http::assertSentCount(3);
        Http::assertSent(fn ($req) => str_ends_with($req->url(), '/token')
            && $req->method() === 'POST');
    }

    public function test_replayed_state_is_rejected(): void
    {
        $callback = $this->start($this->user());
        $this->get($callback)->assertRedirect('/profile/edit?xbox_connection=connected');
        $this->get($callback)->assertRedirect('/profile/edit?xbox_connection=expired');
        $this->assertSame(1, HuntGameConnection::count());
    }

    public function test_xuid_cannot_be_reused_by_another_hnt_user(): void
    {
        $first = $this->user();
        $second = $this->user();
        $this->get($this->start($first))->assertRedirect('/profile/edit?xbox_connection=connected');
        $this->get($this->start($second))->assertRedirect('/profile/edit?xbox_connection=already_linked');
        $this->assertSame($first->id, HuntGameConnection::query()->sole()->user_id);
    }

    public function test_xbox_start_requires_authentication(): void
    {
        $this->postJson('/api/v1/me/game-accounts/xbox/start')->assertUnauthorized();
        $this->deleteJson('/api/v1/me/game-accounts/xbox')->assertUnauthorized();
    }

    private function start(User $user): string
    {
        $response = $this->withToken($this->token($user))
            ->postJson('/api/v1/me/game-accounts/xbox/start')->assertOk();

        $url = $response->json('authorize_url');
        $this->assertStringStartsWith(
            'https://login.microsoftonline.com/consumers/oauth2/v2.0/authorize?',
            $url
        );
        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
        $this->assertSame(
            url('/game-accounts/xbox/callback'),
            $params['redirect_uri']
        );

        return '/game-accounts/xbox/callback?'.http_build_query([
            'state' => $params['state'],
            'code' => 'fake-authorization-code',
        ]);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Xbox test')['access_token'];
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
