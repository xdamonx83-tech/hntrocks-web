@php
    $isEnglish = app()->getLocale() === 'en';
    $settings = is_array($cup->settings) ? $cup->settings : [];
    $platformOptions = ['PC' => 'PC', 'PlayStation' => 'PlayStation 5', 'Xbox' => 'Xbox Series X|S'];
    $selectedPlatforms = old('allowed_platforms', data_get($settings, 'platform_gate.allowed_platforms', []));
    $selectedPlatforms = is_array($selectedPlatforms) ? $selectedPlatforms : [];
    $rulesPreset = old('rules_preset', data_get($settings, 'rules_preset', 'classic_bounty'));
    $defaultAiPreset = match ($rulesPreset) {
        'console_mini_fair' => 'console_platform',
        'summer_trio_first_trophy' => 'awards_first_trophy',
        default => 'classic_summary',
    };
    $aiPreset = old('ai_prompt_preset', data_get($settings, 'ai_prompt_preset', $defaultAiPreset));
    $maxUploads = old('max_submissions_per_participant', data_get($settings, 'submission_limit.max_uploads_per_participant'));
    $maxScored = old('max_scored_submissions_per_participant', data_get($settings, 'submission_limit.max_scored_runs_per_participant'));
    $profileRequired = (bool) old('require_profile_complete', data_get($settings, 'participation_requirements.profile_complete', false));
    $minCommunityActions = old('min_community_actions', data_get($settings, 'participation_requirements.min_community_actions', 0));
    $coverUrl = asset('assets/themes/hnt_preview/assets/quick-cup-cover.jpg');
    $titleValue = old('title', '');
    $summaryDe = old('summary_de', '');
    $summaryEn = old('summary_en', '');
    $descriptionDe = old('cup_description_de', '');
    $descriptionEn = old('cup_description_en', '');
    $teamSize = (int) old('team_size', 1);
    $maxTeams = old('max_teams', '');
    $region = old('region', 'EU');
    $language = old('language', $isEnglish ? 'English' : 'Deutsch / English');
    $status = old('status', 'planned');
    $visibility = old('visibility', 'public');
    $previewPlatforms = collect($selectedPlatforms)->map(fn ($value) => $platformOptions[$value] ?? $value)->implode(' / ');
    $previewPlatforms = $previewPlatforms !== '' ? $previewPlatforms : ($isEnglish ? 'All platforms' : 'Alle Plattformen');
    $statusLabels = [
        'planned' => __('hnt_cup_create.planned'),
        'active' => __('hnt_cup_create.active'),
        'finished' => __('hnt_cup_create.finished'),
        'archived' => __('hnt_cup_create.archived'),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'de' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>{{ __('hnt_cup_create.title') }} · HNT.ROCKS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/theme-colors.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/theme-colors.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/theme-page-polish.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/theme-page-polish.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile-edit/profile-edit-live.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-create-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-create-live.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="cup-create">
@include('themes.hnt_preview.partials.icons')
<main class="app-shell cup-create-page-shell"
      data-hnt-dashboard-header
      data-cups-active-url="{{ route('cups.index', ['status' => 'active']) }}"
      data-cups-mine-url="{{ route('cups.index', ['mine' => 1]) }}"
      data-cups-submissions-url="{{ route('cups.index', ['mine' => 1]).'#submissions' }}"
      data-cups-hall-url="{{ route('hall-of-fame.index') }}">
@include('themes.hnt_preview.partials.header')
<div class="cup-create-scroll" id="cupCreateScroll">
<section class="profile-edit-heading cup-create-heading">
    <div>
        <span>{{ __('hnt_cup_create.eyebrow') }}</span>
        <h1>{{ __('hnt_cup_create.title') }}</h1>
        <p>{{ __('hnt_cup_create.intro') }}</p>
    </div>
    <div class="profile-edit-heading-actions">
        <span class="save-state" id="cupSaveState"><i></i><span>{{ __('hnt_cup_create.clean') }}</span></span>
        <a href="{{ route('cups.index') }}">{{ __('hnt_cup_create.overview') }} <svg><use href="#i-arrow"></use></svg></a>
    </div>
</section>

@if($errors->any())
<div class="profile-edit-flash danger" role="alert">
    <strong>{{ __('hnt_cup_create.validation') }}</strong>
    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<section class="profile-edit-workspace cup-create-workspace">
<aside class="profile-edit-nav-card cup-create-nav-card" aria-label="{{ __('hnt_cup_create.setup') }}">
    <header><div><span>{{ __('hnt_cup_create.areas') }}</span><h2>{{ __('hnt_cup_create.setup') }}</h2></div><strong id="cupCompletionNav">0%</strong></header>
    <div class="profile-edit-nav-list cup-create-nav-list" role="tablist">
        @foreach([
            ['general','i-folder','general','general_small'],
            ['schedule','i-calendar','schedule','schedule_small'],
            ['scoring','i-trophy','scoring','scoring_small'],
            ['participation','i-users','participation','participation_small'],
            ['prizes','i-coins','prizes','prizes_small'],
            ['submissions','i-upload','submissions','submissions_small'],
            ['publish','i-globe','publish','publish_small'],
        ] as $index => $tab)
        <button type="button" class="{{ $index === 0 ? 'active' : '' }}" data-cup-create-tab="{{ $tab[0] }}" data-title="{{ __('hnt_cup_create.'.$tab[2]) }}" role="tab" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
            <svg><use href="#{{ $tab[1] }}"></use></svg>
            <span><strong>{{ __('hnt_cup_create.'.$tab[2]) }}</strong><small>{{ __('hnt_cup_create.'.$tab[3]) }}</small></span>
            <i data-cup-section-completion="{{ $tab[0] }}">0%</i>
        </button>
        @endforeach
    </div>
    <div class="profile-edit-nav-progress"><div><span>{{ __('hnt_cup_create.complete') }}</span><strong id="cupCompletionText">0%</strong></div><i><b id="cupCompletionBar" style="width:0%"></b></i><small>{{ __('hnt_cup_create.complete_hint') }}</small></div>
</aside>

<section class="profile-edit-form-card cup-create-form-card">
<header class="profile-edit-form-head">
    <div><span>{{ __('hnt_cup_create.eyebrow') }}</span><h2 id="cupCreatePanelTitle">{{ __('hnt_cup_create.general') }}</h2></div>
    <div><button id="cupDiscardTop" type="button">{{ __('hnt_cup_create.discard') }}</button><button class="primary" id="cupSaveTop" type="button">{{ __('hnt_cup_create.save') }}</button></div>
</header>

<form id="cupCreateForm" method="POST" action="{{ route('cups.store') }}" enctype="multipart/form-data" novalidate>
@csrf
<section class="profile-edit-panel active" data-cup-create-panel="general">
    <div class="profile-edit-section-intro"><span>{{ __('hnt_cup_create.eyebrow') }}</span><h3>{{ __('hnt_cup_create.public_info') }}</h3><p>{{ __('hnt_cup_create.public_info_text') }}</p></div>
    <div class="profile-edit-field-grid">
        <label class="profile-edit-field full"><span>{{ __('hnt_cup_create.cup_name') }}</span><input id="cupTitle" name="title" maxlength="140" required value="{{ $titleValue }}"><small><b data-count-for="cupTitle">{{ mb_strlen($titleValue) }}</b> / 140</small>@error('title')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.summary_de') }}</span><textarea id="cupSummaryDe" name="summary_de" maxlength="255" rows="4">{{ $summaryDe }}</textarea><small><b data-count-for="cupSummaryDe">{{ mb_strlen($summaryDe) }}</b> / 255</small>@error('summary_de')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.summary_en') }}</span><textarea id="cupSummaryEn" name="summary_en" maxlength="255" rows="4">{{ $summaryEn }}</textarea><small><b data-count-for="cupSummaryEn">{{ mb_strlen($summaryEn) }}</b> / 255</small>@error('summary_en')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.description_de') }}</span><textarea id="cupDescriptionDe" name="cup_description_de" maxlength="12000" rows="8">{{ $descriptionDe }}</textarea>@error('cup_description_de')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.description_en') }}</span><textarea id="cupDescriptionEn" name="cup_description_en" maxlength="12000" rows="8">{{ $descriptionEn }}</textarea>@error('cup_description_en')<em data-field-error>{{ $message }}</em>@enderror</label>
    </div>
    <div class="cup-create-cover-editor">
        <div class="cup-create-cover-preview" id="cupCoverPreview" style="background-image:linear-gradient(180deg,rgba(20,20,18,.05),rgba(20,20,18,.68)),url('{{ $coverUrl }}')">
            <div><span>HNT.ROCKS CUP</span><strong id="cupCoverTitle">{{ $titleValue !== '' ? mb_strtoupper($titleValue) : 'NEW COMMUNITY CUP' }}</strong><small id="cupCoverMeta">{{ $previewPlatforms }}</small></div>
            <button type="button" data-cup-cover-trigger><svg><use href="#i-image"></use></svg>{{ __('hnt_cup_create.cover_change') }}</button>
        </div>
        <input id="cupCoverInput" name="cover" type="file" accept="image/jpeg,image/png,image/webp" hidden>
        <div class="profile-media-note"><span>{{ __('hnt_cup_create.cover') }}</span><p>{{ __('hnt_cup_create.cover_hint') }}</p></div>
        @error('cover')<em class="cup-create-cover-error" data-field-error>{{ $message }}</em>@enderror
    </div>
</section>

<section class="profile-edit-panel" data-cup-create-panel="schedule" hidden>
    <div class="profile-edit-section-intro"><span>{{ __('hnt_cup_create.schedule') }}</span><h3>{{ __('hnt_cup_create.schedule_title') }}</h3><p>{{ __('hnt_cup_create.schedule_text') }}</p></div>
    <div class="profile-edit-field-grid">
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.registration_open') }}</span><input id="registrationOpens" name="registration_opens_at" type="datetime-local" value="{{ old('registration_opens_at') }}">@error('registration_opens_at')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.registration_close') }}</span><input id="registrationCloses" name="registration_closes_at" type="datetime-local" value="{{ old('registration_closes_at') }}">@error('registration_closes_at')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.starts') }}</span><input id="cupStarts" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}">@error('starts_at')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.ends') }}</span><input id="cupEnds" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}">@error('ends_at')<em data-field-error>{{ $message }}</em>@enderror</label>
    </div>
    <div class="cup-create-timeline" aria-hidden="true"><article><i></i><strong>01</strong><span>{{ __('hnt_cup_create.registration_open') }}</span></article><article><i></i><strong>02</strong><span>{{ __('hnt_cup_create.registration_close') }}</span></article><article><i></i><strong>03</strong><span>{{ __('hnt_cup_create.starts') }}</span></article><article><i></i><strong>04</strong><span>{{ __('hnt_cup_create.ends') }}</span></article></div>
</section>

<section class="profile-edit-panel" data-cup-create-panel="scoring" hidden>
    <div class="profile-edit-section-intro"><span>{{ __('hnt_cup_create.scoring') }}</span><h3>{{ __('hnt_cup_create.scoring_title') }}</h3><p>{{ __('hnt_cup_create.scoring_text') }}</p></div>
    <div class="profile-edit-field-grid">
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.rules_preset') }}</span><select id="cupRulesPreset" name="rules_preset">@foreach(\App\Models\Cup::rulesPresetOptions() as $value => $label)<option value="{{ $value }}" @selected($rulesPreset === $value)>{{ $label }}</option>@endforeach</select>@error('rules_preset')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.ai_preset') }}</span><select id="cupAiPreset" name="ai_prompt_preset">@foreach(\App\Models\Cup::aiPromptPresetOptions() as $value => $label)<option value="{{ $value }}" @selected($aiPreset === $value)>{{ $label }}</option>@endforeach</select>@error('ai_prompt_preset')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.rules_de') }}</span><textarea id="cupRulesDe" name="rules_de" maxlength="6000" rows="8">{{ old('rules_de') }}</textarea>@error('rules_de')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.rules_en') }}</span><textarea id="cupRulesEn" name="rules_en" maxlength="6000" rows="8">{{ old('rules_en') }}</textarea>@error('rules_en')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.scoring_de') }}</span><textarea id="cupScoringDe" name="scoring_rules_de" maxlength="6000" rows="6">{{ old('scoring_rules_de') }}</textarea>@error('scoring_rules_de')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.scoring_en') }}</span><textarea id="cupScoringEn" name="scoring_rules_en" maxlength="6000" rows="6">{{ old('scoring_rules_en') }}</textarea>@error('scoring_rules_en')<em data-field-error>{{ $message }}</em>@enderror</label>
    </div>
</section>

<section class="profile-edit-panel" data-cup-create-panel="participation" hidden>
    <div class="profile-edit-section-intro"><span>{{ __('hnt_cup_create.participation') }}</span><h3>{{ __('hnt_cup_create.participation_title') }}</h3><p>{{ __('hnt_cup_create.participation_text') }}</p></div>
    <div class="profile-edit-field-grid">
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.team_size') }}</span><select id="cupTeamSize" name="team_size" required>@foreach([1 => 'Solo', 2 => 'Duo', 3 => 'Trio', 4 => 'Quartet'] as $value => $label)<option value="{{ $value }}" @selected($teamSize === $value)>{{ $label }}</option>@endforeach</select>@error('team_size')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.max_teams') }}</span><input id="cupMaxTeams" name="max_teams" type="number" min="2" max="256" value="{{ $maxTeams }}">@error('max_teams')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.region') }}</span><select id="cupRegion" name="region"><option value="">—</option>@foreach(['EU','US East','US West','Asia','Oceania'] as $option)<option value="{{ $option }}" @selected($region === $option)>{{ $option }}</option>@endforeach</select>@error('region')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.language') }}</span><input id="cupLanguage" name="language" maxlength="60" value="{{ $language }}">@error('language')<em data-field-error>{{ $message }}</em>@enderror</label>
    </div>
    <fieldset class="cup-create-platforms"><legend>{{ __('hnt_cup_create.platforms') }}</legend><div>@foreach($platformOptions as $value => $label)<label><input type="checkbox" name="allowed_platforms[]" value="{{ $value }}" @checked(in_array($value, $selectedPlatforms, true))><span>{{ $label }}</span><i><svg><use href="#i-check"></use></svg></i></label>@endforeach</div>@error('allowed_platforms')<em data-field-error>{{ $message }}</em>@enderror</fieldset>
</section>

<section class="profile-edit-panel" data-cup-create-panel="prizes" hidden>
    <div class="profile-edit-section-intro"><span>{{ __('hnt_cup_create.prizes') }}</span><h3>{{ __('hnt_cup_create.prizes_title') }}</h3><p>{{ __('hnt_cup_create.prizes_text') }}</p></div>
    <div class="profile-edit-field-grid cup-create-prize-grid">
        @foreach([
            ['prize_first_de','first_de',3],['prize_first_en','first_en',3],
            ['prize_second_de','second_de',3],['prize_second_en','second_en',3],
            ['prize_third_de','third_de',3],['prize_third_en','third_en',3],
            ['prize_note_de','prize_note_de',4],['prize_note_en','prize_note_en',4],
            ['cashout_note_de','cashout_de',3],['cashout_note_en','cashout_en',3],
            ['hall_of_fame_note_de','hall_de',3],['hall_of_fame_note_en','hall_en',3],
        ] as $field)
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.'.$field[1]) }}</span><textarea name="{{ $field[0] }}" maxlength="{{ str_contains($field[0], 'prize_note') ? 3000 : (str_contains($field[0], 'prize_') ? 1000 : 2000) }}" rows="{{ $field[2] }}">{{ old($field[0]) }}</textarea>@error($field[0])<em data-field-error>{{ $message }}</em>@enderror</label>
        @endforeach
    </div>
</section>

<section class="profile-edit-panel" data-cup-create-panel="submissions" hidden>
    <div class="profile-edit-section-intro"><span>{{ __('hnt_cup_create.submissions') }}</span><h3>{{ __('hnt_cup_create.submission_title') }}</h3><p>{{ __('hnt_cup_create.submission_text') }}</p></div>
    <div class="profile-edit-field-grid">
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.max_uploads') }}</span><input id="cupMaxUploads" name="max_submissions_per_participant" type="number" min="0" max="99" value="{{ $maxUploads }}">@error('max_submissions_per_participant')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.max_scored') }}</span><input id="cupMaxScored" name="max_scored_submissions_per_participant" type="number" min="0" max="99" value="{{ $maxScored }}">@error('max_scored_submissions_per_participant')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field full"><span>{{ __('hnt_cup_create.community_actions') }}</span><input id="cupCommunityActions" name="min_community_actions" type="number" min="0" max="20" value="{{ $minCommunityActions }}">@error('min_community_actions')<em data-field-error>{{ $message }}</em>@enderror</label>
    </div>
    <article class="profile-setting-row"><div class="profile-setting-icon"><svg><use href="#i-user"></use></svg></div><div><strong>{{ __('hnt_cup_create.profile_required') }}</strong><small>{{ __('hnt_cup_create.profile_required_text') }}</small></div><label class="profile-toggle"><input type="hidden" name="require_profile_complete" value="0"><input id="cupProfileRequired" name="require_profile_complete" value="1" type="checkbox" @checked($profileRequired)><i></i></label></article>
</section>

<section class="profile-edit-panel" data-cup-create-panel="publish" hidden>
    <div class="profile-edit-section-intro"><span>{{ __('hnt_cup_create.publish') }}</span><h3>{{ __('hnt_cup_create.publish_title') }}</h3><p>{{ __('hnt_cup_create.publish_text') }}</p></div>
    <div class="profile-edit-field-grid">
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.status') }}</span><select id="cupStatus" name="status" required>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select>@error('status')<em data-field-error>{{ $message }}</em>@enderror</label>
        <label class="profile-edit-field"><span>{{ __('hnt_cup_create.visibility') }}</span><select id="cupVisibility" name="visibility" required><option value="public" @selected($visibility === 'public')>{{ __('hnt_cup_create.public') }}</option><option value="private" @selected($visibility === 'private')>{{ __('hnt_cup_create.private') }}</option></select>@error('visibility')<em data-field-error>{{ $message }}</em>@enderror</label>
    </div>
    <div class="cup-create-publish-checklist"><article><span><svg><use href="#i-check"></use></svg></span><div><strong>{{ __('hnt_cup_create.public_info') }}</strong><small>{{ __('hnt_cup_create.public_info_text') }}</small></div></article><article><span><svg><use href="#i-trophy"></use></svg></span><div><strong>{{ __('hnt_cup_create.rules_ready') }}</strong><small>{{ __('hnt_cup_create.scoring_text') }}</small></div></article><article><span><svg><use href="#i-calendar"></use></svg></span><div><strong>{{ __('hnt_cup_create.schedule') }}</strong><small>{{ __('hnt_cup_create.schedule_text') }}</small></div></article></div>
</section>

<footer class="profile-edit-save-footer"><div><strong>{{ __('hnt_cup_create.create') }}</strong><span>{{ __('hnt_cup_create.complete') }}: <b id="cupCompletionFooter">0%</b></span></div><div><a href="{{ route('cups.index') }}">{{ __('hnt_cup_create.discard') }}</a><button type="submit" data-cup-create-submit>{{ __('hnt_cup_create.save') }}</button></div></footer>
</form>
</section>

<aside class="profile-live-preview-card cup-create-preview-card">
    <header><div><span>{{ __('hnt_cup_create.live_preview') }}</span><h2>{{ __('hnt_cup_create.cup_card') }}</h2></div><a aria-label="{{ __('hnt_cup_create.overview') }}" href="{{ route('cups.index') }}"><svg><use href="#i-arrow"></use></svg></a></header>
    <article class="cup-create-preview-cover" id="cupLiveCover" style="background-image:linear-gradient(180deg,rgba(20,20,18,.05),rgba(20,20,18,.72)),url('{{ $coverUrl }}')"><span>HNT.ROCKS CUP</span><strong id="cupLiveTitle">{{ $titleValue !== '' ? mb_strtoupper($titleValue) : 'NEW COMMUNITY CUP' }}</strong><small id="cupLivePlatforms">{{ $previewPlatforms }}</small></article>
    <div class="cup-create-preview-body">
        <span class="cup-create-preview-status"><i></i><b id="cupLiveStatus">{{ $statusLabels[$status] ?? $status }}</b></span>
        <h3 id="cupLiveName">{{ $titleValue !== '' ? $titleValue : __('hnt_cup_create.title') }}</h3>
        <p id="cupLiveSummary">{{ $summaryDe !== '' ? $summaryDe : __('hnt_cup_create.public_info_text') }}</p>
        <div class="cup-create-preview-tags"><span id="cupLiveTeamSize">{{ $teamSize === 1 ? 'Solo' : $teamSize }}</span><span id="cupLiveRegion">{{ $region ?: '—' }}</span><span id="cupLiveVisibility">{{ $visibility === 'public' ? __('hnt_cup_create.public') : __('hnt_cup_create.private') }}</span></div>
        <div class="cup-create-preview-stats"><article><strong id="cupLiveMaxTeams">{{ $maxTeams !== '' ? $maxTeams : '∞' }}</strong><span>{{ __('hnt_cup_create.teams') }}</span></article><article><strong id="cupLiveHunters">{{ $teamSize }}</strong><span>{{ __('hnt_cup_create.hunters') }}</span></article><article><strong id="cupLiveUploads">{{ $maxUploads ?: '∞' }}</strong><span>{{ __('hnt_cup_create.uploads') }}</span></article></div>
        <div class="cup-create-preview-rule"><span>{{ __('hnt_cup_create.rules_preset') }}</span><strong id="cupLiveRules">{{ \App\Models\Cup::rulesPresetOptions()[$rulesPreset] ?? $rulesPreset }}</strong></div>
    </div>
    <div class="profile-live-completion"><div><span>{{ __('hnt_cup_create.complete') }}</span><strong id="cupLiveCompletion">0%</strong></div><i><b id="cupLiveCompletionBar" style="width:0%"></b></i><small>{{ __('hnt_cup_create.draft_hint') }}</small></div>
    <button class="cup-create-preview-button" id="cupPreviewSubmit" type="button">{{ __('hnt_cup_create.save') }} <svg><use href="#i-arrow"></use></svg></button>
</aside>
</section>
</div>
<div class="toast" id="toast"></div>
</main>
<script>
window.HNT_DASHBOARD_HEADER_ENDPOINT = '/feed';
window.HNT_CUP_CREATE = {{ \Illuminate\Support\Js::from([
    'clean' => __('hnt_cup_create.clean'),
    'dirty' => __('hnt_cup_create.dirty'),
    'leaveWarning' => __('hnt_cup_create.leave_warning'),
    'publicLabel' => __('hnt_cup_create.public'),
    'privateLabel' => __('hnt_cup_create.private'),
    'statusLabels' => $statusLabels,
    'platformLabels' => $platformOptions,
]) }};
</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-create-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-create-live.js')) ?: time() }}"></script>
</body>
</html>
