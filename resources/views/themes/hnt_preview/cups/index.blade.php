@php
    $viewer = auth()->user();
    $overview = app(\App\Services\Cups\CupOverviewService::class)->forViewer($viewer);
    $stats = $overview['stats'];
    $featuredCup = $overview['featuredCup'];
    $upcomingCups = $overview['upcomingCups'];
    $viewerTeam = $overview['viewerTeam'];
    $viewerCupIds = $overview['viewerCupIds'];
    $topTeams = $overview['topTeams'];
    $hallStats = $overview['hallStats'];

    $statusFilter = (string) ($filters['status'] ?? request('status', ''));
    $platformFilter = (string) ($filters['platform'] ?? request('platform', ''));
    $mineFilter = (bool) ($filters['mine'] ?? request()->boolean('mine'));
    $searchFilter = (string) ($filters['q'] ?? request('q', ''));
    $baseQuery = request()->except(['page', 'status', 'mine']);
    $cleanQuery = static fn (array $values): array => array_filter(
        $values,
        static fn ($value): bool => $value !== '' && $value !== null && $value !== false
    );
    $allCupsUrl = route('cups.index', $cleanQuery($baseQuery));
    $statusUrl = static fn (string $status): string => route(
        'cups.index',
        $cleanQuery(array_merge($baseQuery, ['status' => $status]))
    );
    $mineUrl = route('cups.index', $cleanQuery(array_merge($baseQuery, ['mine' => 1])));
    $resetUrl = route('cups.index');
    $panelTitle = $mineFilter
        ? __('hnt_cups_overview.my_cups')
        : match ($statusFilter) {
            'active' => __('hnt_cups_overview.active_cups'),
            'planned' => __('hnt_cups_overview.planned'),
            'finished' => __('hnt_cups_overview.finished'),
            default => __('hnt_cups_overview.all_cups'),
        };

    $formatCount = static fn (int $value): string => number_format($value, 0, '', app()->isLocale('de') ? '.' : ',');
    $trackedCupCount = (int) $stats['active'] + (int) $stats['planned'] + (int) $stats['finished'];

    $cupPlatformLabel = static function (\App\Models\Cup $cup): string {
        return implode(' / ', $cup->allowedPlatforms()) ?: ($cup->platform ?: __('hnt_cups_overview.all_platforms'));
    };
    $cupPlatformKey = static function (\App\Models\Cup $cup): string {
        $platforms = collect($cup->allowedPlatforms())->map(fn ($platform) => strtolower((string) $platform));

        return $platforms->contains('pc')
            ? 'pc'
            : ($platforms->contains('playstation') && $platforms->contains('xbox')
                ? 'console'
                : ($platforms->contains('playstation')
                    ? 'ps5'
                    : ($platforms->contains('xbox') ? 'xbox' : 'all')));
    };
    $cupModeLabel = static fn (\App\Models\Cup $cup): string => $cup->isSoloLeaderboard()
        ? __('hnt_cups_overview.solo')
        : __('hnt_cups_overview.team_size', ['counter' => $cup->team_size]);
    $cupDateLabel = static fn (\App\Models\Cup $cup): string => $cup->starts_at
        ? $cup->starts_at->translatedFormat('F Y')
        : 'TBA';

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

    $featuredPlatforms = $featuredCup ? $cupPlatformLabel($featuredCup) : '';
    $featuredPlatformKey = $featuredCup ? $cupPlatformKey($featuredCup) : 'all';
    $featuredLimit = $featuredCup?->participantLimit();
    $featuredStart = $featuredCup?->starts_at
        ? ($featuredCup->starts_at->isFuture()
            ? $featuredCup->starts_at->diffForHumans()
            : $featuredCup->starts_at->translatedFormat('d.m.Y · H:i'))
        : 'TBA';
    $featuredMine = $featuredCup && $viewerCupIds->contains((int) $featuredCup->id);

    $viewerCup = $viewerTeam?->cup;
    $viewerMembers = $viewerTeam?->members?->where('status', 'active')->values() ?? collect();
    $viewerRequiredMembers = $viewerTeam ? $viewerTeam->requiredMembersCount() : 0;
    $viewerSubmissionMax = $viewerCup ? max(1, (int) ($viewerCup->maxSubmissionsPerParticipant() ?: 3)) : 0;
    $viewerSubmissionUsed = $viewerTeam ? min($viewerSubmissionMax, (int) $viewerTeam->submissions_count) : 0;
    $viewerSubmissionProgress = $viewerSubmissionMax > 0
        ? min(100, (int) round(($viewerSubmissionUsed / $viewerSubmissionMax) * 100))
        : 0;
    $viewerPlatforms = $viewerCup ? $cupPlatformLabel($viewerCup) : '';

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
<meta content="{{ request()->query() ? 'noindex,follow' : 'index,follow' }}" name="robots"/>
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
      data-cups-active-url="{{ $statusUrl('active') }}"
      data-cups-mine-url="{{ $mineUrl }}"
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
