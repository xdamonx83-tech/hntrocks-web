@extends('layouts.app')

@section('title', __('ui.security') . ' · hnt.rocks')

@section('content')
<div class="section-banner hh-account-hub-banner hh-account-security-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="{{ __('ui.account_hub') }}">
    <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
    <p class="section-banner-text">{{ __('ui.account_security_banner_text') }}</p>
</div>

@if (session('status'))
    <div class="hh-alert hh-alert-success hh-account-security-alert">{{ session('status') }}</div>
@endif

<div class="grid grid-3-9 medium-space hh-account-hub-grid hh-account-security-grid">
    @include('teams.partials.account-sidebar', [
        'activeSection' => 'account',
        'activeLink' => 'security',
    ])

    <div class="account-hub-content">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.account') }}</p>
                <h2 class="section-title">{{ __('ui.security') }}</h2>
            </div>
        </div>

        <div class="grid-column">
            <div class="widget-box hh-account-security-widget">
                <p class="widget-box-title">{{ __('ui.change_password') }}</p>

                <div class="widget-box-content">
                    <form class="form hh-account-security-form" method="POST" action="{{ route('settings.security.password') }}">
                        @csrf

                        <div class="form-row">
                            <div class="form-item">
                                <div class="form-input small @error('current_password') active @enderror">
                                    <label for="current_password">{{ __('ui.current_password') }}</label>
                                    <input id="current_password" type="password" name="current_password" autocomplete="current-password">
                                </div>
                                @error('current_password') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-input small @error('password') active @enderror">
                                    <label for="password">{{ __('ui.new_password') }}</label>
                                    <input id="password" type="password" name="password" autocomplete="new-password">
                                </div>
                                @error('password') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-item">
                                <div class="form-input small">
                                    <label for="password_confirmation">{{ __('ui.confirm_new_password') }}</label>
                                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <div class="form-row split hh-account-security-actions">
                            <div class="form-item">
                                <a class="button full secondary" href="{{ route('password.request') }}">{{ __('ui.forgot_password') }}</a>
                            </div>

                            <div class="form-item">
                                <button class="button full primary" type="submit">{{ __('ui.change_password_now') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="widget-box hh-account-security-widget">
                <p class="widget-box-title">{{ __('ui.two_factor_authentication') }}</p>

                <div class="widget-box-content">
                    @if (! empty($twoFactorRecoveryCodes))
                        <div class="hh-account-security-warning">
                            <p><strong>{{ __('ui.two_factor_recovery_codes_title') }}</strong></p>
                            <p>{{ __('ui.two_factor_recovery_codes_intro') }}</p>
                            <div class="hh-account-security-event-list">
                                @foreach ($twoFactorRecoveryCodes as $recoveryCode)
                                    <div class="hh-account-security-event">
                                        <div>
                                            <p class="hh-account-security-event-title">{{ $recoveryCode }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($twoFactorEnabled)
                        <p class="hh-account-security-text">{{ __('ui.two_factor_enabled_intro', ['count' => $twoFactorRecoveryCount]) }}</p>

                        <div class="grid grid-6-6 medium-space hh-account-security-cards">
                            <div>
                                <form class="form hh-account-security-form" method="POST" action="{{ route('settings.security.two-factor.recovery-codes') }}">
                                    @csrf
                                    <div class="form-row">
                                        <div class="form-item">
                                            <div class="form-input small @error('current_password') active @enderror">
                                                <label for="two_factor_recovery_password">{{ __('ui.current_password') }}</label>
                                                <input id="two_factor_recovery_password" type="password" name="current_password" autocomplete="current-password">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-item">
                                            <div class="form-input small @error('code') active @enderror">
                                                <label for="two_factor_recovery_code">{{ __('ui.two_factor_code_or_recovery') }}</label>
                                                <input id="two_factor_recovery_code" type="text" name="code" autocomplete="one-time-code">
                                            </div>
                                            @error('code') <p class="hh-form-error">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <button class="button full secondary" type="submit">{{ __('ui.two_factor_regenerate_recovery_codes') }}</button>
                                </form>
                            </div>

                            <div>
                                <form class="form hh-account-security-form" method="POST" action="{{ route('settings.security.two-factor.disable') }}">
                                    @csrf
                                    <div class="form-row">
                                        <div class="form-item">
                                            <div class="form-input small @error('current_password') active @enderror">
                                                <label for="two_factor_disable_password">{{ __('ui.current_password') }}</label>
                                                <input id="two_factor_disable_password" type="password" name="current_password" autocomplete="current-password">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-item">
                                            <div class="form-input small @error('code') active @enderror">
                                                <label for="two_factor_disable_code">{{ __('ui.two_factor_code_or_recovery') }}</label>
                                                <input id="two_factor_disable_code" type="text" name="code" autocomplete="one-time-code">
                                            </div>
                                            @error('code') <p class="hh-form-error">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <button class="button full secondary" type="submit">{{ __('ui.two_factor_disable') }}</button>
                                </form>
                            </div>
                        </div>
                    @elseif ($twoFactorSetupSecret)
                        <p class="hh-account-security-text">{{ __('ui.two_factor_setup_manual_intro') }}</p>
                        <div class="hh-account-security-warning">
                            <p><strong>{{ __('ui.two_factor_manual_key') }}</strong></p>
                            <p class="hh-account-security-event-title">{{ $twoFactorSetupSecret }}</p>
                            <p>{{ __('ui.two_factor_otpauth_hint') }}</p>
                            <p class="hh-account-security-event-agent">{{ $twoFactorSetupUri }}</p>
                        </div>

                        <form class="form hh-account-security-form" method="POST" action="{{ route('settings.security.two-factor.confirm') }}">
                            @csrf
                            <div class="form-row">
                                <div class="form-item">
                                    <div class="form-input small @error('code') active @enderror">
                                        <label for="two_factor_confirm_code">{{ __('ui.two_factor_code') }}</label>
                                        <input id="two_factor_confirm_code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code">
                                    </div>
                                    @error('code') <p class="hh-form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <button class="button full primary" type="submit">{{ __('ui.two_factor_confirm_enable') }}</button>
                        </form>
                    @else
                        <p class="hh-account-security-text">{{ __('ui.two_factor_disabled_intro') }}</p>
                        <form class="form hh-account-security-form" method="POST" action="{{ route('settings.security.two-factor.setup') }}">
                            @csrf
                            <div class="form-row">
                                <div class="form-item">
                                    <div class="form-input small @error('current_password') active @enderror">
                                        <label for="two_factor_setup_password">{{ __('ui.current_password') }}</label>
                                        <input id="two_factor_setup_password" type="password" name="current_password" autocomplete="current-password">
                                    </div>
                                    @error('current_password') <p class="hh-form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <button class="button full primary" type="submit">{{ __('ui.two_factor_start_setup') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="grid grid-6-6 medium-space hh-account-security-cards">
                <div class="widget-box hh-account-security-widget">
                    <p class="widget-box-title">{{ __('ui.data_export') }}</p>

                    <div class="widget-box-content">
                        <p class="hh-account-security-text">{{ __('ui.data_export_intro') }}</p>
                        <a class="button secondary full" href="{{ route('settings.security.export') }}">{{ __('ui.download_data_export') }}</a>
                    </div>
                </div>

                <div class="widget-box hh-account-security-widget hh-account-security-danger-widget">
                    <p class="widget-box-title">{{ __('ui.account_deletion') }}</p>

                    <div class="widget-box-content">
                        @if ($deletionRequest && $deletionRequest->isPending())
                            <div class="hh-account-security-warning">
                                <p>{{ __('ui.account_deletion_pending', ['date' => $deletionRequest->scheduled_for?->format('d.m.Y H:i')]) }}</p>
                                <p>{{ __('ui.account_deletion_pending_details') }}</p>
                            </div>

                            <form method="POST" action="{{ route('settings.security.deletion.cancel') }}">
                                @csrf
                                @method('DELETE')
                                <button class="button full secondary" type="submit">{{ __('ui.cancel_account_deletion') }}</button>
                            </form>
                        @else
                            <p class="hh-account-security-text">{{ __('ui.account_deletion_intro') }}</p>
                            <p class="hh-account-security-text">{{ __('ui.account_deletion_will_remove_data') }}</p>

                            <form class="form hh-account-security-form" method="POST" action="{{ route('settings.security.deletion.request') }}">
                                @csrf

                                <div class="form-row">
                                    <div class="form-item">
                                        <div class="form-input small @error('delete_confirmation') active @enderror">
                                            <label for="delete_confirmation">{{ __('ui.account_deletion_confirm_username', ['username' => $user->username]) }}</label>
                                            <input id="delete_confirmation" type="text" name="delete_confirmation" value="{{ old('delete_confirmation') }}" autocomplete="off">
                                        </div>
                                        @error('delete_confirmation') <p class="hh-form-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-item">
                                        <div class="form-input small @error('password') active @enderror">
                                            <label for="deletion_password">{{ __('ui.account_deletion_password_optional') }}</label>
                                            <input id="deletion_password" type="password" name="password" autocomplete="current-password">
                                        </div>
                                        @error('password') <p class="hh-form-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-item">
                                        <div class="form-input textarea @error('reason') active @enderror">
                                            <label for="reason">{{ __('ui.reason_optional') }}</label>
                                            <textarea id="reason" name="reason" rows="3">{{ old('reason') }}</textarea>
                                        </div>
                                        @error('reason') <p class="hh-form-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <button class="button full secondary" type="submit">{{ __('ui.request_account_deletion') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="widget-box hh-account-security-widget hh-account-security-events-widget">
                <p class="widget-box-title">{{ __('ui.security_log') }}</p>

                <div class="widget-box-content">
                    <p class="hh-account-security-text">{{ __('ui.security_log_intro') }}</p>

                    <div class="hh-account-security-event-list">
                        @forelse ($events as $event)
                            <div class="hh-account-security-event">
                                <div>
                                    <p class="hh-account-security-event-title">{{ str_replace('_', ' ', $event->event) }}</p>
                                    <p class="hh-account-security-event-meta">
                                        {{ $event->created_at->format('d.m.Y H:i') }} · IP: {{ $event->ip_address ?: __('ui.unknown') }}
                                    </p>
                                    <p class="hh-account-security-event-agent">{{ $event->user_agent ?: __('ui.no_user_agent_saved') }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="hh-account-security-empty">{{ __('ui.no_security_events') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
