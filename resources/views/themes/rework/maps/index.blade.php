@extends('themes.rework.layouts.app')

@section('title', __('ui.maps_meta_title'))
@section('robots', 'index,follow')
@section('meta_description', __('ui.maps_meta_description'))
@section('canonical', route('maps.index'))
@section('og_title', __('ui.maps_og_title'))
@section('og_description', __('ui.maps_og_description'))
@section('og_url', route('maps.index'))
@section('og_image', asset('assets/hnt/maps/stillwater-bayou/map.webp'))
@section('body_class', 'maps-index-page rework-maps-index-page')
@section('left_col_class', 'rework-maps-index-left')

@section('content')
@php
    $featuredMap = $maps->first();
@endphp

<header class="maps-hero card">
    <div class="maps-hero-copy">
        <span class="maps-kicker">{{ __('ui.maps_kicker') }}</span>
        <h1>{{ __('ui.maps_title') }}</h1>
        <p>{{ __('ui.maps_intro') }}</p>
        <div class="maps-hero-actions">
            @if($featuredMap)
                <a class="maps-primary-action" href="{{ route('maps.show', $featuredMap['slug']) }}">
                    <span>{{ __('ui.maps_hero_cta_primary', ['map' => $featuredMap['name']]) }}</span>
                    <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
                </a>
            @endif
            <a class="maps-secondary-action" href="#available-maps">{{ __('ui.maps_hero_cta_secondary') }}</a>
        </div>
    </div>
    @if($featuredMap && $featuredMap['image_available'])
        <a class="maps-hero-preview" href="{{ route('maps.show', $featuredMap['slug']) }}" aria-label="{{ __('ui.maps_open_named', ['map' => $featuredMap['name']]) }}">
            <img src="{{ $featuredMap['image_url'] }}" alt="{{ __('ui.maps_preview_alt', ['map' => $featuredMap['name']]) }}">
            <span><i class="ph ph-crosshair" aria-hidden="true"></i>{{ __('ui.maps_hero_preview_label') }}</span>
        </a>
    @endif
</header>

<section class="maps-directory" id="available-maps" aria-labelledby="reworkMapsAvailableTitle">
    <div class="maps-section-heading">
        <span class="maps-kicker">{{ __('ui.maps_directory_kicker') }}</span>
        <h2 id="reworkMapsAvailableTitle">{{ __('ui.maps_available_title') }}</h2>
        <p>{{ __('ui.maps_available_intro') }}</p>
    </div>

    <div class="maps-grid">
        @foreach($maps as $map)
            <article class="maps-card card">
                <a class="maps-card-preview" href="{{ route('maps.show', $map['slug']) }}" aria-label="{{ __('ui.maps_open_named', ['map' => $map['name']]) }}">
                    @if($map['image_available'])
                        <img src="{{ $map['image_url'] }}" alt="{{ __('ui.maps_preview_alt', ['map' => $map['name']]) }}">
                    @else
                        <span class="maps-card-missing"><i class="ph ph-map-trifold" aria-hidden="true"></i>{{ __('ui.maps_image_missing_short') }}</span>
                    @endif
                </a>
                <div class="maps-card-body">
                    <span class="maps-card-phase">{{ __('ui.maps_phase_one') }}</span>
                    <h3><a href="{{ route('maps.show', $map['slug']) }}">{{ $map['name'] }}</a></h3>
                    <p>{{ __('ui.maps_map_summary_'.str_replace('-', '_', $map['slug'])) }}</p>
                    @unless($map['data_available'])
                        <p class="maps-card-warning">{{ __('ui.maps_data_missing_short') }}</p>
                    @endunless
                </div>
                <footer class="maps-card-footer">
                    <span>{{ trans_choice('ui.maps_marker_count', $map['marker_count'], ['count' => $map['marker_count']]) }}</span>
                    <a href="{{ route('maps.show', $map['slug']) }}">{{ __('ui.maps_open') }}<i class="ph ph-arrow-right" aria-hidden="true"></i></a>
                </footer>
            </article>
        @endforeach
    </div>
</section>

<section class="maps-feature-grid" aria-label="{{ __('ui.maps_features_aria') }}">
    @foreach(['filters' => 'faders', 'cash' => 'coins', 'markers' => 'map-pin', 'mobile' => 'device-mobile'] as $feature => $icon)
        <article class="maps-feature-card card">
            <i class="ph ph-{{ $icon }}" aria-hidden="true"></i>
            <div>
                <h3>{{ __('ui.maps_feature_'.$feature.'_title') }}</h3>
                <p>{{ __('ui.maps_feature_'.$feature.'_text') }}</p>
            </div>
        </article>
    @endforeach
</section>

<section class="maps-editorial card" aria-labelledby="reworkMapsSeoIntroTitle">
    <div>
        <span class="maps-kicker">{{ __('ui.maps_editorial_kicker') }}</span>
        <h2 id="reworkMapsSeoIntroTitle">{{ __('ui.maps_seo_intro_title') }}</h2>
    </div>
    <div class="maps-editorial-copy">
        <p>{{ __('ui.maps_seo_intro_text') }}</p>
        <p>{{ __('ui.maps_seo_intro_text_secondary') }}</p>
    </div>
</section>

<section class="maps-faq card" aria-labelledby="reworkMapsFaqTitle">
    <div class="maps-section-heading">
        <span class="maps-kicker">{{ __('ui.maps_faq_kicker') }}</span>
        <h2 id="reworkMapsFaqTitle">{{ __('ui.maps_faq_title') }}</h2>
    </div>
    <dl>
        @foreach(['available', 'markers', 'mobile', 'cash'] as $faq)
            <div>
                <dt>{{ __('ui.maps_faq_'.$faq.'_q') }}</dt>
                <dd>{{ __('ui.maps_faq_'.$faq.'_a') }}</dd>
            </div>
        @endforeach
    </dl>
</section>
@endsection
