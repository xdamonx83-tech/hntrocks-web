@php
    $localeIsEnglish = app()->getLocale() === 'en';
    $mapsBySlug = $maps->keyBy('slug');
    $mapCards = collect(['stillwater-bayou', 'lawson-delta', 'desalle', 'mammons-gulch'])
        ->mapWithKeys(function (string $slug) use ($mapsBySlug): array {
            $map = $mapsBySlug->get($slug);

            return [$slug => [
                'image' => $map && ($map['image_available'] ?? false)
                    ? $map['image_url']
                    : asset('assets/themes/hnt_preview/dashboard-maps/demo/'.$slug.'.svg'),
                'url' => $map ? route('maps.show', $slug) : '#',
            ]];
        });
@endphp
<!DOCTYPE html>
<html lang="{{ $localeIsEnglish ? 'en' : 'de' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="index,follow">
<title>HNT.ROCKS — Huntmaps</title>
<link href="https://fonts.googleapis.com" rel="preconnect">
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-maps/maps-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/maps-live.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="maps">
@include('themes.hnt_preview.partials.icons')
<main class="app-shell maps-page-shell">
@include('themes.hnt_preview.partials.header')
<section class="maps-stage"><div aria-label="Huntmaps Übersicht" class="maps-scroll" data-maps-root id="mapsScroll" tabindex="0"><section class="maps-heading">
<div class="maps-heading-copy">
<span>HNT.ROCKS HUNTMAPS</span>
<h1>Huntmaps</h1>
<p>Vier interaktive Hunt-Karten mit Filtern, Markern, Community Cash Spots und direktem Kartenwechsel.</p>
</div>
<div class="maps-heading-stats">
<article><strong>4</strong><span>Karten</span></article>
<article><strong>2048</strong><span>Pixel</span></article>
<article><strong>10</strong><span>Markerarten</span></article>
</div>
</section><section aria-label="Maps Ansicht" class="maps-view-switch">
<button class="active" data-maps-view="maps" type="button">Karten</button>
<button data-maps-view="features" type="button">Marker &amp; Funktionen</button>
</section><section class="maps-view-panel active" data-maps-panel="maps">
<div class="maps-plan-grid">
<article class="map-plan-card selected" data-map-card="stillwater-bayou">
<div class="map-plan-art">
<img alt="Karten-Vorschau Stillwater Bayou" src="{{ $mapCards['stillwater-bayou']['image'] }}"/>
<span>Klassiker</span>
<button aria-label="Stillwater Bayou auswählen" data-detail-href="{{ $mapCards['stillwater-bayou']['url'] }}" data-map-preview="stillwater-bayou" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</div>
<div class="map-plan-content">
<span class="map-plan-kicker">HUNTMAP</span>
<h2>Stillwater Bayou</h2>
<p>Interaktive Übersicht mit Filtern für Compounds, Spawns, Versorgung, Extraktionen und Community-Spots.</p>
<div class="map-plan-meta">
<span><strong>2048 × 2048</strong><small>Kartengröße</small></span>
<span><strong>10</strong><small>Markerarten</small></span>
</div>
<ul>
<li><i><svg><use href="#i-check"></use></svg></i>Interaktive Zoom- und Kartenansicht</li>
<li><i><svg><use href="#i-check"></use></svg></i>Compounds, Bosse und Spawns</li>
<li><i><svg><use href="#i-check"></use></svg></i>Supply, Extracts und Türme</li>
<li><i><svg><use href="#i-check"></use></svg></i>Cash Spots mit Community-Daten</li>
<li><i><svg><use href="#i-check"></use></svg></i>Marker filtern und Linien einblenden</li>
</ul>
<a class="map-open-button" href="{{ $mapCards['stillwater-bayou']['url'] }}">Karte öffnen <svg><use href="#i-arrow"></use></svg></a>
</div>
</article>
<article class="map-plan-card" data-map-card="lawson-delta">
<div class="map-plan-art">
<img alt="Karten-Vorschau Lawson Delta" src="{{ $mapCards['lawson-delta']['image'] }}"/>
<span>Live</span>
<button aria-label="Lawson Delta auswählen" data-detail-href="{{ $mapCards['lawson-delta']['url'] }}" data-map-preview="lawson-delta" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</div>
<div class="map-plan-content">
<span class="map-plan-kicker">HUNTMAP</span>
<h2>Lawson Delta</h2>
<p>Alle zentralen Markergruppen auf einer ruhigen, zoombaren Kartenansicht mit einblendbarer Linienebene.</p>
<div class="map-plan-meta">
<span><strong>2048 × 2048</strong><small>Kartengröße</small></span>
<span><strong>10</strong><small>Markerarten</small></span>
</div>
<ul>
<li><i><svg><use href="#i-check"></use></svg></i>Interaktive Zoom- und Kartenansicht</li>
<li><i><svg><use href="#i-check"></use></svg></i>Compounds, Bosse und Spawns</li>
<li><i><svg><use href="#i-check"></use></svg></i>Supply, Extracts und Türme</li>
<li><i><svg><use href="#i-check"></use></svg></i>Cash Spots mit Community-Daten</li>
<li><i><svg><use href="#i-check"></use></svg></i>Marker filtern und Linien einblenden</li>
</ul>
<a class="map-open-button" href="{{ $mapCards['lawson-delta']['url'] }}">Karte öffnen <svg><use href="#i-arrow"></use></svg></a>
</div>
</article>
<article class="map-plan-card" data-map-card="desalle">
<div class="map-plan-art">
<img alt="Karten-Vorschau DeSalle" src="{{ $mapCards['desalle']['image'] }}"/>
<span>Live</span>
<button aria-label="DeSalle auswählen" data-detail-href="{{ $mapCards['desalle']['url'] }}" data-map-preview="desalle" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</div>
<div class="map-plan-content">
<span class="map-plan-kicker">HUNTMAP</span>
<h2>DeSalle</h2>
<p>Compounds, Extraktionen, Supply-Punkte und zusätzliche Community-Hinweise übersichtlich gebündelt.</p>
<div class="map-plan-meta">
<span><strong>2048 × 2048</strong><small>Kartengröße</small></span>
<span><strong>10</strong><small>Markerarten</small></span>
</div>
<ul>
<li><i><svg><use href="#i-check"></use></svg></i>Interaktive Zoom- und Kartenansicht</li>
<li><i><svg><use href="#i-check"></use></svg></i>Compounds, Bosse und Spawns</li>
<li><i><svg><use href="#i-check"></use></svg></i>Supply, Extracts und Türme</li>
<li><i><svg><use href="#i-check"></use></svg></i>Cash Spots mit Community-Daten</li>
<li><i><svg><use href="#i-check"></use></svg></i>Marker filtern und Linien einblenden</li>
</ul>
<a class="map-open-button" href="{{ $mapCards['desalle']['url'] }}">Karte öffnen <svg><use href="#i-arrow"></use></svg></a>
</div>
</article>
<article class="map-plan-card" data-map-card="mammons-gulch">
<div class="map-plan-art">
<img alt="Karten-Vorschau Mammon's Gulch" src="{{ $mapCards['mammons-gulch']['image'] }}"/>
<span>Live</span>
<button aria-label="Mammon's Gulch auswählen" data-detail-href="{{ $mapCards['mammons-gulch']['url'] }}" data-map-preview="mammons-gulch" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</div>
<div class="map-plan-content">
<span class="map-plan-kicker">HUNTMAP</span>
<h2>Mammon's Gulch</h2>
<p>Vollständige 2048er-Karte mit Markerfiltern, Cash Spots und direktem Wechsel zwischen allen Karten.</p>
<div class="map-plan-meta">
<span><strong>2048 × 2048</strong><small>Kartengröße</small></span>
<span><strong>10</strong><small>Markerarten</small></span>
</div>
<ul>
<li><i><svg><use href="#i-check"></use></svg></i>Interaktive Zoom- und Kartenansicht</li>
<li><i><svg><use href="#i-check"></use></svg></i>Compounds, Bosse und Spawns</li>
<li><i><svg><use href="#i-check"></use></svg></i>Supply, Extracts und Türme</li>
<li><i><svg><use href="#i-check"></use></svg></i>Cash Spots mit Community-Daten</li>
<li><i><svg><use href="#i-check"></use></svg></i>Marker filtern und Linien einblenden</li>
</ul>
<a class="map-open-button" href="{{ $mapCards['mammons-gulch']['url'] }}">Karte öffnen <svg><use href="#i-arrow"></use></svg></a>
</div>
</article>
</div>
</section><section class="maps-view-panel" data-maps-panel="features" hidden="">
<article class="maps-feature-overview">
<header>
<div>
<span>MARKER-LEGENDE</span>
<h2>Alles, was auf den Karten steckt</h2>
<p>Die Karten verwenden dieselben zehn Markergruppen und lassen sich jederzeit einzeln filtern.</p>
</div>
<button data-maps-view-shortcut="maps" type="button">Zurück zu den Karten</button>
</header>
<div class="maps-marker-grid">
<article><i>01</i><div><strong>Compounds</strong><small>Alle zentralen Orte</small></div></article>
<article><i>02</i><div><strong>Bosse</strong><small>Boss- und Lair-Hinweise</small></div></article>
<article><i>03</i><div><strong>Spawns</strong><small>Mögliche Startpunkte</small></div></article>
<article><i>04</i><div><strong>Supply</strong><small>Versorgungspunkte</small></div></article>
<article><i>05</i><div><strong>Extracts</strong><small>Extraktionspunkte</small></div></article>
<article class="cash"><i>06</i><div><strong>Cash Spots</strong><small>Community-Funde mit Bildern</small></div></article>
<article><i>07</i><div><strong>Türme</strong><small>Aussicht und Orientierung</small></div></article>
<article><i>08</i><div><strong>Bugs</strong><small>Gemeldete Kartenfehler</small></div></article>
<article><i>09</i><div><strong>Wild</strong><small>Wilde Zielpunkte</small></div></article>
<article><i>10</i><div><strong>Tarot</strong><small>Tarotkarten-Fundorte</small></div></article>
</div>
</article>
<div class="maps-feature-detail-grid">
<article>
<span>COMMUNITY</span>
<h3>Cash Spots beitragen</h3>
<p>Neue Fundorte können mit Bild eingereicht und nach Prüfung als Kartenmarker freigeschaltet werden.</p>
<button data-toast="Cash-Spot-Einreichung geöffnet" type="button">Spot einreichen</button>
</article>
<article>
<span>INTERAKTIV</span>
<h3>Filtern statt überladen</h3>
<p>Blende nur die Marker ein, die du gerade brauchst. Linien und Kartendetails bleiben getrennt steuerbar.</p>
<button data-maps-view-shortcut="maps" type="button">Karte auswählen</button>
</article>
<article>
<span>FEEDBACK</span>
<h3>Votes und Kommentare</h3>
<p>Community Cash Spots können bewertet und kommentiert werden, damit hilfreiche Funde sichtbar bleiben.</p>
<button data-toast="Community-Hinweise geöffnet" type="button">Mehr erfahren</button>
</article>
</div>
</section><section class="maps-bottom-note">
<div>
<span>AKTUELL VERFÜGBAR</span>
<h2>Stillwater Bayou, Lawson Delta, DeSalle und Mammon's Gulch</h2>
</div>
<p>Die Detailkarten werden später direkt mit den echten Kartenbildern, Markerdaten und Community-Funktionen verbunden.</p>
</section><section aria-labelledby="mapsWorkflowTitle" class="maps-workflow">
<div class="maps-workflow-intro">
<span>DEIN WEG DURCH DEN BAYOU</span>
<h2 id="mapsWorkflowTitle">Planen. Filtern. Im Match schneller entscheiden.</h2>
<p>
      Wähle deine Karte, reduziere die Ansicht auf die wirklich wichtigen Marker
      und nutze Community-Hinweise für deine nächste Route.
    </p>
<button data-maps-view-shortcut="features" type="button">Marker &amp; Funktionen ansehen</button>
</div>
<div class="maps-workflow-steps">
<article>
<i>01</i>
<div>
<strong>Karte auswählen</strong>
<p>Direkt zwischen Stillwater Bayou, Lawson Delta, DeSalle und Mammon's Gulch wechseln.</p>
</div>
</article>
<article>
<i>02</i>
<div>
<strong>Ansicht reduzieren</strong>
<p>Compounds, Spawns, Supply, Extracts, Türme oder Cash Spots gezielt ein- und ausblenden.</p>
</div>
</article>
<article>
<i>03</i>
<div>
<strong>Community-Wissen nutzen</strong>
<p>Fundorte prüfen, bewerten, kommentieren und neue Cash Spots mit Bild einreichen.</p>
</div>
</article>
</div>
</section><section aria-label="Huntmaps Community Übersicht" class="maps-community-section">
<article class="maps-community-main">
<header>
<div>
<span>COMMUNITY-FUNDORTE</span>
<h2>Neue Hinweise aus den Huntmaps</h2>
</div>
<button data-toast="Alle Community-Fundorte geöffnet" type="button">Alle ansehen</button>
</header>
<div class="maps-community-list">
<article>
<div class="maps-community-index">01</div>
<div>
<strong>Oben in der Scheune</strong>
<p>Stillwater Bayou · Cash Spot mit Bild</p>
</div>
<span>18 Stimmen</span>
<button aria-label="Fundort öffnen" data-toast="Fundort auf Stillwater Bayou geöffnet" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</article>
<article>
<div class="maps-community-index">02</div>
<div>
<strong>Unter der Treppe im Lair</strong>
<p>Mammon's Gulch · bestätigter Community-Hinweis</p>
</div>
<span>12 Stimmen</span>
<button aria-label="Fundort öffnen" data-toast="Fundort auf Mammon's Gulch geöffnet" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</article>
<article>
<div class="maps-community-index">03</div>
<div>
<strong>Tarotkarte am Nordpfad</strong>
<p>DeSalle · neuer Marker in Prüfung</p>
</div>
<span>6 Kommentare</span>
<button aria-label="Hinweis öffnen" data-toast="Tarotkarten-Hinweis geöffnet" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</article>
</div>
</article>
<aside class="maps-community-side">
<span>HUNTMAPS STATUS</span>
<h2>Alles an einem Ort</h2>
<div class="maps-community-numbers">
<article><strong>4</strong><small>aktive Karten</small></article>
<article><strong>10</strong><small>Markergruppen</small></article>
<article><strong>Live</strong><small>Community-Daten</small></article>
</div>
<div class="maps-community-chips">
<span>Zoom</span>
<span>Filter</span>
<span>Linien</span>
<span>Votes</span>
<span>Kommentare</span>
<span>Cash Spots</span>
</div>
<button data-toast="Cash-Spot-Einreichung geöffnet" type="button">Neuen Fundort melden</button>
</aside>
</section><section aria-labelledby="mapsHelpTitle" class="maps-help-section">
<header>
<span>KURZ ERKLÄRT</span>
<h2 id="mapsHelpTitle">Was du auf der Maps-Seite machen kannst</h2>
</header>
<div class="maps-help-rows">
<article>
<strong>Kann ich alle Marker gleichzeitig sehen?</strong>
<p>Ja. Du kannst alle Kategorien einblenden oder die Ansicht auf einzelne Markerarten reduzieren.</p>
</article>
<article>
<strong>Woher kommen die Cash Spots?</strong>
<p>Die Community kann Fundorte mit Bildern einreichen. Neue Einträge werden vor der Freischaltung geprüft.</p>
</article>
<article>
<strong>Funktioniert die Karte auch auf dem Handy?</strong>
<p>Die Kartenansicht ist für Desktop und Mobile vorbereitet und bleibt zoombar sowie filterbar.</p>
</article>
<article>
<strong>Kann ich falsche Marker melden?</strong>
<p>Votes, Kommentare und spätere Meldefunktionen helfen dabei, veraltete oder unklare Hinweise zu erkennen.</p>
</article>
</div>
</section></div></section>
<div class="toast" id="toast"></div>
</main>
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = window.location.href;</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-maps/maps-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/maps-live.js')) ?: time() }}"></script>
</body>
</html>
