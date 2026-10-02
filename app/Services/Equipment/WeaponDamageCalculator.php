<?php

namespace App\Services\Equipment;

class WeaponDamageCalculator
{
    public const HUNTER_HP = [150, 125, 100, 75, 50];

    /**
     * HNT model: linear interpolation of existing ammo damage points. Damage is
     * kept unrounded for hit counts; only the display number is rounded to two
     * decimals. This is not a claim about Hunt's internal rounding rules.
     */
    public function evaluate(?float $baseDamage, ?float $zoneMultiplier, array $points, float $distance, int $hp): ?array
    {
        if (! in_array($hp, self::HUNTER_HP, true) || $distance < 0 || ! is_finite($distance)
            || $baseDamage === null || $zoneMultiplier === null
            || $baseDamage <= 0 || $zoneMultiplier <= 0
            || ! is_finite($baseDamage) || ! is_finite($zoneMultiplier)) return null;

        $curve = $this->validatedPoints($points);
        if ($curve === null) return null;

        $firstDamage = $curve[0]['damage'];
        $falloff = $this->interpolatedDamage($curve, $distance) / $firstDamage;
        $rawDamage = $baseDamage * $zoneMultiplier * $falloff;
        if ($rawDamage <= 0 || ! is_finite($rawDamage)) return null;

        return [
            'distance_m' => $distance,
            'hunter_hp' => $hp,
            'falloff_multiplier' => $falloff,
            'raw_damage' => $rawDamage,
            'damage_at_distance' => round($rawDamage, 2),
            'shots_to_kill' => (int) ceil($hp / $rawDamage),
        ];
    }

    /** @return array<int, array{start_m:int,end_m:?int,shots_to_kill:int}>|null */
    public function breakpoints(?float $baseDamage, ?float $zoneMultiplier, array $points, int $hp): ?array
    {
        $curve = $this->validatedPoints($points);
        if ($curve === null) return null;
        $lastMeter = (int) ceil($curve[array_key_last($curve)]['distance']);
        $segments = [];
        $currentHits = null;
        $start = 0;

        for ($meter = 0; $meter <= $lastMeter; $meter++) {
            $value = $this->evaluate($baseDamage, $zoneMultiplier, $curve, (float) $meter, $hp);
            if ($value === null) return null;
            $hits = $value['shots_to_kill'];
            if ($currentHits !== null && $hits !== $currentHits) {
                $segments[] = ['start_m' => $start, 'end_m' => $meter - 1, 'shots_to_kill' => $currentHits];
                $start = $meter;
            }
            $currentHits = $hits;
        }
        $segments[] = ['start_m' => $start, 'end_m' => null, 'shots_to_kill' => $currentHits];

        return $segments;
    }

    private function interpolatedDamage(array $points, float $distance): float
    {
        if ($distance <= $points[0]['distance']) return $points[0]['damage'];
        foreach (array_slice($points, 1) as $index => $point) {
            $before = $points[$index];
            if ($distance <= $point['distance']) {
                $fraction = ($distance - $before['distance']) / ($point['distance'] - $before['distance']);
                return $before['damage'] + $fraction * ($point['damage'] - $before['damage']);
            }
        }
        return $points[array_key_last($points)]['damage'];
    }

    private function validatedPoints(array $points): ?array
    {
        if (count($points) < 2) return null;
        $curve = [];
        foreach ($points as $point) {
            $distance = $point['distance'] ?? null;
            $damage = $point['damage'] ?? null;
            if (! is_numeric($distance) || ! is_numeric($damage)) return null;
            $distance = (float) $distance;
            $damage = (float) $damage;
            if (! is_finite($distance) || ! is_finite($damage) || $distance < 0 || $damage <= 0) return null;
            if ($curve && ($distance <= $curve[array_key_last($curve)]['distance']
                || $damage > $curve[array_key_last($curve)]['damage'])) return null;
            $curve[] = ['distance' => $distance, 'damage' => $damage];
        }
        return $curve;
    }
}
