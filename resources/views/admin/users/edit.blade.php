@extends('admin.layouts.app')

@section('title', 'Nutzer bearbeiten · Admin · hnt.rocks')

@section('admin_heading', 'Nutzer bearbeiten')

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin · Nutzer</p>
        <h1>{{ $editedUser->name }}</h1>
        <p>{{ '@'.$editedUser->username }} · {{ $editedUser->email }}</p>
    </div>
    <div class="hh-admin-actions">
        <a class="hh-secondary-button" href="{{ route('admin.users.index') }}">Zurück</a>
        <a class="hh-secondary-button" href="{{ route('profile.public', $editedUser) }}" target="_blank" rel="noopener">Öffentliches Profil</a>
        <form method="post" action="{{ route('admin.profile-moderation.rescan', $editedUser) }}">
            @csrf
            <button class="hh-secondary-button" type="submit">Profil erneut prüfen</button>
        </form>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="hh-alert">
        <strong>Bitte Eingaben prüfen.</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($openFlags->isNotEmpty())
<section class="hh-card hh-card-compact">
    <h2>Offene Moderationshinweise ({{ $openFlags->count() }})</h2>
    @foreach($openFlags as $flag)
        <p>
            <strong>{{ $flag->score }}/100 · {{ $flag->category }} · {{ $flag->field }}</strong><br>
            {{ $flag->reason }}<br>
            <span class="hh-muted">{{ $flag->excerpt }}</span>
        </p>
    @endforeach
</section>
@endif

<form method="post" action="{{ route('admin.users.update', $editedUser) }}">
    @csrf
    @method('put')

    <section class="hh-card hh-card-compact">
        <h2>Account</h2>
        <div class="hh-admin-filter">
            <div>
                <label for="name">Anzeigename</label>
                <input id="name" name="name" value="{{ old('name', $editedUser->name) }}" required maxlength="80">
            </div>
            <div>
                <label for="username">Username</label>
                <input id="username" name="username" value="{{ old('username', $editedUser->username) }}" required maxlength="32">
            </div>
            <div>
                <label for="email">E-Mail</label>
                <input id="email" type="email" name="email" value="{{ old('email', $editedUser->email) }}" required maxlength="160">
            </div>
        </div>
        <p class="hh-muted">
            Status: {{ $editedUser->status ?? 'active' }} ·
            Rolle: {{ $editedUser->is_admin ? 'Admin' : 'Nutzer' }} ·
            Level: {{ $editedUser->level ?? 1 }} ·
            Letzter Login: {{ $editedUser->last_login_at?->format('d.m.Y H:i') ?? '—' }}
        </p>
    </section>

    <section class="hh-card hh-card-compact">
        <h2>Öffentliches Profil</h2>

        <div>
            <label for="headline">Kurzbeschreibung</label>
            <input id="headline" name="headline" value="{{ old('headline', $profile->headline) }}" maxlength="120">
        </div>

        <div style="margin-top:16px;">
            <label for="bio">Über mich / Bio</label>
            <textarea id="bio" name="bio" rows="8" maxlength="2000">{{ old('bio', $profile->bio) }}</textarea>
        </div>

        <div class="hh-admin-filter" style="margin-top:16px;">
            <div>
                <label for="platform">Plattform</label>
                <input id="platform" name="platform" value="{{ old('platform', $profile->platform) }}" maxlength="40">
            </div>
            <div>
                <label for="playstyle">Playstyle</label>
                <input id="playstyle" name="playstyle" value="{{ old('playstyle', $profile->playstyle) }}" maxlength="60">
            </div>
            <div>
                <label for="region">Region</label>
                <input id="region" name="region" value="{{ old('region', $profile->region) }}" maxlength="60">
            </div>
            <div>
                <label for="language">Sprache</label>
                <input id="language" name="language" value="{{ old('language', $profile->language) }}" maxlength="40">
            </div>
            <div>
                <label for="hunt_role">Hunt-Rolle</label>
                <input id="hunt_role" name="hunt_role" value="{{ old('hunt_role', $profile->hunt_role) }}" maxlength="60">
            </div>
            <div>
                <label for="profile_visibility">Profilsichtbarkeit</label>
                <select id="profile_visibility" name="profile_visibility">
                    @foreach(['public' => 'Öffentlich', 'registered' => 'Nur Mitglieder', 'private' => 'Privat'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('profile_visibility', $profile->profile_visibility ?: 'public') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label class="hh-checkline hh-checkline-card" style="margin-top:16px;">
            <input type="checkbox" name="is_lfg_available" value="1" @checked(old('is_lfg_available', $profile->is_lfg_available))>
            Für LFG verfügbar
        </label>
    </section>

    <section class="hh-card hh-card-compact">
        <h2>Social</h2>
        <div class="hh-admin-filter">
            <div>
                <label for="discord_name">Discord</label>
                <input id="discord_name" name="discord_name" value="{{ old('discord_name', $profile->discord_name) }}" maxlength="80">
            </div>
            <div>
                <label for="steam_url">Steam URL</label>
                <input id="steam_url" type="url" name="steam_url" value="{{ old('steam_url', $profile->steam_url) }}">
            </div>
            <div>
                <label for="twitch_url">Twitch URL</label>
                <input id="twitch_url" type="url" name="twitch_url" value="{{ old('twitch_url', $profile->twitch_url) }}">
            </div>
            <div>
                <label for="youtube_url">YouTube URL</label>
                <input id="youtube_url" type="url" name="youtube_url" value="{{ old('youtube_url', $profile->youtube_url) }}">
            </div>
        </div>
    </section>

    <section class="hh-card hh-card-compact">
        <h2>Moderationsnotiz für diese Änderung</h2>
        <div class="hh-admin-filter">
            <div>
                <label for="moderation_reason">Grund</label>
                <input id="moderation_reason" name="moderation_reason" value="{{ old('moderation_reason') }}" maxlength="120" placeholder="z. B. Spam, beleidigender Profiltext">
            </div>
            <div>
                <label for="moderation_note">Interne Notiz</label>
                <textarea id="moderation_note" name="moderation_note" rows="3" maxlength="2000">{{ old('moderation_note') }}</textarea>
            </div>
        </div>
        <button class="hh-primary-button" type="submit">Änderungen speichern</button>
    </section>
</form>

<section class="hh-card hh-card-compact">
    <h2>Schnellaktionen</h2>
    <div class="hh-admin-actions">
        <form method="post" action="{{ route('admin.users.status', $editedUser) }}">
            @csrf
            <input type="hidden" name="status" value="{{ ($editedUser->status ?? 'active') === 'suspended' ? 'active' : 'suspended' }}">
            <button class="hh-secondary-button" type="submit">{{ ($editedUser->status ?? 'active') === 'suspended' ? 'Entsperren' : 'Nutzer sperren' }}</button>
        </form>

        <form method="post" action="{{ route('admin.users.admin', $editedUser) }}">
            @csrf
            <button class="hh-secondary-button" type="submit">{{ $editedUser->is_admin ? 'Adminrechte entfernen' : 'Adminrechte vergeben' }}</button>
        </form>

        @if(filled($profile->headline))
            <form method="post" action="{{ route('admin.profile-moderation.clear-field', $editedUser) }}" onsubmit="return confirm('Kurzbeschreibung wirklich entfernen?');">
                @csrf
                <input type="hidden" name="field" value="headline">
                <input type="hidden" name="reason" value="Admin-Moderation">
                <button class="hh-secondary-button" type="submit">Headline entfernen</button>
            </form>
        @endif

        @if(filled($profile->bio))
            <form method="post" action="{{ route('admin.profile-moderation.clear-field', $editedUser) }}" onsubmit="return confirm('Bio wirklich entfernen?');">
                @csrf
                <input type="hidden" name="field" value="bio">
                <input type="hidden" name="reason" value="Admin-Moderation">
                <button class="hh-secondary-button" type="submit">Bio entfernen</button>
            </form>
        @endif
    </div>
</section>

<section class="hh-card hh-card-compact">
    <h2>Moderationsverlauf</h2>
    <div class="hh-admin-table">
        <table>
            <thead>
                <tr>
                    <th>Zeit</th>
                    <th>Aktion</th>
                    <th>Feld</th>
                    <th>Admin</th>
                    <th>Grund / Notiz</th>
                </tr>
            </thead>
            <tbody>
                @forelse($moderationEvents as $event)
                    <tr>
                        <td>{{ $event->created_at?->format('d.m.Y H:i') }}</td>
                        <td>{{ $event->action }}</td>
                        <td>{{ $event->field ?? '—' }}</td>
                        <td>{{ $event->admin?->username ? '@'.$event->admin->username : ($event->admin?->name ?? 'System') }}</td>
                        <td>{{ $event->reason ?? '' }} {{ $event->note ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Noch keine Moderationsaktionen protokolliert.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if(! empty($profile->hunter_dna))
<section class="hh-card hh-card-compact">
    <h2>Hunter DNA (nur Ansicht)</h2>
    <pre style="white-space:pre-wrap;">{{ json_encode($profile->hunter_dna, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</section>
@endif
@endsection
