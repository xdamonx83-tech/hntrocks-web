<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeMatch;
use App\Models\CrownTransaction;
use App\Models\CrownWallet;
use App\Models\User;
use App\Services\Arcade\Engines\HuntWinsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArcadeMatchForfeitTest extends TestCase
{
    use RefreshDatabase;

    public function test_waiting_match_can_be_cancelled_without_stats_or_rewards(): void
    {
        [$one, $two, $match] = $this->waitingMatch();

        $response = $this->forfeit($one, $match)->assertOk()->json('data');

        $this->assertSame('cancelled', $response['status']);
        $this->assertNull($response['winner_seat']);
        $this->assertSame('cancelled', $response['state']['termination']['type']);
        $this->assertSame(1, $response['state']['termination']['by_seat']);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'user_id' => $one->id, 'result' => 'cancelled']);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'user_id' => $two->id, 'result' => 'cancelled']);
        $this->assertDatabaseCount('arcade_match_finalizations', 0);
        $this->assertDatabaseCount('arcade_user_stats', 0);
        $this->assertDatabaseCount('arcade_match_rewards', 0);
    }

    public function test_active_ranked_forfeit_awards_opponent_win_but_never_loss_reward_to_forfeiter(): void
    {
        config(['crowns.enabled' => true]);
        [$one, $two, $match] = $this->activeRankedMatch();

        $response = $this->forfeit($one, $match)->assertOk()->json('data');

        $this->assertSame('finished', $response['status']);
        $this->assertSame(2, $response['winner_seat']);
        $this->assertSame('forfeit', $response['state']['termination']['type']);
        $this->assertSame(1, $response['state']['termination']['forfeited_seat']);
        $this->assertSame(2, $response['state']['termination']['winner_seat']);
        $this->assertNull($response['current_seat']);

        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'user_id' => $one->id, 'result' => 'loss']);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'user_id' => $two->id, 'result' => 'win']);
        $this->assertDatabaseHas('arcade_user_stats', ['user_id' => $one->id, 'game_id' => $match->game_id, 'mode' => 'ranked', 'matches_played' => 1, 'losses' => 1]);
        $this->assertDatabaseHas('arcade_user_stats', ['user_id' => $two->id, 'game_id' => $match->game_id, 'mode' => 'ranked', 'matches_played' => 1, 'wins' => 1]);
        $this->assertDatabaseCount('arcade_match_finalizations', 1);

        $this->assertSame(10, (int) CrownWallet::query()->where('user_id', $two->id)->value('balance'));
        $this->assertSame(0, (int) (CrownWallet::query()->where('user_id', $one->id)->value('balance') ?? 0));
        $this->assertSame(1, CrownTransaction::query()->where('user_id', $two->id)->where('action', 'arcade_ranked_win')->count());
        $this->assertSame(0, CrownTransaction::query()->where('user_id', $one->id)->where('action', 'arcade_ranked_loss')->count());
        $this->assertDatabaseMissing('arcade_match_rewards', ['match_id' => $match->id, 'user_id' => $one->id, 'reward_type' => 'loss']);
    }

    public function test_repeated_forfeit_is_idempotent_and_non_participant_is_denied(): void
    {
        [$one, $two, $match] = $this->activeRankedMatch();
        $outsider = $this->user('outsider');

        $this->forfeit($outsider, $match)->assertForbidden();
        $first = $this->forfeit($one, $match)->assertOk()->json('data');
        $second = $this->forfeit($one, $match)->assertOk()->json('data');

        $this->assertSame($first['version'], $second['version']);
        $this->assertSame($first['winner_seat'], $second['winner_seat']);
        $this->assertDatabaseCount('arcade_match_finalizations', 1);
    }

    private function waitingMatch(): array
    {
        $one = $this->user('waiting_one');
        $two = $this->user('waiting_two');
        $game = $this->game('waiting-game');
        $match = ArcadeMatch::query()->create([
            'game_id' => $game->id,
            'mode' => 'casual',
            'status' => 'waiting_ready',
            'state' => [],
            'version' => 0,
            'current_seat' => null,
            'created_by' => $one->id,
        ]);
        $this->players($match, $one, $two, 'joined');

        return [$one, $two, $match];
    }

    private function activeRankedMatch(): array
    {
        $one = $this->user('active_one');
        $two = $this->user('active_two');
        $game = $this->game('hunt-wins', [
            'ranked_enabled' => true,
            'client_engine_key' => 'hunt-wins',
            'reward_settings' => [
                'ranked_reward_enabled' => true,
                'ranked_win_reward' => 10,
                'ranked_draw_reward' => 5,
                'ranked_loss_reward' => 2,
            ],
        ]);
        $match = ArcadeMatch::query()->create([
            'game_id' => $game->id,
            'mode' => 'ranked',
            'status' => 'active',
            'state' => (new HuntWinsEngine())->initialize(),
            'version' => 3,
            'current_seat' => 1,
            'created_by' => $one->id,
            'started_at' => now()->subMinute(),
        ]);
        $this->players($match, $one, $two, 'ready');

        return [$one, $two, $match];
    }

    private function players(ArcadeMatch $match, User $one, User $two, string $status): void
    {
        $match->players()->createMany([
            ['user_id' => $one->id, 'seat' => 1, 'status' => $status, 'joined_at' => now(), 'ready_at' => $status === 'ready' ? now() : null],
            ['user_id' => $two->id, 'seat' => 2, 'status' => $status, 'joined_at' => now(), 'ready_at' => $status === 'ready' ? now() : null],
        ]);
    }

    private function game(string $key, array $extra = []): ArcadeGame
    {
        return ArcadeGame::query()->create(array_merge([
            'key' => $key,
            'name_de' => 'Spiel',
            'name_en' => 'Game',
            'type' => 'native',
            'status' => 'active',
            'sort_order' => 0,
            'min_players' => 2,
            'max_players' => 2,
            'casual_enabled' => true,
            'ranked_enabled' => false,
            'game_version' => 1,
        ], $extra));
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

    private function forfeit(User $user, ArcadeMatch $match)
    {
        $token = ApiAccessToken::createForUser($user, 'Arcade forfeit test')['access_token'];

        return $this->withToken($token)->postJson("/api/v1/arcade/matches/{$match->id}/forfeit");
    }
}
