@extends('admin.layouts.app')

@section('title', $map['name'].' · HNT Maps Admin')
@section('admin_heading', 'HNT Maps')

@section('content')
    <section class="hh-page-header hh-admin-map-header">
        <div>
            <p class="hh-kicker"><a href="{{ route('admin.maps.index') }}">← Zurück zu Admin Maps</a></p>
            <h1>{{ $map['name'] }}</h1>
            <p>Marker ziehen und speichern. Gespeichert werden ausschließlich die Koordinaten x und y.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>{{ number_format($markers->count(), 0, ',', '.') }}</strong>
            <span>Marker</span>
        </div>
    </section>

    <section class="hh-card hh-admin-map-workspace">
        <div class="hh-admin-map-toolbar">
            <label>
                Markertyp
                <select data-admin-map-filter>
                    <option value="">Alle Typen</option>
                    @foreach($markers->pluck('type')->unique()->sort() as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </label>
            <p class="hh-admin-map-save-status" data-admin-map-status role="status">Bereit</p>
        </div>

        @if(! $map['image_url'])
            <div class="hh-alert hh-alert-danger">Das Kartenbild fehlt oder der konfigurierte Pfad ist ungültig.</div>
        @else
            <div id="hntAdminMap" class="hh-admin-map-canvas" aria-label="Markerpositionen für {{ $map['name'] }}"></div>
            <script id="hntAdminMapConfig" type="application/json">{!! json_encode([
                'imageUrl' => $map['image_url'],
                'linesUrl' => $map['lines_url'],
                'width' => $map['width'],
                'height' => $map['height'],
                'markers' => $markers,
            ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
        @endif
    </section>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}?v=1.9.4">
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/admin-maps.css') }}?v=1">
@endpush

@push('scripts')
    <script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}?v=1.9.4"></script>
    <script src="{{ asset('assets/hnt/maps/admin-maps.js') }}?v=1" defer></script>
@endpush
