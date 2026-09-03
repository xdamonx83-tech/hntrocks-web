<?php

namespace Tests\Unit;

use App\Services\Arcade\Engines\MemoryEngine;
use Tests\TestCase;

class MemoryPairOwnershipTest extends TestCase
{
    public function test_matching_pair_records_the_scoring_seat_on_both_cards(): void
    {
        $engine = new MemoryEngine;
        $state = $this->state();
        $state['turn_seat'] = 2;

        $state = $engine->apply($state, 2, ['action' => 'reveal', 'card_index' => 0]);
        $state = $engine->apply($state, 2, ['action' => 'reveal', 'card_index' => 1]);

        $this->assertSame('matched', $state['cards'][0]['status']);
        $this->assertSame('matched', $state['cards'][1]['status']);
        $this->assertSame(2, $state['cards'][0]['matched_by_seat']);
        $this->assertSame(2, $state['cards'][1]['matched_by_seat']);
        $this->assertSame(1, $state['scores'][2]);
        $this->assertSame(1, $state['matched_pairs']);
        $this->assertSame(2, $state['turn_seat']);
    }

    public function test_public_state_exposes_ownership_only_for_matched_cards(): void
    {
        $engine = new MemoryEngine;
        $state = $this->state();
        $state['cards'][0]['status'] = 'matched';
        $state['cards'][0]['matched_by_seat'] = 1;
        $state['cards'][1]['status'] = 'revealed';
        $state['cards'][1]['matched_by_seat'] = 2;
        $state['cards'][2]['matched_by_seat'] = 1;

        $public = $engine->publicState($state, 1);

        $this->assertSame([
            'index' => 0,
            'status' => 'matched',
            'motif_id' => 'memory_01',
            'matched_by_seat' => 1,
        ], $public['cards'][0]);
        $this->assertSame([
            'index' => 1,
            'status' => 'revealed',
            'motif_id' => 'memory_01',
        ], $public['cards'][1]);
        $this->assertSame([
            'index' => 2,
            'status' => 'hidden',
        ], $public['cards'][2]);
        $this->assertArrayNotHasKey('matched_by_seat', $public['cards'][1]);
        $this->assertArrayNotHasKey('matched_by_seat', $public['cards'][2]);
        $this->assertArrayNotHasKey('motif_id', $public['cards'][2]);
    }

    public function test_legacy_matched_cards_without_ownership_remain_publicly_compatible(): void
    {
        $engine = new MemoryEngine;
        $state = $this->state();
        $state['cards'][0]['status'] = 'matched';

        $public = $engine->publicState($state, 1);

        $this->assertSame([
            'index' => 0,
            'status' => 'matched',
            'motif_id' => 'memory_01',
        ], $public['cards'][0]);
        $this->assertArrayNotHasKey('matched_by_seat', $public['cards'][0]);
    }

    private function state(): array
    {
        return [
            'cards' => [
                ['index' => 0, 'motif_id' => 'memory_01', 'status' => 'hidden'],
                ['index' => 1, 'motif_id' => 'memory_01', 'status' => 'hidden'],
                ['index' => 2, 'motif_id' => 'memory_02', 'status' => 'hidden'],
                ['index' => 3, 'motif_id' => 'memory_02', 'status' => 'hidden'],
            ],
            'turn_seat' => 1,
            'scores' => [1 => 0, 2 => 0],
            'matched_pairs' => 0,
            'total_pairs' => 2,
            'revealed_indices' => [],
            'pending_mismatch' => null,
            'winner_seat' => null,
            'draw' => false,
        ];
    }
}
