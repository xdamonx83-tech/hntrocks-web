@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.privacy'))
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

        @include('themes.hnt_preview.settings.partials.tabs', ['active' => 'privacy'])

        <form id="hnt-preview-privacy-settings-form" method="POST" action="{{ route('settings.privacy.update') }}" class="profile-edit-content-panel hnt-settings-form">
            @csrf
            @method('PUT')

            <div class="profile-edit-form">
                <section class="profile-edit-tab-panel is-active hnt-settings-section">
                    <div class="profile-edit-section-title">
                        <span>{{ __('ui.account') }}</span>
                        <h2>{{ __('ui.privacy') }}</h2>
                        <p>{{ __('ui.privacy_settings_intro') }}</p>
                    </div>

                    <div class="form-row compact">
                        <label for="profile_visibility">{{ __('ui.profile_visibility') }}</label>
                        <div>
                            <select id="profile_visibility" name="profile_visibility" required>
                                <option value="public" @selected(old('profile_visibility', $settings->profile_visibility) === 'public')>{{ __('ui.visibility_public') }}</option>
                                <option value="registered" @selected(old('profile_visibility', $settings->profile_visibility) === 'registered')>{{ __('ui.visibility_registered') }}</option>
                                <option value="private" @selected(old('profile_visibility', $settings->profile_visibility) === 'private')>{{ __('ui.visibility_private') }}</option>
                            </select>
                            @error('profile_visibility')<p class="profile-edit-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row compact">
                        <label for="allow_messages_from">{{ __('ui.allow_messages_from') }}</label>
                        <div>
                            <select id="allow_messages_from" name="allow_messages_from" required>
                                <option value="everyone" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'everyone')>{{ __('ui.allow_messages_everyone') }}</option>
                                <option value="registered" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'registered')>{{ __('ui.allow_messages_registered') }}</option>
                                <option value="following" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'following')>{{ __('ui.allow_messages_following') }}</option>
                                <option value="nobody" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'nobody')>{{ __('ui.allow_messages_nobody') }}</option>
                            </select>
                            @error('allow_messages_from')<p class="profile-edit-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="profile-edit-section-title profile-edit-section-spaced hnt-settings-subtitle">
                        <span>{{ __('ui.privacy') }}</span>
                        <h2>{{ __('ui.contact_and_profile_visibility') }}</h2>
                        <p>{{ __('ui.contact_and_profile_visibility_intro') }}</p>
                    </div>

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

                    <div class="hnt-settings-check-list">
                        @foreach ($privacyToggles as $field => $meta)
                            <label class="hnt-lfg-check hnt-settings-check" for="privacy-{{ $field }}">
                                <input
                                    id="privacy-{{ $field }}"
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
                        <strong>{{ __('ui.account_blocked_users') }}</strong>
                        <span>{{ __('ui.blocked_users_privacy_text') }}</span>
                        <div class="hnt-settings-inline-actions">
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
