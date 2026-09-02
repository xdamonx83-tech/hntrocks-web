<?php

namespace Tests\Unit;

use App\Services\Arcade\Engines\MemoryEngine;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MemoryEngineTest extends TestCase
{
    public function test_initialization_builds_twelve_server_shuffled_pairs_from_the_twenty_four_motif_pool(): void
    {
        $calls = 0;
        $engine = new MemoryEngine(function (int $min, int $max) use (&$calls): int {
            $calls++;
            return $min;
        });

        $state = $engine->initialize();
        $this->assertCount(24, $state['cards']);
        $this->assertSame(1, $state['turn_seat']);
        $this->assertSame([1 => 0, 2 => 0], $state['scores']);
        $this->assertSame(0, $state['matched_pairs']);
        $this->assertSame(12, $state['total_pairs']);
        $this->assertSame([], $state['revealed_indices']);
        $this->assertNull($state['pending_mismatch']);
        $this->assertNull($state['winner_seat']);
        $this->assertFalse($state['draw']);
        $this->assertGreaterThan(0, $calls);

        $motifs = array_column($state['cards'], 'motif_id');
        $counts = array_count_values($motifs);
        $this->assertCount(12, $counts);
        foreach ($counts as $motif => $count) {
            $this->assertMatchesRegularExpression('/^memory_(0[1-9]|1[0-9]|2[0-4])$/', $motif);
            $this->assertSame(2, $count);
        }

        $ordered = [];
        foreach (range(1, 12) as $number) {
            $motif = sprintf('memory_%02d', $number);
            $ordered[] = $motif;
            $ordered[] = $motif;
        }
        $this->assertNotSame($ordered, $motifs);
    }

    public function test_first_reveal_keeps_the_same_player_and_exposes_no_score(): void
    {
        $engine = new MemoryEngine;
        $state = $this->state(['memory_01', 'memory_01', 'memory_02', 'memory_02']);
        $state = $engine->apply($state, 1, ['action' => 'reveal', 'card_index' => 0]);

        $this->assertSame('revealed', $state['cards'][0]['status']);
        $this->assertSame([0], $state['revealed_indices']);
        $this->assertSame(1, $state['turn_seat']);
        $this->assertSame([1 => 0, 2 => 0], $state['scores']);
    }

    public function test_matching_second_reveal_scores_pair_keeps_turn_and_leaves_cards_matched(): void
    {
        $engine = new MemoryEngine;
        $state = $this->state(['memory_01', 'memory_01', 'memory_02', 'memory_02']);
        $state = $engine->apply($state, 1, ['action' => 'reveal', 'card_index' => 0]);
        $state = $engine->apply($state, 1, ['action' => 'reveal', 'card_index' => 1]);

        $this->assertSame('matched', $state['cards'][0]['status']);
        $this->assertSame('matched', $state['cards'][1]['status']);
        $this->assertSame(1, $state['scores'][1]);
        $this->assertSame(1, $state['matched_pairs']);
        $this->assertSame([], $state['revealed_indices']);
        $this->assertNull($state['pending_mismatch']);
        $this->assertSame(1, $state['turn_seat']);
    }

    public function test_mismatch_stays_revealed_switches_turn_and_is_hidden_by_the_next_valid_reveal(): void
    {
        $engine = new MemoryEngine;
        $state = $this->state(['memory_01', 'memory_02', 'memory_01', 'memory_02']);
        $state = $engine->apply($state, 1, ['action' => 'reveal', 'card_index' => 0]);
        $state = $engine->apply($state, 1, ['action' => 'reveal', 'card_index' => 1]);

        $this->assertSame('revealed', $state['cards'][0]['status']);
        $this->assertSame('revealed', $state['cards'][1]['status']);
        $this->assertSame([0, 1], $state['pending_mismatch']);
        $this->assertSame([0, 1], $state['revealed_indices']);
        $this->assertSame(2, $state['turn_seat']);

        $state = $engine->apply($state, 2, ['action' => 'reveal', 'card_index' => 2]);
        $this->assertSame('hidden', $state['cards'][0]['status']);
        $this->assertSame('hidden', $state['cards'][1]['status']);
        $this->assertSame('revealed', $state['cards'][2]['status']);
        $this->assertSame([2], $state['revealed_indices']);
        $this->assertNull($state['pending_mismatch']);
        $this->assertSame(2, $state['turn_seat']);
    }

    public function test_same_card_invalid_indices_matched_card_wrong_turn_unknown_action_manipulated_fields_and_finished_state_are_rejected(): void
    {
        $engine = new MemoryEngine;
        $state = $this->state(['memory_01', 'memory_01', 'memory_02', 'memory_02']);

        $this->assertValidation(fn () => $engine->apply($state, 1, ['action' => 'reveal', 'card_index' => -1]));
        $this->assertValidation(fn () => $engine->apply($state, 1, ['action' => 'reveal', 'card_index' => 4]));
        $this->assertValidation(fn () => $engine->apply($state, 2, ['action' => 'reveal', 'card_index' => 0]));
        $this->assertValidation(fn () => $engine->apply($state, 1, ['action' => 'flip', 'card_index' => 0]));

        foreach (['motif_id' => 'memory_01', 'score' => 99, 'winner_seat' => 1, 'turn_seat' => 2, 'seed' => 'client', 'shuffle' => true] as $field => $value) {
            $this->assertValidation(fn () => $engine->apply($state, 1, ['action' => 'reveal', 'card_index' => 0, $field => $value]));
        }

        $first = $engine->apply($state, 1, ['action' => 'reveal', 'card_index' => 0]);
        $this->assertValidation(fn () => $engine->apply($first, 1, ['action' => 'reveal', 'card_index' => 0]));

        $matched = $engine->apply($first, 1, ['action' => 'reveal', 'card_index' => 1]);
        $this->assertValidation(fn () => $engine->apply($matched, 1, ['action' => 'reveal', 'card_index' => 0]));

        $finished = $state;
        $finished['winner_seat'] = 1;
        $this->assertValidation(fn () => $engine->apply($finished, 1, ['action' => 'reveal', 'card_index' => 0]));
    }

    public function test_terminal_state_supports_seat_one_win_seat_two_win_and_draw(): void
    {
        $engine = new MemoryEngine;

        $seatOne = $this->finishLastPair($engine, [1 => 6, 2 => 5], 1);
        $this->assertSame(1, $seatOne['winner_seat']);
        $this->assertFalse($seatOne['draw']);
        $this->assertSame(12, $seatOne['matched_pairs']);

        $seatTwo = $this->finishLastPair($engine, [1 => 5, 2 => 6], 2);
        $this->assertSame(2, $seatTwo['winner_seat']);
        $this->assertFalse($seatTwo['draw']);

        $draw = $this->finishLastPair($engine, [1 => 5, 2 => 6], 1);
        $this->assertNull($draw['winner_seat']);
        $this->assertTrue($draw['draw']);
        $this->assertSame([1 => 6, 2 => 6], $draw['scores']);
    }

    public function test_public_state_hides_hidden_motifs_and_exposes_only_revealed_or_matched_motifs(): void
    {
        $engine = new MemoryEngine;
        $state = $this->state(['memory_01', 'memory_01', 'memory_02', 'memory_02']);
        $state['cards'][0]['status'] = 'revealed';
        $state['cards'][2]['status'] = 'matched';
        $state['revealed_indices'] = [0];

        $public = $engine->publicState($state, 1);
        $this->assertSame(['index' => 0, 'status' => 'revealed', 'motif_id' => 'memory_01'], $public['cards'][0]);
        $this->assertSame(['index' => 1, 'status' => 'hidden'], $public['cards'][1]);
        $this->assertSame(['index' => 2, 'status' => 'matched', 'motif_id' => 'memory_02'], $public['cards'][2]);
        $this->assertSame(['index' => 3, 'status' => 'hidden'], $public['cards'][3]);
        $this->assertArrayNotHasKey('motif_id', $public['cards'][1]);
        $this->assertArrayNotHasKey('motif_id', $public['cards'][3]);
    }

    private function finishLastPair(MemoryEngine $engine, array $scores, int $seat): array
    {
        $motifs = [];
        foreach (range(1, 12) as $number) {
            $motif = sprintf('memory_%02d', $number);
            $motifs[] = $motif;
            $motifs[] = $motif;
        }
        $state = $this->state($motifs, 12);
        foreach (range(0, 21) as $index) {
            $state['cards'][$index]['status'] = 'matched';
        }
        $state['matched_pairs'] = 11;
        $state['scores'] = $scores;
        $state['turn_seat'] = $seat;

        $state = $engine->apply($state, $seat, ['action' => 'reveal', 'card_index' => 22]);
        return $engine->apply($state, $seat, ['action' => 'reveal', 'card_index' => 23]);
    }

    private function state(array $motifs, ?int $totalPairs = null): array
    {
        $cards = [];
        foreach ($motifs as $index => $motif) {
            $cards[] = ['index' => $index, 'motif_id' => $motif, 'status' => 'hidden'];
        }

        return [
            'cards' => $cards,
            'turn_seat' => 1,
            'scores' => [1 => 0, 2 => 0],
            'matched_pairs' => 0,
            'total_pairs' => $totalPairs ?? intdiv(count($cards), 2),
            'revealed_indices' => [],
            'pending_mismatch' => null,
            'winner_seat' => null,
            'draw' => false,
        ];
    }

    private function assertValidation(callable $action): void
    {
        try {
            $action();
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }
}
