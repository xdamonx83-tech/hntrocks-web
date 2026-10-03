<?php

namespace App\Services\Equipment;

use App\Models\EquipmentAmmo;
use App\Models\EquipmentItem;

class WeaponBallisticsPresenter
{
    private const ZONES = [
        'head' => 'headMultiplier',
        'upper_torso' => 'upperTorsoMultiplier',
        'torso' => 'torsoMultiplier',
        'arm' => 'armMultiplier',
        'leg' => 'legMultiplier',
    ];

    public function __construct(
        private readonly WeaponDamageCalculator $calculator,
        private readonly WeaponBulletDropCalculator $bulletDrop,
    ) {}

    public function summary(EquipmentItem $item): array
    {
        $item->loadMissing(['stats.definition', 'ammo.falloffPoints']);
        $modes = $item->ammo->map(function (EquipmentAmmo $ammo) use ($item): array {
            $inputs = $this->inputs($item, $ammo);
            $profiles = [];
            foreach (WeaponDamageCalculator::HUNTER_HP as $hp) {
                foreach ($inputs['hit_zones'] as $zone => $multiplier) {
                    $segments = $this->calculator->breakpoints(
                        $inputs['base_damage'], $multiplier, $inputs['falloff']['points'], $hp);
                    $zero = $this->calculator->evaluate(
                        $inputs['base_damage'], $multiplier, $inputs['falloff']['points'], 0, $hp);
                    $profiles[(string) $hp][$zone] = $segments === null ? null : [
                        'at_zero' => $zero,
                        'breakpoints' => $segments,
                    ];
                }
            }
            $supported = count(array_filter($profiles['150'] ?? [], fn ($profile) => $profile !== null));

            return $inputs + [
                'bullet_drop' => $this->bulletDrop->summary(data_get($ammo->facts, 'bullet_drop')),
                'status' => $supported === 0 ? 'unsupported' : ($supported === count(self::ZONES) ? 'ready' : 'partial'),
                'damage_profiles' => $profiles,
            ];
        })->values()->all();

        return [
            'status' => count(array_filter($modes, fn ($mode) => $mode['status'] !== 'unsupported'))
                ? 'available' : 'unsupported',
            'model' => 'hnt_piecewise_linear_v1',
            'rounding' => [
                'display_decimals' => 2,
                'hits_use_unrounded_damage' => true,
                'exact_game_rules_verified' => false,
            ],
            'supported_hunter_hp' => WeaponDamageCalculator::HUNTER_HP,
            'ammo_modes' => $modes,
        ];
    }

    public function atDistance(EquipmentItem $item, string $ammoKey, float $distance, int $hp): ?array
    {
        $item->loadMissing(['stats.definition', 'ammo.falloffPoints']);
        $ammo = $item->ammo->firstWhere('key', $ammoKey);
        if (! $ammo) return null;
        $inputs = $this->inputs($item, $ammo);
        $results = [];
        foreach ($inputs['hit_zones'] as $zone => $multiplier) {
            $results[$zone] = $this->calculator->evaluate(
                $inputs['base_damage'], $multiplier, $inputs['falloff']['points'], $distance, $hp);
        }

        return [
            'ammo_key' => $ammoKey,
            'distance_m' => $distance,
            'hunter_hp' => $hp,
            'status' => count(array_filter($results)) ? 'available' : 'unsupported',
            'hit_zones' => $results,
            'bullet_drop' => $this->bulletDrop->atDistance(data_get($ammo->facts, 'bullet_drop'), $distance),
            'model' => 'hnt_piecewise_linear_v1',
            'exact_game_rules_verified' => false,
        ];
    }

    private function inputs(EquipmentItem $item, EquipmentAmmo $ammo): array
    {
        $itemStats = $item->stats->filter(fn ($stat) => $stat->definition !== null)
            ->mapWithKeys(fn ($stat) => [$stat->definition->key => (float) $stat->value]);
        $ammoStats = data_get($ammo->facts, 'stats', []);
        if (! is_array($ammoStats)) $ammoStats = [];
        $baseMatches = $item->ammo->filter(fn (EquipmentAmmo $mode) =>
            in_array(strtolower(trim($mode->name)), ['basic', 'stock'], true)
            && strcasecmp((string) $mode->ammo_type, (string) $item->ammo_type) === 0);
        $isCanonicalBaseMode = $baseMatches->count() === 1 && $baseMatches->first()->getKey() === $ammo->getKey();
        $resolve = static function (string $key) use ($ammoStats, $itemStats, $isCanonicalBaseMode): ?float {
            if (array_key_exists($key, $ammoStats)) {
                return is_numeric($ammoStats[$key]) && is_finite((float) $ammoStats[$key])
                    ? (float) $ammoStats[$key] : null;
            }
            return $isCanonicalBaseMode && $itemStats->has($key) ? (float) $itemStats[$key] : null;
        };
        $points = $ammo->falloffPoints->sortBy('distance')->map(fn ($point) => [
            'distance' => (float) $point->distance,
            'damage' => (float) $point->damage,
        ])->values()->all();
        $zones = [];
        foreach (self::ZONES as $zone => $key) $zones[$zone] = $resolve($key);

        return [
            'ammo_key' => $ammo->key,
            'ammo_type' => $ammo->ammo_type,
            'base_damage' => $resolve('baseDamage'),
            'zoom' => $itemStats->has('zoom') ? (float) $itemStats['zoom'] : null,
            'hit_zones' => $zones,
            'falloff' => ['points' => $points, 'interpolation' => 'linear_between_points_hold_after_last'],
        ];
    }
}
