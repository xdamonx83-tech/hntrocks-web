<?php

namespace App\Services\Equipment;

use App\Models\EquipmentAmmo;
use App\Models\EquipmentItem;

class BayouBallisticsPlanner
{
    private const AMMO_FIELDS = [
        'baseDamage', 'headMultiplier', 'upperTorsoMultiplier',
        'torsoMultiplier', 'armMultiplier', 'legMultiplier',
    ];

    public function __construct(
        private readonly BayouAmmoMatcher $ammoMatcher,
        private readonly BayouIndexBallisticsSource $catalogSource,
    ) {}

    /** Plan every HNT ammo mode independently against the reviewed public build. */
    public function planCatalog(EquipmentItem $item, array $source, int $weaponConfidence): array
    {
        $item->loadMissing(['stats.definition', 'ammo.falloffPoints']);
        $provenance = $item->provenance()->get()->keyBy('field_key');
        $stats = $item->stats->filter(fn ($stat) => $stat->definition !== null)
            ->mapWithKeys(fn ($stat) => [$stat->definition->key => (float) $stat->value]);
        $rows = [];
        $matches = [];
        $rows[] = $this->row($item, $source, 'stat.zoom', $stats->get('zoom'), $source['zoom'] ?? null,
            $weaponConfidence, (bool) ($provenance->get('stat.zoom')?->is_manual_override), false, false);

        foreach ($item->ammo as $ammo) {
            $matched = $this->ammoMatcher->match($ammo, $source['modes']);
            $prefix = 'ammo.'.$ammo->key.'.';
            if ($matched['mode'] === null) {
                $rows[] = $this->reportRow($item, $source, $prefix.'identity', $ammo->name, null,
                    'REVIEW_REQUIRED', 0, $matched['reason']);
                continue;
            }
            $mode = $matched['mode'];
            $matches[$ammo->key] = ['source_ammo_id' => $mode['id'], 'confidence' => $matched['confidence']];
            $confidence = min($weaponConfidence, $matched['confidence']);
            $base = in_array(strtolower(trim((string) $ammo->name)), ['basic', 'stock'], true)
                && strcasecmp((string) $ammo->ammo_type, (string) $item->ammo_type) === 0;
            $facts = is_array($ammo->facts) ? $ammo->facts : [];
            $ammoStats = is_array($facts['stats'] ?? null) ? $facts['stats'] : [];
            if (($mode['projectile_kind'] ?? null) !== 'standard_bullet') {
                $rows[] = $this->reportRow($item, $source, $prefix.'projectile', null,
                    $mode['damage_source'] ?? null, 'UNSUPPORTED', $confidence,
                    'Pellet, explosive, arrow, bolt, or other nonstandard damage model.');
            }
            foreach (self::AMMO_FIELDS as $key) {
                $current = $ammoStats[$key] ?? ($base ? $stats->get($key) : null);
                $manual = (bool) ($provenance->get($prefix.'stat.'.$key)?->is_manual_override)
                    || ($base && (bool) ($provenance->get('stat.'.$key)?->is_manual_override));
                $rows[] = $this->row($item, $source, $prefix.'stat.'.$key, $current,
                    $mode['stats'][$key] ?? null, $confidence, $manual, false, false);
            }
            foreach (['damage' => $ammo->damage, 'velocity' => $ammo->velocity,
                'loaded' => $ammo->loaded, 'reserve' => $ammo->reserve,
                'dropRange' => $ammoStats['effectiveRange'] ?? null] as $key => $current) {
                $rows[] = $this->row($item, $source, $prefix.$key, $current,
                    $mode['checks'][$key] ?? null, $confidence,
                    (bool) ($provenance->get($prefix.$key)?->is_manual_override), false, true);
            }
            $profile = $this->catalogSource->bulletDropProfile($mode, $source);
            $currentProfile = $facts['bullet_drop'] ?? null;
            $velocityMatches = $profile === null || (is_numeric($ammo->velocity)
                && abs((float) $ammo->velocity - (float) $profile['muzzle_velocity_mps']) < .0005);
            $rows[] = $this->profileRow($item, $source, $prefix.'bullet_drop', $currentProfile,
                $profile, $confidence, (bool) ($provenance->get($prefix.'bullet_drop')?->is_manual_override),
                $velocityMatches);
            $points = $ammo->falloffPoints->sortBy('distance')->values();
            $ratios = $points->isEmpty() || (float) $points->first()->damage <= 0 ? null
                : $points->map(fn ($point) => [(float) $point->distance,
                    round((float) $point->damage / (float) $points->first()->damage, 5)])->all();
            $sourceRatios = $mode['falloff_ratios'] ?? null;
            $falloffAction = $sourceRatios === null ? 'SKIP' : ($ratios === null ? 'SKIP'
                : ($this->sameRatios($ratios, $sourceRatios) ? 'UNCHANGED' : 'REVIEW_REQUIRED'));
            $rows[] = $this->reportRow($item, $source, $prefix.'falloff_ratios', $ratios,
                $sourceRatios, $falloffAction, $confidence,
                $falloffAction === 'SKIP' ? 'Falloff is audit-only; no automatic point insertion.' :
                    ($falloffAction === 'REVIEW_REQUIRED' ? 'Existing HNT falloff differs and is protected.' : null));
        }

        return ['item' => $item->slug, 'source_page' => $source['source_url'],
            'source_key' => 'bayou_index', 'observed_at' => $source['observed_at'],
            'payload_hash' => $source['payload_hash'], 'matches' => $matches,
            'rows' => $rows, 'writes' => 0];
    }

    private function profileRow(EquipmentItem $item, array $source, string $field,
        mixed $current, ?array $incoming, int $confidence, bool $manual, bool $velocityMatches): array
    {
        $action = 'SKIP';
        $reason = 'No reproducible supported public bullet-drop model.';
        if ($incoming !== null && ! $velocityMatches) {
            $action = 'REVIEW_REQUIRED'; $reason = 'HNT ammo velocity does not confirm the source flight model.';
        } elseif ($incoming !== null && $manual) {
            $action = 'REVIEW_REQUIRED'; $reason = 'Manual override is protected.';
        } elseif ($incoming !== null && $confidence < 95) {
            $action = 'REVIEW_REQUIRED'; $reason = 'Weapon or ammo identity is not certain.';
        } elseif ($incoming !== null && $current === null) {
            $action = 'CREATE'; $reason = null;
        } elseif ($incoming !== null) {
            $keys = ['model', 'zero_range_m', 'gravity_mps2', 'pre_gravity_mps2',
                'air_resistance_per_s', 'muzzle_velocity_mps', 'max_distance_m', 'reference_aim'];
            $same = is_array($current);
            foreach ($keys as $key) $same = $same && ($current[$key] ?? null) === ($incoming[$key] ?? null);
            $action = $same ? 'UNCHANGED' : 'REVIEW_REQUIRED';
            $reason = $same ? null : 'Existing HNT bullet-drop profile is protected.';
        }
        return $this->reportRow($item, $source, $field, $current, $incoming, $action, $confidence, $reason);
    }

    private function sameRatios(array $current, array $source): bool
    {
        if (count($current) !== count($source)) return false;
        foreach ($current as $index => $point) {
            if (abs($point[0] - $source[$index][0]) > .0005
                || abs($point[1] - $source[$index][1]) > .0005) return false;
        }
        return true;
    }

    private function reportRow(EquipmentItem $item, array $source, string $field,
        mixed $current, mixed $incoming, string $action, int $confidence, ?string $reason): array
    {
        return ['item' => $item->slug, 'source_page' => $source['source_url'],
            'field' => $field, 'current_hnt' => $current, 'bayou' => $incoming,
            'action' => $action, 'confidence' => $confidence, 'reason' => $reason,
            'check_only' => ! in_array($action, ['CREATE', 'UNCHANGED'], true)];
    }

    public function plan(EquipmentItem $item, array $source, int $matchConfidence): array
    {
        $item->loadMissing(['stats.definition', 'ammo']);
        $provenance = $item->provenance()->get()->keyBy('field_key');
        $stats = $item->stats->filter(fn ($stat) => $stat->definition !== null)
            ->mapWithKeys(fn ($stat) => [$stat->definition->key => (float) $stat->value]);
        $baseAmmo = $this->baseAmmo($item, $source);
        $rows = [];

        foreach (($source['fields'] ?? []) as $key => $value) {
            if (! in_array($key, [...self::AMMO_FIELDS, 'zoom'], true)) continue;
            $ammoScoped = in_array($key, self::AMMO_FIELDS, true);
            $field = $ammoScoped && $baseAmmo ? 'ammo.'.$baseAmmo->key.'.stat.'.$key : 'stat.'.$key;
            $current = $ammoScoped
                ? ($baseAmmo?->facts['stats'][$key] ?? $stats->get($key))
                : $stats->get($key);
            $manual = (bool) ($provenance->get($field)?->is_manual_override)
                || ($ammoScoped && (bool) ($provenance->get('stat.'.$key)?->is_manual_override));
            $confidence = min($matchConfidence, $key === 'zoom' ? 100 : 95);
            $rows[] = $this->row($item, $source, $field, $current, $value, $confidence,
                $manual, $ammoScoped && $baseAmmo === null, false);
        }

        foreach (($source['checks'] ?? []) as $key => $value) {
            if (! in_array($key, ['damage', 'cycleTime', 'dropRange', 'reserve'], true)) continue;
            $field = $key === 'reserve' && $baseAmmo ? 'ammo.'.$baseAmmo->key.'.reserve' : 'stat.'.$key;
            $current = $key === 'reserve' && $baseAmmo ? $baseAmmo->reserve : $stats->get($key);
            $rows[] = $this->row($item, $source, $field, $current, $value, $matchConfidence,
                (bool) ($provenance->get($field)?->is_manual_override), false, true);
        }

        return [
            'item' => $item->slug,
            'source_page' => $source['source_url'],
            'observed_at' => $source['observed_at'],
            'payload_hash' => $source['payload_hash'],
            'source_key' => 'bayou_index',
            'base_ammo_key' => $baseAmmo?->key,
            'rows' => $rows,
            'writes' => 0,
        ];
    }

    private function baseAmmo(EquipmentItem $item, array $source): ?EquipmentAmmo
    {
        $sourceType = (string) ($source['ammo_type'] ?? '');
        if ($sourceType === '' || strcasecmp($sourceType, (string) $item->ammo_type) !== 0) return null;

        $matches = $item->ammo->filter(fn (EquipmentAmmo $ammo) =>
            strcasecmp((string) $ammo->ammo_type, $sourceType) === 0 &&
            in_array(strtolower(trim($ammo->name)), ['basic', 'stock'], true));

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function row(
        EquipmentItem $item, array $source, string $field, mixed $current, mixed $incoming,
        int $confidence, bool $manual, bool $ambiguousAmmo, bool $checkOnly,
    ): array {
        $action = 'SKIP';
        $reason = 'Source value is absent.';
        if ($incoming !== null && (! is_numeric($incoming) || ! is_finite((float) $incoming))) {
            $reason = 'Source value is not a finite number.';
        } elseif ($incoming !== null && $manual) {
            $action = 'BLOCKED_MANUAL';
            $reason = 'Manual override is protected.';
        } elseif ($incoming !== null && ($confidence < 90 || $ambiguousAmmo)) {
            $action = 'REVIEW_REQUIRED';
            $reason = $ambiguousAmmo ? 'No unique matching base ammunition mode.' : 'Weapon match is not certain.';
        } elseif ($incoming !== null && $current === null) {
            $action = $checkOnly ? 'SKIP' : 'CREATE';
            $reason = $checkOnly ? 'Existing field is absent; conflict check never imports.' : null;
        } elseif ($incoming !== null && abs((float) $current - (float) $incoming) < 0.0005) {
            $action = 'UNCHANGED';
            $reason = null;
        } elseif ($incoming !== null) {
            $action = 'REVIEW_REQUIRED';
            $reason = $checkOnly ? 'Conflict check only; existing HNT value differs.'
                : 'Bayou is additive and never updates an existing HNT value.';
        }

        return [
            'item' => $item->slug,
            'source_page' => $source['source_url'],
            'field' => $field,
            'current_hnt' => $current,
            'bayou' => $incoming,
            'action' => $action,
            'confidence' => $confidence,
            'reason' => $reason,
            'check_only' => $checkOnly,
        ];
    }
}
