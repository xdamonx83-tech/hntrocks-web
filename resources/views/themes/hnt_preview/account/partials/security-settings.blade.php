@php
    $securityUser = $securitySettings['user'];
    $events = $securitySettings['events'];
    $eventsHasMore = $securitySettings['events_has_more'];
    $deletionRequest = $securitySettings['deletion_request'];
    $twoFactorEnabled = $securitySettings['two_factor_enabled'];
    $twoFactorRecoveryCount = $securitySettings['two_factor_recovery_count'];
    $twoFactorSetupSecret = $securitySettings['two_factor_setup_secret'];
    $twoFactorSetupUri = $securitySettings['two_factor_setup_uri'];
    $twoFactorRecoveryCodes = $securitySettings['two_factor_recovery_codes'];
    $deletionErrors = $errors->getBag('security_deletion');
@endphp
<section class="settings-panel" data-settings-panel="security" hidden>
<div class="settings-section-intro">
<span>{{ __('settings.security_eyebrow') }}</span>
<h3>{{ __('settings.security_title') }}</h3>
<p>{{ __('settings.security_intro') }}</p>
</div>
@if (session('status'))
<article class="settings-choice-card full" role="status">
<div>
<span>{{ __('settings.saved_eyebrow') }}</span>
<h3>{{ session('status') }}</h3>
</div>
</article>
@endif
<div class="settings-security-highlight">
<div class="settings-security-highlight-icon"><svg><use href="#i-check"></use></svg></div>
<div>
<span>{{ __('settings.security_status_eyebrow') }}</span>
<h3 id="securityHighlightTitle">{{ __('settings.security_status_title', ['status' => $securityStatus['score_label']]) }}</h3>
<p id="securityHighlightText">{{ __('settings.security_status_text', ['password' => $securityStatus['password']['text'], 'two_factor' => $securityStatus['two_factor']['text']]) }}</p>
</div>
<strong id="securityHighlightScore">{{ $securityStatus['score'] }}%</strong>
</div>
<form class="settings-security-section" method="POST" action="{{ route('settings.security.password') }}">
@csrf
<input type="hidden" name="settings_section" value="security">
<input type="hidden" name="settings_action" value="security_password">
<header>
<div><span>{{ __('settings.security_password_eyebrow') }}</span><h3>{{ __('ui.change_password') }}</h3></div>
<small>{{ $securityStatus['password']['text'] }}</small>
</header>
<div class="settings-password-grid">
<label for="securityCurrentPassword">
<span>{{ __('ui.current_password') }}</span>
<input id="securityCurrentPassword" name="current_password" autocomplete="current-password" type="password" required>
@error('current_password', 'security_password')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
</label>
<label for="securityNewPassword">
<span>{{ __('ui.new_password') }}</span>
<input id="securityNewPassword" name="password" autocomplete="new-password" type="password" required>
@error('password', 'security_password')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
</label>
<label for="securityPasswordConfirmation">
<span>{{ __('ui.confirm_new_password') }}</span>
<input id="securityPasswordConfirmation" name="password_confirmation" autocomplete="new-password" type="password" required>
</label>
</div>
<small class="settings-security-form-hint">{{ __('settings.security_password_requirements') }}</small>
<footer><button type="submit">{{ __('ui.change_password_now') }}</button></footer>
</form>
<article class="settings-two-factor-card is-expanded">
<div>
<span>{{ __('settings.security_two_factor_eyebrow') }}</span>
<h3>{{ $twoFactorEnabled ? __('settings.security_two_factor_enabled_title') : ($twoFactorSetupSecret ? __('settings.security_two_factor_confirm_title') : __('settings.security_two_factor_setup_title')) }}</h3>
<p>{{ $twoFactorEnabled ? __('ui.two_factor_enabled_intro', ['count' => $twoFactorRecoveryCount]) : ($twoFactorSetupSecret ? __('ui.two_factor_setup_manual_intro') : __('ui.two_factor_disabled_intro')) }}</p>
</div>
<div class="settings-two-factor-state">
<span class="{{ $twoFactorEnabled ? 'active' : '' }}">{{ $twoFactorEnabled ? __('settings.security_active') : __('settings.security_inactive') }}</span>
</div>
<div class="settings-two-factor-details">
@if (! empty($twoFactorRecoveryCodes))
<section class="settings-recovery-codes" role="status">
<strong>{{ __('ui.two_factor_recovery_codes_title') }}</strong>
<p>{{ __('ui.two_factor_recovery_codes_intro') }}</p>
<div>
@foreach ($twoFactorRecoveryCodes as $recoveryCode)
<code>{{ $recoveryCode }}</code>
@endforeach
</div>
</section>
@endif
@if ($twoFactorEnabled)
<div class="settings-two-factor-form-grid">
<form class="settings-security-inline-form" method="POST" action="{{ route('settings.security.two-factor.recovery-codes') }}">
@csrf
<input type="hidden" name="settings_section" value="security">
<input type="hidden" name="settings_action" value="security_2fa_recovery">
<label for="securityRecoveryPassword"><span>{{ __('ui.current_password') }}</span><input id="securityRecoveryPassword" name="current_password" type="password" autocomplete="current-password" required></label>
<label for="securityRecoveryCode"><span>{{ __('ui.two_factor_code_or_recovery') }}</span><input id="securityRecoveryCode" name="code" type="text" autocomplete="one-time-code" required></label>
@error('current_password', 'security_2fa_recovery')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
@error('code', 'security_2fa_recovery')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
<button type="submit">{{ __('ui.two_factor_regenerate_recovery_codes') }}</button>
</form>
<form class="settings-security-inline-form danger" method="POST" action="{{ route('settings.security.two-factor.disable') }}">
@csrf
<input type="hidden" name="settings_section" value="security">
<input type="hidden" name="settings_action" value="security_2fa_disable">
<label for="securityDisablePassword"><span>{{ __('ui.current_password') }}</span><input id="securityDisablePassword" name="current_password" type="password" autocomplete="current-password" required></label>
<label for="securityDisableCode"><span>{{ __('ui.two_factor_code_or_recovery') }}</span><input id="securityDisableCode" name="code" type="text" autocomplete="one-time-code" required></label>
@error('current_password', 'security_2fa_disable')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
@error('code', 'security_2fa_disable')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
<button type="submit">{{ __('ui.two_factor_disable') }}</button>
</form>
</div>
@elseif ($twoFactorSetupSecret)
<section class="settings-security-key">
<strong>{{ __('ui.two_factor_manual_key') }}</strong>
<code>{{ $twoFactorSetupSecret }}</code>
@if ($twoFactorSetupUri)<small>{{ $twoFactorSetupUri }}</small>@endif
</section>
<form class="settings-security-inline-form compact" method="POST" action="{{ route('settings.security.two-factor.confirm') }}">
@csrf
<input type="hidden" name="settings_section" value="security">
<input type="hidden" name="settings_action" value="security_2fa_confirm">
<label for="securityConfirmCode"><span>{{ __('ui.two_factor_code') }}</span><input id="securityConfirmCode" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required></label>
@error('code', 'security_2fa_confirm')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
<button type="submit">{{ __('ui.two_factor_confirm_enable') }}</button>
</form>
@else
<form class="settings-security-inline-form compact" method="POST" action="{{ route('settings.security.two-factor.setup') }}">
@csrf
<input type="hidden" name="settings_section" value="security">
<input type="hidden" name="settings_action" value="security_2fa_setup">
<label for="securitySetupPassword"><span>{{ __('ui.current_password') }}</span><input id="securitySetupPassword" name="current_password" type="password" autocomplete="current-password" required></label>
@error('current_password', 'security_2fa_setup')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
<button type="submit">{{ __('ui.two_factor_start_setup') }}</button>
</form>
@endif
</div>
</article>
<article class="settings-security-section sessions">
<header>
<div><span>{{ __('settings.security_events_eyebrow') }}</span><h3>{{ __('ui.security_log') }}</h3></div>
<div class="settings-security-placeholder-action">
<span class="settings-planned-badge">{{ __('settings.planned_badge') }}</span>
<button type="button" disabled>{{ __('settings.security_logout_other_sessions') }}</button>
</div>
</header>
<div class="settings-security-log" id="securityEventsList" tabindex="0" aria-label="{{ __('settings.security_events_scroll_label') }}">
@include('themes.hnt_preview.account.partials.security-events', ['events' => $events])
@if ($events->isEmpty())
<p class="settings-security-log-empty">{{ __('ui.no_security_events') }}</p>
@endif
</div>
<div class="settings-security-log-controls">
<button
id="securityEventsLoadMore"
type="button"
data-url="{{ route('settings.security.events') }}"
data-next-cursor="{{ $events->last()?->id }}"
data-label-more="{{ __('settings.security_events_more') }}"
data-label-loading="{{ __('settings.security_events_loading') }}"
{{ $eventsHasMore ? '' : 'hidden' }}
>{{ __('settings.security_events_more') }}</button>
<small id="securityEventsLoadError" role="alert" hidden>{{ __('settings.security_events_load_error') }}</small>
</div>
</article>
<div class="settings-security-actions-grid">
<article>
<span>{{ __('settings.security_data_eyebrow') }}</span>
<h3>{{ __('ui.data_export') }}</h3>
<p>{{ __('settings.security_export_text') }}</p>
<a class="settings-security-action-button" href="{{ route('settings.security.export') }}">{{ __('ui.download_data_export') }}</a>
</article>
<article class="danger">
<span>{{ __('settings.security_danger_eyebrow') }}</span>
<h3>{{ __('ui.account_deletion') }}</h3>
@if ($deletionRequest && $deletionRequest->isPending())
<p>{{ __('ui.account_deletion_pending', ['date' => $deletionRequest->scheduled_for?->format('d.m.Y H:i')]) }}</p>
<form method="POST" action="{{ route('settings.security.deletion.cancel') }}">
@csrf
@method('DELETE')
<input type="hidden" name="settings_section" value="security">
<input type="hidden" name="settings_action" value="security_deletion_cancel">
<button type="submit">{{ __('ui.cancel_account_deletion') }}</button>
</form>
@elseif ($securityUser->isAdmin())
<p>{{ __('ui.account_deletion_admin_blocked_status') }}</p>
<button type="button" disabled>{{ __('settings.security_admin_deletion_disabled') }}</button>
@else
<p>{{ __('settings.security_deletion_text') }}</p>
<button id="openDeleteAccount" type="button">{{ __('ui.request_account_deletion') }}</button>
@endif
</article>
</div>
@if (! $securityUser->isAdmin() && ! ($deletionRequest && $deletionRequest->isPending()))
<div class="settings-delete-modal" id="settingsDeleteModal" hidden data-username="{{ $securityUser->username }}" data-open-on-load="{{ $deletionErrors->any() ? 'true' : 'false' }}">
<form class="settings-delete-panel" method="POST" action="{{ route('settings.security.deletion.request') }}" aria-labelledby="deleteAccountTitle" aria-modal="true" role="dialog">
@csrf
<input type="hidden" name="settings_section" value="security">
<input type="hidden" name="settings_action" value="security_deletion">
<header>
<div><span>{{ __('settings.security_danger_eyebrow') }}</span><h2 id="deleteAccountTitle">{{ __('ui.request_account_deletion') }}</h2></div>
<button id="closeDeleteAccount" type="button" aria-label="{{ __('settings.security_modal_close') }}">×</button>
</header>
<p>{!! __('settings.security_deletion_confirm_text', ['username' => '<strong>'.e($securityUser->username).'</strong>']) !!}</p>
<label for="deleteAccountConfirmation">
<span>{{ __('settings.security_deletion_confirm_label') }}</span>
<input id="deleteAccountConfirmation" name="delete_confirmation" type="text" value="{{ old('delete_confirmation') }}" autocomplete="off" required>
@error('delete_confirmation', 'security_deletion')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
</label>
<label for="deletionPassword">
<span>{{ __('ui.account_deletion_password_optional') }}</span>
<input id="deletionPassword" name="password" type="password" autocomplete="current-password">
@error('password', 'security_deletion')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
</label>
<label for="deletionReason">
<span>{{ __('ui.reason_optional') }}</span>
<textarea id="deletionReason" name="reason" maxlength="1000" placeholder="{{ __('settings.security_deletion_reason_placeholder') }}" rows="3">{{ old('reason') }}</textarea>
@error('reason', 'security_deletion')<small class="settings-security-error" role="alert">{{ $message }}</small>@enderror
</label>
<footer>
<button id="cancelDeleteAccount" type="button">{{ __('settings.security_modal_cancel') }}</button>
<button id="confirmDeleteAccount" type="submit" disabled>{{ __('ui.request_account_deletion') }}</button>
</footer>
</form>
</div>
@endif
</section>
