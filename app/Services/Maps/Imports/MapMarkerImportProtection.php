<?php

namespace App\Services\Maps\Imports;

use App\Models\HntMapCashSpotSubmission;
use App\Models\HntMapMarker;

final class MapMarkerImportProtection
{
    /** Counts may overlap; total_unique counts distinct protected marker IDs on this map. */
    public function countsForMap(int $mapId): array
    {
        $base = HntMapMarker::query()->where('hnt_map_id', $mapId);
        $cash = (clone $base)->where('type', 'cash')->pluck('id')->all();
        $submissionKey = (clone $base)->whereRaw('LOWER(legacy_key) LIKE ?', ['submission:%'])->pluck('id')->all();
        $linked = (clone $base)->whereIn('id', HntMapCashSpotSubmission::query()->select('hnt_map_marker_id'))
            ->pluck('id')->all();

        return [
            'cash' => count($cash),
            'submission_key' => count($submissionKey),
            'linked_submission' => count($linked),
            'total_unique' => count(array_unique(array_merge($cash, $submissionKey, $linked))),
        ];
    }

    public function isProtected(HntMapMarker $marker): bool
    {
        return $marker->type === 'cash'
            || str_starts_with(strtolower($marker->legacy_key), 'submission:')
            || HntMapCashSpotSubmission::query()->where('hnt_map_marker_id', $marker->id)->exists();
    }
}
