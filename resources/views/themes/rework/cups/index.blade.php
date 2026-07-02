@extends('themes.rework.layouts.app')

@section('title', __('ui.rework_cups_page_title'))
@section('body_class', 'cups-page')
@section('left_col_class', 'cups-overview-left')

@section('content')
@php
    $hallUrl = \Illuminate\Support\Facades\Route::has('hall-of-fame.index') ? route('hall-of-fame.index') : '#';
    $cupsUrl = \Illuminate\Support\Facades\Route::has('cups.index') ? route('cups.index') : '#';
    $cupShowAvailable = \Illuminate\Support\Facades\Route::has('cups.show');
    $cupsCollection = $cups instanceof \Illuminate\Contracts\Pagination\Paginator ? $cups->getCollection() : collect($cups ?? []);
    $activePlatform = (string) ($filters['platform'] ?? '');
    $activeStatus = (string) ($filters['status'] ?? '');

    $filterUrl = function (array $merge = [], array $remove = []) use ($cupsUrl): string {
        $query = request()->query();

        foreach ($remove as $key) {
            unset($query[$key]);
        }

        foreach ($merge as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }

        return $cupsUrl.($query !== [] ? '?'.http_build_query($query) : '');
    };

    $cupDateLabel = function ($cup): string {
        $start = $cup->starts_at;
        $end = $cup->ends_at;

        if ($start && $end) {
            return $start->translatedFormat('d.m.').' - '.$end->translatedFormat('d.m.');
        }

        if ($start) {
            return $start->translatedFormat('d.m.Y');
        }

        if ($end) {
            return __('ui.rework_cup_until_date', ['date' => $end->translatedFormat('d.m.Y')]);
        }

        return __('ui.rework_cup_date_open');
    };

    $cupPlatformLabel = function ($cup): string {
        $allowed = $cup->allowedPlatforms();

        if ($allowed !== []) {
            return implode(' / ', $allowed);
        }

        $platform = trim((string) $cup->platform);

        return $platform !== '' ? $platform : __('ui.cup_all_platforms');
    };

    $cupBadgeLabel = function ($cup): string {
        $teams = (int) ($cup->active_teams_count ?? 0);
        $limit = $cup->participantLimit();

        if ($cup->isSoloLeaderboard()) {
            return $limit
                ? __('ui.rework_cup_players_limited_count', ['count' => $teams, 'limit' => $limit])
                : __('ui.rework_cup_players_count', ['count' => $teams]);
        }

        return $limit
            ? __('ui.rework_cup_teams_limited_count', ['count' => $teams, 'limit' => $limit])
            : __('ui.rework_cup_teams_count', ['count' => $teams]);
    };

    $cupStatusLabel = function ($cup): string {
        if ($cup->isRegistrationOpen()) {
            return __('ui.rework_cup_registration_open');
        }

        if ($cup->isSubmissionOpen()) {
            return __('ui.cup_status_active');
        }

        return $cup->statusLabel();
    };
@endphp

<section class="members-head cups-overview-head">
    <div>
        <span class="members-eyebrow">HNT Cups</span>
        <h1>Cups</h1>
    </div>
</section>

<section id="cup-filters" aria-label="{{ __('ui.rework_cup_filter_aria') }}" class="cups-overview-controls">
    <div class="cups-filter-group">
        <span>{{ __('ui.cup_platform_filter') }}</span>
        <div aria-label="{{ __('ui.cup_platform_filter') }}" class="members-tabs cups-filter-tabs" role="tablist">
            <a @class(['active' => $activePlatform === '']) href="{{ $filterUrl([], ['platform']) }}">{{ __('ui.cup_all_platforms') }}</a>
            <a @class(['active' => $activePlatform === 'PC']) href="{{ $filterUrl(['platform' => 'PC']) }}">PC</a>
            <a @class(['active' => in_array(strtolower($activePlatform), ['ps', 'ps4', 'ps5', 'playstation', 'playstation4', 'playstation5'], true)]) href="{{ $filterUrl(['platform' => 'ps5']) }}">PS5</a>
            <a @class(['active' => in_array(strtolower($activePlatform), ['xbox', 'xboxseries', 'xboxseriesx', 'xboxseriess', 'xboxseriesxs'], true)]) href="{{ $filterUrl(['platform' => 'Xbox']) }}">Xbox</a>
            <a @class(['active' => strcasecmp($activePlatform, 'Konsole') === 0 || strcasecmp($activePlatform, 'Console') === 0]) href="{{ $filterUrl(['platform' => 'Konsole']) }}">{{ __('ui.rework_platform_console') }}</a>
        </div>
    </div>
    <div class="cups-filter-group">
        <span>{{ __('ui.rework_cup_status') }}</span>
        <div aria-label="{{ __('ui.rework_cup_status_filter_aria') }}" class="members-tabs cups-status-tabs" role="tablist">
            <a @class(['active' => $activeStatus === '']) href="{{ $filterUrl([], ['status']) }}">{{ __('ui.rework_all') }}</a>
            <a @class(['active' => $activeStatus === 'active']) href="{{ $filterUrl(['status' => 'active']) }}">{{ __('ui.cup_status_active') }}</a>
            <a @class(['active' => $activeStatus === 'planned']) href="{{ $filterUrl(['status' => 'planned']) }}">{{ __('ui.rework_cup_planned') }}</a>
            <a @class(['active' => $activeStatus === 'finished']) href="{{ $filterUrl(['status' => 'finished']) }}">{{ __('ui.rework_cup_finished') }}</a>
        </div>
    </div>
</section>

<section class="hall-cta card">
    <div class="hall-cta-icon"><i aria-hidden="true" class="ph ph-crown ph-icon"></i></div>
    <div class="hall-cta-copy">
        <span>{{ __('ui.rework_hall_of_fame') }}</span>
        <h2>{{ __('ui.rework_cups_hall_title') }}</h2>
        <p>{{ __('ui.rework_cups_hall_text') }}</p>
    </div>
    <div aria-hidden="true" class="hall-cta-preview">
        <b>1</b><b>2</b><b>3</b>
    </div>
    <a class="btn hall-cta-button" href="{{ $hallUrl }}">{{ __('ui.rework_cups_hall_open') }}</a>
</section>

<div class="cups-grid">
    @forelse($cupsCollection as $cup)
        @php
            $cupUrl = $cupShowAvailable ? route('cups.show', $cup) : '#';
            $coverUrl = $cup->coverUrl();
            $summary = \Illuminate\Support\Str::limit($cup->displaySummary(), 92);
        @endphp

        <article @class(['cup-overview-card card', 'is-featured' => $loop->first])>
            <a class="cup-card-cover" href="{{ $cupUrl }}">
                <img alt="{{ $cup->title }}" src="{{ $coverUrl }}"/>
            </a>
            <div class="cup-card-body">
                <div class="cup-card-title">
                    <h2><a href="{{ $cupUrl }}">{{ $cup->title }}</a></h2>
                    <span><i aria-hidden="true" class="ph {{ $cup->isSoloLeaderboard() ? 'ph-crosshair' : 'ph-trophy' }} ph-icon"></i> {{ $cupBadgeLabel($cup) }}</span>
                </div>
                <p>{{ $summary }}</p>
                <div class="cup-card-meta">
                    <span>{{ $cupDateLabel($cup) }}</span>
                    <strong>{{ $cupStatusLabel($cup) }}</strong>
                </div>
            </div>
        </article>
    @empty
        <section class="card cups-empty-card">
            <i aria-hidden="true" class="ph ph-trophy ph-icon"></i>
            <strong>{{ __('ui.rework_cups_empty_title') }}</strong>
            <p>{{ __('ui.rework_cups_empty_text') }}</p>
            <a class="btn light" href="{{ $cupsUrl }}">{{ __('ui.rework_filters_reset') }}</a>
        </section>
    @endforelse
</div>

@if($cups instanceof \Illuminate\Contracts\Pagination\Paginator && $cups->hasPages())
    <div class="cups-pagination">
        {{ $cups->links() }}
    </div>
@endif
@endsection
