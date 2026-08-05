@php
    $faqKeys = ['available', 'markers', 'mobile', 'cash'];
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'CollectionPage',
                '@id' => route('maps.index').'#collection',
                'url' => route('maps.index'),
                'name' => __('ui.maps_meta_title'),
                'description' => __('ui.maps_meta_description'),
                'inLanguage' => str_replace('_', '-', app()->getLocale()),
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => $maps->count(),
                    'itemListElement' => $maps->values()->map(
                        fn (array $map, int $index): array => [
                            '@type' => 'ListItem',
                            'position' => $index + 1,
                            'name' => $map['name'],
                            'url' => route('maps.show', $map['slug']),
                            'image' => $map['image_url'],
                        ]
                    )->all(),
                ],
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => route('maps.index').'#breadcrumbs',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.maps_dashboard_home'), 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.maps_dashboard_maps'), 'item' => route('maps.index')],
                ],
            ],
            [
                '@type' => 'FAQPage',
                '@id' => route('maps.index').'#faq',
                'mainEntity' => collect($faqKeys)->map(
                    fn (string $faq): array => [
                        '@type' => 'Question',
                        'name' => __('ui.maps_faq_'.$faq.'_q'),
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => __('ui.maps_faq_'.$faq.'_a')],
                    ]
                )->all(),
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
