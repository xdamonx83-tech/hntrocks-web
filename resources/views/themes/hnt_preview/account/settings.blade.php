@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.account_settings'))
@section('app_window_class', 'profile-window')
@section('main_class', 'profile-edit-main')

@section('content')
    <section class="edit-profile-shell hnt-settings-shell">
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

        @include('themes.hnt_preview.settings.partials.tabs', ['active' => 'notifications'])

        <form id="hnt-preview-account-settings-form" method="POST" action="{{ route('account.settings.update') }}" class="profile-edit-content-panel hnt-settings-form">
            @csrf
            @method('PUT')

            <div class="profile-edit-form">
                <section class="profile-edit-tab-panel is-active hnt-settings-section">
                    <div class="profile-edit-section-title">
                        <span>{{ __('ui.account') }}</span>
                        <h2>{{ __('ui.notification_settings') }}</h2>
                        <p>{{ __('ui.notification_settings_intro') }}</p>
                    </div>

                    <div class="hnt-settings-check-list">
                        @foreach ($notificationGroups as $field => $meta)
                            <label class="hnt-lfg-check hnt-settings-check" for="notification-{{ $field }}">
                                <input
                                    id="notification-{{ $field }}"
                                    type="checkbox"
                                    name="{{ $field }}"
                                    value="1"
                                    @checked(old($field, $settings->{$field}))
                                >
                                <span class="hnt-settings-check-copy">
                                    <strong>{{ $meta['title'] }}</strong>
                                    <small>{{ $meta['text'] }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="profile-edit-save-card hnt-settings-note">
                        <strong>{{ __('ui.privacy_settings') }}</strong>
                        <span>{{ __('ui.account_settings_privacy_intro') }}</span>
                        <div class="hnt-settings-inline-actions">
                            <a href="{{ route('settings.privacy.edit') }}" class="btn-create">{{ __('ui.account_privacy_settings') }}</a>
                            <a href="{{ route('settings.privacy.blocks') }}" class="btn-create">{{ __('ui.manage_blocked_users') }}</a>
                        </div>
                    </div>

                    <div class="form-actions profile-edit-actions hnt-settings-actions">
                        <a href="{{ route('profile.show') }}" class="btn-create">{{ __('ui.discard_all') }}</a>
                        <button type="submit" class="btn-create">{{ __('ui.save_changes') }}</button>
                    </div>
                </section>
            </div>
        </form>
    </section>
@endsection
