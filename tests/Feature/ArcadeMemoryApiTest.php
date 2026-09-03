<?php

namespace Tests\Feature;

use App\Events\ArcadeMatchUpdated;
use App\Models\ApiAccessToken;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeMatch;
use App\Models\User;
use App\Services\Arcade\ArcadeGameEngineRegistry;
use App\Services\Arcade\Engines\HuntWinsEngine;
use App\Services\Arcade\Engines\MemoryEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArcadeMemoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_resolves_hunt_memory_engine(): void
    {
        $game = $this->memoryGame();

        $this->assertInstanceOf(MemoryEngine::class, app(ArcadeGameEngineRegistry::class)->resolve($game));
    }

    public function test_ready_show_and_index_never_expose_hidden_motif_ids(): void
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

    public function test_first_reveal_and_matching_pair_are_authoritative_and_keep_hidden_cards_secret(): void
    {
        [$one, $two, $match] = $this->activeMatch();
        [$first, $second] = $this->pairIndices($match->fresh());

        $firstResponse = $this->move($one, $match, $first, 'memory-reveal-1')->assertOk()->json('data');
        $this->assertSame('revealed', $firstResponse['state']['cards'][$first]['status']);
        $this->assertArrayHasKey('motif_id', $firstResponse['state']['cards'][$first]);
        $this->assertSame(1, $firstResponse['current_seat']);
        $this->assertSame(0, $firstResponse['state']['scores'][0]);

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
        $this->assertSame(1, $matched['state']['scores'][0]);
        $this->assertSame(1, $matched['state']['matched_pairs']);
        $this->assertSame(1, $matched['current_seat']);

        foreach ($matched['state']['cards'] as $index => $card) {
            if (! in_array($index, [$first, $second], true)) {
                $this->assertSame('hidden', $card['status']);
                $this->assertArrayNotHasKey('motif_id', $card);
            }
        }

        $this->move($one, $match, $first, 'memory-reveal-matched')->assertUnprocessable();
    }

    public function test_mismatch_switches_turn_and_next_reveal_hides_previous_pair(): void
    {
        [$one, $two, $match] = $this->activeMatch();
        [$first, $second, $next] = $this->mismatchIndices($match->fresh());

        $this->move($one, $match, $first, 'mismatch-1')->assertOk();
        $mismatch = $this->move($one, $match, $second, 'mismatch-2')->assertOk()->json('data');
        $this->assertSame('revealed', $mismatch['state']['cards'][$first]['status']);
        $this->assertSame('revealed', $mismatch['state']['cards'][$second]['status']);
        $this->assertSame([$first, $second], $mismatch['state']['pending_mismatch']);
        $this->assertSame(2, $mismatch['current_seat']);

        $nextResponse = $this->move($two, $match, $next, 'mismatch-3')->assertOk()->json('data');
        $this->assertSame('hidden', $nextResponse['state']['cards'][$first]['status']);
        $this->assertSame('hidden', $nextResponse['state']['cards'][$second]['status']);
        $this->assertArrayNotHasKey('motif_id', $nextResponse['state']['cards'][$first]);
        $this->assertArrayNotHasKey('motif_id', $nextResponse['state']['cards'][$second]);
        $this->assertSame('revealed', $nextResponse['state']['cards'][$next]['status']);
        $this->assertSame([$next], $nextResponse['state']['revealed_indices']);
        $this->assertNull($nextResponse['state']['pending_mismatch']);
        $this->assertSame(2, $nextResponse['current_seat']);
    }

    public function test_generic_memory_move_is_idempotent_and_rejects_conflicting_retry_wrong_turn_invalid_index_and_tampering(): void
    {
        [$one, $two, $match] = $this->activeMatch();
        [$first, $second] = $this->pairIndices($match->fresh());

        $firstResponse = $this->move($one, $match, $first, 'same-memory-id')->assertOk()->json('data');
        $retry = $this->move($one, $match, $first, 'same-memory-id')->assertOk()->json('data');
        $this->assertSame($firstResponse['version'], $retry['version']);
        $this->assertSame($firstResponse['state'], $retry['state']);
        $this->assertDatabaseCount('arcade_match_moves', 1);
        $this->assertDatabaseHas('arcade_match_moves', ['match_id' => $match->id, 'client_move_id' => 'same-memory-id', 'move_type' => 'reveal']);

        $this->move($one, $match, $second, 'same-memory-id')->assertConflict();
        $this->move($two, $match, $second, 'wrong-turn')->assertConflict();
        $this->move($one, $match, 99, 'invalid-index')->assertUnprocessable();

        $this->withToken($this->token($one))->postJson("/api/v1/arcade/matches/{$match->id}/moves", [
            'client_move_id' => 'tampered-memory',
            'payload' => ['action' => 'reveal', 'card_index' => $second, 'motif_id' => 'memory_01'],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('arcade_match_moves', 1);
    }

    public function test_legacy_hunt_wins_move_and_realtime_state_remain_usable(): void
    {
        [$one, $two, $hunt] = $this->activeHuntWinsMatch();
        $response = $this->withToken($this->token($one))->postJson("/api/v1/arcade/matches/{$hunt->id}/moves", [
            'client_move_id' => 'legacy-drop',
            'column' => 3,
        ])->assertOk()->json('data');

        $this->assertSame(1, $response['state']['board'][5][3]);
        $this->assertSame(2, $response['current_seat']);
        $this->assertDatabaseHas('arcade_match_moves', ['match_id' => $hunt->id, 'move_type' => 'drop']);

        $internal = $hunt->fresh(['game']);
        $payload = (new ArcadeMatchUpdated($internal))->broadcastWith();
        $this->assertSame($internal->state, $payload['state']);
    }

    public function test_memory_realtime_projects_state_without_hidden_motif_leaks(): void
    {
        [$one, $two, $match] = $this->activeMatch();
        $internal = $match->fresh(['game']);
        $this->assertStringContainsString('memory_', json_encode($internal->state, JSON_THROW_ON_ERROR));

        $payload = (new ArcadeMatchUpdated($internal))->broadcastWith();
        $this->assertArrayHasKey('state', $payload);
        $this->assertSame($internal->id, $payload['match_id']);
        $this->assertSame($internal->version, $payload['version']);
        $this->assertHiddenCardsAreSecret($payload['state']['cards']);
    }

    public function test_terminal_memory_match_sets_player_results_for_seat_one_win_seat_two_win_and_draw_without_ranked_dependencies(): void
    {
        $seatOne = $this->nearTerminalMatch([1 => 6, 2 => 5], 1);
        $this->finishLastPair($seatOne['match'], $seatOne['one']);
        $this->assertFinishedResults($seatOne['match'], 1, 'win', 'loss');

        $seatTwo = $this->nearTerminalMatch([1 => 5, 2 => 6], 2);
        $this->finishLastPair($seatTwo['match'], $seatTwo['two']);
        $this->assertFinishedResults($seatTwo['match'], 2, 'loss', 'win');

        $draw = $this->nearTerminalMatch([1 => 5, 2 => 6], 1);
        $this->finishLastPair($draw['match'], $draw['one']);
        $draw['match']->refresh();
        $this->assertSame('finished', $draw['match']->status->value);
        $this->assertNull($draw['match']->winner_seat);
        $this->assertTrue($draw['match']->state['draw']);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $draw['match']->id, 'seat' => 1, 'result' => 'draw']);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $draw['match']->id, 'seat' => 2, 'result' => 'draw']);
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
        $match = ArcadeMatch::create([
            'game_id' => $game->id, 'mode' => 'casual', 'status' => 'active', 'state' => (new HuntWinsEngine)->initialize(), 'version' => 1,
            'current_seat' => 1, 'created_by' => $one->id, 'started_at' => now(),
        ]);
        $match->players()->createMany([
            ['user_id' => $one->id, 'seat' => 1, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
            ['user_id' => $two->id, 'seat' => 2, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
        ]);

        return [$one, $two, $match];
    }

    private function nearTerminalMatch(array $scores, int $turnSeat): array
    {
        $one = $this->user('terminal_one');
        $two = $this->user('terminal_two');
        $game = $this->memoryGame();
        $state = $this->terminalState($scores, $turnSeat);
        $match = ArcadeMatch::create([
            'game_id' => $game->id, 'mode' => 'casual', 'status' => 'active', 'state' => $state, 'version' => 10,
            'current_seat' => $turnSeat, 'created_by' => $one->id, 'started_at' => now(),
        ]);
        $match->players()->createMany([
            ['user_id' => $one->id, 'seat' => 1, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
            ['user_id' => $two->id, 'seat' => 2, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()],
        ]);

        return compact('match', 'one', 'two');
    }

    private function terminalState(array $scores, int $turnSeat): array
    {
        $cards = [];
        foreach (range(1, 12) as $number) {
            $motif = sprintf('memory_%02d', $number);
            $cards[] = ['index' => count($cards), 'motif_id' => $motif, 'status' => 'matched'];
            $cards[] = ['index' => count($cards), 'motif_id' => $motif, 'status' => 'matched'];
        }
        $cards[22]['status'] = 'hidden';
        $cards[23]['status'] = 'hidden';

        return [
            'cards' => $cards,
            'turn_seat' => $turnSeat,
            'scores' => $scores,
            'matched_pairs' => 11,
            'total_pairs' => 12,
            'revealed_indices' => [],
            'pending_mismatch' => null,
            'winner_seat' => null,
            'draw' => false,
        ];
    }

    private function finishLastPair(ArcadeMatch $match, User $actor): void
    {
        $this->move($actor, $match, 22, 'terminal-'.$match->id.'-1')->assertOk();
        $this->move($actor, $match, 23, 'terminal-'.$match->id.'-2')->assertOk();
    }

    private function assertFinishedResults(ArcadeMatch $match, int $winnerSeat, string $oneResult, string $twoResult): void
    {
        $match->refresh();
        $this->assertSame('finished', $match->status->value);
        $this->assertSame($winnerSeat, $match->winner_seat);
        $this->assertFalse($match->state['draw']);
        $this->assertSame(12, $match->state['matched_pairs']);
        $this->assertNull($match->current_seat);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'seat' => 1, 'result' => $oneResult]);
        $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'seat' => 2, 'result' => $twoResult]);
    }

    private function pairIndices(ArcadeMatch $match): array
    {
        $groups = [];
        foreach ($match->state['cards'] as $card) {
            $groups[$card['motif_id']][] = $card['index'];
        }

        return array_values($groups)[0];
    }

    private function mismatchIndices(ArcadeMatch $match): array
    {
        $first = $match->state['cards'][0];
        foreach ($match->state['cards'] as $card) {
            if ($card['motif_id'] !== $first['motif_id']) {
                foreach ($match->state['cards'] as $next) {
                    if (! in_array($next['index'], [$first['index'], $card['index']], true)) {
                        return [$first['index'], $card['index'], $next['index']];
                    }
                }
            }
        }

        $this->fail('Could not find Memory mismatch indices.');
    }

    private function assertHiddenCardsAreSecret(array $cards): void
    {
        foreach ($cards as $card) {
            if (($card['status'] ?? null) === 'hidden') {
                $this->assertArrayNotHasKey('motif_id', $card);
            }
        }
    }

    private function memoryGame(): ArcadeGame
    {
        return ArcadeGame::firstOrCreate(
            ['key' => 'hunt-memory'],
            [
                'name_de' => 'Hunt Memory', 'name_en' => 'Hunt Memory', 'type' => 'native', 'status' => 'active',
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
