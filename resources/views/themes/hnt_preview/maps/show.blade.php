@php
    $mapDetailHtml = view('themes.hnt_preview.maps.show-existing', [
        'map' => $map,
        'markers' => $markers,
        'markerTypes' => $markerTypes,
        'availableMaps' => $availableMaps,
        'imageAvailable' => $imageAvailable,
        'dataError' => $dataError,
    ])->render();

    $versionedAsset = static function (string $path): string {
        $absolutePath = public_path($path);
        $version = is_file($absolutePath) ? filemtime($absolutePath) : time();

        return asset($path).'?v='.$version;
    };

    $headAssets = '<link rel="stylesheet" href="'.e($versionedAsset('assets/themes/hnt_preview/dashboard-maps/map-detail-header-layer.css')).'">'
        .'<link rel="stylesheet" href="'.e($versionedAsset('assets/themes/hnt_preview/dashboard-maps/map-detail-cash-submit-live.css')).'">'
        .'<link rel="stylesheet" href="'.e($versionedAsset('assets/themes/hnt_preview/dashboard-maps/map-detail-size.css')).'">'
        .'<link rel="stylesheet" href="'.e($versionedAsset('assets/themes/hnt_preview/dashboard-maps/map-detail-fullscreen.css')).'">';

    $bodyAssets = '<script src="'.e($versionedAsset('assets/themes/hnt_preview/dashboard-maps/map-detail-no-popovers.js')).'"></script>'
        .'<script src="'.e($versionedAsset('assets/themes/hnt_preview/dashboard-maps/map-detail-cash-submit-live.js')).'"></script>'
        .'<script src="'.e($versionedAsset('assets/themes/hnt_preview/dashboard-maps/map-detail-fullscreen.js')).'"></script>';

    $mapsScriptNeedle = '<script src="'.asset('assets/hnt/maps/maps.js');
    if (strpos($mapDetailHtml, 'data-map-measure-reset') === false && strpos($mapDetailHtml, $mapsScriptNeedle) !== false) {
        $measureResetCompatibility = '<button type="button" data-map-measure-reset hidden aria-hidden="true" tabindex="-1"></button>';
        $mapDetailHtml = str_replace($mapsScriptNeedle, $measureResetCompatibility.$mapsScriptNeedle, $mapDetailHtml);
    }

    $mapDetailHtml = str_replace('</head>', $headAssets.'</head>', $mapDetailHtml);
    $mapDetailHtml = str_replace('</body>', $bodyAssets.'</body>', $mapDetailHtml);
@endphp
{!! $mapDetailHtml !!}
