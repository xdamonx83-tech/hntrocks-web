<?php

namespace Tests\Feature;

use App\Events\ArcadeMatchUpdated;
use App\Models\ApiAccessToken;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeMatch;
use App\Models\User;
use App\Services\Arcade\Engines\MemoryEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArcadeMemoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_get_and_index_responses_never_expose_hidden_motif_ids(): void
    {
        [$one, $two, $match] = $this->waitingMatch();
        $this->ready($one, $match)->assertOk();
        $ready = $this->ready($two, $match)->assertOk()->json('data');

        $this->assertSame('active', $ready['status']);
        $this->assertCount(24, $ready['state']['cards']);
        $this->assertHiddenCardsAreSecret($ready['state']['cards']);

        $show = $this->getAs($one, "/api/v1/arcade/matches/{$match->id}")->assertOk()->json('data');
        $this->assertHiddenCardsAreSecret($show['state']['cards']);

        $index = $this->getAs($one, '/api/v1/arcade/matches')->assertOk()->json('data.0');
        $this->assertSame($match->id, $index['id']);
        $this->assertHiddenCardsAreSecret($index['state']['cards']);
    }

    public function test_move_response_exposes_revealed_and_matched_motifs_but_keeps_other_cards_secret(): void
    {
        [$one, $two, $match] = $this->activeMatch();
        [$first, $second] = $this->pairIndices($match->fresh());

        $firstResponse = $this->move($one, $match, $first, 'memory-reveal-1')->assertOk()->json('data');
        $this->assertSame('revealed', $firstResponse['state']['cards'][$first]['status']);
        $this->assertArrayHasKey('motif_id', $firstResponse['state']['cards'][$first]);
        foreach ($firstResponse['state']['cards'] as $index => $card) {
            if ($index !== $first) {
                $this->assertSame('hidden', $card['status']);
                $this->assertArrayNotHasKey('motif_id', $card);
            }
        }

        $matched = $this->move($one, $match, $second, 'memory-reveal-2')->assertOk()->json('data');
        $this->assertSame('matched', $matched['state']['cards'][$first]['status']);
        $this->assertSame('matched', $matched['state']['cards'][$second]['status']);
        $this->assertArrayHasKey('motif_id', $matched['state']['cards'][$first]);
        $this->assertArrayHasKey('motif_id', $matched['state']['cards'][$second]);
        foreach ($matched['state']['cards'] as $index => $card) {
            if (! in_array($index, [$first, $second], true)) {
                $this->assertSame('hidden', $card['status']);
                $this->assertArrayNotHasKey('motif_id', $card);
            }
        }
    }

    public function test_generic_memory_move_is_idempotent_and_uses_reveal_move_type(): void
    {
        [$one, $two, $match] = $this->activeMatch();
        [$first, $second] = $this->pairIndices($match->fresh());

        $firstResponse = $this->move($one, $match, $first, 'same-memory-id')->assertOk()->json('data');
        $retry = $this->move($one, $match, $first, 'same-memory-id')->assertOk()->json('data');

        $this->assertSame($firstResponse['version'], $retry['version']);
        $this->assertSame($firstResponse['state'], $retry['state']);
        $this->assertDatabaseCount('arcade_match_moves', 1);
        $this->assertDatabaseHas('arcade_match_moves', [
            'match_id' => $match->id,
            'client_move_id' => 'same-memory-id',
            'move_type' => 'reveal',
        ]);

        $this->move($one, $match, $second, 'same-memory-id')->assertConflict();
        $this->assertDatabaseCount('arcade_match_moves', 1);
    }

    public function test_memory_move_rejects_manipulated_fields_and_legacy_hunt_wins_contract_stays_usable(): void
    {
        [$one, $two, $match] = $this->activeMatch();
        $this->withToken($this->token($one))->postJson("/api/v1/arcade/matches/{$match->id}/moves", [
            'client_move_id' => 'tampered-memory',
            'payload' => [
                'action' => 'reveal',
                'card_index' => 0,
                'motif_id' => 'memory_01',
            ],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('arcade_match_moves', 0);

        [$huntOne, $huntTwo, $hunt] = $this->activeHuntWinsMatch();
        $this->withToken($this->token($huntOne))->postJson("/api/v1/arcade/matches/{$hunt->id}/moves", [
            'client_move_id' => 'legacy-drop',
            'column' => 3,
        ])->assertOk()
            ->assertJsonPath('data.state.board.5.3', 1)
            ->assertJsonPath('data.current_seat', 2);
        $this->assertDatabaseHas('arcade_match_moves', ['match_id' => $hunt->id, 'move_type' => 'drop']);
    }

    public function test_realtime_update_is_state_free_and_cannot_leak_memory_motifs(): void
    {
        [$one, $two, $match] = $this->activeMatch();
        $internal = $match->fresh();
        $this->assertStringContainsString('memory_', json_encode($internal->state, JSON_THROW_ON_ERROR));

        $payload = (new ArcadeMatchUpdated($internal))->broadcastWith();
        $this->assertArrayNotHasKey('state', $payload);
        $this->assertSame($internal->id, $payload['match_id']);
        $this->assertSame($internal->version, $payload['version']);
        $this->assertStringNotContainsString('memory_', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function test_terminal_memory_results_finalize_ranked_stats_for_seat_one_win_seat_two_win_and_draw(): void
    {
        $seatOne = $this->nearTerminalRankedMatch([1 => 6, 2 => 5], 1);
        $this->finishLastPair($seatOne['match'], $seatOne['one'], 1);
        $this->assertFinishedResultsAndStats($seatOne['match'], $seatOne['one'], $seatOne['two'], 1, 'win', 'loss');

        $seatTwo = $this->nearTerminalRankedMatch([1 => 5, 2 => 6], 2);
        $this->finishLastPair($seatTwo['match'], $seatTwo['two'], 2);
        $this->assertFinishedResultsAndStats($seatTwo['match'], $seatTwo['one'], $seatTwo['two'], 2, 'loss', 'win');

        $draw = $this->nearTerminalRankedMatch([1 => 5, 2 => 6], 1);
        $this->finishLastPair($draw['match'], $draw['one'], 1);
        $draw['match']->refresh();
        $this->assertSame('finished', $draw['match']->status->value);
        $this->assertNull($draw['match']->winner_seat);
        $this->assertTrue($draw['match']->state['draw']);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $draw['match']->id, 'seat' => 1, 'result' => 'draw']);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $draw['match']->id, 'seat' => 2, 'result' => 'draw']);
        $this->assertDatabaseHas('arcade_user_stats', ['user_id' => $draw['one']->id, 'game_id' => $draw['match']->game_id, 'mode' => 'ranked', 'matches_played' => 1, 'draws' => 1]);
        $this->assertDatabaseHas('arcade_user_stats', ['user_id' => $draw['two']->id, 'game_id' => $draw['match']->game_id, 'mode' => 'ranked', 'matches_played' => 1, 'draws' => 1]);
        $this->assertDatabaseCount('arcade_match_finalizations', 3);
    }

    private function waitingMatch(): array
    {
        $one = $this->user('memory_one');
        $two = $this->user('memory_two');
        $game = $this->memoryGame();
        $match = ArcadeMatch::create([
            'game_id' => $game->id,
            'mode' => 'casual',
            'status' => 'waiting_ready',
            'state' => [],
            'version' => 0,
            'current_seat' => null,
            'created_by' => $one->id,
        ]);
        $match->players()->createMany([
            ['user_id' => $one->id, 'seat' => 1, 'status' => 'joined', 'joined_at' => now()],
            ['user_id' => $two->id, 'seat' => 2, 'status' => 'joined', 'joined_at' => now()],
        ]);

        return [$one, $two, $match];
    }

    private function activeMatch(): array
    {
        [$one, $two, $match] = $this->waitingMatch();
        $this->ready($one, $match)->assertOk();
        $this->ready($two, $match)->assertOk();
        return [$one, $two, $match->fresh()];
    }

    private function activeHuntWinsMatch(): array
    {
        $one = $this->user('hunt_one');
        $two = $this->user('hunt_two');
        $game = ArcadeGame::create([
            'key' => 'hunt-wins', 'name_de' => 'Hunt gewinnt', 'name_en' => 'Hunt Wins', 'type' => 'native', 'status' => 'active',
            'min_players' => 2, 'max_players' => 2, 'casual_enabled' => true, 'ranked_enabled' => true, 'client_engine_key' => 'hunt-wins',
        ]);
        $state = app(\App\Services\Arcade\Engines\HuntWinsEngine::class)->initialize();
        $match = ArcadeMatch::create([
            'game_id' => $game->id, 'mode' => 'casual', 'status' => 'active', 'state' => $state, 'version' => 1,
            'current_seat' => 1, 'created_by' => $one->id, 'started_at' => now(),
        ]);
        $match->players()->createMany([
            ['user_id' => $one->id, 'seat' => 1, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
            ['user_id' => $two->id, 'seat' => 2, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
        ]);
        return [$one, $two, $match];
    }

    private function nearTerminalRankedMatch(array $scores, int $turnSeat): array
    {
        $one = $this->user('ranked_one');
        $two = $this->user('ranked_two');
        $game = $this->memoryGame();
        $state = (new MemoryEngine(static fn (int $min, int $max): int => $min))->initialize();
        foreach (range(0, 21) as $index) {
            $state['cards'][$index]['status'] = 'matched';
        }
        $state['cards'][22]['motif_id'] = 'memory_24';
        $state['cards'][23]['motif_id'] = 'memory_24';
        $state['cards'][22]['status'] = 'hidden';
        $state['cards'][23]['status'] = 'hidden';
        $state['matched_pairs'] = 11;
        $state['scores'] = $scores;
        $state['turn_seat'] = $turnSeat;
        $state['revealed_indices'] = [];
        $state['pending_mismatch'] = null;
        $state['winner_seat'] = null;
        $state['draw'] = false;

        $match = ArcadeMatch::create([
            'game_id' => $game->id, 'mode' => 'ranked', 'status' => 'active', 'state' => $state, 'version' => 10,
            'current_seat' => $turnSeat, 'created_by' => $one->id, 'started_at' => now(),
        ]);
        $match->players()->createMany([
            ['user_id' => $one->id, 'seat' => 1, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
            ['user_id' => $two->id, 'seat' => 2, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
        ]);

        return compact('match', 'one', 'two');
    }

    private function finishLastPair(ArcadeMatch $match, User $actor, int $seat): void
    {
        $this->move($actor, $match, 22, 'terminal-'.$match->id.'-1')->assertOk();
        $this->move($actor, $match, 23, 'terminal-'.$match->id.'-2')->assertOk();
        $match->refresh();
        $this->assertSame(12, $match->state['matched_pairs']);
        $this->assertNull($match->current_seat);
    }

    private function assertFinishedResultsAndStats(ArcadeMatch $match, User $one, User $two, int $winnerSeat, string $oneResult, string $twoResult): void
    {
        $match->refresh();
        $this->assertSame('finished', $match->status->value);
        $this->assertSame($winnerSeat, $match->winner_seat);
        $this->assertFalse($match->state['draw']);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'seat' => 1, 'result' => $oneResult]);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'seat' => 2, 'result' => $twoResult]);
        $this->assertDatabaseHas('arcade_user_stats', ['user_id' => $one->id, 'game_id' => $match->game_id, 'mode' => 'ranked', 'matches_played' => 1, $oneResult === 'win' ? 'wins' : 'losses' => 1]);
        $this->assertDatabaseHas('arcade_user_stats', ['user_id' => $two->id, 'game_id' => $match->game_id, 'mode' => 'ranked', 'matches_played' => 1, $twoResult === 'win' ? 'wins' : 'losses' => 1]);
    }

    private function pairIndices(ArcadeMatch $match): array
    {
        $groups = [];
        foreach ($match->state['cards'] as $card) {
            $groups[$card['motif_id']][] = $card['index'];
        }
        return array_values($groups)[0];
    }

    private function assertHiddenCardsAreSecret(array $cards): void
    {
        foreach ($cards as $card) {
            if (($card['status'] ?? null) === 'hidden') {
                $this->assertArrayNotHasKey('motif_id', $card);
            }
        }
        $this->assertStringNotContainsString('memory_', json_encode($cards, JSON_THROW_ON_ERROR));
    }

    private function memoryGame(): ArcadeGame
    {
        return ArcadeGame::firstOrCreate(
            ['key' => 'hunt-memory'],
            [
                'name_de' => 'Memory', 'name_en' => 'Memory', 'type' => 'native', 'status' => 'active',
                'min_players' => 2, 'max_players' => 2, 'casual_enabled' => true, 'ranked_enabled' => true, 'client_engine_key' => 'hunt-memory',
            ],
        );
    }

    private function ready(User $user, ArcadeMatch $match) { return $this->withToken($this->token($user))->postJson("/api/v1/arcade/matches/{$match->id}/ready"); }
    private function move(User $user, ArcadeMatch $match, int $cardIndex, string $id) { return $this->withToken($this->token($user))->postJson("/api/v1/arcade/matches/{$match->id}/moves", ['client_move_id' => $id, 'payload' => ['action' => 'reveal', 'card_index' => $cardIndex]]); }
    private function getAs(User $user, string $uri) { return $this->withToken($this->token($user))->getJson($uri); }
    private function user(string $prefix): User { $id = bin2hex(random_bytes(5)); return User::create(['name' => 'Hunter', 'username' => $prefix.'_'.$id, 'email' => $prefix.'_'.$id.'@example.test', 'password' => 'password', 'status' => 'active']); }
    private function token(User $user): string { return ApiAccessToken::createForUser($user, 'Memory test')['access_token']; }
}