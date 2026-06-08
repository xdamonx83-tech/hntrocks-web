@extends('admin.layouts.app')

@section('title', 'Kampagnenlinks · Admin')
@section('admin_heading', 'Kampagnenlinks')

@section('content')
    <section class="hh-page-header">
        <div>
            <p class="hh-kicker">Shortlinks & Werbung</p>
            <h1>Kampagnenlinks</h1>
            <p>Lege HNT-Shortlinks wie <strong>/go/summer-cup</strong> an und miss, wie oft deine Werbung geklickt wurde. Ideal für Cup-Anzeigen, Social-Posts und Discord-Promos.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>{{ number_format((int) $totalClicks, 0, ',', '.') }}</strong>
            <span>Klicks gesamt</span>
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

    <section class="hh-dashboard-card-grid hh-section-space" aria-label="Kampagnenlink Kennzahlen">
        <div class="hh-dashboard-metric is-gold">
            <span>Aktive Links</span>
            <strong>{{ number_format((int) $activeLinksCount, 0, ',', '.') }}</strong>
            <small>aktuell nutzbar</small>
        </div>
        <div class="hh-dashboard-metric is-green">
            <span>Klicks gesamt</span>
            <strong>{{ number_format((int) $totalClicks, 0, ',', '.') }}</strong>
            <small>über /go/*</small>
        </div>
    </section>

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <h2>Neuen Kampagnenlink anlegen</h2>
                <p class="hh-muted">Die Ziel-URL darf intern sein, z. B. <strong>/cups/bayou-blood-cup</strong>, oder extern mit https:// beginnen.</p>
            </div>
        </div>

        <form method="post" action="{{ route('admin.campaign-links.store') }}" class="hh-admin-outbound-form hh-section-space">
            @csrf
            <label>
                Titel
                <input name="label" value="{{ old('label') }}" required maxlength="160" placeholder="Summer Cup Werbung Facebook">
            </label>
            <label>
                Slug
                <input name="slug" value="{{ old('slug') }}" maxlength="140" placeholder="summer-cup-facebook">
            </label>
            <label class="hh-admin-outbound-full">
                Ziel-URL
                <input name="target_url" value="{{ old('target_url', '/cups/bayou-blood-cup') }}" required maxlength="2048" placeholder="/cups/bayou-blood-cup">
            </label>
            <label>
                UTM Source
                <input name="utm_source" value="{{ old('utm_source') }}" maxlength="80" placeholder="facebook">
            </label>
            <label>
                UTM Medium
                <input name="utm_medium" value="{{ old('utm_medium') }}" maxlength="80" placeholder="paid_social">
            </label>
            <label>
                UTM Campaign
                <input name="utm_campaign" value="{{ old('utm_campaign') }}" maxlength="120" placeholder="summer_cup_2026">
            </label>
            <label class="hh-checkline-card">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                Aktiv
            </label>
            <div class="hh-admin-outbound-actions">
                <button class="hh-primary-button" type="submit">Kampagnenlink speichern</button>
            </div>
        </form>
    </section>

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <h2>Vorhandene Kampagnenlinks</h2>
                <p class="hh-muted">Teile nur den HNT-Link. Klicks werden beim Aufruf gezählt und anschließend zur Zielseite weitergeleitet.</p>
            </div>
            <form method="get" action="{{ route('admin.campaign-links.index') }}" class="hh-admin-actions">
                <input name="q" value="{{ $search }}" placeholder="Suchen">
                <select name="status" onchange="this.form.submit()">
                    <option value="">Alle</option>
                    <option value="active" @selected($status === 'active')>Aktiv</option>
                    <option value="inactive" @selected($status === 'inactive')>Inaktiv</option>
                </select>
                <button class="hh-secondary-button" type="submit">Filtern</button>
            </form>
        </div>

        <div class="hh-admin-outbound-list hh-section-space">
            @forelse($links as $link)
                <article class="hh-admin-outbound-card">
                    <div class="hh-admin-outbound-head">
                        <div>
                            <span class="hh-admin-ai-status {{ $link->is_active ? '' : 'is-possible' }}">{{ $link->is_active ? 'Aktiv' : 'Inaktiv' }}</span>
                            <h3>{{ $link->label }}</h3>
                            <p class="hh-muted">
                                {{ number_format((int) $link->clicks_count, 0, ',', '.') }} Klicks gesamt ·
                                {{ number_format((int) $link->clicks_this_month_count, 0, ',', '.') }} diesen Monat ·
                                {{ number_format((int) $link->clicks_last_7_days_count, 0, ',', '.') }} letzte 7 Tage
                            </p>
                            <p class="hh-muted">Zuletzt geklickt: {{ $link->last_clicked_at?->diffForHumans() ?? 'noch nie' }}</p>
                        </div>
                        <div class="hh-admin-actions">
                            <a class="hh-link-button" target="_blank" rel="noopener" href="{{ $link->publicUrl() }}">Shortlink öffnen</a>
                            <a class="hh-secondary-button" target="_blank" rel="noopener noreferrer" href="{{ $link->targetUrlWithUtm() }}">Ziel öffnen</a>
                        </div>
                    </div>

                    <div class="hh-admin-outbound-copy">
                        <span>Shortlink</span>
                        <code>{{ $link->publicUrl() }}</code>
                    </div>

                    <div class="hh-admin-outbound-copy">
                        <span>Ziel mit UTM</span>
                        <code>{{ $link->targetUrlWithUtm() }}</code>
                    </div>

                    <form method="post" action="{{ route('admin.campaign-links.update', $link) }}" class="hh-admin-outbound-form is-edit">
                        @csrf
                        @method('put')
                        <label>
                            Titel
                            <input name="label" value="{{ old('label', $link->label) }}" required maxlength="160">
                        </label>
                        <label>
                            Slug
                            <input name="slug" value="{{ old('slug', $link->slug) }}" maxlength="140">
                        </label>
                        <label class="hh-admin-outbound-full">
                            Ziel-URL
                            <input name="target_url" value="{{ old('target_url', $link->target_url) }}" required maxlength="2048">
                        </label>
                        <label>
                            UTM Source
                            <input name="utm_source" value="{{ old('utm_source', $link->utm_source) }}" maxlength="80">
                        </label>
                        <label>
                            UTM Medium
                            <input name="utm_medium" value="{{ old('utm_medium', $link->utm_medium) }}" maxlength="80">
                        </label>
                        <label>
                            UTM Campaign
                            <input name="utm_campaign" value="{{ old('utm_campaign', $link->utm_campaign) }}" maxlength="120">
                        </label>
                        <label class="hh-checkline-card">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $link->is_active))>
                            Aktiv
                        </label>
                        <div class="hh-admin-outbound-actions">
                            <button class="hh-primary-button" type="submit">Speichern</button>
                        </div>
                    </form>

                    <form method="post" action="{{ route('admin.campaign-links.destroy', $link) }}" onsubmit="return confirm('Diesen Kampagnenlink wirklich löschen? Die zugehörigen Klick-Zeilen werden mitgelöscht.')" class="hh-admin-outbound-delete">
                        @csrf
                        @method('delete')
                        <button type="submit" class="hh-danger-button">Löschen</button>
                    </form>
                </article>
            @empty
                <div class="hh-admin-list">
                    <div>
                        <strong>Noch keine Kampagnenlinks gefunden.</strong>
                        <span>Lege oben den ersten Shortlink für deine Cup-Werbung an.</span>
                    </div>
                </div>
            @endforelse
        </div>

        {{ $links->links() }}
    </section>
@endsection
