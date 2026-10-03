<?php

namespace App\Services\Equipment;

use App\Models\EquipmentAmmo;

class BayouAmmoMatcher
{
    private const ALIASES = [
        'stock' => 'basic',
        'fmj' => 'fullmetaljacket',
        'hv' => 'highvelocity',
    ];

    public function match(EquipmentAmmo $ammo, array $modes): array
    {
        $type = $this->normalize((string) $ammo->ammo_type);
        $name = $this->normalize((string) $ammo->name);
        $name = self::ALIASES[$name] ?? $name;
        $sourceId = data_get($ammo->facts, 'bayou_index.source_ammo_id');
        $matches = array_values(array_filter($modes, function (array $mode) use ($type, $name, $sourceId): bool {
            if ($this->normalize((string) ($mode['ammo_class'] ?? '')) !== $type) return false;
            if ($sourceId !== null) return $mode['id'] === $sourceId;
            return $this->normalize((string) ($mode['ammo_name'] ?? '')) === $name;
        }));
        if (count($matches) === 1) return [
            'mode' => $matches[0], 'confidence' => $sourceId !== null ? 100 : 95,
            'reason' => null,
        ];
        return ['mode' => null, 'confidence' => 0,
            'reason' => count($matches) > 1 ? 'Multiple public ammo modes share this exact identity.'
                : 'No unique public ammo ID/class/name match.'];
    }

    private function normalize(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $value));
    }
}
