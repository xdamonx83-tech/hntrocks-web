@php
    $localeIsEnglish = app()->getLocale() === 'en';
    $maps = collect($maps ?? []);
    $orderedSlugs = ['stillwater-bayou', 'lawson-delta', 'desalle', 'mammons-gulch'];
    $mapsBySlug = $maps->keyBy('slug');
    $markerTypes = ['compound', 'boss', 'spawn', 'supply', 'extract', 'cash', 'tower', 'bugs', 'wild', 'tarot'];

    $mapCards = collect($orderedSlugs)
        ->map(function (string $slug) use ($mapsBySlug): ?array {
            $map = $mapsBySlug->get($slug);

            if (! is_array($map)) {
                return null;
            }

            return [
                ...$map,
                'slug' => $slug,
                'url' => route('maps.show', $slug),
                'display_image' => ($map['image_available'] ?? false)
                    ? $map['image_url']
                    : asset('assets/themes/hnt_preview/dashboard-maps/demo/'.$slug.'.svg'),
            ];
        })
        ->filter()
        ->values();

    $totalMarkers = (int) $mapCards->sum('marker_count');
    $firstMap = $mapCards->first();
    $availableMapNames = implode(', ', $mapCards->pluck('name')->filter()->all());

    $workflowSteps = trans('maps.workflow.steps');
    $workflowSteps = is_array($workflowSteps) ? $workflowSteps : [];

    $communityChips = trans('maps.community.chips');
    $communityChips = is_array($communityChips) ? $communityChips : [];

    $faqItems = trans('maps.faq.items');
    $faqItems = is_array($faqItems) ? $faqItems : [];

    $formatNumber = static function (mixed $value) use ($localeIsEnglish): string {
        return $localeIsEnglish
            ? number_format((int) $value, 0, '.', ',')
            : number_format((int) $value, 0, ',', '.');
    };
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
@if($firstMap && ($firstMap['image_available'] ?? false))
<meta property="og:image" content="{{ $firstMap['image_url'] }}">
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
<div aria-label="{{ __('maps.title') }}" class="maps-scroll" data-maps-root id="mapsScroll" tabindex="0">
<section class="maps-heading">
<div class="maps-heading-copy">
<span>{{ __('maps.kicker') }}</span>
<h1>{{ __('maps.title') }}</h1>
<p>{{ __('maps.intro') }}</p>
</div>
<div class="maps-heading-stats">
<article><strong>{{ $formatNumber($mapCards->count()) }}</strong><span>{{ __('maps.stats.maps') }}</span></article>
<article><strong>2048</strong><span>{{ __('maps.stats.pixel') }}</span></article>
<article><strong>{{ count($markerTypes) }}</strong><span>{{ __('maps.stats.types') }}</span></article>
</div>
</section>
<section aria-label="{{ __('maps.tabs.aria') }}" class="maps-view-switch">
<button class="active" data-maps-view="maps" type="button">{{ __('maps.tabs.maps') }}</button>
<button data-maps-view="features" type="button">{{ __('maps.tabs.features') }}</button>
</section>
<section class="maps-view-panel active" data-maps-panel="maps">
<div class="maps-plan-grid">
@foreach($mapCards as $map)
<article class="map-plan-card {{ $loop->first ? 'selected' : '' }}" data-map-card="{{ $map['slug'] }}">
<div class="map-plan-art">
<img alt="{{ __('maps.map.preview_alt', ['map' => $map['name']]) }}" src="{{ $map['display_image'] }}"/>
<span>{{ $loop->first ? __('maps.map.classic') : __('maps.map.live') }}</span>
<button aria-label="{{ __('maps.map.select', ['map' => $map['name']]) }}" data-detail-href="{{ $map['url'] }}" data-map-preview="{{ $map['slug'] }}" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</div>
<div class="map-plan-content">
<span class="map-plan-kicker">{{ __('maps.map.kicker') }}</span>
<h2>{{ $map['name'] }}</h2>
<p>{{ __('ui.maps_map_summary_'.str_replace('-', '_', $map['slug'])) }}</p>
<div class="map-plan-meta">
<span><strong>{{ $formatNumber($map['width'] ?? 0) }} × {{ $formatNumber($map['height'] ?? 0) }}</strong><small>{{ __('maps.map.size') }}</small></span>
<span><strong>{{ $formatNumber($map['marker_count'] ?? 0) }}</strong><small>{{ __('maps.map.markers') }}</small></span>
</div>
<ul>
@foreach(['zoom', 'core', 'supply', 'cash', 'filters'] as $feature)
<li><i><svg><use href="#i-check"></use></svg></i>{{ __('maps.map.feature_'.$feature) }}</li>
@endforeach
</ul>
<a class="map-open-button" href="{{ $map['url'] }}">{{ __('maps.map.open') }} <svg><use href="#i-arrow"></use></svg></a>
</div>
</article>
@endforeach
</div>
</section>
<section class="maps-view-panel" data-maps-panel="features" hidden>
<article class="maps-feature-overview">
<header>
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
<i>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</i>
<div><strong>{{ __('maps.marker_types.'.$markerType.'.title') }}</strong><small>{{ __('maps.marker_types.'.$markerType.'.text') }}</small></div>
</article>
@endforeach
</div>
</article>
<div class="maps-feature-detail-grid">
@foreach(['community', 'filters', 'feedback'] as $featureCard)
<article>
<span>{{ __('maps.features.cards.'.$featureCard.'.kicker') }}</span>
<h3>{{ __('maps.features.cards.'.$featureCard.'.title') }}</h3>
<p>{{ __('maps.features.cards.'.$featureCard.'.text') }}</p>
<button data-maps-view-shortcut="maps" type="button">{{ __('maps.features.cards.'.$featureCard.'.action') }}</button>
</article>
@endforeach
</div>
</section>
<section class="maps-bottom-note">
<div>
<span>{{ __('maps.available.kicker') }}</span>
<h2>{{ $availableMapNames }}</h2>
</div>
<p>{{ __('maps.available.text') }}</p>
</section>
<section aria-labelledby="mapsWorkflowTitle" class="maps-workflow">
<div class="maps-workflow-intro">
<span>{{ __('maps.workflow.kicker') }}</span>
<h2 id="mapsWorkflowTitle">{{ __('maps.workflow.title') }}</h2>
<p>{{ __('maps.workflow.intro') }}</p>
<button data-maps-view-shortcut="features" type="button">{{ __('maps.workflow.action') }}</button>
</div>
<div class="maps-workflow-steps">
@foreach($workflowSteps as $step)
<article>
<i>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</i>
<div>
<strong>{{ $step['title'] ?? '' }}</strong>
<p>{{ $step['text'] ?? '' }}</p>
</div>
</article>
@endforeach
</div>
</section>
<section aria-label="{{ __('maps.live_data.aria') }}" class="maps-community-section">
<article class="maps-community-main">
<header>
<div>
<span>{{ __('maps.live_data.kicker') }}</span>
<h2>{{ __('maps.live_data.title') }}</h2>
</div>
<button data-maps-view-shortcut="maps" type="button">{{ __('maps.live_data.all') }}</button>
</header>
<div class="maps-community-list">
@foreach($mapCards as $map)
<article>
<div class="maps-community-index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
<div>
<strong>{{ $map['name'] }}</strong>
<p>{{ $formatNumber($map['width'] ?? 0) }} × {{ $formatNumber($map['height'] ?? 0) }} · {{ __('maps.live_data.real_data') }}</p>
</div>
<span>{{ $formatNumber($map['marker_count'] ?? 0) }} {{ __('maps.map.markers') }}</span>
<button aria-label="{{ __('maps.map.open_named', ['map' => $map['name']]) }}" data-detail-href="{{ $map['url'] }}" data-map-preview="{{ $map['slug'] }}" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</article>
@endforeach
</div>
</article>
<aside class="maps-community-side">
<span>{{ __('maps.community.kicker') }}</span>
<h2>{{ __('maps.community.title') }}</h2>
<div class="maps-community-numbers">
<article><strong>{{ $formatNumber($mapCards->count()) }}</strong><small>{{ __('maps.community.maps') }}</small></article>
<article><strong>{{ $formatNumber($totalMarkers) }}</strong><small>{{ __('maps.community.markers') }}</small></article>
<article><strong>Live</strong><small>{{ __('maps.community.live') }}</small></article>
</div>
<div class="maps-community-chips">
@foreach($communityChips as $chip)
<span>{{ $chip }}</span>
@endforeach
</div>
<button data-maps-view-shortcut="maps" type="button">{{ __('maps.community.action') }}</button>
</aside>
</section>
<section aria-labelledby="mapsHelpTitle" class="maps-help-section">
<header>
<span>{{ __('maps.faq.kicker') }}</span>
<h2 id="mapsHelpTitle">{{ __('maps.faq.title') }}</h2>
</header>
<div class="maps-help-rows">
@foreach($faqItems as $item)
<article>
<strong>{{ $item['question'] ?? '' }}</strong>
<p>{{ $item['answer'] ?? '' }}</p>
</article>
@endforeach
</div>
</section>
</div>
</section>
<div class="toast" id="toast"></div>
</main>
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = window.location.href;</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-maps/maps-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/maps-live.js')) ?: time() }}"></script>
</body>
</html>
