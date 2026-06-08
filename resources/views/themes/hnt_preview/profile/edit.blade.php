@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.edit_profile'))
@section('app_window_class', 'profile-window')
@section('main_class', 'profile-edit-main')

@php
    $profile = $user->profile;
    $avatarUrl = $user->avatarUrl();
    $coverUrl = $user->coverUrl();
    $completion = \App\Support\ProfileCompletion::score($user);
    $visibility = old('profile_visibility', $profile?->profile_visibility ?? 'public');
    $platform = old('platform', $profile?->platform);
    $playstyle = old('playstyle', $profile?->playstyle);
    $region = old('region', $profile?->region);
    $language = old('language', $profile?->language);
    $isLfgAvailable = (bool) old('is_lfg_available', $profile?->is_lfg_available);
@endphp

@section('content')
    <section class="edit-profile-shell">
        @if (session('status'))
            <div class="profile-edit-status profile-edit-status-success" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="profile-edit-status profile-edit-status-danger" role="alert">
                {{ __('ui.profile_validation_error') }}
            </div>
        @endif

        <form id="hnt-preview-profile-edit-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" data-hnt-preview-profile-edit-form>
            @csrf
            @method('PUT')

            <section class="edit-profile-head settings-card">
                <div class="edit-head-profile">
                    <div class="edit-avatar-wrap">
                        <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="avatar edit-avatar" data-profile-edit-avatar-preview>
                        <label class="edit-avatar-btn" for="profile-avatar-file" aria-label="{{ __('ui.change_avatar') }}">
                            <i class="ph ph-camera" aria-hidden="true"></i>
                        </label>
                    </div>
                    <div>
                        <h1>{{ old('name', $user->name) }}</h1>
                        <p>{{ '@'.($user->username ?: \Illuminate\Support\Str::slug($user->name ?: 'hunter')) }}</p>
                    </div>
                </div>

                <div class="settings-tabs profile-edit-tabs" role="tablist" aria-label="{{ __('ui.edit_profile') }}">
                    <button type="button" class="active" role="tab" aria-selected="true" data-profile-edit-tab="general" aria-controls="profile-edit-general">{{ __('ui.preview_profile_edit_tab_general') }}</button>
                    <button type="button" role="tab" aria-selected="false" data-profile-edit-tab="media" aria-controls="profile-edit-media">{{ __('ui.preview_profile_edit_tab_media') }}</button>
                    <button type="button" role="tab" aria-selected="false" data-profile-edit-tab="hunt" aria-controls="profile-edit-hunt">{{ __('ui.preview_profile_edit_tab_hunt') }}</button>
                    <button type="button" role="tab" aria-selected="false" data-profile-edit-tab="social" aria-controls="profile-edit-social">{{ __('ui.profile_social_links') }}</button>
                    <button type="button" role="tab" aria-selected="false" data-profile-edit-tab="privacy" aria-controls="profile-edit-privacy">{{ __('ui.privacy') }}</button>
                </div>
            </section>

            <section class="edit-form-card profile-edit-content-panel">
                <div class="profile-edit-form">
                    <div id="profile-edit-general" class="profile-edit-tab-panel is-active" role="tabpanel" data-profile-edit-panel="general">
                        <div class="profile-edit-section-title">
                            <span>{{ __('ui.my_profile') }}</span>
                            <h2>{{ __('ui.profile_info') }}</h2>
                            <p>{{ __('ui.profile_edit_banner_text') }}</p>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-name">{{ __('ui.account_full_name') }}</label>
                            <div>
                                <input id="profile-name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="80" required>
                                @error('name')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-email">{{ __('ui.email') }}</label>
                            <div>
                                <input id="profile-email" type="email" value="{{ $user->email }}" disabled>
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-headline">{{ __('ui.profile_headline') }}</label>
                            <div>
                                <input id="profile-headline" name="headline" type="text" value="{{ old('headline', $profile?->headline) }}" maxlength="120">
                                @error('headline')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row align-top">
                            <label for="profile-bio">{{ __('ui.profile_bio') }}</label>
                            <div>
                                <textarea id="profile-bio" name="bio" rows="6" maxlength="1200">{{ old('bio', $profile?->bio) }}</textarea>
                                @error('bio')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div id="profile-edit-media" class="profile-edit-tab-panel" role="tabpanel" data-profile-edit-panel="media" hidden>
                        <div class="profile-edit-section-title">
                            <span>{{ __('ui.preview_profile_edit_tab_media') }}</span>
                            <h2>{{ __('ui.preview_profile_edit_media_title') }}</h2>
                            <p>{{ __('ui.preview_profile_edit_media_text') }}</p>
                        </div>

                        <div class="profile-edit-media-preview">
                            <div class="profile-edit-cover-preview" style="background-image: linear-gradient(180deg, rgba(26,26,24,.08), rgba(26,26,24,.82)), url('{{ $coverUrl }}');" data-profile-edit-cover-preview>
                                <span>{{ __('ui.change_cover') }}</span>
                            </div>
                            <div class="profile-edit-file-actions">
                                <input id="profile-avatar-file" class="profile-edit-file-input" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" data-profile-edit-file="avatar">
                                <input id="profile-cover-file" class="profile-edit-file-input" name="cover" type="file" accept="image/jpeg,image/png,image/webp" data-profile-edit-file="cover">
                                <label class="btn-create" for="profile-avatar-file">{{ __('ui.change_avatar') }}</label>
                                <label class="btn-create" for="profile-cover-file">{{ __('ui.change_cover') }}</label>
                            </div>
                            <p class="profile-edit-file-note" data-profile-edit-file-note>{{ __('ui.preview_profile_edit_no_file') }}</p>
                            @error('avatar')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            @error('cover')<p class="profile-edit-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div id="profile-edit-hunt" class="profile-edit-tab-panel" role="tabpanel" data-profile-edit-panel="hunt" hidden>
                        <div class="profile-edit-section-title">
                            <span>Hunt</span>
                            <h2>{{ __('ui.preview_profile_edit_hunt_title') }}</h2>
                            <p>{{ __('ui.preview_profile_edit_hunt_text') }}</p>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-platform">{{ __('ui.platform') }}</label>
                            <div>
                                <select id="profile-platform" name="platform">
                                    <option value="">{{ __('ui.members_platform_open') }}</option>
                                    @foreach (['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                                        <option value="{{ $option }}" @selected($platform === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('platform')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-region">{{ __('ui.region') }}</label>
                            <div>
                                <select id="profile-region" name="region">
                                    <option value="">{{ __('ui.preview_profile_edit_region_open') }}</option>
                                    @foreach (['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                                        <option value="{{ $option }}" @selected($region === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('region')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-playstyle">{{ __('ui.playstyle') }}</label>
                            <div>
                                <select id="profile-playstyle" name="playstyle">
                                    <option value="">{{ __('ui.members_playstyle_open') }}</option>
                                    <option value="Tactical" @selected($playstyle === 'Tactical')>{{ __('ui.playstyle_tactical') }}</option>
                                    <option value="Aggressive" @selected($playstyle === 'Aggressive')>{{ __('ui.playstyle_aggressive') }}</option>
                                    <option value="Beginner friendly" @selected($playstyle === 'Beginner friendly')>{{ __('ui.playstyle_beginner') }}</option>
                                    <option value="Casual" @selected($playstyle === 'Casual')>Casual</option>
                                    <option value="Competitive" @selected($playstyle === 'Competitive')>Competitive</option>
                                </select>
                                @error('playstyle')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-language">{{ __('ui.language') }}</label>
                            <div>
                                <select id="profile-language" name="language">
                                    <option value="">{{ __('ui.preview_profile_edit_language_open') }}</option>
                                    @foreach (['Deutsch', 'English', 'Français', 'Español', 'Other'] as $option)
                                        <option value="{{ $option }}" @selected($language === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('language')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-hunt-role">{{ __('ui.hunt_role') }}</label>
                            <div>
                                <input id="profile-hunt-role" name="hunt_role" type="text" value="{{ old('hunt_role', $profile?->hunt_role) }}" maxlength="60">
                                @error('hunt_role')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-discord">Discord</label>
                            <div>
                                <input id="profile-discord" name="discord_name" type="text" value="{{ old('discord_name', $profile?->discord_name) }}" maxlength="80">
                                @error('discord_name')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row align-top">
                            <label>{{ __('ui.profile_lfg_available') }}</label>
                            <div class="profile-edit-checks">
                                <label class="hnt-lfg-check">
                                    <input type="checkbox" name="is_lfg_available" value="1" @checked($isLfgAvailable)>
                                    <span>{{ __('ui.preview_profile_edit_lfg_available_hint') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="profile-edit-social" class="profile-edit-tab-panel" role="tabpanel" data-profile-edit-panel="social" hidden>
                        <div class="profile-edit-section-title">
                            <span>{{ __('ui.profile_social_links') }}</span>
                            <h2>{{ __('ui.social_stream') }}</h2>
                            <p>{{ __('ui.preview_profile_edit_social_text') }}</p>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-steam">Steam</label>
                            <div>
                                <input id="profile-steam" name="steam_url" type="url" value="{{ old('steam_url', $profile?->steam_url) }}" maxlength="255" placeholder="https://steamcommunity.com/id/...">
                                @error('steam_url')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-twitch">Twitch</label>
                            <div>
                                <input id="profile-twitch" name="twitch_url" type="url" value="{{ old('twitch_url', $profile?->twitch_url) }}" maxlength="255" placeholder="https://www.twitch.tv/...">
                                @error('twitch_url')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-youtube">YouTube</label>
                            <div>
                                <input id="profile-youtube" name="youtube_url" type="url" value="{{ old('youtube_url', $profile?->youtube_url) }}" maxlength="255" placeholder="https://www.youtube.com/@...">
                                @error('youtube_url')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div id="profile-edit-privacy" class="profile-edit-tab-panel" role="tabpanel" data-profile-edit-panel="privacy" hidden>
                        <div class="profile-edit-section-title">
                            <span>{{ __('ui.privacy') }}</span>
                            <h2>{{ __('ui.profile_visibility') }}</h2>
                            <p>{{ __('ui.preview_profile_edit_privacy_text') }}</p>
                        </div>

                        <div class="form-row compact">
                            <label for="profile-visibility">{{ __('ui.profile_visibility') }}</label>
                            <div>
                                <select id="profile-visibility" name="profile_visibility" required>
                                    <option value="public" @selected($visibility === 'public')>{{ __('ui.visibility_public') }}</option>
                                    <option value="registered" @selected($visibility === 'registered')>{{ __('ui.visibility_registered') }}</option>
                                    <option value="private" @selected($visibility === 'private')>{{ __('ui.visibility_private') }}</option>
                                </select>
                                @error('profile_visibility')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="profile-edit-save-card">
                        <strong>{{ __('ui.preview_profile_edit_ready_title') }}</strong>
                        <span>{{ __('ui.preview_profile_edit_ready_text', ['completion' => $completion]) }}</span>
                    </div>

                    <div class="form-actions profile-edit-actions">
                        <a href="{{ route('profile.show') }}" class="btn-create">{{ __('ui.discard_all') }}</a>
                        <button type="submit" class="btn-create">{{ __('ui.save_changes') }}</button>
                    </div>
                </div>
            </section>
        </form>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-hnt-preview-profile-edit-form]');
    var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-profile-edit-tab]'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-profile-edit-panel]'));
    var avatarInput = document.querySelector('[data-profile-edit-file="avatar"]');
    var coverInput = document.querySelector('[data-profile-edit-file="cover"]');
    var avatarPreview = document.querySelector('[data-profile-edit-avatar-preview]');
    var coverPreview = document.querySelector('[data-profile-edit-cover-preview]');
    var fileNote = document.querySelector('[data-profile-edit-file-note]');
    var selectedText = @json(__('ui.preview_profile_edit_file_selected'));
    var noFileText = @json(__('ui.preview_profile_edit_no_file'));

    function activateTab(key) {
        tabs.forEach(function (tab) {
            var isActive = tab.getAttribute('data-profile-edit-tab') === key;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            var isActive = panel.getAttribute('data-profile-edit-panel') === key;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activateTab(tab.getAttribute('data-profile-edit-tab'));
        });
    });

    if (form) {
        var errorPanel = form.querySelector('.profile-edit-tab-panel .profile-edit-error');
        if (errorPanel) {
            var parentPanel = errorPanel.closest('[data-profile-edit-panel]');
            if (parentPanel) {
                activateTab(parentPanel.getAttribute('data-profile-edit-panel'));
            }
        }
    }

    function setFileNote(input) {
        if (!fileNote || !input || !input.files || !input.files[0]) return;
        fileNote.textContent = selectedText.replace(':name', input.files[0].name);
    }

    function previewImage(input, callback) {
        if (!input || !input.files || !input.files[0]) return;
        var url = URL.createObjectURL(input.files[0]);
        callback(url);
        setFileNote(input);
    }

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function () {
            previewImage(avatarInput, function (url) {
                avatarPreview.src = url;
            });
        });
    }

    if (coverInput && coverPreview) {
        coverInput.addEventListener('change', function () {
            previewImage(coverInput, function (url) {
                coverPreview.style.backgroundImage = "linear-gradient(180deg, rgba(26,26,24,.08), rgba(26,26,24,.82)), url('" + url + "')";
            });
        });
    }

    if (fileNote && !fileNote.textContent.trim()) {
        fileNote.textContent = noFileText;
    }
});
</script>
@endpush
