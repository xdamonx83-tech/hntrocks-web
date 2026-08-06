@php
    $siteUrl = 'https://hnt.rocks';
    $mapsUrl = $siteUrl.'/maps';
    $faqKeys = ['available', 'markers', 'mobile', 'cash'];

    $mapItems = $maps->values()->map(
        function (array $map, int $index) use ($siteUrl): array {
            $imagePath = parse_url((string) $map['image_url'], PHP_URL_PATH);

            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $map['name'],
                'url' => $siteUrl.'/maps/'.$map['slug'],
                'image' => $siteUrl.(is_string($imagePath) ? $imagePath : ''),
            ];
        }
    )->all();

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'CollectionPage',
                '@id' => $mapsUrl.'#collection',
                'url' => $mapsUrl,
                'name' => __('ui.maps_meta_title'),
                'description' => __('ui.maps_meta_description'),
                'inLanguage' => app()->getLocale() === 'de' ? 'de-DE' : 'en-US',
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => count($mapItems),
                    'itemListElement' => $mapItems,
                ],
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => $mapsUrl.'#breadcrumbs',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => __('ui.home'),
                        'item' => $siteUrl.'/',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => __('ui.maps_title'),
                        'item' => $mapsUrl,
                    ],
                ],
            ],
            [
                '@type' => 'FAQPage',
                '@id' => $mapsUrl.'#faq',
                'mainEntity' => collect($faqKeys)->map(
                    fn (string $faq): array => [
                        '@type' => 'Question',
                        'name' => __('ui.maps_faq_'.$faq.'_q'),
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => __('ui.maps_faq_'.$faq.'_a'),
                        ],
                    ]
                )->all(),
            ],
        ],
    ];
@endphp

@if($mode === 'head')
    <meta name="description" content="{{ __('ui.maps_meta_description') }}">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <link rel="canonical" href="{{ $mapsUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __('ui.maps_og_title') }}">
    <meta property="og:description" content="{{ __('ui.maps_og_description') }}">
    <meta property="og:url" content="{{ $mapsUrl }}">
    <meta property="og:image" content="{{ $siteUrl }}/assets/hnt/maps/stillwater-bayou/map.webp">
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@else
    <main
        id="hnt-maps-seo-fallback"
        style="min-height:100vh;background:#141412;color:#f2e8d8;padding:96px 24px 48px;font-family:Urbanist,Arial,sans-serif"
    >
        <div style="width:min(1180px,100%);margin:0 auto">
            <h1 style="margin:0 0 12px;font-size:38px;line-height:1.1">{{ __('ui.maps_title') }}</h1>
            <p style="max-width:820px;margin:0 0 32px;color:#aaa49a;line-height:1.7">{{ __('ui.maps_intro') }}</p>

            <section aria-labelledby="mapsSeoAvailable">
                <h2 id="mapsSeoAvailable">{{ __('ui.maps_available_title') }}</h2>
                <ul>
                    @foreach($maps as $map)
                        <li>
                            <a href="{{ $siteUrl }}/maps/{{ $map['slug'] }}">{{ $map['name'] }}</a>
                            – {{ trans_choice('ui.maps_marker_count', $map['marker_count'], ['count' => $map['marker_count']]) }}
                        </li>
                    @endforeach
                </ul>
            </section>

            <section aria-labelledby="mapsSeoFaq">
                <h2 id="mapsSeoFaq">{{ __('ui.maps_faq_title') }}</h2>
                @foreach($faqKeys as $faq)
                    <h3>{{ __('ui.maps_faq_'.$faq.'_q') }}</h3>
                    <p>{{ __('ui.maps_faq_'.$faq.'_a') }}</p>
                @endforeach
            </section>
        </div>
    </main>
@endif
