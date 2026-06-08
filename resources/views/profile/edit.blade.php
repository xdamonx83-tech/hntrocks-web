@extends('layouts.app')

@section('title', __('ui.edit_profile'))

@section('content')
@php
    $profile = $user->profile;
    $avatarUrl = $user->avatarUrl();
    $coverUrl = $user->coverUrl();
    $completion = \App\Support\ProfileCompletion::score($user);
    $visibility = old('profile_visibility', $profile?->profile_visibility ?? 'public');
    $platform = old('platform', $profile?->platform);
    $playstyle = old('playstyle', $profile?->playstyle);
    $isLfgAvailable = (bool) old('is_lfg_available', $profile?->is_lfg_available);
@endphp

<div class="section-banner hh-profile-edit-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="{{ __('ui.account_hub') }}">
    <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
    <p class="section-banner-text">{{ __('ui.profile_edit_banner_text') }}</p>
</div>

<div class="grid grid-3-9 medium-space hh-profile-edit-grid">
    @include('teams.partials.account-sidebar', [
        'activeSection' => 'profile',
        'activeLink' => 'edit',
        'teamFormId' => 'hh-profile-edit-form',
        'teamPrimaryLabel' => __('ui.save_changes'),
        'teamSecondaryHref' => route('profile.show'),
        'teamSecondaryLabel' => __('ui.discard_all'),
        'profileSubmitButton' => true,
    ])

    <div class="account-hub-content">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.my_profile') }}</p>
                <h2 class="section-title">{{ __('ui.profile_info') }}</h2>
            </div>
        </div>

        @if ($errors->any())
            <div class="hh-alert hh-alert-danger">{{ __('ui.profile_validation_error') }}</div>
        @endif

        <form id="hh-profile-edit-form" class="form hh-profile-edit-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" data-hh-profile-edit-form data-success-message="{{ __('ui.profile_saved') }}" novalidate>
            @csrf
            @method('PUT')

            <div class="grid-column">
                <div id="profile-edit-media" class="grid grid-3-3-3 centered hh-profile-edit-media-grid">
                    <div class="user-preview small fixed-height hh-profile-edit-preview-card">
                        <figure class="user-preview-cover liquid hh-profile-edit-cover-preview">
                            <img src="{{ $coverUrl }}" alt="{{ $user->name }}" data-hh-profile-cover-preview>
                        </figure>

                        <div class="user-preview-info">
                            <div class="user-short-description small">
                                <div class="user-short-description-avatar user-avatar">
                                    <div class="user-avatar-border"><div class="hexagon-100-110"></div></div>
                                    <div class="user-avatar-content hh-profile-avatar-preview-frame">
                                        <div class="hexagon-image-68-74" data-src="{{ $avatarUrl }}" style="background-image:url('{{ $avatarUrl }}');" data-hh-profile-avatar-preview></div>
                                        <img class="hh-profile-avatar-preview-img" src="{{ $avatarUrl }}" alt="{{ $user->name }}" data-hh-profile-avatar-preview-img>
                                    </div>
                                    <div class="user-avatar-progress"><div class="hexagon-progress-84-92"></div></div>
                                    <div class="user-avatar-progress-border"><div class="hexagon-border-84-92"></div></div>
                                    <div class="user-avatar-badge">
                                        <div class="user-avatar-badge-border"><div class="hexagon-28-32"></div></div>
                                        <div class="user-avatar-badge-content"><div class="hexagon-dark-22-24"></div></div>
                                        <p class="user-avatar-badge-text">{{ max(1, (int) ($user->level ?: 1)) }}</p>
                                    </div>
                                </div>
                                <p class="user-short-description-title small" data-hh-profile-preview-name>{{ $user->name }}</p>
                                <p class="user-short-description-text" data-hh-profile-preview-headline data-empty-text="{{ __('ui.profile_no_headline') }}">{{ $profile?->headline ?: __('ui.profile_no_headline') }}</p>
                            </div>

                            <div class="user-stats">
                                <div class="user-stat big">
                                    <p class="user-stat-title" data-hh-profile-preview-completion>{{ $completion }}%</p>
                                    <p class="user-stat-text">{{ __('ui.gamification_profile_text') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <label class="upload-box hh-profile-upload-box" for="profile-avatar">
                        <i class="upload-box-icon hh-ph-action-icon ph ph-users" aria-hidden="true"></i>
                        <p class="upload-box-title">{{ __('ui.change_avatar') }}</p>
                        <p class="upload-box-text">{{ __('ui.avatar_upload_hint') }}</p>
                        <input class="hh-profile-file-input" type="file" id="profile-avatar" name="avatar" accept="image/jpeg,image/png,image/webp" data-hh-profile-file="avatar">
                        <span class="hh-form-error" data-hh-profile-error="avatar">@error('avatar'){{ $message }}@enderror</span>
                    </label>

                    <label class="upload-box hh-profile-upload-box" for="profile-cover">
                        <i class="upload-box-icon hh-ph-action-icon ph ph-images" aria-hidden="true"></i>
                        <p class="upload-box-title">{{ __('ui.change_cover') }}</p>
                        <p class="upload-box-text">{{ __('ui.cover_upload_hint') }}</p>
                        <input class="hh-profile-file-input" type="file" id="profile-cover" name="cover" accept="image/jpeg,image/png,image/webp" data-hh-profile-file="cover">
                        <span class="hh-form-error" data-hh-profile-error="cover">@error('cover'){{ $message }}@enderror</span>
                    </label>
                </div>

                <div id="profile-edit-about" class="widget-box hh-profile-edit-widget">
                    <p class="widget-box-title">{{ __('ui.about_your_profile') }}</p>

                    <div class="widget-box-content">
                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-input small active">
                                    <label for="profile-name">{{ __('ui.display_name') }}</label>
                                    <input type="text" id="profile-name" name="name" value="{{ old('name', $user->name) }}" required maxlength="80" data-hh-profile-live="name">
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="name">@error('name'){{ $message }}@enderror</span>
                            </div>

                            <div class="form-item">
                                <div class="form-input small {{ old('headline', $profile?->headline) ? 'active' : '' }}">
                                    <label for="profile-headline">{{ __('ui.profile_headline') }}</label>
                                    <input type="text" id="profile-headline" name="headline" value="{{ old('headline', $profile?->headline) }}" maxlength="120" data-hh-profile-live="headline">
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="headline">@error('headline'){{ $message }}@enderror</span>
                            </div>
                        </div>

                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-input small mid-textarea {{ old('bio', $profile?->bio) ? 'active' : '' }}">
                                    <label for="profile-bio">{{ __('ui.profile_bio') }}</label>
                                    <textarea id="profile-bio" name="bio" maxlength="1200">{{ old('bio', $profile?->bio) }}</textarea>
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="bio">@error('bio'){{ $message }}@enderror</span>
                            </div>

                            <div class="form-item">
                                <div class="form-select">
                                    <label for="profile-visibility">{{ __('ui.profile_visibility') }}</label>
                                    <select id="profile-visibility" name="profile_visibility" required>
                                        <option value="public" @selected($visibility === 'public')>{{ __('ui.visibility_public') }}</option>
                                        <option value="registered" @selected($visibility === 'registered')>{{ __('ui.visibility_registered') }}</option>
                                        <option value="private" @selected($visibility === 'private')>{{ __('ui.visibility_private') }}</option>
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="profile_visibility">@error('profile_visibility'){{ $message }}@enderror</span>

                                <input type="hidden" name="is_lfg_available" value="0">
                                <label for="profile-is-lfg-available" class="hh-profile-lfg-native-line">
                                    <input id="profile-is-lfg-available" type="checkbox" name="is_lfg_available" value="1" class="hh-profile-lfg-native" @checked($isLfgAvailable)>
                                    <span>{{ __('ui.profile_lfg_available') }}</span>
                                </label>
                                <span class="hh-form-error" data-hh-profile-error="is_lfg_available">@error('is_lfg_available'){{ $message }}@enderror</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="profile-edit-hunt" class="widget-box hh-profile-edit-widget">
                    <p class="widget-box-title">{{ __('ui.hunt_details') }}</p>

                    <div class="widget-box-content">
                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-select">
                                    <label for="profile-platform">{{ __('ui.platform') }}</label>
                                    <select id="profile-platform" name="platform">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        <option value="PC" @selected($platform === 'PC')>PC</option>
                                        <option value="Xbox" @selected($platform === 'Xbox')>Xbox</option>
                                        <option value="PlayStation" @selected($platform === 'PlayStation')>PlayStation</option>
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="platform">@error('platform'){{ $message }}@enderror</span>
                            </div>

                            <div class="form-item">
                                <div class="form-select">
                                    <label for="profile-playstyle">{{ __('ui.playstyle') }}</label>
                                    <select id="profile-playstyle" name="playstyle">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        <option value="Ruhig / taktisch" @selected($playstyle === 'Ruhig / taktisch')>{{ __('ui.playstyle_tactical') }}</option>
                                        <option value="Aggressiv / PvP" @selected($playstyle === 'Aggressiv / PvP')>{{ __('ui.playstyle_aggressive') }}</option>
                                        <option value="Einsteigerfreundlich" @selected($playstyle === 'Einsteigerfreundlich')>{{ __('ui.playstyle_beginner') }}</option>
                                        <option value="Competitive" @selected($playstyle === 'Competitive')>Competitive</option>
                                        <option value="Casual" @selected($playstyle === 'Casual')>Casual</option>
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="playstyle">@error('playstyle'){{ $message }}@enderror</span>
                            </div>
                        </div>

                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-input small {{ old('region', $profile?->region) ? 'active' : '' }}">
                                    <label for="profile-region">{{ __('ui.region') }}</label>
                                    <input type="text" id="profile-region" name="region" value="{{ old('region', $profile?->region) }}" maxlength="60">
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="region">@error('region'){{ $message }}@enderror</span>
                            </div>

                            <div class="form-item">
                                <div class="form-input small {{ old('language', $profile?->language) ? 'active' : '' }}">
                                    <label for="profile-language">{{ __('ui.language') }}</label>
                                    <input type="text" id="profile-language" name="language" value="{{ old('language', $profile?->language) }}" maxlength="40">
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="language">@error('language'){{ $message }}@enderror</span>
                            </div>
                        </div>

                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-input small {{ old('hunt_role', $profile?->hunt_role) ? 'active' : '' }}">
                                    <label for="profile-hunt-role">{{ __('ui.hunt_role') }}</label>
                                    <input type="text" id="profile-hunt-role" name="hunt_role" value="{{ old('hunt_role', $profile?->hunt_role) }}" maxlength="60">
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="hunt_role">@error('hunt_role'){{ $message }}@enderror</span>
                            </div>

                            <div class="form-item">
                                <div class="form-input small {{ old('discord_name', $profile?->discord_name) ? 'active' : '' }}">
                                    <label for="profile-discord">Discord</label>
                                    <input type="text" id="profile-discord" name="discord_name" value="{{ old('discord_name', $profile?->discord_name) }}" maxlength="80">
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="discord_name">@error('discord_name'){{ $message }}@enderror</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="profile-edit-social" class="widget-box hh-profile-edit-widget">
                    <p class="widget-box-title">{{ __('ui.social_stream') }}</p>

                    <div class="widget-box-content">
                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-input small {{ old('steam_url', $profile?->steam_url) ? 'active' : '' }}">
                                    <label for="profile-steam">Steam URL</label>
                                    <input type="url" id="profile-steam" name="steam_url" value="{{ old('steam_url', $profile?->steam_url) }}" maxlength="255">
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="steam_url">@error('steam_url'){{ $message }}@enderror</span>
                            </div>

                            <div class="form-item">
                                <div class="form-input small {{ old('twitch_url', $profile?->twitch_url) ? 'active' : '' }}">
                                    <label for="profile-twitch">Twitch URL</label>
                                    <input type="url" id="profile-twitch" name="twitch_url" value="{{ old('twitch_url', $profile?->twitch_url) }}" maxlength="255">
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="twitch_url">@error('twitch_url'){{ $message }}@enderror</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-item">
                                <div class="form-input small {{ old('youtube_url', $profile?->youtube_url) ? 'active' : '' }}">
                                    <label for="profile-youtube">YouTube URL</label>
                                    <input type="url" id="profile-youtube" name="youtube_url" value="{{ old('youtube_url', $profile?->youtube_url) }}" maxlength="255">
                                </div>
                                <span class="hh-form-error" data-hh-profile-error="youtube_url">@error('youtube_url'){{ $message }}@enderror</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
