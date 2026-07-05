@extends('admin.layouts.app')

@section('title', 'Feed Cards · Admin')
@section('admin_heading', 'Remote Feed Cards')

@section('content')
@php
    $card = $editingCard;
    $actionOptions = [
        '' => 'Keine Aktion',
        'hntrocks://notifications' => 'Benachrichtigungen',
        'hntrocks://messages' => 'Messages',
        'hntrocks://feed' => 'Feed',
        'hntrocks://moments' => 'Moments',
        'hntrocks://lfg' => 'LFG',
        'hntrocks://shop' => 'Shop',
        'hntrocks://profile' => 'Profil',
    ];
    $currentActionUrl = old('action_url', $card?->action_url);
    $currentActionChoice = array_key_exists((string) $currentActionUrl, $actionOptions) ? (string) $currentActionUrl : '__custom';
    $audienceHelp = [
        'all' => 'alle',
        'admins' => 'nur Admins',
        'user_ids' => 'JSON Beispiel {"user_ids":[1,2,3]}',
        'app_version_below' => 'JSON Beispiel {"version_code":9}',
        'app_version_above' => 'JSON Beispiel {"version_code":8}',
    ];
@endphp
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin · App Feed</p>
        <h1>Remote Feed Cards</h1>
        <p>Steuerbare Info Cards für den App-Feed. Wiederkehrende Hinweise bitte mit neuer remote_id anlegen.</p>
    </div>
    <div class="hh-page-header-meta">
        <strong>{{ $cards->total() }}</strong>
        <span>Cards</span>
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
            <h2>{{ $card ? 'Card bearbeiten' : 'Neue Card' }}</h2>
            <p class="hh-muted">action_url akzeptiert nur HNT Deep Links und freigegebene interne Pfade.</p>
        </div>
        <button class="hh-primary-button" type="submit" form="remote-card-form">{{ $card ? 'Speichern' : 'Erstellen' }}</button>
    </div>

    <div class="hh-remote-card-admin-grid">
        <form id="remote-card-form" class="hh-admin-menu-create-form" method="post" action="{{ $card ? route('admin.app-remote-feed-cards.update', $card) : route('admin.app-remote-feed-cards.store') }}">
            @csrf
            @if($card)
                @method('put')
            @endif
            <div class="hh-admin-menu-field">
                <label for="remote_id">remote_id</label>
                <input id="remote_id" name="remote_id" value="{{ old('remote_id', $card?->remote_id ?? 'feed_card_'.now()->format('Y_m_d').'_') }}" maxlength="191" required>
            </div>
            <div class="hh-admin-menu-field">
                <label for="title_de">Titel DE</label>
                <input id="title_de" name="title_de" value="{{ old('title_de', $card?->title_de) }}" maxlength="160" required data-remote-card-preview="title">
            </div>
            <div class="hh-admin-menu-field">
                <label for="title_en">Titel EN</label>
                <input id="title_en" name="title_en" value="{{ old('title_en', $card?->title_en) }}" maxlength="160">
            </div>
            <div class="hh-admin-menu-field">
                <label for="body_de">Text DE</label>
                <textarea id="body_de" name="body_de" rows="4" maxlength="1200" required data-remote-card-preview="body">{{ old('body_de', $card?->body_de) }}</textarea>
            </div>
            <div class="hh-admin-menu-field">
                <label for="body_en">Text EN</label>
                <textarea id="body_en" name="body_en" rows="4" maxlength="1200">{{ old('body_en', $card?->body_en) }}</textarea>
            </div>
            <div class="hh-admin-menu-field">
                <label for="cta_label_de">CTA DE</label>
                <input id="cta_label_de" name="cta_label_de" value="{{ old('cta_label_de', $card?->cta_label_de) }}" maxlength="80" data-remote-card-preview="cta">
            </div>
            <div class="hh-admin-menu-field">
                <label for="cta_label_en">CTA EN</label>
                <input id="cta_label_en" name="cta_label_en" value="{{ old('cta_label_en', $card?->cta_label_en) }}" maxlength="80">
            </div>
            <div class="hh-admin-menu-field">
                <label for="action_url_choice">Häufiges Ziel</label>
                <select id="action_url_choice" data-remote-card-action-choice>
                    @foreach($actionOptions as $url => $label)
                        <option value="{{ $url }}" @selected($currentActionChoice === (string) $url)>{{ $label }}{{ $url ? ' · '.$url : '' }}</option>
                    @endforeach
                    <option value="__custom" @selected($currentActionChoice === '__custom')>Custom erlaubter interner Pfad</option>
                </select>
            </div>
            <div class="hh-admin-menu-field">
                <label for="action_url">action_url</label>
                <input id="action_url" name="action_url" value="{{ $currentActionUrl }}" maxlength="255" placeholder="/feed/posts/123 oder hntrocks://notifications">
            </div>
            <div class="hh-admin-menu-field">
                <label for="style_variant">Style</label>
                <select id="style_variant" name="style_variant" data-remote-card-preview="variant">
                    @foreach(['gold_glass', 'warning', 'maintenance', 'neutral'] as $variant)
                        <option value="{{ $variant }}" @selected(old('style_variant', $card?->style_variant ?? 'gold_glass') === $variant)>{{ $variant }}</option>
                    @endforeach
                </select>
            </div>
            <div class="hh-admin-menu-field">
                <label for="priority">Priorität</label>
                <input id="priority" type="number" name="priority" value="{{ old('priority', $card?->priority ?? 100) }}" min="0" max="9999">
            </div>
            <div class="hh-admin-menu-field">
                <label for="starts_at">starts_at</label>
                <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $card?->starts_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="hh-admin-menu-field">
                <label for="ends_at">ends_at</label>
                <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', $card?->ends_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="hh-admin-menu-field">
                <label for="audience_type">Audience</label>
                <select id="audience_type" name="audience_type" data-remote-card-audience>
                    @foreach(array_keys($audienceHelp) as $audience)
                        <option value="{{ $audience }}" @selected(old('audience_type', $card?->audience_type ?? 'all') === $audience)>{{ $audience }}</option>
                    @endforeach
                </select>
                <small class="hh-muted" data-remote-card-audience-help></small>
            </div>
            <div class="hh-admin-menu-field">
                <label for="audience_payload">Audience JSON</label>
                <textarea id="audience_payload" name="audience_payload" rows="3" placeholder='{"user_ids":[1,2,3]} oder {"version_code":9}'>{{ old('audience_payload', $card?->audience_payload ? json_encode($card->audience_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
            </div>
            <label class="hh-admin-menu-check hh-admin-menu-create-check">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $card?->is_active ?? false))>
                <span>aktiv</span>
            </label>
            <label class="hh-admin-menu-check hh-admin-menu-create-check">
                <input type="checkbox" name="dismissible" value="1" @checked(old('dismissible', $card?->dismissible ?? true)) data-remote-card-preview="dismissible">
                <span>wegklickbar</span>
            </label>
        </form>

        <aside class="hh-remote-card-preview-wrap" aria-label="Card Preview">
            <div class="hh-remote-card-preview" data-remote-card-preview-card>
                <button type="button" class="hh-remote-card-preview-close" data-remote-card-preview-close aria-label="Schließen">×</button>
                <span class="hh-remote-card-preview-kicker" data-remote-card-preview-kicker>gold_glass</span>
                <strong data-remote-card-preview-title>Neue Card</strong>
                <p data-remote-card-preview-body>Text der Remote Feed Card</p>
                <span class="hh-remote-card-preview-cta" data-remote-card-preview-cta hidden>CTA</span>
            </div>
        </aside>
    </div>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <h2>Alle Cards</h2>
            <p class="hh-muted">Deaktivieren ist bevorzugt, damit remote_id-Historie erhalten bleibt.</p>
        </div>
    </div>
    <div class="hh-admin-table">
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>remote_id</th>
                    <th>Titel</th>
                    <th>Priorität</th>
                    <th>Weggeklickt</th>
                    <th>Zeitraum</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cards as $item)
                    @php
                        $now = now();
                        $isPlanned = $item->starts_at && $item->starts_at->isFuture();
                        $isExpired = $item->ends_at && $item->ends_at->isPast();
                        $isRunning = $item->is_active && ! $isPlanned && ! $isExpired;
                    @endphp
                    <tr>
                        <td>
                            <div class="hh-remote-card-badges">
                                <span class="hh-admin-ai-status {{ $item->is_active ? 'is-confirmed' : '' }}">{{ $item->is_active ? 'Aktiv' : 'Inaktiv' }}</span>
                                @if($isPlanned)
                                    <span class="hh-admin-ai-status">Geplant</span>
                                @endif
                                @if($isExpired)
                                    <span class="hh-admin-ai-status is-possible">Abgelaufen</span>
                                @endif
                                @if($isRunning)
                                    <span class="hh-admin-ai-status is-confirmed">Läuft</span>
                                @endif
                                <span class="hh-admin-ai-status">Audience: {{ $item->audience_type }}</span>
                                <span class="hh-admin-ai-status">Dismissible: {{ $item->dismissible ? 'ja' : 'nein' }}</span>
                            </div>
                        </td>
                        <td><strong>{{ $item->remote_id }}</strong><span>{{ $item->style_variant }}</span></td>
                        <td>{{ $item->title_de }}</td>
                        <td>{{ $item->priority }}</td>
                        <td>{{ $item->dismissals_count }}</td>
                        <td>{{ $item->starts_at?->format('d.m.Y H:i') ?? 'sofort' }} - {{ $item->ends_at?->format('d.m.Y H:i') ?? 'offen' }}</td>
                        <td>
                            <div class="hh-remote-card-actions">
                                <a class="hh-secondary-button" href="{{ route('admin.app-remote-feed-cards.edit', $item) }}">Bearbeiten</a>
                                <form method="post" action="{{ route('admin.app-remote-feed-cards.duplicate', $item) }}">
                                    @csrf
                                    <button class="hh-secondary-button" type="submit">Duplizieren</button>
                                </form>
                                <form method="post" action="{{ route('admin.app-remote-feed-cards.version', $item) }}">
                                    @csrf
                                    <button class="hh-secondary-button" type="submit">Neue Version</button>
                                </form>
                                <form method="post" action="{{ route('admin.app-remote-feed-cards.reset-dismissals', $item) }}" onsubmit="return confirm('Dismissals für diese Card wirklich zurücksetzen?');">
                                    @csrf
                                    <button class="hh-secondary-button" type="submit">Dismissals zurücksetzen</button>
                                </form>
                                @if($item->is_active)
                                    <form method="post" action="{{ route('admin.app-remote-feed-cards.deactivate', $item) }}">
                                        @csrf
                                        <button class="hh-secondary-button" type="submit">Deaktivieren</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Noch keine Remote Feed Cards.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $cards->links() }}
</section>

<script>
(() => {
    const form = document.getElementById('remote-card-form');
    if (! form) {
        return;
    }

    const actionChoice = form.querySelector('[data-remote-card-action-choice]');
    const actionUrl = form.querySelector('#action_url');
    const audience = form.querySelector('[data-remote-card-audience]');
    const audienceHelp = form.querySelector('[data-remote-card-audience-help]');
    const audienceHints = @json($audienceHelp);
    const preview = document.querySelector('[data-remote-card-preview-card]');
    const previewTitle = document.querySelector('[data-remote-card-preview-title]');
    const previewBody = document.querySelector('[data-remote-card-preview-body]');
    const previewCta = document.querySelector('[data-remote-card-preview-cta]');
    const previewKicker = document.querySelector('[data-remote-card-preview-kicker]');
    const previewClose = document.querySelector('[data-remote-card-preview-close]');

    const syncActionUrl = () => {
        if (! actionChoice || ! actionUrl || actionChoice.value === '__custom') {
            return;
        }

        actionUrl.value = actionChoice.value;
    };

    const syncAudienceHelp = () => {
        if (! audience || ! audienceHelp) {
            return;
        }

        audienceHelp.textContent = audienceHints[audience.value] || '';
    };

    const syncPreview = () => {
        if (! preview) {
            return;
        }

        const title = form.querySelector('[data-remote-card-preview="title"]')?.value.trim() || 'Neue Card';
        const body = form.querySelector('[data-remote-card-preview="body"]')?.value.trim() || 'Text der Remote Feed Card';
        const cta = form.querySelector('[data-remote-card-preview="cta"]')?.value.trim();
        const variant = form.querySelector('[data-remote-card-preview="variant"]')?.value || 'gold_glass';
        const dismissible = form.querySelector('[data-remote-card-preview="dismissible"]')?.checked;

        preview.dataset.variant = variant;
        previewTitle.textContent = title;
        previewBody.textContent = body;
        previewKicker.textContent = variant;
        previewClose.hidden = ! dismissible;
        previewCta.textContent = cta || '';
        previewCta.hidden = ! cta;
    };

    actionChoice?.addEventListener('change', syncActionUrl);
    audience?.addEventListener('change', syncAudienceHelp);
    form.querySelectorAll('[data-remote-card-preview]').forEach((input) => {
        input.addEventListener('input', syncPreview);
        input.addEventListener('change', syncPreview);
    });

    syncAudienceHelp();
    syncPreview();
})();
</script>
@endsection
