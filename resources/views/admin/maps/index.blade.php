@extends('admin.layouts.app')

@section('title', 'HNT Maps · Admin')
@section('admin_heading', 'HNT Maps')

@section('content')
    <section class="hh-page-header">
        <div>
            <p class="hh-kicker">Map-Verwaltung</p>
            <h1>HNT Maps</h1>
            <p>Bestehende Markerpositionen verwalten. Marker, Inhalte und Dateien werden hier nicht angelegt oder gelöscht.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>{{ number_format($maps->count(), 0, ',', '.') }}</strong>
            <span>Karten</span>
        </div>
    </section>

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <h2>Vorhandene Karten</h2>
                <p class="hh-muted">Öffne eine Karte, um ihre Marker per Drag & Save zu verschieben.</p>
            </div>
        </div>

        <div class="hh-admin-map-list hh-section-space">
            @forelse($maps as $map)
                <article class="hh-admin-map-row">
                    <div>
                        <span class="hh-admin-ai-status {{ $map->is_active ? '' : 'is-possible' }}">{{ $map->is_active ? 'Aktiv' : 'Inaktiv' }}</span>
                        <h3>{{ $map->name }}</h3>
                        <p class="hh-muted">{{ $map->slug }} · {{ number_format($map->markers_count, 0, ',', '.') }} Marker</p>
                    </div>
                    <a class="hh-primary-button" href="{{ route('admin.maps.markers', $map) }}">Marker verwalten</a>
                </article>
            @empty
                <div class="hh-admin-list">
                    <div>
                        <strong>Keine Karten gefunden.</strong>
                        <span>Führe zuerst die HNT-Maps-Migration und den Seeder aus.</span>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/admin-maps.css') }}?v=1">
@endpush
