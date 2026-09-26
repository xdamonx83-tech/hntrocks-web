@extends('admin.layouts.app')

@section('title', 'Website Appearance · Admin')
@section('admin_heading', 'Website Appearance')

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin · System</p>
        <h1>Website Appearance</h1>
        <p>Remote-Hintergründe für die Website. JPG, PNG oder WebP, maximal 8 MB pro Bild.</p>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="hh-alert hh-alert-danger">{{ $errors->first() }}</div>
@endif

<section class="hh-card hh-card-compact">
    <div class="hh-card-title-row">
        <div>
            <h2>Website-Hintergründe</h2>
            <p class="hh-muted">Ein oder mehrere Bilder auswählen und gemeinsam speichern. Nicht ausgewählte Slots bleiben unverändert.</p>
        </div>
        <button class="hh-primary-button" type="submit" form="appearance-upload-form">Bilder speichern</button>
    </div>

    <form id="appearance-upload-form" method="post" action="{{ route('admin.appearance.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="hh-remote-upload-grid">
            @foreach($slots as $slot => [$label, $description])
                <div>
                    <h3>{{ $label }}</h3>
                    <p class="hh-muted">{{ $description }}</p>
                    @if($backgrounds[$slot]['url'])
                        <p>Remote-Bild aktiv · Version {{ $backgrounds[$slot]['version'] }}</p>
                        <div class="hh-remote-current-logo">
                            <img src="{{ $backgrounds[$slot]['url'] }}" alt="Aktueller {{ $label }}" loading="lazy">
                        </div>
                    @else
                        <p>Lokaler Fallback aktiv</p>
                    @endif
                    <label class="hh-admin-menu-field">
                        <span>{{ $label }} hochladen</span>
                        <input type="file" name="backgrounds[{{ $slot }}]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </label>
                </div>
            @endforeach
        </div>
    </form>
</section>

<section class="hh-card hh-card-compact">
    <h2>Remote-Bild entfernen</h2>
    <p class="hh-muted">Das Entfernen aktiviert für diesen Slot wieder den lokalen Fallback.</p>
    <div class="hh-remote-upload-grid">
        @foreach($slots as $slot => [$label, $description])
            <form method="post" action="{{ route('admin.appearance.reset', $slot) }}">
                @csrf
                @method('DELETE')
                <button class="hh-secondary-button" type="submit" @disabled(!$backgrounds[$slot]['url'])>{{ $label }} entfernen</button>
            </form>
        @endforeach
    </div>
</section>
@endsection
