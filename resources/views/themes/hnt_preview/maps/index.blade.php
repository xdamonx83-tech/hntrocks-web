@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.maps_title').' · HNT.rocks')
@section('app_window_class', 'hnt-maps-window')
@section('main_class', 'hnt-maps-main')
@section('right_sidebar')<aside hidden></aside>@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/maps.css') }}?v=2">
@endpush

@section('content')
<div class="hnt-maps-shell">
    <header class="hnt-maps-hero">
        <span>{{ __('ui.maps_kicker') }}</span>
        <h1>{{ __('ui.maps_title') }}</h1>
        <p>{{ __('ui.maps_intro') }}</p>
    </header>

    <section class="hnt-maps-grid" aria-label="{{ __('ui.maps_available') }}">
        @foreach($maps as $map)
            <article class="hnt-map-card">
                <a class="hnt-map-card-preview" href="{{ route('maps.show', $map['slug']) }}">
                    @if($map['image_available'])
                        <img src="{{ $map['image_url'] }}" alt="{{ __('ui.maps_preview_alt', ['map' => $map['name']]) }}">
                    @else
                        <span class="hnt-map-card-missing"><i class="ph ph-map-trifold" aria-hidden="true"></i>{{ __('ui.maps_image_missing_short') }}</span>
                    @endif
                </a>
                <div class="hnt-map-card-copy">
                    <span>{{ __('ui.maps_phase_one') }}</span>
                    <h2><a href="{{ route('maps.show', $map['slug']) }}">{{ $map['name'] }}</a></h2>
                    <p>{{ __('ui.maps_card_text', ['count' => $map['marker_count']]) }}</p>
                    @unless($map['data_available'])
                        <p class="hnt-map-card-warning">{{ __('ui.maps_data_missing_short') }}</p>
                    @endunless
                    <a class="btn-create hnt-map-card-action" href="{{ route('maps.show', $map['slug']) }}">{{ __('ui.maps_open') }}</a>
                </div>
            </article>
        @endforeach
    </section>
</div>
@endsection
