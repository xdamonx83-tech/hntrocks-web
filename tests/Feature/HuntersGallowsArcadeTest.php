<?php

namespace Tests\Feature;

use App\Enums\Arcade\ArcadeMatchMode;
use App\Enums\Arcade\ArcadeMatchStatus;
use App\Http\Resources\Api\ArcadeMatchResource;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeMatch;
use App\Models\User;
use App\Services\Arcade\ArcadeGameEngineRegistry;
use App\Services\Arcade\ArcadeMatchService;
use App\Services\Arcade\ArcadeMoveService;
use App\Services\Arcade\Engines\HuntersGallowsEngine;
use Database\Seeders\ArcadeGameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HuntersGallowsArcadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_registers_game_idempotently_and_preserves_acp_values(): void
    {
        $this->seed(ArcadeGameSeeder::class);
        $game = ArcadeGame::query()->where('key', 'hunters-gallows')->firstOrFail();
        $this->assertSame("Hunter's Gallows", $game->name_de);
        $this->assertSame('A dark word duel for two hunters.', $game->description_en);
        $this->assertSame('disabled', $game->status->value);
        $this->assertSame('native', $game->type->value);
        $this->assertSame('hunters-gallows', $game->client_engine_key);
        $this->assertSame(2, $game->min_players);
        $this->assertSame(2, $game->max_players);
        $this->assertTrue($game->casual_enabled);
        $this->assertTrue($game->ranked_enabled);
        $this->assertSame(1, $game->game_version);

        $game->update(['status' => 'active', 'sort_order' => 777, 'ranked_enabled' => false, 'reward_settings' => ['ranked_reward_enabled' => true, 'ranked_win_reward' => 9]]);
        $this->seed(ArcadeGameSeeder::class);
        $game->refresh();
        $this->assertSame('active', $game->status->value);
        $this->assertSame(777, $game->sort_order);
        $this->assertFalse($game->ranked_enabled);
        $this->assertSame(9, $game->reward_settings['ranked_win_reward']);
        $this->assertSame(1, ArcadeGame::query()->where('key', 'hunters-gallows')->count());
    }

    public function test_ready_initializes_match_and_resource_never_exposes_secret(): void
    {
        [$game, $one, $two, $match] = $this->waitingMatch();
        $this->app->instance(HuntersGallowsEngine::class, new HuntersGallowsEngine('WINFIELD', 'weapons'));
        $service = app(ArcadeMatchService::class);
        $service->ready($match, $one);
        $match = $service->ready($match->fresh(), $two);

        $this->assertSame(ArcadeMatchStatus::Active, $match->status);
        $this->assertSame('WINFIELD', $match->state['secret_word']);
        $this->assertInstanceOf(HuntersGallowsEngine::class, app(ArcadeGameEngineRegistry::class)->resolve($game));

        $request = Request::create('/api/v1/arcade/matches/'.$match->id);
        $request->setUserResolver(fn () => $one);
        $payload = (new ArcadeMatchResource($match))->toArray($request);
        $encoded = json_encode($payload);
        $this->assertSame(array_fill(0, 8, '*'), $payload['state']['masked_word']);
        $this->assertStringNotContainsString('WINFIELD', $encoded);
        $this->assertStringNotContainsString('secret_word', $encoded);
    }

    public function test_generic_move_pipeline_accepts_guess_rejects_invalid_and_finishes_solve(): void
    {
        [$game, $one, $two, $match] = $this->activeMatch();
        $service = app(ArcadeMoveService::class);
        $match = $service->move($match, $one, 'guess-1', ['action' => 'guess_letter', 'letter' => 'i']);
        $this->assertSame(2, $match->state['players']['1']['score']);
        $this->assertSame(2, $match->current_seat);
        $this->assertDatabaseHas('arcade_match_moves', ['match_id' => $match->id, 'move_type' => 'gallows_move', 'client_move_id' => 'guess-1']);

        try {
            $service->move($match, $two, 'invalid-1', ['action' => 'guess_letter', 'letter' => 'I']);
            $this->fail('Duplicate letter accepted through generic pipeline.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('arcade_match_moves', ['client_move_id' => 'invalid-1']);
        }

        $match = $service->move($match, $two, 'solve-1', ['action' => 'solve', 'word' => ' winfield ']);
        $this->assertSame(ArcadeMatchStatus::Finished, $match->status);
        $this->assertSame(2, $match->winner_seat);
        $this->assertNull($match->current_seat);
        $this->assertDatabaseHas('arcade_user_stats', ['user_id' => $two->id, 'game_id' => $game->id, 'wins' => 1]);
        $this->assertDatabaseHas('arcade_user_stats', ['user_id' => $one->id, 'game_id' => $game->id, 'losses' => 1]);
    }

    public function test_generic_pipeline_finalizes_draw_and_forfeit_still_works(): void
    {
        [, $one, $two, $match] = $this->activeMatch();
        $state = $match->state;
        $state['revealed_positions'] = array_fill(0, 8, true);
        $state['revealed_positions'][7] = false;
        $state['players'] = ['1' => ['score' => 1, 'mistakes' => 1], '2' => ['score' => 2, 'mistakes' => 1]];
        $match->update(['state' => $state]);
        $match = app(ArcadeMoveService::class)->move($match, $one, 'draw-1', ['action' => 'guess_letter', 'letter' => 'D']);
        $this->assertTrue($match->state['draw']);
        $this->assertSame('draw', $match->players->firstWhere('seat', 1)->result->value);

        [, $one, , $forfeitMatch] = $this->activeMatch();
        $forfeited = app(ArcadeMatchService::class)->forfeit($forfeitMatch, $one);
        $this->assertSame(2, $forfeited->winner_seat);
        $this->assertSame('forfeit', $forfeited->state['termination']['type']);
    }

    private function activeMatch(): array
    {
        $game = $this->game();
        $one = $this->user('gallows_one');
        $two = $this->user('gallows_two');
        $this->app->instance(HuntersGallowsEngine::class, new HuntersGallowsEngine('WINFIELD', 'weapons'));
        $match = ArcadeMatch::query()->create(['game_id' => $game->id, 'mode' => ArcadeMatchMode::Casual->value, 'status' => 'active', 'state' => app(HuntersGallowsEngine::class)->initialize(), 'version' => 0, 'current_seat' => 1, 'created_by' => $one->id, 'started_at' => now()]);
        $match->players()->createMany([
            ['user_id' => $one->id, 'seat' => 1, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
            ['user_id' => $two->id, 'seat' => 2, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
        ]);
        return [$game, $one, $two, $match->fresh(['game', 'players.user'])];
    }

    private function waitingMatch(): array
    {
        $game = $this->game();
        $one = $this->user('ready_one');
        $two = $this->user('ready_two');
        $match = ArcadeMatch::query()->create(['game_id' => $game->id, 'mode' => 'casual', 'status' => 'waiting_ready', 'state' => [], 'version' => 0, 'created_by' => $one->id]);
        $match->players()->createMany([
            ['user_id' => $one->id, 'seat' => 1, 'status' => 'joined', 'joined_at' => now()],
            ['user_id' => $two->id, 'seat' => 2, 'status' => 'joined', 'joined_at' => now()],
        ]);
        return [$game, $one, $two, $match];
    }

    private function game(): ArcadeGame
    {
        return ArcadeGame::query()->create(['key' => 'hunters-gallows', 'name_de' => "Hunter's Gallows", 'name_en' => "Hunter's Gallows", 'type' => 'native', 'status' => 'active', 'sort_order' => 40, 'min_players' => 2, 'max_players' => 2, 'casual_enabled' => true, 'ranked_enabled' => true, 'client_engine_key' => 'hunters-gallows', 'game_version' => 1, 'reward_settings' => []]);
    }

    private function user(string $prefix): User
    {
        $id = bin2hex(random_bytes(5));
        return User::query()->create(['name' => ucfirst($prefix), 'username' => $prefix.'_'.$id, 'email' => $prefix.'_'.$id.'@example.test', 'password' => 'password', 'status' => 'active']);
    }
}
