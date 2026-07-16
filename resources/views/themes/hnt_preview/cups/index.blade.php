@php
    $demoOverview = app(\App\Services\Cups\CupOverviewService::class)->forViewer(auth()->user());
    $demoCup = $demoOverview['featuredCup'] ?? null;
    $demoViewerTeam = $demoOverview['viewerTeam'] ?? null;
    $demoCupUrl = $demoCup ? route('cups.show', $demoCup) : route('cups.index');
    $demoTeamUrl = ($demoCup && $demoViewerTeam)
        ? route('cups.teams.index', $demoCup)
        : $demoCupUrl;
    $cupsCssVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cups-overview.css')) ?: time();
    $cupsJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cups-overview.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta content="index,follow" name="robots"/>
<title>Cups · HNT.ROCKS</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cups-overview.css') }}?v={{ $cupsCssVersion }}" rel="stylesheet"/>
</head>
<body data-page="cups">
@include('themes.hnt_preview.cups.demo.demo-svg')
<main class="app-shell cups-page-shell"
      data-cups-active-url="{{ route('cups.index', ['status' => 'active']) }}"
      data-cups-mine-url="{{ route('cups.index', ['mine' => 1]) }}"
      data-cups-submissions-url="{{ $demoCup ? route('cups.show', $demoCup).'#submissions' : route('cups.index') }}"
      data-cups-hall-url="{{ route('hall-of-fame.index') }}">
@include('themes.hnt_preview.partials.header')
<section class="cups-stage">
<div aria-label="Cup Übersicht" class="cups-scroll" id="cupsScroll" tabindex="0">
@include('themes.hnt_preview.cups.demo.demo-overview')
<section class="cups-center-flow" id="cupsCenterFlow">
<article class="cups-center-card">
@include('themes.hnt_preview.cups.demo.demo-center-primary')
@include('themes.hnt_preview.cups.demo.demo-center-directory')
</article>
</section>
</div>
@include('themes.hnt_preview.cups.demo.demo-left')
@include('themes.hnt_preview.cups.demo.demo-right')
</section>
<div class="toast" id="toast"></div>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v=20260710-1"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-cups/cups-overview.js') }}?v={{ $cupsJsVersion }}"></script>
</body>
</html>
