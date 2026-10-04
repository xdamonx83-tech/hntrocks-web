@extends('admin.layouts.app')

@section('title', 'Interner Admin-Chat · HNT-ACP')
@section('admin_heading', 'Admin-Chat')

@section('content')
<div class="acp-collaboration">
    <header class="acp-collab-head">
        <div>
            <span class="acp-collab-kicker">Intern / Zusammenarbeit</span>
            <h1>Admin-Chat</h1>
            <p>Vorbereitete Team-Unterhaltung für mehrere Administratoren.</p>
        </div>
        <span class="acp-collab-tag">Entwicklungsvorschau · kein Nachrichtenversand</span>
    </header>
    <section class="acp-chat-shell" aria-label="Interner Admin-Chat: Designvorschau">
        <aside class="acp-chat-conversations">
            <h2>Unterhaltungen</h2>
            <p>Aktuell sind keine Unterhaltungen angelegt. Nachrichten werden weder geladen noch gespeichert.</p>
        </aside>
        <div class="acp-chat-message-area">
            <div class="acp-chat-top">Admin-Team · Chatvorschau</div>
            <div class="acp-chat-empty">
                <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#message-question"></use></svg>
                <strong>Noch keine Nachrichten</strong>
                <p>Dieser Bereich zeigt die spätere Chat-Oberfläche. Der Nachrichtenversand wird erst mit Backend, Berechtigungen und Speicherung aktiviert.</p>
            </div>
            <div class="acp-chat-compose">
                <input type="text" placeholder="Nachrichtenfunktion noch nicht verfügbar" disabled aria-label="Nachrichtenfunktion noch nicht verfügbar">
            </div>
        </div>
    </section>
</div>
@endsection
