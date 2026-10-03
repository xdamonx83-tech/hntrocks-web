<?php

namespace App\Services\Maps\Imports;

use App\Models\HntMap;
use InvalidArgumentException;

final class MapMarkerImportSourcePlan
{
    public function __construct(private readonly ExternalMapCoordinateTransformer $transformer)
    {
    }

    public function build(MapMarkerImportProviderInterface $provider, array $mapSlugs, array $selections, string $mode): array
    {
        $mapSlugs = array_values(array_unique($mapSlugs));
        $selections = array_values(array_unique($selections));
        sort($mapSlugs);
        sort($selections);
        $allowed = array_keys($provider->categories());
        foreach ($provider->categories() as $category) {
            $allowed[] = $category['type'];
        }
        // Wild targets can be selected by their confirmed technical subtype.
        $allowed = array_merge($allowed, ['wild_target:rotjaw', 'wild_target:hellborn']);
        if (! in_array($mode, ['sync', 'add_only'], true) || $mapSlugs === [] || $selections === []
            || array_diff($mapSlugs, array_keys($provider->maps())) || array_diff($selections, $allowed)) {
            throw new InvalidArgumentException('Unknown map, category or import mode.');
        }

        $maps = [];
        $fingerprintRows = [];
        foreach ($mapSlugs as $slug) {
            $map = HntMap::query()->where('slug', $slug)->first();
            if ($map === null) {
                throw new InvalidArgumentException('Selected HNT map is unavailable.');
            }
            $rows = [];
            $unclassified = 0;
            $fingerprintRows[$slug] = [];
            foreach ($provider->markers($slug) as $source) {
                if (! self::selected($source, $selections)) {
                    continue;
                }
                if ($source['type'] === 'wild_target' && $source['subtype'] === null) {
                    $unclassified++;
                    $fingerprintRows[$slug][] = [$source['source_key'], 'unclassified'];
                    continue;
                }
                $point = $this->transformer->transform([$source['y'], $source['x']], $map->width, $map->height);
                $normalized = [...$source, ...$point, 'source_provider' => $provider->id()];
                $normalized['category'] = $source['subtype'] === null ? $source['type'] : $source['type'].':'.$source['subtype'];
                $normalized['source_payload_hash'] = hash('sha256', json_encode([
                    'type' => $source['type'], 'subtype' => $source['subtype'],
                    'x' => $normalized['x'], 'y' => $normalized['y'],
                    'label_de' => $source['label_de'], 'label_en' => $source['label_en'],
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                if (isset($rows[$source['source_key']])) {
                    throw new SourceFormatException('Duplicate external source key.');
                }
                $rows[$source['source_key']] = $normalized;
                $fingerprintRows[$slug][] = [$source['source_key'], $source['source_category'], $normalized['source_payload_hash']];
            }
            ksort($rows);
            sort($fingerprintRows[$slug]);
            $outOfBounds = [];
            foreach ($provider->outOfBounds($slug) as $sourceCategory => $count) {
                foreach ($provider->categories() as $category) {
                    if ($category['source_category'] === $sourceCategory && self::selected($category, $selections)) {
                        $key = $category['subtype'] === null ? $category['type'] : $category['type'].':'.$category['subtype'];
                        $outOfBounds[$key] = ($outOfBounds[$key] ?? 0) + $count;
                        break;
                    }
                }
            }
            $outOfBoundsKeys = $provider->outOfBoundsKeys($slug);
            sort($outOfBoundsKeys);
            $fingerprintRows[$slug][] = ['out_of_bounds', $outOfBoundsKeys];
            $maps[$slug] = compact('map', 'rows', 'unclassified', 'outOfBounds', 'outOfBoundsKeys');
        }
        return [
            'provider' => $provider->id(), 'mode' => $mode, 'selections' => $selections,
            'maps' => $maps,
            'fingerprint' => hash('sha256', json_encode([$provider->id(), $mode, $selections, $fingerprintRows], JSON_THROW_ON_ERROR)),
        ];
    }

    public static function selected(array $source, array $selections): bool
    {
        $type = $source['type'];
        $subtype = $source['subtype'] ?? null;
        return in_array($type, $selections, true)
            || ($subtype !== null && in_array($type.':'.$subtype, $selections, true));
    }
}
