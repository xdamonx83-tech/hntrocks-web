@php
    $isEnglish = app()->getLocale() === 'en';
    $nameValue = old('name', $team->name);
    $taglineValue = old('tagline', $team->tagline);
    $descriptionValue = old('description', $team->description);
    $platformValue = old('platform', $team->platform);
    $playstyleValue = old('playstyle', $team->playstyle);
    $regionValue = old('region', $team->region);
    $languageValue = old('language', $team->language);
    $visibilityValue = old('visibility', $team->visibility ?: 'public');
    $recruitmentValue = old('recruitment_status', $team->recruitment_status ?: 'open');
    $avatarUrl = $team->avatarUrl();
    $coverUrl = $team->coverUrl();
    $hasAvatar = filled($team->avatar_path);
    $hasCover = filled($team->cover_path);
    $teamInitials = \Illuminate\Support\Str::of($nameValue)
        ->trim()
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');
    $teamInitials = $teamInitials !== '' ? $teamInitials : 'HT';
    $profileFields = [
        $nameValue,
        $taglineValue,
        $descriptionValue,
        $platformValue,
        $playstyleValue,
        $regionValue,
        $languageValue,
        $hasAvatar ? $team->avatar_path : null,
        $hasCover ? $team->cover_path : null,
    ];
    $completion = (int) round(collect($profileFields)->filter(fn ($value) => filled($value))->count() / count($profileFields) * 100);
    $generalCompletion = (int) round(collect([$nameValue, $taglineValue, $descriptionValue])->filter(fn ($value) => filled($value))->count() / 3 * 100);
    $mediaCompletion = (int) round(collect([$hasAvatar, $hasCover])->filter()->count() / 2 * 100);
    $profileCompletion = (int) round(collect([$platformValue, $playstyleValue, $regionValue, $languageValue])->filter(fn ($value) => filled($value))->count() / 4 * 100);
    $platformOptions = collect(['PC', 'PlayStation', 'Xbox', 'Crossplay', $platformValue])->filter()->unique()->values();
    $playstyleOptions = collect(['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich', $playstyleValue])->filter()->unique()->values();
    $regionOptions = collect(['EU', 'US East', 'US West', 'Asia', 'Oceania', $regionValue])->filter()->unique()->values();
    $languageOptions = collect(['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig', $languageValue])->filter()->unique()->values();
    $isOwner = $team->isOwner(auth()->user());
    $ownerName = $team->owner?->name ?: ($team->owner?->username ?: 'HNT Hunter');
    $copy = $isEnglish ? [
        'eyebrow' => 'HNT.ROCKS TEAM',
        'title' => 'Edit team',
        'subtitle' => 'Update the team profile, Hunt focus, media and visibility of :team.',
        'clean' => 'No unsaved changes',
        'dirty' => 'Unsaved changes',
        'saving' => 'Saving …',
        'view_team' => 'View team',
        'areas' => 'YOUR SECTIONS',
        'content' => 'Team content',
        'general' => 'General',
        'general_small' => 'Name, tagline and description',
        'media' => 'Media',
        'media_small' => 'Team avatar and cover',
        'profile' => 'Team profile',
        'profile_small' => 'Platform and playstyle',
        'settings' => 'Visibility',
        'settings_small' => 'Public access and recruiting',
        'complete' => 'Team profile complete',
        'complete_hint' => 'All team details are complete.',
        'edit' => 'EDIT TEAM',
        'discard' => 'Discard',
        'save' => 'Save changes',
        'team_info' => 'TEAM INFO',
        'public_info' => 'Public team information',
        'public_info_text' => 'Name, tagline and description appear on the team page and in team search.',
        'name' => 'Team name',
        'shortcode' => 'Team initials',
        'shortcode_hint' => 'Generated automatically from the team name.',
        'tagline' => 'Tagline',
        'description' => 'Team description',
        'characters' => 'characters',
        'team_media' => 'TEAM MEDIA',
        'avatar_cover' => 'Avatar and cover',
        'media_text' => 'The avatar sits directly on the cover. JPG, PNG and WebP are supported.',
        'preview' => 'TEAM PREVIEW',
        'change_cover' => 'Change cover',
        'recommended' => 'Recommended',
        'cover_hint' => '1600 × 500 pixels · maximum 4 MB',
        'avatar' => 'TEAM AVATAR',
        'avatar_hint' => 'A clear logo or short mark works best.',
        'change_avatar' => 'Change avatar',
        'clear' => 'Easy to recognize',
        'clear_text' => 'Do not make the logo or initials too small.',
        'cover_avatar' => 'Avatar on cover',
        'cover_avatar_text' => 'The team avatar remains visible on the cover.',
        'safe' => 'Community safe',
        'safe_text' => 'No abusive or third-party content.',
        'hunt_profile' => 'HUNT PROFILE',
        'focus' => 'Playstyle and focus',
        'focus_text' => 'These details help other hunters understand your team.',
        'select' => 'Please select',
        'platform' => 'Platform',
        'playstyle' => 'Playstyle',
        'region' => 'Region',
        'language' => 'Language',
        'profile_fill' => 'Complete the team profile',
        'profile_fill_text' => 'Complete details improve discovery in team search.',
        'visibility' => 'VISIBILITY',
        'visibility_title' => 'Who can see and join the team?',
        'visibility_text' => 'Visibility and recruiting can be configured separately.',
        'team_visibility' => 'TEAM VISIBILITY',
        'public_private' => 'Public or private',
        'public' => 'Public',
        'public_text' => 'The team appears in search and public areas.',
        'private' => 'Private',
        'private_text' => 'Only active members can see the team profile and content.',
        'recruiting' => 'RECRUITING',
        'new_members' => 'New members',
        'open' => 'Recruiting open',
        'open_text' => 'Interested hunters can send join requests.',
        'closed' => 'Recruiting closed',
        'closed_text' => 'New join requests are temporarily disabled.',
        'archive' => 'Archive team',
        'archive_text' => 'The team page is hidden. Existing content stays available for possible restoration.',
        'archive_confirm' => 'Archive this team now?',
        'ready' => 'Ready to save',
        'profile_is' => 'The team profile is',
        'done' => 'complete.',
        'discard_all' => 'Discard all changes',
        'new_team' => 'Create new team',
        'live' => 'LIVE PREVIEW',
        'your_team' => 'Your team',
        'your_team_label' => 'YOUR TEAM',
        'visibility_label' => 'Visibility',
        'recruiting_label' => 'Recruiting',
        'captain' => 'Captain',
        'members' => 'Members',
        'posts' => 'Posts',
        'sessions' => 'Sessions',
        'preview_hint' => 'The preview updates while typing.',
        'complete_label' => 'Complete',
        'incomplete_label' => 'Incomplete',
        'leave_warning' => 'You have unsaved team changes.',
        'name_required' => 'Please enter a team name.',
        'discard_message' => 'Changes discarded.',
        'nearly' => 'Only a few details are missing.',
        'incomplete' => 'Complete team profile and media.',
        'platform_open' => 'Platform open',
        'region_open' => 'Region open',
        'language_open' => 'Language open',
        'playstyle_open' => 'Playstyle open',
        'fallback_tagline' => 'Your team tagline',
        'fallback_description' => 'Briefly describe what your team stands for.',
    ] : [
        'eyebrow' => 'HNT.ROCKS TEAM',
        'title' => 'Team bearbeiten',
        'subtitle' => 'Passe Teamprofil, Hunt-Ausrichtung, Medien und Sichtbarkeit von :team an.',
        'clean' => 'Keine offenen Änderungen',
        'dirty' => 'Ungespeicherte Änderungen',
        'saving' => 'Wird gespeichert …',
        'view_team' => 'Team ansehen',
        'areas' => 'DEINE BEREICHE',
        'content' => 'Teaminhalt',
        'general' => 'Allgemein',
        'general_small' => 'Name, Tagline und Beschreibung',
        'media' => 'Medien',
        'media_small' => 'Teamavatar und Titelbild',
        'profile' => 'Teamprofil',
        'profile_small' => 'Plattform und Spielstil',
        'settings' => 'Sichtbarkeit',
        'settings_small' => 'Öffentlichkeit und Recruiting',
        'complete' => 'Teamprofil vollständig',
        'complete_hint' => 'Alle Teamangaben sind vollständig.',
        'edit' => 'TEAM BEARBEITEN',
        'discard' => 'Verwerfen',
        'save' => 'Änderungen speichern',
        'team_info' => 'TEAMINFO',
        'public_info' => 'Öffentliche Teaminformationen',
        'public_info_text' => 'Name, Tagline und Beschreibung erscheinen auf der Teamseite und in der Teamsuche.',
        'name' => 'Teamname',
        'shortcode' => 'Teamkürzel',
        'shortcode_hint' => 'Wird automatisch aus dem Teamnamen erzeugt.',
        'tagline' => 'Tagline',
        'description' => 'Teambeschreibung',
        'characters' => 'Zeichen',
        'team_media' => 'TEAMMEDIEN',
        'avatar_cover' => 'Avatar und Titelbild',
        'media_text' => 'Der Avatar liegt direkt auf dem Titelbild. JPG, PNG und WebP werden unterstützt.',
        'preview' => 'TEAMVORSCHAU',
        'change_cover' => 'Titelbild ändern',
        'recommended' => 'Empfohlen',
        'cover_hint' => '1600 × 500 Pixel · maximal 4 MB',
        'avatar' => 'TEAMAVATAR',
        'avatar_hint' => 'Ein klares Logo oder Kürzel funktioniert am besten.',
        'change_avatar' => 'Avatar ändern',
        'clear' => 'Klar erkennbar',
        'clear_text' => 'Logo und Kürzel nicht zu klein gestalten.',
        'cover_avatar' => 'Avatar im Cover',
        'cover_avatar_text' => 'Der Teamavatar bleibt auf dem Titelbild sichtbar.',
        'safe' => 'Community-tauglich',
        'safe_text' => 'Keine fremden oder beleidigenden Inhalte.',
        'hunt_profile' => 'HUNT-PROFIL',
        'focus' => 'Spielweise und Ausrichtung',
        'focus_text' => 'Diese Angaben helfen anderen Huntern, euer Team richtig einzuordnen.',
        'select' => 'Bitte auswählen',
        'platform' => 'Plattform',
        'playstyle' => 'Spielstil',
        'region' => 'Region',
        'language' => 'Sprache',
        'profile_fill' => 'Teamprofil vollständig ausfüllen',
        'profile_fill_text' => 'Vollständige Angaben verbessern die Auffindbarkeit in der Teamsuche.',
        'visibility' => 'SICHTBARKEIT',
        'visibility_title' => 'Wer darf das Team sehen und beitreten?',
        'visibility_text' => 'Öffentlichkeit und Recruiting sind voneinander getrennt einstellbar.',
        'team_visibility' => 'TEAMSICHTBARKEIT',
        'public_private' => 'Öffentlich oder privat',
        'public' => 'Öffentlich',
        'public_text' => 'Das Team erscheint in Suche und öffentlichen Bereichen.',
        'private' => 'Privat',
        'private_text' => 'Nur aktive Mitglieder können Teamprofil und Inhalte sehen.',
        'recruiting' => 'RECRUITING',
        'new_members' => 'Neue Mitglieder',
        'open' => 'Recruiting offen',
        'open_text' => 'Interessierte Hunter dürfen Beitrittsanfragen senden.',
        'closed' => 'Recruiting geschlossen',
        'closed_text' => 'Neue Beitrittsanfragen werden vorübergehend deaktiviert.',
        'archive' => 'Team archivieren',
        'archive_text' => 'Die Teamseite wird ausgeblendet. Bestehende Inhalte bleiben für eine spätere Wiederherstellung erhalten.',
        'archive_confirm' => 'Dieses Team jetzt archivieren?',
        'ready' => 'Bereit zum Speichern',
        'profile_is' => 'Das Teamprofil ist zu',
        'done' => 'vollständig.',
        'discard_all' => 'Alle Änderungen verwerfen',
        'new_team' => 'Neues Team erstellen',
        'live' => 'LIVE-VORSCHAU',
        'your_team' => 'Dein Team',
        'your_team_label' => 'DEIN TEAM',
        'visibility_label' => 'Sichtbarkeit',
        'recruiting_label' => 'Recruiting',
        'captain' => 'Captain',
        'members' => 'Mitglieder',
        'posts' => 'Beiträge',
        'sessions' => 'Sessions',
        'preview_hint' => 'Die Vorschau aktualisiert sich direkt während der Eingabe.',
        'complete_label' => 'Vollständig',
        'incomplete_label' => 'Unvollständig',
        'leave_warning' => 'Du hast ungespeicherte Teamänderungen.',
        'name_required' => 'Bitte gib einen Teamnamen ein.',
        'discard_message' => 'Änderungen verworfen.',
        'nearly' => 'Nur noch wenige Angaben fehlen.',
        'incomplete' => 'Vervollständige Teamprofil und Medien.',
        'platform_open' => 'Plattform offen',
        'region_open' => 'Region offen',
        'language_open' => 'Sprache offen',
        'playstyle_open' => 'Spielstil offen',
        'fallback_tagline' => 'Deine Team-Tagline',
        'fallback_description' => 'Beschreibe kurz, wofür dein Team steht.',
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
<link href="{{ asset('assets/themes/hnt_preview/dashboard-teams/team-form-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/team-form-live.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="team-edit">
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
@include('themes.hnt_preview.partials.icons')
<main class="app-shell team-form-page-shell">
@include('themes.hnt_preview.partials.header')

<section class="team-form-stage">
<div class="team-form-scroll" id="teamFormScroll">
<section class="profile-edit-heading team-form-heading">
<div>
<span>{{ $copy['eyebrow'] }}</span>
<h1>{{ $copy['title'] }}</h1>
<p>{{ str_replace(':team', $team->name, $copy['subtitle']) }}</p>
</div>
<div class="profile-edit-heading-actions">
<span class="save-state" id="teamSaveState"><i></i>{{ $copy['clean'] }}</span>
<a href="{{ route('teams.show', $team) }}">{{ $copy['view_team'] }} <svg><use href="#i-arrow"></use></svg></a>
</div>
</section>

@if(session('status'))
<div class="profile-edit-flash success team-form-flash" role="status">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="profile-edit-flash danger team-form-flash" role="alert">{{ $errors->first() }}</div>
@endif

<section class="profile-edit-workspace team-form-workspace">
<aside class="profile-edit-nav-card team-form-nav-card">
<header>
<div><span>{{ $copy['areas'] }}</span><h2>{{ $copy['content'] }}</h2></div>
<strong id="teamCompletionNav">{{ $completion }}%</strong>
</header>
<div class="profile-edit-nav-list" role="tablist">
<button class="active" data-team-form-tab="general" data-title="{{ $copy['general'] }}" type="button">
<svg><use href="#i-users"></use></svg>
<span><strong>{{ $copy['general'] }}</strong><small>{{ $copy['general_small'] }}</small></span>
<i id="teamSectionGeneral">{{ $generalCompletion }}%</i>
</button>
<button data-team-form-tab="media" data-title="{{ $copy['media'] }}" type="button">
<svg><use href="#i-image"></use></svg>
<span><strong>{{ $copy['media'] }}</strong><small>{{ $copy['media_small'] }}</small></span>
<i id="teamSectionMedia">{{ $mediaCompletion }}%</i>
</button>
<button data-team-form-tab="profile" data-title="{{ $copy['profile'] }}" type="button">
<svg><use href="#i-sliders"></use></svg>
<span><strong>{{ $copy['profile'] }}</strong><small>{{ $copy['profile_small'] }}</small></span>
<i id="teamSectionProfile">{{ $profileCompletion }}%</i>
</button>
<button data-team-form-tab="settings" data-title="{{ $copy['settings'] }}" type="button">
<svg><use href="#i-eye"></use></svg>
<span><strong>{{ $copy['settings'] }}</strong><small>{{ $copy['settings_small'] }}</small></span>
<i id="teamSectionSettings">100%</i>
</button>
</div>
<div class="profile-edit-nav-progress">
<div><span>{{ $copy['complete'] }}</span><strong id="teamCompletionText">{{ $completion }}%</strong></div>
<i><b id="teamCompletionBar" style="width:{{ $completion }}%"></b></i>
<small id="teamCompletionHint">{{ $completion === 100 ? $copy['complete_hint'] : ($completion >= 70 ? $copy['nearly'] : $copy['incomplete']) }}</small>
</div>
</aside>

<section class="profile-edit-form-card team-form-card">
<header class="profile-edit-form-head">
<div><span>{{ $copy['edit'] }}</span><h2 id="teamFormPanelTitle">{{ $copy['general'] }}</h2></div>
<div>
<button id="teamDiscardTop" type="button">{{ $copy['discard'] }}</button>
<button class="primary" id="teamSaveTop" type="button">{{ $copy['save'] }}</button>
</div>
</header>

<form id="teamEditForm"
      method="post"
      action="{{ route('teams.update', $team) }}"
      enctype="multipart/form-data"
      data-avatar-url="{{ $avatarUrl }}"
      data-cover-url="{{ $coverUrl }}"
      data-has-avatar="{{ $hasAvatar ? '1' : '0' }}"
      data-has-cover="{{ $hasCover ? '1' : '0' }}"
      data-clean-label="{{ $copy['clean'] }}"
      data-dirty-label="{{ $copy['dirty'] }}"
      data-saving-label="{{ $copy['saving'] }}"
      data-general-label="{{ $copy['general'] }}"
      data-leave-warning="{{ $copy['leave_warning'] }}"
      data-name-required="{{ $copy['name_required'] }}"
      data-discard-message="{{ $copy['discard_message'] }}"
      data-complete-label="{{ $copy['complete_hint'] }}"
      data-nearly-label="{{ $copy['nearly'] }}"
      data-incomplete-label="{{ $copy['incomplete'] }}"
      data-profile-complete-label="{{ $copy['complete_label'] }}"
      data-profile-incomplete-label="{{ $copy['incomplete_label'] }}"
      data-public-label="{{ $copy['public'] }}"
      data-private-label="{{ $copy['private'] }}"
      data-open-label="{{ $isEnglish ? 'Open' : 'Offen' }}"
      data-closed-label="{{ $isEnglish ? 'Closed' : 'Geschlossen' }}"
      data-platform-open-label="{{ $copy['platform_open'] }}"
      data-region-open-label="{{ $copy['region_open'] }}"
      data-language-open-label="{{ $copy['language_open'] }}"
      data-playstyle-open-label="{{ $copy['playstyle_open'] }}"
      data-new-team-label="{{ $team->name }}"
      data-tagline-fallback="{{ $copy['fallback_tagline'] }}"
      data-description-fallback="{{ $copy['fallback_description'] }}">
@csrf
@method('PUT')

<section class="profile-edit-panel active" data-team-form-panel="general">
<div class="profile-edit-section-intro">
<span>{{ $copy['team_info'] }}</span>
<h3>{{ $copy['public_info'] }}</h3>
<p>{{ $copy['public_info_text'] }}</p>
</div>
<div class="profile-edit-field-grid">
<label class="profile-edit-field">
<span>{{ $copy['name'] }}</span>
<input id="teamName" maxlength="80" name="name" placeholder="{{ $copy['name'] }}" required value="{{ $nameValue }}">
<small><b id="teamNameCount">{{ \Illuminate\Support\Str::length($nameValue) }}</b> / 80 {{ $copy['characters'] }}</small>
@error('name')<em>{{ $message }}</em>@enderror
</label>
<label class="profile-edit-field">
<span>{{ $copy['shortcode'] }}</span>
<input class="team-form-readonly" id="teamShortcode" disabled value="{{ $teamInitials }}">
<small>{{ $copy['shortcode_hint'] }}</small>
</label>
<label class="profile-edit-field full">
<span>{{ $copy['tagline'] }}</span>
<input id="teamTagline" maxlength="140" name="tagline" placeholder="{{ $copy['fallback_tagline'] }}" value="{{ $taglineValue }}">
<small><b id="teamTaglineCount">{{ \Illuminate\Support\Str::length((string) $taglineValue) }}</b> / 140 {{ $copy['characters'] }}</small>
@error('tagline')<em>{{ $message }}</em>@enderror
</label>
<label class="profile-edit-field full">
<span>{{ $copy['description'] }}</span>
<textarea id="teamDescription" maxlength="2500" name="description" placeholder="{{ $copy['fallback_description'] }}" rows="8">{{ $descriptionValue }}</textarea>
<small><b id="teamDescriptionCount">{{ \Illuminate\Support\Str::length((string) $descriptionValue) }}</b> / 2500 {{ $copy['characters'] }}</small>
@error('description')<em>{{ $message }}</em>@enderror
</label>
</div>
</section>

<section class="profile-edit-panel" data-team-form-panel="media" hidden>
<div class="profile-edit-section-intro">
<span>{{ $copy['team_media'] }}</span>
<h3>{{ $copy['avatar_cover'] }}</h3>
<p>{{ $copy['media_text'] }}</p>
</div>
<div class="profile-media-layout team-media-layout">
<article class="profile-cover-editor">
<div class="profile-cover-preview team-cover-editor-preview {{ $hasCover ? 'has-current-cover' : '' }}"
     id="teamCoverEditorPreview"
     @if($hasCover) style="background-image:linear-gradient(135deg,rgba(32,33,30,.22),rgba(32,33,30,.42)),url('{{ $coverUrl }}')" @endif>
<div class="team-cover-editor-identity">
<div class="team-form-avatar-monogram" id="teamEditorAvatarMonogram" @if($hasAvatar) hidden @endif>{{ $teamInitials }}</div>
<img alt="{{ $team->name }}" id="teamEditorAvatarImage" src="{{ $avatarUrl }}" @if(!$hasAvatar) hidden @endif>
<div><span>{{ $copy['preview'] }}</span><strong id="teamEditorCoverName">{{ $nameValue }}</strong><small id="teamEditorCoverTagline">{{ $taglineValue ?: $copy['fallback_tagline'] }}</small></div>
</div>
<label for="teamCoverInput"><svg><use href="#i-image"></use></svg>{{ $copy['change_cover'] }}</label>
</div>
<input accept="image/jpeg,image/png,image/webp" hidden id="teamCoverInput" name="cover" type="file">
@error('cover')<em>{{ $message }}</em>@enderror
<div class="profile-media-note"><span>{{ $copy['recommended'] }}</span><p>{{ $copy['cover_hint'] }}</p></div>
</article>
<article class="profile-avatar-editor team-avatar-editor">
<div class="team-avatar-editor-image">
<div class="team-form-avatar-monogram" id="teamAvatarEditorMonogram" @if($hasAvatar) hidden @endif>{{ $teamInitials }}</div>
<img alt="{{ $team->name }}" id="teamAvatarEditorImage" src="{{ $avatarUrl }}" @if(!$hasAvatar) hidden @endif>
</div>
<div>
<span>{{ $copy['avatar'] }}</span>
<h3 id="teamAvatarEditorName">{{ $nameValue }}</h3>
<p>{{ $copy['avatar_hint'] }}</p>
<label for="teamAvatarInput">{{ $copy['change_avatar'] }}</label>
<input accept="image/jpeg,image/png,image/webp" hidden id="teamAvatarInput" name="avatar" type="file">
@error('avatar')<em>{{ $message }}</em>@enderror
</div>
</article>
</div>
<div class="profile-media-guidelines">
<article><span>01</span><div><strong>{{ $copy['clear'] }}</strong><small>{{ $copy['clear_text'] }}</small></div></article>
<article><span>02</span><div><strong>{{ $copy['cover_avatar'] }}</strong><small>{{ $copy['cover_avatar_text'] }}</small></div></article>
<article><span>03</span><div><strong>{{ $copy['safe'] }}</strong><small>{{ $copy['safe_text'] }}</small></div></article>
</div>
</section>

<section class="profile-edit-panel" data-team-form-panel="profile" hidden>
<div class="profile-edit-section-intro">
<span>{{ $copy['hunt_profile'] }}</span>
<h3>{{ $copy['focus'] }}</h3>
<p>{{ $copy['focus_text'] }}</p>
</div>
<div class="profile-edit-field-grid">
<label class="profile-edit-field">
<span>{{ $copy['platform'] }}</span>
<select id="teamPlatform" name="platform">
<option value="">{{ $copy['select'] }}</option>
@foreach($platformOptions as $option)
<option value="{{ $option }}" @selected($platformValue === $option)>{{ $option }}</option>
@endforeach
</select>
@error('platform')<em>{{ $message }}</em>@enderror
</label>
<label class="profile-edit-field">
<span>{{ $copy['playstyle'] }}</span>
<select id="teamPlaystyle" name="playstyle">
<option value="">{{ $copy['select'] }}</option>
@foreach($playstyleOptions as $option)
<option value="{{ $option }}" @selected($playstyleValue === $option)>{{ $option }}</option>
@endforeach
</select>
@error('playstyle')<em>{{ $message }}</em>@enderror
</label>
<label class="profile-edit-field">
<span>{{ $copy['region'] }}</span>
<select id="teamRegion" name="region">
<option value="">{{ $copy['select'] }}</option>
@foreach($regionOptions as $option)
<option value="{{ $option }}" @selected($regionValue === $option)>{{ $option }}</option>
@endforeach
</select>
@error('region')<em>{{ $message }}</em>@enderror
</label>
<label class="profile-edit-field">
<span>{{ $copy['language'] }}</span>
<select id="teamLanguage" name="language">
<option value="">{{ $copy['select'] }}</option>
@foreach($languageOptions as $option)
<option value="{{ $option }}" @selected($languageValue === $option)>{{ $option }}</option>
@endforeach
</select>
@error('language')<em>{{ $message }}</em>@enderror
</label>
</div>
<article class="profile-setting-row">
<div class="profile-setting-icon"><svg><use href="#i-users"></use></svg></div>
<div><strong>{{ $copy['profile_fill'] }}</strong><small>{{ $copy['profile_fill_text'] }}</small></div>
<span class="team-form-status-chip {{ $profileCompletion === 100 ? 'complete' : '' }}" id="teamProfileStatusChip">{{ $profileCompletion === 100 ? $copy['complete_label'] : $copy['incomplete_label'] }}</span>
</article>
</section>

<section class="profile-edit-panel" data-team-form-panel="settings" hidden>
<div class="profile-edit-section-intro">
<span>{{ $copy['visibility'] }}</span>
<h3>{{ $copy['visibility_title'] }}</h3>
<p>{{ $copy['visibility_text'] }}</p>
</div>
<div class="team-form-setting-group">
<header><span>{{ $copy['team_visibility'] }}</span><h3>{{ $copy['public_private'] }}</h3></header>
<div class="profile-visibility-options">
<label>
<input name="visibility" type="radio" value="public" @checked($visibilityValue === 'public')>
<span class="visibility-icon"><svg><use href="#i-eye"></use></svg></span>
<div><strong>{{ $copy['public'] }}</strong><small>{{ $copy['public_text'] }}</small></div>
<i><svg><use href="#i-check"></use></svg></i>
</label>
<label>
<input name="visibility" type="radio" value="private" @checked($visibilityValue === 'private')>
<span class="visibility-icon"><svg><use href="#i-user"></use></svg></span>
<div><strong>{{ $copy['private'] }}</strong><small>{{ $copy['private_text'] }}</small></div>
<i><svg><use href="#i-check"></use></svg></i>
</label>
</div>
@error('visibility')<em>{{ $message }}</em>@enderror
</div>

<div class="team-form-setting-group">
<header><span>{{ $copy['recruiting'] }}</span><h3>{{ $copy['new_members'] }}</h3></header>
<div class="profile-visibility-options">
<label>
<input name="recruitment_status" type="radio" value="open" @checked($recruitmentValue === 'open')>
<span class="visibility-icon recruiting-open"><svg><use href="#i-users"></use></svg></span>
<div><strong>{{ $copy['open'] }}</strong><small>{{ $copy['open_text'] }}</small></div>
<i><svg><use href="#i-check"></use></svg></i>
</label>
<label>
<input name="recruitment_status" type="radio" value="closed" @checked($recruitmentValue === 'closed')>
<span class="visibility-icon"><svg><use href="#i-x"></use></svg></span>
<div><strong>{{ $copy['closed'] }}</strong><small>{{ $copy['closed_text'] }}</small></div>
<i><svg><use href="#i-check"></use></svg></i>
</label>
</div>
@error('recruitment_status')<em>{{ $message }}</em>@enderror
</div>

<article class="team-form-danger-card">
<span><svg><use href="#i-x"></use></svg></span>
<div><strong>{{ $copy['archive'] }}</strong><p>{{ $copy['archive_text'] }}</p></div>
@if($isOwner)
<button type="submit" form="teamArchiveForm">{{ $copy['archive'] }}</button>
@else
<button type="button" disabled>{{ $copy['archive'] }}</button>
@endif
</article>
</section>

<footer class="profile-edit-save-footer">
<div><strong>{{ $copy['ready'] }}</strong><span>{{ $copy['profile_is'] }} <b id="teamCompletionFooter">{{ $completion }}%</b> {{ $copy['done'] }}</span></div>
<div>
<a class="team-form-secondary-link" href="{{ route('teams.create') }}">{{ $copy['new_team'] }}</a>
<a href="{{ route('teams.show', $team) }}">{{ $copy['discard_all'] }}</a>
<button type="submit">{{ $copy['save'] }}</button>
</div>
</footer>
</form>

@if($isOwner)
<form class="team-danger-form" id="teamArchiveForm" method="post" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm(@js($copy['archive_confirm']))">
@csrf
@method('DELETE')
</form>
@endif
</section>

<aside class="profile-live-preview-card team-live-preview-card">
<header>
<div><span>{{ $copy['live'] }}</span><h2>{{ $copy['your_team'] }}</h2></div>
<a aria-label="{{ $copy['view_team'] }}" href="{{ route('teams.show', $team) }}"><svg><use href="#i-arrow"></use></svg></a>
</header>
<div class="team-live-cover {{ $hasCover ? 'has-current-cover' : '' }}"
     id="teamLiveCover"
     @if($hasCover) style="background-image:linear-gradient(135deg,rgba(32,33,30,.22),rgba(32,33,30,.42)),url('{{ $coverUrl }}')" @endif>
<div class="team-live-cover-lines"></div>
<div class="team-live-cover-identity">
<div class="team-form-avatar-monogram" id="teamLiveAvatarMonogram" @if($hasAvatar) hidden @endif>{{ $teamInitials }}</div>
<img alt="{{ $team->name }}" id="teamLiveAvatarImage" src="{{ $avatarUrl }}" @if(!$hasAvatar) hidden @endif>
<div><span>{{ $copy['your_team_label'] }}</span><strong id="teamLiveName">{{ $nameValue }}</strong><small id="teamLiveTagline">{{ $taglineValue ?: $copy['fallback_tagline'] }}</small></div>
</div>
</div>
<p class="team-live-description" id="teamLiveDescription">{{ $descriptionValue ?: $copy['fallback_description'] }}</p>
<div class="profile-live-tags team-live-tags">
<span id="teamLivePlatform">{{ $platformValue ?: $copy['platform_open'] }}</span>
<span id="teamLiveRegion">{{ $regionValue ?: $copy['region_open'] }}</span>
<span id="teamLiveLanguage">{{ $languageValue ?: $copy['language_open'] }}</span>
<span id="teamLivePlaystyle">{{ $playstyleValue ?: $copy['playstyle_open'] }}</span>
</div>
<div class="profile-live-info">
<article><span>{{ $copy['visibility_label'] }}</span><strong id="teamLiveVisibility">{{ $visibilityValue === 'private' ? $copy['private'] : $copy['public'] }}</strong></article>
<article><span>{{ $copy['recruiting_label'] }}</span><strong id="teamLiveRecruitment">{{ $recruitmentValue === 'closed' ? ($isEnglish ? 'Closed' : 'Geschlossen') : ($isEnglish ? 'Open' : 'Offen') }}</strong></article>
<article><span>{{ $copy['captain'] }}</span><strong>{{ $ownerName }}</strong></article>
</div>
<div class="profile-live-stats">
<span><strong>{{ (int) ($team->members_count ?? 0) }}</strong><small>{{ $copy['members'] }}</small></span>
<span><strong>{{ (int) ($team->posts_count ?? 0) }}</strong><small>{{ $copy['posts'] }}</small></span>
<span><strong>{{ (int) ($team->sessions_count ?? 0) }}</strong><small>{{ $copy['sessions'] }}</small></span>
</div>
<div class="profile-live-completion">
<div><span>{{ $copy['complete'] }}</span><strong id="teamPreviewCompletionText">{{ $completion }}%</strong></div>
<i><b id="teamPreviewCompletionBar" style="width:{{ $completion }}%"></b></i>
<small>{{ $copy['preview_hint'] }}</small>
</div>
</aside>
</section>
</div>
</section>

<div class="toast" id="toast"></div>
</main>

<script>
window.HNT_DASHBOARD_HEADER_ENDPOINT = @json(route('feed.index'));
window.HNT_PREVIEW_LOCALE = @json(str_replace('_', '-', app()->getLocale()));
window.HNT_PREVIEW_USER_ID = @json(auth()->id());
window.HNT_PREVIEW_LIVE_BADGES = {
  endpoint: @json(route('socialite.header.live-badges')),
  notificationsEndpoint: @json(route('socialite.header.notifications')),
  messagesEndpoint: @json(route('socialite.header.messages')),
  friendRequestsEndpoint: @json(route('socialite.header.friend-requests')),
  interval: 8000,
  shellInterval: 5000,
  chatTabInterval: 4500
};
</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-teams/team-edit-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/team-edit-live.js')) ?: time() }}" defer></script>
</body>
</html>
