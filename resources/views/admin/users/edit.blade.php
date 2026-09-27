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

    @php
        $hunterDna = old('hunter_dna', $profile->hunter_dna ?? []);
        $hunterGoals = (array) ($hunterDna['goals'] ?? []);
    @endphp
    <section class="hh-card hh-card-compact">
        <h2>Hunter DNA</h2>
        <input type="hidden" name="hunter_dna_present" value="1">
        <div class="hh-admin-filter">
            <div>
                <label for="hunter_voice">Voice</label>
                <select id="hunter_voice" name="hunter_dna[voice]">
                    <option value="">—</option>
                    @foreach(['yes' => 'Ja', 'no' => 'Nein', 'optional' => 'Optional'] as $value => $label)
                        <option value="{{ $value }}" @selected(($hunterDna['voice'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="hunter_mode">Bevorzugter Modus</label>
                <select id="hunter_mode" name="hunter_dna[preferred_mode]">
                    <option value="">—</option>
                    @foreach(['solo' => 'Solo', 'duo' => 'Duo', 'trio' => 'Trio', 'flexible' => 'Flexibel'] as $value => $label)
                        <option value="{{ $value }}" @selected(($hunterDna['preferred_mode'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="hunter_experience">Erfahrung</label>
                <select id="hunter_experience" name="hunter_dna[experience]">
                    <option value="">—</option>
                    @foreach(['new' => 'Neu', 'casual' => 'Casual', 'experienced' => 'Erfahren', 'veteran' => 'Veteran'] as $value => $label)
                        <option value="{{ $value }}" @selected(($hunterDna['experience'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="hunter_temper">Spielstil / Temperament</label>
                <select id="hunter_temper" name="hunter_dna[temper]">
                    <option value="">—</option>
                    @foreach(['chill' => 'Chill', 'focused' => 'Fokussiert', 'tryhard' => 'Tryhard', 'chaotic' => 'Chaotisch'] as $value => $label)
                        <option value="{{ $value }}" @selected(($hunterDna['temper'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <p style="margin-top:16px;"><strong>Ziele</strong></p>
        <div class="hh-admin-actions">
            @foreach(['pvp' => 'PvP', 'bounty' => 'Bounty', 'boss' => 'Boss', 'extract' => 'Extract', 'events' => 'Events', 'quests' => 'Quests', 'teach' => 'Beibringen', 'learn' => 'Lernen', 'memes' => 'Memes'] as $value => $label)
                <label class="hh-checkline hh-checkline-card">
                    <input type="checkbox" name="hunter_dna[goals][]" value="{{ $value }}" @checked(in_array($value, $hunterGoals, true))>
                    {{ $label }}
                </label>
            @endforeach
        </div>

        <label class="hh-checkline hh-checkline-card" style="margin-top:16px;">
            <input type="checkbox" name="hunter_dna[mentor]" value="1" @checked((bool) ($hunterDna['mentor'] ?? false))>
            Mentor
        </label>
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

@if($hiddenProfileFields->isNotEmpty())
<section class="hh-card hh-card-compact">
    <h2>Ausgeblendete Profilinhalte</h2>
    @foreach($hiddenProfileFields as $field => $event)
        <div style="margin-bottom:16px;">
            <strong>{{ $field === 'bio' ? 'Bio' : 'Headline' }}</strong>
            <p class="hh-muted">{{ $event->old_value }}</p>
            <form method="post" action="{{ route('admin.profile-moderation.restore-field', $editedUser) }}">
                @csrf
                <input type="hidden" name="field" value="{{ $field }}">
                <button class="hh-secondary-button" type="submit">Wiederherstellen</button>
            </form>
        </div>
    @endforeach
</section>
@endif

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
            <form method="post" action="{{ route('admin.profile-moderation.hide-field', $editedUser) }}" onsubmit="return confirm('Kurzbeschreibung ausblenden? Sie kann später wiederhergestellt werden.');">
                @csrf
                <input type="hidden" name="field" value="headline">
                <input type="hidden" name="reason" value="Admin-Moderation">
                <button class="hh-secondary-button" type="submit">Headline ausblenden</button>
            </form>
            <form method="post" action="{{ route('admin.profile-moderation.clear-field', $editedUser) }}" onsubmit="return confirm('Kurzbeschreibung endgültig leeren?');">
                @csrf
                <input type="hidden" name="field" value="headline">
                <input type="hidden" name="reason" value="Admin-Moderation">
                <button class="hh-secondary-button" type="submit">Headline leeren</button>
            </form>
        @endif

        @if(filled($profile->bio))
            <form method="post" action="{{ route('admin.profile-moderation.hide-field', $editedUser) }}" onsubmit="return confirm('Bio ausblenden? Sie kann später wiederhergestellt werden.');">
                @csrf
                <input type="hidden" name="field" value="bio">
                <input type="hidden" name="reason" value="Admin-Moderation">
                <button class="hh-secondary-button" type="submit">Bio ausblenden</button>
            </form>
            <form method="post" action="{{ route('admin.profile-moderation.clear-field', $editedUser) }}" onsubmit="return confirm('Bio endgültig leeren?');">
                @csrf
                <input type="hidden" name="field" value="bio">
                <input type="hidden" name="reason" value="Admin-Moderation">
                <button class="hh-secondary-button" type="submit">Bio leeren</button>
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

@endsection
