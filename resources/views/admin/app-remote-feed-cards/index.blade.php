@extends('admin.layouts.app')

@section('title', 'Feed Cards · Admin')
@section('admin_heading', 'Remote Feed Cards')

@section('content')
@php($card = $editingCard)
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
            <input id="title_de" name="title_de" value="{{ old('title_de', $card?->title_de) }}" maxlength="160" required>
        </div>
        <div class="hh-admin-menu-field">
            <label for="title_en">Titel EN</label>
            <input id="title_en" name="title_en" value="{{ old('title_en', $card?->title_en) }}" maxlength="160">
        </div>
        <div class="hh-admin-menu-field">
            <label for="body_de">Text DE</label>
            <textarea id="body_de" name="body_de" rows="4" maxlength="1200" required>{{ old('body_de', $card?->body_de) }}</textarea>
        </div>
        <div class="hh-admin-menu-field">
            <label for="body_en">Text EN</label>
            <textarea id="body_en" name="body_en" rows="4" maxlength="1200">{{ old('body_en', $card?->body_en) }}</textarea>
        </div>
        <div class="hh-admin-menu-field">
            <label for="cta_label_de">CTA DE</label>
            <input id="cta_label_de" name="cta_label_de" value="{{ old('cta_label_de', $card?->cta_label_de) }}" maxlength="80">
        </div>
        <div class="hh-admin-menu-field">
            <label for="cta_label_en">CTA EN</label>
            <input id="cta_label_en" name="cta_label_en" value="{{ old('cta_label_en', $card?->cta_label_en) }}" maxlength="80">
        </div>
        <div class="hh-admin-menu-field">
            <label for="action_url">action_url</label>
            <input id="action_url" name="action_url" value="{{ old('action_url', $card?->action_url) }}" maxlength="255" placeholder="hntrocks://notifications">
        </div>
        <div class="hh-admin-menu-field">
            <label for="style_variant">Style</label>
            <select id="style_variant" name="style_variant">
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
            <select id="audience_type" name="audience_type">
                @foreach(['all', 'admins', 'user_ids', 'app_version_below', 'app_version_above'] as $audience)
                    <option value="{{ $audience }}" @selected(old('audience_type', $card?->audience_type ?? 'all') === $audience)>{{ $audience }}</option>
                @endforeach
            </select>
        </div>
        <div class="hh-admin-menu-field">
            <label for="audience_payload">Audience JSON</label>
            <textarea id="audience_payload" name="audience_payload" rows="3">{{ old('audience_payload', $card?->audience_payload ? json_encode($card->audience_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
        </div>
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $card?->is_active ?? false))>
            <span>aktiv</span>
        </label>
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="dismissible" value="1" @checked(old('dismissible', $card?->dismissible ?? true))>
            <span>wegklickbar</span>
        </label>
    </form>
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
                    <th>Zeitraum</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cards as $item)
                    <tr>
                        <td>{{ $item->is_active ? 'aktiv' : 'inaktiv' }}</td>
                        <td><strong>{{ $item->remote_id }}</strong><span>{{ $item->style_variant }}</span></td>
                        <td>{{ $item->title_de }}</td>
                        <td>{{ $item->priority }}</td>
                        <td>{{ $item->starts_at?->format('d.m.Y H:i') ?? 'sofort' }} - {{ $item->ends_at?->format('d.m.Y H:i') ?? 'offen' }}</td>
                        <td>
                            <a class="hh-secondary-button" href="{{ route('admin.app-remote-feed-cards.edit', $item) }}">Bearbeiten</a>
                            @if($item->is_active)
                                <form method="post" action="{{ route('admin.app-remote-feed-cards.deactivate', $item) }}" style="display:inline">
                                    @csrf
                                    <button class="hh-secondary-button" type="submit">Deaktivieren</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Noch keine Remote Feed Cards.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $cards->links() }}
</section>
@endsection
