@php
    $siteUrl = 'https://hnt.rocks';
    $canonicalUrl = 'https://hnt.rocks/';
    $title = __('ui.landing_meta_title');
    $description = __('ui.landing_meta_description');
    $locale = app()->getLocale();
    $ogLocale = match ($locale) {
        'de' => 'de_DE',
        'es' => 'es_ES',
        'ru' => 'ru_RU',
        default => 'en_US',
    };
    $ogImage = $siteUrl.'/assets/socialite/images/seo/hnt-og-default.png';

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $siteUrl.'/#organization',
                'name' => 'HNT.rocks',
                'url' => $siteUrl.'/',
                'logo' => asset('assets/socialite/images/logo-light.png'),
            ],
            [
                '@type' => 'WebSite',
                '@id' => $siteUrl.'/#website',
                'url' => $siteUrl.'/',
                'name' => 'HNT.rocks',
                'description' => $description,
                'publisher' => [
                    '@id' => $siteUrl.'/#organization',
                ],
                'inLanguage' => str_replace('_', '-', $locale),
            ],
            [
                '@type' => 'WebPage',
                '@id' => $siteUrl.'/#webpage',
                'url' => $canonicalUrl,
                'name' => $title,
                'description' => $description,
                'isPartOf' => [
                    '@id' => $siteUrl.'/#website',
                ],
                'about' => [
                    '@type' => 'VideoGame',
                    'name' => 'Hunt: Showdown',
                ],
                'inLanguage' => str_replace('_', '-', $locale),
            ],
        ],
    ];

    $mapNames = [
        ['slug' => 'stillwater-bayou', 'name' => 'Stillwater Bayou'],
        ['slug' => 'lawson-delta', 'name' => 'Lawson Delta'],
        ['slug' => 'desalle', 'name' => 'DeSalle'],
        ['slug' => 'mammons-gulch', 'name' => 'Mammon’s Gulch'],
    ];
@endphp

@if($mode === 'head')
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="HNT.rocks">
    <meta property="og:locale" content="{{ $ogLocale }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rajdhani:wght@500;600;700&family=Michroma&display=swap">
    <link rel="stylesheet" href="{{ asset('assets/vikinger/fonts/phosphor/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/socialite/css/public-landing.css') }}">
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@else
    <div class="public-landing">
        <header class="public-header">
            <a class="public-brand" href="{{ url('/') }}" aria-label="HNT.ROCKS">
                <span>HNT</span>
                <span>ROCKS</span>
            </a>
            <nav class="public-nav" aria-label="{{ __('ui.landing_nav_aria') }}">
                <a href="{{ url('/feed') }}">{{ __('ui.landing_nav_community') }}</a>
                <a href="{{ url('/moments') }}">{{ __('ui.landing_nav_moments') }}</a>
                <a href="{{ url('/maps') }}">{{ __('ui.landing_nav_maps') }}</a>
                <a href="{{ url('/ready-lobbies') }}">{{ __('ui.landing_nav_ready') }}</a>
                <a href="{{ url('/guides') }}">{{ __('ui.landing_nav_guides') }}</a>
                <a href="{{ url('/teams') }}">{{ __('ui.landing_teams_title') }}</a>
                <a href="{{ url('/cups') }}">{{ __('ui.landing_nav_cups') }}</a>
                <a href="{{ url('/login') }}" class="mobile-auth">{{ __('ui.login') }}</a>
                <a href="{{ url('/register') }}" class="mobile-auth">{{ __('ui.register') }}</a>
            </nav>
            <div class="public-header-actions">
                <a href="{{ url('/login') }}">{{ __('ui.login') }}</a>
                <a class="public-button primary" href="{{ url('/register') }}">{{ __('ui.landing_create_account') }}</a>
            </div>
        </header>

        <main>
            <section class="public-hero" aria-labelledby="public-hero-title">
                <div class="public-hero-inner">
                    <div class="public-hero-copy">
                        <span class="public-eyebrow">{{ __('ui.landing_hero_eyebrow') }}</span>
                        <h1 id="public-hero-title">{{ __('ui.landing_hero_h1_line1') }}<br>{{ __('ui.landing_hero_h1_line2') }}</h1>
                        <p>{{ __('ui.landing_hero_body') }}</p>
                        <div class="public-actions">
                            <a class="public-button primary" href="{{ url('/feed') }}">{{ __('ui.landing_discover_community') }}</a>
                            <a class="public-button secondary" href="{{ url('/register') }}">{{ __('ui.landing_create_account') }}</a>
                        </div>
                    </div>
                    <div class="public-hero-visual" aria-hidden="true">
                        <div class="public-orbit" aria-label="HNT.ROCKS Community">
                            <span class="public-orbit-ring inner"></span>
                            <span class="public-orbit-ring middle"></span>
                            <span class="public-orbit-ring outer"></span>
                            <span class="public-orbit-logo">
                                <span>HNT</span>
                                <span>ROCKS</span>
                            </span>
                        </div>
                        <div class="public-widget community">
                            <i class="ph ph-users-three"></i>
                            <div>
                                <span>{{ __('ui.landing_widget_community') }}</span>
                                <strong>{{ __('ui.landing_feature_community') }}</strong>
                            </div>
                        </div>
                        <div class="public-widget maps">
                            <i class="ph ph-map-trifold"></i>
                            <div>
                                <span>{{ __('ui.landing_widget_maps') }}</span>
                                <strong>{{ __('ui.landing_feature_maps') }}</strong>
                            </div>
                        </div>
                        <div class="public-widget feed">
                            <i class="ph ph-chat-circle-dots"></i>
                            <div>
                                <span>{{ __('ui.landing_widget_feed') }}</span>
                                <strong>{{ __('ui.landing_feature_feed') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="public-stats" aria-label="HNT.ROCKS">
                <div class="public-stats-inner">
                    <div class="public-stat">
                        <i class="ph ph-users-three"></i>
                        <div>
                            <strong>{{ __('ui.landing_stat_members') }}</strong>
                            <span>{{ __('ui.landing_feature_community') }}</span>
                        </div>
                    </div>
                    <div class="public-stat">
                        <i class="ph ph-chat-circle-dots"></i>
                        <div>
                            <strong>{{ __('ui.landing_stat_posts') }}</strong>
                            <span>{{ __('ui.landing_feature_feed') }}</span>
                        </div>
                    </div>
                    <div class="public-stat">
                        <i class="ph ph-map-trifold"></i>
                        <div>
                            <strong>4 {{ __('ui.landing_stat_maps') }}</strong>
                            <span>{{ __('ui.landing_feature_maps') }}</span>
                        </div>
                    </div>
                    <div class="public-stat">
                        <i class="ph ph-map-pin"></i>
                        <div>
                            <strong>{{ __('ui.landing_stat_markers') }}</strong>
                            <span>{{ __('ui.landing_feature_maps') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <section class="public-section public-community" aria-labelledby="public-community-title">
                <div class="public-container public-community-grid">
                    <div class="public-section-copy">
                        <span class="public-eyebrow">{{ __('ui.landing_community_eyebrow') }}</span>
                        <h2 id="public-community-title">{{ __('ui.landing_community_title') }}</h2>
                        <p>{{ __('ui.landing_community_body') }}</p>
                        <a class="public-button primary" href="{{ url('/feed') }}">
                            {{ __('ui.landing_feed_cta') }} &rarr;
                        </a>
                    </div>
                    <div class="public-community-previews">
                        <article class="public-preview-card">
                            <div class="public-card-header">
                                <i class="ph ph-chat-circle-dots"></i>
                                <span>{{ __('ui.landing_public_post') }}</span>
                            </div>
                            <p class="public-post-fallback">{{ __('ui.landing_post_fallback') }}</p>
                            <a class="public-card-link" href="{{ url('/feed') }}">
                                {{ __('ui.landing_feed_cta') }} &rarr;
                            </a>
                        </article>
                        <article class="public-preview-card public-moments-card">
                            <div class="public-card-header">
                                <i class="ph ph-play-circle"></i>
                                <span>Moments</span>
                            </div>
                            <div class="public-moment-visual" aria-hidden="true">
                                <i class="ph ph-play-circle"></i>
                            </div>
                            <h3>{{ __('ui.landing_moment_title') }}</h3>
                            <p>{{ __('ui.landing_moment_body') }}</p>
                            <a class="public-card-link" href="{{ url('/moments') }}">
                                {{ __('ui.landing_moments_cta') }} &rarr;
                            </a>
                        </article>
                    </div>
                </div>
            </section>

            <section class="public-section public-maps-section" aria-labelledby="public-maps-title">
                <div class="public-container public-maps-grid">
                    <div class="public-section-copy">
                        <span class="public-eyebrow">{{ __('ui.landing_maps_eyebrow') }}</span>
                        <h2 id="public-maps-title">{{ __('ui.landing_maps_title') }}</h2>
                        <p>{{ __('ui.landing_maps_body') }}</p>
                        <div class="public-feature-list">
                            <span><i class="ph ph-magnifying-glass"></i>{{ __('ui.landing_maps_search') }}</span>
                            <span><i class="ph ph-sliders-horizontal"></i>{{ __('ui.landing_maps_filter') }}</span>
                            <span><i class="ph ph-ruler"></i>{{ __('ui.landing_maps_measure') }}</span>
                            <span><i class="ph ph-map-pin"></i>{{ __('ui.landing_maps_marker') }}</span>
                            <span>{{ __('ui.landing_maps_compounds') }}</span>
                            <span>{{ __('ui.landing_maps_spawns') }}</span>
                            <span>{{ __('ui.landing_maps_extracts') }}</span>
                            <span>{{ __('ui.landing_maps_supplies') }}</span>
                            <span>{{ __('ui.landing_maps_towers') }}</span>
                            <span>{{ __('ui.landing_maps_cash') }}</span>
                        </div>
                        <a class="public-button primary" href="{{ url('/maps') }}">
                            {{ __('ui.landing_maps_cta') }} &rarr;
                        </a>
                    </div>
                    <div class="public-map-side">
                        <figure class="public-map-visual">
                            <img src="{{ asset('assets/hnt/maps/stillwater-bayou/map.webp') }}" width="1500" height="1014" loading="lazy" alt="Stillwater Bayou">
                            <figcaption>
                                <i class="ph ph-map-trifold"></i>
                                <span>Stillwater Bayou</span>
                                <small>{{ __('ui.landing_map_interactive') }}</small>
                            </figcaption>
                        </figure>
                        <div class="public-map-links">
                            <strong>{{ __('ui.landing_maps_links_heading') }}</strong>
                            <div>
                                @foreach($mapNames as $map)
                                    <a href="{{ url('/maps/'.$map['slug']) }}">
                                        {{ $map['name'] }} {{ __('ui.landing_map_interactive') }} &rarr;
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="public-section public-modules-section" aria-labelledby="public-modules-title">
                <div class="public-container">
                    <div class="public-section-heading">
                        <span class="public-eyebrow">{{ __('ui.landing_modules_eyebrow') }}</span>
                        <h2 id="public-modules-title">{{ __('ui.landing_modules_title') }}</h2>
                        <p>{{ __('ui.landing_modules_body') }}</p>
                    </div>
                    <div class="public-modules-grid">
                        <article class="public-module-card">
                            <i class="ph ph-users-three"></i>
                            <h3>{{ __('ui.landing_ready_title') }}</h3>
                            <p>{{ __('ui.landing_ready_body') }}</p>
                            <div class="public-module-chips" aria-label="{{ __('ui.landing_ready_title') }}">
                                <span>{{ __('ui.landing_chip_platform') }}</span>
                                <span>{{ __('ui.landing_chip_region') }}</span>
                                <span>{{ __('ui.landing_chip_playstyle') }}</span>
                            </div>
                            <a href="{{ url('/ready-lobbies') }}">
                                {{ __('ui.landing_ready_cta') }} &rarr;
                            </a>
                        </article>
                        <article class="public-module-card">
                            <i class="ph ph-users"></i>
                            <h3>{{ __('ui.landing_teams_title') }}</h3>
                            <p>{{ __('ui.landing_teams_body') }}</p>
                            <div class="public-module-chips" aria-label="{{ __('ui.landing_teams_title') }}">
                                <span>{{ __('ui.landing_chip_create') }}</span>
                                <span>{{ __('ui.landing_chip_join') }}</span>
                                <span>{{ __('ui.landing_chip_organize') }}</span>
                            </div>
                            <a href="{{ url('/teams') }}">
                                {{ __('ui.landing_teams_cta') }} &rarr;
                            </a>
                        </article>
                        <article class="public-module-card">
                            <i class="ph ph-book-open"></i>
                            <h3>{{ __('ui.landing_guides_title') }}</h3>
                            <p>{{ __('ui.landing_guides_body') }}</p>
                            <div class="public-module-chips" aria-label="{{ __('ui.landing_guides_title') }}">
                                <span>{{ __('ui.landing_chip_tips') }}</span>
                                <span>{{ __('ui.landing_chip_loadouts') }}</span>
                                <span>{{ __('ui.landing_chip_strategies') }}</span>
                            </div>
                            <a href="{{ url('/guides') }}">
                                {{ __('ui.landing_guides_cta') }} &rarr;
                            </a>
                        </article>
                    </div>
                </div>
            </section>

            <section class="public-section public-community" id="app" aria-labelledby="public-app-title">
                <div class="public-container">
                    <div class="public-section-copy">
                        <span class="public-eyebrow">{{ __('ui.landing_app_eyebrow') }}</span>
                        <h2 id="public-app-title">{{ __('ui.landing_app_title') }}</h2>
                        <p>{{ __('ui.landing_app_body') }}</p>
                        <div class="public-actions">
                            <a class="public-button primary" href="https://play.google.com/store/apps/details?id=rocks.hnt.app" target="_blank" rel="noreferrer">
                                {{ __('ui.landing_app_store_cta') }}
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="public-section public-final-cta" aria-labelledby="public-final-title">
                <div class="public-container public-final-inner">
                    <div>
                        <span class="public-eyebrow">HNT.ROCKS</span>
                        <h2 id="public-final-title">{{ __('ui.landing_cta_title') }}</h2>
                        <p>{{ __('ui.landing_cta_body') }}</p>
                    </div>
                    <div class="public-actions">
                        <a class="public-button primary" href="{{ url('/register') }}">{{ __('ui.landing_register_cta') }}</a>
                        <a class="public-button secondary" href="{{ url('/feed') }}">{{ __('ui.landing_discover_community') }}</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="public-footer">
            <div class="public-container public-footer-inner">
                <a class="public-brand" href="{{ url('/') }}" aria-label="HNT.ROCKS">
                    <span>HNT</span>
                    <span>ROCKS</span>
                </a>
                <nav aria-label="{{ __('ui.landing_nav_aria') }}">
                    <a href="{{ url('/feed') }}">{{ __('ui.landing_nav_community') }}</a>
                    <a href="{{ url('/maps') }}">{{ __('ui.landing_nav_maps') }}</a>
                    <a href="{{ url('/ready-lobbies') }}">{{ __('ui.landing_nav_ready') }}</a>
                    <a href="{{ url('/guides') }}">{{ __('ui.landing_nav_guides') }}</a>
                    <a href="{{ url('/teams') }}">{{ __('ui.landing_teams_title') }}</a>
                    <a href="{{ url('/cups') }}">{{ __('ui.landing_nav_cups') }}</a>
                </nav>
                <div class="public-legal">
                    <a href="{{ url('/datenschutz') }}">{{ __('ui.legal_datenschutz') }}</a>
                    <a href="{{ url('/nutzungsbedingungen') }}">{{ __('ui.legal_nutzungsbedingungen') }}</a>
                    <a href="{{ url('/impressum') }}">{{ __('ui.legal_impressum') }}</a>
                </div>
                <div class="public-languages" aria-label="Language">
                    <a href="{{ route('locale.switch', ['locale' => 'de']) }}">DE</a>
                    <a href="{{ route('locale.switch', ['locale' => 'en']) }}">EN</a>
                </div>
            </div>
        </footer>
    </div>
@endif
