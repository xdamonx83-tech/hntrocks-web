<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Arcade\ArcadeGame;
use App\Models\User;
use Database\Seeders\ArcadeGameSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArcadeGameCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_requires_existing_api_authentication(): void
    {
        $this->getJson('/api/v1/arcade/games')->assertUnauthorized();
    }

    public function test_catalog_filters_statuses_and_orders_games(): void
    {
        foreach ([['event', 40], ['maintenance', 30], ['coming_soon', 20], ['active', 10], ['disabled', 0]] as [$status, $sort]) {
            $this->game($status.'-game', $status, $sort);
        }

        $this->getAs('/api/v1/arcade/games')->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.key', 'active-game')
            ->assertJsonPath('data.1.key', 'coming_soon-game')
            ->assertJsonPath('data.2.key', 'maintenance-game')
            ->assertJsonPath('data.3.key', 'event-game')
            ->assertJsonMissing(['key' => 'disabled-game']);
    }

    public function test_detail_localizes_and_unknown_or_disabled_keys_are_not_found(): void
    {
        $this->game('native-game', 'active', 1, ['name_de' => 'Deutsch', 'name_en' => 'English', 'client_engine_key' => 'native-v1']);
        $this->game('hidden', 'disabled');

        $this->getAs('/api/v1/arcade/games/native-game?locale=en')->assertOk()
            ->assertJsonPath('data.name', 'English')->assertJsonPath('data.type', 'native')
            ->assertJsonPath('data.is_playable', true)->assertJsonPath('data.client_engine_key', 'native-v1');
        $this->getAs('/api/v1/arcade/games/missing')->assertNotFound();
        $this->getAs('/api/v1/arcade/games/hidden')->assertNotFound();
    }

    public function test_web_resource_and_cover_url_are_client_ready(): void
    {
        Storage::fake('public');
        $this->game('web-game', 'maintenance', 1, ['type' => 'web', 'launch_url' => 'https://games.hnt.rocks/bounty-runner/', 'cover_path' => 'arcade/covers/game.jpg']);
        $this->getAs('/api/v1/arcade/games/web-game')->assertOk()
            ->assertJsonPath('data.launch_url', 'https://games.hnt.rocks/bounty-runner/')
            ->assertJsonPath('data.cover_url', '/storage/arcade/covers/game.jpg')
            ->assertJsonPath('data.is_playable', false)->assertJsonPath('data.unavailable_reason', 'maintenance');
    }

    public function test_model_enforces_unique_key_and_player_bounds(): void
    {
        $this->game('unique');
        try { $this->game('unique'); $this->fail('Duplicate key accepted.'); } catch (QueryException) { $this->assertTrue(true); }
        foreach ([['min_players' => 0], ['min_players' => 2, 'max_players' => 1]] as $invalid) {
            try { $this->game(bin2hex(random_bytes(3)), 'active', 0, $invalid); $this->fail('Invalid player bounds accepted.'); } catch (ValidationException) { $this->assertTrue(true); }
        }
    }

    public function test_admin_launch_url_only_accepts_https_on_trusted_hosts(): void
    {
        $game = $this->game('admin-web', 'disabled', 1, ['type' => 'web']);
        $admin = $this->user(['is_admin' => true]);
        $base = ['name_de'=>'Web','name_en'=>'Web','type'=>'web','status'=>'disabled','sort_order'=>1,'min_players'=>1,'max_players'=>1,'game_version'=>1];
        $this->actingAs($admin)->put('/admin/arcade-games/'.$game->key, $base + ['launch_url'=>'https://games.hnt.rocks/game/'])->assertSessionHasNoErrors();
        foreach (['javascript:alert(1)', 'data:text/html,bad', 'file:///tmp/game', 'http://games.hnt.rocks/game', 'https://evil.example/game'] as $url) {
            $this->actingAs($admin)->put('/admin/arcade-games/'.$game->key, $base + ['launch_url'=>$url])->assertSessionHasErrors('launch_url');
        }
    }

    public function test_hunt_wins_seed_is_idempotent_and_disabled(): void
    {
        $this->seed(ArcadeGameSeeder::class); $this->seed(ArcadeGameSeeder::class);
        $this->assertDatabaseCount('arcade_games', 1);
        $game = ArcadeGame::query()->where('key', 'hunt-wins')->firstOrFail();
        $this->assertSame('disabled', $game->status->value); $this->assertSame(2, $game->min_players); $this->assertSame(2, $game->max_players);
        $this->assertTrue($game->casual_enabled); $this->assertTrue($game->ranked_enabled); $this->assertSame('hunt-wins', $game->client_engine_key);
    }

    private function game(string $key, string $status = 'active', int $sort = 0, array $extra = []): ArcadeGame
    {
        return ArcadeGame::query()->create(array_merge(['key'=>$key,'name_de'=>'Spiel','name_en'=>'Game','type'=>'native','status'=>$status,'sort_order'=>$sort,'min_players'=>1,'max_players'=>2,'casual_enabled'=>true,'ranked_enabled'=>false,'game_version'=>1], $extra));
    }
    private function getAs(string $uri) { $user = $this->user(); return $this->withToken(ApiAccessToken::createForUser($user, 'Arcade test')['access_token'])->getJson($uri); }
    private function user(array $extra = []): User { $id = bin2hex(random_bytes(5)); return User::query()->create(array_merge(['name'=>'Hunter','username'=>'hunter_'.$id,'email'=>$id.'@example.test','password'=>'password','status'=>'active'], $extra)); }
}
