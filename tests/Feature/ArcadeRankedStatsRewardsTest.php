<?php

namespace Tests\Feature;

use App\Enums\Arcade\ArcadeMatchMode;
use App\Enums\Arcade\ArcadeMatchPlayerResult;
use App\Enums\Arcade\ArcadeMatchStatus;
use App\Models\ApiAccessToken;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeMatch;
use App\Models\Arcade\ArcadeUserStat;
use App\Models\CrownTransaction;
use App\Models\CrownWallet;
use App\Models\User;
use App\Services\Arcade\ArcadeMatchResultProcessor;
use App\Services\Arcade\ArcadeMoveService;
use App\Services\Arcade\Engines\HuntWinsEngine;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArcadeRankedStatsRewardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hunt_wins_ranked_win_loss_and_move_retry_are_finalized_exactly_once(): void
    {
        $game = $this->game('hunt-wins', ['ranked_enabled' => true, 'client_engine_key' => 'hunt-wins']);
        $winner = $this->user('winner');
        $loser = $this->user('loser');
        $match = $this->activeHuntWinsMatch($game, $winner, $loser, ArcadeMatchMode::Ranked);
        $service = app(ArcadeMoveService::class);

        $moves = [
            [$winner, 0], [$loser, 6], [$winner, 1], [$loser, 6],
            [$winner, 2], [$loser, 5], [$winner, 3],
        ];

        foreach ($moves as $index => [$actor, $column]) {
            $match = $service->move($match, $actor, 'move-' . $index, ['column' => $column]);
        }

        $this->assertSame(ArcadeMatchStatus::Finished, $match->status);
        $this->assertStats($winner, $game, ArcadeMatchMode::Ranked, 1, 1, 0, 0);
        $this->assertStats($loser, $game, ArcadeMatchMode::Ranked, 1, 0, 1, 0);
        $this->assertDatabaseCount('arcade_match_finalizations', 1);

        $service->move($match, $winner, 'move-6', ['column' => 3]);

        $this->assertStats($winner, $game, ArcadeMatchMode::Ranked, 1, 1, 0, 0);
        $this->assertStats($loser, $game, ArcadeMatchMode::Ranked, 1, 0, 1, 0);
        $this->assertDatabaseCount('arcade_match_finalizations', 1);
    }

    public function test_ranked_draw_is_counted_once_for_both_players(): void
    {
        [$match, $one, $two, $game] = $this->finishedMatch(ArcadeMatchMode::Ranked, ArcadeMatchPlayerResult::Draw, ArcadeMatchPlayerResult::Draw);
        $processor = app(ArcadeMatchResultProcessor::class);

        $this->assertTrue($processor->process($match));
        $this->assertFalse($processor->process($match));

        $this->assertStats($one, $game, ArcadeMatchMode::Ranked, 1, 0, 0, 1);
        $this->assertStats($two, $game, ArcadeMatchMode::Ranked, 1, 0, 0, 1);
    }

    public function test_casual_stats_are_separate_and_do_not_create_ranked_rewards(): void
    {
        $game = $this->game('casual-game', [
            'reward_settings' => [
                'ranked_reward_enabled' => true,
                'ranked_win_reward' => 7,
                'ranked_draw_reward' => 3,
                'ranked_loss_reward' => 1,
            ],
        ]);
        [$match, $winner, $loser] = $this->finishedMatch(ArcadeMatchMode::Casual, ArcadeMatchPlayerResult::Win, ArcadeMatchPlayerResult::Loss, $game);

        app(ArcadeMatchResultProcessor::class)->process($match);

        $this->assertStats($winner, $game, ArcadeMatchMode::Casual, 1, 1, 0, 0);
        $this->assertStats($loser, $game, ArcadeMatchMode::Casual, 1, 0, 1, 0);
        $this->assertDatabaseMissing('arcade_user_stats', ['user_id' => $winner->id, 'game_id' => $game->id, 'mode' => 'ranked']);
        $this->assertDatabaseCount('arcade_match_rewards', 0);
        $this->assertDatabaseCount('crown_transactions', 0);
    }

    public function test_unfinished_match_does_not_create_stats_or_rewards(): void
    {
        $game = $this->game('unfinished', ['ranked_enabled' => true]);
        $one = $this->user('one');
        $two = $this->user('two');
        $match = ArcadeMatch::query()->create([
            'game_id' => $game->id,
            'mode' => ArcadeMatchMode::Ranked->value,
            'status' => ArcadeMatchStatus::Active->value,
            'state' => (new HuntWinsEngine())->initialize(),
            'version' => 0,
            'current_seat' => 1,
            'created_by' => $one->id,
            'started_at' => now(),
        ]);
        $this->player($match, $one, 1, null);
        $this->player($match, $two, 2, null);

        $this->assertFalse(app(ArcadeMatchResultProcessor::class)->process($match));
        $this->assertDatabaseCount('arcade_user_stats', 0);
        $this->assertDatabaseCount('arcade_match_finalizations', 0);
        $this->assertDatabaseCount('arcade_match_rewards', 0);
    }

    public function test_database_uniques_backstop_competing_finalization_and_reward_attempts(): void
    {
        [$match, $one] = $this->finishedMatch(ArcadeMatchMode::Ranked, ArcadeMatchPlayerResult::Win, ArcadeMatchPlayerResult::Loss);
        app(ArcadeMatchResultProcessor::class)->process($match);

        try {
            DB::table('arcade_match_finalizations')->insert([
                'match_id' => $match->id,
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Duplicate match finalization was accepted.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        DB::table('arcade_match_rewards')->insert([
            'match_id' => $match->id,
            'user_id' => $one->id,
            'reward_type' => 'win',
            'amount' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            DB::table('arcade_match_rewards')->insert([
                'match_id' => $match->id,
                'user_id' => $one->id,
                'reward_type' => 'win',
                'amount' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Duplicate match/user/reward was accepted.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    public function test_ranked_leaderboard_is_public_sorted_and_isolated_by_game(): void
    {
        $game = $this->game('ranked-board', ['ranked_enabled' => true]);
        $otherGame = $this->game('other-board', ['ranked_enabled' => true]);
        $a = $this->user('a');
        $b = $this->user('b');
        $c = $this->user('c');
        $d = $this->user('d');

        $this->stat($a, $game, ArcadeMatchMode::Ranked, 10, 5, 5, 0);
        $this->stat($b, $game, ArcadeMatchMode::Ranked, 8, 5, 3, 0);
        $this->stat($c, $game, ArcadeMatchMode::Ranked, 5, 4, 1, 0);
        $this->stat($d, $game, ArcadeMatchMode::Casual, 1, 99, 0, 0);
        $this->stat($d, $otherGame, ArcadeMatchMode::Ranked, 1, 99, 0, 0);

        $response = $this->getJson('/api/v1/arcade/games/ranked-board/leaderboard')->assertOk();
        $response->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.rank', 1)
            ->assertJsonPath('data.0.user.username', $b->username)
            ->assertJsonPath('data.0.wins', 5)
            ->assertJsonPath('data.0.win_rate', 62.5)
            ->assertJsonPath('data.1.user.username', $a->username)
            ->assertJsonPath('data.1.win_rate', 50.0)
            ->assertJsonPath('data.2.user.username', $c->username)
            ->assertJsonPath('meta.mode', 'ranked');
        $response->assertJsonMissing(['username' => $d->username]);
    }

    public function test_leaderboard_uses_deterministic_user_id_tie_breaker_and_disabled_ranked_is_not_public(): void
    {
        $game = $this->game('ties', ['ranked_enabled' => true]);
        $first = $this->user('first');
        $second = $this->user('second');
        $this->stat($second, $game, ArcadeMatchMode::Ranked, 10, 5, 5, 0);
        $this->stat($first, $game, ArcadeMatchMode::Ranked, 10, 5, 5, 0);

        $expected = collect([$first, $second])->sortBy('id')->values();
        $this->getJson('/api/v1/arcade/games/ties/leaderboard')->assertOk()
            ->assertJsonPath('data.0.user.id', $expected[0]->id)
            ->assertJsonPath('data.1.user.id', $expected[1]->id);

        $notRanked = $this->game('casual-only', ['ranked_enabled' => false]);
        $this->stat($first, $notRanked, ArcadeMatchMode::Casual, 1, 1, 0, 0);
        $this->getJson('/api/v1/arcade/games/casual-only/leaderboard')->assertNotFound();
    }

    public function test_personal_stats_returns_ranked_and_casual_with_zero_defaults(): void
    {
        $game = $this->game('my-stats', ['ranked_enabled' => true]);
        $user = $this->user('me');
        $this->stat($user, $game, ArcadeMatchMode::Ranked, 4, 3, 1, 0);
        $this->stat($user, $game, ArcadeMatchMode::Casual, 2, 1, 0, 1);

        $this->getAs($user, '/api/v1/arcade/games/my-stats/stats/me')->assertOk()
            ->assertJsonPath('data.ranked.matches_played', 4)
            ->assertJsonPath('data.ranked.wins', 3)
            ->assertJsonPath('data.ranked.win_rate', 75.0)
            ->assertJsonPath('data.casual.matches_played', 2)
            ->assertJsonPath('data.casual.draws', 1)
            ->assertJsonPath('data.casual.win_rate', 50.0);

        $emptyGame = $this->game('empty-stats');
        $this->getAs($user, '/api/v1/arcade/games/empty-stats/stats/me')->assertOk()
            ->assertJsonPath('data.ranked.matches_played', 0)
            ->assertJsonPath('data.casual.matches_played', 0);
    }

    public function test_ranked_win_draw_loss_rewards_use_existing_crowns_ledger_and_retry_never_double_credits(): void
    {
        config(['crowns.enabled' => true]);
        $game = $this->game('rewarded', [
            'ranked_enabled' => true,
            'reward_settings' => [
                'ranked_reward_enabled' => true,
                'ranked_win_reward' => 7,
                'ranked_draw_reward' => 3,
                'ranked_loss_reward' => 1,
            ],
        ]);
        $processor = app(ArcadeMatchResultProcessor::class);

        [$winMatch, $winner, $loser] = $this->finishedMatch(ArcadeMatchMode::Ranked, ArcadeMatchPlayerResult::Win, ArcadeMatchPlayerResult::Loss, $game);
        $processor->process($winMatch);
        $processor->process($winMatch);
        $this->assertSame(7, (int) CrownWallet::query()->where('user_id', $winner->id)->value('balance'));
        $this->assertSame(1, (int) CrownWallet::query()->where('user_id', $loser->id)->value('balance'));
        $this->assertSame(1, CrownTransaction::query()->where('user_id', $winner->id)->where('action', 'arcade_ranked_win')->count());
        $this->assertSame(1, CrownTransaction::query()->where('user_id', $loser->id)->where('action', 'arcade_ranked_loss')->count());

        [$drawMatch, $drawOne, $drawTwo] = $this->finishedMatch(ArcadeMatchMode::Ranked, ArcadeMatchPlayerResult::Draw, ArcadeMatchPlayerResult::Draw, $game);
        $processor->process($drawMatch);
        $this->assertSame(3, (int) CrownWallet::query()->where('user_id', $drawOne->id)->value('balance'));
        $this->assertSame(3, (int) CrownWallet::query()->where('user_id', $drawTwo->id)->value('balance'));
        $this->assertSame(2, CrownTransaction::query()->where('action', 'arcade_ranked_draw')->count());
    }

    public function test_disabled_ranked_rewards_create_no_wallet_credit(): void
    {
        config(['crowns.enabled' => true]);
        $game = $this->game('reward-disabled', [
            'ranked_enabled' => true,
            'reward_settings' => [
                'ranked_reward_enabled' => false,
                'ranked_win_reward' => 7,
                'ranked_draw_reward' => 3,
                'ranked_loss_reward' => 1,
            ],
        ]);
        [$match] = $this->finishedMatch(ArcadeMatchMode::Ranked, ArcadeMatchPlayerResult::Win, ArcadeMatchPlayerResult::Loss, $game);

        app(ArcadeMatchResultProcessor::class)->process($match);

        $this->assertDatabaseCount('arcade_match_rewards', 0);
        $this->assertDatabaseCount('crown_transactions', 0);
    }

    public function test_reward_settings_default_to_disabled_zero_and_reject_invalid_amounts(): void
    {
        $game = $this->game('reward-defaults');
        $this->assertSame([
            'ranked_reward_enabled' => false,
            'ranked_win_reward' => 0,
            'ranked_draw_reward' => 0,
            'ranked_loss_reward' => 0,
        ], $game->rankedRewardSettings());

        try {
            $game->update(['reward_settings' => ['ranked_reward_enabled' => true, 'ranked_win_reward' => ArcadeGame::MAX_RANKED_REWARD + 1]]);
            $this->fail('Reward above server limit was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    private function activeHuntWinsMatch(ArcadeGame $game, User $one, User $two, ArcadeMatchMode $mode): ArcadeMatch
    {
        $match = ArcadeMatch::query()->create([
            'game_id' => $game->id,
            'mode' => $mode->value,
            'status' => ArcadeMatchStatus::Active->value,
            'state' => (new HuntWinsEngine())->initialize(),
            'version' => 0,
            'current_seat' => 1,
            'created_by' => $one->id,
            'started_at' => now(),
        ]);
        $this->player($match, $one, 1, null);
        $this->player($match, $two, 2, null);

        return $match->fresh(['game', 'players.user']);
    }

    private function finishedMatch(
        ArcadeMatchMode $mode,
        ArcadeMatchPlayerResult $resultOne,
        ArcadeMatchPlayerResult $resultTwo,
        ?ArcadeGame $game = null,
    ): array {
        $game ??= $this->game('game-' . bin2hex(random_bytes(4)), ['ranked_enabled' => true]);
        $one = $this->user('one');
        $two = $this->user('two');
        $winnerSeat = $resultOne === ArcadeMatchPlayerResult::Win ? 1 : ($resultTwo === ArcadeMatchPlayerResult::Win ? 2 : null);
        $match = ArcadeMatch::query()->create([
            'game_id' => $game->id,
            'mode' => $mode->value,
            'status' => ArcadeMatchStatus::Finished->value,
            'state' => ['winner_seat' => $winnerSeat, 'draw' => $resultOne === ArcadeMatchPlayerResult::Draw],
            'version' => 1,
            'current_seat' => null,
            'winner_seat' => $winnerSeat,
            'created_by' => $one->id,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
        $this->player($match, $one, 1, $resultOne);
        $this->player($match, $two, 2, $resultTwo);

        return [$match->fresh(['game', 'players.user']), $one, $two, $game];
    }

    private function player(ArcadeMatch $match, User $user, int $seat, ?ArcadeMatchPlayerResult $result): void
    {
        $match->players()->create([
            'user_id' => $user->id,
            'seat' => $seat,
            'status' => 'joined',
            'joined_at' => now(),
            'ready_at' => now(),
            'result' => $result?->value,
        ]);
    }

    private function stat(User $user, ArcadeGame $game, ArcadeMatchMode $mode, int $matches, int $wins, int $losses, int $draws): ArcadeUserStat
    {
        return ArcadeUserStat::query()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'mode' => $mode->value,
            'matches_played' => $matches,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
        ]);
    }

    private function assertStats(User $user, ArcadeGame $game, ArcadeMatchMode $mode, int $matches, int $wins, int $losses, int $draws): void
    {
        $this->assertDatabaseHas('arcade_user_stats', [
            'user_id' => $user->id,
            'game_id' => $game->id,
            'mode' => $mode->value,
            'matches_played' => $matches,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
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
            'username' => $prefix . '_' . $id,
            'email' => $id . '@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
    }

    private function getAs(User $user, string $uri)
    {
        $token = ApiAccessToken::createForUser($user, 'Arcade stats test')['access_token'];

        return $this->withToken($token)->getJson($uri);
    }
}
