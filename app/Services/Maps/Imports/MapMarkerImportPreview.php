<?php

namespace App\Services\Maps\Imports;

use App\Models\HntMap;
use App\Models\HntMapMarker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class MapMarkerImportPreview
{
    public function __construct(private readonly ExternalMapCoordinateTransformer $transformer)
    {
    }

    /**
     * @param array<int, string> $mapSlugs
     * @param array<int, string> $selections
     * @return array<string, mixed>
     */
    public function create(MapMarkerImportProviderInterface $provider, array $mapSlugs, array $selections, string $mode): array
    {
        if (! in_array($mode, ['sync', 'add_only'], true) || $mapSlugs === [] || $selections === []) {
            throw new InvalidArgumentException('Invalid import preview selection.');
        }

        $mapSlugs = array_values(array_unique($mapSlugs));
        $selections = array_values(array_unique($selections));
        $allowedSelections = array_keys($provider->categories());
        $allowedSelections[] = 'tower';
        $allowedSelections[] = 'wild_target:rotjaw';
        $allowedSelections[] = 'wild_target:hellborn';

        if (array_diff($mapSlugs, array_keys($provider->maps())) || array_diff($selections, $allowedSelections)) {
            throw new InvalidArgumentException('Unknown map or marker category.');
        }

        $identityColumnsReady = Schema::hasColumn('hnt_map_markers', 'source_provider')
            && Schema::hasColumn('hnt_map_markers', 'source_key')
            && Schema::hasColumn('hnt_map_markers', 'source_category')
            && Schema::hasColumn('hnt_map_markers', 'subtype')
            && Schema::hasColumn('hnt_map_markers', 'source_payload_hash');
        $result = [
            'provider' => $provider->id(),
            'provider_name' => $provider->name(),
            'mode' => $mode,
            'identity_columns_ready' => $identityColumnsReady,
            'maps' => [],
            'total' => $this->emptyCounts(),
        ];

        foreach ($mapSlugs as $slug) {
            $map = HntMap::query()->where('slug', $slug)->first();

            if ($map === null) {
                throw new InvalidArgumentException('Selected HNT map is unavailable.');
            }
            $sourceMarkers = $provider->markers($slug);
            $selected = [];
            $unknownSubtypeCount = 0;

            foreach ($sourceMarkers as $source) {
                if (! $this->selected($source, $selections)) {
                    continue;
                }

                if ($source['type'] === 'wild_target' && $source['subtype'] === null) {
                    $unknownSubtypeCount++;
                    continue;
                }

                $point = $this->transformer->transform([$source['y'], $source['x']], $map->width, $map->height);
                $category = $source['subtype'] === null ? $source['type'] : $source['type'].':'.$source['subtype'];
                $normalized = [
                    ...$source,
                    ...$point,
                    'source_provider' => $provider->id(),
                    'category' => $category,
                ];
                $normalized['source_payload_hash'] = $this->hash($normalized);
                $selected[$source['source_key']] = $normalized;
            }

            $existing = $identityColumnsReady
                ? HntMapMarker::query()->where('hnt_map_id', $map->id)
                    ->where('source_provider', $provider->id())
                    ->where('type', '!=', 'cash')
                    ->get()->keyBy('source_key')
                : collect();
            $rows = [];
            $examples = [];

            foreach ($selected as $sourceKey => $source) {
                $category = $source['category'];
                $rows[$category] ??= $this->emptyCounts();
                $current = $existing->get($sourceKey);
                $outcome = $current === null ? 'new' : ($this->matches($current, $source) ? 'unchanged' : 'changed');
                $rows[$category][$outcome]++;

                if (count($examples) < 12) {
                    $examples[] = [
                        'category' => $category,
                        'source_key' => $sourceKey,
                        'x' => $source['x'],
                        'y' => $source['y'],
                    ];
                }
            }

            foreach ($existing as $sourceKey => $marker) {
                if (isset($selected[$sourceKey]) || in_array($sourceKey, $provider->outOfBoundsKeys($slug), true) || ! $this->selected([
                    'type' => $marker->type,
                    'subtype' => $marker->subtype,
                ], $selections)) {
                    continue;
                }

                $category = $marker->subtype === null ? $marker->type : $marker->type.':'.$marker->subtype;
                $rows[$category] ??= $this->emptyCounts();
                $rows[$category]['removed_external']++;
            }

            if ($unknownSubtypeCount > 0) {
                $rows['wild_target:unclassified'] = $this->emptyCounts();
                $rows['wild_target:unclassified']['unclassified'] = $unknownSubtypeCount;
            }

            foreach ($provider->outOfBounds($slug) as $sourceCategory => $count) {
                foreach ($provider->categories() as $category) {
                    if ($category['source_category'] !== $sourceCategory || ! $this->selected($category, $selections)) {
                        continue;
                    }

                    $categoryKey = $category['subtype'] === null ? $category['type'] : $category['type'].':'.$category['subtype'];
                    $rows[$categoryKey] ??= $this->emptyCounts();
                    $rows[$categoryKey]['out_of_bounds'] += $count;
                    break;
                }
            }

            $legacyQuery = HntMapMarker::query()->where('hnt_map_id', $map->id)
                ->whereIn('type', ['tower', 'bugs', 'wild']);

            if ($identityColumnsReady) {
                $legacyQuery->whereNull('source_provider');
            }

            $legacy = $legacyQuery->selectRaw('type, count(*) as count')->groupBy('type')->pluck('count', 'type');
            $mapCounts = $this->emptyCounts();

            foreach ($rows as $counts) {
                foreach ($mapCounts as $key => $_) {
                    $mapCounts[$key] += $counts[$key];
                }
            }

            foreach ($result['total'] as $key => $_) {
                $result['total'][$key] += $mapCounts[$key];
            }

            $result['maps'][$slug] = [
                'name' => $map->name,
                'categories' => $rows,
                'total' => $mapCounts,
                'legacy' => [
                    'tower' => (int) ($legacy['tower'] ?? 0),
                    'bugs' => (int) ($legacy['bugs'] ?? 0),
                    'wild' => (int) ($legacy['wild'] ?? 0),
                ],
                'examples' => $examples,
            ];
        }

        Log::info('Map marker import preview', [
            'provider' => $provider->id(),
            'maps' => $mapSlugs,
            'categories' => $selections,
            'mode' => $mode,
            'marker_count' => $result['total']['new'] + $result['total']['changed'] + $result['total']['unchanged'],
            'unclassified_subtypes' => $result['total']['unclassified'],
            'out_of_bounds' => $result['total']['out_of_bounds'],
        ]);

        return $result;
    }

    /** @param array<string, mixed> $source */
    private function selected(array $source, array $selections): bool
    {
        $type = $source['type'];
        $subtype = $source['subtype'] ?? null;

        return in_array($type, $selections, true)
            || ($subtype !== null && in_array($type.':'.$subtype, $selections, true));
    }

    /** @param array<string, mixed> $source */
    private function hash(array $source): string
    {
        return hash('sha256', json_encode([
            'type' => $source['type'],
            'subtype' => $source['subtype'],
            'x' => $source['x'],
            'y' => $source['y'],
            'label_de' => $source['label_de'],
            'label_en' => $source['label_en'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @param array<string, mixed> $source */
    private function matches(HntMapMarker $current, array $source): bool
    {
        return $current->source_payload_hash === $source['source_payload_hash']
            && $current->source_category === $source['source_category']
            && $current->type === $source['type']
            && $current->subtype === $source['subtype']
            && abs($current->x - $source['x']) < 0.000001
            && abs($current->y - $source['y']) < 0.000001
            && $current->label_de === $source['label_de']
            && $current->label_en === $source['label_en'];
    }

    /** @return array{new: int, changed: int, unchanged: int, removed_external: int, unclassified: int, out_of_bounds: int} */
    private function emptyCounts(): array
    {
        return ['new' => 0, 'changed' => 0, 'unchanged' => 0, 'removed_external' => 0, 'unclassified' => 0, 'out_of_bounds' => 0];
    }
}
