<?php

namespace App\Support\Maps;

final class MapMarkerRegistry
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $definitions = [
            'compound' => self::item('Compound', 'Compound', '#d6a84f', null, 26, false, true),
            'boss' => self::item('Boss', 'Boss', '#b8463a', 'boss', 32, false, true),
            'spawn' => self::item('Spawn', 'Spawn', '#6d9dc5', 'spawn', 24, false, true),
            'supply' => self::item('Vorrat', 'Supply', '#69a878', 'supply', 18, false, true),
            'extract' => self::item('Ausgang', 'Extraction', '#d9d2c2', null, 26, false, true),
            'cash' => self::item('Kasse', 'Cash', '#c2ad4a', 'cash', 18, false, true),
            'tower' => self::item('Türme', 'Towers', '#9b7653', 'tower', 24, true, true, 'structures', [
                'hunting' => ['de' => 'Jagdturm', 'en' => 'Hunting Tower'],
                'watch' => ['de' => 'Wachturm', 'en' => 'Watch Tower'],
                'scout' => ['de' => 'Spähturm', 'en' => 'Scout Tower'],
            ]),
            'bugs' => self::item('Käferbäume', 'Bugs', '#7d9260', 'bugs', 24, false, true),
            'wild' => self::item('Wildes Ziel', 'Wild Target', '#9c6f5d', 'wild', 24, false, true),
            'tarot' => self::item('Tarot', 'Tarot', '#876f9e', null, 26, false, false),
            'easter_egg' => self::item('Easter Eggs', 'Easter Eggs', '#57d98a', null, 16, true, true, 'discoveries'),
            'workbench' => self::item('Werkbänke', 'Workbenches', '#38bdf8', null, 16, true, true, 'structures'),
            'beast' => self::item('Bestien', 'Brutes', '#f0883e', null, 20, true, true, 'creatures'),
            'beetle' => self::item('Käfer', 'Beetles', '#b06cff', null, 16, true, true, 'creatures'),
            'wild_target' => self::item('Wilde Ziele', 'Wild Targets', '#ff5a5a', null, 20, true, true, 'creatures', [
                'rotjaw' => ['de' => 'Rotjaw', 'en' => 'Rotjaw'],
                'hellborn' => ['de' => 'Hellborn', 'en' => 'Hellborn'],
            ]),
        ];

        foreach ($definitions as $key => &$definition) {
            $definition['key'] = $key;
            $definition['type'] = $key;
        }

        return $definitions;
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** @return array<int, string> */
    public static function seoKeys(): array
    {
        return array_keys(array_filter(self::all(), fn (array $item): bool => $item['seo_relevant']));
    }

    /** @return array<int, string> */
    public static function visibleKeys(): array
    {
        return array_keys(array_filter(self::all(), fn (array $item): bool => $item['visible']));
    }

    /** @return array<string, array{color: string, icon_key: ?string, size: int}> */
    public static function presentation(): array
    {
        return array_map(fn (array $item): array => [
            'color' => $item['color'],
            'icon_key' => $item['icon_key'],
            'size' => $item['size'],
        ], self::all());
    }

    /** @return array<string, mixed> */
    private static function item(string $de, string $en, string $color, ?string $icon, int $size, bool $importable, bool $seo, ?string $group = null, array $subtypes = []): array
    {
        return [
            'label_de' => $de,
            'label_en' => $en,
            'group' => $group,
            'subtypes' => $subtypes,
            'color' => $color,
            'icon_key' => $icon,
            'size' => $size,
            'importable' => $importable,
            'seo_relevant' => $seo,
            'visible' => true,
        ];
    }
}
