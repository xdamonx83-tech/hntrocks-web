@php
    $isEnglish = app()->getLocale() === 'en';
    $t = static fn (string $de, string $en): string => $isEnglish ? $en : $de;

    $nameValue = old('name', '');
    $taglineValue = old('tagline', '');
    $descriptionValue = old('description', '');
    $platformValue = old('platform', '');
    $playstyleValue = old('playstyle', '');
    $regionValue = old('region', 'EU');
    $languageValue = old('language', 'Deutsch / Englisch');
    $visibilityValue = old('visibility', 'public');
    $recruitmentValue = old('recruitment_status', 'open');

    $teamInitials = \Illuminate\Support\Str::of($nameValue)
        ->trim()
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');
    $teamInitials = $teamInitials !== '' ? $teamInitials : 'NT';

    $generalCompletion = (int) round(collect([$nameValue, $taglineValue, $descriptionValue])->filter(fn ($value) => filled($value))->count() / 3 * 100);
    $mediaCompletion = 0;
    $profileCompletion = (int) round(collect([$platformValue, $playstyleValue, $regionValue, $languageValue])->filter(fn ($value) => filled($value))->count() / 4 * 100);
    $settingsCompletion = 100;
    $completion = (int) round(
        $generalCompletion * .38 +
        $mediaCompletion * .16 +
        $profileCompletion * .30 +
        $settingsCompletion * .16
    );

    $platformOptions = collect(['PC', 'PlayStation', 'Xbox', 'Crossplay', $platformValue])->filter()->unique()->values();
    $playstyleOptions = collect(['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich', $playstyleValue])->filter()->unique()->values();
    $regionOptions = collect(['EU', 'US East', 'US West', 'Asia', 'Oceania', $regionValue])->filter()->unique()->values();
    $languageOptions = collect(['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig', $languageValue])->filter()->unique()->values();
    $ownerName = auth()->user()?->name ?: (auth()->user()?->username ?: 'HNT Hunter');
@endphp
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'de' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>{{ $t('Team erstellen', 'Create team') }} · HNT.ROCKS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-live.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-teams/team-form-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/team-form-live.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="team-create">
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
@include('themes.hnt_preview.partials.icons')
<main class="app-shell team-form-page-shell">
@include('themes.hnt_preview.partials.header')

<section class="team-form-stage">
<div class="team-form-scroll" id="teamFormScroll">
<section class="profile-edit-heading team-form-heading">
<div>
<span>HNT.ROCKS TEAM</span>
<h1>{{ $t('Team erstellen', 'Create team') }}</h1>
<p>{{ $t('Gründe ein eigenständiges Community-Team und richte das öffentliche Teamprofil ein.', 'Create a community team and set up its public profile.') }}</p>
</div>
<div class="profile-edit-heading-actions">
<span class="save-state" id="teamSaveState"><i></i>{{ $t('Keine offenen Änderungen', 'No unsaved changes') }}</span>
<a href="{{ route('teams.index') }}">{{ $t('Teamübersicht', 'Team directory') }} <svg><use href="#i-arrow"></use></svg></a>
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
<div><span>{{ $t('DEINE BEREICHE', 'YOUR SECTIONS') }}</span><h2>{{ $t('Teaminhalt', 'Team content') }}</h2></div>
<strong id="teamCompletionNav">{{ $completion }}%</strong>
</header>
<div class="profile-edit-nav-list" role="tablist">
<button class="active" data-team-form-tab="general" data-title="{{ $t('Allgemein', 'General') }}" type="button">
<svg><use href="#i-users"></use></svg>
<span><strong>{{ $t('Allgemein', 'General') }}</strong><small>{{ $t('Name, Tagline und Beschreibung', 'Name, tagline and description') }}</small></span>
<i id="teamSectionGeneral">{{ $generalCompletion }}%</i>
</button>
<button data-team-form-tab="media" data-title="{{ $t('Medien', 'Media') }}" type="button">
<svg><use href="#i-image"></use></svg>
<span><strong>{{ $t('Medien', 'Media') }}</strong><small>{{ $t('Teamavatar und Titelbild', 'Team avatar and cover') }}</small></span>
<i id="teamSectionMedia">{{ $mediaCompletion }}%</i>
</button>
<button data-team-form-tab="profile" data-title="{{ $t('Teamprofil', 'Team profile') }}" type="button">
<svg><use href="#i-sliders"></use></svg>
<span><strong>{{ $t('Teamprofil', 'Team profile') }}</strong><small>{{ $t('Plattform und Spielstil', 'Platform and playstyle') }}</small></span>
<i id="teamSectionProfile">{{ $profileCompletion }}%</i>
</button>
<button data-team-form-tab="settings" data-title="{{ $t('Sichtbarkeit', 'Visibility') }}" type="button">
<svg><use href="#i-eye"></use></svg>
<span><strong>{{ $t('Sichtbarkeit', 'Visibility') }}</strong><small>{{ $t('Öffentlichkeit und Recruiting', 'Public access and recruiting') }}</small></span>
<i id="teamSectionSettings">100%</i>
</button>
</div>
<div class="profile-edit-nav-progress">
<div><span>{{ $t('Teamprofil vollständig', 'Team profile complete') }}</span><strong id="teamCompletionText">{{ $completion }}%</strong></div>
<i><b id="teamCompletionBar" style="width:{{ $completion }}%"></b></i>
<small id="teamCompletionHint">{{ $completion >= 70 ? $t('Nur noch wenige Angaben fehlen.', 'Only a few details are missing.') : $t('Vervollständige Teamprofil und Medien.', 'Complete team profile and media.') }}</small>
</div>
</aside>

<section class="profile-edit-form-card team-form-card">
<header class="profile-edit-form-head">
<div><span>{{ $t('TEAM ERSTELLEN', 'CREATE TEAM') }}</span><h2 id="teamFormPanelTitle">{{ $t('Allgemein', 'General') }}</h2></div>
<div>
<button id="teamDiscardTop" type="button">{{ $t('Verwerfen', 'Discard') }}</button>
<button class="primary" id="teamSaveTop" type="button">{{ $t('Team erstellen', 'Create team') }}</button>
</div>
</header>

<form id="teamEditForm"
      method="post"
      action="{{ route('teams.store') }}"
      enctype="multipart/form-data"
      data-avatar-url=""
      data-cover-url=""
      data-has-avatar="0"
      data-has-cover="0"
      data-clean-label="{{ $t('Keine offenen Änderungen', 'No unsaved changes') }}"
      data-dirty-label="{{ $t('Ungespeicherte Änderungen', 'Unsaved changes') }}"
      data-saving-label="{{ $t('Team wird erstellt …', 'Creating team …') }}"
      data-general-label="{{ $t('Allgemein', 'General') }}"
      data-leave-warning="{{ $t('Du hast ungespeicherte Teamangaben.', 'You have unsaved team details.') }}"
      data-name-required="{{ $t('Bitte gib einen Teamnamen ein.', 'Please enter a team name.') }}"
      data-discard-message="{{ $t('Änderungen verworfen.', 'Changes discarded.') }}"
      data-complete-label="{{ $t('Alle Teamangaben sind vollständig.', 'All team details are complete.') }}"
      data-nearly-label="{{ $t('Nur noch wenige Angaben fehlen.', 'Only a few details are missing.') }}"
      data-incomplete-label="{{ $t('Vervollständige Teamprofil und Medien.', 'Complete team profile and media.') }}"
      data-profile-complete-label="{{ $t('Vollständig', 'Complete') }}"
      data-profile-incomplete-label="{{ $t('Unvollständig', 'Incomplete') }}"
      data-public-label="{{ $t('Öffentlich', 'Public') }}"
      data-private-label="{{ $t('Privat', 'Private') }}"
      data-open-label="{{ $t('Offen', 'Open') }}"
      data-closed-label="{{ $t('Geschlossen', 'Closed') }}"
      data-platform-open-label="{{ $t('Plattform offen', 'Platform open') }}"
      data-region-open-label="{{ $t('Region offen', 'Region open') }}"
      data-language-open-label="{{ $t('Sprache offen', 'Language open') }}"
      data-playstyle-open-label="{{ $t('Spielstil offen', 'Playstyle open') }}"
      data-new-team-label="{{ $t('Neues Team', 'New team') }}"
      data-tagline-fallback="{{ $t('Deine Team-Tagline', 'Your team tagline') }}"
      data-description-fallback="{{ $t('Beschreibe kurz, wofür dein Team steht und welche Mitspieler zu euch passen.', 'Briefly describe what your team stands for and who fits in.') }}">
@csrf

<section class="profile-edit-panel active" data-team-form-panel="general">
<div class="profile-edit-section-intro">
<span>TEAMINFO</span>
<h3>{{ $t('Öffentliche Teaminformationen', 'Public team information') }}</h3>
<p>{{ $t('Name, Tagline und Beschreibung erscheinen auf der Teamseite und in der Teamsuche.', 'Name, tagline and description appear on the team page and in team search.') }}</p>
</div>
<div class="profile-edit-field-grid">
<label class="profile-edit-field">
<span>{{ $t('Teamname', 'Team name') }}</span>
<input id="teamName" maxlength="80" name="name" placeholder="{{ $t('Zum Beispiel Night Ravens', 'For example Night Ravens') }}" required value="{{ $nameValue }}">
<small><b id="teamNameCount">{{ \Illuminate\Support\Str::length($nameValue) }}</b> / 80 {{ $t('Zeichen', 'characters') }}</small>
@error('name')<em>{{ $message }}</em>@enderror
</label>
<label class="profile-edit-field">
<span>{{ $t('Teamkürzel', 'Team initials') }}</span>
<input class="team-form-readonly" id="teamShortcode" disabled value="{{ $teamInitials }}">
<small>{{ $t('Wird automatisch aus dem Teamnamen erzeugt.', 'Generated automatically from the team name.') }}</small>
</label>
<label class="profile-edit-field full">
<span>Tagline</span>
<input id="teamTagline" maxlength="140" name="tagline" placeholder="{{ $t('Kurzer Satz über euer Team', 'A short line about your team') }}" value="{{ $taglineValue }}">
<small><b id="teamTaglineCount">{{ \Illuminate\Support\Str::length((string) $taglineValue) }}</b> / 140 {{ $t('Zeichen', 'characters') }}</small>
@error('tagline')<em>{{ $message }}</em>@enderror
</label>
<label class="profile-edit-field full">
<span>{{ $t('Teambeschreibung', 'Team description') }}</span>
<textarea id="teamDescription" maxlength="2500" name="description" placeholder="{{ $t('Beschreibe Spielweise, Zeiten und Erwartungen.', 'Describe playstyle, schedules and expectations.') }}" rows="8">{{ $descriptionValue }}</textarea>
<small><b id="teamDescriptionCount">{{ \Illuminate\Support\Str::length((string) $descriptionValue) }}</b> / 2500 {{ $t('Zeichen', 'characters') }}</small>
@error('description')<em>{{ $message }}</em>@enderror
</label>
</div>
</section>

<section class="profile-edit-panel" data-team-form-panel="media" hidden>
<div class="profile-edit-section-intro">
<span>{{ $t('TEAMMEDIEN', 'TEAM MEDIA') }}</span>
<h3>{{ $t('Avatar und Titelbild', 'Avatar and cover') }}</h3>
<p>{{ $t('Der Avatar liegt direkt auf dem Titelbild. JPG, PNG und WebP werden unterstützt.', 'The avatar appears on the cover. JPG, PNG and WebP are supported.') }}</p>
</div>
<div class="profile-media-layout team-media-layout">
<article class="profile-cover-editor">
<div class="profile-cover-preview team-cover-editor-preview" id="teamCoverEditorPreview">
<div class="team-cover-editor-identity">
<div class="team-form-avatar-monogram" id="teamEditorAvatarMonogram">{{ $teamInitials }}</div>
<img alt="" hidden id="teamEditorAvatarImage">
<div><span>{{ $t('TEAMVORSCHAU', 'TEAM PREVIEW') }}</span><strong id="teamEditorCoverName">{{ $nameValue ?: $t('Neues Team', 'New team') }}</strong><small id="teamEditorCoverTagline">{{ $taglineValue ?: $t('Deine Team-Tagline', 'Your team tagline') }}</small></div>
</div>
<label for="teamCoverInput"><svg><use href="#i-image"></use></svg>{{ $t('Titelbild ändern', 'Change cover') }}</label>
</div>
<input accept="image/jpeg,image/png,image/webp" hidden id="teamCoverInput" name="cover" type="file">
@error('cover')<em>{{ $message }}</em>@enderror
<div class="profile-media-note"><span>{{ $t('Empfohlen', 'Recommended') }}</span><p>1600 × 500 Pixel · {{ $t('maximal 4 MB', 'maximum 4 MB') }}</p></div>
</article>
<article class="profile-avatar-editor team-avatar-editor">
<div class="team-avatar-editor-image">
<div class="team-form-avatar-monogram" id="teamAvatarEditorMonogram">{{ $teamInitials }}</div>
<img alt="" hidden id="teamAvatarEditorImage">
</div>
<div>
<span>{{ $t('TEAMAVATAR', 'TEAM AVATAR') }}</span>
<h3 id="teamAvatarEditorName">{{ $nameValue ?: $t('Neues Team', 'New team') }}</h3>
<p>{{ $t('Ein klares Logo oder Kürzel funktioniert am besten.', 'A clear logo or initials work best.') }}</p>
<label for="teamAvatarInput">{{ $t('Avatar ändern', 'Change avatar') }}</label>
<input accept="image/jpeg,image/png,image/webp" hidden id="teamAvatarInput" name="avatar" type="file">
@error('avatar')<em>{{ $message }}</em>@enderror
</div>
</article>
</div>
<div class="profile-media-guidelines">
<article><span>01</span><div><strong>{{ $t('Klar erkennbar', 'Easy to recognize') }}</strong><small>{{ $t('Logo und Kürzel nicht zu klein gestalten.', 'Do not make the logo or initials too small.') }}</small></div></article>
<article><span>02</span><div><strong>{{ $t('Avatar im Cover', 'Avatar on cover') }}</strong><small>{{ $t('Der Teamavatar liegt sichtbar auf dem Titelbild.', 'The team avatar remains visible on the cover.') }}</small></div></article>
<article><span>03</span><div><strong>{{ $t('Community-tauglich', 'Community safe') }}</strong><small>{{ $t('Keine fremden oder beleidigenden Inhalte.', 'No abusive or third-party content.') }}</small></div></article>
</div>
</section>

<section class="profile-edit-panel" data-team-form-panel="profile" hidden>
<div class="profile-edit-section-intro">
<span>{{ $t('HUNT-PROFIL', 'HUNT PROFILE') }}</span>
<h3>{{ $t('Spielweise und Ausrichtung', 'Playstyle and focus') }}</h3>
<p>{{ $t('Diese Angaben helfen anderen Huntern, euer Team richtig einzuordnen.', 'These details help other hunters understand your team.') }}</p>
</div>
<div class="profile-edit-field-grid">
<label class="profile-edit-field">
<span>{{ $t('Plattform', 'Platform') }}</span>
<select id="teamPlatform" name="platform">
<option value="">{{ $t('Bitte auswählen', 'Select an option') }}</option>
@foreach($platformOptions as $option)
<option value="{{ $option }}" @selected($platformValue === $option)>{{ $option }}</option>
@endforeach
</select>
@error('platform')<em>{{ $message }}</em>@enderror
</label>
<label class="profile-edit-field">
<span>{{ $t('Spielstil', 'Playstyle') }}</span>
<select id="teamPlaystyle" name="playstyle">
<option value="">{{ $t('Bitte auswählen', 'Select an option') }}</option>
@foreach($playstyleOptions as $option)
<option value="{{ $option }}" @selected($playstyleValue === $option)>{{ $option }}</option>
@endforeach
</select>
@error('playstyle')<em>{{ $message }}</em>@enderror
</label>
<label class="profile-edit-field">
<span>{{ $t('Region', 'Region') }}</span>
<select id="teamRegion" name="region">
<option value="">{{ $t('Bitte auswählen', 'Select an option') }}</option>
@foreach($regionOptions as $option)
<option value="{{ $option }}" @selected($regionValue === $option)>{{ $option }}</option>
@endforeach
</select>
@error('region')<em>{{ $message }}</em>@enderror
</label>
<label class="profile-edit-field">
<span>{{ $t('Sprache', 'Language') }}</span>
<select id="teamLanguage" name="language">
<option value="">{{ $t('Bitte auswählen', 'Select an option') }}</option>
@foreach($languageOptions as $option)
<option value="{{ $option }}" @selected($languageValue === $option)>{{ $option }}</option>
@endforeach
</select>
@error('language')<em>{{ $message }}</em>@enderror
</label>
</div>
<article class="profile-setting-row">
<div class="profile-setting-icon"><svg><use href="#i-users"></use></svg></div>
<div><strong>{{ $t('Teamprofil vollständig ausfüllen', 'Complete the team profile') }}</strong><small>{{ $t('Vollständige Angaben verbessern die Auffindbarkeit in der Teamsuche.', 'Complete details improve visibility in team search.') }}</small></div>
<span class="team-form-status-chip {{ $profileCompletion === 100 ? 'complete' : '' }}" id="teamProfileStatusChip">{{ $profileCompletion === 100 ? $t('Vollständig', 'Complete') : $t('Unvollständig', 'Incomplete') }}</span>
</article>
</section>

<section class="profile-edit-panel" data-team-form-panel="settings" hidden>
<div class="profile-edit-section-intro">
<span>{{ $t('SICHTBARKEIT', 'VISIBILITY') }}</span>
<h3>{{ $t('Wer darf das Team sehen und beitreten?', 'Who can see and join the team?') }}</h3>
<p>{{ $t('Öffentlichkeit und Recruiting sind voneinander getrennt einstellbar.', 'Visibility and recruiting can be configured separately.') }}</p>
</div>
<div class="team-form-setting-group">
<header><span>{{ $t('TEAMSICHTBARKEIT', 'TEAM VISIBILITY') }}</span><h3>{{ $t('Öffentlich oder privat', 'Public or private') }}</h3></header>
<div class="profile-visibility-options">
<label>
<input name="visibility" type="radio" value="public" @checked($visibilityValue === 'public')>
<span class="visibility-icon"><svg><use href="#i-eye"></use></svg></span>
<div><strong>{{ $t('Öffentlich', 'Public') }}</strong><small>{{ $t('Das Team erscheint in Suche und öffentlichen Bereichen.', 'The team appears in search and public areas.') }}</small></div>
<i><svg><use href="#i-check"></use></svg></i>
</label>
<label>
<input name="visibility" type="radio" value="private" @checked($visibilityValue === 'private')>
<span class="visibility-icon"><svg><use href="#i-user"></use></svg></span>
<div><strong>{{ $t('Privat', 'Private') }}</strong><small>{{ $t('Nur aktive Mitglieder können Teamprofil und Inhalte sehen.', 'Only active members can view the team profile and content.') }}</small></div>
<i><svg><use href="#i-check"></use></svg></i>
</label>
</div>
@error('visibility')<em>{{ $message }}</em>@enderror
</div>

<div class="team-form-setting-group">
<header><span>RECRUITING</span><h3>{{ $t('Neue Mitglieder', 'New members') }}</h3></header>
<div class="profile-visibility-options">
<label>
<input name="recruitment_status" type="radio" value="open" @checked($recruitmentValue === 'open')>
<span class="visibility-icon recruiting-open"><svg><use href="#i-users"></use></svg></span>
<div><strong>{{ $t('Recruiting offen', 'Recruiting open') }}</strong><small>{{ $t('Interessierte Hunter dürfen Beitrittsanfragen senden.', 'Interested hunters may send join requests.') }}</small></div>
<i><svg><use href="#i-check"></use></svg></i>
</label>
<label>
<input name="recruitment_status" type="radio" value="closed" @checked($recruitmentValue === 'closed')>
<span class="visibility-icon"><svg><use href="#i-x"></use></svg></span>
<div><strong>{{ $t('Recruiting geschlossen', 'Recruiting closed') }}</strong><small>{{ $t('Neue Beitrittsanfragen werden vorübergehend deaktiviert.', 'New join requests are temporarily disabled.') }}</small></div>
<i><svg><use href="#i-check"></use></svg></i>
</label>
</div>
@error('recruitment_status')<em>{{ $message }}</em>@enderror
</div>

<article class="team-form-create-note">
<span><svg><use href="#i-check"></use></svg></span>
<div><strong>{{ $t('Du wirst automatisch Captain', 'You automatically become captain') }}</strong><p>{{ $t('Nach dem Erstellen bist du das erste Mitglied und kannst weitere Hunter einladen.', 'After creation you are the first member and can invite more hunters.') }}</p></div>
</article>
</section>

<footer class="profile-edit-save-footer">
<div><strong>{{ $t('Bereit zum Erstellen', 'Ready to create') }}</strong><span>{{ $t('Das Teamprofil ist zu', 'The team profile is') }} <b id="teamCompletionFooter">{{ $completion }}%</b> {{ $t('vollständig.', 'complete.') }}</span></div>
<div>
<a href="{{ route('teams.index') }}">{{ $t('Alle Änderungen verwerfen', 'Discard all changes') }}</a>
<button type="submit">{{ $t('Team erstellen', 'Create team') }}</button>
</div>
</footer>
</form>
</section>

<aside class="profile-live-preview-card team-live-preview-card">
<header>
<div><span>{{ $t('LIVE-VORSCHAU', 'LIVE PREVIEW') }}</span><h2>{{ $t('Dein Team', 'Your team') }}</h2></div>
<a aria-label="{{ $t('Teamübersicht ansehen', 'Open team directory') }}" href="{{ route('teams.index') }}"><svg><use href="#i-arrow"></use></svg></a>
</header>
<div class="team-live-cover" id="teamLiveCover">
<div class="team-live-cover-lines"></div>
<div class="team-live-cover-identity">
<div class="team-form-avatar-monogram" id="teamLiveAvatarMonogram">{{ $teamInitials }}</div>
<img alt="" hidden id="teamLiveAvatarImage">
<div><span>{{ $t('DEIN TEAM', 'YOUR TEAM') }}</span><strong id="teamLiveName">{{ $nameValue ?: $t('Neues Team', 'New team') }}</strong><small id="teamLiveTagline">{{ $taglineValue ?: $t('Deine Team-Tagline', 'Your team tagline') }}</small></div>
</div>
</div>
<p class="team-live-description" id="teamLiveDescription">{{ $descriptionValue ?: $t('Beschreibe kurz, wofür dein Team steht und welche Mitspieler zu euch passen.', 'Briefly describe what your team stands for and who fits in.') }}</p>
<div class="profile-live-tags team-live-tags">
<span id="teamLivePlatform">{{ $platformValue ?: $t('Plattform offen', 'Platform open') }}</span>
<span id="teamLiveRegion">{{ $regionValue ?: $t('Region offen', 'Region open') }}</span>
<span id="teamLiveLanguage">{{ $languageValue ?: $t('Sprache offen', 'Language open') }}</span>
<span id="teamLivePlaystyle">{{ $playstyleValue ?: $t('Spielstil offen', 'Playstyle open') }}</span>
</div>
<div class="profile-live-info">
<article><span>{{ $t('Sichtbarkeit', 'Visibility') }}</span><strong id="teamLiveVisibility">{{ $visibilityValue === 'private' ? $t('Privat', 'Private') : $t('Öffentlich', 'Public') }}</strong></article>
<article><span>Recruiting</span><strong id="teamLiveRecruitment">{{ $recruitmentValue === 'closed' ? $t('Geschlossen', 'Closed') : $t('Offen', 'Open') }}</strong></article>
<article><span>Captain</span><strong>{{ $ownerName }}</strong></article>
</div>
<div class="profile-live-stats">
<span><strong>1</strong><small>{{ $t('Mitglieder', 'Members') }}</small></span>
<span><strong>0</strong><small>{{ $t('Beiträge', 'Posts') }}</small></span>
<span><strong>0</strong><small>Sessions</small></span>
</div>
<div class="profile-live-completion">
<div><span>{{ $t('Teamprofil vollständig', 'Team profile complete') }}</span><strong id="teamPreviewCompletionText">{{ $completion }}%</strong></div>
<i><b id="teamPreviewCompletionBar" style="width:{{ $completion }}%"></b></i>
<small>{{ $t('Die Vorschau aktualisiert sich direkt während der Eingabe.', 'The preview updates while typing.') }}</small>
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
