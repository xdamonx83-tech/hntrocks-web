@extends('admin.layouts.app')

@section('title', 'App Push · Admin')
@section('admin_heading', 'App Push an User')

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin · Push</p>
        <h1>Push an einzelnen User</h1>
        <p>Kein Bulk-Push. Versand nur nach Zielauswahl, erlaubtem action_url und Bestätigung.</p>
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
            <h2>User suchen</h2>
            <p class="hh-muted">Suche nach Name, Username oder E-Mail.</p>
        </div>
    </div>
    <form class="hh-admin-menu-create-form" method="get" action="{{ route('admin.app-push.index') }}">
        <div class="hh-admin-menu-field">
            <label for="q">Suchbegriff</label>
            <input id="q" name="q" value="{{ $query }}" placeholder="christian@example.test">
        </div>
        <button class="hh-primary-button" type="submit">Suchen</button>
    </form>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <h2>Push senden</h2>
            <p class="hh-muted">action_url optional: hntrocks://notifications, hntrocks://messages, hntrocks://feed, hntrocks://moments, hntrocks://lfg, hntrocks://shop, hntrocks://profile.</p>
        </div>
        <button class="hh-primary-button" type="submit" form="app-push-form">Senden</button>
    </div>
    <form id="app-push-form" class="hh-admin-menu-create-form" method="post" action="{{ route('admin.app-push.send') }}">
        @csrf
        <div class="hh-admin-menu-field">
            <label for="target_user_id">Ziel-User</label>
            <select id="target_user_id" name="target_user_id" required>
                <option value="">Bitte auswählen</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(old('target_user_id') == $user->id)>
                        {{ $user->name }} / @{{ $user->username }} / {{ $user->email }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="hh-admin-menu-field">
            <label for="title">Titel</label>
            <input id="title" name="title" value="{{ old('title') }}" maxlength="120" required>
        </div>
        <div class="hh-admin-menu-field">
            <label for="body">Text</label>
            <textarea id="body" name="body" rows="4" maxlength="800" required>{{ old('body') }}</textarea>
        </div>
        <div class="hh-admin-menu-field">
            <label for="action_url">action_url</label>
            <input id="action_url" name="action_url" value="{{ old('action_url', 'hntrocks://notifications') }}" maxlength="255">
        </div>
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="confirm_send" value="1" required>
            <span>Versand an genau diesen User bestätigen</span>
        </label>
    </form>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <h2>Letzte Push Logs</h2>
            <p class="hh-muted">Audit für Admin-Pushs und blockierte FCM-Versuche.</p>
        </div>
    </div>
    <div class="hh-admin-table">
        <table>
            <thead>
                <tr>
                    <th>Zeit</th>
                    <th>Ziel</th>
                    <th>Titel</th>
                    <th>Ergebnis</th>
                    <th>action_url</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                        <td>{{ $log->targetUser?->name }}<span>{{ $log->targetUser?->email }}</span></td>
                        <td>{{ $log->title }}</td>
                        <td>{{ $log->sent_count }} gesendet / {{ $log->failed_count }} fehlgeschlagen</td>
                        <td>{{ $log->action_url ?? 'none' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Noch keine Push Logs.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
