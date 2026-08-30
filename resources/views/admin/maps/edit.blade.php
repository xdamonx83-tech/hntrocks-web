@extends('admin.layouts.app')

@php
    $editing = $map !== null;
@endphp

@section('title', ($editing ? $map->name.' bearbeiten' : 'Neue Map').' · HNT Maps Admin')
@section('admin_heading', 'HNT Maps')

@section('content')
    <section class="hh-page-header">
        <div>
            <p class="hh-kicker"><a href="{{ route('admin.maps.index') }}">← Zurück zu HNT Maps</a></p>
            <h1>{{ $editing ? $map->name : 'Neue Map anlegen' }}</h1>
            <p>
                {{ $editing
                    ? 'Kartenbild, Lines, Größe, Sortierung und Sichtbarkeit verwalten.'
                    : 'Die Map wird zunächst inaktiv angelegt. Danach Marker setzen und erst anschließend aktivieren.' }}
            </p>
        </div>
        @if($editing)
            <div class="hh-page-header-meta">
                <strong>{{ $map->is_active ? 'Aktiv' : 'Inaktiv' }}</strong>
                <span>{{ $map->slug }}</span>
            </div>
        @endif
    </section>

    @if(session('status'))
        <div class="hh-alert hh-alert-success hh-section-space">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="hh-alert hh-alert-danger hh-section-space">
            <strong>Bitte prüfen:</strong>
            <ul style="margin:8px 0 0 18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        class="hh-card hh-section-space"
        method="POST"
        action="{{ $editing ? route('admin.maps.update', $map) : route('admin.maps.store') }}"
        enctype="multipart/form-data"
    >
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        <div class="hh-card-title-row">
            <div>
                <h2>Map-Daten</h2>
                <p class="hh-muted">Der Slug ist nach dem Anlegen absichtlich unveränderlich, damit Web- und App-Links stabil bleiben.</p>
            </div>
            @if($editing)
                <a class="hh-secondary-button" href="{{ route('admin.maps.markers', $map) }}">Marker verwalten</a>
            @endif
        </div>

        <div class="hh-admin-map-form-grid hh-section-space">
            <label>
                Name
                <input name="name" type="text" maxlength="120" required value="{{ old('name', $map?->name) }}">
            </label>

            <label>
                Slug
                <input
                    name="slug"
                    type="text"
                    maxlength="120"
                    pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                    required
                    value="{{ old('slug', $map?->slug) }}"
                    {{ $editing ? 'readonly' : '' }}
                >
            </label>

            <label>
                Breite
                <input name="width" type="number" min="1" max="10000" required value="{{ old('width', $map?->width ?? 2048) }}">
            </label>

            <label>
                Höhe
                <input name="height" type="number" min="1" max="10000" required value="{{ old('height', $map?->height ?? 2048) }}">
            </label>

            <label>
                Sortierung
                <input name="sort_order" type="number" min="0" max="100000" value="{{ old('sort_order', $map?->sort_order ?? 0) }}">
            </label>

            @if($editing)
                <label>
                    Sichtbarkeit
                    <span style="display:flex;align-items:center;gap:10px;min-height:42px">
                        <input type="hidden" name="is_active" value="0">
                        <input name="is_active" type="checkbox" value="1" style="width:auto" @checked(old('is_active', $map->is_active))>
                        Aktiv auf Web + App
                    </span>
                </label>
            @endif
        </div>

        <div class="hh-card-title-row hh-section-space">
            <div>
                <h2>Dateien</h2>
                <p class="hh-muted">Map: WEBP/PNG/JPG. Lines: PNG/WEBP. Maximal 30 MB pro Datei.</p>
            </div>
        </div>

        <div class="hh-admin-map-form-grid hh-section-space">
            <label>
                Kartenbild {{ $editing ? '(optional ersetzen)' : '' }}
                <input name="map_image" type="file" accept=".webp,.png,.jpg,.jpeg,image/webp,image/png,image/jpeg" {{ $editing ? '' : 'required' }}>
            </label>

            <label>
                Lines {{ $editing ? '(optional ersetzen)' : '(optional)' }}
                <input name="lines_image" type="file" accept=".webp,.png,image/webp,image/png">
            </label>

            @if($editing && $map->lines_path)
                <label style="grid-column:1/-1">
                    <span style="display:flex;align-items:center;gap:10px">
                        <input name="remove_lines" type="checkbox" value="1" style="width:auto">
                        Vorhandene Lines entfernen
                    </span>
                </label>
            @endif
        </div>

        @if($editing)
            <div class="hh-section-space" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px">
                <div>
                    <h3>Kartenbild</h3>
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $map->name }}" style="display:block;width:100%;max-height:360px;object-fit:contain;background:#171512;border-radius:14px">
                    @else
                        <div class="hh-alert hh-alert-danger">Kartenbild fehlt oder ist nicht erreichbar.</div>
                    @endif
                </div>
                <div>
                    <h3>Lines</h3>
                    @if($linesUrl)
                        <img src="{{ $linesUrl }}" alt="Lines {{ $map->name }}" style="display:block;width:100%;max-height:360px;object-fit:contain;background:#171512;border-radius:14px">
                    @else
                        <p class="hh-muted">Kein Lines-Overlay hinterlegt.</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="hh-admin-map-form-actions hh-section-space">
            <a class="hh-secondary-button" href="{{ route('admin.maps.index') }}">Abbrechen</a>
            <button class="hh-primary-button" type="submit">{{ $editing ? 'Map speichern' : 'Map anlegen' }}</button>
        </div>
    </form>

    @if($editing)
        <section class="hh-card hh-section-space">
            <div class="hh-card-title-row">
                <div>
                    <h2>Nächster Schritt</h2>
                    <p class="hh-muted">Marker setzen oder bearbeiten. Sobald alles fertig ist, diese Map oben aktivieren.</p>
                </div>
                <a class="hh-primary-button" href="{{ route('admin.maps.markers', $map) }}">Marker verwalten</a>
            </div>
        </section>
    @endif
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/admin-maps.css') }}?v=3">
@endpush
