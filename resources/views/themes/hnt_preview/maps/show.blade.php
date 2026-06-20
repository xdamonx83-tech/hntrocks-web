@extends('themes.hnt_preview.maps.layout')

@section('title', $map['name'].' · HNT Maps')

@section('content')
@php
    $viewer = auth()->user();
    $viewerName = $viewer?->name ?: $viewer?->username;
    $backUrl = $viewer ? route('feed.index') : route('home');
@endphp

<div class="hnt-map-app" data-map-app>
    <button class="hnt-map-tools-trigger" type="button" data-map-tools-toggle aria-controls="hntMapTools" aria-expanded="false">
        <i class="ph ph-sliders-horizontal" aria-hidden="true"></i>
        <span>{{ __('ui.maps_tools_open') }}</span>
    </button>
    <button class="hnt-map-tools-backdrop" type="button" data-map-tools-backdrop aria-label="{{ __('ui.maps_tools_close') }}" tabindex="-1"></button>

    <aside class="hnt-map-tools" id="hntMapTools" data-map-tools-panel aria-label="{{ __('ui.maps_tools_aria') }}">
        <header class="hnt-map-tools-head">
            <div>
                <span class="hnt-map-tools-brand">HNT Maps</span>
                <strong>{{ $map['name'] }}</strong>
            </div>
            <button type="button" class="hnt-map-tools-close" data-map-tools-close aria-label="{{ __('ui.maps_tools_close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </header>

        <a class="hnt-map-home-link" href="{{ $backUrl }}">
            <i class="ph ph-arrow-left" aria-hidden="true"></i>
            {{ $viewer ? __('ui.maps_back_feed') : __('ui.maps_back_home') }}
        </a>

        <section class="hnt-map-account">
            @if($viewer)
                <span class="hnt-map-account-avatar">
                    @if($viewer->avatarUrl())
                        <img src="{{ $viewer->avatarUrl() }}" alt="">
                    @else
                        <i class="ph ph-user" aria-hidden="true"></i>
                    @endif
                </span>
                <div>
                    <span>{{ __('ui.maps_signed_in_as') }}</span>
                    <strong>{{ $viewerName }}</strong>
                    <a href="{{ route('profile.show') }}">{{ __('ui.preview_nav_profile') }}</a>
                </div>
            @else
                <span class="hnt-map-account-avatar"><i class="ph ph-user" aria-hidden="true"></i></span>
                <div>
                    <strong>{{ __('ui.maps_guest_title') }}</strong>
                    <span>{{ __('ui.maps_guest_text') }}</span>
                    <span class="hnt-map-account-links"><a href="{{ route('login') }}">{{ __('ui.login') }}</a><a href="{{ route('register') }}">{{ __('ui.register') }}</a></span>
                </div>
            @endif
        </section>

        <div class="hnt-map-tools-scroll">
            <section class="hnt-map-tool-section">
                <label for="hntMapSelect">{{ __('ui.maps_choose_map') }}</label>
                <div class="hnt-map-select-wrap">
                    <i class="ph ph-map-trifold" aria-hidden="true"></i>
                    <select id="hntMapSelect" data-map-select>
                        @foreach($availableMaps as $availableMap)
                            <option value="{{ $availableMap['url'] }}" @selected($availableMap['slug'] === $map['slug'])>{{ $availableMap['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <a class="hnt-map-overview-link" href="{{ route('maps.index') }}">{{ __('ui.maps_back') }}</a>
            </section>

            <section class="hnt-map-tool-section">
                <label for="hntMapSearch">{{ __('ui.maps_search') }}</label>
                <div class="hnt-map-search-wrap" aria-disabled="true">
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <input id="hntMapSearch" type="search" placeholder="{{ __('ui.maps_search_placeholder') }}" disabled>
                </div>
                <small>{{ __('ui.maps_search_followup') }}</small>
            </section>

            <section class="hnt-map-tool-section" aria-label="{{ __('ui.maps_filters') }}">
                <div class="hnt-map-tool-title">
                    <span>{{ __('ui.maps_filters') }}</span>
                    <small>{{ __('ui.maps_filter_help') }}</small>
                </div>
                <div class="hnt-map-filter-options">
                    @foreach(['compound', 'boss', 'spawn', 'supply', 'extract', 'cash'] as $type)
                        <label class="hnt-map-filter-chip hnt-map-filter-chip--{{ $type }}">
                            <input type="checkbox" value="{{ $type }}" checked data-map-filter>
                            <span aria-hidden="true"></span>{{ __('ui.maps_type_'.$type) }}
                        </label>
                    @endforeach
                </div>
            </section>

            @if($map['lines_url'])
                <section class="hnt-map-tool-section">
                    <div class="hnt-map-tool-title">
                        <span>{{ __('ui.maps_layers') }}</span>
                    </div>
                    <label class="hnt-map-layer-toggle">
                        <span><i class="ph ph-path" aria-hidden="true"></i>{{ __('ui.maps_layer_lines') }}</span>
                        <input type="checkbox" checked data-map-lines-toggle>
                    </label>
                </section>
            @endif
        </div>

        <footer class="hnt-map-tools-footer">
            <button type="button" class="btn-create hnt-map-reset" data-map-reset>
                <i class="ph ph-arrows-out-cardinal" aria-hidden="true"></i>{{ __('ui.maps_reset_view') }}
            </button>
            <span>{{ __('ui.maps_read_only') }}</span>
        </footer>
    </aside>

    <main class="hnt-map-stage">
        @if(! $imageAvailable || $dataError)
            <section class="hnt-map-error" role="status">
                <i class="ph ph-map-trifold" aria-hidden="true"></i>
                <div>
                    <span>{{ __('ui.maps_unavailable_kicker') }}</span>
                    <h1>{{ __('ui.maps_unavailable_title') }}</h1>
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
            <div id="hntMap" class="hnt-map-canvas" aria-label="{{ __('ui.maps_canvas_aria', ['map' => $map['name']]) }}"></div>
            @if(empty($markers))
                <p class="hnt-map-empty-markers">{{ __('ui.maps_no_markers') }}</p>
            @endif
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
    </main>
</div>
@endsection
