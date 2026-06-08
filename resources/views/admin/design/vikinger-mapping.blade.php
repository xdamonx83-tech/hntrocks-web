@extends('admin.layouts.app')

@section('title', 'Vikinger Mapping')

@section('admin_heading', 'Vikinger-Struktur-Mapping')

@section('content')
    <section class="hh-page-header">
        <div>
            <p class="hh-kicker">Admin · Design</p>
            <h1>Vikinger-Struktur-Mapping</h1>
            <p class="hh-muted">Diese Übersicht legt fest, welche Vikinger-HTML-Dateien als optische Referenz für welches hnt.rocks-Modul dienen. Marketplace bleibt ausgeschlossen.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>Phase {{ $mapping['phase'] ?? '22' }}</strong>
            <span>Optische Referenz: {{ $mapping['source'] ?? 'Vikinger HTML Template' }}</span>
        </div>
    </section>

        <section class="hh-card hh-card-compact hh-design-principles">
        <h2>Arbeitsregeln für die Designphase</h2>
        <div class="hh-design-rule-grid">
            @foreach(($mapping['principles'] ?? []) as $principle)
                <article>
                    <strong>{{ $loop->iteration }}</strong>
                    <span>{{ $principle }}</span>
                </article>
            @endforeach
        </div>
    </section>

    <section class="hh-design-map-list">
        @foreach(($mapping['modules'] ?? []) as $module)
            <article class="hh-card hh-card-compact hh-design-map-card">
                <div class="hh-design-map-head">
                    <div>
                        <p class="hh-kicker">{{ $module['status'] }}</p>
                        <h2>{{ $module['area'] }}</h2>
                        <p>{{ $module['notes'] }}</p>
                    </div>
                    <span>{{ $module['route'] }}</span>
                </div>

                <div class="hh-design-map-columns">
                    <div>
                        <h3>Vikinger-Referenz</h3>
                        <ul>
                            @foreach(($module['template_files'] ?? []) as $file)
                                <li><code>{{ $file }}</code></li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h3>hnt.rocks-Ziel</h3>
                        <ul>
                            @foreach(($module['target_views'] ?? []) as $view)
                                <li><code>{{ $view }}</code></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <section class="hh-card hh-card-compact hh-design-excluded">
        <h2>Bewusst ausgeschlossen</h2>
        <p class="hh-muted">Diese Marketplace-/Store-Dateien werden nicht als hnt.rocks-Funktion übernommen.</p>
        <div class="hh-design-chip-list">
            @foreach(($mapping['excluded_templates'] ?? []) as $file)
                <code>{{ $file }}</code>
            @endforeach
        </div>
    </section>

    <section class="hh-card hh-card-compact">
        <h2>Asset-Strategie</h2>
        <p>{{ $mapping['asset_strategy']['current'] ?? '' }}</p>
        <p>{{ $mapping['asset_strategy']['next'] ?? '' }}</p>
    </section>
@endsection
