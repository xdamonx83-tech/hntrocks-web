@php
    $hhBrandTitle = 'HNT.rocks';
    $hhPageTitle = trim($__env->yieldContent('title', ''));
    $hhTitle = $hhPageTitle !== ''
        ? ($hhPageTitle . (str_contains(strtolower($hhPageTitle), 'hnt.rocks') ? '' : ' · ' . $hhBrandTitle))
        : $hhBrandTitle;

    $hhDescription = trim(strip_tags($__env->yieldContent('meta_description', __('ui.default_meta_description'))));
    $hhDescription = $hhDescription !== '' ? \Illuminate\Support\Str::limit($hhDescription, 165, '') : __('ui.default_meta_description');

    $hhBaseUrl = rtrim((string) config('app.url', 'https://hnt.rocks'), '/');
    $hhRequestPath = request()->path();
    $hhCanonicalPath = $hhRequestPath === '/' ? '/' : '/' . ltrim($hhRequestPath, '/');
    $hhCanonicalDefault = $hhBaseUrl . ($hhCanonicalPath === '/' ? '/' : $hhCanonicalPath);
    $hhCanonicalUrl = trim($__env->yieldContent('canonical_url', $hhCanonicalDefault));

    $hhRobots = trim($__env->yieldContent('robots', 'index,follow'));
    $hhOgType = trim($__env->yieldContent('og_type', 'website'));
    $hhOgImage = trim($__env->yieldContent('og_image', asset('assets/socialite/images/seo/hnt-og-default.png')));
    if ($hhOgImage !== '' && ! \Illuminate\Support\Str::startsWith($hhOgImage, ['http://', 'https://'])) {
        $hhOgImage = url($hhOgImage);
    }
    $hhLocale = str_replace('-', '_', app()->getLocale() === 'de' ? 'de_DE' : 'en_US');
    $hhJsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $hhBaseUrl . '/#organization',
                'name' => 'HNT.rocks',
                'url' => $hhBaseUrl . '/',
                'logo' => asset('assets/socialite/images/logo-light.png'),
            ],
            [
                '@type' => 'WebSite',
                '@id' => $hhBaseUrl . '/#website',
                'url' => $hhBaseUrl . '/',
                'name' => 'HNT.rocks',
                'description' => __('ui.default_meta_description'),
                'publisher' => ['@id' => $hhBaseUrl . '/#organization'],
                'inLanguage' => str_replace('_', '-', app()->getLocale()),
            ],
        ],
    ];
@endphp

<title>{{ $hhTitle }}</title>
<meta name="description" content="{{ $hhDescription }}">
<meta name="robots" content="{{ $hhRobots }}">
<link rel="canonical" href="{{ $hhCanonicalUrl }}">

<meta property="og:site_name" content="HNT.rocks">
<meta property="og:locale" content="{{ $hhLocale }}">
<meta property="og:type" content="{{ $hhOgType }}">
<meta property="og:title" content="{{ $hhTitle }}">
<meta property="og:description" content="{{ $hhDescription }}">
<meta property="og:url" content="{{ $hhCanonicalUrl }}">
<meta property="og:image" content="{{ $hhOgImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $hhTitle }}">
<meta name="twitter:description" content="{{ $hhDescription }}">
<meta name="twitter:image" content="{{ $hhOgImage }}">

<script type="application/ld+json">{!! json_encode($hhJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
