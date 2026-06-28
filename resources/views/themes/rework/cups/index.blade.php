@extends('themes.rework.layouts.app')

@section('title', 'Cups · HNT.rocks')
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
            return $start->translatedFormat('d.m.').'–'.$end->translatedFormat('d.m.');
        }

        if ($start) {
            return $start->translatedFormat('d.m.Y');
        }

        if ($end) {
            return 'Bis '.$end->translatedFormat('d.m.Y');
        }

        return 'Termin offen';
    };

    $cupPlatformLabel = function ($cup): string {
        $allowed = $cup->allowedPlatforms();

        if ($allowed !== []) {
            return implode(' / ', $allowed);
        }

        $platform = trim((string) $cup->platform);

        return $platform !== '' ? $platform : 'Alle Plattformen';
    };

    $cupBadgeLabel = function ($cup): string {
        $teams = (int) ($cup->active_teams_count ?? 0);
        $limit = $cup->participantLimit();

        if ($cup->isSoloLeaderboard()) {
            return $limit ? $teams.'/'.$limit.' Spieler' : $teams.' Spieler';
        }

        return $limit ? $teams.'/'.$limit.' Teams' : $teams.' Teams';
    };

    $cupStatusLabel = function ($cup): string {
        if ($cup->isRegistrationOpen()) {
            return 'Anmeldung offen';
        }

        if ($cup->isSubmissionOpen()) {
            return 'Aktiv';
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

<section id="cup-filters" aria-label="Cup Filter" class="cups-overview-controls">
    <div class="cups-filter-group">
        <span>Plattform</span>
        <div aria-label="Plattformfilter" class="members-tabs cups-filter-tabs" role="tablist">
            <a @class(['active' => $activePlatform === '']) href="{{ $filterUrl([], ['platform']) }}">Alle Plattformen</a>
            <a @class(['active' => $activePlatform === 'PC']) href="{{ $filterUrl(['platform' => 'PC']) }}">PC</a>
            <a @class(['active' => in_array(strtolower($activePlatform), ['ps', 'ps4', 'ps5', 'playstation', 'playstation4', 'playstation5'], true)]) href="{{ $filterUrl(['platform' => 'ps5']) }}">PS5</a>
            <a @class(['active' => in_array(strtolower($activePlatform), ['xbox', 'xboxseries', 'xboxseriesx', 'xboxseriess', 'xboxseriesxs'], true)]) href="{{ $filterUrl(['platform' => 'Xbox']) }}">Xbox</a>
            <a @class(['active' => strcasecmp($activePlatform, 'Konsole') === 0 || strcasecmp($activePlatform, 'Console') === 0]) href="{{ $filterUrl(['platform' => 'Konsole']) }}">Konsole</a>
        </div>
    </div>
    <div class="cups-filter-group">
        <span>Status</span>
        <div aria-label="Statusfilter" class="members-tabs cups-status-tabs" role="tablist">
            <a @class(['active' => $activeStatus === '']) href="{{ $filterUrl([], ['status']) }}">Alle</a>
            <a @class(['active' => $activeStatus === 'active']) href="{{ $filterUrl(['status' => 'active']) }}">Aktiv</a>
            <a @class(['active' => $activeStatus === 'planned']) href="{{ $filterUrl(['status' => 'planned']) }}">Geplant</a>
            <a @class(['active' => $activeStatus === 'finished']) href="{{ $filterUrl(['status' => 'finished']) }}">Beendet</a>
        </div>
    </div>
</section>

<section class="hall-cta card">
    <div class="hall-cta-icon"><i aria-hidden="true" class="ph ph-crown ph-icon"></i></div>
    <div class="hall-cta-copy">
        <span>Hall of Fame</span>
        <h2>Die besten Cup-Teams</h2>
        <p>Sieger, MVPs und legendäre Einreichungen aus vergangenen HNT Cups gesammelt an einem Ort.</p>
    </div>
    <div aria-hidden="true" class="hall-cta-preview">
        <b>1</b><b>2</b><b>3</b>
    </div>
    <a class="btn hall-cta-button" href="{{ $hallUrl }}">Hall of Fame öffnen</a>
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
            <strong>Keine Cups gefunden</strong>
            <p>Aktuell gibt es für diese Filter keine Cups. Setz die Filter zurück oder leg später einen neuen Cup an.</p>
            <a class="btn light" href="{{ $cupsUrl }}">Filter zurücksetzen</a>
        </section>
    @endforelse
</div>

@if($cups instanceof \Illuminate\Contracts\Pagination\Paginator && $cups->hasPages())
    <div class="cups-pagination">
        {{ $cups->links() }}
    </div>
@endif
@endsection
