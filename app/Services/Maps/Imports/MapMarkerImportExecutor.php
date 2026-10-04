<?php

namespace App\Services\Maps\Imports;

use App\Models\HntMapMarker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

final class MapMarkerImportExecutor
{
    private const LEGACY_TYPES = ['tower' => 'tower', 'bugs' => 'beetle', 'wild' => 'wild_target'];

    public function __construct(
        private readonly MapMarkerImportSourcePlan $sourcePlan,
        private readonly MapMarkerImportProtection $protection,
        private readonly MapMarkerImportDatabaseFingerprint $databaseFingerprint,
        private readonly MapMarkerSpatialDuplicates $spatialDuplicates,
    )
    {
    }

    /**
     * The provider is read and the source fingerprint is checked before any write transaction.
     * $expectedLegacy is required for every explicitly requested legacy replacement.
     */
    public function execute(
        MapMarkerImportProviderInterface $provider,
        array $mapSlugs,
        array $selections,
        string $mode,
        string $expectedFingerprint,
        string $expectedDatabaseFingerprint,
        array $replaceLegacy = [],
        array $expectedLegacy = [],
    ): array {
        foreach ($replaceLegacy as $type) {
            if (! isset(self::LEGACY_TYPES[$type])) {
                throw new InvalidArgumentException('Unknown legacy replacement type.');
            }
        }
        $replaceLegacy = array_values(array_unique($replaceLegacy));
        $plan = $this->sourcePlan->build($provider, $mapSlugs, $selections, $mode);
        if (! hash_equals($expectedFingerprint, $plan['fingerprint'])) {
            throw new StaleMapMarkerImportPreviewException();
        }
        if (! hash_equals($expectedDatabaseFingerprint, $this->databaseFingerprint->create(
            $provider->id(), array_keys($plan['maps']), $plan['selections'],
        ))) {
            throw new StaleMapMarkerImportDatabaseException();
        }
        foreach ($replaceLegacy as $legacyType) {
            $targetType = self::LEGACY_TYPES[$legacyType];
            $requiredSelections = match ($legacyType) {
                'tower' => ['tower:hunting', 'tower:watch', 'tower:scout'],
                'wild' => ['wild_target:rotjaw', 'wild_target:hellborn'],
                'bugs' => ['beetle'],
            };
            if (! in_array($targetType, $plan['selections'], true)
                && array_diff($requiredSelections, $plan['selections'])) {
                throw new InvalidArgumentException('Legacy replacement requires all source subtypes of its category.');
            }
            foreach ($plan['maps'] as $slug => $mapPlan) {
                if (! isset($expectedLegacy[$slug][$legacyType]) || ! is_int($expectedLegacy[$slug][$legacyType])) {
                    throw new InvalidArgumentException('Missing confirmed legacy count.');
                }
                if (! collect($mapPlan['rows'])->contains(fn ($row) => $row['type'] === $targetType)) {
                    throw new InvalidArgumentException('Legacy replacement requires new source markers on each map.');
                }
            }
        }
        foreach (['source_provider', 'source_key', 'source_category', 'subtype', 'source_payload_hash', 'last_synced_at'] as $column) {
            if (! Schema::hasColumn('hnt_map_markers', $column)) {
                throw new RuntimeException('External marker identity migration is not applied.');
            }
        }
        if (! Schema::hasTable('hnt_map_cash_spot_submissions')) {
            throw new RuntimeException('Cash spot protection table is unavailable.');
        }

        return DB::transaction(function () use ($provider, $plan, $replaceLegacy, $expectedLegacy): array {
            $result = ['provider' => $provider->id(), 'mode' => $plan['mode'], 'maps' => [], 'total' => $this->counts()];
            foreach ($plan['maps'] as $slug => $mapPlan) {
                $map = $mapPlan['map'];
                $categories = [];
                $add = function (string $category, string $count, int $amount = 1) use (&$categories, &$result, $slug): void {
                    $categories[$category] ??= $this->counts();
                    $categories[$category][$count] += $amount;
                    $result['total'][$count] += $amount;
                };
                $spatialCandidates = $this->spatialDuplicates->existing($map->id);
                foreach ($mapPlan['rows'] as $source) {
                    $category = $source['category'];
                    if ($source['type'] === 'cash') {
                        $add($category, 'protected');
                        continue;
                    }
                    $current = HntMapMarker::query()->where('hnt_map_id', $map->id)
                        ->where('source_provider', $provider->id())
                        ->where('source_key', $source['source_key'])->lockForUpdate()->first();
                    if ($current !== null && ($this->protection->isProtected($current)
                        || ! MapMarkerImportSourcePlan::selected(['type' => $current->type, 'subtype' => $current->subtype], $plan['selections']))) {
                        $add($category, 'protected');
                        continue;
                    }
                    if ($current === null && $this->spatialDuplicates->collides($source, $spatialCandidates)) {
                        $add($category, 'duplicate');
                        continue;
                    }
                    if ($current === null) {
                        HntMapMarker::query()->create([
                            'hnt_map_id' => $map->id,
                            'legacy_key' => 'external:'.$provider->id().':'.$source['source_key'],
                            'source_provider' => $provider->id(), 'source_key' => $source['source_key'],
                            'source_category' => $source['source_category'], 'type' => $source['type'],
                            'subtype' => $source['subtype'], 'source_payload_hash' => $source['source_payload_hash'],
                            'last_synced_at' => now(), 'x' => $source['x'], 'y' => $source['y'],
                            'label_de' => $source['label_de'], 'label_en' => $source['label_en'],
                            'status' => 'approved',
                        ]);
                        $this->spatialDuplicates->remember($spatialCandidates, $source);
                        $add($category, 'created');
                        continue;
                    }
                    if ($this->matches($current, $source)) {
                        $add($category, 'unchanged');
                    } elseif ($plan['mode'] === 'add_only') {
                        $add($category, 'skipped_add_only');
                    } else {
                        $current->update([
                            'source_category' => $source['source_category'], 'type' => $source['type'],
                            'subtype' => $source['subtype'], 'source_payload_hash' => $source['source_payload_hash'],
                            'last_synced_at' => now(), 'x' => $source['x'], 'y' => $source['y'],
                            'label_de' => $source['label_de'], 'label_en' => $source['label_en'],
                        ]);
                        $add($category, 'updated');
                    }
                }
                if ($mapPlan['unclassified']) {
                    $add('wild_target:unclassified', 'unclassified', $mapPlan['unclassified']);
                }
                foreach ($mapPlan['outOfBounds'] as $category => $count) {
                    $add($category, 'out_of_bounds', $count);
                }
                $existing = HntMapMarker::query()->where('hnt_map_id', $map->id)
                    ->where('source_provider', $provider->id())->where('type', '!=', 'cash')->get();
                foreach ($existing as $marker) {
                    if (isset($mapPlan['rows'][$marker->source_key])
                        || in_array($marker->source_key, $mapPlan['outOfBoundsKeys'], true)
                        || ! MapMarkerImportSourcePlan::selected(['type' => $marker->type, 'subtype' => $marker->subtype], $plan['selections'])) {
                        continue;
                    }
                    if ($this->protection->isProtected($marker)) {
                        $add($marker->type, 'protected');
                    } else {
                        $add($marker->subtype ? $marker->type.':'.$marker->subtype : $marker->type, 'external_missing');
                    }
                }
                $result['maps'][$slug] = ['name' => $map->name, 'categories' => $categories, 'legacy_deleted' => []];

                foreach ($replaceLegacy as $legacyType) {
                    $query = HntMapMarker::query()->where('hnt_map_id', $map->id)
                        ->where('type', $legacyType)->whereNull('source_provider')->lockForUpdate();
                    $legacy = $query->get();
                    if ($legacy->count() !== $expectedLegacy[$slug][$legacyType]) {
                        throw new StaleMapMarkerImportPreviewException('Legacy marker count changed.');
                    }
                    foreach ($legacy as $marker) {
                        if ($this->protection->isProtected($marker)) {
                            throw new RuntimeException('Protected marker found in legacy replacement.');
                        }
                    }
                    $targetType = self::LEGACY_TYPES[$legacyType];
                    foreach ($mapPlan['rows'] as $source) {
                        if ($source['type'] !== $targetType) {
                            continue;
                        }
                        $imported = HntMapMarker::query()->where('hnt_map_id', $map->id)
                            ->where('source_provider', $provider->id())->where('source_key', $source['source_key'])
                            ->where('type', $targetType)->first();
                        if ($imported === null || $this->protection->isProtected($imported) || ! $this->matches($imported, $source)) {
                            throw new RuntimeException('Expected imported marker is missing; legacy replacement cancelled.');
                        }
                    }
                    foreach ($legacy as $marker) {
                        $marker->delete();
                    }
                    $result['maps'][$slug]['legacy_deleted'][$legacyType] = $legacy->count();
                }
            }
            Log::info('Map marker import executed', [
                'provider' => $provider->id(), 'maps' => array_keys($plan['maps']),
                'categories' => $plan['selections'], 'mode' => $plan['mode'],
                'replace_legacy' => $replaceLegacy, 'counts' => $result['total'],
            ]);
            return $result;
        });
    }

    private function matches(HntMapMarker $current, array $source): bool
    {
        return $current->source_payload_hash === $source['source_payload_hash']
            && $current->source_category === $source['source_category']
            && $current->type === $source['type'] && $current->subtype === $source['subtype']
            && abs($current->x - $source['x']) < 0.000001 && abs($current->y - $source['y']) < 0.000001
            && $current->label_de === $source['label_de'] && $current->label_en === $source['label_en'];
    }

    private function counts(): array
    {
        return array_fill_keys(['created', 'updated', 'unchanged', 'duplicate', 'skipped_add_only', 'unclassified', 'out_of_bounds', 'external_missing', 'protected', 'errors'], 0);
    }
}
