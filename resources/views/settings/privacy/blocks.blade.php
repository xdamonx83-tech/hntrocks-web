@extends('layouts.app')

@section('title', 'Blockierte Nutzer')

@section('content')
@if (session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<div class="hh-page-header">
    <div>
        <p class="hh-kicker">Datenschutz</p>
        <h1>Blockierte Nutzer</h1>
        <p>Blockierte Nutzer sind für spätere Nachrichten, Einladungen und Interaktionen vorbereitet.</p>
    </div>
    <a class="hh-secondary-button" href="{{ route('settings.privacy.edit') }}">Zurück zum Datenschutz</a>
</div>

<section class="hh-card hh-card-wide">
    <h2>Nutzer blockieren</h2>
    <form class="hh-form" method="POST" action="{{ route('settings.privacy.blocks.store') }}">
        @csrf
        <div class="hh-form-grid">
            <div>
                <label for="username">Benutzername</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" placeholder="z. B. huntername">
                @error('username') <p class="hh-form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="reason">Notiz optional</label>
                <input id="reason" type="text" name="reason" value="{{ old('reason') }}" maxlength="120">
                @error('reason') <p class="hh-form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <button class="hh-primary-button" type="submit">Blockieren</button>
    </form>
</section>

<section class="hh-card hh-card-wide hh-settings-section">
    <h2>Aktuelle Blockierungen</h2>
    @forelse ($blocks as $block)
        <div class="hh-settings-list-row">
            <div>
                <strong>{{ $block->blockedUser?->name ?? 'Gelöschter Nutzer' }}</strong>
                <span>&#64;{{ $block->blockedUser?->username ?? 'unbekannt' }}</span>
                @if ($block->reason)
                    <small>{{ $block->reason }}</small>
                @endif
            </div>
            <form method="POST" action="{{ route('settings.privacy.blocks.destroy', $block) }}">
                @csrf
                @method('DELETE')
                <button class="hh-secondary-button" type="submit">Aufheben</button>
            </form>
        </div>
    @empty
        <p class="hh-muted">Du hast aktuell keine Nutzer blockiert.</p>
    @endforelse

    {{ $blocks->links() }}
</section>
@endsection
