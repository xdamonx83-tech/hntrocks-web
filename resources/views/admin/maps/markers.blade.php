@extends('admin.layouts.app')

@section('title', $map['name'].' · HNT Maps Admin')
@section('admin_heading', 'HNT Maps')

@section('content')
    <section class="hh-page-header hh-admin-map-header">
        <div>
            <p class="hh-kicker"><a href="{{ route('admin.maps.index') }}">← Zurück zu Admin Maps</a></p>
            <h1>{{ $map['name'] }}</h1>
            <p>Marker erstellen, bearbeiten, verschieben und löschen. Änderungen sind nach dem Reload auch auf der Public Map sichtbar.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong data-admin-map-count>{{ number_format($markers->count(), 0, ',', '.') }}</strong>
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
            <button class="hh-primary-button" type="button" data-admin-map-add>Marker hinzufügen</button>
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
                'storeUrl' => $map['store_url'],
                'markers' => $markers,
            ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
        @endif
    </section>

    @php
        $cashMarkersWithUploads = $markers
            ->where('type', 'cash')
            ->filter(fn (array $marker) => $marker['uploads']->isNotEmpty());
    @endphp

    <section class="hh-card hh-admin-map-moderation">
        <div class="hh-admin-map-moderation-heading">
            <div>
                <p class="hh-kicker">Cash-Marker Uploads</p>
                <h2>Bildfreigaben</h2>
            </div>
            <p>Uploads bleiben privat, bis sie hier freigegeben werden. Pro Marker ist nur das zuletzt freigegebene Bild aktiv.</p>
        </div>

        @forelse($cashMarkersWithUploads as $marker)
            <article class="hh-admin-map-upload-group">
                <h3>#{{ $marker['id'] }} · {{ $marker['label'] }}</h3>
                <p>Aktives Bild: <code>{{ $marker['source_image'] ?: 'keins' }}</code></p>

                <div class="hh-admin-map-upload-grid">
                    @foreach($marker['uploads'] as $upload)
                        <div class="hh-admin-map-upload-card" data-status="{{ $upload['status'] }}">
                            <a href="{{ $upload['preview_url'] }}" target="_blank" rel="noopener">
                                <img src="{{ $upload['preview_url'] }}" alt="Upload {{ $upload['id'] }} für {{ $marker['label'] }}">
                            </a>
                            <div>
                                <strong>{{ $upload['original_name'] }}</strong>
                                <span>{{ ucfirst($upload['status']) }} · {{ number_format($upload['size'] / 1024, 0, ',', '.') }} KB</span>
                                @if($upload['uploader'])
                                    <span>Von {{ '@'.$upload['uploader'] }}</span>
                                @endif
                                @if($upload['rejection_reason'])
                                    <span>{{ $upload['rejection_reason'] }}</span>
                                @endif
                            </div>

                            @if($upload['status'] === 'pending')
                                <div class="hh-admin-map-upload-actions">
                                    <form method="POST" action="{{ $upload['approve_url'] }}">
                                        @csrf
                                        <button class="hh-primary-button" type="submit">Freigeben</button>
                                    </form>
                                    <form method="POST" action="{{ $upload['reject_url'] }}">
                                        @csrf
                                        <input name="reason" type="text" maxlength="1000" placeholder="Grund (optional)">
                                        <button class="hh-danger-button" type="submit">Ablehnen</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </article>
        @empty
            <p>Für diese Karte liegen noch keine Cash-Marker-Uploads vor.</p>
        @endforelse
    </section>

    <dialog class="hh-admin-map-dialog" data-admin-map-dialog>
        <form class="hh-admin-map-form" data-admin-map-form>
            <div class="hh-admin-map-dialog-header">
                <div>
                    <p class="hh-kicker" data-admin-map-form-mode>Marker bearbeiten</p>
                    <h2 data-admin-map-form-title>Marker</h2>
                </div>
                <button class="hh-admin-map-dialog-close" type="button" data-admin-map-cancel aria-label="Schließen">×</button>
            </div>

            <div class="hh-admin-map-form-grid">
                <label>
                    Typ
                    <select name="type" required>
                        @foreach(['compound', 'boss', 'spawn', 'supply', 'extract', 'cash', 'tower', 'bugs', 'wild', 'tarot'] as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Status
                    <select name="status" required>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="hidden">Hidden</option>
                    </select>
                </label>
                <label>
                    Label DE
                    <input name="label_de" type="text" maxlength="120">
                </label>
                <label>
                    Label EN
                    <input name="label_en" type="text" maxlength="120">
                </label>
                <label>
                    X
                    <input name="x" type="number" min="0" max="{{ $map['width'] }}" step="any" required>
                </label>
                <label>
                    Y
                    <input name="y" type="number" min="0" max="{{ $map['height'] }}" step="any" required>
                </label>
            </div>

            <p class="hh-admin-map-form-error" data-admin-map-form-error role="alert" hidden></p>
            <div class="hh-admin-map-form-actions">
                <button class="hh-danger-button" type="button" data-admin-map-delete>Marker löschen</button>
                <span></span>
                <button class="hh-secondary-button" type="button" data-admin-map-cancel>Abbrechen</button>
                <button class="hh-primary-button" type="submit">Speichern</button>
            </div>
        </form>
    </dialog>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}?v=1.9.4">
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/admin-maps.css') }}?v=2">
@endpush

@push('scripts')
    <script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}?v=1.9.4"></script>
    <script src="{{ asset('assets/hnt/maps/admin-maps.js') }}?v=2" defer></script>
@endpush
