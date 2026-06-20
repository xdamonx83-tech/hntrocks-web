<?php

namespace Database\Seeders;

use App\Models\HntMap;
use App\Models\HntMapMarker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class HntMapsSeeder extends Seeder
{
    private const MAPS = [
        'stillwater-bayou' => ['name' => 'Stillwater Bayou', 'file' => 'stillwater-bayou.json', 'sort_order' => 0],
        'lawson-delta' => ['name' => 'Lawson Delta', 'file' => 'lawson-delta.json', 'sort_order' => 1],
        'desalle' => ['name' => 'DeSalle', 'file' => 'desalle.json', 'sort_order' => 2],
        'mammons-gulch' => ['name' => "Mammon's Gulch", 'file' => 'mammons-gulch.json', 'sort_order' => 3],
    ];

    public function run(): void
    {
        $inserted = 0;
        $updated = 0;

        DB::transaction(function () use (&$inserted, &$updated): void {
            foreach (self::MAPS as $slug => $mapData) {
                $map = HntMap::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $mapData['name'],
                        'width' => 2048,
                        'height' => 2048,
                        'image_path' => "assets/hnt/maps/{$slug}/map.webp",
                        'lines_path' => "assets/hnt/maps/{$slug}/lines.png",
                        'sort_order' => $mapData['sort_order'],
                        'is_active' => true,
                    ]
                );

                foreach ($this->markers($mapData['file']) as $index => $markerData) {
                    $labels = is_array($markerData['label'] ?? null) ? $markerData['label'] : [];
                    $sourceId = is_numeric($markerData['source_id'] ?? null)
                        ? (int) $markerData['source_id']
                        : null;
                    $sourceImage = is_string($markerData['source_image'] ?? null)
                        ? $markerData['source_image']
                        : null;
                    $legacyKey = $sourceId !== null
                        ? 'source:'.$sourceId
                        : $this->hashedLegacyKey($markerData, $labels, $sourceImage);

                    $identity = ['hnt_map_id' => $map->id, 'legacy_key' => $legacyKey];
                    $existingMarker = HntMapMarker::query()->where($identity)->first();
                    $values = [
                        'source_id' => $sourceId,
                        'type' => (string) $markerData['type'],
                        'x' => (float) $markerData['x'],
                        'y' => (float) $markerData['y'],
                        'label_de' => $this->nullableString($labels['de'] ?? null),
                        'label_en' => $this->nullableString($labels['en'] ?? null),
                        'sort_order' => $index,
                        'meta' => null,
                    ];

                    if ($existingMarker === null) {
                        $values['source_image'] = $this->nullableString($sourceImage);
                        $values['status'] = 'approved';
                    }

                    $marker = HntMapMarker::updateOrCreate($identity, $values);

                    $marker->wasRecentlyCreated ? $inserted++ : $updated++;
                }
            }
        });

        $this->command?->info(sprintf(
            'HNT Maps seeded: %d maps, %d markers inserted, %d markers updated, %d markers total.',
            count(self::MAPS),
            $inserted,
            $updated,
            $inserted + $updated,
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function markers(string $file): array
    {
        $path = resource_path('data/maps/'.$file);

        if (! File::isFile($path)) {
            throw new RuntimeException("HNT Maps seed file missing: {$path}");
        }

        $decoded = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || ! is_array($decoded['markers'] ?? null)) {
            throw new RuntimeException("HNT Maps seed file is invalid: {$path}");
        }

        return $decoded['markers'];
    }

    /**
     * @param  array<string, mixed>  $marker
     * @param  array<string, mixed>  $labels
     */
    private function hashedLegacyKey(array $marker, array $labels, ?string $sourceImage): string
    {
        $identity = [
            'type' => (string) ($marker['type'] ?? ''),
            'x' => (string) ($marker['x'] ?? ''),
            'y' => (string) ($marker['y'] ?? ''),
            'label_de' => $this->nullableString($labels['de'] ?? null),
            'label_en' => $this->nullableString($labels['en'] ?? null),
            'source_image' => $this->nullableString($sourceImage),
        ];

        return 'hash:'.sha1(json_encode(
            $identity,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
