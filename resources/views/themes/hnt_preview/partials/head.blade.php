<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $hntTitle = trim($__env->yieldContent('title', 'HNT Preview · hnt.rocks'));
    $hntDescription = trim(strip_tags($__env->yieldContent('meta_description', __('ui.default_meta_description'))));
    $hntCanonical = trim($__env->yieldContent('canonical', url()->current()));
    $hntOgTitle = trim($__env->yieldContent('og_title', $hntTitle));
    $hntOgDescription = trim(strip_tags($__env->yieldContent('og_description', $hntDescription)));
    $hntOgUrl = trim($__env->yieldContent('og_url', $hntCanonical));
    $hntOgImage = trim($__env->yieldContent('og_image', ''));
@endphp
<meta name="robots" content="@yield('robots', 'noindex,nofollow')">
<meta name="color-scheme" content="dark">
<title>{{ $hntTitle }}</title>
<meta name="description" content="{{ $hntDescription }}">
<link rel="canonical" href="{{ $hntCanonical }}">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:title" content="{{ $hntOgTitle }}">
<meta property="og:description" content="{{ $hntOgDescription }}">
<meta property="og:url" content="{{ $hntOgUrl }}">
<meta property="og:site_name" content="HNT.rocks">
@if($hntOgImage !== '')
    <meta property="og:image" content="{{ $hntOgImage }}">
@endif
<meta name="twitter:card" content="{{ $hntOgImage !== '' ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $hntOgTitle }}">
<meta name="twitter:description" content="{{ $hntOgDescription }}">
@if($hntOgImage !== '')
    <meta name="twitter:image" content="{{ $hntOgImage }}">
@endif
<link href="{{ asset('assets/socialite/images/favicon.png') }}" rel="icon" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/hnt/crowns/crowns-cosmetics.css') }}?v=637">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css">
<link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/styles.css') }}?v=779">
<link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/comms-dock.css') }}?v=1">
