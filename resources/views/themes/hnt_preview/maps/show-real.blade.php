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
    $detailCashCount = (int) ($detailTypeCounts['cash'] ?? 0);
    $detailCommentCount = (int) $detailMarkerCollection->sum(fn (array $marker): int => (int) ($marker['comment_count'] ?? 0));
    $detailTypeLabels = collect($detailMarkerTypes)
        ->mapWithKeys(fn (string $type): array => [$type => __('ui.maps_type_'.$type)])
        ->all();
    $detailAvailableMaps = collect($availableMaps ?? [])
        ->map(function (array $availableMap): array {
            $slug = (string) ($availableMap['slug'] ?? '');

            return [
                ...$availableMap,
                'width' => 2048,
                'height' => 2048,
                'image_url' => asset('assets/hnt/maps/'.$slug.'/map.webp'),
            ];
        })
        ->values()
        ->all();
    $detailViewer = auth()->user();
    $detailViewerAvatar = $detailViewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $detailCopy = $detailLocaleIsEnglish ? [
        'live' => 'Live',
        'interactive' => 'Interactive markers',
        'community' => 'Community data',
        'markers' => 'Markers',
        'cash_spots' => 'Cash spots',
        'comments' => 'Comments',
        'maps' => 'MAPS',
        'switch_map' => 'Switch map',
        'overview' => 'Back to overview',
        'marker' => 'MARKERS',
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
        'measure_end' => 'Stop measuring',
        'share' => 'Share',
        'details' => 'Details',
        'visible_markers' => ':count markers visible',
        'coordinates' => 'X :x · Y :y',
        'submit_cash' => 'Submit cash spot',
        'selected_marker' => 'SELECTED MARKER',
        'area' => 'Map',
        'status' => 'Status',
        'verified' => 'Approved',
        'helpful' => 'Was this location helpful?',
        'anonymous_vote' => 'Guests can vote too.',
        'view_comments' => 'View comments',
        'close' => 'Close',
        'community_kicker' => 'COMMUNITY',
        'no_comments' => 'No comments yet.',
        'loading_comments' => 'Loading comments…',
        'login_to_comment' => 'Log in to comment',
        'comment_placeholder' => 'Write a comment…',
        'new_cash' => 'Submit a new location',
        'cash_help' => 'Select the location on the map and upload a clear screenshot. New locations are reviewed before publication.',
        'screenshot' => 'Screenshot',
        'name' => 'Name (optional)',
        'email' => 'Email (optional)',
        'selected_position' => 'Selected position',
        'cancel' => 'Cancel',
        'submit' => 'Submit',
        'unavailable_title' => 'Map unavailable',
        'unavailable_text' => 'The map image or marker data could not be loaded.',
        'select_marker' => 'Select a marker first.',
        'comments_unavailable' => 'Comments are only available for approved cash spots.',
        'view_reset' => 'Map view reset.',
        'share_success' => 'Map view copied.',
        'share_fallback' => 'Copy this link:',
        'measure_point_a' => 'Select the first point.',
        'measure_point_b' => 'Select the second point.',
        'measure_result' => ':meters m',
        'cash_select_point' => 'Select the cash spot position on the map.',
        'cash_running' => 'Uploading…',
        'cash_pending' => 'Cash spot submitted for review.',
        'cash_error' => 'The cash spot could not be submitted.',
        'vote_error' => 'The vote could not be saved.',
        'comment_error' => 'The comment could not be saved.',
    ] : [
        'live' => 'Live',
        'interactive' => 'Interaktive Marker',
        'community' => 'Community-Daten',
        'markers' => 'Marker',
        'cash_spots' => 'Cash Spots',
        'comments' => 'Kommentare',
        'maps' => 'KARTEN',
        'switch_map' => 'Map wechseln',
        'overview' => 'Zur Übersicht',
        'marker' => 'MARKER',
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
        'measure_end' => 'Messung beenden',
        'share' => 'Teilen',
        'details' => 'Details',
        'visible_markers' => ':count Marker sichtbar',
        'coordinates' => 'X :x · Y :y',
        'submit_cash' => 'Cash Spot einreichen',
        'selected_marker' => 'AUSGEWÄHLTER MARKER',
        'area' => 'Karte',
        'status' => 'Status',
        'verified' => 'Bestätigt',
        'helpful' => 'War dieser Fundort hilfreich?',
        'anonymous_vote' => 'Auch Gäste können abstimmen.',
        'view_comments' => 'Kommentare ansehen',
        'close' => 'Schließen',
        'community_kicker' => 'COMMUNITY',
        'no_comments' => 'Noch keine Kommentare vorhanden.',
        'loading_comments' => 'Kommentare werden geladen…',
        'login_to_comment' => 'Zum Kommentieren anmelden',
        'comment_placeholder' => 'Kommentar schreiben …',
        'new_cash' => 'Neuen Fundort einreichen',
        'cash_help' => 'Wähle den Punkt auf der Karte und lade einen gut erkennbaren Screenshot hoch. Neue Fundorte werden vor der Freischaltung geprüft.',
        'screenshot' => 'Screenshot',
        'name' => 'Name (optional)',
        'email' => 'E-Mail (optional)',
        'selected_position' => 'Ausgewählte Position',
        'cancel' => 'Abbrechen',
        'submit' => 'Einreichen',
        'unavailable_title' => 'Karte nicht verfügbar',
        'unavailable_text' => 'Kartenbild oder Markerdaten konnten nicht geladen werden.',
        'select_marker' => 'Wähle zuerst einen Marker aus.',
        'comments_unavailable' => 'Kommentare sind nur bei freigegebenen Cash Spots verfügbar.',
        'view_reset' => 'Kartenansicht zurückgesetzt.',
        'share_success' => 'Kartenansicht kopiert.',
        'share_fallback' => 'Diesen Link kopieren:',
        'measure_point_a' => 'Wähle den ersten Punkt.',
        'measure_point_b' => 'Wähle den zweiten Punkt.',
        'measure_result' => ':meters m',
        'cash_select_point' => 'Wähle die Position des Cash Spots auf der Karte.',
        'cash_running' => 'Wird hochgeladen…',
        'cash_pending' => 'Cash Spot wurde zur Prüfung eingereicht.',
        'cash_error' => 'Der Cash Spot konnte nicht eingereicht werden.',
        'vote_error' => 'Die Bewertung konnte nicht gespeichert werden.',
        'comment_error' => 'Der Kommentar konnte nicht gespeichert werden.',
    ];
    $detailConfig = [
        'slug' => $detailSlug,
        'name' => $detailName,
        'width' => (int) ($map['width'] ?? 2048),
        'height' => (int) ($map['height'] ?? 2048),
        'imageUrl' => (string) ($map['image_url'] ?? ''),
        'linesUrl' => $map['lines_url'] ?? null,
        'imageAvailable' => (bool) ($imageAvailable ?? false),
        'dataError' => $dataError ?? null,
        'markers' => $detailMarkers,
        'markerTypes' => $detailMarkerTypes,
        'typeLabels' => $detailTypeLabels,
        'typeCounts' => $detailTypeCounts,
        'availableMaps' => $detailAvailableMaps,
        'cashSpotSubmissionUrl' => (string) ($map['cash_spot_submission_url'] ?? ''),
        'stats' => [
            'markers' => count($detailMarkers),
            'cash' => $detailCashCount,
            'comments' => $detailCommentCount,
        ],
        'viewer' => [
            'authenticated' => auth()->check(),
            'name' => $detailViewer?->name ?: $detailViewer?->username,
            'avatar' => $detailViewerAvatar,
        ],
        'copy' => $detailCopy,
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
<link href="{{ asset('assets/themes/hnt_preview/dashboard-maps/map-detail-real.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/map-detail-real.css')) ?: time() }}" rel="stylesheet">
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
<h1 id="mapDetailTitle">{{ $detailName }}</h1>
<div class="map-detail-heading-meta">
<span class="live"><i></i>{{ $detailCopy['live'] }}</span>
<span id="mapDetailSubtitle">{{ number_format((int) ($map['width'] ?? 2048), 0, ',', '.') }} × {{ number_format((int) ($map['height'] ?? 2048), 0, ',', '.') }}</span>
<span>{{ $detailCopy['interactive'] }}</span>
<span>{{ $detailCopy['community'] }}</span>
</div>
</div>
<div class="map-detail-heading-stats">
<article><strong id="mapStatMarkers">{{ count($detailMarkers) }}</strong><span>{{ $detailCopy['markers'] }}</span></article>
<article><strong id="mapStatCash">{{ $detailCashCount }}</strong><span>{{ $detailCopy['cash_spots'] }}</span></article>
<article><strong id="mapStatComments">{{ $detailCommentCount }}</strong><span>{{ $detailCopy['comments'] }}</span></article>
</div>
</section>
<section class="map-detail-workspace">
<aside class="map-detail-left">
<article class="map-switch-card">
<header>
<div><span>{{ $detailCopy['maps'] }}</span><h2>{{ $detailCopy['switch_map'] }}</h2></div>
<a aria-label="{{ $detailCopy['overview'] }}" href="{{ route('maps.index') }}"><svg><use href="#i-arrow"></use></svg></a>
</header>
<div class="map-switch-list">
@foreach($detailAvailableMaps as $availableMap)
<button class="map-switch-item {{ $availableMap['slug'] === $detailSlug ? 'active' : '' }}" data-map-switch="{{ $availableMap['slug'] }}" type="button">
<img alt="{{ $availableMap['name'] }}" src="{{ $availableMap['image_url'] }}">
<span><strong>{{ $availableMap['name'] }}</strong><small>{{ $availableMap['width'] }} × {{ $availableMap['height'] }}</small></span>
<i><svg><use href="#i-arrow"></use></svg></i>
</button>
@endforeach
</div>
</article>
<article class="map-filter-card">
<header>
<div><span>{{ $detailCopy['marker'] }}</span><h2>{{ $detailCopy['filter'] }}</h2></div>
<button id="resetMapFilters" type="button">{{ $detailCopy['all'] }}</button>
</header>
<label class="map-filter-search">
<svg><use href="#i-search"></use></svg>
<input id="mapMarkerSearch" placeholder="{{ $detailCopy['search'] }}" type="search">
</label>
<div class="map-filter-list">
@foreach($detailMarkerTypes as $markerType)
<label class="map-filter-chip active" data-filter-chip="{{ $markerType }}">
<input checked type="checkbox" value="{{ $markerType }}">
<i></i><span>{{ $detailTypeLabels[$markerType] ?? $markerType }}</span><b>{{ $detailTypeCounts[$markerType] ?? 0 }}</b>
</label>
@endforeach
</div>
</article>
<article class="map-layer-card">
<header><span>{{ $detailCopy['layers'] }}</span><h2>{{ $detailCopy['display'] }}</h2></header>
<label><span><strong>{{ $detailCopy['lines'] }}</strong><small>{{ $detailCopy['lines_help'] }}</small></span><input checked id="toggleMapLines" type="checkbox"><i></i></label>
<label><span><strong>{{ $detailCopy['labels'] }}</strong><small>{{ $detailCopy['labels_help'] }}</small></span><input checked id="toggleMapLabels" type="checkbox"><i></i></label>
</article>
</aside>
<section class="map-detail-center">
<header class="map-canvas-toolbar">
<div><span>{{ $detailCopy['interactive_map'] }}</span><h2 id="mapCanvasTitle">{{ $detailName }}</h2></div>
<div class="map-toolbar-actions">
<button aria-label="{{ $detailCopy['zoom_out'] }}" id="mapZoomOut" type="button">−</button>
<button aria-label="{{ $detailCopy['zoom_in'] }}" id="mapZoomIn" type="button">+</button>
<button id="mapResetView" type="button"><svg><use href="#i-sliders"></use></svg> {{ $detailCopy['view'] }}</button>
<button id="mapMeasureToggle" type="button"><svg><use href="#i-arrow"></use></svg> <span>{{ $detailCopy['measure'] }}</span></button>
<button id="mapShareView" type="button"><svg><use href="#i-share"></use></svg> {{ $detailCopy['share'] }}</button>
<button disabled id="mapOpenMarkerDetails" type="button"><svg><use href="#i-eye"></use></svg> {{ $detailCopy['details'] }}</button>
<button disabled id="mapOpenMarkerComments" type="button"><svg><use href="#i-comment"></use></svg> {{ $detailCopy['comments'] }}</button>
</div>
</header>
<div class="map-canvas-frame" id="mapCanvasFrame">
<div class="map-canvas-surface" id="mapCanvasSurface"></div>
<div class="map-selected-popover" hidden id="mapSelectedPopover"><span>{{ $detailCopy['selected_marker'] }}</span><strong id="mapPopoverTitle"></strong><small id="mapPopoverMeta"></small><button id="openMarkerDetails" type="button">{{ $detailCopy['details'] }}</button></div>
<div class="map-canvas-status">
<span id="mapZoomText">100%</span>
<span id="mapVisibleCount">{{ count($detailMarkers) }} {{ $detailCopy['markers'] }}</span>
<span id="mapCoordinates">X 0 · Y 0</span>
<span id="mapMeasureStatus"></span>
<button id="submitCashSpot" type="button"><svg><use href="#i-image"></use></svg> {{ $detailCopy['submit_cash'] }}</button>
</div>
</div>
</section>
</section>
</div>
</section>
<div class="map-overlay-modal" hidden id="markerDetailModal">
<div aria-labelledby="markerDetailTitle" aria-modal="true" class="map-overlay-panel marker-overlay-panel" role="dialog">
<article class="marker-detail-card">
<header><div><span>{{ $detailCopy['selected_marker'] }}</span><h2 id="markerDetailTitle"></h2></div><button aria-label="{{ $detailCopy['close'] }}" id="closeMarkerDetails" type="button">×</button></header>
<div class="marker-detail-preview"><img alt="" id="markerDetailImage" src="{{ $map['image_url'] ?? '' }}"><span id="markerDetailType"></span></div>
<div class="marker-detail-meta">
<article><span>{{ $detailCopy['area'] }}</span><strong id="markerDetailArea">{{ $detailName }}</strong></article>
<article><span>{{ $detailCopy['coordinates'] }}</span><strong id="markerDetailCoords"></strong></article>
<article><span>{{ $detailCopy['status'] }}</span><strong class="verified">{{ $detailCopy['verified'] }}</strong></article>
</div>
<div class="marker-vote-row" hidden><div><span>{{ $detailCopy['helpful'] }}</span><small>{{ $detailCopy['anonymous_vote'] }}</small></div><button id="markerVoteUp" type="button">↑ <b id="markerVoteCount">0</b></button><button id="markerVoteDown" type="button">↓ <b id="markerVoteDownCount">0</b></button></div>
<div class="marker-detail-modal-actions"><button hidden id="openCommentsFromDetails" type="button">{{ $detailCopy['view_comments'] }}</button><button id="closeMarkerDetailsFooter" type="button">{{ $detailCopy['close'] }}</button></div>
</article>
</div>
</div>
<div class="map-overlay-modal" hidden id="markerCommentsModal">
<div aria-labelledby="markerCommentsTitle" aria-modal="true" class="map-overlay-panel comments-overlay-panel" role="dialog">
<article class="marker-comments-card">
<header><div><span>{{ $detailCopy['community_kicker'] }}</span><h2 id="markerCommentsTitle">{{ $detailCopy['comments'] }}</h2></div><div class="marker-comments-head-tools"><strong id="markerCommentCount">0</strong><button aria-label="{{ $detailCopy['close'] }}" id="closeMarkerComments" type="button">×</button></div></header>
<div class="marker-comment-list" id="markerCommentList"></div>
<p id="markerCommentState">{{ $detailCopy['loading_comments'] }}</p>
<p hidden id="markerCommentLogin"><a href="{{ route('login') }}">{{ $detailCopy['login_to_comment'] }}</a></p>
<form hidden id="markerCommentForm"><img alt="" src="{{ $detailViewerAvatar }}"><input id="markerCommentInput" maxlength="2000" placeholder="{{ $detailCopy['comment_placeholder'] }}" type="text"><button type="submit">→</button></form>
</article>
</div>
</div>
<div class="map-cash-modal" hidden id="mapCashModal">
<div aria-labelledby="mapCashModalTitle" aria-modal="true" class="map-cash-modal-panel" role="dialog">
<header><div><span>{{ $detailCopy['cash_spots'] }}</span><h2 id="mapCashModalTitle">{{ $detailCopy['new_cash'] }}</h2></div><button aria-label="{{ $detailCopy['close'] }}" id="closeCashModal" type="button">×</button></header>
<p>{{ $detailCopy['cash_help'] }}</p>
<form id="mapCashForm">
<input name="x" type="hidden"><input name="y" type="hidden"><input class="map-upload-honeypot" name="website" tabindex="-1" type="text">
<label><span>{{ $detailCopy['screenshot'] }}</span><input accept="image/jpeg,image/png,image/webp" name="image" required type="file"></label>
<label><span>{{ $detailCopy['name'] }}</span><input maxlength="80" name="submitter_name" type="text"></label>
<label><span>{{ $detailCopy['email'] }}</span><input maxlength="160" name="submitter_email" type="email"></label>
<div class="map-cash-modal-coords"><span>{{ $detailCopy['selected_position'] }}</span><strong id="modalCoords">X 0 · Y 0</strong></div>
<p id="mapCashStatus" role="status"></p>
<footer><button id="cancelCashModal" type="button">{{ $detailCopy['cancel'] }}</button><button type="submit">{{ $detailCopy['submit'] }}</button></footer>
</form>
</div>
</div>
<div class="toast" id="toast"></div>
</main>
<script id="hntMapDetailConfig" type="application/json">{!! json_encode($detailConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = @json(route('feed.index'));</script>
<script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}?v=1.9.4"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-maps/map-detail-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/map-detail-live.js')) ?: time() }}"></script>
</body>
</html>
