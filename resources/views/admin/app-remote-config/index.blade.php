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

@php
    $paletteLabels = [
        'canvas' => 'Canvas',
        'canvas_deep' => 'Canvas Deep',
        'surface' => 'Surface',
        'surface_raised' => 'Surface Raised',
        'surface_soft' => 'Surface Soft',
        'primary' => 'Primary',
        'primary_strong' => 'Primary Strong',
        'primary_muted' => 'Primary Muted',
        'text' => 'Text',
        'text_muted' => 'Text Muted',
        'text_faint' => 'Text Faint',
        'line' => 'Line',
        'danger' => 'Danger',
        'success' => 'Success',
        'warning' => 'Warning',
    ];
@endphp

<section class="hh-card hh-card-compact">
    <div class="hh-card-title-row">
        <div>
            <h2>Config JSON</h2>
            <p class="hh-muted">Beim Speichern werden fehlende Keys ergänzt und riskante Werte normalisiert.</p>
        </div>
        <button class="hh-primary-button" type="submit" form="remote-config-form">Speichern</button>
    </div>

    <form id="remote-config-form" method="post" action="{{ route('admin.app-remote-config.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="remote_branding_form" value="0" data-hnt-branding-dirty>
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="is_active" value="1" @checked($config->is_active)>
            <span>API aktiv ausliefern</span>
        </label>
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="publish_now" value="1">
            <span>published_at jetzt setzen</span>
        </label>

        <section class="hh-remote-branding-panel" aria-labelledby="remote-branding-heading">
            <div class="hh-card-title-row">
                <div>
                    <h2 id="remote-branding-heading">Remote Branding</h2>
                    <p class="hh-muted">Farben und App-Logo werden in die Remote Config gemerged. Zum Persistieren unten speichern.</p>
                </div>
                <button class="hh-secondary-button" type="button" data-hnt-default-palette>HNT Standardfarben einsetzen</button>
            </div>

            <div class="hh-remote-branding-grid">
                <div>
                    <label class="hh-admin-menu-check hh-admin-menu-create-check">
                        <input type="checkbox" name="palette_enabled" value="1" @checked(data_get($preview, 'config.theme.palette_enabled'))>
                        <span>Remote Farben aktivieren</span>
                    </label>

                    <div class="hh-remote-color-grid">
                        @foreach($paletteLabels as $key => $label)
                            @php($value = old('theme_palette.'.$key, $themePalette[$key] ?? '#000000'))
                            @php($defaultValue = $defaultThemePalette[$key] ?? $value)
                            <label class="hh-remote-color-field">
                                <span>{{ $label }}</span>
                                <input type="color" value="{{ $value }}" data-hnt-color-swatch="{{ $key }}">
                                <input type="text" name="theme_palette[{{ $key }}]" value="{{ $value }}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" data-hnt-color-input="{{ $key }}" data-default="{{ $defaultValue }}">
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="hh-remote-branding-side">
                    <label class="hh-admin-menu-check hh-admin-menu-create-check">
                        <input type="checkbox" name="logo_enabled" value="1" @checked($branding['logo_enabled'] ?? false)>
                        <span>Branding Logo aktivieren</span>
                    </label>

                    <div class="hh-remote-upload-grid">
                        <label class="hh-admin-menu-field">
                            <span>Logo Upload</span>
                            <input type="file" name="logo_file" accept=".svg,.png,.webp,image/svg+xml,image/png,image/webp">
                        </label>
                        <label class="hh-admin-menu-field">
                            <span>Optional Logo Dark Upload</span>
                            <input type="file" name="logo_dark_file" accept=".svg,.png,.webp,image/svg+xml,image/png,image/webp">
                        </label>
                    </div>

                    @if(! empty($branding['logo_url']))
                        <div class="hh-remote-current-logo">
                            <span>Aktuelles Logo</span>
                            <img src="{{ $branding['logo_url'] }}" alt="Aktuelles Remote Branding Logo">
                        </div>
                    @endif

                    @if(! empty($branding['logo_dark_url']))
                        <div class="hh-remote-current-logo">
                            <span>Aktuelles Dark Logo</span>
                            <img src="{{ $branding['logo_dark_url'] }}" alt="Aktuelles Remote Branding Dark Logo">
                        </div>
                    @endif
                </div>
            </div>

            <div class="hh-remote-branding-preview" style="--hh-preview-canvas: {{ $themePalette['canvas'] }}; --hh-preview-surface: {{ $themePalette['surface'] }}; --hh-preview-line: {{ $themePalette['line'] }}; --hh-preview-text: {{ $themePalette['text'] }}; --hh-preview-muted: {{ $themePalette['text_muted'] }}; --hh-preview-primary: {{ $themePalette['primary'] }}; --hh-preview-primary-strong: {{ $themePalette['primary_strong'] }}; --hh-preview-danger: {{ $themePalette['danger'] }}; --hh-preview-success: {{ $themePalette['success'] }};">
                <div>
                    @if(($branding['logo_enabled'] ?? false) && ! empty($branding['logo_url']))
                        <img src="{{ $branding['logo_url'] }}" alt="Remote Branding Preview Logo">
                    @endif
                    <strong>Remote Branding Preview</strong>
                    <span>Canvas, Surface, Text und Gold-Button aus der aktuellen Palette.</span>
                </div>
                <button type="button">Primary Action</button>
                <p><span>Success</span><span>Danger</span></p>
            </div>
        </section>

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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dirtyInput = document.querySelector('[data-hnt-branding-dirty]');
    const markDirty = () => {
        if (dirtyInput) {
            dirtyInput.value = '1';
        }
    };

    const syncColor = (key, value) => {
        const normalized = String(value || '').trim().toUpperCase();
        const textInput = document.querySelector(`[data-hnt-color-input="${key}"]`);
        const swatch = document.querySelector(`[data-hnt-color-swatch="${key}"]`);

        if (textInput) {
            textInput.value = normalized;
        }

        if (swatch && /^#[0-9A-F]{6}$/.test(normalized)) {
            swatch.value = normalized;
        }
    };

    document.querySelectorAll('[data-hnt-color-swatch]').forEach((swatch) => {
        swatch.addEventListener('input', () => {
            markDirty();
            syncColor(swatch.dataset.hntColorSwatch, swatch.value);
        });
    });

    document.querySelectorAll('[data-hnt-color-input]').forEach((input) => {
        input.addEventListener('input', () => {
            markDirty();
            syncColor(input.dataset.hntColorInput, input.value);
        });
    });

    document.querySelectorAll('input[name="palette_enabled"], input[name="logo_enabled"], input[type="file"]').forEach((input) => {
        input.addEventListener('change', markDirty);
    });

    document.querySelector('[data-hnt-default-palette]')?.addEventListener('click', () => {
        markDirty();
        document.querySelectorAll('[data-hnt-color-input]').forEach((input) => {
            syncColor(input.dataset.hntColorInput, input.dataset.default);
        });
    });
});
</script>
@endsection
