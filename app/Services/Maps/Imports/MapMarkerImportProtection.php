<?php

namespace App\Services\Maps\Imports;

use App\Models\HntMapCashSpotSubmission;
use App\Models\HntMapMarker;

final class MapMarkerImportProtection
{
    public function isProtected(HntMapMarker $marker): bool
    {
        return $marker->type === 'cash'
            || str_starts_with(strtolower($marker->legacy_key), 'submission:')
            || HntMapCashSpotSubmission::query()->where('hnt_map_marker_id', $marker->id)->exists();
    }
}
