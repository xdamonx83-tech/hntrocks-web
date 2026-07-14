@php
    $viewer = auth()->user();
    $overview = app(\App\Services\Cups\CupOverviewService::class)->forViewer($viewer);
    $stats = $overview['stats'];
    $featuredCup = $overview['featuredCup'];
    $upcomingCups = $overview['upcomingCups'];
    $viewerTeam = $overview['viewerTeam'];
    $topTeams = $overview['topTeams'];

    $statusFilter = (string) ($filters['status'] ?? request('status', ''));
    $platformFilter = (string) ($filters['platform'] ?? request('platform', ''));
    $mineFilter = (bool) ($filters['mine'] ?? request()->boolean('mine'));
    $searchFilter = (string) ($filters['q'] ?? request('q', ''));
    $baseQuery = request()->except(['page', 'status', 'mine']);
    $allCupsUrl = route('cups.index', $baseQuery);
    $statusUrl = static fn (string $status) => route('cups.index', array_filter(array_merge($baseQuery, ['status' => $status]), static fn ($value) => $value !== '' && $value !== null && $value !== false));
    $mineUrl = route('cups.index', array_filter(array_merge($baseQuery, ['mine' => 1]), static fn ($value) => $value !== '' && $value !== null && $value !== false));
    $resetUrl = route('cups.index');
    $panelTitle = $mineFilter
        ? __('hnt_cups_overview.my_cups')
        : match ($statusFilter) {
            'active' => __('hnt_cups_overview.active_cups'),
            'planned' => __('hnt_cups_overview.planned'),
            'finished' => __('hnt_cups_overview.finished'),
            default => __('hnt_cups_overview.all_cups'),
        };
    $formatCount = static fn (int $value): string => number_format($value, 0, ',', '.');
    $totalTracked = max(1, (int) $stats['active'] + (int) $stats['planned'] + (int) $stats['finished']);
    $activePercent = min(100, max(0, (int) round(((int) $stats['active'] / $totalTracked) * 100)));
    $timelineCup = $viewerTeam?->cup ?: $featuredCup;
    $timelineEvents = collect([
        [
            'date' => now(),
            'date_label' => __('hnt_cups_overview.now'),
            'title' => __('hnt_cups_overview.status_now'),
            'detail' => $timelineCup?->statusLabel() ?: __('hnt_cups_overview.no_current_cup'),
        ],
        $timelineCup?->registration_closes_at ? [
            'date' => $timelineCup->registration_closes_at,
            'date_label' => $timelineCup->registration_closes_at->translatedFormat('d.m.'),
            'title' => __('hnt_cups_overview.registration_closes'),
            'detail' => $timelineCup->registration_closes_at->translatedFormat('H:i'),
        ] : null,
        $timelineCup?->starts_at ? [
            'date' => $timelineCup->starts_at,
            'date_label' => $timelineCup->starts_at->translatedFormat('d.m.'),
            'title' => __('hnt_cups_overview.cup_starts'),
            'detail' => $timelineCup->starts_at->translatedFormat('H:i'),
        ] : null,
        $timelineCup?->ends_at ? [
            'date' => $timelineCup->ends_at,
            'date_label' => $timelineCup->ends_at->translatedFormat('d.m.'),
            'title' => __('hnt_cups_overview.cup_ends'),
            'detail' => $timelineCup->ends_at->translatedFormat('H:i'),
        ] : null,
    ])->filter()->take(4)->values();
    $submissionUrl = $viewerTeam
        ? route('cups.show', $viewerTeam->cup).'#submissions'
        : $mineUrl;
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
<svg aria-hidden="true" class="svg-defs">
<symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.8"></circle><path d="m16.2 16.2 4 4"></path></symbol>
<symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></symbol>
<symbol id="i-sliders" viewBox="0 0 24 24"><path d="M4 7h9M17 7h3M4 17h3M11 17h9M13 4v6M8 14v6"></path></symbol>
<symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.1"></circle><path d="M19 13.6v-3.2l-2-.7-.7-1.7.9-1.9-2.3-2.3-1.9.9-1.7-.7-.7-2H8.4l-.7 2-1.7.7-1.9-.9-2.3 2.3.9 1.9-.7 1.7-2 .7v3.2l2 .7.7 1.7-.9 1.9 2.3 2.3 1.9-.9 1.7.7.7 2h3.2l.7-2 1.7-.7 1.9.9 2.3-2.3-.9-1.9.7-1.7z"></path></symbol>
<symbol id="i-bell" viewBox="0 0 24 24"><path d="M6 9a6 6 0 0 1 12 0c0 7 3 6 3 8H3c0-2 3-1 3-8"></path><path d="M9.5 20h5"></path></symbol>
<symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.4"></circle><path d="M5.5 20a6.5 6.5 0 0 1 13 0"></path></symbol>
<symbol id="i-arrow" viewBox="0 0 24 24"><path d="M7 17 17 7M9 7h8v8"></path></symbol>
<symbol id="i-briefcase" viewBox="0 0 24 24"><rect height="12" rx="3" width="18" x="3" y="7"></rect><path d="M9 7V5h6v2M3 12h18M10 12v2h4v-2"></path></symbol>
<symbol id="i-chevron" viewBox="0 0 24 24"><path d="m8 10 4 4 4-4"></path></symbol>
<symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8.5" r="3"></circle><circle cx="17" cy="9.5" r="2.3"></circle><path d="M3 19a6 6 0 0 1 12 0M14 18a4.5 4.5 0 0 1 7 0"></path></symbol>
<symbol id="i-folder" viewBox="0 0 24 24"><path d="M3 7h7l2 2h9v10H3z"></path><path d="M3 7V5h7l2 2"></path></symbol>
<symbol id="i-check" viewBox="0 0 24 24"><path d="m6 12 4 4 8-8"></path></symbol>
<symbol id="i-comment" viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-8 8H5l-3 2 1-5a8 8 0 1 1 18-5Z"></path></symbol>
<symbol id="i-bookmark" viewBox="0 0 24 24"><path d="M6 3h12v18l-6-4-6 4z"></path></symbol>
<symbol id="i-image" viewBox="0 0 24 24"><rect height="16" rx="3" width="18" x="3" y="4"></rect><circle cx="9" cy="10" r="2"></circle><path d="m5 18 5-5 3 3 2-2 4 4"></path></symbol>
<symbol id="i-eye" viewBox="0 0 24 24"><path d="M2.8 12s3.3-6 9.2-6 9.2 6 9.2 6-3.3 6-9.2 6-9.2-6-9.2-6"></path><circle cx="12" cy="12" r="2.6"></circle></symbol>
</svg>
<main class="app-shell cups-page-shell" data-cups-active-url="{{ $statusUrl('active') }}" data-cups-mine-url="{{ $mineUrl }}" data-cups-submissions-url="{{ $submissionUrl }}" data-cups-hall-url="{{ route('hall-of-fame.index') }}">
@include('themes.hnt_preview.partials.header')
<section class="cups-stage">
<aside aria-label="{{ __('hnt_cups_overview.your_cup') }}" class="cups-fixed-column cups-fixed-left">
@if($viewerTeam && $viewerTeam->cup)
    @php
        $myCup = $viewerTeam->cup;
        $submissionMax = max(1, (int) ($myCup->maxSubmissionsPerParticipant() ?: 3));
        $submissionUsed = min($submissionMax, (int) $viewerTeam->submissions_count);
        $submissionProgress = min(100, (int) round(($submissionUsed / $submissionMax) * 100));
        $activeMembers = $viewerTeam->members->where('status', 'active')->values();
    @endphp
    <article class="cups-my-card">
        <div class="cups-my-cover" style="background-image:url('{{ $myCup->coverUrl() }}')"><span><i></i>{{ $myCup->statusLabel() }}</span></div>
        <div class="cups-my-content">
            <span>{{ __('hnt_cups_overview.your_cup') }}</span>
            <h2>{{ $myCup->title }}</h2>
            <p>{{ $viewerTeam->displayName() }} · {{ $myCup->isSoloLeaderboard() ? __('hnt_cups_overview.solo') : __('hnt_cups_overview.team_size', ['counter' => $myCup->team_size]) }} · {{ implode(' / ', $myCup->allowedPlatforms()) ?: ($myCup->platform ?: __('hnt_cups_overview.all_platforms')) }}</p>
            <div class="cups-team-stack">
                @foreach($activeMembers->take(4) as $member)<img src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt="{{ $member->user?->name ?: $member->user?->username ?: 'Hunter' }}"/>@endforeach
                <strong>{{ $activeMembers->count() }}/{{ max(1, (int) $myCup->team_size) }}</strong>
            </div>
            <div class="cups-my-progress"><div><span>{{ __('hnt_cups_overview.participation') }}</span><strong>{{ $submissionProgress }}%</strong></div><i><b style="width:{{ $submissionProgress }}%"></b></i><small>{{ __('hnt_cups_overview.submission_progress', ['used' => $submissionUsed, 'max' => $submissionMax]) }}</small></div>
            <div class="cups-my-actions"><a href="{{ route('cups.show', $myCup) }}">{{ __('hnt_cups_overview.open_cup') }}</a><a href="{{ route('cups.teams.index', $myCup) }}">{{ __('hnt_cups_overview.manage_team') }}</a></div>
        </div>
    </article>
@else
    <article class="cups-my-card cups-no-current"><strong>{{ __('hnt_cups_overview.no_current_cup') }}</strong><p>{{ __('hnt_cups_overview.no_current_cup_text') }}</p><a href="{{ $statusUrl('active') }}">{{ __('hnt_cups_overview.browse_cups') }}</a></article>
@endif
<article class="cups-timeline-card"><header><span>{{ __('hnt_cups_overview.today_next') }}</span><h3>{{ __('hnt_cups_overview.schedule') }}</h3></header><div class="cups-mini-timeline">@foreach($timelineEvents as $event)<article class="{{ $loop->first ? 'current' : '' }}"><time>{{ $event['date_label'] }}</time><div><strong>{{ $event['title'] }}</strong><small>{{ $event['detail'] }}</small></div></article>@endforeach</div></article>
</aside>
<div aria-label="{{ __('hnt_cups_overview.title') }}" class="cups-scroll" id="cupsScroll" tabindex="0">
<section class="cups-overview">
<div class="cups-heading"><span>{{ __('hnt_cups_overview.eyebrow') }}</span><h1>{{ __('hnt_cups_overview.title') }}</h1><div class="cups-meta"><span class="live"><i></i>{{ $formatCount((int) $stats['active']) }} {{ __('hnt_cups_overview.active') }}</span><span>{{ __('hnt_cups_overview.solo_and_teams') }}</span><span>{{ __('hnt_cups_overview.fair_scoring') }}</span><span>{{ __('hnt_cups_overview.manual_review') }}</span></div><p>{{ __('hnt_cups_overview.intro') }}</p><div class="cups-bars"><div class="cups-summary-bar wide"><span>{{ __('hnt_cups_overview.active_cups') }}</span><div class="dark" style="background:linear-gradient(90deg,var(--cups-red) 0 {{ $activePercent }}%,var(--dark) {{ $activePercent }}% 100%)"><b>{{ $formatCount((int) $stats['active']) }} / {{ $formatCount($totalTracked) }}</b></div></div><div class="cups-summary-bar"><span>{{ __('hnt_cups_overview.planned') }}</span><div class="red"><b>{{ $formatCount((int) $stats['planned']) }}</b></div></div><div class="cups-summary-bar"><span>{{ __('hnt_cups_overview.finished') }}</span><div class="striped"><b>{{ $formatCount((int) $stats['finished']) }}</b></div></div><div class="cups-summary-bar compact"><span>{{ __('hnt_cups_overview.pending_reviews') }}</span><div class="outline"><b>{{ $formatCount((int) $stats['pending']) }}</b></div></div></div></div>
<div class="cups-overview-stats"><article><strong>{{ $formatCount((int) $stats['teams']) }}</strong><span>{{ __('hnt_cups_overview.teams') }}</span></article><article><strong>{{ $formatCount((int) $stats['hunters']) }}</strong><span>{{ __('hnt_cups_overview.hunters') }}</span></article><article><strong>{{ $formatCount((int) $stats['scores']) }}</strong><span>{{ __('hnt_cups_overview.scores') }}</span></article></div>
</section>
<section class="cups-center-flow"><article class="cups-center-card">
<header class="cups-center-head"><div class="cups-center-title"><span>{{ __('hnt_cups_overview.community_cups') }}</span><h2>{{ $panelTitle }}</h2></div><nav aria-label="{{ __('hnt_cups_overview.title') }}" class="cups-tabs"><a class="{{ ! $mineFilter && $statusFilter === '' ? 'active' : '' }}" href="{{ $allCupsUrl }}">{{ __('hnt_cups_overview.tab_all') }}</a><a class="{{ ! $mineFilter && $statusFilter === 'active' ? 'active' : '' }}" href="{{ $statusUrl('active') }}">{{ __('hnt_cups_overview.tab_active') }}</a><a class="{{ ! $mineFilter && $statusFilter === 'planned' ? 'active' : '' }}" href="{{ $statusUrl('planned') }}">{{ __('hnt_cups_overview.tab_planned') }}</a><a class="{{ ! $mineFilter && $statusFilter === 'finished' ? 'active' : '' }}" href="{{ $statusUrl('finished') }}">{{ __('hnt_cups_overview.tab_finished') }}</a><a class="{{ $mineFilter ? 'active' : '' }}" href="{{ $mineUrl }}">{{ __('hnt_cups_overview.tab_mine') }}</a></nav></header>
<form class="cups-filter-row" method="get" action="{{ route('cups.index') }}">@if($statusFilter !== '')<input type="hidden" name="status" value="{{ $statusFilter }}"/>@endif @if($mineFilter)<input type="hidden" name="mine" value="1"/>@endif<label class="cups-search"><svg><use href="#i-search"></use></svg><input name="q" value="{{ $searchFilter }}" placeholder="{{ __('hnt_cups_overview.search_placeholder') }}" type="search"/></label><select aria-label="{{ __('hnt_cups_overview.platforms') }}" name="platform"><option value="">{{ __('hnt_cups_overview.all_platforms') }}</option><option value="console" @selected($platformFilter === 'console')>{{ __('hnt_cups_overview.console') }}</option><option value="ps5" @selected($platformFilter === 'ps5')>PlayStation 5</option><option value="xbox" @selected($platformFilter === 'xbox')>Xbox</option><option value="pc" @selected($platformFilter === 'pc')>PC</option></select><div class="cups-filter-actions"><button type="submit"><svg><use href="#i-search"></use></svg>{{ __('hnt_cups_overview.details') }}</button><a class="cups-filter-reset" href="{{ $resetUrl }}"><svg><use href="#i-sliders"></use></svg>{{ __('hnt_cups_overview.reset_filters') }}</a></div></form>
@if($featuredCup)
@php $featuredLimit = $featuredCup->participantLimit(); $featuredPlatforms = implode(' / ', $featuredCup->allowedPlatforms()) ?: ($featuredCup->platform ?: __('hnt_cups_overview.all_platforms')); $featuredStart = $featuredCup->starts_at?->isFuture() ? $featuredCup->starts_at->diffForHumans() : ($featuredCup->starts_at?->translatedFormat('d.m.Y · H:i') ?: 'TBA'); @endphp
<section class="cups-featured"><div class="cups-featured-cover" style="background-image:url('{{ $featuredCup->coverUrl() }}')"><span>{{ __('hnt_cups_overview.featured') }}</span></div><div class="cups-featured-copy"><div class="cups-card-status"><span class="active"><i></i>{{ $featuredCup->isRegistrationOpen() ? __('hnt_cups_overview.registration_open') : $featuredCup->statusLabel() }}</span><span>{{ $featuredCup->isSoloLeaderboard() ? __('hnt_cups_overview.solo') : __('hnt_cups_overview.team_size', ['counter' => $featuredCup->team_size]) }}</span><span>{{ $featuredPlatforms }}</span></div><h3>{{ $featuredCup->title }}</h3><p>{{ \Illuminate\Support\Str::limit($featuredCup->displaySummary(), 260) }}</p><div class="cups-featured-stats"><span><strong>{{ $formatCount((int) $featuredCup->active_teams_count) }}{{ $featuredLimit ? ' / '.$formatCount($featuredLimit) : '' }}</strong><small>{{ $featuredCup->isSoloLeaderboard() ? __('hnt_cups_overview.participants') : __('hnt_cups_overview.teams') }}</small></span><span><strong>{{ $featuredStart }}</strong><small>{{ __('hnt_cups_overview.until_start') }}</small></span><span><strong>{{ $formatCount((int) $featuredCup->submissions_count) }}</strong><small>{{ __('hnt_cups_overview.submissions') }}</small></span></div><div class="cups-featured-actions"><a href="{{ route('cups.show', $featuredCup) }}">{{ __('hnt_cups_overview.view_cup') }} <svg><use href="#i-arrow"></use></svg></a></div></div></section>
@endif
@if($upcomingCups->isNotEmpty())
<section class="cups-section"><header><div><span>{{ __('hnt_cups_overview.next') }}</span><h3>{{ __('hnt_cups_overview.upcoming_highlights') }}</h3></div><small>{{ __('hnt_cups_overview.planned_count', ['count' => $upcomingCups->count()]) }}</small></header><div class="cups-highlight-grid">@foreach($upcomingCups as $cup)<article class="cup-highlight-card"><div class="cup-highlight-art" style="background-image:url('{{ $cup->coverUrl() }}')"><div><span>{{ $cup->statusLabel() }}</span><strong>{{ $cup->title }}</strong><small>{{ $cup->starts_at?->translatedFormat('F Y') ?: 'TBA' }}</small></div></div><div class="cup-highlight-content"><span class="planned">{{ $cup->statusLabel() }}</span><h4>{{ $cup->title }}</h4><p>{{ \Illuminate\Support\Str::limit($cup->displaySummary(), 118) }}</p><div><span>{{ $cup->isSoloLeaderboard() ? __('hnt_cups_overview.solo') : __('hnt_cups_overview.team_size', ['counter' => $cup->team_size]) }}</span><span>{{ implode(' / ', $cup->allowedPlatforms()) ?: ($cup->platform ?: __('hnt_cups_overview.all_platforms')) }}</span><span>{{ $formatCount((int) $cup->active_teams_count) }} {{ __('hnt_cups_overview.teams') }}</span></div><a href="{{ route('cups.show', $cup) }}">{{ __('hnt_cups_overview.remember') }}</a></div></article>@endforeach</div></section>
@endif
<section class="cups-section cups-all-section"><header><div><span>{{ __('hnt_cups_overview.all_events') }}</span><h3>{{ __('hnt_cups_overview.directory') }}</h3></div><small>{{ $formatCount((int) $cups->total()) }} {{ $cups->total() === 1 ? __('hnt_cups_overview.cup_singular') : __('hnt_cups_overview.cup_plural') }}</small></header>@if($cups->isEmpty())<div class="cups-empty-state"><strong>{{ __('hnt_cups_overview.empty_title') }}</strong><p>{{ __('hnt_cups_overview.empty_text') }}</p></div>@else<div class="cups-card-grid">@foreach($cups as $cup)@php $participantLimit = $cup->participantLimit(); $dateLabel = $cup->status === 'finished' ? __('hnt_cups_overview.ended_on', ['date' => $cup->ends_at?->translatedFormat('d.m.Y') ?: $cup->starts_at?->translatedFormat('d.m.Y') ?: '—']) : __('hnt_cups_overview.starts_on', ['date' => $cup->starts_at?->translatedFormat('d.m.Y') ?: 'TBA']); @endphp<article class="cup-card"><div class="cup-card-art" style="background-image:url('{{ $cup->coverUrl() }}')"><div><span>{{ $cup->statusLabel() }}</span><b>{{ $cup->title }}</b><small>{{ $dateLabel }}</small></div></div><div class="cup-card-body"><div><span class="{{ $cup->status }}"><i></i>{{ $cup->statusLabel() }}</span><em>{{ $cup->isSoloLeaderboard() ? __('hnt_cups_overview.solo') : __('hnt_cups_overview.team_size', ['counter' => $cup->team_size]) }}</em></div><h4>{{ $cup->title }}</h4><p>{{ \Illuminate\Support\Str::limit($cup->displaySummary(), 122) }}</p><ul><li>{{ $formatCount((int) $cup->active_teams_count) }} {{ $cup->isSoloLeaderboard() ? __('hnt_cups_overview.participants') : __('hnt_cups_overview.teams') }}</li><li>{{ implode(' / ', $cup->allowedPlatforms()) ?: ($cup->platform ?: __('hnt_cups_overview.all_platforms')) }}</li><li>{{ $participantLimit ? __('hnt_cups_overview.open_slots', ['count' => max(0, $participantLimit - (int) $cup->active_teams_count)]) : __('hnt_cups_overview.unlimited_slots') }}</li></ul><a href="{{ route('cups.show', $cup) }}">{{ in_array($cup->status, ['finished', 'archived'], true) ? __('hnt_cups_overview.results') : __('hnt_cups_overview.details') }} <svg><use href="#i-arrow"></use></svg></a></div></article>@endforeach</div>@endif</section>
@if($viewer?->isAdmin())<a class="cups-admin-create" href="{{ route('cups.create') }}"><svg><use href="#i-plus"></use></svg>{{ __('hnt_cups_overview.create_cup') }}</a>@endif
@if($cups->hasPages())<div class="cups-pagination">@if($cups->previousPageUrl())<a href="{{ $cups->previousPageUrl() }}">{{ __('hnt_cups_overview.previous') }}</a>@endif<span>{{ $cups->currentPage() }} / {{ $cups->lastPage() }}</span>@if($cups->nextPageUrl())<a href="{{ $cups->nextPageUrl() }}">{{ __('hnt_cups_overview.next_page') }}</a>@endif</div>@endif
</article></section></div>
<aside aria-label="{{ __('hnt_cups_overview.cup_hub') }}" class="cups-fixed-column cups-fixed-right"><article class="cups-hall-card"><header><div><span>{{ __('hnt_cups_overview.hall_of_fame') }}</span><h2>{{ __('hnt_cups_overview.top_teams') }}</h2></div><a href="{{ route('hall-of-fame.index') }}" aria-label="{{ __('hnt_cups_overview.hall_of_fame') }}"><svg><use href="#i-arrow"></use></svg></a></header>@if($topTeams->isNotEmpty())<div class="cups-podium">@foreach($topTeams as $team)<article class="{{ $loop->iteration === 1 ? 'first' : ($loop->iteration === 2 ? 'second' : 'third') }}"><b>{{ $loop->iteration }}</b><img alt="{{ $team->displayName() }}" src="{{ $team->owner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/><strong>{{ $team->displayName() }}</strong><small>{{ __('hnt_cups_overview.points', ['count' => $formatCount((int) $team->points_total)]) }}</small></article>@endforeach</div><div class="cups-hall-stats"><span><strong>{{ $formatCount((int) $stats['finished']) }}</strong><small>{{ __('hnt_cups_overview.cups_stat') }}</small></span><span><strong>{{ $formatCount($topTeams->count()) }}</strong><small>{{ __('hnt_cups_overview.finalists') }}</small></span><span><strong>{{ $topTeams->isNotEmpty() ? '1' : '0' }}</strong><small>{{ __('hnt_cups_overview.winners') }}</small></span></div>@else<p class="cups-hall-empty">{{ __('hnt_cups_overview.no_hall_entries') }}</p>@endif</article><article class="cups-hub-card"><header><span>{{ __('hnt_cups_overview.cup_hub') }}</span><h3>{{ __('hnt_cups_overview.direct_access') }}</h3></header><a href="{{ $statusUrl('active') }}"><svg><use href="#i-check"></use></svg><span><strong>{{ __('hnt_cups_overview.active_cups') }}</strong><small>{{ __('hnt_cups_overview.active_events', ['count' => $stats['active']]) }}</small></span><i>→</i></a><a href="{{ $statusUrl('planned') }}"><svg><use href="#i-folder"></use></svg><span><strong>{{ __('hnt_cups_overview.planned') }}</strong><small>{{ __('hnt_cups_overview.planned_events', ['count' => $stats['planned']]) }}</small></span><i>→</i></a><a href="{{ $featuredCup ? route('cups.show', $featuredCup).'#rules' : $allCupsUrl }}"><svg><use href="#i-sliders"></use></svg><span><strong>{{ __('hnt_cups_overview.scoring_fair_play') }}</strong><small>{{ __('hnt_cups_overview.rules_and_scores') }}</small></span><i>→</i></a><a href="{{ $mineUrl }}"><svg><use href="#i-users"></use></svg><span><strong>{{ __('hnt_cups_overview.my_cups') }}</strong><small>{{ __('hnt_cups_overview.teams_and_submissions') }}</small></span><i>→</i></a><a href="{{ route('hall-of-fame.index') }}"><svg><use href="#i-bookmark"></use></svg><span><strong>{{ __('hnt_cups_overview.hall_of_fame') }}</strong><small>{{ __('hnt_cups_overview.hall_text') }}</small></span><i>→</i></a></article></aside>
</section>
<div class="toast" id="toast"></div>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v=20260710-1"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-cups/cups-overview.js') }}?v={{ $cupsJsVersion }}"></script>
</body>
</html>
