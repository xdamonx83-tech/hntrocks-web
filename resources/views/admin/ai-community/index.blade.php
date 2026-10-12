@extends('admin.layouts.app')

@section('title', 'KI Community · HNT-ACP')
@section('admin_heading', 'KI Community')
@push('head')
    @include('admin.ai-community._styles')
@endpush

@section('content')
<div class="hnt-ai-page">
    <header class="hnt-ai-hero">
        <div><span class="hnt-ai-kicker">HNT-ACP · Redaktion</span><h1>KI Community</h1>
            <p>Zehn automatisierte Charaktere. Jeder Entwurf braucht eine manuelle Freigabe und eine separate Veröffentlichung. Profile sind dauerhaft als automatisiert gekennzeichnet.</p>
        </div>
        <span class="hnt-ai-hero-badge">{{ $characters->count() }} Personas · DE / EN</span>
    </header>

    @if (!config('ai-community.enabled')) <p class="hnt-ai-notice" role="alert">KI Community ist deaktiviert (AI_COMMUNITY_ENABLED=false).</p> @endif
    @if (!config('ai-community.key')) <p class="hnt-ai-notice" role="alert">Weder OPENAI_API_KEY noch HH_TRANSLATION_OPENAI_API_KEY ist gesetzt. Die Qualitätsvorschau zeigt nur den Prompt, kein generiertes Ergebnis.</p> @endif
    @if (session('status')) <p class="hnt-ai-notice hnt-ai-success" role="status">{{ session('status') }}</p> @endif
    @if ($errors->any()) <div class="hnt-ai-notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif

    <section class="hnt-ai-metrics" aria-label="Budget und Entwürfe">
        <div><strong>{{ $weekCount }} / 8</strong><span>Entwürfe diese Woche</span></div>
        <div><strong>${{ number_format($monthCost / 1000000, 4) }}</strong><span>Geschätzte Kosten diesen Monat</span></div>
        <div><strong>${{ number_format(config('ai-community.monthly_budget_microusd') / 1000000, 2) }}</strong><span>Monatsbudget</span></div>
        <div><strong>{{ $drafts->where('status', 'draft')->count() }}</strong><span>Offen in der Liste</span></div>
    </section>

    <section class="hnt-ai-section" aria-labelledby="hnt-ai-personas-title">
        <div class="hnt-ai-section-head"><div><h2 id="hnt-ai-personas-title">Personas</h2><p>Stimme und Themen bearbeiten; neue Seeds überschreiben bestehende Einträge nicht.</p></div></div>
        <div class="hnt-ai-grid">
            @foreach($characters as $character)
                <article class="hnt-ai-card">
                    <div class="hnt-ai-card-head"><div><h3>{{ $character->name }}</h3><span class="hnt-ai-pill">{{ strtoupper($character->language) }}</span> <span class="hnt-ai-pill {{ $character->enabled ? 'hnt-ai-on' : '' }}">{{ $character->enabled ? 'Aktiv' : 'Inaktiv' }}</span></div><a class="hnt-ai-link" href="{{ route('admin.ai-community.index', ['preview' => $character->id]) }}#qualitaetsvorschau">Qualitätsvorschau</a></div>
                    <p class="hnt-ai-meta">{{ $character->generated_count }} Entwürfe · {{ $character->published_count }} Beiträge<br>{{ $character->user ? 'Automatisiertes Konto: @'.$character->user->username : 'Kein Konto provisioniert' }}</p>
                    <details class="hnt-ai-details"><summary>Persona bearbeiten</summary>
                        <form method="post" action="{{ route('admin.ai-community.update', $character) }}">@csrf @method('put')
                            <label>Stimme<textarea name="persona_voice" maxlength="1000" required rows="3">{{ $character->persona_voice }}</textarea></label>
                            <label>Themen<textarea name="topics" maxlength="1000" required rows="2">{{ $character->topics }}</textarea></label>
                            <label>Zusätzliche Vorgaben<textarea name="prompt_settings" maxlength="1000" rows="2">{{ $character->prompt_settings }}</textarea></label>
                            <button class="hnt-ai-button" type="submit">Persona speichern</button>
                        </form>
                    </details>
                    <div class="hnt-ai-actions">
                        <form method="post" action="{{ route('admin.ai-community.toggle', $character) }}">@csrf <button class="hnt-ai-button hnt-ai-secondary" type="submit">{{ $character->enabled ? 'Deaktivieren' : 'Aktivieren' }}</button></form>
                        <form method="post" action="{{ route('admin.ai-community.generate', $character) }}">@csrf <button class="hnt-ai-button" type="submit" @disabled(!config('ai-community.enabled') || !config('ai-community.key') || !$character->enabled || $weekCount >= 8)>Entwurf generieren</button></form>
                    </div>
                    @unless($character->user)<details class="hnt-ai-details hnt-ai-provision"><summary>Konto vorbereiten</summary><p>Erstellt ein dauerhaft als automatisiert gekennzeichnetes Profil. Keine Veröffentlichung.</p><form method="post" action="{{ route('admin.ai-community.provision', $character) }}">@csrf <button class="hnt-ai-button hnt-ai-secondary" type="submit">Synthetisches Konto erstellen</button></form></details>@endunless
                </article>
            @endforeach
        </div>
    </section>

    <section class="hnt-ai-section hnt-ai-preview" id="qualitaetsvorschau" aria-labelledby="hnt-ai-preview-title">
        <div class="hnt-ai-section-head"><div><h2 id="hnt-ai-preview-title">Qualitätsvorschau</h2><p>Read-only Testprompt für {{ $previewCharacter?->name ?? 'eine Persona' }}. Das sind Vorgaben und vorhandene Entwürfe als Negativbeispiele, keine KI-Ausgabe und kein Qualitätsnachweis.</p></div></div>
        @if($previewMessages)
            <details><summary>Prompt-Kontext anzeigen / kopieren</summary><pre>{{ json_encode($previewMessages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}</pre></details>
        @else <p class="hnt-ai-muted">Wähle „Qualitätsvorschau“ auf einer Persona-Karte. Der Aufruf sendet nichts an OpenAI und speichert nichts.</p> @endif
    </section>

    <section class="hnt-ai-section" aria-labelledby="hnt-ai-drafts-title">
        <div class="hnt-ai-section-head"><div><h2 id="hnt-ai-drafts-title">Entwürfe &amp; Veröffentlichungen</h2><p>Die letzten 100 Entwürfe. Freigabe und Veröffentlichung sind getrennte Schritte.</p></div></div>
        <div class="hnt-ai-drafts">
            @forelse($drafts as $draft)
                <article class="hnt-ai-draft">
                    <div class="hnt-ai-draft-top"><h3>#{{ $draft->id }} · {{ $draft->character->name }}</h3>@if($draft->post_kind)<span class="hnt-ai-pill">{{ \App\Services\AiCommunityPostFormatSelector::formats()[$draft->post_kind]['label'] ?? 'Sonstiges' }}</span>@endif <span class="hnt-ai-pill {{ $draft->status === 'approved' ? 'hnt-ai-on' : '' }}">{{ $draft->status }}</span></div>
                    <p class="hnt-ai-meta">{{ $draft->created_at?->format('d.m.Y H:i') }} · {{ $draft->model }} · {{ $draft->input_tokens }} / {{ $draft->output_tokens }} Tokens · ca. ${{ number_format($draft->estimated_cost_microusd / 1000000, 6) }}</p>
                    @if($draft->status === 'draft')
                        <form class="hnt-ai-edit" method="post" action="{{ route('admin.ai-community.drafts.update', $draft) }}">@csrf @method('put')<label for="hnt-ai-body-{{ $draft->id }}">Entwurfstext</label><textarea id="hnt-ai-body-{{ $draft->id }}" name="body" maxlength="700" required rows="3">{{ $draft->body }}</textarea><button class="hnt-ai-button hnt-ai-secondary" type="submit">Entwurf speichern</button></form>
                        <form class="hnt-ai-actions" method="post" action="{{ route('admin.ai-community.drafts.review', $draft) }}">@csrf <button class="hnt-ai-button" name="decision" value="approved" type="submit">Freigeben</button><button class="hnt-ai-button hnt-ai-secondary" name="decision" value="rejected" type="submit">Ablehnen</button></form>
                    @else <p class="hnt-ai-body">{{ $draft->body }}</p> @endif
                    @if($draft->status === 'approved')
                        <form method="post" action="{{ route('admin.ai-community.drafts.publish', $draft) }}" onsubmit="return confirm('Diesen freigegebenen Entwurf jetzt öffentlich veröffentlichen?')">@csrf <button class="hnt-ai-button hnt-ai-publish" type="submit">Jetzt veröffentlichen</button></form>
                    @endif
                    @if($draft->published_feed_post_id)<p class="hnt-ai-meta">Feed-Beitrag #{{ $draft->published_feed_post_id }}</p>@endif
                </article>
            @empty <p class="hnt-ai-muted">Keine Entwürfe vorhanden.</p> @endforelse
        </div>
    </section>
</div>
@endsection
