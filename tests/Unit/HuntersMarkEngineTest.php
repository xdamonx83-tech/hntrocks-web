<?php

namespace Tests\Unit;

use App\Services\Arcade\Engines\HuntersMarkEngine;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class HuntersMarkEngineTest extends TestCase
{
    public function test_initial_state_is_empty_three_by_three_board_with_seat_one_turn(): void
    {
        $engine = new HuntersMarkEngine();
        $state = $engine->initialize();

        $this->assertCount(9, $state['board']);
        $this->assertSame(array_fill(0, 9, null), $state['board']);
        $this->assertSame(1, $state['turn_seat']);
        $this->assertSame(0, $state['move_count']);
        $this->assertNull($state['winner_seat']);
        $this->assertFalse($state['draw']);
        $this->assertSame([], $state['winning_cells']);
        $this->assertSame('place', $engine->moveType());
    }

    public function test_valid_move_places_marker_and_changes_turn(): void
    {
        $engine = new HuntersMarkEngine();
        $state = $engine->apply($engine->initialize(), 1, ['action' => 'place', 'cell_index' => 4]);

        $this->assertSame(1, $state['board'][4]);
        $this->assertSame(2, $state['turn_seat']);
        $this->assertSame(1, $state['move_count']);
    }

    public function test_rejects_invalid_payload_turn_and_occupied_cell(): void
    {
        $engine = new HuntersMarkEngine();

        foreach ([
            ['action' => 'drop', 'cell_index' => 0],
            ['action' => 'place', 'cell_index' => -1],
            ['action' => 'place', 'cell_index' => 9],
            ['action' => 'place', 'cell_index' => '1'],
            ['action' => 'place', 'cell_index' => 1, 'extra' => true],
        ] as $payload) {
            try {
                $engine->apply($engine->initialize(), 1, $payload);
                $this->fail('Invalid Hunter\'s Mark move was accepted.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        try {
            $engine->apply($engine->initialize(), 2, ['action' => 'place', 'cell_index' => 0]);
            $this->fail('Wrong seat was allowed to move.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $state = $engine->apply($engine->initialize(), 1, ['action' => 'place', 'cell_index' => 0]);
        try {
            $engine->apply($state, 2, ['action' => 'place', 'cell_index' => 0]);
            $this->fail('Occupied cell was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_detects_horizontal_vertical_and_both_diagonal_wins(): void
    {
        $this->assertWin([0, 3, 1, 4, 2], [0, 1, 2]);
        $this->assertWin([0, 1, 3, 2, 6], [0, 3, 6]);
        $this->assertWin([0, 1, 4, 2, 8], [0, 4, 8]);
        $this->assertWin([2, 0, 4, 1, 6], [2, 4, 6]);
    }

    public function test_detects_draw_on_full_board_without_winner(): void
    {
        $engine = new HuntersMarkEngine();
        $state = $engine->initialize();

        foreach ([0, 1, 2, 4, 3, 5, 7, 6, 8] as $cell) {
            $state = $engine->apply($state, $state['turn_seat'], ['action' => 'place', 'cell_index' => $cell]);
        }

        $this->assertTrue($state['draw']);
        $this->assertNull($state['winner_seat']);
        $this->assertSame([], $state['winning_cells']);
        $this->assertNull($engine->publicState($state)['current_seat']);
    }

    public function test_public_state_hides_internal_turn_and_move_count(): void
    {
        $engine = new HuntersMarkEngine();
        $public = $engine->publicState($engine->initialize());

        $this->assertSame([
            'board' => array_fill(0, 9, null),
            'current_seat' => 1,
            'winner_seat' => null,
            'draw' => false,
            'winning_cells' => [],
        ], $public);
        $this->assertArrayNotHasKey('turn_seat', $public);
        $this->assertArrayNotHasKey('move_count', $public);
    }

    public function test_rejects_moves_after_game_is_finished(): void
    {
        $engine = new HuntersMarkEngine();
        $state = $this->play($engine, [0, 3, 1, 4, 2]);

        try {
            $engine->apply($state, 2, ['action' => 'place', 'cell_index' => 5]);
            $this->fail('Move after victory was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    private function assertWin(array $moves, array $winningCells): void
    {
        $engine = new HuntersMarkEngine();
        $state = $this->play($engine, $moves);

        $this->assertSame(1, $state['winner_seat']);
        $this->assertFalse($state['draw']);
        $this->assertSame($winningCells, $state['winning_cells']);
        $this->assertNull($engine->publicState($state)['current_seat']);
    }

    private function play(HuntersMarkEngine $engine, array $moves): array
    {
        $state = $engine->initialize();
        foreach ($moves as $cell) {
            $state = $engine->apply($state, $state['turn_seat'], ['action' => 'place', 'cell_index' => $cell]);
        }

        return $state;
    }
}
