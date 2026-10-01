<?php

namespace App\Services\Equipment;

use App\Models\EquipmentFamilyAlias;
use App\Models\EquipmentFieldProvenance;
use App\Models\EquipmentItem;

class EquipmentMergePolicy
{
    public function decide(
        EquipmentItem $item,
        string $fieldKey,
        mixed $current,
        mixed $sourceValue,
        string $sourceKey,
        int $confidence,
        ?EquipmentFieldProvenance $provenance = null,
    ): array {
        if ($sourceValue === null || $sourceValue === '') {
            return ['action' => 'SKIP', 'reason' => 'Source value is absent'];
        }
        if ($confidence < 70) {
            return ['action' => 'BLOCKED_AMBIGUOUS', 'reason' => 'Resolver confidence below 70'];
        }
        if ($provenance?->is_manual_override) {
            return ['action' => 'BLOCKED_MANUAL', 'reason' => 'Manual override'];
        }
        if ($fieldKey === 'family') {
            if ($this->same($current, $sourceValue) || $this->isFamilyAlias($item, $sourceKey, (string) $sourceValue)) {
                return ['action' => 'UNCHANGED', 'reason' => null];
            }
            return ['action' => 'REVIEW_REQUIRED', 'reason' => 'Family mapping is not confirmed'];
        }
        if ($this->same($current, $sourceValue)) {
            return ['action' => 'UNCHANGED', 'reason' => null];
        }
        if ($provenance && $provenance->source_key !== $sourceKey) {
            return ['action' => 'REVIEW_REQUIRED', 'reason' => 'Value belongs to another source'];
        }
        if ($current === null || $current === '') {
            return ['action' => 'CREATE', 'reason' => null];
        }
        if ($provenance?->source_key === $sourceKey && ! $provenance->is_manual_override) {
            return ['action' => 'UPDATE', 'reason' => null];
        }
        return ['action' => 'REVIEW_REQUIRED', 'reason' => 'Existing value has no verified ownership by this source'];
    }

    private function isFamilyAlias(EquipmentItem $item, string $sourceKey, string $alias): bool
    {
        if (! $item->family_id) return false;

        return EquipmentFamilyAlias::query()
            ->where('equipment_family_id', $item->family_id)
            ->where('source_key', $sourceKey)
            ->where('alias_key', EquipmentFamilyAlias::keyFor($alias))
            ->exists();
    }

    public function isProtected(?EquipmentFieldProvenance $provenance, string $sourceKey): bool
    {
        return $provenance !== null &&
            ($provenance->is_manual_override || $provenance->source_key !== $sourceKey);
    }

    private function same(mixed $left, mixed $right): bool
    {
        if (is_numeric($left) && is_numeric($right)) {
            return abs((float) $left - (float) $right) < 0.0005;
        }
        return trim((string) $left) === trim((string) $right);
    }
}
