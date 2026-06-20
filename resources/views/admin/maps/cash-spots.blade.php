@extends('admin.layouts.app')

@section('title', 'Kassenspot-Vorschläge · HNT Maps Admin')
@section('admin_heading', 'Kassenspot-Vorschläge')

@section('content')
    <section class="hh-page-header hh-admin-map-header">
        <div>
            <p class="hh-kicker"><a href="{{ route('admin.maps.index') }}">← Zu HNT Maps</a></p>
            <h1>Neue Kassenspots prüfen</h1>
            <p>Vorschläge mit Position und Screenshot prüfen. Eine Freigabe erzeugt einen neuen Cash-Marker.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>{{ number_format($cashSpots->count(), 0, ',', '.') }}</strong>
            <span>{{ $statusOptions[$status] }}</span>
        </div>
    </section>

    <nav class="hh-admin-map-upload-filters" aria-label="Vorschlagsstatus filtern">
        @foreach($statusOptions as $value => $label)
            <a href="{{ route('admin.maps.cash-spots.index', ['status' => $value]) }}" @class(['is-active' => $status === $value])>{{ $label }}</a>
        @endforeach
    </nav>

    <section class="hh-admin-map-upload-review-grid">
        @forelse($cashSpots as $submission)
            @php
                $submitter = $submission->user?->username ? '@'.$submission->user->username : 'Gast';
                $mapUrl = route('maps.show', $submission->map->slug).'?x='.rawurlencode((string) $submission->x).'&y='.rawurlencode((string) $submission->y).'&z=2';
            @endphp
            <article class="hh-card hh-admin-map-upload-review-card" data-status="{{ $submission->status }}">
                <a class="hh-admin-map-upload-review-image" href="{{ route('admin.maps.cash-spots.show', $submission) }}" target="_blank" rel="noopener">
                    <img src="{{ route('admin.maps.cash-spots.show', $submission) }}" alt="Kassenspot-Vorschlag {{ $submission->id }} auf {{ $submission->map->name }}">
                </a>

                <div class="hh-admin-map-upload-review-body">
                    <div class="hh-admin-map-upload-review-title">
                        <div><p class="hh-kicker">{{ $submission->map->name }}</p><h2>Vorschlag #{{ $submission->id }}</h2></div>
                        <span class="hh-admin-map-upload-status">{{ ucfirst($submission->status) }}</span>
                    </div>

                    <dl class="hh-admin-map-upload-meta">
                        <div><dt>Position</dt><dd><strong>X {{ number_format($submission->x, 2, ',', '.') }} · Y {{ number_format($submission->y, 2, ',', '.') }}</strong> · <a href="{{ $mapUrl }}" target="_blank" rel="noopener">Karte öffnen</a></dd></div>
                        <div><dt>Eingereicht von</dt><dd>{{ $submitter }}</dd></div>
                        @if($submission->submitter_name)<div><dt>Name</dt><dd>{{ $submission->submitter_name }}</dd></div>@endif
                        @if($submission->submitter_email)<div><dt>E-Mail</dt><dd>{{ $submission->submitter_email }}</dd></div>@endif
                        <div><dt>Datei</dt><dd>{{ $submission->original_name }}</dd></div>
                        <div><dt>Größe</dt><dd>{{ number_format($submission->size / 1024, 0, ',', '.') }} KB</dd></div>
                        <div><dt>Upload</dt><dd>{{ $submission->created_at?->format('d.m.Y H:i') }}</dd></div>
                        @if($submission->marker)<div><dt>Marker</dt><dd>#{{ $submission->marker->id }}</dd></div>@endif
                        @if($submission->reviewer)<div><dt>Geprüft von</dt><dd>{{ '@'.$submission->reviewer->username }}</dd></div>@endif
                        @if($submission->reviewed_at)<div><dt>Geprüft am</dt><dd>{{ $submission->reviewed_at->format('d.m.Y H:i') }}</dd></div>@endif
                        @if($submission->rejection_reason)<div><dt>Ablehnungsgrund</dt><dd>{{ $submission->rejection_reason }}</dd></div>@endif
                    </dl>

                    @if($submission->status === 'pending')
                        <div class="hh-admin-map-upload-actions">
                            <form method="POST" action="{{ route('admin.maps.cash-spots.approve', $submission) }}">@csrf<button class="hh-primary-button" type="submit">Als neuen Kassenspot freigeben</button></form>
                            <form method="POST" action="{{ route('admin.maps.cash-spots.reject', $submission) }}">@csrf<input name="reason" type="text" maxlength="1000" placeholder="Grund (optional)"><button class="hh-danger-button" type="submit">Ablehnen</button></form>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="hh-card hh-admin-map-upload-empty">Keine Vorschläge für diesen Filter vorhanden.</div>
        @endforelse
    </section>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/hnt/maps/admin-maps.css') }}?v=4">
@endpush
