@extends('admin.layouts.app')

@section('title', 'HNT Maps · Admin')
@section('admin_heading', 'HNT Maps')

@section('content')
    <section class="hh-page-header">
        <div>
            <p class="hh-kicker">Remote Map-Verwaltung</p>
            <h1>HNT Maps</h1>
            <p>Maps, Kartenbilder und Lines zentral verwalten. Aktive Maps erscheinen automatisch auf der Webseite und über <code>/api/v1/maps</code> in der App.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>{{ number_format($maps->count(), 0, ',', '.') }}</strong>
            <span>Karten</span>
        </div>
    </section>

    @if(session('status'))
        <div class="hh-alert hh-alert-success hh-section-space">{{ session('status') }}</div>
    @endif

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <h2>Maps</h2>
                <p class="hh-muted">Neue Maps werden zunächst inaktiv angelegt. Danach Bild/Lines prüfen, Marker setzen und die Map aktivieren.</p>
            </div>
            <a class="hh-primary-button" href="{{ route('admin.maps.create') }}">Neue Map anlegen</a>
        </div>

        <div class="hh-admin-map-list hh-section-space">
            @forelse($maps as $map)
                <article class="hh-admin-map-row">
                    <div>
                        <span class="hh-admin-ai-status {{ $map->is_active ? '' : 'is-possible' }}">{{ $map->is_active ? 'Aktiv' : 'Inaktiv' }}</span>
                        <h3>{{ $map->name }}</h3>
                        <p class="hh-muted">
                            {{ $map->slug }}
                            · {{ number_format($map->width, 0, ',', '.') }}×{{ number_format($map->height, 0, ',', '.') }}
                            · {{ number_format($map->markers_count, 0, ',', '.') }} Marker
                        </p>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end">
                        <a class="hh-secondary-button" href="{{ route('admin.maps.edit', $map) }}">Map bearbeiten</a>
                        <a class="hh-primary-button" href="{{ route('admin.maps.markers', $map) }}">Marker verwalten</a>
                    </div>
                </article>
            @empty
                <div class="hh-admin-list">
                    <div>
                        <strong>Keine Karten gefunden.</strong>
                        <span>Lege die erste Map direkt im Admin an.</span>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/admin-maps.css') }}?v=3">
@endpush
