@extends('admin.layouts.app')

@section('title', 'Cash Uploads · HNT Maps Admin')
@section('admin_heading', 'Cash Uploads')

@section('content')
    <section class="hh-page-header hh-admin-map-header">
        <div>
            <p class="hh-kicker"><a href="{{ route('admin.maps.index') }}">← Zu HNT Maps</a></p>
            <h1>Cash-Screenshot Freigaben</h1>
            <p>Private Einreichungen prüfen. Nur eine explizite Freigabe setzt das aktive öffentliche Markerbild.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>{{ number_format($cashUploads->count(), 0, ',', '.') }}</strong>
            <span>{{ $statusOptions[$status] }}</span>
        </div>
    </section>

    <nav class="hh-admin-map-upload-filters" aria-label="Uploadstatus filtern">
        @foreach($statusOptions as $value => $label)
            <a href="{{ route('admin.maps.uploads.index', ['status' => $value]) }}" @class(['is-active' => $status === $value])>{{ $label }}</a>
        @endforeach
    </nav>

    <section class="hh-admin-map-upload-review-grid">
        @forelse($cashUploads as $upload)
            @php
                $marker = $upload->marker;
                $markerLabel = $marker?->label_de ?: $marker?->label_en ?: 'Cash-Marker #'.($marker?->id ?? '?');
                $submitter = $upload->uploader?->username ? '@'.$upload->uploader->username : 'Gast';
            @endphp
            <article class="hh-card hh-admin-map-upload-review-card" data-status="{{ $upload->status }}">
                <a class="hh-admin-map-upload-review-image" href="{{ route('admin.maps.uploads.show', $upload) }}" target="_blank" rel="noopener">
                    <img src="{{ route('admin.maps.uploads.show', $upload) }}" alt="Upload {{ $upload->id }} für {{ $markerLabel }}">
                </a>

                <div class="hh-admin-map-upload-review-body">
                    <div class="hh-admin-map-upload-review-title">
                        <div>
                            <p class="hh-kicker">{{ $marker?->map?->name ?? 'Unbekannte Karte' }}</p>
                            <h2>{{ $markerLabel }}</h2>
                        </div>
                        <span class="hh-admin-map-upload-status">{{ ucfirst($upload->status) }}</span>
                    </div>

                    <dl class="hh-admin-map-upload-meta">
                        <div><dt>Eingereicht von</dt><dd>{{ $submitter }}</dd></div>
                        @if($upload->submitter_name)
                            <div><dt>Name</dt><dd>{{ $upload->submitter_name }}</dd></div>
                        @endif
                        @if($upload->submitter_email)
                            <div><dt>E-Mail</dt><dd>{{ $upload->submitter_email }}</dd></div>
                        @endif
                        <div><dt>Datei</dt><dd>{{ $upload->original_name }}</dd></div>
                        <div><dt>Größe</dt><dd>{{ number_format($upload->size / 1024, 0, ',', '.') }} KB</dd></div>
                        <div><dt>Upload</dt><dd>{{ $upload->created_at?->format('d.m.Y H:i') }}</dd></div>
                        @if($upload->reviewer)
                            <div><dt>Geprüft von</dt><dd>{{ '@'.$upload->reviewer->username }}</dd></div>
                        @endif
                        @if($upload->reviewed_at)
                            <div><dt>Geprüft am</dt><dd>{{ $upload->reviewed_at->format('d.m.Y H:i') }}</dd></div>
                        @endif
                        @if($upload->rejection_reason)
                            <div><dt>Ablehnungsgrund</dt><dd>{{ $upload->rejection_reason }}</dd></div>
                        @endif
                    </dl>

                    @if($upload->status === 'pending')
                        <div class="hh-admin-map-upload-actions">
                            <form method="POST" action="{{ route('admin.maps.uploads.approve', $upload) }}">
                                @csrf
                                <button class="hh-primary-button" type="submit">Freigeben</button>
                            </form>
                            <form method="POST" action="{{ route('admin.maps.uploads.reject', $upload) }}">
                                @csrf
                                <input name="reason" type="text" maxlength="1000" placeholder="Grund (optional)">
                                <button class="hh-danger-button" type="submit">Ablehnen</button>
                            </form>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="hh-card hh-admin-map-upload-empty">Keine Uploads für diesen Filter vorhanden.</div>
        @endforelse
    </section>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/admin-maps.css') }}?v=3">
@endpush
