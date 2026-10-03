<?php

namespace App\Services\Equipment;

class WeaponBulletDropCalculator
{
    /**
     * Only the publicly documented zero-range model is supported. A future
     * drag or pre-gravity profile needs a separately verified implementation.
     */
    public function valid(?array $profile): bool
    {
        if (! is_array($profile) || ($profile['status'] ?? null) !== 'available'
            || ($profile['model'] ?? null) !== 'bayou_public_zero_range_gravity_v1') return false;
        foreach (['muzzle_velocity_mps', 'zero_range_m', 'gravity_mps2', 'pre_gravity_mps2',
            'air_resistance_per_s', 'max_distance_m', 'head_size_m'] as $key) {
            if (! isset($profile[$key]) || ! is_numeric($profile[$key])
                || ! is_finite((float) $profile[$key])) return false;
        }
        return $profile['muzzle_velocity_mps'] > 0 && $profile['zero_range_m'] >= 0
            && $profile['gravity_mps2'] < 0 && (float) $profile['pre_gravity_mps2'] === 0.0
            && (float) $profile['air_resistance_per_s'] === 0.0
            && $profile['max_distance_m'] >= $profile['zero_range_m']
            && $profile['head_size_m'] > 0
            && ($profile['reference_aim'] ?? null) === 'top_of_head'
            && $this->validOffsets($profile['zone_offsets_m'] ?? null);
    }

    public function dropAt(?array $profile, float $distance): ?float
    {
        if (! $this->valid($profile) || ! is_finite($distance) || $distance < 0
            || $distance > (float) $profile['max_distance_m']) return null;
        $travel = max(0.0, $distance - (float) $profile['zero_range_m']);
        $time = $travel / (float) $profile['muzzle_velocity_mps'];

        return abs((float) $profile['gravity_mps2']) * $time * $time / 2;
    }

    public function distanceForDrop(?array $profile, float $drop): ?float
    {
        if (! $this->valid($profile) || ! is_finite($drop) || $drop < 0) return null;
        $distance = (float) $profile['zero_range_m']
            + (float) $profile['muzzle_velocity_mps'] * sqrt(2 * $drop / abs((float) $profile['gravity_mps2']));

        return $distance <= (float) $profile['max_distance_m'] ? $distance : null;
    }

    public function atDistance(?array $profile, float $distance): ?array
    {
        $drop = $this->dropAt($profile, $distance);
        if ($drop === null) return null;
        $zone = 'head';
        $reference = 0.0;
        foreach ($profile['zone_offsets_m'] as $key => $offset) {
            if ($key === 'head') continue;
            $start = $this->distanceForDrop($profile, (float) $offset);
            if ($start !== null && $distance >= $start) {
                $zone = $key;
                $reference = (float) $offset;
            }
        }

        return [
            'distance_m' => $distance,
            'drop_m' => round($drop, 3),
            'reference_zone' => $zone,
            'reference_drop_m' => round($reference, 2),
        ];
    }

    public function summary(?array $profile): ?array
    {
        if (! $this->valid($profile)) return null;
        $zones = [];
        foreach ($profile['zone_offsets_m'] as $key => $offset) {
            $start = $key === 'head' ? 0.0 : $this->distanceForDrop($profile, (float) $offset);
            if ($start === null) continue;
            $zones[] = ['key' => $key, 'start_m' => (int) round($start),
                'reference_drop_m' => round((float) $offset, 2)];
        }
        $distances = [0.0, (float) $profile['zero_range_m'], (float) $profile['max_distance_m']];
        for ($meter = 5; $meter <= (int) $profile['max_distance_m']; $meter += 5) $distances[] = (float) $meter;
        foreach ($zones as $zone) $distances[] = (float) $zone['start_m'];
        $distances = array_values(array_unique($distances));
        sort($distances, SORT_NUMERIC);
        $points = array_map(fn ($distance) => [
            'distance_m' => $distance, 'drop_m' => round($this->dropAt($profile, $distance), 3),
        ], $distances);
        $headSizeRange = $this->distanceForDrop($profile, (float) $profile['head_size_m']);

        return [
            'status' => 'available', 'model' => $profile['model'],
            'source' => $profile['source'],
            'verification' => ['source_model_reproduced' => true, 'game_rules_verified' => false],
            'reference_aim' => $profile['reference_aim'],
            'flat_until_m' => (float) $profile['zero_range_m'],
            'head_size_range_m' => $headSizeRange === null ? null : (int) round($headSizeRange),
            'max_distance_m' => (float) $profile['max_distance_m'],
            'zones' => $zones, 'points' => $points,
        ];
    }

    private function validOffsets(mixed $offsets): bool
    {
        if (! is_array($offsets) || array_keys($offsets) !== ['head', 'upper_torso', 'torso', 'legs']) return false;
        $last = -1.0;
        foreach ($offsets as $offset) {
            if (! is_numeric($offset) || ! is_finite((float) $offset) || $offset < 0 || $offset <= $last) return false;
            $last = (float) $offset;
        }
        return (float) $offsets['head'] === 0.0;
    }
}
