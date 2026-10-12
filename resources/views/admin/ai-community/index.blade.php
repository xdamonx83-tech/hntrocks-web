@extends('admin.layouts.app')

@section('title', 'KI Community · HNT-ACP')
@section('admin_heading', 'KI Community')

@section('content')
<h1>KI Community-Charaktere</h1>
<p>Alle Entwürfe benötigen manuelle Freigabe und eine separate Veröffentlichung. Konten werden nur auf ausdrückliche Admin-Aktion erstellt.</p>
@if (!config('ai-community.enabled')) <p role="alert">KI Community ist deaktiviert (AI_COMMUNITY_ENABLED=false).</p> @endif
@if (!config('ai-community.key')) <p role="alert">OPENAI_API_KEY fehlt. Entwurfsgenerierung benötigt einen konfigurierten API-Schlüssel und Guthaben.</p> @endif
@if (session('status')) <p role="status">{{ session('status') }}</p> @endif
@if ($errors->any()) <div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif

<h2>Personas</h2>
@foreach($characters as $character)
<section style="padding:1rem;margin:1rem 0;border:1px solid #555">
    <h3>{{ $character->name }} · {{ strtoupper($character->language) }} · {{ $character->enabled ? 'aktiv' : 'inaktiv' }}</h3>
    <p>{{ $character->user ? 'Synthetisches Konto: @'.$character->user->username : 'Kein Konto provisioniert' }} · {{ $character->generated_count }} Entwürfe · {{ $character->published_count }} Beiträge</p>
    <form method="post" action="{{ route('admin.ai-community.update', $character) }}">@csrf @method('put')
        <label>Stimme <textarea name="persona_voice" maxlength="1000" required>{{ $character->persona_voice }}</textarea></label>
        <label>Themen <textarea name="topics" maxlength="1000" required>{{ $character->topics }}</textarea></label>
        <label>Zusätzliche Vorgaben <textarea name="prompt_settings" maxlength="1000">{{ $character->prompt_settings }}</textarea></label>
        <button type="submit">Persona speichern</button>
    </form>
    <form method="post" action="{{ route('admin.ai-community.toggle', $character) }}">@csrf <button type="submit">{{ $character->enabled ? 'Deaktivieren' : 'Aktivieren' }}</button></form>
    @unless($character->user)<form method="post" action="{{ route('admin.ai-community.provision', $character) }}">@csrf <button type="submit">Synthetisches Konto erstellen</button></form>@endunless
    <form method="post" action="{{ route('admin.ai-community.generate', $character) }}">@csrf <button type="submit" @disabled(!config('ai-community.enabled') || !config('ai-community.key') || !$character->enabled)>Entwurf generieren</button></form>
</section>
@endforeach

<h2>Entwürfe und Veröffentlichungen</h2>
@forelse($drafts as $draft)
<section style="padding:1rem;margin:1rem 0;border:1px solid #555">
    <h3>#{{ $draft->id }} · {{ $draft->character->name }} · {{ $draft->status }}</h3>
    <p>Modell {{ $draft->model }} · {{ $draft->input_tokens }} Eingabe- / {{ $draft->output_tokens }} Ausgabe-Tokens · geschätzt ${{ number_format($draft->estimated_cost_microusd / 1000000, 6) }}</p>
    @if($draft->status === 'draft')
        <form method="post" action="{{ route('admin.ai-community.drafts.update', $draft) }}">@csrf @method('put')<textarea name="body" maxlength="700" required>{{ $draft->body }}</textarea><button type="submit">Entwurf speichern</button></form>
        <form method="post" action="{{ route('admin.ai-community.drafts.review', $draft) }}">@csrf <button name="decision" value="approved">Freigeben</button><button name="decision" value="rejected">Ablehnen</button></form>
    @else <p>{{ $draft->body }}</p> @endif
    @if($draft->status === 'approved')<form method="post" action="{{ route('admin.ai-community.drafts.publish', $draft) }}">@csrf <button type="submit">Jetzt veröffentlichen</button></form>@endif
    @if($draft->published_feed_post_id)<p>Feed-Beitrag #{{ $draft->published_feed_post_id }}</p>@endif
</section>
@empty <p>Keine Entwürfe vorhanden.</p> @endforelse
@endsection
