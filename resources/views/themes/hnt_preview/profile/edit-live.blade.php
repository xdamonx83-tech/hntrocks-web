@php
    $profile = $user->profile;
    $avatarUrl = $user->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $coverUrl = $user->coverUrl();
    $completion = \App\Support\ProfileCompletion::score($user);
    $visibility = old('profile_visibility', $profile?->profile_visibility ?? 'public');
    $platform = old('platform', $profile?->platform);
    $region = old('region', $profile?->region);
    $playstyle = old('playstyle', $profile?->playstyle);
    $language = old('language', $profile?->language);
    $isLfgAvailable = (bool) old('is_lfg_available', $profile?->is_lfg_available);
    $coverDisplayMode = old('cover_display_mode', $profile?->coverDisplayMode() ?? \App\Models\UserProfile::COVER_DISPLAY_AUTO);
    $coverDisplayMode = in_array($coverDisplayMode, \App\Models\UserProfile::COVER_DISPLAY_MODES, true)
        ? $coverDisplayMode
        : \App\Models\UserProfile::COVER_DISPLAY_AUTO;
    $isEnglish = app()->getLocale() === 'en';
    $handle = $user->username ? '@'.$user->username : '@hunter';
    $onlineLabel = $user->isOnline() ? 'Online' : 'Offline';
    $copy = $isEnglish ? [
        'eyebrow' => 'HNT.ROCKS PROFILE', 'title' => 'Edit profile',
        'subtitle' => 'Keep your public details, Hunt preferences and profile media up to date.',
        'clean' => 'No unsaved changes', 'dirty' => 'Unsaved changes', 'saving' => 'Saving…',
        'public_profile' => 'Public profile', 'areas' => 'YOUR SECTIONS', 'content' => 'Profile content',
        'complete' => 'Profile complete', 'remaining' => 'Complete the highlighted core fields for 100%.',
        'general' => 'General', 'general_small' => 'Name, headline and bio',
        'media' => 'Media', 'media_small' => 'Avatar and cover',
        'hunt' => 'Hunt profile', 'hunt_small' => 'Platform and playstyle',
        'social' => 'Social links', 'social_small' => 'Steam, Twitch and YouTube',
        'privacy' => 'Privacy', 'privacy_small' => 'Visibility and LFG',
        'edit' => 'EDIT PROFILE', 'discard' => 'Discard', 'save' => 'Save changes',
        'my_profile' => 'MY PROFILE', 'public_info' => 'Public profile information',
        'public_info_text' => 'These details appear on your profile and in community areas.',
        'name' => 'Display name', 'name_hint' => 'Shown publicly on your profile.',
        'email' => 'Email address', 'email_hint' => 'Change it only in account settings.',
        'headline' => 'Profile headline', 'bio' => 'About me', 'characters' => 'characters',
        'profile_media' => 'PROFILE MEDIA', 'avatar_cover' => 'Avatar and cover image',
        'media_text' => 'Images are cropped and saved through the real media service.',
        'cover' => 'COVER IMAGE', 'cover_head' => 'Your public profile header', 'change_cover' => 'Change cover',
        'recommended' => 'Recommended', 'cover_hint' => 'Wide image · JPG, PNG or WebP · server limit applies',
        'avatar' => 'AVATAR', 'avatar_hint' => 'Square images work best.', 'change_avatar' => 'Change avatar',
        'cover_display' => 'Cover display',
        'cover_display_text' => 'Choose how your cover appears in the public profile header.',
        'cover_auto' => 'Automatic',
        'cover_auto_text' => 'Desktop hover and the cover button reveal it when needed.',
        'cover_always' => 'Always visible',
        'cover_always_text' => 'Keep the faded cover permanently visible behind your profile details.',
        'cover_hidden' => 'Do not show',
        'cover_hidden_text' => 'Keep the image saved without displaying it on your public profile.',
        'clear' => 'Easy to recognize', 'clear_text' => 'Avoid very dark or blurry images.',
        'crop' => 'Good crop', 'crop_text' => 'Keep important content centered.',
        'community' => 'Community safe', 'community_text' => 'Do not upload abusive or third-party content.',
        'hunt_profile' => 'HUNT PROFILE', 'play_availability' => 'Playstyle and availability',
        'hunt_text' => 'These details improve member search, LFG and future Ready Lobbies.',
        'platform' => 'Platform', 'region' => 'Region', 'playstyle' => 'Playstyle', 'language' => 'Language',
        'role' => 'Preferred role', 'role_hint' => 'For example Scout, Shotcaller or Support.',
        'discord_hint' => 'Optional and used for community features.',
        'lfg' => 'Available for LFG and Ready Lobbies', 'lfg_text' => 'Other hunters may find you for matching groups.',
        'external' => 'Your external profiles', 'external_text' => 'Only completed links appear on your profile.',
        'public_view' => 'PUBLIC VIEW', 'social_bar' => 'Social bar',
        'who' => 'Who can see your profile?', 'who_text' => 'Choose public, registered users only, or private.',
        'public' => 'Public', 'public_text' => 'Anyone can view your profile and public content.',
        'registered' => 'Registered users only', 'registered_text' => 'Visitors without an account cannot view it.',
        'private' => 'Private', 'private_text' => 'Your profile is visible only to you.',
        'impact' => 'Visibility impact', 'impact_text' => 'Private profiles are hidden from member search and public suggestions.',
        'ready' => 'Ready to save', 'profile_is' => 'Your profile is', 'done' => 'complete.',
        'discard_all' => 'Discard all changes', 'live' => 'LIVE PREVIEW', 'your_profile' => 'Your profile',
        'no_headline' => 'No headline yet', 'no_bio' => 'No profile description yet.', 'open' => 'Open',
        'not_set' => 'Not specified', 'visibility' => 'Visibility', 'posts' => 'Posts', 'friends' => 'Friends',
        'moments' => 'Moments', 'preview_hint' => 'Preview updates while typing.',
        'crop_title' => 'Crop image', 'crop_help' => 'Move the image and adjust zoom before saving.',
        'zoom' => 'Zoom', 'choose_another' => 'Choose another', 'cancel' => 'Cancel', 'save_image' => 'Save image',
        'image_saved' => 'Profile image saved.', 'image_failed' => 'The image could not be saved.',
        'choose_image' => 'Choose a JPG, PNG or WebP image.', 'leave_warning' => 'You have unsaved profile changes.',
    ] : [
        'eyebrow' => 'HNT.ROCKS PROFIL', 'title' => 'Profil bearbeiten',
        'subtitle' => 'Halte deine öffentlichen Angaben, Hunt-Präferenzen und Profilmedien aktuell.',
        'clean' => 'Keine offenen Änderungen', 'dirty' => 'Ungespeicherte Änderungen', 'saving' => 'Wird gespeichert …',
        'public_profile' => 'Öffentliches Profil', 'areas' => 'DEINE BEREICHE', 'content' => 'Profilinhalt',
        'complete' => 'Profil vollständig', 'remaining' => 'Fülle die markierten Kernfelder für 100 % aus.',
        'general' => 'Allgemein', 'general_small' => 'Name, Headline und Bio',
        'media' => 'Medien', 'media_small' => 'Avatar und Titelbild',
        'hunt' => 'Hunt-Profil', 'hunt_small' => 'Plattform und Spielstil',
        'social' => 'Social Links', 'social_small' => 'Steam, Twitch und YouTube',
        'privacy' => 'Privatsphäre', 'privacy_small' => 'Sichtbarkeit und LFG',
        'edit' => 'PROFIL BEARBEITEN', 'discard' => 'Verwerfen', 'save' => 'Änderungen speichern',
        'my_profile' => 'MEIN PROFIL', 'public_info' => 'Öffentliche Profilinformationen',
        'public_info_text' => 'Diese Angaben erscheinen in deinem Profil und in Community-Bereichen.',
        'name' => 'Anzeigename', 'name_hint' => 'Wird öffentlich in deinem Profil angezeigt.',
        'email' => 'E-Mail-Adresse', 'email_hint' => 'Kann nur in den Kontoeinstellungen geändert werden.',
        'headline' => 'Profil-Headline', 'bio' => 'Über mich', 'characters' => 'Zeichen',
        'profile_media' => 'PROFILMEDIEN', 'avatar_cover' => 'Avatar und Titelbild',
        'media_text' => 'Bilder werden über den echten Medienservice zugeschnitten und gespeichert.',
        'cover' => 'TITELBILD', 'cover_head' => 'Dein öffentlicher Profilkopf', 'change_cover' => 'Titelbild ändern',
        'recommended' => 'Empfohlen', 'cover_hint' => 'Breites Bild · JPG, PNG oder WebP · Serverlimit beachten',
        'avatar' => 'AVATAR', 'avatar_hint' => 'Quadratische Bilder funktionieren am besten.', 'change_avatar' => 'Avatar ändern',
        'cover_display' => 'Titelbild-Anzeige',
        'cover_display_text' => 'Bestimme, wie dein Titelbild im öffentlichen Profilkopf erscheint.',
        'cover_auto' => 'Automatisch',
        'cover_auto_text' => 'Desktop-Hover und der Titelbild-Button zeigen es bei Bedarf an.',
        'cover_always' => 'Immer sichtbar',
        'cover_always_text' => 'Das ausgeblendete Titelbild bleibt dauerhaft hinter deinen Profildaten sichtbar.',
        'cover_hidden' => 'Nicht anzeigen',
        'cover_hidden_text' => 'Das Bild bleibt gespeichert, wird aber im öffentlichen Profil nicht eingeblendet.',
        'clear' => 'Gut erkennbar', 'clear_text' => 'Keine zu dunklen oder unscharfen Bilder.',
        'crop' => 'Passender Ausschnitt', 'crop_text' => 'Wichtige Inhalte mittig platzieren.',
        'community' => 'Community-tauglich', 'community_text' => 'Keine beleidigenden oder fremden Inhalte.',
        'hunt_profile' => 'HUNT-PROFIL', 'play_availability' => 'Spielweise und Verfügbarkeit',
        'hunt_text' => 'Diese Angaben helfen bei Mitgliedersuche, LFG und künftigen Ready Lobbys.',
        'platform' => 'Plattform', 'region' => 'Region', 'playstyle' => 'Spielstil', 'language' => 'Sprache',
        'role' => 'Bevorzugte Rolle', 'role_hint' => 'Zum Beispiel Scout, Shotcaller oder Support.',
        'discord_hint' => 'Optional und nur für Community-Funktionen.',
        'lfg' => 'Für LFG und Ready Lobbys verfügbar', 'lfg_text' => 'Andere Hunter dürfen dich für passende Gruppen finden.',
        'external' => 'Deine externen Profile', 'external_text' => 'Nur ausgefüllte Links werden im Profil angezeigt.',
        'public_view' => 'ÖFFENTLICHE ANSICHT', 'social_bar' => 'Social-Leiste',
        'who' => 'Wer darf dein Profil sehen?', 'who_text' => 'Wähle öffentlich, nur registrierte Nutzer oder privat.',
        'public' => 'Öffentlich', 'public_text' => 'Jeder kann dein Profil und öffentliche Inhalte sehen.',
        'registered' => 'Nur registrierte Nutzer', 'registered_text' => 'Besucher ohne Konto sehen dein Profil nicht.',
        'private' => 'Privat', 'private_text' => 'Dein Profil ist nur für dich sichtbar.',
        'impact' => 'Auswirkungen der Sichtbarkeit', 'impact_text' => 'Private Profile erscheinen nicht in Mitgliedersuche und öffentlichen Vorschlägen.',
        'ready' => 'Bereit zum Speichern', 'profile_is' => 'Dein Profil ist zu', 'done' => 'vollständig.',
        'discard_all' => 'Alle Änderungen verwerfen', 'live' => 'LIVE-VORSCHAU', 'your_profile' => 'Dein Profil',
        'no_headline' => 'Noch keine Headline', 'no_bio' => 'Noch keine Profilbeschreibung vorhanden.', 'open' => 'Offen',
        'not_set' => 'Nicht angegeben', 'visibility' => 'Sichtbarkeit', 'posts' => 'Posts', 'friends' => 'Freunde',
        'moments' => 'Moments', 'preview_hint' => 'Vorschau aktualisiert sich während der Eingabe.',
        'crop_title' => 'Bild zuschneiden', 'crop_help' => 'Verschiebe das Bild und passe den Zoom vor dem Speichern an.',
        'zoom' => 'Zoom', 'choose_another' => 'Anderes wählen', 'cancel' => 'Abbrechen', 'save_image' => 'Bild speichern',
        'image_saved' => 'Profilbild gespeichert.', 'image_failed' => 'Das Bild konnte nicht gespeichert werden.',
        'choose_image' => 'Bitte ein JPG-, PNG- oder WebP-Bild wählen.', 'leave_warning' => 'Du hast ungespeicherte Profiländerungen.',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'de' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>{{ $copy['title'] }} · HNT.ROCKS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-live.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-profile-edit/profile-cover-display-mode.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile-edit/profile-cover-display-mode.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="profile-edit">
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
@include('themes.hnt_preview.partials.icons')
<main class="app-shell profile-edit-page-shell" data-hnt-dashboard-header data-profile-edit-live>
@include('themes.hnt_preview.partials.header')
<div class="profile-edit-scroll">
<section class="profile-edit-heading">
<div><span>{{ $copy['eyebrow'] }}</span><h1>{{ $copy['title'] }}</h1><p>{{ $copy['subtitle'] }}</p></div>
<div class="profile-edit-heading-actions"><span class="save-state" id="profileSaveState"><i></i><span>{{ $copy['clean'] }}</span></span><a href="{{ route('profile.show') }}">{{ $copy['public_profile'] }} <svg><use href="#i-arrow"></use></svg></a></div>
</section>
@if(session('status') || $errors->any())
<div class="profile-edit-flash {{ $errors->any() ? 'danger' : 'success' }}" role="{{ $errors->any() ? 'alert' : 'status' }}">{{ $errors->any() ? __('ui.profile_validation_error') : session('status') }}</div>
@endif
<section class="profile-edit-workspace">
<aside class="profile-edit-nav-card" aria-label="{{ $copy['content'] }}"><header><div><span>{{ $copy['areas'] }}</span><h2>{{ $copy['content'] }}</h2></div><strong id="profileCompletionNav">{{ $completion }}%</strong></header><div class="profile-edit-nav-list" role="tablist">
@foreach([['general','i-user',$copy['general'],$copy['general_small']],['media','i-image',$copy['media'],$copy['media_small']],['hunt','i-sliders',$copy['hunt'],$copy['hunt_small']],['social','i-share',$copy['social'],$copy['social_small']],['privacy','i-eye',$copy['privacy'],$copy['privacy_small']]] as $index => $tab)
<button type="button" class="{{ $index === 0 ? 'active' : '' }}" data-profile-tab="{{ $tab[0] }}" data-title="{{ $tab[2] }}" role="tab" aria-selected="{{ $index === 0 ? 'true' : 'false' }}"><svg><use href="#{{ $tab[1] }}"></use></svg><span><strong>{{ $tab[2] }}</strong><small>{{ $tab[3] }}</small></span><i data-tab-completion="{{ $tab[0] }}">—</i></button>
@endforeach
</div><div class="profile-edit-nav-progress"><div><span>{{ $copy['complete'] }}</span><strong id="profileCompletionText">{{ $completion }}%</strong></div><i><b id="profileCompletionBar" style="width:{{ $completion }}%"></b></i><small>{{ $copy['remaining'] }}</small></div></aside>
<section class="profile-edit-form-card"><header class="profile-edit-form-head"><div><span>{{ $copy['edit'] }}</span><h2 id="profileEditPanelTitle">{{ $copy['general'] }}</h2></div><div><button id="profileDiscardTop" type="button">{{ $copy['discard'] }}</button><button class="primary" id="profileSaveTop" type="button">{{ $copy['save'] }}</button></div></header>
<form id="profileEditForm" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
<section class="profile-edit-panel active" data-profile-panel="general"><div class="profile-edit-section-intro"><span>{{ $copy['my_profile'] }}</span><h3>{{ $copy['public_info'] }}</h3><p>{{ $copy['public_info_text'] }}</p></div><div class="profile-edit-field-grid">
<label class="profile-edit-field"><span>{{ $copy['name'] }}</span><input id="profileName" maxlength="80" name="name" required value="{{ old('name', $user->name) }}"><small>{{ $copy['name_hint'] }}</small>@error('name')<em data-field-error>{{ $message }}</em>@enderror</label>
<label class="profile-edit-field"><span>{{ $copy['email'] }}</span><input disabled type="email" value="{{ $user->email }}"><small>{{ $copy['email_hint'] }}</small></label>
<label class="profile-edit-field full"><span>{{ $copy['headline'] }}</span><input id="profileHeadline" maxlength="120" name="headline" value="{{ old('headline', $profile?->headline) }}"><small><b id="headlineCount">0</b> / 120 {{ $copy['characters'] }}</small>@error('headline')<em data-field-error>{{ $message }}</em>@enderror</label>
<label class="profile-edit-field full"><span>{{ $copy['bio'] }}</span><textarea id="profileBio" maxlength="1200" name="bio" rows="7">{{ old('bio', $profile?->bio) }}</textarea><small><b id="bioCount">0</b> / 1200 {{ $copy['characters'] }}</small>@error('bio')<em data-field-error>{{ $message }}</em>@enderror</label>
</div></section>
<section class="profile-edit-panel" data-profile-panel="media" hidden>
<div class="profile-edit-section-intro"><span>{{ $copy['profile_media'] }}</span><h3>{{ $copy['avatar_cover'] }}</h3><p>{{ $copy['media_text'] }}</p></div>
<div class="profile-media-layout"><article class="profile-cover-editor"><div class="profile-cover-preview" id="profileCoverPreview" style="background-image:linear-gradient(135deg,rgba(255,255,255,.12),rgba(255,213,90,.20)),url('{{ $coverUrl }}')"><span>{{ $copy['cover'] }}</span><strong>{{ $copy['cover_head'] }}</strong><button type="button" data-profile-media-trigger="cover"><svg><use href="#i-image"></use></svg> {{ $copy['change_cover'] }}</button></div><div class="profile-media-note"><span>{{ $copy['recommended'] }}</span><p>{{ $copy['cover_hint'] }}</p></div></article><article class="profile-avatar-editor"><div class="profile-avatar-editor-image"><img alt="{{ $user->name }}" id="profileAvatarPreview" src="{{ $avatarUrl }}"><i></i></div><div><span>{{ $copy['avatar'] }}</span><h3 data-preview-name>{{ old('name', $user->name) }}</h3><p>{{ $copy['avatar_hint'] }}</p><button type="button" data-profile-media-trigger="avatar">{{ $copy['change_avatar'] }}</button></div></article></div>
<div class="profile-media-guidelines"><article><span>01</span><div><strong>{{ $copy['clear'] }}</strong><small>{{ $copy['clear_text'] }}</small></div></article><article><span>02</span><div><strong>{{ $copy['crop'] }}</strong><small>{{ $copy['crop_text'] }}</small></div></article><article><span>03</span><div><strong>{{ $copy['community'] }}</strong><small>{{ $copy['community_text'] }}</small></div></article></div>
<fieldset class="profile-cover-mode-card">
<legend>{{ $copy['cover_display'] }}</legend>
<p>{{ $copy['cover_display_text'] }}</p>
<div class="profile-cover-mode-options">
@foreach([
    [\App\Models\UserProfile::COVER_DISPLAY_AUTO, 'i-eye', $copy['cover_auto'], $copy['cover_auto_text']],
    [\App\Models\UserProfile::COVER_DISPLAY_ALWAYS, 'i-image', $copy['cover_always'], $copy['cover_always_text']],
    [\App\Models\UserProfile::COVER_DISPLAY_HIDDEN, 'i-x', $copy['cover_hidden'], $copy['cover_hidden_text']],
] as $option)
<label>
<input type="radio" name="cover_display_mode" value="{{ $option[0] }}" @checked($coverDisplayMode === $option[0])>
<span class="profile-cover-mode-icon"><svg><use href="#{{ $option[1] }}"></use></svg></span>
<div><strong>{{ $option[2] }}</strong><small>{{ $option[3] }}</small></div>
<i><svg><use href="#i-check"></use></svg></i>
</label>
@endforeach
</div>
@error('cover_display_mode')<em class="profile-cover-mode-error" data-field-error>{{ $message }}</em>@enderror
</fieldset>
</section>
<section class="profile-edit-panel" data-profile-panel="hunt" hidden><div class="profile-edit-section-intro"><span>{{ $copy['hunt_profile'] }}</span><h3>{{ $copy['play_availability'] }}</h3><p>{{ $copy['hunt_text'] }}</p></div><div class="profile-edit-field-grid">
<label class="profile-edit-field"><span>{{ $copy['platform'] }}</span><select id="profilePlatform" name="platform"><option value="">{{ $copy['open'] }}</option>@foreach(['PC','PlayStation','Xbox','Crossplay'] as $option)<option value="{{ $option }}" @selected($platform === $option)>{{ $option }}</option>@endforeach</select>@error('platform')<em data-field-error>{{ $message }}</em>@enderror</label>
<label class="profile-edit-field"><span>{{ $copy['region'] }}</span><select id="profileRegion" name="region"><option value="">{{ $copy['open'] }}</option>@foreach(['EU','US East','US West','Asia','Oceania'] as $option)<option value="{{ $option }}" @selected($region === $option)>{{ $option }}</option>@endforeach</select>@error('region')<em data-field-error>{{ $message }}</em>@enderror</label>
<label class="profile-edit-field"><span>{{ $copy['playstyle'] }}</span><select id="profilePlaystyle" name="playstyle"><option value="">{{ $copy['open'] }}</option>@foreach(['Tactical','Aggressive','Beginner friendly','Casual','Competitive'] as $option)<option value="{{ $option }}" @selected($playstyle === $option)>{{ $option }}</option>@endforeach</select>@error('playstyle')<em data-field-error>{{ $message }}</em>@enderror</label>
<label class="profile-edit-field"><span>{{ $copy['language'] }}</span><select id="profileLanguage" name="language"><option value="">{{ $copy['open'] }}</option>@foreach(['Deutsch','English','Français','Español','Other'] as $option)<option value="{{ $option }}" @selected($language === $option)>{{ $option }}</option>@endforeach</select>@error('language')<em data-field-error>{{ $message }}</em>@enderror</label>
<label class="profile-edit-field"><span>{{ $copy['role'] }}</span><input id="profileRole" maxlength="60" name="hunt_role" value="{{ old('hunt_role', $profile?->hunt_role) }}"><small>{{ $copy['role_hint'] }}</small>@error('hunt_role')<em data-field-error>{{ $message }}</em>@enderror</label>
<label class="profile-edit-field"><span>Discord</span><input id="profileDiscord" maxlength="80" name="discord_name" value="{{ old('discord_name', $profile?->discord_name) }}"><small>{{ $copy['discord_hint'] }}</small>@error('discord_name')<em data-field-error>{{ $message }}</em>@enderror</label>
</div><article class="profile-setting-row"><div class="profile-setting-icon"><svg><use href="#i-users"></use></svg></div><div><strong>{{ $copy['lfg'] }}</strong><small>{{ $copy['lfg_text'] }}</small></div><label class="profile-toggle"><input type="hidden" name="is_lfg_available" value="0"><input id="profileLfg" name="is_lfg_available" value="1" type="checkbox" @checked($isLfgAvailable)><i></i></label></article></section>
<section class="profile-edit-panel" data-profile-panel="social" hidden><div class="profile-edit-section-intro"><span>{{ $copy['social'] }}</span><h3>{{ $copy['external'] }}</h3><p>{{ $copy['external_text'] }}</p></div><div class="profile-social-list">
<label><span class="profile-social-icon steam">S</span><div><strong>Steam</strong><small>Community</small></div><input id="profileSteam" name="steam_url" placeholder="https://steamcommunity.com/id/..." type="url" value="{{ old('steam_url', $profile?->steam_url) }}">@error('steam_url')<em data-field-error>{{ $message }}</em>@enderror</label>
<label><span class="profile-social-icon twitch">T</span><div><strong>Twitch</strong><small>Livestream</small></div><input id="profileTwitch" name="twitch_url" placeholder="https://www.twitch.tv/..." type="url" value="{{ old('twitch_url', $profile?->twitch_url) }}">@error('twitch_url')<em data-field-error>{{ $message }}</em>@enderror</label>
<label><span class="profile-social-icon youtube">Y</span><div><strong>YouTube</strong><small>Videos</small></div><input id="profileYoutube" name="youtube_url" placeholder="https://www.youtube.com/@..." type="url" value="{{ old('youtube_url', $profile?->youtube_url) }}">@error('youtube_url')<em data-field-error>{{ $message }}</em>@enderror</label>
</div><article class="profile-social-preview"><div><span>{{ $copy['public_view'] }}</span><h3>{{ $copy['social_bar'] }}</h3><p>{{ $copy['external_text'] }}</p></div><div class="profile-social-preview-buttons"><button type="button" data-social-preview="profileSteam">Steam</button><button type="button" data-social-preview="profileTwitch">Twitch</button><button type="button" data-social-preview="profileYoutube">YouTube</button></div></article></section>
<section class="profile-edit-panel" data-profile-panel="privacy" hidden><div class="profile-edit-section-intro"><span>{{ $copy['privacy'] }}</span><h3>{{ $copy['who'] }}</h3><p>{{ $copy['who_text'] }}</p></div><div class="profile-visibility-options">@foreach([['public','i-eye',$copy['public'],$copy['public_text']],['registered','i-users',$copy['registered'],$copy['registered_text']],['private','i-user',$copy['private'],$copy['private_text']]] as $option)<label><input name="profile_visibility" type="radio" value="{{ $option[0] }}" @checked($visibility === $option[0])><span class="visibility-icon"><svg><use href="#{{ $option[1] }}"></use></svg></span><div><strong>{{ $option[2] }}</strong><small>{{ $option[3] }}</small></div><i><svg><use href="#i-check"></use></svg></i></label>@endforeach @error('profile_visibility')<em class="profile-visibility-error" data-field-error>{{ $message }}</em>@enderror</div><article class="profile-privacy-note"><span><svg><use href="#i-eye"></use></svg></span><div><strong>{{ $copy['impact'] }}</strong><p>{{ $copy['impact_text'] }}</p></div></article></section>
<footer class="profile-edit-save-footer"><div><strong>{{ $copy['ready'] }}</strong><span>{{ $copy['profile_is'] }} <b id="profileCompletionFooter">{{ $completion }}%</b> {{ $copy['done'] }}</span></div><div><a href="{{ route('profile.show') }}">{{ $copy['discard_all'] }}</a><button type="submit" data-profile-submit>{{ $copy['save'] }}</button></div></footer></form></section>
<aside class="profile-live-preview-card"><header><div><span>{{ $copy['live'] }}</span><h2>{{ $copy['your_profile'] }}</h2></div><a aria-label="{{ $copy['public_profile'] }}" href="{{ route('profile.show') }}"><svg><use href="#i-arrow"></use></svg></a></header><div class="profile-live-cover" id="liveCoverPreview" style="background-image:linear-gradient(135deg,rgba(255,255,255,.12),rgba(255,213,90,.20)),url('{{ $coverUrl }}')"><span>HNT.ROCKS</span></div><div class="profile-live-person"><div class="profile-live-avatar"><img alt="{{ $user->name }}" id="liveAvatarPreview" src="{{ $avatarUrl }}"><i></i></div><div><strong id="liveName">{{ old('name', $user->name) }}</strong><span>{{ $handle }} · {{ $onlineLabel }}</span></div></div><p id="liveHeadline">{{ old('headline', $profile?->headline) ?: $copy['no_headline'] }}</p><div class="profile-live-tags"><span id="livePlatform">{{ $platform ?: $copy['open'] }}</span><span id="liveRegion">{{ $region ?: $copy['open'] }}</span><span id="liveLanguage">{{ $language ?: $copy['open'] }}</span><span id="livePlaystyle">{{ $playstyle ?: $copy['open'] }}</span></div><div class="profile-live-bio" id="liveBio">{{ old('bio', $profile?->bio) ?: $copy['no_bio'] }}</div><div class="profile-live-info"><article><span>{{ $copy['role'] }}</span><strong id="liveRole">{{ old('hunt_role', $profile?->hunt_role) ?: $copy['not_set'] }}</strong></article><article><span>Discord</span><strong id="liveDiscord">{{ old('discord_name', $profile?->discord_name) ?: $copy['not_set'] }}</strong></article><article><span>{{ $copy['visibility'] }}</span><strong id="liveVisibility">{{ $visibility === 'private' ? $copy['private'] : ($visibility === 'registered' ? $copy['registered'] : $copy['public']) }}</strong></article></div><div class="profile-live-stats"><span><strong>{{ number_format((int)($user->profile_edit_posts_count ?? 0),0,',','.') }}</strong><small>{{ $copy['posts'] }}</small></span><span><strong>{{ number_format((int)$profileEditFriendsCount,0,',','.') }}</strong><small>{{ $copy['friends'] }}</small></span><span><strong>{{ number_format((int)($user->profile_edit_moments_count ?? 0),0,',','.') }}</strong><small>{{ $copy['moments'] }}</small></span></div><div class="profile-live-completion"><div><span>{{ $copy['complete'] }}</span><strong id="previewCompletionText">{{ $completion }}%</strong></div><i><b id="previewCompletionBar" style="width:{{ $completion }}%"></b></i><small>{{ $copy['preview_hint'] }}</small></div></aside>
</section>
</div>
<input id="profileMediaInput" type="file" accept="image/jpeg,image/png,image/webp" hidden>
<div class="profile-crop-modal" id="profileCropModal" aria-hidden="true" hidden><div class="profile-crop-dialog" role="dialog" aria-modal="true" aria-labelledby="profileCropTitle"><header><div><span>{{ $copy['profile_media'] }}</span><h2 id="profileCropTitle">{{ $copy['crop_title'] }}</h2><p>{{ $copy['crop_help'] }}</p></div><button type="button" data-crop-close aria-label="{{ $copy['cancel'] }}"><svg><use href="#i-x"></use></svg></button></header><div class="profile-crop-body"><div class="profile-crop-stage"><canvas id="profileCropCanvas"></canvas></div><label>{{ $copy['zoom'] }}<input id="profileCropZoom" type="range" min="1" max="3" step="0.01" value="1"></label><p id="profileCropError" class="profile-crop-error" hidden></p></div><footer><button type="button" id="profileCropChoose">{{ $copy['choose_another'] }}</button><button type="button" data-crop-close>{{ $copy['cancel'] }}</button><button type="button" class="primary" id="profileCropSave">{{ $copy['save_image'] }}</button></footer></div></div>
<div class="toast" id="toast"></div>
</main>
@php
    $profileEditConfig = [
        'updateMediaUrl' => route('profile.media.update'),
        'csrf' => csrf_token(),
        'copy' => [
            'clean' => $copy['clean'],
            'dirty' => $copy['dirty'],
            'saving' => $copy['saving'],
            'noHeadline' => $copy['no_headline'],
            'noBio' => $copy['no_bio'],
            'open' => $copy['open'],
            'notSet' => $copy['not_set'],
            'public' => $copy['public'],
            'registered' => $copy['registered'],
            'private' => $copy['private'],
            'imageSaved' => $copy['image_saved'],
            'imageFailed' => $copy['image_failed'],
            'chooseImage' => $copy['choose_image'],
            'leaveWarning' => $copy['leave_warning'],
            'saveImage' => $copy['save_image'],
        ],
    ];
@endphp
<script>
    window.HNT_PROFILE_EDIT = {{ \Illuminate\Support\Js::from($profileEditConfig) }};
</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-deeplink.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-deeplink.js')) ?: time() }}"></script>
</body>
</html>
