@extends('layouts.app')

@section('title', 'Moment hochladen · hnt.rocks')

@section('content')
<div class="hh-page-header">
    <div>
        <p class="hh-kicker">Moments Upload</p>
        <h1>Moment hochladen</h1>
        <p>Reels werden nach dem Upload automatisch auf 9:16 komprimiert. Originaldateien werden nach erfolgreicher Verarbeitung gelöscht.</p>
    </div>
    <div class="hh-page-header-actions">
        <a class="hh-secondary-button" href="{{ route('moments.index') }}">Zurück</a>
    </div>
</div>

@if ($errors->any())
    <div class="hh-alert hh-alert-danger">
        <strong>Bitte prüfen:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="hh-card hh-card-wide">
    <form class="hh-form" method="post" action="{{ route('moments.store') }}" enctype="multipart/form-data">
        @csrf

        <label for="video">Video</label>
        <input id="video" name="video" type="file" accept="video/mp4,video/webm,video/quicktime" required>
        <p class="hh-form-hint">Erlaubt: MP4, WebM, QuickTime. Maximal 200 MB. Ausgabe: optimiertes MP4 in 9:16 mit 720p-Höhe/Breite 720×1280.</p>

        <label for="cover">Cover optional</label>
        <input id="cover" name="cover" type="file" accept="image/*">

        <label for="caption">Caption</label>
        <input id="caption" name="caption" type="text" value="{{ old('caption') }}" maxlength="220" placeholder="Kurzer Text zum Moment ...">

        <label for="description">Beschreibung optional</label>
        <textarea id="description" name="description" rows="4" placeholder="Mehr Kontext, Loadout, Match-Situation ...">{{ old('description') }}</textarea>

        <div class="hh-form-grid">
            <div>
                <label for="visibility">Sichtbarkeit</label>
                <select id="visibility" name="visibility">
                    <option value="public" @selected(old('visibility', 'public') === 'public')>Öffentlich</option>
                    <option value="registered" @selected(old('visibility') === 'registered')>Nur eingeloggte Nutzer</option>
                    <option value="private" @selected(old('visibility') === 'private')>Privat / nur ich</option>
                </select>
            </div>
            <div>
                <label for="trim_start_seconds">Trim Start in Sekunden optional</label>
                <input id="trim_start_seconds" name="trim_start_seconds" type="number" min="0" value="{{ old('trim_start_seconds') }}" placeholder="0">
            </div>
            <div>
                <label for="trim_end_seconds">Trim Ende in Sekunden optional</label>
                <input id="trim_end_seconds" name="trim_end_seconds" type="number" min="1" value="{{ old('trim_end_seconds') }}" placeholder="z.B. 28">
            </div>
        </div>

        <button class="hh-primary-button" type="submit">Moment speichern</button>
    </form>
</section>
@endsection
