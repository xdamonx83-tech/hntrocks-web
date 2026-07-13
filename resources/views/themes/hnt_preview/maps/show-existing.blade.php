@php
    $detailLocaleIsEnglish = app()->getLocale() === 'en';
    $detailSlug = (string) ($map['slug'] ?? 'stillwater-bayou');
    $detailName = (string) ($map['name'] ?? 'Stillwater Bayou');
    $detailMarkers = array_values(is_array($markers ?? null) ? $markers : []);
    $detailMarkerTypes = array_values(is_array($markerTypes ?? null) ? $markerTypes : []);
    $detailMarkerCollection = collect($detailMarkers);
    $detailTypeCounts = collect($detailMarkerTypes)
        ->mapWithKeys(fn (string $type): array => [$type => $detailMarkerCollection->where('type', $type)->count()])
        ->all();
    $detailTypeLabels = collect($detailMarkerTypes)
        ->mapWithKeys(fn (string $type): array => [$type => __('ui.maps_type_'.$type)])
        ->all();
    $detailCashCount = (int) ($detailTypeCounts['cash'] ?? 0);
    $detailCommentCount = (int) $detailMarkerCollection->sum(fn (array $marker): int => (int) ($marker['comment_count'] ?? 0));
    $detailAvailableMaps = collect($availableMaps ?? [])->map(function (array $availableMap): array {
        $slug = (string) ($availableMap['slug'] ?? '');

        return [
            ...$availableMap,
            'image_url' => asset('assets/hnt/maps/'.$slug.'/map.webp'),
        ];
    })->values()->all();
    $copy = $detailLocaleIsEnglish ? [
        'live' => 'Live',
        'interactive' => 'Interactive markers',
        'community' => 'Community data',
        'markers' => 'Markers',
        'cash' => 'Cash spots',
        'comments' => 'Comments',
        'maps' => 'MAPS',
        'switch' => 'Switch map',
        'overview' => 'Back to overview',
        'filter' => 'Filters',
        'all' => 'All',
        'search' => 'Search markers',
        'layers' => 'LAYERS',
        'display' => 'Display',
        'lines' => 'Connection lines',
        'lines_help' => 'Routes between compounds',
        'labels' => 'Labels',
        'labels_help' => 'Names directly on the map',
        'interactive_map' => 'INTERACTIVE MAP',
        'zoom_out' => 'Zoom out',
        'zoom_in' => 'Zoom in',
        'view' => 'View',
        'measure' => 'Measure',
        'share' => 'Share',
        'submit_cash' => 'Submit cash spot',
    ] : [
        'live' => 'Live',
        'interactive' => 'Interaktive Marker',
        'community' => 'Community-Daten',
        'markers' => 'Marker',
        'cash' => 'Cash Spots',
        'comments' => 'Kommentare',
        'maps' => 'KARTEN',
        'switch' => 'Map wechseln',
        'overview' => 'Zur Übersicht',
        'filter' => 'Filter',
        'all' => 'Alle',
        'search' => 'Marker suchen',
        'layers' => 'EBENEN',
        'display' => 'Darstellung',
        'lines' => 'Verbindungslinien',
        'lines_help' => 'Routen zwischen Compounds',
        'labels' => 'Beschriftungen',
        'labels_help' => 'Namen direkt auf der Karte',
        'interactive_map' => 'INTERAKTIVE KARTE',
        'zoom_out' => 'Herauszoomen',
        'zoom_in' => 'Hineinzoomen',
        'view' => 'Ansicht',
        'measure' => 'Messen',
        'share' => 'Teilen',
        'submit_cash' => 'Cash Spot einreichen',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $detailLocaleIsEnglish ? 'en' : 'de' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="index,follow">
<title>HNT.ROCKS — {{ $detailName }}</title>
<meta name="description" content="{{ __('ui.maps_detail_meta_description', ['map' => $detailName]) }}">
<link rel="canonical" href="{{ route('maps.show', $detailSlug) }}">
<meta property="og:title" content="HNT.ROCKS — {{ $detailName }}">
<meta property="og:description" content="{{ __('ui.maps_detail_meta_description', ['map' => $detailName]) }}">
<meta property="og:url" content="{{ route('maps.show', $detailSlug) }}">
<meta property="og:image" content="{{ $map['image_url'] ?? '' }}">
<link href="https://fonts.googleapis.com" rel="preconnect">
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/vendor/leaflet/leaflet.css') }}?v=1.9.4" rel="stylesheet">
<link data-hnt-theme-colors href="{{ asset('assets/themes/hnt_preview/theme-colors.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/theme-colors.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-maps/map-detail-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/map-detail-live.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/hnt/maps/maps.css') }}?v={{ @filemtime(public_path('assets/hnt/maps/maps.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-maps/map-detail-existing.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/map-detail-existing.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="map-detail" data-map-slug="{{ $detailSlug }}">
@include('themes.hnt_preview.partials.icons')
<main class="app-shell map-detail-page-shell">
@include('themes.hnt_preview.partials.header')
<section class="map-detail-stage">
<div class="map-detail-scroll" id="mapDetailScroll">
<section class="map-detail-heading">
<div>
<span>HNT.ROCKS HUNTMAP</span>
<h1>{{ $detailName }}</h1>
<div class="map-detail-heading-meta">
<span class="live"><i></i>{{ $copy['live'] }}</span>
<span>{{ number_format((int) ($map['width'] ?? 2048), 0, ',', '.') }} × {{ number_format((int) ($map['height'] ?? 2048), 0, ',', '.') }}</span>
<span>{{ $copy['interactive'] }}</span>
<span>{{ $copy['community'] }}</span>
</div>
</div>
<div class="map-detail-heading-stats">
<article><strong>{{ count($detailMarkers) }}</strong><span>{{ $copy['markers'] }}</span></article>
<article><strong>{{ $detailCashCount }}</strong><span>{{ $copy['cash'] }}</span></article>
<article><strong>{{ $detailCommentCount }}</strong><span>{{ $copy['comments'] }}</span></article>
</div>
</section>
<section class="map-detail-workspace">
<aside class="map-detail-left">
<article class="map-switch-card">
<header>
<div><span>{{ $copy['maps'] }}</span><h2>{{ $copy['switch'] }}</h2></div>
<a aria-label="{{ $copy['overview'] }}" href="{{ route('maps.index') }}"><svg><use href="#i-arrow"></use></svg></a>
</header>
<div class="map-switch-list">
@foreach($detailAvailableMaps as $availableMap)
<button class="map-switch-item {{ ($availableMap['slug'] ?? '') === $detailSlug ? 'active' : '' }}" data-existing-map-url="{{ $availableMap['url'] ?? '' }}" type="button">
<img alt="{{ $availableMap['name'] ?? '' }}" src="{{ $availableMap['image_url'] ?? '' }}">
<span><strong>{{ $availableMap['name'] ?? '' }}</strong><small>2048 × 2048</small></span>
<i><svg><use href="#i-arrow"></use></svg></i>
</button>
@endforeach
</div>
</article>
<article class="map-filter-card">
<header>
<div><span>MARKER</span><h2>{{ $copy['filter'] }}</h2></div>
<button id="resetMapFilters" type="button">{{ $copy['all'] }}</button>
</header>
<label class="map-filter-search">
<svg><use href="#i-search"></use></svg>
<input id="hntMapSearch" placeholder="{{ $copy['search'] }}" type="search" autocomplete="off" aria-controls="hntMapSearchResults" aria-expanded="false">
<div id="hntMapSearchResults" class="hnt-map-search-results" role="listbox" hidden></div>
</label>
<div class="map-filter-list">
@foreach($detailMarkerTypes as $markerType)
<label class="map-filter-chip active" data-filter-chip="{{ $markerType }}">
<input checked data-map-filter type="checkbox" value="{{ $markerType }}">
<i></i><span>{{ $detailTypeLabels[$markerType] ?? $markerType }}</span><b>{{ $detailTypeCounts[$markerType] ?? 0 }}</b>
</label>
@endforeach
</div>
</article>
<article class="map-layer-card">
<header><span>{{ $copy['layers'] }}</span><h2>{{ $copy['display'] }}</h2></header>
<label><span><strong>{{ $copy['lines'] }}</strong><small>{{ $copy['lines_help'] }}</small></span><input checked data-map-lines-toggle type="checkbox"><i></i></label>
<label><span><strong>{{ $copy['labels'] }}</strong><small>{{ $copy['labels_help'] }}</small></span><input checked id="toggleMapLabels" type="checkbox"><i></i></label>
</article>
</aside>
<section class="map-detail-center">
<header class="map-canvas-toolbar">
<div><span>{{ $copy['interactive_map'] }}</span><h2>{{ $detailName }}</h2></div>
<div class="map-toolbar-actions">
<button aria-label="{{ $copy['zoom_out'] }}" data-detail-zoom-out type="button">−</button>
<button aria-label="{{ $copy['zoom_in'] }}" data-detail-zoom-in type="button">+</button>
<button data-map-reset type="button"><svg><use href="#i-sliders"></use></svg> {{ $copy['view'] }}</button>
<button data-map-measure-toggle type="button"><svg><use href="#i-arrow"></use></svg> <span>{{ $copy['measure'] }}</span></button>
<button data-map-share type="button"><svg><use href="#i-share"></use></svg> {{ $copy['share'] }}</button>
</div>
</header>
<div class="map-canvas-frame hnt-map-stage">
@if(! $imageAvailable || $dataError)
<div class="map-live-error" role="status"><strong>{{ __('ui.maps_unavailable_title') }}</strong><span>{{ ! $imageAvailable ? __('ui.maps_image_missing') : __('ui.maps_data_invalid') }}</span></div>
@else
<div id="hntMap" class="map-canvas-surface hnt-map-canvas" aria-label="{{ __('ui.maps_canvas_aria', ['map' => $detailName]) }}"></div>
<p class="hnt-map-measure-hint" data-map-measure-hint hidden>{{ __('ui.maps_measure_point_a') }}</p>
<p class="hnt-map-measure-hint" data-map-cash-spot-hint hidden>{{ __('ui.maps_cash_spot_select') }}</p>
@if(empty($detailMarkers))
<p class="hnt-map-empty-markers">{{ __('ui.maps_no_markers') }}</p>
@endif
@endif
<div class="map-canvas-status">
<span id="mapVisibleCount">{{ count($detailMarkers) }} {{ $copy['markers'] }}</span>
<span data-map-measure-status>{{ __('ui.maps_measure_idle') }}</span>
<label class="map-share-fallback" data-map-share-fallback hidden><input data-map-share-url readonly type="text"></label>
<button data-map-cash-spot-toggle type="button"><svg><use href="#i-image"></use></svg> {{ $copy['submit_cash'] }}</button>
</div>
@if($imageAvailable && ! $dataError)
<div class="hnt-map-lightbox hnt-map-cash-submission-modal" data-map-cash-spot-modal hidden>
<div class="hnt-map-lightbox-panel hnt-map-cash-submission-panel" role="dialog" aria-modal="true" aria-labelledby="hntCashSpotSubmissionTitle">
<header class="hnt-map-lightbox-header">
<h2 id="hntCashSpotSubmissionTitle">{{ __('ui.maps_cash_spot_form_title') }}</h2>
<button type="button" class="hnt-map-lightbox-close" data-map-cash-spot-cancel aria-label="{{ __('ui.maps_close') }}">×</button>
</header>
<form class="hnt-map-cash-submission-form" data-map-cash-spot-form>
<p>{{ __('ui.maps_cash_spot_form_help') }}</p>
<input type="hidden" name="x">
<input type="hidden" name="y">
<label><span>{{ __('ui.maps_cash_spot_screenshot') }}</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label>
<label data-map-cash-spot-guest-field @auth hidden @endauth><span>{{ __('ui.maps_cash_spot_name') }}</span><input type="text" name="submitter_name" maxlength="80" @auth disabled @endauth></label>
<label data-map-cash-spot-guest-field @auth hidden @endauth><span>{{ __('ui.maps_cash_spot_email') }}</span><input type="email" name="submitter_email" maxlength="160" @auth disabled @endauth></label>
<input class="hnt-map-upload-honeypot" type="text" name="website" maxlength="120" tabindex="-1" autocomplete="off" aria-hidden="true">
<p class="hnt-map-cash-submission-status" data-map-cash-spot-status role="status"></p>
<div class="hnt-map-cash-submission-actions">
<button type="button" class="hnt-map-popup-action" data-map-cash-spot-cancel>{{ __('ui.maps_cash_spot_cancel') }}</button>
<button type="submit" class="hnt-map-popup-action">{{ __('ui.maps_cash_spot_send') }}</button>
</div>
</form>
</div>
</div>
@endif
<p class="hnt-map-toast" data-map-toast role="status" hidden></p>
</div>
</section>
</section>
</div>
</section>
</main>
@if($imageAvailable && ! $dataError)
<script id="hntMapConfig" type="application/json">{!! json_encode([
    'imageUrl' => $map['image_url'],
    'linesUrl' => $map['lines_url'],
    'width' => $map['width'],
    'height' => $map['height'],
    'markers' => $detailMarkers,
    'typeLabels' => $detailTypeLabels,
    'searchEmptyText' => __('ui.maps_search_empty'),
    'measureStartText' => __('ui.maps_measure_start'),
    'measureEndText' => __('ui.maps_measure_end'),
    'measureIdleText' => __('ui.maps_measure_idle'),
    'measurePointAText' => __('ui.maps_measure_point_a'),
    'measurePointBText' => __('ui.maps_measure_point_b'),
    'measureSavedText' => __('ui.maps_measure_saved'),
    'measureDistanceText' => __('ui.maps_measure_distance'),
    'measureMarkerAText' => __('ui.maps_measure_marker_a'),
    'measureMarkerBText' => __('ui.maps_measure_marker_b'),
    'shareSuccessText' => __('ui.maps_share_success'),
    'viewerIsAuthenticated' => auth()->check(),
    'cashSpotSubmissionUrl' => $map['cash_spot_submission_url'],
    'cashScreenshotText' => __('ui.maps_cash_screenshot'),
    'cashScreenshotErrorText' => __('ui.maps_cash_screenshot_error'),
    'cashSpotSelectText' => __('ui.maps_cash_spot_select'),
    'cashSpotRunningText' => __('ui.maps_cash_spot_running'),
    'cashSpotPendingText' => __('ui.maps_cash_spot_pending'),
    'cashSpotErrorText' => __('ui.maps_cash_spot_error'),
    'cashSpotDetailTitle' => __('ui.maps_cash_spot_detail_title'),
    'cashSpotEyebrowText' => __('ui.maps_cash_spot_eyebrow'),
    'cashSpotHelpfulText' => __('ui.maps_cash_spot_helpful'),
    'cashSpotUpvoteText' => __('ui.maps_cash_spot_upvote'),
    'cashSpotDownvoteText' => __('ui.maps_cash_spot_downvote'),
    'cashSpotVoteAnonymousHintText' => __('ui.maps_cash_spot_vote_anonymous_hint'),
    'cashSpotCommentsTitleText' => __('ui.maps_cash_spot_comments_title'),
    'cashSpotCommentsLoadingText' => __('ui.maps_cash_spot_comments_loading'),
    'cashSpotCommentsEmptyText' => __('ui.maps_cash_spot_comments_empty'),
    'cashSpotCommentPlaceholderText' => __('ui.maps_cash_spot_comment_placeholder'),
    'cashSpotCommentSendText' => __('ui.maps_cash_spot_comment_send'),
    'cashSpotCommentLoginText' => __('ui.maps_cash_spot_comment_login'),
    'cashSpotCommentErrorText' => __('ui.maps_cash_spot_comment_error'),
    'cashSpotCommentEditText' => __('ui.maps_cash_spot_comment_edit'),
    'cashSpotCommentDeleteText' => __('ui.maps_cash_spot_comment_delete'),
    'cashSpotCommentDeleteConfirmText' => __('ui.maps_cash_spot_comment_delete_confirm'),
    'cashSpotCommentSaveText' => __('ui.maps_cash_spot_comment_save'),
    'cashSpotCommentCancelText' => __('ui.maps_cash_spot_comment_cancel'),
    'cashSpotCommentDeletedText' => __('ui.maps_cash_spot_comment_deleted'),
    'cashSpotCommentUpdatedText' => __('ui.maps_cash_spot_comment_updated'),
    'cashSpotVoteErrorText' => __('ui.maps_cash_spot_vote_error'),
    'closeText' => __('ui.maps_close'),
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endif
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = @json(route('feed.index'));</script>
<script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}?v=1.9.4"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
@if($imageAvailable && ! $dataError)
<script src="{{ asset('assets/hnt/maps/maps.js') }}?v={{ @filemtime(public_path('assets/hnt/maps/maps.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-maps/map-detail-existing.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/map-detail-existing.js')) ?: time() }}"></script>
@endif
</body>
</html>
