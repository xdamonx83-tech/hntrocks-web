@php
    $isEnglish = app()->getLocale() === 'en';
    $viewer?->loadMissing('profile');
    $profile = $viewer?->profile;
    $avatar = $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $defaultPlatformRaw = strtolower(trim((string) ($profile?->platform ?? '')));
    $defaultPlatform = match (true) {
        str_contains($defaultPlatformRaw, 'playstation'), str_contains($defaultPlatformRaw, 'ps5'), str_contains($defaultPlatformRaw, 'ps4') => 'playstation',
        str_contains($defaultPlatformRaw, 'xbox') => 'xbox',
        default => 'pc',
    };
    $copy = $isEnglish ? [
        'title' => 'Start lobby', 'eyebrow' => 'HNT.ROCKS READY LOBBY',
        'intro' => 'Build a lobby for Hunters who are ready right now.',
        'not_live' => 'Lobby is not live yet', 'live' => 'Lobby is live',
        'radar' => 'Back to lobby radar', 'sections' => 'Lobby content',
        'general' => 'Lobby', 'general_small' => 'Title and short message',
        'mode' => 'Game setup', 'mode_small' => 'Team size, platform and region',
        'squad' => 'Squad', 'squad_small' => 'Voice, MMR and playstyle',
        'contact' => 'Contact', 'contact_small' => 'Lobby code and platform handles',
        'complete' => 'Lobby complete', 'complete_hint' => 'Complete platform, language and squad settings.',
        'automatic' => 'Automatically current', 'automatic_text' => 'The lobby stays visible for 15 minutes and disappears automatically.',
        'planned_lfg' => 'Create a planned LFG instead', 'new' => 'NEW READY LOBBY',
        'discard' => 'Discard', 'publish' => 'Publish lobby',
        'what' => 'What do you want to play?', 'what_text' => 'Ready Lobbies are intended for an immediate start.',
        'lobby_title' => 'Lobby title', 'title_placeholder' => 'For example: Relaxed trio round',
        'note' => 'Short message', 'note_placeholder' => 'What should matching Hunters know?',
        'team_size' => 'Team size', 'duo' => 'DUO', 'trio' => 'TRIO',
        'platform' => 'Platform', 'region' => 'Region', 'language' => 'Language',
        'playstyle' => 'Playstyle', 'mood' => 'Mood',
        'communication' => 'Communication and matching', 'voice' => 'Voice required',
        'mmr' => 'MMR stars', 'open' => 'Open', 'reset' => 'Reset',
        'handle' => 'Platform handle', 'contact_title' => 'Contact after joining',
        'contact_text' => 'These details are shown only to active lobby members.',
        'lobby_code' => 'Lobby code', 'discord' => 'Discord handle',
        'steam' => 'Steam ID', 'psn' => 'PSN ID', 'xbox' => 'Xbox gamertag',
        'preview' => 'LIVE PREVIEW', 'host' => 'HOST', 'ready' => 'ready',
        'matching' => 'MATCHING', 'expires' => '15 minutes visible in the radar.',
        'saving' => 'Publishing …', 'created' => 'Ready Lobby created.',
        'validation' => 'Please complete the required lobby fields.',
        'error' => 'The Ready Lobby could not be created.', 'characters' => 'characters',
    ] : [
        'title' => 'Lobby starten', 'eyebrow' => 'HNT.ROCKS READY LOBBY',
        'intro' => 'Stelle eine Lobby für Hunter zusammen, die sofort spielbereit sind.',
        'not_live' => 'Lobby noch nicht live', 'live' => 'Lobby ist live',
        'radar' => 'Zum Lobby-Radar', 'sections' => 'Lobby-Inhalt',
        'general' => 'Lobby', 'general_small' => 'Titel und kurze Nachricht',
        'mode' => 'Spielmodus', 'mode_small' => 'Teamgröße, Plattform und Region',
        'squad' => 'Squad', 'squad_small' => 'Voice, MMR und Spielstil',
        'contact' => 'Kontakt', 'contact_small' => 'Lobby-Code und Plattform-Namen',
        'complete' => 'Lobby vollständig', 'complete_hint' => 'Plattform, Sprache und Squad-Einstellungen vervollständigen.',
        'automatic' => 'Automatisch aktuell', 'automatic_text' => 'Die Lobby bleibt 15 Minuten sichtbar und verschwindet danach automatisch.',
        'planned_lfg' => 'Lieber eine geplante LFG erstellen', 'new' => 'NEUE READY LOBBY',
        'discard' => 'Verwerfen', 'publish' => 'Lobby live schalten',
        'what' => 'Was möchtet ihr spielen?', 'what_text' => 'Die Ready Lobby ist für einen sofortigen Start gedacht.',
        'lobby_title' => 'Lobby-Titel', 'title_placeholder' => 'Zum Beispiel: Ruhige Trio-Runde',
        'note' => 'Kurze Nachricht', 'note_placeholder' => 'Was sollen passende Hunter wissen?',
        'team_size' => 'Teamgröße', 'duo' => 'DUO', 'trio' => 'TRIO',
        'platform' => 'Plattform', 'region' => 'Region', 'language' => 'Sprache',
        'playstyle' => 'Spielstil', 'mood' => 'Stimmung',
        'communication' => 'Kommunikation und Matching', 'voice' => 'Voice erforderlich',
        'mmr' => 'MMR-Sterne', 'open' => 'Offen', 'reset' => 'Zurücksetzen',
        'handle' => 'Plattform-Name', 'contact_title' => 'Kontakt nach dem Beitritt',
        'contact_text' => 'Diese Angaben werden nur aktiven Lobby-Mitgliedern angezeigt.',
        'lobby_code' => 'Lobby-Code', 'discord' => 'Discord-Name',
        'steam' => 'Steam-ID', 'psn' => 'PSN-ID', 'xbox' => 'Xbox-Gamertag',
        'preview' => 'LIVE-VORSCHAU', 'host' => 'HOST', 'ready' => 'bereit',
        'matching' => 'MATCHING', 'expires' => '15 Minuten im Radar sichtbar.',
        'saving' => 'Wird veröffentlicht …', 'created' => 'Ready Lobby wurde erstellt.',
        'validation' => 'Bitte vervollständige die erforderlichen Lobby-Angaben.',
        'error' => 'Die Ready Lobby konnte nicht erstellt werden.', 'characters' => 'Zeichen',
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
<link href="{{ asset('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-live.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/ready-lobbies/ready-lobby-create.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/ready-lobbies/ready-lobby-create.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="ready-lobby-create">
@include('themes.hnt_preview.partials.icons')
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
<main class="app-shell profile-edit-page-shell ready-create-page-shell" data-ready-create-root data-store-url="{{ route('ready-lobbies.store') }}" data-index-url="{{ route('ready-lobbies.index') }}">
@include('themes.hnt_preview.partials.header')
<div class="profile-edit-scroll" id="readyCreateScroll">
<section class="profile-edit-heading ready-create-heading"><div><span>{{ $copy['eyebrow'] }}</span><h1>{{ $copy['title'] }}</h1><p>{{ $copy['intro'] }}</p></div><div class="profile-edit-heading-actions"><span class="save-state" id="readyCreateState"><i></i><span>{{ $copy['not_live'] }}</span></span><a href="{{ route('ready-lobbies.index') }}">{{ $copy['radar'] }} <svg><use href="#i-arrow"></use></svg></a></div></section>
<section class="profile-edit-workspace ready-create-workspace">
<aside class="profile-edit-nav-card ready-create-nav"><header><div><span>SOFORT STARTEN</span><h2>{{ $copy['sections'] }}</h2></div><strong id="readyCompletionNav">0%</strong></header><div class="profile-edit-nav-list" role="tablist">
@foreach([['general','i-comment',$copy['general'],$copy['general_small']],['mode','i-sliders',$copy['mode'],$copy['mode_small']],['squad','i-users',$copy['squad'],$copy['squad_small']],['contact','i-phone',$copy['contact'],$copy['contact_small']]] as $index => $tab)
<button class="{{ $index === 0 ? 'active' : '' }}" data-ready-form-tab="{{ $tab[0] }}" data-title="{{ $tab[2] }}" type="button" role="tab" aria-selected="{{ $index === 0 ? 'true' : 'false' }}"><svg><use href="#{{ $tab[1] }}"></use></svg><span><strong>{{ $tab[2] }}</strong><small>{{ $tab[3] }}</small></span><i data-ready-tab-status="{{ $tab[0] }}">0%</i></button>
@endforeach
</div><div class="profile-edit-nav-progress"><div><span>{{ $copy['complete'] }}</span><strong id="readyCompletionText">0%</strong></div><i><b id="readyCompletionBar" style="width:0"></b></i><small id="readyCompletionHint">{{ $copy['complete_hint'] }}</small></div><article class="ready-expiry-card"><span><svg><use href="#i-reply"></use></svg></span><div><strong>{{ $copy['automatic'] }}</strong><p>{{ $copy['automatic_text'] }}</p></div></article><a class="ready-switch-link" href="{{ route('lfg.create') }}">{{ $copy['planned_lfg'] }}<svg><use href="#i-arrow"></use></svg></a></aside>
<section class="profile-edit-form-card ready-create-form-card"><header class="profile-edit-form-head"><div><span>{{ $copy['new'] }}</span><h2 id="readyFormPanelTitle">{{ $copy['general'] }}</h2></div><div><button id="readyDiscardTop" type="button">{{ $copy['discard'] }}</button><button class="primary" id="readyPublishTop" type="button">{{ $copy['publish'] }}</button></div></header>
<form id="readyCreateForm" novalidate>
<section class="profile-edit-panel active" data-ready-form-panel="general"><div class="profile-edit-section-intro"><span>LOBBY</span><h3>{{ $copy['what'] }}</h3><p>{{ $copy['what_text'] }}</p></div><div class="profile-edit-field-grid"><label class="profile-edit-field full"><span>{{ $copy['lobby_title'] }}</span><input id="readyTitle" maxlength="80" placeholder="{{ $copy['title_placeholder'] }}" required><small><b id="readyTitleCount">0</b> / 80 {{ $copy['characters'] }}</small></label><label class="profile-edit-field full"><span>{{ $copy['note'] }}</span><textarea id="readyNote" maxlength="410" placeholder="{{ $copy['note_placeholder'] }}" rows="7"></textarea><small><b id="readyNoteCount">0</b> / 410 {{ $copy['characters'] }}</small></label></div></section>
<section class="profile-edit-panel" data-ready-form-panel="mode" hidden><div class="profile-edit-section-intro"><span>{{ $copy['mode'] }}</span><h3>{{ $copy['team_size'] }}</h3><p>{{ $copy['automatic_text'] }}</p></div><div class="ready-squad-size"><label><input name="mode" type="radio" value="duo"><span>{{ $copy['duo'] }}</span><strong>2 Hunter</strong><small>1 {{ $isEnglish ? 'open slot' : 'freier Platz' }}</small><i><svg><use href="#i-check"></use></svg></i></label><label><input checked name="mode" type="radio" value="trio"><span>{{ $copy['trio'] }}</span><strong>3 Hunter</strong><small>2 {{ $isEnglish ? 'open slots' : 'freie Plätze' }}</small><i><svg><use href="#i-check"></use></svg></i></label></div><div class="profile-edit-field-grid"><label class="profile-edit-field"><span>{{ $copy['platform'] }}</span><select id="readyPlatform" name="platform" required><option value="pc" @selected($defaultPlatform === 'pc')>PC</option><option value="playstation" @selected($defaultPlatform === 'playstation')>PlayStation</option><option value="xbox" @selected($defaultPlatform === 'xbox')>Xbox</option></select></label><label class="profile-edit-field"><span>{{ $copy['region'] }}</span><select id="readyRegion" name="region"><option value="">—</option>@foreach(['EU','US East','US West','Asia','Oceania'] as $option)<option value="{{ $option }}" @selected(($profile?->region ?? '') === $option)>{{ $option }}</option>@endforeach</select></label><label class="profile-edit-field"><span>{{ $copy['language'] }}</span><select id="readyLanguage" name="language"><option value="">—</option>@foreach(['Deutsch','English','Deutsch / English','Français','Español'] as $option)<option value="{{ $option }}" @selected(($profile?->language ?? '') === $option)>{{ $option }}</option>@endforeach</select></label><label class="profile-edit-field"><span>{{ $copy['playstyle'] }}</span><select id="readyPlaystyle" name="playstyle"><option value="">—</option>@foreach(['Entspannt','Taktisch','Aggressiv','Competitive','Einsteigerfreundlich'] as $option)<option value="{{ $option }}" @selected(($profile?->playstyle ?? '') === $option)>{{ $option }}</option>@endforeach</select></label></div></section>
<section class="profile-edit-panel" data-ready-form-panel="squad" hidden><div class="profile-edit-section-intro"><span>{{ $copy['squad'] }}</span><h3>{{ $copy['communication'] }}</h3><p>{{ $copy['complete_hint'] }}</p></div><div class="profile-edit-field-grid"><label class="profile-edit-field"><span>{{ $copy['mood'] }}</span><select id="readyMood" name="mood"><option value="">—</option><option value="chill">Chill</option><option value="serious">Serious</option><option value="pvp">PvP</option><option value="bossrush">Bossrush</option><option value="meme">Meme</option><option value="teaching">Teaching</option></select></label><label class="profile-edit-field"><span>{{ $copy['handle'] }}</span><input id="readyHandle" name="platform_handle" maxlength="100"></label></div><article class="profile-setting-row ready-voice-row"><div class="profile-setting-icon"><svg><use href="#i-comment"></use></svg></div><div><strong>{{ $copy['voice'] }}</strong><small>{{ $isEnglish ? 'Hunters need voice communication to join.' : 'Hunter benötigen Voice-Kommunikation für den Beitritt.' }}</small></div><label class="profile-toggle"><input id="readyVoice" name="voice_required" type="checkbox" value="1"><i></i></label></article><section class="ready-mmr-card"><header><div><span>OPTIONALE MMR-EINGRENZUNG</span><h3>{{ $copy['mmr'] }}</h3></div><strong id="readyMmrValue">{{ $copy['open'] }}</strong></header><p>{{ $isEnglish ? 'Without a selection the lobby stays open to every MMR level.' : 'Ohne Auswahl bleibt die Lobby für alle MMR-Stufen offen.' }}</p><div id="readyMmrStars">@for($star=1;$star<=6;$star++)<button data-mmr="{{ $star }}" type="button">★</button>@endfor<button class="clear" data-mmr="0" type="button">{{ $copy['reset'] }}</button></div></section><section class="ready-squad-preview-card"><header><span>LOBBY-VORSCHAU</span><strong id="readySlotHeadline">1 / 3</strong></header><div id="readySlotPreview"></div></section></section>
<section class="profile-edit-panel" data-ready-form-panel="contact" hidden><div class="profile-edit-section-intro"><span>{{ $copy['contact'] }}</span><h3>{{ $copy['contact_title'] }}</h3><p>{{ $copy['contact_text'] }}</p></div><div class="profile-edit-field-grid"><label class="profile-edit-field"><span>{{ $copy['lobby_code'] }}</span><input id="readyLobbyCode" name="lobby_code" maxlength="64"></label><label class="profile-edit-field"><span>{{ $copy['discord'] }}</span><input id="readyDiscord" name="discord_handle" maxlength="100"></label><label class="profile-edit-field" data-platform-contact="pc"><span>{{ $copy['steam'] }}</span><input id="readySteam" name="steam_id" maxlength="100"></label><label class="profile-edit-field" data-platform-contact="playstation"><span>{{ $copy['psn'] }}</span><input id="readyPsn" name="psn_id" maxlength="100"></label><label class="profile-edit-field" data-platform-contact="xbox"><span>{{ $copy['xbox'] }}</span><input id="readyXbox" name="xbox_gamertag" maxlength="100"></label></div><article class="ready-contact-note"><span><svg><use href="#i-eye"></use></svg></span><div><strong>{{ $copy['contact_title'] }}</strong><p>{{ $copy['contact_text'] }}</p></div></article></section>
<p class="ready-create-error" id="readyCreateError" hidden></p><footer class="profile-edit-save-footer"><div><strong>{{ $copy['publish'] }}</strong><span>{{ $copy['complete'] }}: <b id="readyCompletionFooter">0%</b></span></div><div><a href="{{ route('ready-lobbies.index') }}">{{ $copy['discard'] }}</a><button type="submit">{{ $copy['publish'] }}</button></div></footer>
</form></section>
<aside class="profile-live-preview-card ready-live-preview-card"><header><div><span>{{ $copy['preview'] }}</span><h2>Ready Lobby</h2></div><a href="{{ route('ready-lobbies.index') }}"><svg><use href="#i-arrow"></use></svg></a></header><article class="ready-create-preview-hero"><div class="ready-preview-radar-lines"></div><header><img alt="{{ $viewer?->name }}" src="{{ $avatar }}"><div><span>{{ $copy['host'] }}</span><strong>{{ $viewer?->name ?: $viewer?->username }}</strong><small>online · {{ $copy['ready'] }}</small></div><em>JETZT</em></header><h3 id="readyPreviewTitle">{{ $copy['title_placeholder'] }}</h3><p id="readyPreviewNote">{{ $copy['note_placeholder'] }}</p><div class="ready-preview-tags"><span id="readyPreviewMode">{{ $copy['trio'] }}</span><span id="readyPreviewPlatform">{{ ucfirst($defaultPlatform) }}</span><span id="readyPreviewRegion">{{ $profile?->region ?: '—' }}</span><span id="readyPreviewLanguage">{{ $profile?->language ?: '—' }}</span></div></article><section class="ready-preview-squad" id="readyPreviewSquad"></section><section class="ready-preview-match-card"><header><span>{{ $copy['matching'] }}</span><strong id="readyPreviewMatch">70%</strong></header><div><article><i class="good"></i><span>{{ $copy['platform'] }}</span><strong id="readyMatchPlatform">{{ ucfirst($defaultPlatform) }}</strong></article><article><i></i><span>{{ $copy['region'] }}</span><strong id="readyMatchRegion">{{ $profile?->region ?: '—' }}</strong></article><article><i></i><span>{{ $copy['playstyle'] }}</span><strong id="readyMatchStyle">{{ $profile?->playstyle ?: '—' }}</strong></article><article><i></i><span>MMR</span><strong id="readyMatchMmr">{{ $copy['open'] }}</strong></article></div></section><div class="profile-live-completion"><div><span>{{ $copy['complete'] }}</span><strong id="readyPreviewCompletionText">0%</strong></div><i><b id="readyPreviewCompletionBar" style="width:0"></b></i><small>{{ $copy['expires'] }}</small></div></aside>
</section></div>
<div class="ready-lobby-toast" id="readyLobbyToast" role="status" aria-live="polite"></div>
<script type="application/json" id="readyCreateCopy">{!! json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/ready-lobbies/ready-lobby-create.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/ready-lobbies/ready-lobby-create.js')) ?: time() }}" defer></script>
</body></html>
