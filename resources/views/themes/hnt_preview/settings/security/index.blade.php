@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.security'))
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

        @include('themes.hnt_preview.settings.partials.tabs', ['active' => 'security'])

        <div class="profile-edit-content-panel hnt-settings-form">
            <div class="profile-edit-form">
                <section class="profile-edit-tab-panel is-active hnt-settings-section">
                    <div class="profile-edit-section-title">
                        <span>{{ __('ui.account') }}</span>
                        <h2>{{ __('ui.security') }}</h2>
                        <p>{{ __('ui.account_security_banner_text') }}</p>
                    </div>

                    <form method="POST" action="{{ route('settings.security.password') }}" class="hnt-settings-inline-form">
                        @csrf
                        <div class="profile-edit-section-title hnt-settings-subtitle hnt-settings-subtitle-compact">
                            <span>{{ __('ui.security') }}</span>
                            <h2>{{ __('ui.change_password') }}</h2>
                            <p>{{ __('ui.account_security_links') }}</p>
                        </div>

                        <div class="form-row compact">
                            <label for="current_password">{{ __('ui.current_password') }}</label>
                            <div>
                                <input id="current_password" type="password" name="current_password" autocomplete="current-password">
                                @error('current_password')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="password">{{ __('ui.new_password') }}</label>
                            <div>
                                <input id="password" type="password" name="password" autocomplete="new-password">
                                @error('password')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="password_confirmation">{{ __('ui.confirm_new_password') }}</label>
                            <div>
                                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="form-actions profile-edit-actions hnt-settings-actions">
                            <a href="{{ route('password.request') }}" class="btn-create">{{ __('ui.forgot_password') }}</a>
                            <button type="submit" class="btn-create">{{ __('ui.change_password_now') }}</button>
                        </div>
                    </form>

                    <div class="profile-edit-section-title profile-edit-section-spaced hnt-settings-subtitle">
                        <span>{{ __('ui.security') }}</span>
                        <h2>{{ __('ui.two_factor_authentication') }}</h2>
                        <p>
                            @if ($twoFactorEnabled)
                                {{ __('ui.two_factor_enabled_intro', ['count' => $twoFactorRecoveryCount]) }}
                            @else
                                {{ __('ui.two_factor_disabled_intro') }}
                            @endif
                        </p>
                    </div>

                    @if (! empty($twoFactorRecoveryCodes))
                        <div class="profile-edit-save-card hnt-settings-note hnt-settings-warning">
                            <strong>{{ __('ui.two_factor_recovery_codes_title') }}</strong>
                            <span>{{ __('ui.two_factor_recovery_codes_intro') }}</span>
                            <div class="hnt-settings-code-list">
                                @foreach ($twoFactorRecoveryCodes as $recoveryCode)
                                    <code>{{ $recoveryCode }}</code>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($twoFactorEnabled)
                        <div class="hnt-settings-two-col">
                            <form method="POST" action="{{ route('settings.security.two-factor.recovery-codes') }}" class="hnt-settings-mini-form">
                                @csrf
                                <div class="form-row compact">
                                    <label for="two_factor_recovery_password">{{ __('ui.current_password') }}</label>
                                    <div><input id="two_factor_recovery_password" type="password" name="current_password" autocomplete="current-password"></div>
                                </div>
                                <div class="form-row compact">
                                    <label for="two_factor_recovery_code">{{ __('ui.two_factor_code_or_recovery') }}</label>
                                    <div>
                                        <input id="two_factor_recovery_code" type="text" name="code" autocomplete="one-time-code">
                                        @error('code')<p class="profile-edit-error">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                <button class="btn-create" type="submit">{{ __('ui.two_factor_regenerate_recovery_codes') }}</button>
                            </form>

                            <form method="POST" action="{{ route('settings.security.two-factor.disable') }}" class="hnt-settings-mini-form">
                                @csrf
                                <div class="form-row compact">
                                    <label for="two_factor_disable_password">{{ __('ui.current_password') }}</label>
                                    <div><input id="two_factor_disable_password" type="password" name="current_password" autocomplete="current-password"></div>
                                </div>
                                <div class="form-row compact">
                                    <label for="two_factor_disable_code">{{ __('ui.two_factor_code_or_recovery') }}</label>
                                    <div>
                                        <input id="two_factor_disable_code" type="text" name="code" autocomplete="one-time-code">
                                        @error('code')<p class="profile-edit-error">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                <button class="btn-create" type="submit">{{ __('ui.two_factor_disable') }}</button>
                            </form>
                        </div>
                    @elseif ($twoFactorSetupSecret)
                        <div class="profile-edit-save-card hnt-settings-note">
                            <strong>{{ __('ui.two_factor_manual_key') }}</strong>
                            <span>{{ $twoFactorSetupSecret }}</span>
                            @if ($twoFactorSetupUri)
                                <small>{{ $twoFactorSetupUri }}</small>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('settings.security.two-factor.confirm') }}" class="hnt-settings-inline-form">
                            @csrf
                            <div class="form-row compact">
                                <label for="two_factor_confirm_code">{{ __('ui.two_factor_code') }}</label>
                                <div>
                                    <input id="two_factor_confirm_code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code">
                                    @error('code')<p class="profile-edit-error">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="form-actions profile-edit-actions hnt-settings-actions">
                                <button class="btn-create" type="submit">{{ __('ui.two_factor_confirm_enable') }}</button>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('settings.security.two-factor.setup') }}" class="hnt-settings-inline-form">
                            @csrf
                            <div class="form-row compact">
                                <label for="two_factor_setup_password">{{ __('ui.current_password') }}</label>
                                <div>
                                    <input id="two_factor_setup_password" type="password" name="current_password" autocomplete="current-password">
                                    @error('current_password')<p class="profile-edit-error">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="form-actions profile-edit-actions hnt-settings-actions">
                                <button class="btn-create" type="submit">{{ __('ui.two_factor_start_setup') }}</button>
                            </div>
                        </form>
                    @endif

                    <div class="profile-edit-section-title profile-edit-section-spaced hnt-settings-subtitle">
                        <span>{{ __('ui.account_security_info') }}</span>
                        <h2>{{ __('ui.data_export') }}</h2>
                        <p>{{ __('ui.data_export_intro') }}</p>
                    </div>

                    <div class="hnt-settings-inline-actions hnt-settings-section-actions">
                        <a class="btn-create" href="{{ route('settings.security.export') }}">{{ __('ui.download_data_export') }}</a>
                    </div>

                    <div class="profile-edit-section-title profile-edit-section-spaced hnt-settings-subtitle">
                        <span>{{ __('ui.account_security_info') }}</span>
                        <h2>{{ __('ui.account_deletion') }}</h2>
                        <p>{{ __('ui.account_deletion_intro') }}</p>
                    </div>

                    @if ($deletionRequest && $deletionRequest->isPending())
                        <div class="profile-edit-save-card hnt-settings-note hnt-settings-warning">
                            <strong>{{ __('ui.account_deletion_pending', ['date' => $deletionRequest->scheduled_for?->format('d.m.Y H:i')]) }}</strong>
                            <span>{{ __('ui.account_deletion_pending_details') }}</span>
                        </div>
                        <form method="POST" action="{{ route('settings.security.deletion.cancel') }}" class="hnt-settings-inline-form">
                            @csrf
                            @method('DELETE')
                            <div class="form-actions profile-edit-actions hnt-settings-actions">
                                <button class="btn-create" type="submit">{{ __('ui.cancel_account_deletion') }}</button>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('settings.security.deletion.request') }}" class="hnt-settings-inline-form">
                            @csrf
                            <div class="form-row compact">
                                <label for="delete_confirmation">{{ __('ui.account_deletion_confirm_username', ['username' => $user->username]) }}</label>
                                <div>
                                    <input id="delete_confirmation" type="text" name="delete_confirmation" value="{{ old('delete_confirmation') }}" autocomplete="off">
                                    @error('delete_confirmation')<p class="profile-edit-error">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="form-row compact">
                                <label for="deletion_password">{{ __('ui.account_deletion_password_optional') }}</label>
                                <div>
                                    <input id="deletion_password" type="password" name="password" autocomplete="current-password">
                                    @error('password')<p class="profile-edit-error">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="form-row align-top">
                                <label for="reason">{{ __('ui.reason_optional') }}</label>
                                <div>
                                    <textarea id="reason" name="reason" rows="3">{{ old('reason') }}</textarea>
                                    @error('reason')<p class="profile-edit-error">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="form-actions profile-edit-actions hnt-settings-actions">
                                <button class="btn-create" type="submit">{{ __('ui.request_account_deletion') }}</button>
                            </div>
                        </form>
                    @endif

                    <div class="profile-edit-section-title profile-edit-section-spaced hnt-settings-subtitle">
                        <span>{{ __('ui.security') }}</span>
                        <h2>{{ __('ui.security_log') }}</h2>
                        <p>{{ __('ui.security_log_intro') }}</p>
                    </div>

                    <div class="hnt-settings-table" role="table" aria-label="{{ __('ui.security_log') }}">
                        <div class="hnt-settings-table-head" role="row">
                            <span role="columnheader">{{ __('ui.preview_settings_device') }}</span>
                            <span role="columnheader">IP</span>
                            <span role="columnheader">{{ __('ui.preview_settings_time') }}</span>
                        </div>
                        @forelse ($events as $event)
                            <div class="hnt-settings-table-row" role="row">
                                <strong role="cell">{{ str_replace('_', ' ', $event->event) }}</strong>
                                <span role="cell">{{ $event->ip_address ?: __('ui.unknown') }}</span>
                                <span role="cell">{{ $event->created_at->format('d.m.Y H:i') }}</span>
                                <small role="cell">{{ $event->user_agent ?: __('ui.no_user_agent_saved') }}</small>
                            </div>
                        @empty
                            <p class="hnt-settings-empty">{{ __('ui.no_security_events') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection
