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

    private const MODE_SUFFIXES = [
        'waxedfragcharge' => ['waxedfrag'],

        'compactbolt' => ['compactboltbasic', 'basic'],
        'incendiarybolt' => ['incendiary'],
        'explosivebolt' => ['explosive'],

        'bolt' => ['boltbasic'],
        'shotbolt' => ['boltshotbolt'],
        'steelbolt' => ['boltsteelbolt'],

        'poisonbolt' => ['compactboltpoison'],
        'chaosbolt' => ['compactboltchaos'],
        'chokebolt' => ['compactboltchoke'],
        'dragonbolt' => ['compactboltdragon'],
        'revivebolt' => ['compactboltrevive'],

        'fmj' => ['fmj'],
    ];

    public function match(EquipmentAmmo $ammo, array $modes): array
    {
        $type = $this->normalize((string) $ammo->ammo_type);
        $rawName = $this->normalize((string) $ammo->name);
        $name = self::ALIASES[$rawName] ?? $rawName;
        $sourceId = data_get($ammo->facts, 'bayou_index.source_ammo_id');

        $matches = array_values(array_filter(
            $modes,
            function (array $mode) use ($type, $name, $sourceId): bool {
                if (! $this->classMatches($type, $mode)) {
                    return false;
                }

                if ($sourceId !== null) {
                    return ($mode['id'] ?? null) === $sourceId;
                }

                return $this->normalize((string) ($mode['ammo_name'] ?? '')) === $name;
            }
        ));

        if (count($matches) === 1) {
            return [
                'mode' => $matches[0],
                'confidence' => $sourceId !== null ? 100 : 95,
                'reason' => null,
            ];
        }

        if ($sourceId === null && count($matches) === 0) {
            $fallback = $this->matchKnownModeSuffix($rawName, $type, $modes);

            if ($fallback !== null) {
                return [
                    'mode' => $fallback,
                    'confidence' => 95,
                    'reason' => null,
                ];
            }
        }

        return [
            'mode' => null,
            'confidence' => 0,
            'reason' => count($matches) > 1
                ? 'Multiple public ammo modes share this exact identity.'
                : 'No unique public ammo ID/class/name match.',
        ];
    }

    private function matchKnownModeSuffix(
        string $ammoName,
        string $type,
        array $modes
    ): ?array {
        $suffixes = self::MODE_SUFFIXES[$ammoName] ?? null;

        if ($suffixes === null) {
            return null;
        }

        $matches = array_values(array_filter(
            $modes,
            function (array $mode) use ($suffixes, $type): bool {
                if (! $this->classMatches($type, $mode)) {
                    return false;
                }

                $sourceName = $this->normalize((string) ($mode['name'] ?? ''));

                foreach ($suffixes as $suffix) {
                    if ($sourceName === $suffix || str_ends_with($sourceName, $suffix)) {
                        return true;
                    }
                }

                return false;
            }
        ));

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function classMatches(string $type, array $mode): bool
    {
        $sourceClass = $this->normalize((string) ($mode['ammo_class'] ?? ''));

        return $sourceClass === '' || $type === '' || $sourceClass === $type;
    }

    private function normalize(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $value));
    }
}
