@php
    $viewer = auth()->user();
    $viewer?->loadMissing('profile');
    $viewerProfile = $viewer?->profile;
    $viewerAvatar = $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $viewerCover = $viewer?->coverUrl() ?: asset('assets/vikinger/img/default-cover.svg');
    $viewerCompletion = $viewer ? \App\Support\ProfileCompletion::score($viewer) : 0;
    $profileVisibility = old('profile_visibility', $viewerProfile?->profile_visibility ?? 'public');
    $profilePlatform = old('platform', $viewerProfile?->platform);
    $profilePlaystyle = old('playstyle', $viewerProfile?->playstyle);
    $profileRegion = old('region', $viewerProfile?->region);
    $profileLanguage = old('language', $viewerProfile?->language);
    $profileIsLfgAvailable = (bool) old('is_lfg_available', $viewerProfile?->is_lfg_available);
    $reworkProfileOptions = [
        'platform' => ['PC', 'PlayStation', 'Xbox', 'Crossplay'],
        'playstyle' => [
            'Tactical' => __('ui.playstyle_tactical'),
            'Aggressive' => __('ui.playstyle_aggressive'),
            'Beginner friendly' => __('ui.playstyle_beginner'),
            'Casual' => 'Casual',
            'Competitive' => 'Competitive',
        ],
        'region' => ['EU', 'US East', 'US West', 'Asia', 'Oceania'],
        'language' => ['Deutsch', 'English', 'Francais', 'Espanol', 'Other'],
    ];
@endphp

<div
    aria-hidden="true"
    class="modal-backdrop profile-edit-backdrop"
    data-profile-edit-modal=""
    data-label-saved="{{ __('ui.profile_saved') }}"
    data-label-save-failed="{{ __('ui.profile_save_failed') }}"
    data-label-validation="{{ __('ui.profile_validation_error') }}"
>
<section aria-labelledby="profile-edit-modal-title" aria-modal="true" class="settings-modal profile-edit-modal settings-has-footer-submit" role="dialog">
<header class="settings-modal-header">
<div>
<span class="settings-eyebrow">{{ __('ui.my_profile') }}</span>
<h2 id="profile-edit-modal-title">{{ __('ui.edit_profile') }}</h2>
<p>{{ __('ui.profile_edit_modal_intro') }}</p>
</div>
<button aria-label="{{ __('ui.close') }}" class="settings-close" data-profile-edit-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<nav aria-label="{{ __('ui.edit_profile') }}" class="settings-tabs profile-edit-tabs">
<button class="is-active" data-profile-edit-tab="basic" type="button"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>{{ __('ui.profile_basic_info') }}</span></button>
<button data-profile-edit-tab="game" type="button"><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i><span>{{ __('ui.profile_game_info') }}</span></button>
<button data-profile-edit-tab="social" type="button"><i aria-hidden="true" class="ph ph-link ph-icon"></i><span>{{ __('ui.profile_social_links') }}</span></button>
<button data-profile-edit-tab="media" type="button"><i aria-hidden="true" class="ph ph-image ph-icon"></i><span>{{ __('ui.profile_media') }}</span></button>
</nav>
<form action="{{ route('profile.update') }}" data-profile-edit-form enctype="multipart/form-data" method="post" novalidate>
@csrf
@method('PUT')
<div class="settings-modal-body profile-edit-modal-body">
<div class="profile-edit-summary">
<div class="profile-edit-cover" style="background-image: linear-gradient(180deg, rgba(17,17,15,.08), rgba(17,17,15,.76)), url('{{ $viewerCover }}');" data-profile-edit-cover-preview></div>
<div class="profile-edit-summary-row">
<img alt="{{ $viewer?->name ?: 'HNT Hunter' }}" data-profile-edit-avatar-preview data-rework-profile-avatar src="{{ $viewerAvatar }}">
<div>
<strong data-profile-edit-name-preview data-rework-profile-name>{{ old('name', $viewer?->name) ?: 'HNT Hunter' }}</strong>
<span data-profile-edit-headline-preview data-rework-profile-headline>{{ old('headline', $viewerProfile?->headline) ?: __('ui.profile_no_headline') }}</span>
</div>
<em data-profile-edit-completion>{{ $viewerCompletion }}%</em>
</div>
</div>
<p class="settings-status profile-edit-status" data-profile-edit-status hidden></p>
<section class="settings-panel is-active" data-profile-edit-panel="basic">
<div class="settings-section-head"><span>{{ __('ui.my_profile') }}</span><h3>{{ __('ui.profile_basic_info') }}</h3><p>{{ __('ui.profile_completion_text') }}</p></div>
<div class="settings-form-grid">
<label><span>{{ __('ui.display_name') }}</span><input autocomplete="name" maxlength="80" name="name" required type="text" value="{{ old('name', $viewer?->name) }}"/><small class="settings-field-error" data-profile-edit-error="name"></small></label>
<label><span>{{ __('ui.profile_headline') }}</span><input maxlength="120" name="headline" type="text" value="{{ old('headline', $viewerProfile?->headline) }}"/><small class="settings-field-error" data-profile-edit-error="headline"></small></label>
<label class="profile-edit-wide"><span>{{ __('ui.profile_bio') }}</span><textarea maxlength="1200" name="bio" rows="4">{{ old('bio', $viewerProfile?->bio) }}</textarea><small class="settings-field-error" data-profile-edit-error="bio"></small></label>
<label><span>{{ __('ui.profile_visibility') }}</span><select name="profile_visibility" required><option value="public" @selected($profileVisibility === 'public')>{{ __('ui.visibility_public') }}</option><option value="registered" @selected($profileVisibility === 'registered')>{{ __('ui.visibility_registered') }}</option><option value="private" @selected($profileVisibility === 'private')>{{ __('ui.visibility_private') }}</option></select><small class="settings-field-error" data-profile-edit-error="profile_visibility"></small></label>
</div>
<label class="settings-toggle-row profile-edit-lfg" for="rework-profile-lfg"><input id="rework-profile-lfg" name="is_lfg_available" value="1" @checked($profileIsLfgAvailable) type="checkbox"/><span></span><div><strong>{{ __('ui.profile_lfg_available') }}</strong><p>{{ __('ui.preview_profile_edit_lfg_available_hint') }}</p></div></label>
</section>
<section class="settings-panel" data-profile-edit-panel="game">
<div class="settings-section-head"><span>Hunt</span><h3>{{ __('ui.profile_game_info') }}</h3><p>{{ __('ui.preview_profile_edit_hunt_text') }}</p></div>
<div class="settings-form-grid">
<label><span>{{ __('ui.platform') }}</span><select name="platform"><option value="">{{ __('ui.platform_open') }}</option>@foreach($reworkProfileOptions['platform'] as $option)<option value="{{ $option }}" @selected($profilePlatform === $option)>{{ $option }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="platform"></small></label>
<label><span>{{ __('ui.playstyle') }}</span><select name="playstyle"><option value="">{{ __('ui.playstyle_open') }}</option>@foreach($reworkProfileOptions['playstyle'] as $value => $label)<option value="{{ $value }}" @selected($profilePlaystyle === $value || $profilePlaystyle === $label)>{{ $label }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="playstyle"></small></label>
<label><span>{{ __('ui.region') }}</span><select name="region"><option value="">{{ __('ui.preview_profile_edit_region_open') }}</option>@foreach($reworkProfileOptions['region'] as $option)<option value="{{ $option }}" @selected($profileRegion === $option)>{{ $option }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="region"></small></label>
<label><span>{{ __('ui.language') }}</span><select name="language"><option value="">{{ __('ui.preview_profile_edit_language_open') }}</option>@foreach($reworkProfileOptions['language'] as $option)<option value="{{ $option }}" @selected($profileLanguage === $option)>{{ $option }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="language"></small></label>
<label><span>{{ __('ui.hunt_role') }}</span><input maxlength="60" name="hunt_role" type="text" value="{{ old('hunt_role', $viewerProfile?->hunt_role) }}"/><small class="settings-field-error" data-profile-edit-error="hunt_role"></small></label>
<label><span>Discord</span><input maxlength="80" name="discord_name" type="text" value="{{ old('discord_name', $viewerProfile?->discord_name) }}"/><small class="settings-field-error" data-profile-edit-error="discord_name"></small></label>
</div>
</section>
<section class="settings-panel" data-profile-edit-panel="social">
<div class="settings-section-head"><span>{{ __('ui.profile_social_links') }}</span><h3>{{ __('ui.profile_social_links') }}</h3><p>{{ __('ui.preview_profile_edit_social_text') }}</p></div>
<div class="settings-form-grid">
<label><span>Steam URL</span><input maxlength="255" name="steam_url" placeholder="https://steamcommunity.com/id/..." type="url" value="{{ old('steam_url', $viewerProfile?->steam_url) }}"/><small class="settings-field-error" data-profile-edit-error="steam_url"></small></label>
<label><span>Twitch URL</span><input maxlength="255" name="twitch_url" placeholder="https://www.twitch.tv/..." type="url" value="{{ old('twitch_url', $viewerProfile?->twitch_url) }}"/><small class="settings-field-error" data-profile-edit-error="twitch_url"></small></label>
<label><span>YouTube URL</span><input maxlength="255" name="youtube_url" placeholder="https://www.youtube.com/@..." type="url" value="{{ old('youtube_url', $viewerProfile?->youtube_url) }}"/><small class="settings-field-error" data-profile-edit-error="youtube_url"></small></label>
</div>
</section>
<section class="settings-panel" data-profile-edit-panel="media">
<div class="settings-section-head"><span>{{ __('ui.profile_media') }}</span><h3>{{ __('ui.profile_media') }}</h3><p>{{ __('ui.profile_upload_hint') }}</p></div>
<div class="profile-edit-upload-grid">
<label class="profile-edit-upload" for="rework-profile-avatar"><span>{{ __('ui.profile_avatar') }}</span><img alt="{{ __('ui.profile_avatar') }}" data-profile-edit-avatar-preview src="{{ $viewerAvatar }}"><input accept="image/jpeg,image/png,image/webp" id="rework-profile-avatar" name="avatar" type="file" data-profile-edit-file="avatar"><em>{{ __('ui.avatar_upload_hint') }}</em><small class="settings-field-error" data-profile-edit-error="avatar"></small></label>
<label class="profile-edit-upload" for="rework-profile-cover"><span>{{ __('ui.profile_cover') }}</span><div style="background-image: url('{{ $viewerCover }}');" data-profile-edit-cover-preview></div><input accept="image/jpeg,image/png,image/webp" id="rework-profile-cover" name="cover" type="file" data-profile-edit-file="cover"><em>{{ __('ui.cover_upload_hint') }}</em><small class="settings-field-error" data-profile-edit-error="cover"></small></label>
</div>
</section>
</div>
<footer class="settings-modal-footer"><button class="settings-cancel" data-profile-edit-modal-close="" type="button">{{ __('ui.cancel') }}</button><button class="settings-save" data-profile-edit-submit="" type="submit">{{ __('ui.save_changes') }}</button></footer>
</form>
</section>
</div>
