@php
    $detailLocaleIsEnglish = app()->getLocale() === 'en';
    $detailSlug = (string) ($map['slug'] ?? 'stillwater-bayou');
    $detailName = (string) ($map['name'] ?? 'Stillwater Bayou');
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
<link href="https://fonts.googleapis.com" rel="preconnect">
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link data-hnt-theme-colors href="{{ asset('assets/themes/hnt_preview/theme-colors.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/theme-colors.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-maps/map-detail-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/map-detail-live.css')) ?: time() }}" rel="stylesheet">
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
<h1 id="mapDetailTitle">Stillwater Bayou</h1>
<div class="map-detail-heading-meta">
<span class="live"><i></i>Live</span>
<span id="mapDetailSubtitle">Klassischer Bayou · 2048 × 2048</span>
<span>Interaktive Marker</span>
<span>Community-Daten</span>
</div>
</div>
<div class="map-detail-heading-stats">
<article><strong id="mapStatMarkers">128</strong><span>Marker</span></article>
<article><strong id="mapStatCash">38</strong><span>Cash Spots</span></article>
<article><strong id="mapStatComments">64</strong><span>Kommentare</span></article>
</div>
</section>
<section class="map-detail-workspace">
<aside class="map-detail-left">
<article class="map-switch-card">
<header>
<div><span>KARTEN</span><h2>Map wechseln</h2></div>
<a aria-label="Zur Übersicht" href="{{ route('maps.index') }}"><svg><use href="#i-arrow"></use></svg></a>
</header>
<div class="map-switch-list">
<button class="map-switch-item active" data-map-switch="stillwater-bayou" type="button">
<img alt="" src="/assets/themes/hnt_preview/dashboard-maps/demo/stillwater-bayou.svg"/>
<span><strong>Stillwater Bayou</strong><small>Klassischer Bayou · 2048 × 2048</small></span>
<i><svg><use href="#i-arrow"></use></svg></i>
</button>
<button class="map-switch-item" data-map-switch="lawson-delta" type="button">
<img alt="" src="/assets/themes/hnt_preview/dashboard-maps/demo/lawson-delta.svg"/>
<span><strong>Lawson Delta</strong><small>Industriegebiet · 2048 × 2048</small></span>
<i><svg><use href="#i-arrow"></use></svg></i>
</button>
<button class="map-switch-item" data-map-switch="desalle" type="button">
<img alt="" src="/assets/themes/hnt_preview/dashboard-maps/demo/desalle.svg"/>
<span><strong>DeSalle</strong><small>Höhenunterschiede · 2048 × 2048</small></span>
<i><svg><use href="#i-arrow"></use></svg></i>
</button>
<button class="map-switch-item" data-map-switch="mammons-gulch" type="button">
<img alt="" src="/assets/themes/hnt_preview/dashboard-maps/demo/mammons-gulch.svg"/>
<span><strong>Mammon's Gulch</strong><small>Gebirgiges Terrain · 2048 × 2048</small></span>
<i><svg><use href="#i-arrow"></use></svg></i>
</button>
</div>
</article>
<article class="map-filter-card">
<header>
<div><span>MARKER</span><h2>Filter</h2></div>
<button id="resetMapFilters" type="button">Alle</button>
</header>
<label class="map-filter-search">
<svg><use href="#i-search"></use></svg>
<input id="mapMarkerSearch" placeholder="Marker suchen" type="search"/>
</label>
<div class="map-filter-list">
<label class="map-filter-chip active" data-filter-chip="compound"><input checked type="checkbox" value="compound"/><i></i><span>Compounds</span><b>9</b></label>
<label class="map-filter-chip active" data-filter-chip="boss"><input checked type="checkbox" value="boss"/><i></i><span>Bosse</span><b>1</b></label>
<label class="map-filter-chip active" data-filter-chip="spawn"><input checked type="checkbox" value="spawn"/><i></i><span>Spawns</span><b>1</b></label>
<label class="map-filter-chip active" data-filter-chip="supply"><input checked type="checkbox" value="supply"/><i></i><span>Supply</span><b>1</b></label>
<label class="map-filter-chip active" data-filter-chip="extract"><input checked type="checkbox" value="extract"/><i></i><span>Extracts</span><b>1</b></label>
<label class="map-filter-chip active" data-filter-chip="cash"><input checked type="checkbox" value="cash"/><i></i><span>Cash Spots</span><b>3</b></label>
<label class="map-filter-chip active" data-filter-chip="tower"><input checked type="checkbox" value="tower"/><i></i><span>Türme</span><b>1</b></label>
<label class="map-filter-chip active" data-filter-chip="bugs"><input checked type="checkbox" value="bugs"/><i></i><span>Bugs</span><b>1</b></label>
<label class="map-filter-chip active" data-filter-chip="wild"><input checked type="checkbox" value="wild"/><i></i><span>Wild</span><b>1</b></label>
<label class="map-filter-chip active" data-filter-chip="tarot"><input checked type="checkbox" value="tarot"/><i></i><span>Tarot</span><b>1</b></label>
</div>
</article>
<article class="map-layer-card">
<header><span>EBENEN</span><h2>Darstellung</h2></header>
<label><span><strong>Verbindungslinien</strong><small>Routen zwischen Compounds</small></span><input checked id="toggleMapLines" type="checkbox"/><i></i></label>
<label><span><strong>Beschriftungen</strong><small>Namen direkt auf der Karte</small></span><input checked id="toggleMapLabels" type="checkbox"/><i></i></label>
</article>
</aside>
<section class="map-detail-center">
<header class="map-canvas-toolbar">
<div><span>INTERAKTIVE KARTE</span><h2 id="mapCanvasTitle">Stillwater Bayou</h2></div>
<div class="map-toolbar-actions">
<button aria-label="Herauszoomen" id="mapZoomOut" type="button">−</button>
<button aria-label="Hineinzoomen" id="mapZoomIn" type="button">+</button>
<button id="mapResetView" type="button"><svg><use href="#i-sliders"></use></svg> Ansicht</button>
<button id="mapMeasureToggle" type="button"><svg><use href="#i-arrow"></use></svg> Messen</button>
<button id="mapShareView" type="button"><svg><use href="#i-share"></use></svg> Teilen</button>
<button id="mapOpenMarkerDetails" type="button"><svg><use href="#i-eye"></use></svg> Details</button>
<button id="mapOpenMarkerComments" type="button"><svg><use href="#i-comment"></use></svg> Kommentare</button>
</div>
</header>
<div class="map-canvas-frame" id="mapCanvasFrame">
<div class="map-canvas-surface" id="mapCanvasSurface">
<img alt="Stillwater Bayou Karte" id="mapCanvasImage" src="/assets/themes/hnt_preview/dashboard-maps/demo/stillwater-bayou.svg"/>
<div aria-hidden="true" class="map-line-overlay" id="mapLineOverlay"><svg preserveaspectratio="none" viewbox="0 0 1000 720"><path d="M180 150L390 120L690 150M205 350L470 320L735 350M275 555L560 520L810 555M390 120L470 320L560 520M690 150L735 350L810 555"></path><path class="soft" d="M180 150L205 350L275 555M690 150L470 320L275 555"></path></svg></div>
<div class="map-label-layer" id="mapLabelLayer">
<span style="left:12%;top:14%">Healing-Waters Church</span><span style="left:34%;top:10%">Pitching Crematorium</span><span style="left:64%;top:11%">Stillwater Bend</span><span style="left:14%;top:40%">Scupper Lake</span><span style="left:41%;top:36%">Lockbay Docks</span><span style="left:68%;top:37%">Darrow Livestock</span><span style="left:20%;top:69%">Catfish Grove</span><span style="left:51%;top:65%">The Chapel</span><span style="left:75%;top:69%">Blanchett Graves</span>
</div>
<div class="map-marker-layer" id="mapMarkerLayer">
<button aria-label="Healing-Waters Church" class="map-detail-marker marker-compound" data-marker-label="Healing-Waters Church" data-marker-type="compound" style="left:18%;top:21%" type="button"><span>C</span></button>
<button aria-label="Pitching Crematorium" class="map-detail-marker marker-compound" data-marker-label="Pitching Crematorium" data-marker-type="compound" style="left:38%;top:17%" type="button"><span>C</span></button>
<button aria-label="Stillwater Bend" class="map-detail-marker marker-compound" data-marker-label="Stillwater Bend" data-marker-type="compound" style="left:68%;top:18%" type="button"><span>C</span></button>
<button aria-label="Scupper Lake" class="map-detail-marker marker-compound" data-marker-label="Scupper Lake" data-marker-type="compound" style="left:20%;top:47%" type="button"><span>C</span></button>
<button aria-label="Lockbay Docks" class="map-detail-marker marker-compound" data-marker-label="Lockbay Docks" data-marker-type="compound" style="left:45%;top:43%" type="button"><span>C</span></button>
<button aria-label="Darrow Livestock" class="map-detail-marker marker-compound" data-marker-label="Darrow Livestock" data-marker-type="compound" style="left:73%;top:44%" type="button"><span>C</span></button>
<button aria-label="Catfish Grove" class="map-detail-marker marker-compound" data-marker-label="Catfish Grove" data-marker-type="compound" style="left:27%;top:76%" type="button"><span>C</span></button>
<button aria-label="The Chapel" class="map-detail-marker marker-compound" data-marker-label="The Chapel" data-marker-type="compound" style="left:56%;top:72%" type="button"><span>C</span></button>
<button aria-label="Blanchett Graves" class="map-detail-marker marker-compound" data-marker-label="Blanchett Graves" data-marker-type="compound" style="left:80%;top:76%" type="button"><span>C</span></button>
<button aria-label="Oben in der Scheune" class="map-detail-marker marker-cash selected" data-marker-label="Oben in der Scheune" data-marker-type="cash" style="left:47%;top:53%" type="button"><span>C</span></button>
<button aria-label="Im Lair unter der Treppe" class="map-detail-marker marker-cash" data-marker-label="Im Lair unter der Treppe" data-marker-type="cash" style="left:34%;top:59%" type="button"><span>C</span></button>
<button aria-label="Kiste hinter der Hütte" class="map-detail-marker marker-cash" data-marker-label="Kiste hinter der Hütte" data-marker-type="cash" style="left:71%;top:62%" type="button"><span>C</span></button>
<button aria-label="Tarotkarte" class="map-detail-marker marker-tarot" data-marker-label="Tarotkarte" data-marker-type="tarot" style="left:52%;top:31%" type="button"><span>T</span></button>
<button aria-label="Wild Target" class="map-detail-marker marker-wild" data-marker-label="Wild Target" data-marker-type="wild" style="left:15%;top:67%" type="button"><span>W</span></button>
<button aria-label="Supply Point" class="map-detail-marker marker-supply" data-marker-label="Supply Point" data-marker-type="supply" style="left:84%;top:31%" type="button"><span>S</span></button>
<button aria-label="Extraction" class="map-detail-marker marker-extract" data-marker-label="Extraction" data-marker-type="extract" style="left:89%;top:69%" type="button"><span>E</span></button>
<button aria-label="Spawn" class="map-detail-marker marker-spawn" data-marker-label="Spawn" data-marker-type="spawn" style="left:10%;top:29%" type="button"><span>S</span></button>
<button aria-label="Wachturm" class="map-detail-marker marker-tower" data-marker-label="Wachturm" data-marker-type="tower" style="left:62%;top:84%" type="button"><span>T</span></button>
<button aria-label="Boss Lair" class="map-detail-marker marker-boss" data-marker-label="Boss Lair" data-marker-type="boss" style="left:41%;top:33%" type="button"><span>B</span></button>
<button aria-label="Gemeldeter Kartenfehler" class="map-detail-marker marker-bugs" data-marker-label="Gemeldeter Kartenfehler" data-marker-type="bugs" style="left:77%;top:27%" type="button"><span>B</span></button>
</div>
<div class="map-selected-popover" id="mapSelectedPopover" style="left:49%;top:52%"><span>CASH SPOT</span><strong id="mapPopoverTitle">Oben in der Scheune</strong><small id="mapPopoverMeta">Healing-Waters Church · 18 hilfreich</small><button id="openMarkerDetails" type="button">Details</button></div>
<div class="map-measure-layer" hidden id="mapMeasureLayer"><i class="point-a"></i><i class="point-b"></i><span>184 m</span></div>
</div>
<div class="map-canvas-status"><span id="mapZoomText">100%</span><span id="mapVisibleCount">20 Marker sichtbar</span><span id="mapCoordinates">X 942 · Y 1455</span><button id="submitCashSpot" type="button"><svg><use href="#i-image"></use></svg> Cash Spot einreichen</button></div>
</div>
</section>
</section>
</div>
</section>
<div class="map-overlay-modal" hidden id="markerDetailModal"><div aria-labelledby="markerDetailTitle" aria-modal="true" class="map-overlay-panel marker-overlay-panel" role="dialog"><article class="marker-detail-card"><header><div><span>AUSGEWÄHLTER MARKER</span><h2 id="markerDetailTitle">Oben in der Scheune</h2></div><button aria-label="Marker-Details schließen" id="closeMarkerDetails" type="button">×</button></header><div class="marker-detail-preview"><img alt="" id="markerDetailImage" src="/assets/themes/hnt_preview/dashboard-maps/demo/stillwater-bayou.svg"/><span id="markerDetailType">Cash Spot</span></div><div class="marker-detail-meta"><article><span>Bereich</span><strong id="markerDetailArea">Healing-Waters Church</strong></article><article><span>Koordinaten</span><strong id="markerDetailCoords">X 942 · Y 1455</strong></article><article><span>Status</span><strong class="verified">Bestätigt</strong></article></div><div class="marker-vote-row"><div><span>War dieser Fundort hilfreich?</span><small>Auch Gäste können abstimmen.</small></div><button id="markerVoteUp" type="button">↑ <b id="markerVoteCount">18</b></button><button id="markerVoteDown" type="button">↓ 2</button></div><div class="marker-detail-modal-actions"><button id="openCommentsFromDetails" type="button">Kommentare ansehen</button><button id="closeMarkerDetailsFooter" type="button">Schließen</button></div></article></div></div>
<div class="map-overlay-modal" hidden id="markerCommentsModal"><div aria-labelledby="markerCommentsTitle" aria-modal="true" class="map-overlay-panel comments-overlay-panel" role="dialog"><article class="marker-comments-card"><header><div><span>COMMUNITY</span><h2 id="markerCommentsTitle">Kommentare</h2></div><div class="marker-comments-head-tools"><strong id="markerCommentCount">6</strong><button aria-label="Kommentare schließen" id="closeMarkerComments" type="button">×</button></div></header><div class="marker-comment-list" id="markerCommentList"><article><img alt="" src="/assets/vikinger/img/default-avatar.svg"/><div><strong>Katy Fuller</strong><p>War gestern noch da. Der Spot liegt oben hinter den Kisten.</p><small>vor 12 Min.</small></div></article><article><img alt="" src="/assets/vikinger/img/default-avatar.svg"/><div><strong>Jonathan Kelly</strong><p>Bestätigt. Von der Westseite aus am schnellsten erreichbar.</p><small>vor 34 Min.</small></div></article></div><form id="markerCommentForm"><img alt="" src="/assets/vikinger/img/default-avatar.svg"/><input id="markerCommentInput" maxlength="180" placeholder="Kommentar schreiben …" type="text"/><button type="submit">→</button></form></article></div></div>
<div class="map-cash-modal" hidden id="mapCashModal"><div aria-labelledby="mapCashModalTitle" aria-modal="true" class="map-cash-modal-panel" role="dialog"><header><div><span>CASH SPOT</span><h2 id="mapCashModalTitle">Neuen Fundort einreichen</h2></div><button id="closeCashModal" type="button">×</button></header><p>Wähle den Punkt auf der Karte und lade einen gut erkennbaren Screenshot hoch. Neue Fundorte werden vor der Freischaltung geprüft.</p><label><span>Screenshot</span><input accept="image/jpeg,image/png,image/webp" type="file"/></label><label><span>Kurze Beschreibung</span><input maxlength="120" type="text" value="Oben in der Scheune hinter den Kisten"/></label><div class="map-cash-modal-coords"><span>Ausgewählte Position</span><strong id="modalCoords">X 942 · Y 1455</strong></div><footer><button id="cancelCashModal" type="button">Abbrechen</button><button id="sendCashModal" type="button">Einreichen</button></footer></div></div>
<div class="toast" id="toast"></div>
</main>
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = @json(route('feed.index'));</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-maps/map-detail-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/map-detail-live.js')) ?: time() }}"></script>
</body>
</html>
