@extends('layouts.app')

@section('title', __('ui.privacy') . ' · hnt.rocks')

@section('content')
<div class="section-banner hh-account-hub-banner hh-account-privacy-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="{{ __('ui.account_hub') }}">
    <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
    <p class="section-banner-text">{{ __('ui.account_privacy_banner_text') }}</p>
</div>

@if (session('status'))
    <div class="hh-alert hh-alert-success hh-account-privacy-alert">{{ session('status') }}</div>
@endif

<div class="grid grid-3-9 medium-space hh-account-hub-grid hh-account-privacy-grid">
    @include('teams.partials.account-sidebar', [
        'activeSection' => 'account',
        'activeLink' => 'privacy',
        'teamFormId' => 'privacy-settings-form',
        'teamPrimaryLabel' => __('ui.save_changes'),
        'teamSecondaryHref' => route('account.index'),
        'teamSecondaryLabel' => __('ui.discard_all'),
    ])

    <div class="account-hub-content">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.account') }}</p>
                <h2 class="section-title">{{ __('ui.privacy') }}</h2>
            </div>
        </div>

        <form id="privacy-settings-form" class="form hh-account-privacy-form" method="POST" action="{{ route('settings.privacy.update') }}">
            @csrf
            @method('PUT')

            <div class="grid-column">
                <div class="widget-box hh-account-privacy-widget">
                    <p class="widget-box-title">{{ __('ui.privacy_settings') }}</p>

                    <div class="widget-box-content">
                        <p class="hh-account-privacy-intro">{{ __('ui.privacy_settings_intro') }}</p>

                        <div class="form-row split hh-account-privacy-select-row">
                            <div class="form-item centered">
                                <label class="form-title" for="profile_visibility">{{ __('ui.profile_visibility') }}</label>
                            </div>

                            <div class="form-item">
                                <div class="form-select">
                                    <select id="profile_visibility" name="profile_visibility">
                                        <option value="public" @selected(old('profile_visibility', $settings->profile_visibility) === 'public')>{{ __('ui.visibility_public') }}</option>
                                        <option value="registered" @selected(old('profile_visibility', $settings->profile_visibility) === 'registered')>{{ __('ui.visibility_registered') }}</option>
                                        <option value="private" @selected(old('profile_visibility', $settings->profile_visibility) === 'private')>{{ __('ui.visibility_private') }}</option>
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('profile_visibility') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-row split hh-account-privacy-select-row">
                            <div class="form-item centered">
                                <label class="form-title" for="allow_messages_from">{{ __('ui.allow_messages_from') }}</label>
                            </div>

                            <div class="form-item">
                                <div class="form-select">
                                    <select id="allow_messages_from" name="allow_messages_from">
                                        <option value="everyone" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'everyone')>{{ __('ui.allow_messages_everyone') }}</option>
                                        <option value="registered" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'registered')>{{ __('ui.allow_messages_registered') }}</option>
                                        <option value="following" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'following')>{{ __('ui.allow_messages_following') }}</option>
                                        <option value="nobody" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'nobody')>{{ __('ui.allow_messages_nobody') }}</option>
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('allow_messages_from') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="widget-box hh-account-privacy-widget">
                    <p class="widget-box-title">{{ __('ui.contact_and_profile_visibility') }}</p>

                    <div class="widget-box-content">
                        <p class="hh-account-privacy-intro">{{ __('ui.contact_and_profile_visibility_intro') }}</p>

                        <div class="switch-option-list hh-privacy-switch-list">
                            @php
                                $privacyToggles = [
                                    'allow_team_invites' => ['title' => __('ui.allow_team_invites'), 'text' => __('ui.allow_team_invites_text')],
                                    'allow_lfg_invites' => ['title' => __('ui.allow_lfg_invites'), 'text' => __('ui.allow_lfg_invites_text')],
                                    'show_online_status' => ['title' => __('ui.show_online_status'), 'text' => __('ui.show_online_status_text')],
                                    'show_activity_feed' => ['title' => __('ui.show_activity_feed'), 'text' => __('ui.show_activity_feed_text')],
                                    'show_gamification' => ['title' => __('ui.show_gamification'), 'text' => __('ui.show_gamification_text')],
                                    'data_usage_consent' => ['title' => __('ui.data_usage_consent'), 'text' => __('ui.data_usage_consent_text')],
                                ];
                            @endphp

                            @foreach ($privacyToggles as $field => $meta)
                                <label class="switch-option hh-privacy-switch-option" for="privacy-{{ $field }}">
                                    <span class="hh-privacy-switch-copy">
                                        <span class="switch-option-title">{{ $meta['title'] }}</span>
                                        <span class="switch-option-text">{{ $meta['text'] }}</span>
                                    </span>

                                    <input
                                        class="hh-privacy-switch-input"
                                        id="privacy-{{ $field }}"
                                        type="checkbox"
                                        name="{{ $field }}"
                                        value="1"
                                        @checked(old($field, $settings->{$field}))
                                    >
                                    <span class="form-switch @if(old($field, $settings->{$field})) active @endif" aria-hidden="true">
                                        <span class="form-switch-button"></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="widget-box hh-account-privacy-widget">
                    <p class="widget-box-title">{{ __('ui.account_blocked_users') }}</p>

                    <div class="widget-box-content">
                        <p class="hh-account-privacy-intro">{{ __('ui.blocked_users_privacy_text') }}</p>

                        <div class="hh-account-link-grid hh-account-privacy-link-grid">
                            <a class="hh-account-link-card" href="{{ route('settings.privacy.blocks') }}">
                                <i class="hh-account-link-icon hh-ph-action-icon ph ph-x" aria-hidden="true"></i>
                                <span>
                                    <strong>{{ __('ui.manage_blocked_users') }}</strong>
                                    <small>{{ __('ui.account_settings_blocks_text') }}</small>
                                </span>
                            </a>

                            <a class="hh-account-link-card" href="{{ route('account.settings.edit') }}">
                                <i class="hh-account-link-icon hh-ph-action-icon ph ph-gear-six" aria-hidden="true"></i>
                                <span>
                                    <strong>{{ __('ui.notification_settings') }}</strong>
                                    <small>{{ __('ui.notification_settings_intro_short') }}</small>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
