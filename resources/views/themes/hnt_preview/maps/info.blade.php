<section class="hnt-map-detail-info" aria-labelledby="hnt-map-detail-heading" style="background:#121519;color:#e7e9ea;border-top:1px solid #3c4147;padding:2.5rem 1.5rem 3rem;">
    <div style="max-width:72rem;margin:0 auto;">
        <nav aria-label="{{ __('ui.maps_detail_breadcrumb_label') }}" style="font-size:.875rem;margin-bottom:1.25rem;">
            <a href="{{ url('/') }}" style="color:#d9bd86;">{{ __('ui.maps_detail_breadcrumb_home') }}</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ $seo['map_links']['overview']['url'] }}" style="color:#d9bd86;">{{ __('ui.maps_detail_breadcrumb_maps') }}</a>
            <span aria-hidden="true"> / </span>
            <span aria-current="page">{{ $seo['name'] }}</span>
        </nav>

        <h1 id="hnt-map-detail-heading" style="font-size:clamp(1.6rem,3vw,2.5rem);line-height:1.2;margin:0 0 1rem;">{{ __('ui.maps_detail_info_heading', ['map' => $seo['name']]) }}</h1>
        <p style="max-width:52rem;line-height:1.7;margin:0 0 1rem;">{{ $seo['description'] }}</p>
        <p style="font-weight:600;margin:0 0 2rem;">{{ trans_choice('ui.maps_marker_count', $seo['marker_count'], ['count' => $seo['marker_count']]) }}</p>

        <h2 style="font-size:1.25rem;margin:0 0 .75rem;">{{ __('ui.maps_detail_info_marker_types') }}</h2>
        <ul style="display:grid;grid-template-columns:repeat(auto-fit,minmax(10rem,1fr));gap:.5rem 1.5rem;list-style:none;padding:0;margin:0 0 2rem;">
            @foreach (['compound', 'spawn', 'extract', 'supply', 'tower', 'cash'] as $type)
                <li>{{ __('ui.maps_type_'.$type) }}: {{ $seo['marker_counts'][$type] }}</li>
            @endforeach
        </ul>

        @if (count($seo['compounds']) > 0)
            <h2 style="font-size:1.25rem;margin:0 0 .75rem;">{{ __('ui.maps_detail_info_compounds', ['map' => $seo['name']]) }}</h2>
            <ul style="columns:3 12rem;column-gap:2rem;line-height:1.7;margin:0 0 2rem;padding-left:1.25rem;">
                @foreach ($seo['compounds'] as $compound)
                    <li style="break-inside:avoid;">{{ $compound }}</li>
                @endforeach
            </ul>
        @endif

        <h2 style="font-size:1.25rem;margin:0 0 .75rem;">{{ __('ui.maps_detail_info_features') }}</h2>
        <p style="max-width:52rem;line-height:1.7;margin:0 0 2rem;">{{ __('ui.maps_detail_info_features_text') }}</p>

        <h2 style="font-size:1.25rem;margin:0 0 .75rem;">{{ __('ui.maps_detail_info_other_maps') }}</h2>
        <nav aria-label="{{ __('ui.maps_detail_info_other_maps') }}">
            <ul style="display:flex;flex-wrap:wrap;gap:.75rem 1.5rem;list-style:none;padding:0;margin:0;">
                <li><a href="{{ $seo['map_links']['overview']['url'] }}" style="color:#d9bd86;">{{ __('ui.maps_detail_info_overview_link') }}</a></li>
                @foreach ($seo['map_links']['other_maps'] as $otherMap)
                    <li><a href="{{ $otherMap['url'] }}" style="color:#d9bd86;">{{ __('ui.maps_detail_info_map_link', ['map' => $otherMap['name']]) }}</a></li>
                @endforeach
            </ul>
        </nav>
    </div>
</section>
