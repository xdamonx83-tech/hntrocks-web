<?php

namespace App\Services\Equipment;

use App\Models\EquipmentSourceSnapshot;

class EquipmentSnapshotDiffService
{
    public function diff(EquipmentSourceSnapshot $before, EquipmentSourceSnapshot $after): array
    {
        if ($before->equipment_item_id !== $after->equipment_item_id || $before->source_key !== $after->source_key) {
            throw new \InvalidArgumentException('Snapshots must belong to the same item and source.');
        }
        $old = $this->fields($before->normalized_payload ?? []);
        $new = $this->fields($after->normalized_payload ?? []);
        $changes = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
            if (($old[$field] ?? null) === ($new[$field] ?? null)) continue;
            $changes[] = [
                'field' => $field,
                'old_value' => $old[$field] ?? null,
                'new_value' => $new[$field] ?? null,
                'source_key' => $after->source_key,
                'from_snapshot_id' => $before->id,
                'to_snapshot_id' => $after->id,
            ];
        }
        return $changes;
    }

    private function fields(array $payload): array
    {
        $fields = [];
        foreach (['family', 'price', 'slot_size', 'ammo_type', 'loaded', 'reserve'] as $field) {
            if (array_key_exists($field, $payload)) $fields[$field] = $payload[$field];
        }
        foreach ((array) ($payload['stats'] ?? []) as $key => $value) {
            if (is_numeric($value)) $fields['stat.'.$key] = $value;
        }
        return $fields;
    }
}
