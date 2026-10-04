<?php

namespace App\Services\Maps\Imports;

use App\Models\HntMap;
use App\Models\HntMapMarker;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class MapMarkerImportDatabaseFingerprint
{
    public function __construct(private readonly MapMarkerImportProtection $protection)
    {
    }

    /**
     * Fingerprint only importable rows in selected maps/categories, plus all three
     * legacy replacement classes in those maps. Protected cash submissions are excluded.
     */
    public function create(string $providerId, array $mapSlugs, array $selections): string
    {
        $mapSlugs = array_values(array_unique($mapSlugs));
        $selections = array_values(array_unique($selections));
        sort($mapSlugs);
        sort($selections);
        if ($mapSlugs === [] || $selections === []) {
            throw new InvalidArgumentException('Empty map import fingerprint selection.');
        }
        $maps = HntMap::query()->whereIn('slug', $mapSlugs)->get()->keyBy('slug');
        if ($maps->count() !== count($mapSlugs)) {
            throw new InvalidArgumentException('Selected HNT map is unavailable.');
        }
        $hasIdentity = Schema::hasColumn('hnt_map_markers', 'source_provider');
        // Spatial duplicate detection also depends on pre-existing HNT markers and
        // markers from other sources, not only on this provider's imported rows.
        $spatialTypes = MapMarkerSpatialDuplicates::selectedTypes($selections);
        $rows = [];
        foreach ($mapSlugs as $slug) {
            $map = $maps[$slug];
            $query = HntMapMarker::query()->where('hnt_map_id', $map->id);
            if ($hasIdentity) {
                $query->where(function ($query) use ($providerId, $spatialTypes): void {
                    $query->where('source_provider', $providerId)
                        ->orWhere(function ($query): void {
                            $query->whereNull('source_provider')->whereIn('type', ['tower', 'bugs', 'wild']);
                        });
                    if ($spatialTypes !== []) {
                        $query->orWhereIn('type', $spatialTypes);
                    }
                });
            } else {
                $query->whereIn('type', array_unique(array_merge(['tower', 'bugs', 'wild'], $spatialTypes)));
            }
            foreach ($query->get() as $marker) {
                if ($this->protection->isProtected($marker)) {
                    continue;
                }
                $legacy = ! $hasIdentity || $marker->source_provider === null;
                if (! $legacy && ! in_array($marker->type, $spatialTypes, true)
                    && ! MapMarkerImportSourcePlan::selected([
                        'type' => $marker->type, 'subtype' => $marker->subtype,
                    ], $selections)) {
                    continue;
                }
                $rows[] = [
                    'map' => $slug,
                    'id' => $marker->id,
                    'legacy' => $legacy,
                    'legacy_key' => $marker->legacy_key,
                    'source_key' => $hasIdentity ? $marker->source_key : null,
                    'source_category' => $hasIdentity ? $marker->source_category : null,
                    'type' => $marker->type,
                    'subtype' => $hasIdentity ? $marker->subtype : null,
                    'source_payload_hash' => $hasIdentity ? $marker->source_payload_hash : null,
                    'x' => number_format($marker->x, 6, '.', ''),
                    'y' => number_format($marker->y, 6, '.', ''),
                    'label_de' => $marker->label_de,
                    'label_en' => $marker->label_en,
                    'status' => $marker->status,
                    'updated_at' => $marker->getRawOriginal('updated_at'),
                ];
            }
        }
        usort($rows, fn (array $a, array $b): int => [$a['map'], $a['id']] <=> [$b['map'], $b['id']]);

        return hash('sha256', json_encode([
            'provider' => $providerId, 'maps' => $mapSlugs,
            'categories' => $selections, 'rows' => $rows,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
