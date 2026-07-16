@php
    $viewer = auth()->user();
    $overview = app(\App\Services\Cups\CupOverviewService::class)->forViewer($viewer);
    $stats = $overview['stats'];
    $featuredCup = $overview['featuredCup'];
    $upcomingCups = $overview['upcomingCups'];
    $viewerTeam = $overview['viewerTeam'];
    $topTeams = $overview['topTeams'];

    $formatCount = static fn (int $value): string => number_format($value, 0, '', app()->isLocale('de') ? '.' : ',');
    $trackedCupCount = (int) $stats['active'] + (int) $stats['planned'] + (int) $stats['finished'];

    $timelineCup = $viewerTeam?->cup ?: $featuredCup;
    $timelineEvents = collect([
        [
            'date_label' => __('hnt_cups_overview.now'),
            'title' => __('hnt_cups_overview.status_now'),
            'detail' => $timelineCup?->statusLabel() ?: __('hnt_cups_overview.no_current_cup'),
        ],
        $timelineCup?->registration_closes_at ? [
            'date_label' => $timelineCup->registration_closes_at->translatedFormat('d.m.'),
            'title' => __('hnt_cups_overview.registration_closes'),
            'detail' => $timelineCup->registration_closes_at->translatedFormat('H:i'),
        ] : null,
        $timelineCup?->starts_at ? [
            'date_label' => $timelineCup->starts_at->translatedFormat('d.m.'),
            'title' => __('hnt_cups_overview.cup_starts'),
            'detail' => $timelineCup->starts_at->translatedFormat('H:i'),
        ] : null,
        $timelineCup?->ends_at ? [
            'date_label' => $timelineCup->ends_at->translatedFormat('d.m.'),
            'title' => __('hnt_cups_overview.cup_ends'),
            'detail' => $timelineCup->ends_at->translatedFormat('H:i'),
        ] : null,
    ])->filter()->take(4)->values();

    $featuredPlatforms = $featuredCup
        ? (implode(' / ', $featuredCup->allowedPlatforms()) ?: ($featuredCup->platform ?: __('hnt_cups_overview.all_platforms')))
        : '';
    $featuredPlatformNames = $featuredCup
        ? collect($featuredCup->allowedPlatforms())->map(fn ($platform) => strtolower((string) $platform))
        : collect();
    $featuredPlatformKey = $featuredPlatformNames->contains('pc')
        ? 'pc'
        : ($featuredPlatformNames->contains('playstation') && $featuredPlatformNames->contains('xbox')
            ? 'console'
            : ($featuredPlatformNames->contains('playstation')
                ? 'ps5'
                : ($featuredPlatformNames->contains('xbox') ? 'xbox' : 'all')));
    $featuredLimit = $featuredCup?->participantLimit();
    $featuredStart = $featuredCup?->starts_at
        ? ($featuredCup->starts_at->isFuture()
            ? $featuredCup->starts_at->diffForHumans()
            : $featuredCup->starts_at->translatedFormat('d.m.Y · H:i'))
        : 'TBA';
    $featuredMine = $featuredCup && $viewerTeam && (int) $viewerTeam->cup_id === (int) $featuredCup->id;

    $viewerCup = $viewerTeam?->cup;
    $viewerMembers = $viewerTeam?->members?->where('status', 'active')->values() ?? collect();
    $viewerRequiredMembers = $viewerTeam ? $viewerTeam->requiredMembersCount() : 0;
    $viewerSubmissionMax = $viewerCup ? max(1, (int) ($viewerCup->maxSubmissionsPerParticipant() ?: 3)) : 0;
    $viewerSubmissionUsed = $viewerTeam ? min($viewerSubmissionMax, (int) $viewerTeam->submissions_count) : 0;
    $viewerSubmissionProgress = $viewerSubmissionMax > 0
        ? min(100, (int) round(($viewerSubmissionUsed / $viewerSubmissionMax) * 100))
        : 0;
    $viewerPlatforms = $viewerCup
        ? (implode(' / ', $viewerCup->allowedPlatforms()) ?: ($viewerCup->platform ?: __('hnt_cups_overview.all_platforms')))
        : '';

    $demoCup = $featuredCup;
    $demoViewerTeam = $viewerTeam;
    $demoCupUrl = $featuredCup ? route('cups.show', $featuredCup) : route('cups.index');
    $demoTeamUrl = ($viewerCup && $viewerTeam)
        ? route('cups.teams.index', $viewerCup)
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
<title>{{ __('hnt_cups_overview.title') }} · HNT.ROCKS</title>
<meta name="description" content="{{ __('hnt_cups_overview.meta_description') }}"/>
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
      data-cups-submissions-url="{{ $featuredCup ? route('cups.show', $featuredCup).'#submissions' : route('cups.index') }}"
      data-cups-hall-url="{{ route('hall-of-fame.index') }}">
@include('themes.hnt_preview.partials.header')
<section class="cups-stage">
<div aria-label="{{ __('hnt_cups_overview.title') }}" class="cups-scroll" id="cupsScroll" tabindex="0">
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
