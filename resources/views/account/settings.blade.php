@extends('layouts.app')

@section('title', __('ui.account_settings') . ' · hnt.rocks')

@section('content')
<div class="section-banner hh-account-hub-banner hh-account-settings-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="{{ __('ui.account_hub') }}">
    <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
    <p class="section-banner-text">{{ __('ui.account_settings_banner_text') }}</p>
</div>

@if (session('status'))
    <div class="hh-alert hh-alert-success hh-account-settings-alert">{{ session('status') }}</div>
@endif

<div class="grid grid-3-9 medium-space hh-account-hub-grid hh-account-settings-grid">
    @include('teams.partials.account-sidebar', [
        'activeSection' => 'account',
        'activeLink' => 'settings',
        'teamFormId' => 'account-settings-form',
        'teamPrimaryLabel' => __('ui.save_changes'),
        'teamSecondaryHref' => route('account.index'),
        'teamSecondaryLabel' => __('ui.discard_all'),
    ])

    <div class="account-hub-content">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.account') }}</p>
                <h2 class="section-title">{{ __('ui.account_settings') }}</h2>
            </div>
        </div>

        <form id="account-settings-form" class="form hh-account-settings-form" method="POST" action="{{ route('account.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="grid-column">
                <div class="widget-box hh-account-settings-widget">
                    <p class="widget-box-title">{{ __('ui.notification_settings') }}</p>

                    <div class="widget-box-content">
                        <p class="hh-account-settings-intro">{{ __('ui.notification_settings_intro') }}</p>

                        <div class="switch-option-list hh-notification-switch-list">
                            @foreach ($notificationGroups as $field => $meta)
                                <label class="switch-option hh-notification-switch-option" for="notification-{{ $field }}">
                                    <span class="hh-notification-switch-copy">
                                        <span class="switch-option-title">{{ $meta['title'] }}</span>
                                        <span class="switch-option-text">{{ $meta['text'] }}</span>
                                    </span>

                                    <input
                                        class="hh-notification-switch-input"
                                        id="notification-{{ $field }}"
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

                <div class="widget-box hh-account-settings-widget">
                    <p class="widget-box-title">{{ __('ui.privacy_settings') }}</p>

                    <div class="widget-box-content">
                        <p class="hh-account-settings-intro">{{ __('ui.account_settings_privacy_intro') }}</p>

                        <div class="hh-account-link-grid hh-account-settings-link-grid">
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
                                    <small>{{ __('ui.account_settings_blocks_text') }}</small>
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
