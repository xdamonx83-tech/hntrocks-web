@extends('admin.layouts.app')

@section('title', 'Theme Preview · hnt.rocks')

@section('admin_heading', 'Theme Preview')

@section('content')
<section class="hh-admin-dashboard-hero">
    <div>
        <p class="hh-kicker">HNT Preview Mode</p>
        <h1>Neues Template sicher testen</h1>
        <p>Der Preview-Modus wird nur in deiner aktuellen Session gesetzt. Fehlt eine Preview-View, fällt HNT.rocks automatisch auf das konfigurierte Fallback-Theme zurück.</p>
    </div>
    <div class="hh-admin-dashboard-hero-actions">
        <a class="hh-secondary-button" href="{{ route('feed.index') }}" target="_blank" rel="noopener">Website öffnen</a>
        @if($previewActive)
            <a class="hh-secondary-button" href="{{ route('admin.theme-preview.shell') }}" target="_blank" rel="noopener">Preview-Shell öffnen</a>
        @endif
        @if($previewActive)
            <form method="post" action="{{ route('admin.theme-preview.stop') }}">
                @csrf
                <button class="hh-primary-button" type="submit">Preview beenden</button>
            </form>
        @else
            <form method="post" action="{{ route('admin.theme-preview.start') }}">
                @csrf
                <button class="hh-primary-button" type="submit" @disabled(! $previewAvailable)>Preview aktivieren</button>
            </form>
        @endif
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

@if(session('error'))
    <div class="hh-alert hh-alert-danger">{{ session('error') }}</div>
@endif

<section class="hh-dashboard-card-grid" aria-label="Theme Preview Status">
    <article class="hh-dashboard-metric is-{{ $previewActive ? 'green' : 'gold' }}">
        <span>Session-Status</span>
        <strong>{{ $previewActive ? 'Aktiv' : 'Aus' }}</strong>
        <small>{{ $previewActive ? 'nur für diese Session' : 'Socialite bleibt aktiv' }}</small>
    </article>

    <article class="hh-dashboard-metric is-gold">
        <span>Preview Theme</span>
        <strong>{{ $previewTheme }}</strong>
        <small>resources/views/themes/{{ $previewTheme }}</small>
    </article>

    <article class="hh-dashboard-metric is-green">
        <span>Fallback</span>
        <strong>{{ $fallbackTheme }}</strong>
        <small>bei fehlenden Preview-Views</small>
    </article>

    <article class="hh-dashboard-metric is-{{ $previewAvailable ? 'green' : 'red' }}">
        <span>Zugriff</span>
        <strong>{{ $previewAvailable ? 'Erlaubt' : 'Gesperrt' }}</strong>
        <small>{{ $previewAvailable ? 'Admin ist freigeschaltet' : 'Allowlist fehlt oder passt nicht' }}</small>
    </article>
</section>

<section class="hh-dashboard-layout">
    <div class="hh-dashboard-main-column">
        <article class="hh-card">
            <div class="hh-card-title-row">
                <div>
                    <h2>Preview-Schutz</h2>
                    <p class="hh-muted">Die Preview ist absichtlich zusätzlich zur Admin-Rolle über User-ID oder E-Mail begrenzbar.</p>
                </div>
                <span class="hh-admin-chip">Session: {{ $sessionKey }}</span>
            </div>

            <div class="hh-dashboard-health-list">
                <div class="hh-dashboard-health-item is-{{ $allowAnyAdmin ? 'warning' : 'neutral' }}">
                    <div>
                        <strong>{{ $allowAnyAdmin ? 'Ja' : 'Nein' }}</strong>
                        <span>Jeder Admin erlaubt</span>
                    </div>
                    <small>HH_THEME_PREVIEW_ALLOW_ANY_ADMIN</small>
                </div>

                <div class="hh-dashboard-health-item is-{{ count($allowedUserIds) ? 'good' : 'neutral' }}">
                    <div>
                        <strong>{{ count($allowedUserIds) ? implode(', ', $allowedUserIds) : '—' }}</strong>
                        <span>Erlaubte User-IDs</span>
                    </div>
                    <small>HH_THEME_PREVIEW_USER_IDS</small>
                </div>

                <div class="hh-dashboard-health-item is-{{ count($allowedEmails) ? 'good' : 'neutral' }}">
                    <div>
                        <strong>{{ count($allowedEmails) ? implode(', ', $allowedEmails) : '—' }}</strong>
                        <span>Erlaubte E-Mails</span>
                    </div>
                    <small>HH_THEME_PREVIEW_USER_EMAILS</small>
                </div>
            </div>

            @unless($previewRestrictionConfigured)
                <p class="hh-empty-state">Aus Sicherheitsgründen ist die Aktivierung gesperrt, solange keine Allowlist gesetzt ist. Setze mindestens eine User-ID oder E-Mail in der .env.</p>
            @endunless
        </article>

        <article class="hh-card">
            <div class="hh-card-title-row">
                <div>
                    <h2>View-Auflösung</h2>
                    <p class="hh-muted">Diese Liste zeigt, welche View aktuell über das Theme-System gefunden wird.</p>
                </div>
                <span class="hh-admin-chip">Aktiv: {{ $activeTheme }}</span>
            </div>

            <div class="hh-dashboard-list">
                @foreach($sampleResolutions as $item)
                    <div class="hh-dashboard-list-row">
                        <span class="hh-dashboard-dot is-{{ str_starts_with($item['resolved'], 'themes.'.$previewTheme.'.') ? 'green' : (str_starts_with($item['resolved'], 'themes.'.$fallbackTheme.'.') ? 'gold' : 'red') }}"></span>
                        <div>
                            <strong>{{ $item['view'] }}</strong>
                            <small>{{ $item['resolved'] }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </div>

    <aside class="hh-dashboard-side-column">
        <article class="hh-card">
            <div class="hh-card-title-row">
                <div>
                    <h2>Template-Mapping</h2>
                    <p class="hh-muted">Die HTML-Dateien sind Referenzen für die spätere Seitenübertragung.</p>
                </div>
            </div>
            <div class="hh-dashboard-list">
                @foreach($templateReferences as $file => $target)
                    <div class="hh-dashboard-list-row">
                        <span class="hh-dashboard-dot is-green"></span>
                        <div>
                            <strong>{{ $file }}</strong>
                            <small>{{ $target }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="hh-card">
            <h2>Preview-Shell</h2>
            <p class="hh-muted">Die globale Layout-Hülle ist als isolierte Admin-Testseite vorhanden. Sie ersetzt noch keine echte Nutzerseite.</p>
            @if($previewActive)
                <div class="hh-admin-dashboard-hero-actions" style="margin-top: 1rem;">
                    <a class="hh-primary-button" href="{{ route('admin.theme-preview.shell') }}" target="_blank" rel="noopener">Shell ansehen</a>
                </div>
            @else
                <p class="hh-empty-state">Aktiviere zuerst die Preview-Session, dann wird die Shell-Testseite freigeschaltet.</p>
            @endif
        </article>
    </aside>
</section>
@endsection
