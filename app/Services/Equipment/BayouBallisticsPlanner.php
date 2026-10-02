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
