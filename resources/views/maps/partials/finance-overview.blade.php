@php
    $featured = $maps->first();
    $totalMarkers = max(1, (int) $maps->sum('marker_count'));
    $readyMaps = $maps->where('data_available', true)->count();
    $faqKeys = ['available', 'markers', 'mobile', 'cash'];

    $ends = [];
    $running = 0.0;
    foreach ($maps as $map) {
        $running += ((int) $map['marker_count'] / $totalMarkers) * 100;
        $ends[] = round($running, 2);
    }

    $stats = [
        ['icon' => 'map-trifold', 'value' => $maps->count(), 'label' => __('ui.maps_dashboard_stat_maps')],
        ['icon' => 'map-pin', 'value' => $maps->sum('marker_count'), 'label' => __('ui.maps_dashboard_stat_markers')],
        ['icon' => 'check-circle', 'value' => $readyMaps, 'label' => __('ui.maps_dashboard_stat_ready')],
        ['icon' => 'faders', 'value' => 4, 'label' => __('ui.maps_dashboard_stat_tools')],
    ];

    $features = [
        ['icon' => 'path', 'title' => __('ui.maps_dashboard_routes_title'), 'text' => __('ui.maps_dashboard_routes_text')],
        ['icon' => 'faders', 'title' => __('ui.maps_feature_filters_title'), 'text' => __('ui.maps_feature_filters_text')],
        ['icon' => 'coins', 'title' => __('ui.maps_feature_cash_title'), 'text' => __('ui.maps_feature_cash_text')],
        ['icon' => 'device-mobile', 'title' => __('ui.maps_feature_mobile_title'), 'text' => __('ui.maps_feature_mobile_text')],
    ];
@endphp

<div class="hnt-maps-finance">
    <header class="hnt-maps-finance__heading">
        <div>
            <h1>{{ __('ui.maps_title') }}</h1>
            <nav aria-label="Breadcrumb">
                <a href="{{ url('/') }}">{{ __('ui.maps_dashboard_home') }}</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ __('ui.maps_dashboard_maps') }}</span>
            </nav>
            <p>{{ __('ui.maps_intro') }}</p>
        </div>
        @if($featured)
            <a class="hnt-maps-finance__cta" href="{{ route('maps.show', $featured['slug']) }}">
                <i class="ph ph-crosshair" aria-hidden="true"></i>
                {{ __('ui.maps_hero_cta_primary', ['map' => $featured['name']]) }}
            </a>
        @endif
    </header>

    <section class="hnt-maps-finance__stats" aria-label="{{ __('ui.maps_dashboard_stats_aria') }}">
        @foreach($stats as $stat)
            <article>
                <i class="ph ph-{{ $stat['icon'] }}" aria-hidden="true"></i>
                <span>{{ $stat['label'] }}</span>
                <strong>{{ number_format((int) $stat['value'], 0, ',', '.') }}</strong>
            </article>
        @endforeach
    </section>

    <div class="hnt-maps-finance__grid">
        <section class="hnt-maps-finance__feature card" aria-labelledby="mapsFeaturedTitle">
            <header>
                <div><small>{{ __('ui.maps_kicker') }}</small><h2 id="mapsFeaturedTitle">{{ __('ui.maps_dashboard_featured') }}</h2></div>
                @if($featured)<a href="{{ route('maps.show', $featured['slug']) }}">{{ __('ui.maps_open') }} <i class="ph ph-arrow-right"></i></a>@endif
            </header>
            @if($featured)
                <a class="hnt-maps-finance__hero" href="{{ route('maps.show', $featured['slug']) }}">
                    @if($featured['image_available'])
                        <img src="{{ $featured['image_url'] }}" alt="{{ __('ui.maps_preview_alt', ['map' => $featured['name']]) }}" width="2048" height="2048" fetchpriority="high">
                    @endif
                    <div>
                        <span>{{ __('ui.maps_phase_one') }}</span>
                        <h3>{{ $featured['name'] }}</h3>
                        <p>{{ __('ui.maps_map_summary_'.str_replace('-', '_', $featured['slug'])) }}</p>
                        <em>{{ trans_choice('ui.maps_marker_count', $featured['marker_count'], ['count' => $featured['marker_count']]) }}</em>
                    </div>
                </a>
            @endif
        </section>

        <aside class="hnt-maps-finance__distribution card" aria-labelledby="mapsDistributionTitle">
            <header><div><small>{{ __('ui.maps_dashboard_data') }}</small><h2 id="mapsDistributionTitle">{{ __('ui.maps_dashboard_distribution') }}</h2></div></header>
            <div class="hnt-maps-finance__ring"
                 style="--m1:{{ $ends[0] ?? 25 }}%;--m2:{{ $ends[1] ?? 50 }}%;--m3:{{ $ends[2] ?? 75 }}%;"
                 role="img"
                 aria-label="{{ __('ui.maps_dashboard_distribution_aria', ['count' => $maps->sum('marker_count')]) }}">
                <div><strong>{{ number_format((int) $maps->sum('marker_count'), 0, ',', '.') }}</strong><span>{{ __('ui.maps_dashboard_markers_total') }}</span></div>
            </div>
            <ul>
                @foreach($maps as $map)
                    <li><span class="map-{{ $loop->iteration }}"></span><b>{{ $map['name'] }}</b><em>{{ $map['marker_count'] }}</em></li>
                @endforeach
            </ul>
        </aside>

        <section class="hnt-maps-finance__directory card" id="available-maps" aria-labelledby="mapsDirectoryTitle">
            <header>
                <div>
                    <small>{{ __('ui.maps_directory_kicker') }}</small>
                    <h2 id="mapsDirectoryTitle">{{ __('ui.maps_dashboard_all_maps') }}</h2>
                    <p>{{ __('ui.maps_dashboard_all_maps_text') }}</p>
                </div>
            </header>
            <div class="hnt-maps-finance__cards">
                @foreach($maps as $map)
                    <article>
                        <a class="hnt-maps-finance__image" href="{{ route('maps.show', $map['slug']) }}">
                            @if($map['image_available'])
                                <img src="{{ $map['image_url'] }}" alt="{{ __('ui.maps_preview_alt', ['map' => $map['name']]) }}" width="2048" height="2048" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                            @endif
                            <small>{{ $map['data_available'] ? __('ui.maps_dashboard_ready') : __('ui.maps_data_missing_short') }}</small>
                        </a>
                        <div>
                            <h3><a href="{{ route('maps.show', $map['slug']) }}">{{ $map['name'] }}</a></h3>
                            <p>{{ __('ui.maps_map_summary_'.str_replace('-', '_', $map['slug'])) }}</p>
                            <span><i class="ph ph-map-pin"></i>{{ trans_choice('ui.maps_marker_count', $map['marker_count'], ['count' => $map['marker_count']]) }}</span>
                            <a class="hnt-maps-finance__open" href="{{ route('maps.show', $map['slug']) }}">{{ __('ui.maps_dashboard_open') }} <i class="ph ph-arrow-up-right"></i></a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <aside class="hnt-maps-finance__actions card" aria-labelledby="mapsActionsTitle">
            <header><div><small>{{ __('ui.maps_dashboard_tools') }}</small><h2 id="mapsActionsTitle">{{ __('ui.maps_dashboard_actions') }}</h2></div></header>
            <div>
                @foreach($features as $feature)
                    <article><i class="ph ph-{{ $feature['icon'] }}"></i><div><h3>{{ $feature['title'] }}</h3><p>{{ $feature['text'] }}</p></div></article>
                @endforeach
            </div>
        </aside>
    </div>

    <section class="hnt-maps-finance__editorial card" aria-labelledby="mapsEditorialTitle">
        <div><small>{{ __('ui.maps_editorial_kicker') }}</small><h2 id="mapsEditorialTitle">{{ __('ui.maps_seo_intro_title') }}</h2></div>
        <div><p>{{ __('ui.maps_seo_intro_text') }}</p><p>{{ __('ui.maps_seo_intro_text_secondary') }}</p></div>
    </section>

    <section class="hnt-maps-finance__faq card" aria-labelledby="mapsFaqTitle">
        <header><div><small>{{ __('ui.maps_faq_kicker') }}</small><h2 id="mapsFaqTitle">{{ __('ui.maps_faq_title') }}</h2><p>{{ __('ui.maps_dashboard_faq_text') }}</p></div></header>
        <div class="hnt-maps-finance__accordion">
            @foreach($faqKeys as $faq)
                <details @if($loop->first) open @endif>
                    <summary><span>{{ __('ui.maps_faq_'.$faq.'_q') }}</span><i class="ph ph-caret-right"></i></summary>
                    <div><p>{{ __('ui.maps_faq_'.$faq.'_a') }}</p></div>
                </details>
            @endforeach
        </div>
    </section>
</div>
