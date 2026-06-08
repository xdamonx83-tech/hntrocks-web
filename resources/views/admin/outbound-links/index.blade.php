@extends('admin.layouts.app')

@section('title', 'Externe Links · Admin')
@section('admin_heading', 'Freigegebene externe Links')

@section('content')
    <section class="hh-page-header">
        <div>
            <p class="hh-kicker">Outgoing Links</p>
            <h1>Freigegebene externe Links</h1>
            <p>Erstelle geprüfte HNT-Links wie <strong>/out/discord-cup</strong>. Externe Links bleiben im Feed weiterhin deaktiviert, aber diese geprüften Weiterleitungen sind intern klickbar.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>{{ $links->total() }}</strong>
            <span>Links</span>
        </div>
    </section>

    @if(session('status'))
        <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="hh-alert hh-alert-danger">
            <strong>Bitte Eingaben prüfen.</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <h2>Neuen Link freigeben</h2>
                <p class="hh-muted">Für Discord-Cups, Partner-Turniere oder geprüfte externe Eventseiten.</p>
            </div>
        </div>

        <form method="post" action="{{ route('admin.outbound-links.store') }}" class="hh-admin-outbound-form hh-section-space">
            @csrf
            <label>
                Titel
                <input name="title" value="{{ old('title') }}" required maxlength="160" placeholder="Discord Cup von Partner XY">
            </label>
            <label>
                Slug
                <input name="slug" value="{{ old('slug') }}" maxlength="140" placeholder="discord-cup">
            </label>
            <label class="hh-admin-outbound-full">
                Ziel-URL
                <input name="target_url" value="{{ old('target_url') }}" required placeholder="https://discord.gg/...">
            </label>
            <label class="hh-admin-outbound-full">
                Beschreibung für Zwischenseite
                <textarea name="description" maxlength="1200" placeholder="Kurze Erklärung, warum dieser Link freigegeben ist">{{ old('description') }}</textarea>
            </label>
            <label class="hh-admin-outbound-full">
                Admin-Notiz
                <textarea name="admin_note" maxlength="1200" placeholder="Nur intern sichtbar">{{ old('admin_note') }}</textarea>
            </label>
            <label class="hh-checkline-card">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                Aktiv
            </label>
            <div class="hh-admin-outbound-actions">
                <button class="hh-primary-button" type="submit">Link speichern</button>
            </div>
        </form>
    </section>

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <h2>Vorhandene Links</h2>
                <p class="hh-muted">Teile im Feed nur die HNT-URL. Der externe Link bleibt geschützt hinter der Zwischenseite.</p>
            </div>
            <form method="get" action="{{ route('admin.outbound-links.index') }}" class="hh-admin-actions">
                <select name="status" onchange="this.form.submit()">
                    <option value="">Alle</option>
                    <option value="active" @selected($status === 'active')>Aktiv</option>
                    <option value="inactive" @selected($status === 'inactive')>Inaktiv</option>
                </select>
            </form>
        </div>

        <div class="hh-admin-outbound-list hh-section-space">
            @forelse($links as $link)
                <article class="hh-admin-outbound-card">
                    <div class="hh-admin-outbound-head">
                        <div>
                            <span class="hh-admin-ai-status {{ $link->is_active ? '' : 'is-possible' }}">{{ $link->is_active ? 'Aktiv' : 'Inaktiv' }}</span>
                            <h3>{{ $link->title }}</h3>
                            <p class="hh-muted">{{ $link->safeDomain() }} · {{ number_format($link->clicks_count, 0, ',', '.') }} Klicks</p>
                        </div>
                        <div class="hh-admin-actions">
                            <a class="hh-link-button" target="_blank" rel="noopener" href="{{ $link->publicUrl() }}">HNT-Link öffnen</a>
                            <a class="hh-secondary-button" target="_blank" rel="noopener noreferrer nofollow" href="{{ $link->target_url }}">Ziel öffnen</a>
                        </div>
                    </div>

                    <div class="hh-admin-outbound-copy">
                        <span>Feed-Link</span>
                        <code>{{ $link->publicUrl() }}</code>
                    </div>

                    <form method="post" action="{{ route('admin.outbound-links.update', $link) }}" class="hh-admin-outbound-form is-edit">
                        @csrf
                        @method('put')
                        <label>
                            Titel
                            <input name="title" value="{{ old('title', $link->title) }}" required maxlength="160">
                        </label>
                        <label>
                            Slug
                            <input name="slug" value="{{ old('slug', $link->slug) }}" maxlength="140">
                        </label>
                        <label class="hh-admin-outbound-full">
                            Ziel-URL
                            <input name="target_url" value="{{ old('target_url', $link->target_url) }}" required>
                        </label>
                        <label class="hh-admin-outbound-full">
                            Beschreibung
                            <textarea name="description" maxlength="1200">{{ old('description', $link->description) }}</textarea>
                        </label>
                        <label class="hh-admin-outbound-full">
                            Admin-Notiz
                            <textarea name="admin_note" maxlength="1200">{{ old('admin_note', $link->admin_note) }}</textarea>
                        </label>
                        <label class="hh-checkline-card">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $link->is_active))>
                            Aktiv
                        </label>
                        <div class="hh-admin-outbound-actions">
                            <button class="hh-primary-button" type="submit">Speichern</button>
                        </div>
                    </form>

                    <form method="post" action="{{ route('admin.outbound-links.destroy', $link) }}" onsubmit="return confirm('Diesen Link wirklich löschen?')" class="hh-admin-outbound-delete">
                        @csrf
                        @method('delete')
                        <button type="submit" class="hh-danger-button">Löschen</button>
                    </form>
                </article>
            @empty
                <div class="hh-admin-list">
                    <div>
                        <strong>Noch keine freigegebenen externen Links.</strong>
                        <span>Lege oben den ersten Link an, z. B. für einen Discord-Cup.</span>
                    </div>
                </div>
            @endforelse
        </div>

        {{ $links->links() }}
    </section>
@endsection
