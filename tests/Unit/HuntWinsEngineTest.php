<?php

namespace Tests\Unit;

use App\Services\Arcade\Engines\HuntWinsEngine;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class HuntWinsEngineTest extends TestCase
{
    private HuntWinsEngine $engine;
    protected function setUp(): void { parent::setUp(); $this->engine = new HuntWinsEngine; }

    public function test_initial_state_is_deterministic_six_by_seven_board(): void
    {
        $state = $this->engine->initialize();
        $this->assertCount(6, $state['board']); foreach ($state['board'] as $row) $this->assertSame(array_fill(0, 7, 0), $row);
        $this->assertSame(1, $state['turn_seat']); $this->assertSame(0, $state['move_count']);
    }

    public function test_gravity_stacking_and_turn_alternation(): void
    {
        $state = $this->engine->apply($this->engine->initialize(), 1, ['column' => 3]);
        $this->assertSame(1, $state['board'][5][3]); $this->assertSame(2, $state['turn_seat']);
        $state = $this->engine->apply($state, 2, ['column' => 3]);
        $this->assertSame(2, $state['board'][4][3]); $this->assertSame(1, $state['turn_seat']);
    }

    public function test_horizontal_and_vertical_wins(): void
    {
        $horizontal = $this->play([0, 6, 1, 6, 2, 5, 3]);
        $vertical = $this->play([0, 1, 0, 1, 0, 1, 0]);
        $this->assertSame(1, $horizontal['winner_seat']); $this->assertSame(1, $vertical['winner_seat']);
    }

    public function test_both_diagonal_directions_win(): void
    {
        $slash = $this->play([0, 1, 1, 2, 4, 2, 2, 3, 4, 3, 5, 3, 3]);
        $backslash = $this->play([3, 2, 2, 1, 5, 1, 1, 0, 5, 0, 6, 0, 0]);
        $this->assertSame(1, $slash['winner_seat']); $this->assertSame(1, $backslash['winner_seat']);
    }

    public function test_invalid_wrong_turn_and_full_columns_are_rejected(): void
    {
        foreach ([-1, 7] as $column) { try { $this->engine->apply($this->engine->initialize(), 1, ['column' => $column]); $this->fail(); } catch (ValidationException) { $this->assertTrue(true); } }
        $state = $this->engine->initialize();
        try { $this->engine->apply($state, 2, ['column' => 0]); $this->fail(); } catch (ValidationException) { $this->assertTrue(true); }
        for ($row = 0; $row < 6; $row++) $state['board'][$row][0] = ($row % 2) + 1;
        $this->expectException(ValidationException::class); $this->engine->apply($state, 1, ['column' => 0]);
    }

    public function test_full_board_without_winner_is_a_draw(): void
    {
        $state = $this->play([6,5,4,0,5,2,0,1,2,2,3,2,1,6,2,5,4,3,6,6,4,2,5,4,6,5,6,0,3,1,4,0,0,3,1,1,4,5,0,1,3,3]);
        $this->assertTrue($state['draw']); $this->assertNull($state['winner_seat']); $this->assertSame(42, $state['move_count']);
    }

    private function play(array $columns): array
    {
        $state = $this->engine->initialize();
        foreach ($columns as $column) $state = $this->engine->apply($state, $state['turn_seat'], ['column' => $column]);
        return $state;
    }
}
