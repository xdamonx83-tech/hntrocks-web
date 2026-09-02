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

    public function test_guest_cannot_open_arcade_admin(): void
    {
        $this->get('/admin/arcade-games')->assertRedirect('/login');
    }

    public function test_normal_user_cannot_access_arcade_admin_pages_or_update(): void
    {
        $game = $this->game('guarded-game', 'disabled');
        $user = $this->user();

        $this->actingAs($user)->get('/admin/arcade-games')->assertForbidden();
        $this->actingAs($user)->get('/admin/arcade-games/'.$game->key.'/edit')->assertForbidden();
        $this->actingAs($user)->put('/admin/arcade-games/'.$game->key, $this->adminPayload())->assertForbidden();
    }

    public function test_admin_can_open_arcade_admin_pages_and_update_game(): void
    {
        $game = $this->game('editable-game', 'disabled');
        $admin = $this->user(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/arcade-games')->assertOk();
        $this->actingAs($admin)->get('/admin/arcade-games/'.$game->key.'/edit')->assertOk()->assertSee('name="min_client_version"', false);
        $this->actingAs($admin)->put('/admin/arcade-games/'.$game->key, $this->adminPayload([
            'name_de' => 'Aktualisiert',
            'min_client_version' => '1.2.3',
        ]))->assertRedirect(route('admin.arcade-games.index'));

        $this->assertDatabaseHas('arcade_games', [
            'key' => 'editable-game',
            'name_de' => 'Aktualisiert',
            'min_client_version' => '1.2.3',
            'updated_by' => $admin->id,
        ]);
    }

    public function test_memory_seed_registers_disabled_native_game_with_safe_defaults(): void
    {
        $this->seed(ArcadeGameSeeder::class);

        $this->assertDatabaseCount('arcade_games', 2);
        $huntWins = ArcadeGame::query()->where('key', 'hunt-wins')->firstOrFail();
        $memory = ArcadeGame::query()->where('key', 'hunt-memory')->firstOrFail();

        $this->assertSame('disabled', $memory->status->value);
        $this->assertSame('native', $memory->type->value);
        $this->assertSame('hunt-memory', $memory->client_engine_key);
        $this->assertSame('Hunt Memory', $memory->name_de);
        $this->assertSame('Hunt Memory', $memory->name_en);
        $this->assertSame('Ein düsteres Memory-Duell für zwei Hunter.', $memory->description_de);
        $this->assertSame('A dark memory duel for two hunters.', $memory->description_en);
        $this->assertSame(2, $memory->min_players);
        $this->assertSame(2, $memory->max_players);
        $this->assertTrue($memory->casual_enabled);
        $this->assertTrue($memory->ranked_enabled);
        $this->assertSame(1, $memory->game_version);
        $this->assertSame([], $memory->reward_settings);
        $this->assertSame(((int) $huntWins->sort_order) + 10, $memory->sort_order);
    }

    public function test_memory_seed_is_idempotent_and_preserves_existing_hunt_wins_and_memory_values(): void
    {
        $huntWins = $this->game('hunt-wins', 'active', 37, [
            'name_de' => 'Produktives Hunt Wins',
            'client_engine_key' => 'hunt-wins-live',
            'casual_enabled' => false,
            'ranked_enabled' => false,
            'min_client_version' => '9.9.9',
            'reward_settings' => ['existing' => 7],
        ]);

        $this->seed(ArcadeGameSeeder::class);

        $memory = ArcadeGame::query()->where('key', 'hunt-memory')->firstOrFail();
        $this->assertSame(47, $memory->sort_order);

        $memory->update([
            'name_de' => 'ACP Memory',
            'status' => 'maintenance',
            'sort_order' => 91,
            'casual_enabled' => false,
            'ranked_enabled' => false,
            'min_client_version' => '2.0.0',
            'reward_settings' => ['custom' => 5],
        ]);

        $this->seed(ArcadeGameSeeder::class);

        $this->assertDatabaseCount('arcade_games', 2);
        $huntWins->refresh();
        $memory->refresh();

        $this->assertSame('active', $huntWins->status->value);
        $this->assertSame(37, $huntWins->sort_order);
        $this->assertSame('Produktives Hunt Wins', $huntWins->name_de);
        $this->assertSame('hunt-wins-live', $huntWins->client_engine_key);
        $this->assertFalse($huntWins->casual_enabled);
        $this->assertFalse($huntWins->ranked_enabled);
        $this->assertSame('9.9.9', $huntWins->min_client_version);
        $this->assertSame(['existing' => 7], $huntWins->reward_settings);

        $this->assertSame('ACP Memory', $memory->name_de);
        $this->assertSame('maintenance', $memory->status->value);
        $this->assertSame(91, $memory->sort_order);
        $this->assertFalse($memory->casual_enabled);
        $this->assertFalse($memory->ranked_enabled);
        $this->assertSame('2.0.0', $memory->min_client_version);
        $this->assertSame(['custom' => 5], $memory->reward_settings);
    }

    private function game(string $key, string $status = 'active', int $sort = 0, array $extra = []): ArcadeGame
    {
        return ArcadeGame::query()->create(array_merge(['key'=>$key,'name_de'=>'Spiel','name_en'=>'Game','type'=>'native','status'=>$status,'sort_order'=>$sort,'min_players'=>1,'max_players'=>2,'casual_enabled'=>true,'ranked_enabled'=>false,'game_version'=>1], $extra));
    }
    private function adminPayload(array $extra = []): array { return array_merge(['name_de'=>'Spiel','name_en'=>'Game','type'=>'native','status'=>'disabled','sort_order'=>1,'min_players'=>1,'max_players'=>2,'game_version'=>1], $extra); }
    private function getAs(string $uri) { $user = $this->user(); return $this->withToken(ApiAccessToken::createForUser($user, 'Arcade test')['access_token'])->getJson($uri); }
    private function user(array $extra = []): User { $id = bin2hex(random_bytes(5)); return User::query()->create(array_merge(['name'=>'Hunter','username'=>'hunter_'.$id,'email'=>$id.'@example.test','password'=>'password','status'=>'active'], $extra)); }
}
