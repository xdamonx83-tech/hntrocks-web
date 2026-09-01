<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeGameRelease;
use App\Models\Arcade\ArcadeLaunchTicket;
use App\Models\User;
use Database\Seeders\ArcadeGameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArcadeDynamicPublisherTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_game_but_creation_is_always_draft(): void
    {
        $admin = $this->user(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/arcade-games', $this->gamePayload([
            'key' => 'dynamic-test',
            'status' => 'active',
            'type' => 'web',
        ]))->assertRedirect('/admin/arcade-games/dynamic-test/edit');

        $this->assertDatabaseHas('arcade_games', [
            'key' => 'dynamic-test',
            'status' => 'draft',
            'type' => 'web',
            'created_by' => $admin->id,
        ]);
    }

    public function test_dynamic_release_publisher_rejects_foreign_hosts_and_does_not_enable_game(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $game = $this->game('publisher-test', 'draft');
        $sha = str_repeat('a', 64);

        $this->actingAs($admin)->post('/admin/arcade-games/'.$game->key.'/releases', [
            'version' => '1.0.0',
            'entrypoint_url' => 'https://evil.example/game/index.html',
            'integrity_sha256' => $sha,
        ])->assertSessionHasErrors('entrypoint_url');

        $this->actingAs($admin)->post('/admin/arcade-games/'.$game->key.'/releases', [
            'version' => '1.0.0',
            'entrypoint_url' => 'https://games.hnt.rocks/publisher-test/1.0.0/index.html',
            'manifest_url' => 'https://games.hnt.rocks/publisher-test/1.0.0/manifest.json',
            'integrity_sha256' => $sha,
        ])->assertRedirect('/admin/arcade-games/'.$game->key.'/edit');

        $release = ArcadeGameRelease::query()->where('game_id', $game->id)->firstOrFail();
        $this->assertSame('draft', $release->status);
        $this->assertSame(1, $release->manifest['contract_version']);
        $this->assertSame('publisher-test', $release->manifest['game_key']);

        $this->actingAs($admin)->post('/admin/arcade-games/'.$game->key.'/releases/'.$release->id.'/publish')
            ->assertRedirect('/admin/arcade-games/'.$game->key.'/edit');

        $this->assertDatabaseHas('arcade_game_releases', ['id' => $release->id, 'status' => 'published']);
        $this->assertDatabaseHas('arcade_games', ['id' => $game->id, 'status' => 'draft']);
    }

    public function test_launch_ticket_is_short_lived_single_use_and_never_stores_raw_secret(): void
    {
        $user = $this->user();
        $game = $this->game('launch-test', 'active');
        $release = $this->publishedRelease($game);
        $accessToken = ApiAccessToken::createForUser($user, 'Dynamic Arcade test')['access_token'];

        $response = $this->withToken($accessToken)->postJson('/api/v1/arcade/games/'.$game->key.'/launch-tickets', [
            'client' => 'web',
        ])->assertCreated()
            ->assertJsonPath('data.contract_version', 1)
            ->assertJsonPath('data.origin', 'https://games.hnt.rocks')
            ->assertJsonPath('data.release.version', $release->version);

        $launchUrl = (string) $response->json('data.launch_url');
        $this->assertStringStartsWith($release->entrypoint_url.'#', $launchUrl);
        parse_str((string) parse_url($launchUrl, PHP_URL_FRAGMENT), $fragment);
        $rawTicket = (string) ($fragment['hnt_launch_ticket'] ?? '');
        $this->assertNotSame('', $rawTicket);
        $this->assertSame('1', (string) ($fragment['hnt_contract'] ?? ''));

        $ticket = ArcadeLaunchTicket::query()->firstOrFail();
        $this->assertSame(hash('sha256', $rawTicket), $ticket->token_hash);
        $this->assertNotSame($rawTicket, $ticket->token_hash);

        $this->postJson('/api/v1/arcade/launch-tickets/exchange', ['ticket' => $rawTicket])
            ->assertOk()
            ->assertJsonPath('data.game.key', 'launch-test')
            ->assertJsonPath('data.viewer.id', $user->id)
            ->assertJsonPath('data.capabilities.bearer_token_exposed', false)
            ->assertJsonPath('data.capabilities.launch_ticket_single_use', true);

        $this->postJson('/api/v1/arcade/launch-tickets/exchange', ['ticket' => $rawTicket])
            ->assertUnprocessable();
    }

    public function test_catalog_exposes_dynamic_descriptor_only_after_release_and_hunt_wins_stays_disabled(): void
    {
        $user = $this->user();
        $game = $this->game('catalog-web', 'active');
        $token = ApiAccessToken::createForUser($user, 'Dynamic Arcade catalog')['access_token'];

        $this->withToken($token)->getJson('/api/v1/arcade/games/catalog-web')
            ->assertOk()
            ->assertJsonPath('data.is_playable', false)
            ->assertJsonPath('data.unavailable_reason', 'dynamic_release_missing')
            ->assertJsonPath('data.dynamic_client.contract_version', 1)
            ->assertJsonPath('data.dynamic_client.release_version', null);

        $this->publishedRelease($game);

        $this->withToken($token)->getJson('/api/v1/arcade/games/catalog-web')
            ->assertOk()
            ->assertJsonPath('data.is_playable', true)
            ->assertJsonPath('data.dynamic_client.launch_ticket_required', true)
            ->assertJsonPath('data.dynamic_client.release_version', '1.0.0');

        $this->seed(ArcadeGameSeeder::class);
        $this->assertDatabaseHas('arcade_games', ['key' => 'hunt-wins', 'status' => 'disabled']);
    }

    private function publishedRelease(ArcadeGame $game): ArcadeGameRelease
    {
        return $game->releases()->create([
            'version' => '1.0.0',
            'status' => 'published',
            'entrypoint_url' => 'https://games.hnt.rocks/'.$game->key.'/1.0.0/index.html',
            'integrity_sha256' => str_repeat('b', 64),
            'manifest' => [
                'contract_version' => 1,
                'game_key' => $game->key,
                'version' => '1.0.0',
                'entrypoint_url' => 'https://games.hnt.rocks/'.$game->key.'/1.0.0/index.html',
                'integrity_sha256' => str_repeat('b', 64),
            ],
            'published_at' => now(),
        ]);
    }

    private function game(string $key, string $status): ArcadeGame
    {
        return ArcadeGame::query()->create($this->gamePayload([
            'key' => $key,
            'status' => $status,
            'type' => 'web',
        ]));
    }

    private function gamePayload(array $extra = []): array
    {
        return array_merge([
            'key' => 'dynamic-game',
            'name_de' => 'Dynamic Spiel',
            'name_en' => 'Dynamic Game',
            'type' => 'web',
            'status' => 'draft',
            'sort_order' => 20,
            'min_players' => 1,
            'max_players' => 1,
            'casual_enabled' => false,
            'ranked_enabled' => false,
            'game_version' => 1,
        ], $extra);
    }

    private function user(array $extra = []): User
    {
        $id = bin2hex(random_bytes(5));
        return User::query()->create(array_merge([
            'name' => 'Hunter',
            'username' => 'hunter_'.$id,
            'email' => $id.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ], $extra));
    }
}
