<?php
namespace App\Services\Equipment;

class EquipmentDescriptionGenerator
{
    private const DE_CLASSES = [
        'Rifle'=>'Gewehr', 'Pistol'=>'Pistole', 'Shotgun'=>'Schrotflinte',
        'Melee'=>'Nahkampfwaffe', 'Sword'=>'Nahkampfwaffe', 'Bow'=>'Bogen', 'Crossbow'=>'Armbrust',
    ];
    private const DE_AMMO = [
        'Compact'=>'Kompakt', 'Medium'=>'Mittel', 'Long'=>'Lang',
        'Shell'=>'Schrot', 'Shotgun'=>'Schrot', 'Special'=>'Spezial',
    ];
    private const DE_CATEGORIES = [
        'Melee'=>'Nahkampf', 'Melee / Throwable'=>'Nahkampf und Wurf', 'Healing'=>'Heilung',
        'Utility'=>'Hilfsmittel', 'Traps'=>'Fallen', 'Deception'=>'Täuschung',
        'Illumination'=>'Beleuchtung', 'Sidearm'=>'Seitenwaffe',
        'Restoration'=>'Wiederherstellung', 'Explosive'=>'Sprengstoff',
        'Recon'=>'Aufklärung', 'Poison'=>'Gift', 'Fire'=>'Feuer',
    ];

    public function generate(array $row, string $locale = 'en'): string
    {
        $name = (string) ($row['name'] ?? 'Equipment');
        $type = (string) ($row['_item_type'] ?? 'item');
        $class = (string) ($row['weaponType'] ?? $row['category'] ?? $type);
        $category = (string) ($row['category'] ?? $class);
        $stats = (new EquipmentStatCatalog)->values($row);
        $variant = abs(crc32($name)) % 2;

        if ($locale === 'de') {
            $classLabel = self::DE_CLASSES[$class] ?? $class;
            $categoryLabel = self::DE_CATEGORIES[$category] ?? $category;
            $opening = match ($type) {
                'weapon' => $variant === 0 ? "$name gehört zur Klasse $classLabel." : "$name wird als $classLabel geführt.",
                'tool' => $variant === 0 ? "$name gehört zur Werkzeugkategorie $categoryLabel." : "Die Werkzeugkategorie von $name ist $categoryLabel.",
                'consumable' => $variant === 0 ? "$name ist ein Verbrauchsgegenstand der Kategorie $categoryLabel." : "$name wird als Verbrauchsgegenstand der Kategorie $categoryLabel geführt.",
                default => "$name gehört zur Kategorie $categoryLabel.",
            };
            $parts = [$opening];
            if ($type === 'weapon' && ! empty($row['caliber'])) {
                $ammo = self::DE_AMMO[$row['caliber']] ?? (string) $row['caliber'];
                $parts[] = "Der Munitionstyp ist $ammo.";
            }
            if (isset($stats['damage'], $stats['muzzleVelocity'])) $parts[] = "Erfasst sind {$stats['damage']} Schaden und {$stats['muzzleVelocity']} m/s Mündungsgeschwindigkeit.";
            elseif (isset($stats['damage'])) $parts[] = "Der angegebene Schaden beträgt {$stats['damage']}.";
            elseif (isset($stats['throwDamage'])) $parts[] = "Der angegebene Wurfschaden beträgt {$stats['throwDamage']}.";
            if (isset($stats['magazine']) && $stats['magazine'] > 0) $parts[] = "Geladen sind {$stats['magazine']} Schuss.";
            if (isset($stats['reload'])) $parts[] = "Die Nachladezeit beträgt {$stats['reload']} Sekunden.";
            return implode(' ', $parts);
        }

        $opening = match ($type) {
            'weapon' => $variant === 0 ? "$name is classified as $class." : "$name belongs to the $class class.",
            'tool' => $variant === 0 ? "$name is a tool in the $category category." : "$name is listed in the $category tool category.",
            'consumable' => $variant === 0 ? "$name is a consumable in the $category category." : "$name is listed as a $category consumable.",
            default => "$name belongs to the $category category.",
        };
        $parts = [$opening];
        if ($type === 'weapon' && ! empty($row['caliber'])) $parts[] = "Its ammunition type is {$row['caliber']}.";
        if (isset($stats['damage'], $stats['muzzleVelocity'])) $parts[] = "The listed values are {$stats['damage']} damage and {$stats['muzzleVelocity']} m/s muzzle velocity.";
        elseif (isset($stats['damage'])) $parts[] = "Listed damage is {$stats['damage']}.";
        elseif (isset($stats['throwDamage'])) $parts[] = "Listed throw damage is {$stats['throwDamage']}.";
        if (isset($stats['magazine']) && $stats['magazine'] > 0) $parts[] = "It carries {$stats['magazine']} loaded rounds.";
        if (isset($stats['reload'])) $parts[] = "Reload time is {$stats['reload']} seconds.";
        return implode(' ', $parts);
    }
}
