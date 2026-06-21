@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.maps_meta_title'))
@section('robots', 'index,follow')
@section('meta_description', __('ui.maps_meta_description'))
@section('canonical', route('maps.index'))
@section('og_title', __('ui.maps_og_title'))
@section('og_description', __('ui.maps_og_description'))
@section('og_url', route('maps.index'))
@section('og_image', asset('assets/hnt/maps/stillwater-bayou/map.webp'))
@section('app_window_class', 'hnt-maps-window')
@section('main_class', 'hnt-maps-main')
@section('right_sidebar')<aside hidden></aside>@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/maps.css') }}?v=12">
@endpush

@section('content')
<div class="hnt-maps-shell">
    @php($featuredMap = $maps->first())

    <header class="hnt-maps-hero">
        <div class="hnt-maps-hero-copy">
            <span class="hnt-maps-kicker">{{ __('ui.maps_kicker') }}</span>
            <h1>{{ __('ui.maps_title') }}</h1>
            <p>{{ __('ui.maps_intro') }}</p>
            <div class="hnt-maps-hero-actions">
                @if($featuredMap)
                    <a class="btn-create hnt-maps-primary-action" href="{{ route('maps.show', $featuredMap['slug']) }}">
                        {{ __('ui.maps_hero_cta_primary', ['map' => $featuredMap['name']]) }}
                        <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
                    </a>
                @endif
                <a class="hnt-maps-secondary-action" href="#available-maps">{{ __('ui.maps_hero_cta_secondary') }}</a>
            </div>
        </div>
        @if($featuredMap && $featuredMap['image_available'])
            <a class="hnt-maps-hero-preview" href="{{ route('maps.show', $featuredMap['slug']) }}" aria-label="{{ __('ui.maps_open_named', ['map' => $featuredMap['name']]) }}">
                <img src="{{ $featuredMap['image_url'] }}" alt="{{ __('ui.maps_preview_alt', ['map' => $featuredMap['name']]) }}">
                <span><i class="ph ph-crosshair" aria-hidden="true"></i>{{ __('ui.maps_hero_preview_label') }}</span>
            </a>
        @endif
    </header>

    <section class="hnt-maps-directory" id="available-maps" aria-labelledby="hntMapsAvailableTitle">
        <div class="hnt-maps-section-heading">
            <span class="hnt-maps-kicker">{{ __('ui.maps_directory_kicker') }}</span>
            <h2 id="hntMapsAvailableTitle">{{ __('ui.maps_available_title') }}</h2>
            <p>{{ __('ui.maps_available_intro') }}</p>
        </div>

        <div class="hnt-maps-list">
            @foreach($maps as $map)
                <article class="hnt-map-row">
                    <a class="hnt-map-row-preview" href="{{ route('maps.show', $map['slug']) }}" aria-label="{{ __('ui.maps_open_named', ['map' => $map['name']]) }}">
                    @if($map['image_available'])
                        <img src="{{ $map['image_url'] }}" alt="{{ __('ui.maps_preview_alt', ['map' => $map['name']]) }}">
                    @else
                            <span class="hnt-map-row-missing"><i class="ph ph-map-trifold" aria-hidden="true"></i>{{ __('ui.maps_image_missing_short') }}</span>
                    @endif
                    </a>
                    <div class="hnt-map-row-copy">
                        <span>{{ __('ui.maps_phase_one') }}</span>
                        <h3><a href="{{ route('maps.show', $map['slug']) }}">{{ $map['name'] }}</a></h3>
                        <p>{{ __('ui.maps_map_summary_'.str_replace('-', '_', $map['slug'])) }}</p>
                        @unless($map['data_available'])
                            <p class="hnt-map-row-warning">{{ __('ui.maps_data_missing_short') }}</p>
                        @endunless
                    </div>
                    <div class="hnt-map-row-meta">
                        <span>{{ trans_choice('ui.maps_marker_count', $map['marker_count'], ['count' => $map['marker_count']]) }}</span>
                        <a href="{{ route('maps.show', $map['slug']) }}">{{ __('ui.maps_open') }}<i class="ph ph-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="hnt-maps-feature-band" aria-label="{{ __('ui.maps_features_aria') }}">
        @foreach(['filters' => 'faders', 'cash' => 'coins', 'markers' => 'map-pin', 'mobile' => 'device-mobile'] as $feature => $icon)
            <div class="hnt-maps-feature">
                <i class="ph ph-{{ $icon }}" aria-hidden="true"></i>
                <div>
                    <h3>{{ __('ui.maps_feature_'.$feature.'_title') }}</h3>
                    <p>{{ __('ui.maps_feature_'.$feature.'_text') }}</p>
                </div>
            </div>
        @endforeach
    </section>

    <section class="hnt-maps-editorial" aria-labelledby="hntMapsSeoIntroTitle">
        <div>
            <span class="hnt-maps-kicker">{{ __('ui.maps_editorial_kicker') }}</span>
            <h2 id="hntMapsSeoIntroTitle">{{ __('ui.maps_seo_intro_title') }}</h2>
        </div>
        <div class="hnt-maps-editorial-copy">
            <p>{{ __('ui.maps_seo_intro_text') }}</p>
            <p>{{ __('ui.maps_seo_intro_text_secondary') }}</p>
        </div>
    </section>

    <section class="hnt-maps-faq" aria-labelledby="hntMapsFaqTitle">
        <div class="hnt-maps-section-heading">
            <span class="hnt-maps-kicker">{{ __('ui.maps_faq_kicker') }}</span>
            <h2 id="hntMapsFaqTitle">{{ __('ui.maps_faq_title') }}</h2>
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
</div>
@endsection
