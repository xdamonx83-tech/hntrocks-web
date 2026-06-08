@extends('layouts.app')

@section('title', __('ui.account_info') . ' · hnt.rocks')

@section('content')
@php
    $user = auth()->user();
    $profileUrl = $user->username ? route('profile.public', $user->username) : route('profile.show');
    $profileHandle = $user->username ? url('/u/'.$user->username) : route('profile.show');
    $lastLogin = $user->last_login_at?->format('d.m.Y H:i') ?? __('ui.account_not_saved');
    $memberSince = $user->created_at?->format('d.m.Y') ?? __('ui.account_not_saved');
@endphp

<div class="section-banner hh-account-hub-banner hh-account-info-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="{{ __('ui.account_hub') }}">
    <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
    <p class="section-banner-text">{{ __('ui.account_info_banner_text') }}</p>
</div>

<div class="grid grid-3-9 medium-space hh-account-hub-grid hh-account-info-grid">
    @include('teams.partials.account-sidebar', ['activeSection' => 'account', 'activeLink' => 'info'])

    <div class="account-hub-content">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.account') }}</p>
                <h2 class="section-title">{{ __('ui.account_info') }}</h2>
            </div>
        </div>

        <div class="grid-column">
            <div class="widget-box hh-account-info-widget">
                <p class="widget-box-title">{{ __('ui.account_personal_info') }}</p>

                <div class="widget-box-content">
                    <div class="form hh-account-readonly-form">
                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-input small active hh-account-readonly-input">
                                    <label for="account-full-name">{{ __('ui.account_full_name') }}</label>
                                    <input type="text" id="account-full-name" value="{{ $user->name }}" readonly>
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-input small active hh-account-readonly-input">
                                    <label for="account-email">{{ __('ui.account_email') }}</label>
                                    <input type="text" id="account-email" value="{{ $user->email }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-input small active hh-account-readonly-input">
                                    <label for="account-url-username">{{ __('ui.account_profile_url') }}</label>
                                    <input type="text" id="account-url-username" value="{{ $profileHandle }}" readonly>
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-input small active hh-account-readonly-input">
                                    <label for="account-username">{{ __('ui.account_username') }}</label>
                                    <input type="text" id="account-username" value="{{ $user->username ?? __('ui.account_not_saved') }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-input small active hh-account-readonly-input">
                                    <label for="account-member-since">{{ __('ui.account_member_since') }}</label>
                                    <input type="text" id="account-member-since" value="{{ $memberSince }}" readonly>
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-input small active hh-account-readonly-input">
                                    <label for="account-last-login">{{ __('ui.account_last_login') }}</label>
                                    <input type="text" id="account-last-login" value="{{ $lastLogin }}" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="widget-box hh-account-info-widget">
                <p class="widget-box-title">{{ __('ui.account_security_info') }}</p>

                <div class="widget-box-content">
                    <p class="hh-account-info-text">{{ __('ui.account_overview_hint') }}</p>

                    <div class="hh-account-link-grid">
                        <a class="hh-account-link-card" href="{{ $profileUrl }}">
                            <i class="hh-account-link-icon hh-ph-action-icon ph ph-user" aria-hidden="true"></i>
                            <span>
                                <strong>{{ __('ui.account_view_profile') }}</strong>
                                <small>{{ __('ui.account_profile_links') }}</small>
                            </span>
                        </a>

                        <a class="hh-account-link-card" href="{{ route('profile.edit') }}">
                            <i class="hh-account-link-icon hh-ph-action-icon ph ph-gear-six" aria-hidden="true"></i>
                            <span>
                                <strong>{{ __('ui.account_edit_profile') }}</strong>
                                <small>{{ __('ui.profile_edit_banner_text') }}</small>
                            </span>
                        </a>

                        <a class="hh-account-link-card" href="{{ route('account.settings.edit') }}">
                            <i class="hh-account-link-icon hh-ph-action-icon ph ph-bell" aria-hidden="true"></i>
                            <span>
                                <strong>{{ __('ui.account_settings') }}</strong>
                                <small>{{ __('ui.notification_settings_intro_short') }}</small>
                            </span>
                        </a>

                        <a class="hh-account-link-card" href="{{ route('settings.privacy.edit') }}">
                            <i class="hh-account-link-icon hh-ph-action-icon ph ph-lock" aria-hidden="true"></i>
                            <span>
                                <strong>{{ __('ui.account_privacy_settings') }}</strong>
                                <small>{{ __('ui.account_privacy_links') }}</small>
                            </span>
                        </a>

                        <a class="hh-account-link-card" href="{{ route('settings.privacy.blocks') }}">
                            <i class="hh-account-link-icon hh-ph-action-icon ph ph-x" aria-hidden="true"></i>
                            <span>
                                <strong>{{ __('ui.account_blocked_users') }}</strong>
                                <small>{{ __('ui.account_privacy_links') }}</small>
                            </span>
                        </a>

                        <a class="hh-account-link-card" href="{{ route('settings.security.index') }}">
                            <i class="hh-account-link-icon hh-ph-action-icon ph ph-gear-six" aria-hidden="true"></i>
                            <span>
                                <strong>{{ __('ui.account_security_data') }}</strong>
                                <small>{{ __('ui.account_security_links') }}</small>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
