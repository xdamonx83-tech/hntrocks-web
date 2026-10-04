<?php

namespace App\Services\Maps\Imports;

use App\Models\HntMapMarker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

final class MapMarkerImportPreview
{
    public function __construct(
        private readonly MapMarkerImportSourcePlan $sourcePlan,
        private readonly MapMarkerImportProtection $protection,
        private readonly MapMarkerImportDatabaseFingerprint $databaseFingerprint,
        private readonly MapMarkerSpatialDuplicates $spatialDuplicates,
    )
    {
    }

    /**
     * @param array<int, string> $mapSlugs
     * @param array<int, string> $selections
     * @return array<string, mixed>
     */
    public function create(MapMarkerImportProviderInterface $provider, array $mapSlugs, array $selections, string $mode): array
    {
        $plan = $this->sourcePlan->build($provider, $mapSlugs, $selections, $mode);
        $mapSlugs = array_keys($plan['maps']);
        $selections = $plan['selections'];

        $identityColumnsReady = Schema::hasColumn('hnt_map_markers', 'source_provider')
            && Schema::hasColumn('hnt_map_markers', 'source_key')
            && Schema::hasColumn('hnt_map_markers', 'source_category')
            && Schema::hasColumn('hnt_map_markers', 'subtype')
            && Schema::hasColumn('hnt_map_markers', 'source_payload_hash');
        $result = [
            'provider' => $provider->id(),
            'provider_name' => $provider->name(),
            'mode' => $mode,
            'can_replace_supply' => $mode === 'sync' && MapMarkerImportSourcePlan::completeSupplySelection($selections),
            'identity_columns_ready' => $identityColumnsReady,
            'fingerprint' => $plan['fingerprint'],
            'database_fingerprint' => $this->databaseFingerprint->create($provider->id(), $mapSlugs, $selections),
            'maps' => [],
            'total' => $this->emptyCounts(),
        ];

        foreach ($plan['maps'] as $slug => $mapPlan) {
            $map = $mapPlan['map'];
            $selected = $mapPlan['rows'];
            $unknownSubtypeCount = $mapPlan['unclassified'];

            $existing = $identityColumnsReady
                ? HntMapMarker::query()->where('hnt_map_id', $map->id)
                    ->where('source_provider', $provider->id())
                    ->get()->keyBy('source_key')
                : collect();
            $rows = [];
            $examples = [];
            $spatialCandidates = $this->spatialDuplicates->existing($map->id);

            foreach ($selected as $sourceKey => $source) {
                $category = $source['category'];
                $rows[$category] ??= $this->emptyCounts();
                $current = $existing->get($sourceKey);
                if ($current === null && $this->spatialDuplicates->collides($source, $spatialCandidates)) {
                    $outcome = 'duplicate';
                } else {
                    $outcome = $current === null ? 'new' : ($this->protection->isProtected($current) ? 'protected' : ($this->matches($current, $source) ? 'unchanged' : 'changed'));
                    if ($current === null) {
                        $this->spatialDuplicates->remember($spatialCandidates, $source);
                    }
                }
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
                if ($this->protection->isProtected($marker) || isset($selected[$sourceKey]) || in_array($sourceKey, $mapPlan['outOfBoundsKeys'], true) || ! MapMarkerImportSourcePlan::selected([
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

            foreach ($mapPlan['outOfBounds'] as $categoryKey => $count) {
                $rows[$categoryKey] ??= $this->emptyCounts();
                $rows[$categoryKey]['out_of_bounds'] += $count;
            }

            $legacyQuery = HntMapMarker::query()->where('hnt_map_id', $map->id)
                ->whereIn('type', ['tower', 'bugs', 'wild', 'supply']);

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
                    'supply' => (int) ($legacy['supply'] ?? 0),
                ],
                'supply_replacement' => $result['can_replace_supply']
                    ? $this->supplyReplacementPreview($map->id, $selected, $existing)
                    : null,
                'protected_existing' => $this->protection->countsForMap($map->id),
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

    /**
     * Additional read-only estimate for explicit legacy supply replacement.
     * The ordinary preview still shows what an import WITHOUT replacement does.
     */
    private function supplyReplacementPreview(int $mapId, array $selected, $existing): array
    {
        $candidates = $this->spatialDuplicates->existing($mapId, true);
        $counts = ['new' => 0, 'duplicate' => 0, 'unchanged' => 0, 'changed' => 0, 'protected' => 0, 'protected_legacy' => 0];

        foreach ($selected as $sourceKey => $source) {
            if ($source['type'] !== 'supply') {
                continue;
            }
            $current = $existing->get($sourceKey);
            if ($current === null && $this->spatialDuplicates->collides($source, $candidates)) {
                $counts['duplicate']++;
                continue;
            }
            if ($current === null) {
                $counts['new']++;
                $this->spatialDuplicates->remember($candidates, $source);
            } elseif ($this->protection->isProtected($current)) {
                $counts['protected']++;
            } elseif ($this->matches($current, $source)) {
                $counts['unchanged']++;
            } else {
                $counts['changed']++;
            }
        }

        $legacy = HntMapMarker::query()->where('hnt_map_id', $mapId)
            ->where('type', 'supply')->whereNull('source_provider')->get();
        $counts['protected_legacy'] = $legacy->filter(
            fn (HntMapMarker $marker): bool => $this->protection->isProtected($marker)
        )->count();

        return $counts;
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

    /** @return array{new: int, changed: int, unchanged: int, removed_external: int, unclassified: int, out_of_bounds: int, protected: int} */
    private function emptyCounts(): array
    {
        return ['new' => 0, 'changed' => 0, 'unchanged' => 0, 'duplicate' => 0, 'removed_external' => 0, 'unclassified' => 0, 'out_of_bounds' => 0, 'protected' => 0];
    }
}
