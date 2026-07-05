@extends('admin.layouts.app')

@section('title', 'Remote Config · Admin')
@section('admin_heading', 'App Remote Config')

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin · App-Steuerung</p>
        <h1>Remote Config</h1>
        <p>Globale App-Konfiguration mit sicheren Defaults, Feature Flags und Android-Versionen.</p>
    </div>
    <div class="hh-page-header-meta">
        <strong>{{ $config->is_active ? 'aktiv' : 'inaktiv' }}</strong>
        <span>{{ $config->published_at?->format('d.m.Y H:i') ?? 'nicht publiziert' }}</span>
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
            <h2>Config JSON</h2>
            <p class="hh-muted">Beim Speichern werden fehlende Keys ergänzt und riskante Werte normalisiert.</p>
        </div>
        <button class="hh-primary-button" type="submit" form="remote-config-form">Speichern</button>
    </div>

    <form id="remote-config-form" method="post" action="{{ route('admin.app-remote-config.update') }}">
        @csrf
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="is_active" value="1" @checked($config->is_active)>
            <span>API aktiv ausliefern</span>
        </label>
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="publish_now" value="1">
            <span>published_at jetzt setzen</span>
        </label>
        <div class="hh-admin-menu-field">
            <label for="config-json">JSON</label>
            <textarea id="config-json" class="hh-admin-menu-input" name="config_json" rows="24" spellcheck="false" required>{{ old('config_json', $configJson) }}</textarea>
        </div>
    </form>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <h2>API Preview</h2>
            <p class="hh-muted">Vereinfachte Vorschau ohne user-spezifische Dismissals.</p>
        </div>
    </div>
    <pre style="white-space: pre-wrap; margin: 0;">{{ json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</section>
@endsection
