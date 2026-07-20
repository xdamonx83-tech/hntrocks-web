@php
    $isEnglish = app()->getLocale() === 'en';
    $viewer?->loadMissing('profile');
    $viewerProfile = $viewer?->profile;
    $defaultPlatformRaw = strtolower(trim((string) ($viewerProfile?->platform ?? '')));
    $defaultPlatform = match (true) {
        str_contains($defaultPlatformRaw, 'playstation'), str_contains($defaultPlatformRaw, 'ps5'), str_contains($defaultPlatformRaw, 'ps4') => 'playstation',
        str_contains($defaultPlatformRaw, 'xbox') => 'xbox',
        default => 'pc',
    };
    $copy = $isEnglish ? [
        'title' => 'Ready Lobbies', 'eyebrow' => 'HNT.ROCKS LFG',
        'intro' => 'Find Hunters who want to start now or within the next few minutes.',
        'create' => 'Start lobby', 'all' => 'All lobbies', 'duo' => 'Duo', 'trio' => 'Trio',
        'console' => 'Console', 'pc' => 'PC', 'available' => 'Available now',
        'open_lobbies' => 'Open lobbies', 'hunters_ready' => 'Hunters ready', 'teams_full' => 'Full teams',
        'radar' => 'LIVE RADAR', 'radar_title' => 'Who matches you right now?',
        'relaxed' => 'Relaxed', 'competitive' => 'Competitive', 'now' => 'Now', 'later' => 'Later',
        'very_good' => 'Strong match', 'friend_online' => 'Mentor Hunter',
        'click_hint' => 'Select a node to inspect the lobby.',
        'list' => 'READY LOBBIES', 'next_minutes' => 'Active for the next 15 minutes',
        'refresh' => 'Refresh', 'view' => 'View', 'join' => 'Join',
        'best' => 'YOUR BEST MATCH', 'match' => 'Lobby match', 'hunters_online' => 'Hunters online',
        'your_status' => 'YOUR STATUS', 'not_ready' => 'No active lobby',
        'active_lobby' => 'You are in a Ready Lobby.',
        'status_text' => 'Start your own lobby or join a matching team from the radar.',
        'create_own' => 'Create own lobby', 'manage' => 'Manage lobby',
        'loading' => 'Loading Ready Lobbies …', 'load_error' => 'Ready Lobbies could not be loaded.',
        'no_lobbies' => 'No matching Ready Lobbies', 'no_lobbies_text' => 'Change the filter or create a new lobby.',
        'details' => 'Lobby details', 'members' => 'Hunters', 'contact' => 'Lobby contact',
        'contact_locked' => 'Contact details are shown after joining.',
        'platform' => 'Platform', 'mmr' => 'MMR stars', 'handle' => 'Platform handle',
        'join_submit' => 'Join now', 'leave' => 'Leave lobby', 'close' => 'Close lobby',
        'saving' => 'Saving …', 'joined' => 'You joined the Ready Lobby.',
        'left' => 'You left the Ready Lobby.', 'closed' => 'Ready Lobby closed.',
        'confirm_close' => 'Close this Ready Lobby for everyone?', 'confirm_leave' => 'Leave this Ready Lobby?',
        'feedback' => 'After-hunt feedback', 'feedback_open' => 'Give feedback',
        'feedback_for' => 'Feedback for', 'positive' => 'What went well?',
        'private' => 'Private notes for moderation', 'comment' => 'Optional comment',
        'submit_feedback' => 'Submit feedback', 'dismiss_feedback' => 'Skip',
        'feedback_saved' => 'Feedback saved.', 'feedback_empty' => 'No feedback is waiting.',
        'reliable' => 'Reliable', 'chill' => 'Chill', 'teamplayer' => 'Team player',
        'good_communication' => 'Good communication', 'helpful' => 'Helpful',
        'beginner_friendly' => 'Beginner friendly', 'would_play_again' => 'Would play again',
        'no_show' => 'No show', 'left_early' => 'Left early',
        'not_again' => 'Would not play again', 'uncomfortable' => 'Uncomfortable situation',
        'hunter_number' => 'Hunter', 'creator' => 'Host', 'member' => 'Member',
        'open' => 'Open', 'full' => 'Full', 'expired' => 'Expired', 'closed_status' => 'Closed',
        'voice' => 'Voice required', 'voice_optional' => 'Voice optional',
        'copy' => 'Copy', 'copied' => 'Copied', 'unknown_error' => 'Something went wrong.',
        'expires' => 'Expires in', 'full_expires' => 'Team ready · closes in', 'mentor' => 'Mentor Hunter',
    ] : [
        'title' => 'Ready Lobbys', 'eyebrow' => 'HNT.ROCKS LFG',
        'intro' => 'Finde Hunter, die jetzt oder in wenigen Minuten starten wollen.',
        'create' => 'Lobby starten', 'all' => 'Alle Lobbys', 'duo' => 'Duo', 'trio' => 'Trio',
        'console' => 'Konsole', 'pc' => 'PC', 'available' => 'Jetzt verfügbar',
        'open_lobbies' => 'Offene Lobbys', 'hunters_ready' => 'Hunter bereit', 'teams_full' => 'Volle Teams',
        'radar' => 'LIVE-RADAR', 'radar_title' => 'Wer passt gerade zu dir?',
        'relaxed' => 'Entspannt', 'competitive' => 'Competitive', 'now' => 'Sofort', 'later' => 'Später',
        'very_good' => 'Sehr passend', 'friend_online' => 'Mentor-Hunter',
        'click_hint' => 'Knoten anklicken, um die Lobby zu prüfen.',
        'list' => 'READY LOBBYS', 'next_minutes' => 'In den nächsten 15 Minuten',
        'refresh' => 'Aktualisieren', 'view' => 'Ansehen', 'join' => 'Beitreten',
        'best' => 'DEIN BESTER TREFFER', 'match' => 'Lobby Match', 'hunters_online' => 'Hunter online',
        'your_status' => 'DEIN STATUS', 'not_ready' => 'Keine aktive Lobby',
        'active_lobby' => 'Du bist in einer Ready Lobby.',
        'status_text' => 'Starte eine eigene Lobby oder tritt über den Radar einem passenden Team bei.',
        'create_own' => 'Eigene Lobby erstellen', 'manage' => 'Lobby verwalten',
        'loading' => 'Ready Lobbys werden geladen …', 'load_error' => 'Ready Lobbys konnten nicht geladen werden.',
        'no_lobbies' => 'Keine passenden Ready Lobbys', 'no_lobbies_text' => 'Ändere den Filter oder erstelle eine neue Lobby.',
        'details' => 'Lobby-Details', 'members' => 'Hunter', 'contact' => 'Lobby-Kontakt',
        'contact_locked' => 'Kontaktdaten werden erst nach dem Beitritt angezeigt.',
        'platform' => 'Plattform', 'mmr' => 'MMR-Sterne', 'handle' => 'Plattform-Name',
        'join_submit' => 'Jetzt beitreten', 'leave' => 'Lobby verlassen', 'close' => 'Lobby schließen',
        'saving' => 'Wird gespeichert …', 'joined' => 'Du bist der Ready Lobby beigetreten.',
        'left' => 'Du hast die Ready Lobby verlassen.', 'closed' => 'Ready Lobby wurde geschlossen.',
        'confirm_close' => 'Diese Ready Lobby für alle schließen?', 'confirm_leave' => 'Diese Ready Lobby verlassen?',
        'feedback' => 'Feedback nach der Jagd', 'feedback_open' => 'Feedback geben',
        'feedback_for' => 'Feedback für', 'positive' => 'Was lief gut?',
        'private' => 'Private Hinweise für die Moderation', 'comment' => 'Optionaler Kommentar',
        'submit_feedback' => 'Feedback senden', 'dismiss_feedback' => 'Überspringen',
        'feedback_saved' => 'Feedback wurde gespeichert.', 'feedback_empty' => 'Aktuell wartet kein Feedback.',
        'reliable' => 'Zuverlässig', 'chill' => 'Entspannt', 'teamplayer' => 'Teamplayer',
        'good_communication' => 'Gute Kommunikation', 'helpful' => 'Hilfsbereit',
        'beginner_friendly' => 'Anfängerfreundlich', 'would_play_again' => 'Würde wieder spielen',
        'no_show' => 'Nicht erschienen', 'left_early' => 'Früh gegangen',
        'not_again' => 'Nicht noch einmal', 'uncomfortable' => 'Unangenehme Situation',
        'hunter_number' => 'Hunter', 'creator' => 'Host', 'member' => 'Mitglied',
        'open' => 'Offen', 'full' => 'Voll', 'expired' => 'Abgelaufen', 'closed_status' => 'Geschlossen',
        'voice' => 'Voice erforderlich', 'voice_optional' => 'Voice optional',
        'copy' => 'Kopieren', 'copied' => 'Kopiert', 'unknown_error' => 'Etwas ist schiefgelaufen.',
        'expires' => 'Läuft ab in', 'full_expires' => 'Team bereit · schließt in', 'mentor' => 'Mentor-Hunter',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'de' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow">
<title>{{ $copy['title'] }} · HNT.ROCKS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/ready-lobbies/ready-lobbies.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/ready-lobbies/ready-lobbies.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="ready-lobbies">
@include('themes.hnt_preview.partials.icons')
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
<main class="app-shell ready-page-shell">
@include('themes.hnt_preview.partials.header')
<section class="ready-stage"><div class="ready-scroll" id="readyScroll">
<div data-ready-lobby-root
  data-initial-lobby="{{ $initialLobbyId }}"
  data-list-url="{{ route('ready-lobbies.data') }}"
  data-mine-url="{{ route('ready-lobbies.mine.data') }}"
  data-create-url="{{ route('ready-lobbies.create') }}"
  data-show-template="{{ route('ready-lobbies.show.data', ['lobby' => '__LOBBY__']) }}"
  data-join-template="{{ route('ready-lobbies.join', ['lobby' => '__LOBBY__']) }}"
  data-leave-template="{{ route('ready-lobbies.leave', ['lobby' => '__LOBBY__']) }}"
  data-close-template="{{ route('ready-lobbies.close', ['lobby' => '__LOBBY__']) }}"
  data-feedback-url="{{ route('ready-lobbies.feedback.requests.index') }}"
  data-feedback-submit-template="{{ route('ready-lobbies.feedback.submit', ['feedbackRequest' => '__REQUEST__']) }}"
  data-feedback-dismiss-template="{{ route('ready-lobbies.feedback.dismiss', ['feedbackRequest' => '__REQUEST__']) }}"
  data-default-platform="{{ $defaultPlatform }}">
<section class="ready-heading">
<div><span>{{ $copy['eyebrow'] }}</span><h1>{{ $copy['title'] }}</h1><p>{{ $copy['intro'] }}</p><a class="ready-create-link" href="{{ route('ready-lobbies.create') }}"><svg><use href="#i-plus"></use></svg>{{ $copy['create'] }}</a></div>
<div class="ready-heading-stats"><article><strong data-ready-hunter-count>0</strong><span>{{ $copy['hunters_ready'] }}</span></article><article><strong data-ready-lobby-count>0</strong><span>{{ $copy['open_lobbies'] }}</span></article><article><strong data-ready-full-count>0</strong><span>{{ $copy['teams_full'] }}</span></article></div>
</section>
<section aria-label="{{ $copy['title'] }}" class="ready-filter-strip">
<button class="active" data-ready-filter="all" type="button"><span>{{ $copy['all'] }}</span><strong data-filter-count="all">0</strong><small>{{ $copy['available'] }}</small></button>
<button data-ready-filter="duo" type="button"><span>{{ $copy['duo'] }}</span><strong data-filter-count="duo">0</strong><small>2 Hunter</small></button>
<button data-ready-filter="trio" type="button"><span>{{ $copy['trio'] }}</span><strong data-filter-count="trio">0</strong><small>3 Hunter</small></button>
<button data-ready-filter="console" type="button"><span>{{ $copy['console'] }}</span><strong data-filter-count="console">0</strong><small>PS5 / Xbox</small></button>
<button data-ready-filter="pc" type="button"><span>{{ $copy['pc'] }}</span><strong data-filter-count="pc">0</strong><small>PC Pool</small></button>
</section>
<section class="ready-workspace">
<div class="ready-main-column">
<article class="lobby-radar-card"><header><div><span>{{ $copy['radar'] }}</span><h2>{{ $copy['radar_title'] }}</h2></div><div class="radar-controls"><button data-radar-zoom="out" type="button">−</button><button data-radar-zoom="in" type="button">+</button><button data-refresh type="button"><svg><use href="#i-reply"></use></svg>{{ $copy['refresh'] }}</button></div></header>
<div class="lobby-radar" id="lobbyRadar"><div class="radar-axis horizontal"><span>{{ $copy['relaxed'] }}</span><span>{{ $copy['competitive'] }}</span></div><div class="radar-axis vertical"><span>{{ $copy['now'] }}</span><span>{{ $copy['later'] }}</span></div><div class="radar-grid-lines"></div><div class="radar-route route-a"></div><div class="radar-route route-b"></div><div class="radar-route route-c"></div><div data-radar-nodes></div><article class="radar-selection" data-radar-selection hidden></article><div class="ready-radar-empty" data-radar-empty><span class="ready-lobby-spinner"></span><strong>{{ $copy['loading'] }}</strong></div></div>
<footer class="radar-legend"><span><i class="yellow"></i>{{ $copy['very_good'] }}</span><span><i class="dark"></i>{{ $copy['competitive'] }}</span><span><i class="green"></i>{{ $copy['friend_online'] }}</span><small>{{ $copy['click_hint'] }}</small></footer></article>
<article class="ready-list-card"><header><div><span>{{ $copy['list'] }}</span><h2>{{ $copy['next_minutes'] }}</h2></div><button data-refresh type="button"><svg><use href="#i-reply"></use></svg>{{ $copy['refresh'] }}</button></header><div class="ready-lobby-list" data-lobby-list aria-live="polite"></div></article>
</div>
<aside class="ready-right-sidebar">
<article class="ready-match-card"><header><div><span>{{ $copy['best'] }}</span><h2>{{ $copy['match'] }}</h2></div><button data-open-best type="button"><svg><use href="#i-arrow"></use></svg></button></header><div data-best-match></div></article>
<article class="ready-hunters-card"><header><div><span>{{ $copy['available'] }}</span><h2>{{ $copy['hunters_online'] }}</h2></div><strong data-ready-hunter-side-count>0</strong></header><div class="ready-hunter-list" data-hunter-list></div></article>
<article class="ready-status-card"><header><span>{{ $copy['your_status'] }}</span><strong data-my-status-label>{{ $copy['not_ready'] }}</strong></header><p data-my-status-copy>{{ $copy['status_text'] }}</p><a class="ready-status-create" data-my-status-link href="{{ route('ready-lobbies.create') }}">{{ $copy['create_own'] }}<svg><use href="#i-arrow"></use></svg></a><button class="ready-lobby-feedback-button" type="button" data-feedback-open hidden><span>{{ $copy['feedback_open'] }}</span><strong data-feedback-count>0</strong></button></article>
</aside>
</section>
</div></div></section>
<div class="ready-lobby-modal" data-detail-modal hidden><div class="ready-lobby-modal-backdrop" data-modal-close></div><section class="ready-lobby-dialog ready-lobby-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="readyLobbyDetailTitle"><header><div><span>{{ $copy['title'] }}</span><h2 id="readyLobbyDetailTitle" data-detail-title>{{ $copy['details'] }}</h2></div><button class="ready-lobby-circle" type="button" aria-label="Close" data-modal-close><svg><use href="#i-x"></use></svg></button></header><div data-detail-content></div></section></div>
<div class="ready-lobby-modal" data-feedback-modal hidden><div class="ready-lobby-modal-backdrop" data-modal-close></div><section class="ready-lobby-dialog ready-lobby-feedback-dialog" role="dialog" aria-modal="true" aria-labelledby="readyLobbyFeedbackTitle"><header><div><span>{{ $copy['feedback'] }}</span><h2 id="readyLobbyFeedbackTitle" data-feedback-title>{{ $copy['feedback'] }}</h2></div><button class="ready-lobby-circle" type="button" aria-label="Close" data-modal-close><svg><use href="#i-x"></use></svg></button></header><div data-feedback-content></div></section></div>
<div class="ready-lobby-toast" id="readyLobbyToast" role="status" aria-live="polite"></div>
<script type="application/json" id="readyLobbyCopy">{!! json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/ready-lobbies/ready-lobbies.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/ready-lobbies/ready-lobbies.js')) ?: time() }}" defer></script>
</body></html>
