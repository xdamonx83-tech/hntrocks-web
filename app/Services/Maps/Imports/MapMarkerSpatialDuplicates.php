<?php

namespace App\Services\Maps\Imports;

use App\Models\HntMapMarker;

/**
 * Import-only safety gate for already mapped HNT spawns, exits and supplies.
 * Existing markers are never claimed, rewritten or deleted by proximity.
 */
final class MapMarkerSpatialDuplicates
{
    private const RADII = ['spawn' => 7.0, 'extract' => 7.0, 'supply' => 6.0];

    /** @return array<int, array<string, mixed>> */
    public function existing(int $mapId): array
    {
        return HntMapMarker::query()->where('hnt_map_id', $mapId)
            ->whereIn('type', array_keys(self::RADII))
            ->get(['type', 'subtype', 'x', 'y', 'source_provider', 'source_key'])
            ->map(fn (HntMapMarker $marker): array => [
                'type' => $marker->type,
                'subtype' => $marker->subtype,
                'x' => (float) $marker->x,
                'y' => (float) $marker->y,
                'source_provider' => $marker->source_provider,
                'source_key' => $marker->source_key,
            ])->all();
    }

    public function collides(array $source, array $candidates): bool
    {
        $radius = self::RADII[$source['type']] ?? null;
        if ($radius === null) {
            return false;
        }

        foreach ($candidates as $marker) {
            if ($marker['type'] !== $source['type']) {
                continue;
            }

            // Sync an already imported source by its stable ID, not by position.
            if (($marker['source_provider'] ?? null) === $source['source_provider']
                && ($marker['source_key'] ?? null) === $source['source_key']) {
                continue;
            }

            $dx = (float) $source['x'] - (float) $marker['x'];
            $dy = (float) $source['y'] - (float) $marker['y'];
            if (($dx * $dx + $dy * $dy) <= $radius * $radius) {
                return true;
            }
        }

        return false;
    }

    /** Include planned insertions so two different source IDs cannot create a duplicate together. */
    public function remember(array &$candidates, array $source): void
    {
        if (isset(self::RADII[$source['type']])) {
            $candidates[] = $source;
        }
    }

    /** @param array<int, string> $selections */
    public static function selectedTypes(array $selections): array
    {
        $selected = [];
        foreach ($selections as $selection) {
            $type = explode(':', $selection, 2)[0];
            if (isset(self::RADII[$type])) {
                $selected[$type] = $type;
            }
        }
        return array_values($selected);
    }
}
