@extends('themes.hnt_preview.layouts.app')

@php
    $isEnglish = app()->getLocale() === 'en';
    $copy = $isEnglish ? [
        'title' => 'Ready Lobby',
        'eyebrow' => 'PLAY NOW',
        'intro' => 'Find Hunters who are ready right now. Lobbies stay active for 15 minutes and close automatically.',
        'all' => 'All lobbies',
        'mine' => 'My lobby',
        'create' => 'Create lobby',
        'refresh' => 'Refresh',
        'filters' => 'Filters',
        'all_platforms' => 'All platforms',
        'all_modes' => 'Duo & Trio',
        'all_regions' => 'All regions',
        'all_languages' => 'All languages',
        'mentor' => 'Mentor Hunters',
        'open_slots' => 'open slots',
        'voice' => 'Voice required',
        'voice_optional' => 'Voice optional',
        'expires' => 'Expires in',
        'full_expires' => 'Team ready · closes in',
        'join' => 'Join lobby',
        'leave' => 'Leave lobby',
        'close' => 'Close lobby',
        'details' => 'View details',
        'members' => 'Hunters',
        'contact' => 'Lobby contact',
        'contact_locked' => 'Contact details are shown after joining.',
        'no_lobbies' => 'No matching Ready Lobbies',
        'no_lobbies_text' => 'Change the filters or create a new lobby.',
        'loading' => 'Loading Ready Lobbies …',
        'load_error' => 'Ready Lobbies could not be loaded.',
        'retry' => 'Try again',
        'mode' => 'Team size',
        'duo' => 'Duo',
        'trio' => 'Trio',
        'platform' => 'Platform',
        'pc' => 'PC',
        'playstation' => 'PlayStation',
        'xbox' => 'Xbox',
        'region' => 'Region',
        'language' => 'Language',
        'playstyle' => 'Playstyle',
        'mood' => 'Mood',
        'mmr' => 'MMR stars',
        'handle' => 'Platform handle',
        'note' => 'Short note',
        'lobby_code' => 'Lobby code',
        'discord' => 'Discord handle',
        'steam' => 'Steam ID',
        'psn' => 'PSN ID',
        'gamertag' => 'Xbox gamertag',
        'cancel' => 'Cancel',
        'create_submit' => 'Create Ready Lobby',
        'join_submit' => 'Join now',
        'saving' => 'Saving …',
        'created' => 'Ready Lobby created.',
        'joined' => 'You joined the Ready Lobby.',
        'left' => 'You left the Ready Lobby.',
        'closed' => 'Ready Lobby closed.',
        'confirm_close' => 'Close this Ready Lobby for everyone?',
        'confirm_leave' => 'Leave this Ready Lobby?',
        'feedback' => 'After-hunt feedback',
        'feedback_open' => 'Give feedback',
        'feedback_for' => 'Feedback for',
        'positive' => 'What went well?',
        'private' => 'Private notes for moderation',
        'comment' => 'Optional comment',
        'submit_feedback' => 'Submit feedback',
        'dismiss_feedback' => 'Skip',
        'feedback_saved' => 'Feedback saved.',
        'feedback_empty' => 'No feedback is waiting.',
        'reliable' => 'Reliable',
        'chill' => 'Chill',
        'teamplayer' => 'Team player',
        'good_communication' => 'Good communication',
        'helpful' => 'Helpful',
        'beginner_friendly' => 'Beginner friendly',
        'would_play_again' => 'Would play again',
        'no_show' => 'No show',
        'left_early' => 'Left early',
        'not_again' => 'Would not play again',
        'uncomfortable' => 'Uncomfortable situation',
        'hunter_number' => 'Hunter',
        'creator' => 'Host',
        'member' => 'Member',
        'open' => 'Open',
        'full' => 'Full',
        'expired' => 'Expired',
        'closed_status' => 'Closed',
        'copy' => 'Copy',
        'copied' => 'Copied',
        'unknown_error' => 'Something went wrong.',
    ] : [
        'title' => 'Ready Lobby',
        'eyebrow' => 'JETZT SPIELEN',
        'intro' => 'Finde Hunter, die gerade sofort bereit sind. Lobbies bleiben 15 Minuten aktiv und schließen automatisch.',
        'all' => 'Alle Lobbies',
        'mine' => 'Meine Lobby',
        'create' => 'Lobby erstellen',
        'refresh' => 'Aktualisieren',
        'filters' => 'Filter',
        'all_platforms' => 'Alle Plattformen',
        'all_modes' => 'Duo & Trio',
        'all_regions' => 'Alle Regionen',
        'all_languages' => 'Alle Sprachen',
        'mentor' => 'Mentor-Hunter',
        'open_slots' => 'Plätze frei',
        'voice' => 'Voice erforderlich',
        'voice_optional' => 'Voice optional',
        'expires' => 'Läuft ab in',
        'full_expires' => 'Team bereit · schließt in',
        'join' => 'Lobby beitreten',
        'leave' => 'Lobby verlassen',
        'close' => 'Lobby schließen',
        'details' => 'Details ansehen',
        'members' => 'Hunter',
        'contact' => 'Lobby-Kontakt',
        'contact_locked' => 'Kontaktdaten werden erst nach dem Beitritt angezeigt.',
        'no_lobbies' => 'Keine passenden Ready Lobbies',
        'no_lobbies_text' => 'Ändere die Filter oder erstelle eine neue Lobby.',
        'loading' => 'Ready Lobbies werden geladen …',
        'load_error' => 'Ready Lobbies konnten nicht geladen werden.',
        'retry' => 'Erneut versuchen',
        'mode' => 'Teamgröße',
        'duo' => 'Duo',
        'trio' => 'Trio',
        'platform' => 'Plattform',
        'pc' => 'PC',
        'playstation' => 'PlayStation',
        'xbox' => 'Xbox',
        'region' => 'Region',
        'language' => 'Sprache',
        'playstyle' => 'Spielstil',
        'mood' => 'Stimmung',
        'mmr' => 'MMR-Sterne',
        'handle' => 'Plattform-Name',
        'note' => 'Kurze Notiz',
        'lobby_code' => 'Lobby-Code',
        'discord' => 'Discord-Name',
        'steam' => 'Steam-ID',
        'psn' => 'PSN-ID',
        'gamertag' => 'Xbox-Gamertag',
        'cancel' => 'Abbrechen',
        'create_submit' => 'Ready Lobby erstellen',
        'join_submit' => 'Jetzt beitreten',
        'saving' => 'Wird gespeichert …',
        'created' => 'Ready Lobby wurde erstellt.',
        'joined' => 'Du bist der Ready Lobby beigetreten.',
        'left' => 'Du hast die Ready Lobby verlassen.',
        'closed' => 'Ready Lobby wurde geschlossen.',
        'confirm_close' => 'Diese Ready Lobby für alle schließen?',
        'confirm_leave' => 'Diese Ready Lobby verlassen?',
        'feedback' => 'Feedback nach der Jagd',
        'feedback_open' => 'Feedback geben',
        'feedback_for' => 'Feedback für',
        'positive' => 'Was lief gut?',
        'private' => 'Private Hinweise für die Moderation',
        'comment' => 'Optionaler Kommentar',
        'submit_feedback' => 'Feedback senden',
        'dismiss_feedback' => 'Überspringen',
        'feedback_saved' => 'Feedback wurde gespeichert.',
        'feedback_empty' => 'Aktuell wartet kein Feedback.',
        'reliable' => 'Zuverlässig',
        'chill' => 'Entspannt',
        'teamplayer' => 'Teamplayer',
        'good_communication' => 'Gute Kommunikation',
        'helpful' => 'Hilfsbereit',
        'beginner_friendly' => 'Anfängerfreundlich',
        'would_play_again' => 'Würde wieder spielen',
        'no_show' => 'Nicht erschienen',
        'left_early' => 'Früh gegangen',
        'not_again' => 'Nicht noch einmal',
        'uncomfortable' => 'Unangenehme Situation',
        'hunter_number' => 'Hunter',
        'creator' => 'Host',
        'member' => 'Mitglied',
        'open' => 'Offen',
        'full' => 'Voll',
        'expired' => 'Abgelaufen',
        'closed_status' => 'Geschlossen',
        'copy' => 'Kopieren',
        'copied' => 'Kopiert',
        'unknown_error' => 'Etwas ist schiefgelaufen.',
    ];

    $viewer?->loadMissing('profile');
    $viewerProfile = $viewer?->profile;
    $defaultPlatform = strtolower(trim((string) ($viewerProfile?->platform ?? '')));
    $defaultPlatform = match (true) {
        str_contains($defaultPlatform, 'playstation'), str_contains($defaultPlatform, 'ps5'), str_contains($defaultPlatform, 'ps4') => 'playstation',
        str_contains($defaultPlatform, 'xbox') => 'xbox',
        default => 'pc',
    };
@endphp

@section('title', $copy['title'])
@section('main_class', 'ready-lobby-main')

@push('head')
<link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/ready-lobbies/ready-lobbies.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/ready-lobbies/ready-lobbies.css')) ?: time() }}">
@endpush

@section('content')
<div
    class="ready-lobby-shell"
    data-ready-lobby-root
    data-initial-lobby="{{ $initialLobbyId }}"
    data-list-url="{{ route('ready-lobbies.data') }}"
    data-mine-url="{{ route('ready-lobbies.mine.data') }}"
    data-store-url="{{ route('ready-lobbies.store') }}"
    data-page-template="{{ route('ready-lobbies.show', ['lobby' => '__LOBBY__']) }}"
    data-show-template="{{ route('ready-lobbies.show.data', ['lobby' => '__LOBBY__']) }}"
    data-join-template="{{ route('ready-lobbies.join', ['lobby' => '__LOBBY__']) }}"
    data-leave-template="{{ route('ready-lobbies.leave', ['lobby' => '__LOBBY__']) }}"
    data-close-template="{{ route('ready-lobbies.close', ['lobby' => '__LOBBY__']) }}"
    data-feedback-url="{{ route('ready-lobbies.feedback.requests.index') }}"
    data-feedback-submit-template="{{ route('ready-lobbies.feedback.submit', ['feedbackRequest' => '__REQUEST__']) }}"
    data-feedback-dismiss-template="{{ route('ready-lobbies.feedback.dismiss', ['feedbackRequest' => '__REQUEST__']) }}"
    data-default-platform="{{ $defaultPlatform }}"
    data-default-region="{{ $viewerProfile?->region }}"
    data-default-language="{{ $viewerProfile?->language ?: app()->getLocale() }}"
>
    <section class="ready-lobby-hero">
        <div>
            <span class="ready-lobby-eyebrow">{{ $copy['eyebrow'] }}</span>
            <h1>{{ $copy['title'] }}</h1>
            <p>{{ $copy['intro'] }}</p>
        </div>
        <div class="ready-lobby-hero-actions">
            <button class="ready-lobby-feedback-button" type="button" data-feedback-open hidden>
                <i class="ph ph-chat-circle-dots" aria-hidden="true"></i>
                <span>{{ $copy['feedback_open'] }}</span>
                <strong data-feedback-count>0</strong>
            </button>
            <button class="ready-lobby-primary" type="button" data-create-open>
                <i class="ph ph-plus" aria-hidden="true"></i>
                {{ $copy['create'] }}
            </button>
        </div>
    </section>

    <section class="ready-lobby-toolbar" aria-label="{{ $copy['filters'] }}">
        <div class="ready-lobby-tabs" role="tablist">
            <button class="active" type="button" role="tab" aria-selected="true" data-lobby-scope="all">
                {{ $copy['all'] }} <span data-all-count>0</span>
            </button>
            <button type="button" role="tab" aria-selected="false" data-lobby-scope="mine">
                {{ $copy['mine'] }} <span data-mine-count>0</span>
            </button>
        </div>

        <div class="ready-lobby-filter-grid">
            <label>
                <span>{{ $copy['platform'] }}</span>
                <select data-filter="platform">
                    <option value="">{{ $copy['all_platforms'] }}</option>
                    <option value="pc">PC</option>
                    <option value="playstation">PlayStation</option>
                    <option value="xbox">Xbox</option>
                </select>
            </label>
            <label>
                <span>{{ $copy['mode'] }}</span>
                <select data-filter="mode">
                    <option value="">{{ $copy['all_modes'] }}</option>
                    <option value="duo">{{ $copy['duo'] }}</option>
                    <option value="trio">{{ $copy['trio'] }}</option>
                </select>
            </label>
            <label>
                <span>{{ $copy['region'] }}</span>
                <input type="text" maxlength="60" placeholder="{{ $copy['all_regions'] }}" data-filter="region">
            </label>
            <label>
                <span>{{ $copy['language'] }}</span>
                <input type="text" maxlength="40" placeholder="{{ $copy['all_languages'] }}" data-filter="language">
            </label>
            <label class="ready-lobby-check-filter">
                <input type="checkbox" value="1" data-filter="mentor_hunter">
                <span>{{ $copy['mentor'] }}</span>
            </label>
            <button class="ready-lobby-refresh" type="button" data-refresh>
                <i class="ph ph-arrows-clockwise" aria-hidden="true"></i>
                {{ $copy['refresh'] }}
            </button>
        </div>
    </section>

    <section class="ready-lobby-my-strip" data-my-strip hidden></section>

    <section class="ready-lobby-list" data-lobby-list aria-live="polite">
        <div class="ready-lobby-state">
            <span class="ready-lobby-spinner" aria-hidden="true"></span>
            <strong>{{ $copy['loading'] }}</strong>
        </div>
    </section>
</div>

<div class="ready-lobby-modal" data-create-modal hidden>
    <div class="ready-lobby-modal-backdrop" data-modal-close></div>
    <section class="ready-lobby-dialog ready-lobby-create-dialog" role="dialog" aria-modal="true" aria-labelledby="readyLobbyCreateTitle">
        <header>
            <div>
                <span>{{ $copy['eyebrow'] }}</span>
                <h2 id="readyLobbyCreateTitle">{{ $copy['create'] }}</h2>
            </div>
            <button class="ready-lobby-circle" type="button" aria-label="{{ $copy['cancel'] }}" data-modal-close><i class="ph ph-x"></i></button>
        </header>
        <form data-create-form>
            <div class="ready-lobby-form-grid">
                <label><span>{{ $copy['mode'] }}</span><select name="mode" required><option value="duo">{{ $copy['duo'] }}</option><option value="trio" selected>{{ $copy['trio'] }}</option></select></label>
                <label><span>{{ $copy['platform'] }}</span><select name="platform" required><option value="pc">PC</option><option value="playstation">PlayStation</option><option value="xbox">Xbox</option></select></label>
                <label><span>{{ $copy['region'] }}</span><input name="region" maxlength="60" value="{{ $viewerProfile?->region }}" placeholder="EU"></label>
                <label><span>{{ $copy['language'] }}</span><input name="language" maxlength="40" value="{{ $viewerProfile?->language ?: app()->getLocale() }}" placeholder="DE"></label>
                <label><span>{{ $copy['playstyle'] }}</span><input name="playstyle" maxlength="60" placeholder="Chill / PvP / Mixed"></label>
                <label><span>{{ $copy['mood'] }}</span><select name="mood"><option value="">—</option><option value="chill">Chill</option><option value="serious">Serious</option><option value="pvp">PvP</option><option value="bossrush">Bossrush</option><option value="meme">Meme</option><option value="teaching">Teaching</option></select></label>
                <label><span>{{ $copy['mmr'] }}</span><select name="mmr_stars"><option value="">—</option>@for($star = 1; $star <= 6; $star++)<option value="{{ $star }}">{{ $star }} ★</option>@endfor</select></label>
                <label><span>{{ $copy['handle'] }}</span><input name="platform_handle" maxlength="100"></label>
                <label><span>{{ $copy['lobby_code'] }}</span><input name="lobby_code" maxlength="64"></label>
                <label><span>{{ $copy['discord'] }}</span><input name="discord_handle" maxlength="100"></label>
                <label data-platform-contact="pc"><span>{{ $copy['steam'] }}</span><input name="steam_id" maxlength="100"></label>
                <label data-platform-contact="playstation"><span>{{ $copy['psn'] }}</span><input name="psn_id" maxlength="100"></label>
                <label data-platform-contact="xbox"><span>{{ $copy['gamertag'] }}</span><input name="xbox_gamertag" maxlength="100"></label>
                <label class="ready-lobby-form-wide"><span>{{ $copy['note'] }}</span><textarea name="note" maxlength="500" rows="4"></textarea></label>
                <label class="ready-lobby-switch ready-lobby-form-wide"><input type="checkbox" name="voice_required" value="1"><span>{{ $copy['voice'] }}</span></label>
            </div>
            <p class="ready-lobby-form-error" data-create-error hidden></p>
            <footer>
                <button class="ready-lobby-secondary" type="button" data-modal-close>{{ $copy['cancel'] }}</button>
                <button class="ready-lobby-primary" type="submit">{{ $copy['create_submit'] }}</button>
            </footer>
        </form>
    </section>
</div>

<div class="ready-lobby-modal" data-detail-modal hidden>
    <div class="ready-lobby-modal-backdrop" data-modal-close></div>
    <section class="ready-lobby-dialog ready-lobby-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="readyLobbyDetailTitle">
        <header>
            <div><span>{{ $copy['title'] }}</span><h2 id="readyLobbyDetailTitle" data-detail-title>{{ $copy['details'] }}</h2></div>
            <button class="ready-lobby-circle" type="button" aria-label="{{ $copy['cancel'] }}" data-modal-close><i class="ph ph-x"></i></button>
        </header>
        <div data-detail-content></div>
    </section>
</div>

<div class="ready-lobby-modal" data-feedback-modal hidden>
    <div class="ready-lobby-modal-backdrop" data-modal-close></div>
    <section class="ready-lobby-dialog ready-lobby-feedback-dialog" role="dialog" aria-modal="true" aria-labelledby="readyLobbyFeedbackTitle">
        <header>
            <div><span>{{ $copy['feedback'] }}</span><h2 id="readyLobbyFeedbackTitle" data-feedback-title>{{ $copy['feedback'] }}</h2></div>
            <button class="ready-lobby-circle" type="button" aria-label="{{ $copy['cancel'] }}" data-modal-close><i class="ph ph-x"></i></button>
        </header>
        <div data-feedback-content></div>
    </section>
</div>

<div class="ready-lobby-toast" id="readyLobbyToast" role="status" aria-live="polite"></div>
<script type="application/json" id="readyLobbyCopy">{!! json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
<script src="{{ asset('assets/themes/hnt_preview/ready-lobbies/ready-lobbies.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/ready-lobbies/ready-lobbies.js')) ?: time() }}" defer></script>
@endpush
