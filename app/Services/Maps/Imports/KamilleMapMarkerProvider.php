<?php

namespace App\Services\Maps\Imports;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class KamilleMapMarkerProvider implements MapMarkerImportProviderInterface
{
    private ?array $typeManifest = null;

    /** @var array<string, array<string, int>> */
    private array $outOfBounds = [];

    /** @var array<string, array<int, string>> */
    private array $outOfBoundsKeys = [];

    private const BASE_URL = 'https://hunt.kamille.ovh/maps/cache/';

    private const MAPS = [
        'stillwater-bayou' => ['id' => 1, 'name' => 'Stillwater Bayou'],
        'lawson-delta' => ['id' => 2, 'name' => 'Lawson Delta'],
        'desalle' => ['id' => 3, 'name' => 'DeSalle'],
        'mammons-gulch' => ['id' => 4, 'name' => "Mammon's Gulch"],
    ];

    private const CATEGORIES = [
        'easter_egg' => ['source_category' => 'easter_eggs', 'type' => 'easter_egg', 'subtype' => null],
        'wild_target' => ['source_category' => 'wild_targets', 'type' => 'wild_target', 'subtype' => null],
        'beast' => ['source_category' => 'brutes', 'type' => 'beast', 'subtype' => null],
        'beetle' => ['source_category' => 'beetles', 'type' => 'beetle', 'subtype' => null],
        'tower:hunting' => ['source_category' => 'towers', 'type' => 'tower', 'subtype' => 'hunting'],
        'tower:watch' => ['source_category' => 'big_towers', 'type' => 'tower', 'subtype' => 'watch'],
        'tower:scout' => ['source_category' => 'scout_towers', 'type' => 'tower', 'subtype' => 'scout'],
        'workbench' => ['source_category' => 'workbenches', 'type' => 'workbench', 'subtype' => null],
    ];

    public function id(): string
    {
        return 'kamille';
    }

    public function name(): string
    {
        return 'Kamille / hunt.kamille.ovh';
    }

    public function maps(): array
    {
        return self::MAPS;
    }

    public function categories(): array
    {
        return self::CATEGORIES;
    }

    public function markers(string $mapSlug): array
    {
        $map = self::MAPS[$mapSlug] ?? throw new SourceFormatException('Unknown external map.');
        $types = $this->typeManifest ?? $this->loadJson('poi-types.json');

        if (! is_array($types)) {
            throw new SourceFormatException('Invalid category manifest.');
        }

        $this->typeManifest = $types;

        foreach (self::CATEGORIES as $category) {
            $sourceKey = $category['source_category'];
            $typeKey = $category['type'] === 'tower'
                ? ['towers' => 'tower', 'big_towers' => 'big_tower', 'scout_towers' => 'scout_tower'][$sourceKey]
                : ['easter_eggs' => 'easter_egg', 'wild_targets' => 'wild_target', 'brutes' => 'brute', 'beetles' => 'beetle', 'workbenches' => 'workbench'][$sourceKey];

            if (($types[$typeKey]['categories'] ?? null) !== $sourceKey) {
                throw new SourceFormatException('External category format changed.');
            }
        }

        $data = $this->loadJson('data-'.$map['id'].'.json');

        return $this->parseMap($mapSlug, $data);
    }

    /** @return array<int, array{source_key: string, source_category: string, type: string, subtype: ?string, x: float, y: float, label_de: ?string, label_en: ?string}> */
    public function parseMap(string $mapSlug, mixed $data): array
    {
        $map = self::MAPS[$mapSlug] ?? throw new SourceFormatException('Unknown external map.');

        if (! is_array($data) || ($data['i'] ?? null) !== $map['id'] || ($data['n'] ?? null) !== $map['name']) {
            throw new SourceFormatException('External map format changed.');
        }

        $markers = [];
        $seen = [];
        $this->outOfBounds[$mapSlug] = [];
        $this->outOfBoundsKeys[$mapSlug] = [];

        foreach (self::CATEGORIES as $category) {
            $sourceCategory = $category['source_category'];
            $rows = $data[$sourceCategory] ?? null;

            if (! is_array($rows) || ! array_is_list($rows)) {
                throw new SourceFormatException('External marker category format changed.');
            }

            foreach ($rows as $row) {
                $id = is_array($row) ? ($row['id'] ?? null) : null;
                $point = is_array($row) ? ($row['c'] ?? null) : null;

                if (! is_string($id) || ! preg_match('/^[A-Za-z0-9]{12}$/D', $id)
                    || isset($seen[$id]) || ! is_array($point) || count($point) !== 2
                    || ! is_numeric($point[0]) || ! is_numeric($point[1])
                    || ! is_finite((float) $point[0]) || ! is_finite((float) $point[1])) {
                    throw new SourceFormatException('External marker identity or coordinate format changed.');
                }

                $seen[$id] = true;

                if ($point[0] < 0 || $point[0] > ExternalMapCoordinateTransformer::SOURCE_SIZE
                    || $point[1] < 0 || $point[1] > ExternalMapCoordinateTransformer::SOURCE_SIZE) {
                    $this->outOfBounds[$mapSlug][$sourceCategory] = ($this->outOfBounds[$mapSlug][$sourceCategory] ?? 0) + 1;
                    $this->outOfBoundsKeys[$mapSlug][] = $id;
                    Log::warning('Map import skipped out-of-bounds marker', [
                        'provider' => $this->id(), 'map' => $mapSlug,
                        'category' => $sourceCategory, 'source_key' => $id,
                    ]);
                    continue;
                }

                $subtype = $category['subtype'];

                if ($category['type'] === 'wild_target') {
                    $boss = $row['boss'] ?? null;
                    $subtype = in_array($boss, ['rotjaw', 'hellborn'], true) ? $boss : null;
                }

                $markers[] = [
                    'source_key' => $id,
                    'source_category' => $sourceCategory,
                    'type' => $category['type'],
                    'subtype' => $subtype,
                    'x' => (float) $point[1],
                    'y' => (float) $point[0],
                    'label_de' => null,
                    'label_en' => null,
                ];
            }
        }

        $known = array_column(self::CATEGORIES, 'source_category');

        foreach ($data as $key => $value) {
            if (is_array($value) && ! in_array($key, $known, true)
                && ! in_array($key, ['spawns', 'armories', 'compounds', 'extractions', 'bounty_clash_extractions', 'crashed_balloons', 'melee_weapons', 'cash_registers', 'supply_points', 'postal_supplies', 'clockmaker_supplies', 'firefighter_supplies', 'medical_supplies', 'military_supplies', 'bileweaver_compounds'], true)) {
                Log::warning('Map import skipped unknown source category', ['provider' => $this->id(), 'map' => $mapSlug, 'category' => $key]);
            }
        }

        return $markers;
    }

    public function outOfBounds(string $mapSlug): array
    {
        return $this->outOfBounds[$mapSlug] ?? [];
    }

    public function outOfBoundsKeys(string $mapSlug): array
    {
        return $this->outOfBoundsKeys[$mapSlug] ?? [];
    }

    private function loadJson(string $file): mixed
    {
        try {
            $response = Http::acceptJson()->connectTimeout(4)->timeout(12)->get(self::BASE_URL.$file);
        } catch (ConnectionException $exception) {
            throw new SourceUnavailableException('External marker source unavailable.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new SourceUnavailableException('External marker source unavailable (HTTP '.$response->status().').');
        }

        try {
            return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new SourceFormatException('External marker source returned invalid JSON.', previous: $exception);
        }
    }
}
