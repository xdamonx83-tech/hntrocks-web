@php
    $cupDetailCssVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-detail.css')) ?: time();
    $cupDetailThemeVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-detail-theme-live-red.css')) ?: time();
    $cupDetailJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-detail.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta content="{{ ($activeSection ?? 'overview') === 'overview' ? 'index,follow' : 'noindex,follow' }}" name="robots"/>
<title>Summer Hunt Cup · HNT.ROCKS</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail.css') }}?v={{ $cupDetailCssVersion }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail-theme-live-red.css') }}?v={{ $cupDetailThemeVersion }}" rel="stylesheet"/>
</head>
<body data-page="cup-detail">
@include('themes.hnt_preview.cups.demo.demo-svg')
<main class="app-shell cup-detail-page-shell"
      data-cups-active-url="{{ route('cups.index', ['status' => 'active']) }}"
      data-cups-mine-url="{{ route('cups.index', ['mine' => 1]) }}"
      data-cups-submissions-url="{{ route('cups.show.section', [$cup, 'submissions']) }}"
      data-cups-hall-url="{{ route('hall-of-fame.index') }}">
@include('themes.hnt_preview.partials.header')
<section class="cup-detail-stage">
@include('themes.hnt_preview.cups.demo.demo-detail-scroll')
@include('themes.hnt_preview.cups.demo.demo-detail-left')
@include('themes.hnt_preview.cups.demo.demo-detail-right')
</section>
<div class="toast" id="toast"></div>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v=20260710-1"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail.js') }}?v={{ $cupDetailJsVersion }}"></script>
</body>
</html>
