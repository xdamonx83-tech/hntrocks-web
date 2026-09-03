<?php

namespace Tests\Feature;

use App\Enums\Arcade\ArcadeMatchMode;
use App\Enums\Arcade\ArcadeMatchStatus;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeMatch;
use App\Models\CrownWallet;
use App\Models\User;
use App\Services\Arcade\ArcadeGameEngineRegistry;
use App\Services\Arcade\ArcadeMatchService;
use App\Services\Arcade\ArcadeMoveService;
use App\Services\Arcade\Engines\HuntersMarkEngine;
use Database\Seeders\ArcadeGameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HuntersMarkArcadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_registers_disabled_native_game_and_preserves_acp_values(): void
    {
        $this->seed(ArcadeGameSeeder::class);

        $game = ArcadeGame::query()->where('key', 'hunters-mark')->firstOrFail();
        $this->assertSame("Hunter's Mark", $game->name_de);
        $this->assertSame("Hunter's Mark", $game->name_en);
        $this->assertSame('disabled', $game->status->value);
        $this->assertSame('native', $game->type->value);
        $this->assertSame('hunters-mark', $game->client_engine_key);
        $this->assertSame(2, $game->min_players);
        $this->assertSame(2, $game->max_players);
        $this->assertTrue($game->casual_enabled);
        $this->assertTrue($game->ranked_enabled);
        $this->assertSame([], $game->reward_settings);

        $game->update([
            'status' => 'active',
            'sort_order' => 123,
            'casual_enabled' => false,
            'ranked_enabled' => false,
            'reward_settings' => ['ranked_reward_enabled' => true, 'ranked_win_reward' => 17],
        ]);

        $this->seed(ArcadeGameSeeder::class);
        $game->refresh();

        $this->assertSame('active', $game->status->value);
        $this->assertSame(123, $game->sort_order);
        $this->assertFalse($game->casual_enabled);
        $this->assertFalse($game->ranked_enabled);
        $this->assertSame(['ranked_reward_enabled' => true, 'ranked_win_reward' => 17], $game->reward_settings);
    }

    public function test_registry_resolves_hunters_mark_engine(): void
    {
        $game = $this->game();
        $this->assertInstanceOf(HuntersMarkEngine::class, app(ArcadeGameEngineRegistry::class)->resolve($game));
    }

    public function test_generic_move_pipeline_finishes_ranked_match_and_processes_stats_rewards_once(): void
    {
        config(['crowns.enabled' => true]);
        $game = $this->game([
            'ranked_enabled' => true,
            'reward_settings' => [
                'ranked_reward_enabled' => true,
                'ranked_win_reward' => 10,
                'ranked_draw_reward' => 3,
                'ranked_loss_reward' => 1,
            ],
        ]);
        $one = $this->user('mark_one');
        $two = $this->user('mark_two');
        $match = $this->activeMatch($game, $one, $two, ArcadeMatchMode::Ranked);
        $moves = [[$one, 0], [$two, 3], [$one, 1], [$two, 4], [$one, 2]];
        $service = app(ArcadeMoveService::class);

        foreach ($moves as $index => [$actor, $cell]) {
            $match = $service->move($match, $actor, 'hunters-mark-'.$index, ['action' => 'place', 'cell_index' => $cell]);
        }

        $this->assertSame(ArcadeMatchStatus::Finished, $match->status);
        $this->assertSame(1, $match->winner_seat);
        $this->assertSame([0, 1, 2], $match->state['winning_cells']);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'user_id' => $one->id, 'result' => 'win']);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'user_id' => $two->id, 'result' => 'loss']);
        $this->assertDatabaseHas('arcade_user_stats', ['user_id' => $one->id, 'game_id' => $game->id, 'mode' => 'ranked', 'matches_played' => 1, 'wins' => 1]);
        $this->assertDatabaseHas('arcade_user_stats', ['user_id' => $two->id, 'game_id' => $game->id, 'mode' => 'ranked', 'matches_played' => 1, 'losses' => 1]);
        $this->assertDatabaseCount('arcade_match_finalizations', 1);
        $this->assertSame(10, (int) CrownWallet::query()->where('user_id', $one->id)->value('balance'));
        $this->assertSame(1, (int) CrownWallet::query()->where('user_id', $two->id)->value('balance'));

        $retry = $service->move($match, $one, 'hunters-mark-4', ['action' => 'place', 'cell_index' => 2]);
        $this->assertSame($match->version, $retry->version);
        $this->assertDatabaseCount('arcade_match_finalizations', 1);
    }

    public function test_generic_forfeit_flow_works_for_active_hunters_mark_match(): void
    {
        $game = $this->game(['ranked_enabled' => true]);
        $one = $this->user('forfeit_one');
        $two = $this->user('forfeit_two');
        $match = $this->activeMatch($game, $one, $two, ArcadeMatchMode::Ranked);

        $result = app(ArcadeMatchService::class)->forfeit($match, $one);

        $this->assertSame(ArcadeMatchStatus::Finished, $result->status);
        $this->assertSame(2, $result->winner_seat);
        $this->assertSame('forfeit', $result->state['termination']['type']);
        $this->assertSame(1, $result->state['termination']['forfeited_seat']);
        $this->assertSame(2, $result->state['termination']['winner_seat']);
    }

    private function game(array $extra = []): ArcadeGame
    {
        return ArcadeGame::query()->create(array_merge([
            'key' => 'hunters-mark',
            'name_de' => "Hunter's Mark",
            'name_en' => "Hunter's Mark",
            'type' => 'native',
            'status' => 'active',
            'sort_order' => 30,
            'min_players' => 2,
            'max_players' => 2,
            'casual_enabled' => true,
            'ranked_enabled' => false,
            'client_engine_key' => 'hunters-mark',
            'game_version' => 1,
            'reward_settings' => [],
        ], $extra));
    }

    private function activeMatch(ArcadeGame $game, User $one, User $two, ArcadeMatchMode $mode): ArcadeMatch
    {
        $match = ArcadeMatch::query()->create([
            'game_id' => $game->id,
            'mode' => $mode->value,
            'status' => ArcadeMatchStatus::Active->value,
            'state' => (new HuntersMarkEngine())->initialize(),
            'version' => 0,
            'current_seat' => 1,
            'created_by' => $one->id,
            'started_at' => now(),
        ]);

        $match->players()->createMany([
            ['user_id' => $one->id, 'seat' => 1, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
            ['user_id' => $two->id, 'seat' => 2, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
        ]);

        return $match->fresh(['game', 'players.user']);
    }

    private function user(string $prefix): User
    {
        $id = bin2hex(random_bytes(5));

        return User::query()->create([
            'name' => ucfirst($prefix),
            'username' => $prefix.'_'.$id,
            'email' => $prefix.'_'.$id.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
    }
}
