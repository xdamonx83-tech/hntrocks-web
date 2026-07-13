@php
    $localeIsEnglish = app()->getLocale() === 'en';
    $featuredMap = $maps->first();
    $totalMarkers = (int) $maps->sum(fn (array $map): int => (int) ($map['marker_count'] ?? 0));
    $markerTypes = ['compound', 'boss', 'spawn', 'supply', 'extract', 'cash', 'tower', 'bugs', 'wild', 'tarot'];
    $formatNumber = static fn (int $value): string => $localeIsEnglish
        ? number_format($value, 0, '.', ',')
        : number_format($value, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="{{ $localeIsEnglish ? 'en' : 'de' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="index,follow">
<title>{{ __('maps.meta_title') }}</title>
<meta name="description" content="{{ __('maps.meta_description') }}">
<link rel="canonical" href="{{ route('maps.index') }}">
<meta property="og:title" content="{{ __('maps.meta_title') }}">
<meta property="og:description" content="{{ __('maps.meta_description') }}">
<meta property="og:url" content="{{ route('maps.index') }}">
@if($featuredMap && $featuredMap['image_available'])
<meta property="og:image" content="{{ $featuredMap['image_url'] }}">
@endif
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

<section class="maps-stage">
<div class="maps-scroll" id="mapsScroll" tabindex="0">
<div class="maps-content" data-maps-root>
    <section class="maps-overview" aria-labelledby="mapsPageTitle">
        <div class="maps-overview-copy">
            <span class="maps-eyebrow">{{ __('maps.kicker') }}</span>
            <h1 id="mapsPageTitle">{{ __('maps.title') }}</h1>
            <p>{{ __('maps.intro') }}</p>
        </div>
        <div class="maps-overview-stats" aria-label="{{ __('maps.title') }}">
            <article><strong>{{ $formatNumber($maps->count()) }}</strong><span>{{ __('maps.stats.maps') }}</span></article>
            <article><strong>{{ $formatNumber($totalMarkers) }}</strong><span>{{ __('maps.stats.markers') }}</span></article>
            <article><strong>{{ count($markerTypes) }}</strong><span>{{ __('maps.stats.types') }}</span></article>
        </div>
    </section>

    <div class="maps-view-switch" role="tablist" aria-label="{{ __('maps.title') }}">
        <button class="active" id="mapsTabMaps" data-maps-view="maps" type="button" role="tab" aria-controls="mapsPanelMaps" aria-selected="true">{{ __('maps.tabs.maps') }}</button>
        <button id="mapsTabFeatures" data-maps-view="features" type="button" role="tab" aria-controls="mapsPanelFeatures" aria-selected="false" tabindex="-1">{{ __('maps.tabs.features') }}</button>
    </div>

    <section class="maps-view-panel active" id="mapsPanelMaps" data-maps-panel="maps" role="tabpanel" aria-labelledby="mapsTabMaps">
        <div class="maps-grid">
            @foreach($maps as $map)
                <article class="map-card {{ $loop->first ? 'selected' : '' }}" data-map-card="{{ $map['slug'] }}">
                    <div class="map-card-art">
                        @if($map['image_available'])
                            <img src="{{ $map['image_url'] }}" alt="{{ __('maps.map.preview_alt', ['map' => $map['name']]) }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                        @else
                            <span class="map-card-missing"><svg><use href="#i-image"></use></svg>{{ __('maps.map.image_missing') }}</span>
                        @endif
                        <span class="map-card-status">{{ __('maps.map.live') }}</span>
                        <a class="map-card-art-link" href="{{ route('maps.show', $map['slug']) }}" aria-label="{{ __('maps.map.select', ['map' => $map['name']]) }}"><svg><use href="#i-arrow"></use></svg></a>
                    </div>
                    <div class="map-card-content">
                        <span class="map-card-kicker">{{ __('maps.map.kicker') }}</span>
                        <h2>{{ $map['name'] }}</h2>
                        <p>{{ __('ui.maps_map_summary_'.str_replace('-', '_', $map['slug'])) }}</p>
                        @unless($map['data_available'])
                            <p class="map-card-warning">{{ __('maps.map.data_missing') }}</p>
                        @endunless
                        <div class="map-card-meta">
                            <span><strong>{{ $formatNumber((int) $map['width']) }} × {{ $formatNumber((int) $map['height']) }}</strong><small>{{ __('maps.map.size') }}</small></span>
                            <span><strong>{{ $formatNumber((int) $map['marker_count']) }}</strong><small>{{ __('maps.map.markers') }}</small></span>
                        </div>
                        <ul class="map-card-features">
                            @foreach(['zoom', 'core', 'supply', 'cash', 'filters'] as $feature)
                                <li><i><svg><use href="#i-check"></use></svg></i>{{ __('maps.map.feature_'.$feature) }}</li>
                            @endforeach
                        </ul>
                        <a class="map-open-button" href="{{ route('maps.show', $map['slug']) }}">{{ __('maps.map.open') }} <svg><use href="#i-arrow"></use></svg></a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="maps-view-panel" id="mapsPanelFeatures" data-maps-panel="features" role="tabpanel" aria-labelledby="mapsTabFeatures" hidden>
        <article class="maps-feature-overview">
            <header class="maps-feature-head">
                <div>
                    <span>{{ __('maps.features.kicker') }}</span>
                    <h2>{{ __('maps.features.title') }}</h2>
                    <p>{{ __('maps.features.intro') }}</p>
                </div>
                <button data-maps-view-shortcut="maps" type="button">{{ __('maps.features.back') }}</button>
            </header>
            <div class="maps-marker-grid">
                @foreach($markerTypes as $markerType)
                    <article class="{{ $markerType === 'cash' ? 'cash' : '' }}">
                        <i>{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</i>
                        <div><strong>{{ __('maps.marker_types.'.$markerType.'.title') }}</strong><small>{{ __('maps.marker_types.'.$markerType.'.text') }}</small></div>
                    </article>
                @endforeach
            </div>
        </article>

        <div class="maps-feature-cards">
            @foreach(['community', 'filters', 'feedback'] as $card)
                <article class="maps-feature-card">
                    <span>{{ __('maps.features.cards.'.$card.'.kicker') }}</span>
                    <h3>{{ __('maps.features.cards.'.$card.'.title') }}</h3>
                    <p>{{ __('maps.features.cards.'.$card.'.text') }}</p>
                    @if($featuredMap)
                        <a href="{{ route('maps.show', $featuredMap['slug']) }}">{{ __('maps.features.cards.'.$card.'.action') }}</a>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="maps-workflow" aria-labelledby="mapsWorkflowTitle">
        <div class="maps-workflow-copy">
            <span>{{ __('maps.workflow.kicker') }}</span>
            <h2 id="mapsWorkflowTitle">{{ __('maps.workflow.title') }}</h2>
            <p>{{ __('maps.workflow.intro') }}</p>
            <button data-maps-view-shortcut="features" type="button">{{ __('maps.workflow.action') }}</button>
        </div>
        <div class="maps-workflow-steps">
            @foreach(__('maps.workflow.steps') as $step)
                <article>
                    <i>{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</i>
                    <div><strong>{{ $step['title'] }}</strong><p>{{ $step['text'] }}</p></div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="maps-community" aria-labelledby="mapsCommunityTitle">
        <article class="maps-community-copy">
            <span>{{ __('maps.community.kicker') }}</span>
            <h2 id="mapsCommunityTitle">{{ __('maps.community.title') }}</h2>
            <p>{{ __('maps.community.text') }}</p>
            @if($featuredMap)
                <a href="{{ route('maps.show', $featuredMap['slug']) }}">{{ __('maps.community.action') }}</a>
            @endif
        </article>
        <aside class="maps-community-data">
            <div class="maps-community-numbers">
                <article><strong>{{ $formatNumber($maps->count()) }}</strong><small>{{ __('maps.community.maps') }}</small></article>
                <article><strong>{{ $formatNumber($totalMarkers) }}</strong><small>{{ __('maps.community.markers') }}</small></article>
                <article><strong>Live</strong><small>{{ __('maps.community.live') }}</small></article>
            </div>
            <div class="maps-community-chips">
                @foreach(__('maps.community.chips') as $chip)<span>{{ $chip }}</span>@endforeach
            </div>
        </aside>
    </section>

    <section class="maps-faq" aria-labelledby="mapsFaqTitle">
        <header>
            <span>{{ __('maps.faq.kicker') }}</span>
            <h2 id="mapsFaqTitle">{{ __('maps.faq.title') }}</h2>
        </header>
        <div class="maps-faq-list">
            @foreach(__('maps.faq.items') as $item)
                <article><strong>{{ $item['question'] }}</strong><p>{{ $item['answer'] }}</p></article>
            @endforeach
        </div>
    </section>
</div>
</div>
</section>
</main>
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = window.location.href;</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-maps/maps-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/maps-live.js')) ?: time() }}"></script>
</body>
</html>
