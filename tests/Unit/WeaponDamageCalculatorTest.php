<?php

namespace Tests\Unit;

use App\Services\Equipment\WeaponDamageCalculator;
use PHPUnit\Framework\TestCase;

class WeaponDamageCalculatorTest extends TestCase
{
    private WeaponDamageCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new WeaponDamageCalculator();
    }

    private function points(): array
    {
        return [
            ['distance' => 30, 'damage' => 100],
            ['distance' => 80, 'damage' => 64],
            ['distance' => 130, 'damage' => 51],
        ];
    }

    public function test_linear_falloff_and_zone_multiplication_keep_unrounded_damage(): void
    {
        $atZero = $this->calculator->evaluate(99, 1.3, $this->points(), 0, 150);
        $this->assertEqualsWithDelta(128.7, $atZero['raw_damage'], 0.000001);
        $this->assertSame(128.7, $atZero['damage_at_distance']);
        $this->assertSame(2, $atZero['shots_to_kill']);

        $atFiftyFive = $this->calculator->evaluate(100, 1, $this->points(), 55, 150);
        $this->assertEqualsWithDelta(.82, $atFiftyFive['falloff_multiplier'], 0.000001);
        $this->assertSame(82.0, $atFiftyFive['damage_at_distance']);
        $this->assertSame(2, $atFiftyFive['shots_to_kill']);
        $this->assertSame(51.0, $this->calculator->evaluate(100, 1, $this->points(), 1000, 150)['damage_at_distance']);
    }

    public function test_all_supported_hunter_hp_values_and_breakpoint_at_one_meter_resolution(): void
    {
        $points = [['distance' => 10, 'damage' => 100], ['distance' => 20, 'damage' => 50]];
        $this->assertSame([
            ['start_m' => 0, 'end_m' => 15, 'shots_to_kill' => 2],
            ['start_m' => 16, 'end_m' => null, 'shots_to_kill' => 3],
        ], $this->calculator->breakpoints(100, 1, $points, 150));

        foreach ([125 => 2, 100 => 1, 75 => 1, 50 => 1] as $hp => $hits) {
            $this->assertSame($hits, $this->calculator->evaluate(100, 1, $points, 0, $hp)['shots_to_kill']);
        }
        $this->assertSame(2, $this->calculator->evaluate(100, 1, $points, 20, 100)['shots_to_kill']);
    }

    public function test_missing_or_invalid_input_is_unsupported(): void
    {
        $this->assertNull($this->calculator->evaluate(null, 1.3, $this->points(), 0, 150));
        $this->assertNull($this->calculator->evaluate(99, null, $this->points(), 0, 150));
        $this->assertNull($this->calculator->evaluate(99, 1.3, [], 0, 150));
        $this->assertNull($this->calculator->evaluate(99, 1.3, $this->points(), 0, 151));
        $this->assertNull($this->calculator->breakpoints(99, 1.3, [
            ['distance' => 10, 'damage' => 50], ['distance' => 10, 'damage' => 40],
        ], 150));
    }
}
