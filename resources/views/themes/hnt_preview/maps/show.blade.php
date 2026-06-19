@extends('themes.hnt_preview.layouts.app')

@section('title', $map['name'].' · HNT Maps')
@section('app_window_class', 'hnt-maps-window')
@section('main_class', 'hnt-maps-main')
@section('right_sidebar')<aside hidden></aside>@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}?v=1.9.4">
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/maps.css') }}?v=1">
@endpush

@section('content')
<div class="hnt-maps-shell">
    <header class="hnt-map-detail-head">
        <div>
            <a class="hnt-map-back" href="{{ route('maps.index') }}"><i class="ph ph-arrow-left" aria-hidden="true"></i>{{ __('ui.maps_back') }}</a>
            <span>{{ __('ui.maps_kicker') }}</span>
            <h1>{{ $map['name'] }}</h1>
            <p>{{ __('ui.maps_detail_intro') }}</p>
        </div>
        <span class="hnt-map-readonly">{{ __('ui.maps_read_only') }}</span>
    </header>

    @if(! $imageAvailable || $dataError)
        <section class="hnt-map-error" role="status">
            <i class="ph ph-map-trifold" aria-hidden="true"></i>
            <div>
                <span>{{ __('ui.maps_unavailable_kicker') }}</span>
                <h2>{{ __('ui.maps_unavailable_title') }}</h2>
                <p>
                    @if(! $imageAvailable)
                        {{ __('ui.maps_image_missing') }}
                    @else
                        {{ $dataError === 'missing' ? __('ui.maps_data_missing') : __('ui.maps_data_invalid') }}
                    @endif
                </p>
            </div>
        </section>
    @else
        <section class="hnt-map-workspace">
            <div class="hnt-map-filter" aria-label="{{ __('ui.maps_filters') }}">
                <div>
                    <span>{{ __('ui.maps_filters') }}</span>
                    <strong>{{ __('ui.maps_filter_help') }}</strong>
                </div>
                <div class="hnt-map-filter-options">
                    @foreach(['compound', 'boss', 'spawn', 'supply', 'extract', 'cash'] as $type)
                        <label class="hnt-map-filter-chip hnt-map-filter-chip--{{ $type }}">
                            <input type="checkbox" value="{{ $type }}" checked data-map-filter>
                            <span aria-hidden="true"></span>{{ __('ui.maps_type_'.$type) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div id="hntMap" class="hnt-map-canvas" aria-label="{{ __('ui.maps_canvas_aria', ['map' => $map['name']]) }}"></div>
            @if(empty($markers))
                <p class="hnt-map-empty-markers">{{ __('ui.maps_no_markers') }}</p>
            @endif
        </section>

        <script id="hntMapConfig" type="application/json">{!! json_encode([
            'imageUrl' => $map['image_url'],
            'linesUrl' => $map['lines_url'],
            'width' => $map['width'],
            'height' => $map['height'],
            'markers' => $markers,
            'typeLabels' => collect(['compound', 'boss', 'spawn', 'supply', 'extract', 'cash'])
                ->mapWithKeys(fn ($type) => [$type => __('ui.maps_type_'.$type)]),
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    @endif

    <p class="hnt-map-disclaimer">{{ __('ui.maps_disclaimer') }}</p>
</div>
@endsection

@if($imageAvailable && ! $dataError)
    @push('scripts')
        <script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}?v=1.9.4" defer></script>
        <script src="{{ asset('assets/hnt/maps/maps.js') }}?v=1" defer></script>
    @endpush
@endif
