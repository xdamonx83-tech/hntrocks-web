<?php

namespace Tests\Unit;

use App\Services\Arcade\Engines\HuntersGallowsEngine;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HuntersGallowsEngineTest extends TestCase
{
    private HuntersGallowsEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new HuntersGallowsEngine('WINFIELD', 'weapons');
    }

    public function test_initial_internal_and_public_state_contract_and_secret_protection(): void
    {
        $state = $this->engine->initialize();
        $this->assertSame('WINFIELD', $state['secret_word']);
        $this->assertSame(1, $state['turn_seat']);
        $this->assertSame(6, $state['max_mistakes']);
        $this->assertSame(['score' => 0, 'mistakes' => 0], $state['players']['1']);

        $public = $this->engine->publicState($state);
        $this->assertSame(array_fill(0, 8, '*'), $public['masked_word']);
        $this->assertSame('weapons', $public['category_key']);
        $this->assertSame(1, $public['current_seat']);
        $encoded = json_encode($public);
        $this->assertStringNotContainsString('WINFIELD', $encoded);
        foreach (['secret_word', 'word', 'answer', 'solution', 'revealed_positions'] as $forbidden) {
            $this->assertStringNotContainsString('"'.$forbidden.'"', $encoded);
        }
    }

    public function test_correct_repeated_letter_scores_every_new_position_and_switches_seat(): void
    {
        $state = $this->engine->apply($this->engine->initialize(), 1, ['action' => 'guess_letter', 'letter' => 'i']);
        $this->assertSame(2, $state['players']['1']['score']);
        $this->assertSame([false, true, false, false, true, false, false, false], $state['revealed_positions']);
        $this->assertSame(['I'], $state['guessed_letters']);
        $this->assertSame(2, $state['turn_seat']);
    }

    public function test_incorrect_guess_adds_mistake_and_duplicate_invalid_payload_and_turn_are_rejected(): void
    {
        $state = $this->engine->apply($this->engine->initialize(), 1, ['action' => 'guess_letter', 'letter' => 'z']);
        $this->assertSame(1, $state['players']['1']['mistakes']);
        $this->assertSame(2, $state['turn_seat']);

        foreach ([
            [2, ['action' => 'guess_letter', 'letter' => 'Z']],
            [2, ['action' => 'guess_letter', 'letter' => '12']],
            [2, ['action' => 'guess_letter', 'letter' => 'Ä']],
            [1, ['action' => 'guess_letter', 'letter' => 'A']],
            [2, ['action' => 'unknown']],
            [2, ['action' => 'solve', 'word' => 'WIN FIELD']],
            [3, ['action' => 'solve', 'word' => 'WINFIELD']],
        ] as [$seat, $payload]) {
            $this->expectValidation(fn () => $this->engine->apply($state, $seat, $payload));
        }
    }

    public function test_correct_solve_wins_immediately_and_wrong_solve_costs_two_and_switches(): void
    {
        $state = $this->engine->apply($this->engine->initialize(), 1, ['action' => 'solve', 'word' => ' winfield ']);
        $this->assertSame(1, $state['winner_seat']);
        $this->assertSame(1, $state['solved_by_seat']);
        $this->assertNull($state['turn_seat']);
        $this->expectValidation(fn () => $this->engine->apply($state, 2, ['action' => 'guess_letter', 'letter' => 'A']));

        $wrong = $this->engine->apply($this->engine->initialize(), 1, ['action' => 'solve', 'word' => 'OUTLAW']);
        $this->assertSame(2, $wrong['players']['1']['mistakes']);
        $this->assertSame(2, $wrong['turn_seat']);
    }

    public function test_wrong_solve_and_wrong_letter_reaching_limit_award_opponent_win(): void
    {
        $state = $this->engine->initialize();
        $state['players']['1']['mistakes'] = 4;
        $state = $this->engine->apply($state, 1, ['action' => 'solve', 'word' => 'OUTLAW']);
        $this->assertSame(2, $state['winner_seat']);
        $this->assertNull($state['turn_seat']);

        $state = $this->engine->initialize();
        $state['players']['1']['mistakes'] = 5;
        $state = $this->engine->apply($state, 1, ['action' => 'guess_letter', 'letter' => 'Z']);
        $this->assertSame(2, $state['winner_seat']);
        $this->assertNull($state['turn_seat']);
    }

    public function test_fully_revealed_word_uses_score_then_mistakes_then_draw(): void
    {
        $higherScore = $this->almostRevealed(['score' => 7, 'mistakes' => 5], ['score' => 1, 'mistakes' => 0]);
        $this->assertSame(1, $this->finishWithD($higherScore)['winner_seat']);

        $fewerMistakes = $this->almostRevealed(['score' => 1, 'mistakes' => 1], ['score' => 2, 'mistakes' => 3]);
        $this->assertSame(1, $this->finishWithD($fewerMistakes)['winner_seat']);

        $tie = $this->almostRevealed(['score' => 1, 'mistakes' => 1], ['score' => 2, 'mistakes' => 1]);
        $finished = $this->finishWithD($tie);
        $this->assertTrue($finished['draw']);
        $this->assertNull($finished['winner_seat']);
        $this->assertNull($finished['turn_seat']);
    }

    private function almostRevealed(array $one, array $two): array
    {
        $state = $this->engine->initialize();
        $state['revealed_positions'] = array_fill(0, 8, true);
        $state['revealed_positions'][7] = false;
        $state['players'] = ['1' => $one, '2' => $two];
        return $state;
    }

    private function finishWithD(array $state): array
    {
        return $this->engine->apply($state, 1, ['action' => 'guess_letter', 'letter' => 'D']);
    }

    private function expectValidation(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Invalid Gallows move was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }
}
