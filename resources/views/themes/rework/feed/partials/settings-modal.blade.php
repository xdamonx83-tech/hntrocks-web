@php
    $reworkSettingsShouldOpen = $reworkSettingsShouldOpen ?? false;
    $reworkSettingsActiveTab = $reworkSettingsActiveTab ?? 'notifications';
    $reworkSettingsStatusMessages = $reworkSettingsStatusMessages ?? [];
    $reworkSettingsHasErrors = $reworkSettingsHasErrors ?? false;
    $reworkNotificationSettings = $reworkNotificationSettings ?? null;
    $reworkNotificationGroups = $reworkNotificationGroups ?? [];
    $reworkPrivacySettings = $reworkPrivacySettings ?? null;
    $reworkPrivacyToggles = $reworkPrivacyToggles ?? [];
    $reworkBlockedUsers = $reworkBlockedUsers ?? collect();
    $reworkTwoFactorEnabled = $reworkTwoFactorEnabled ?? false;
    $reworkTwoFactorRecoveryCount = $reworkTwoFactorRecoveryCount ?? 0;
    $reworkDeletionRequest = $reworkDeletionRequest ?? null;
@endphp

<div aria-hidden="{{ $reworkSettingsShouldOpen ? 'false' : 'true' }}" class="modal-backdrop settings-backdrop @if($reworkSettingsShouldOpen) is-open @endif" data-settings-modal="" @if($reworkSettingsShouldOpen) data-settings-modal-autopen="1" @endif>
<section aria-labelledby="settings-modal-title" aria-modal="true" class="settings-modal settings-has-footer-submit" role="dialog">
<header class="settings-modal-header">
<div>
<span class="settings-eyebrow">{{ __('ui.account') }}</span>
<h2 id="settings-modal-title">{{ __('ui.settings') }}</h2>
<p>{{ __('ui.rework_settings_modal_intro') }}</p>
</div>
<button aria-label="{{ __('ui.rework_settings_close_aria') }}" class="settings-close" data-settings-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<nav aria-label="{{ __('ui.rework_settings_tabs_aria') }}" class="settings-tabs">
<button class="@if($reworkSettingsActiveTab === 'notifications') is-active @endif" data-settings-tab="notifications" type="button"><i aria-hidden="true" class="ph ph-bell ph-icon"></i><span>{{ __('ui.notification_settings') }}</span></button>
<button class="@if($reworkSettingsActiveTab === 'privacy') is-active @endif" data-settings-tab="privacy" type="button"><i aria-hidden="true" class="ph ph-shield-check ph-icon"></i><span>{{ __('ui.privacy') }}</span></button>
<button class="@if($reworkSettingsActiveTab === 'blocked') is-active @endif" data-settings-tab="blocked" type="button"><i aria-hidden="true" class="ph ph-prohibit ph-icon"></i><span>{{ __('ui.account_blocked_users') }}</span></button>
<button class="@if($reworkSettingsActiveTab === 'security') is-active @endif" data-settings-tab="security" type="button"><i aria-hidden="true" class="ph ph-lock-key ph-icon"></i><span>{{ __('ui.security') }}</span></button>
</nav>
<div class="settings-modal-body">
@if (session('status') && in_array(session('status'), $reworkSettingsStatusMessages, true))
<div class="settings-status">{{ session('status') }}</div>
@endif
@if ($reworkSettingsHasErrors)
<div class="settings-status is-error">{{ __('ui.profile_validation_error') }}</div>
@endif
<section class="settings-panel @if($reworkSettingsActiveTab === 'notifications') is-active @endif" data-settings-panel="notifications">
<div class="settings-section-head">
<span>Notification Center</span>
<h3>{{ __('ui.notification_settings') }}</h3>
<p>{{ __('ui.notification_settings_intro') }}</p>
</div>
<form action="{{ route('account.settings.update') }}" method="post">
@csrf
@method('PUT')
<div class="settings-toggle-list">
@foreach($reworkNotificationGroups as $field => $meta)
<label class="settings-toggle-row" for="rework-notification-{{ $field }}"><input id="rework-notification-{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $reworkNotificationSettings?->{$field})) type="checkbox"/><span></span><div><strong>{{ $meta['title'] }}</strong><p>{{ $meta['text'] }}</p></div></label>
@endforeach
</div>
<div class="settings-form-actions"><button class="settings-inline-btn" type="submit">{{ __('ui.save_changes') }}</button></div>
</form>
</section>
<section class="settings-panel @if($reworkSettingsActiveTab === 'privacy') is-active @endif" data-settings-panel="privacy">
<div class="settings-section-head">
<span>{{ __('ui.privacy') }}</span>
<h3>{{ __('ui.contact_and_profile_visibility') }}</h3>
<p>{{ __('ui.privacy_settings_intro') }}</p>
</div>
<form action="{{ route('settings.privacy.update') }}" method="post">
@csrf
@method('PUT')
<div class="settings-form-grid">
<label><span>{{ __('ui.profile_visibility') }}</span><select name="profile_visibility"><option value="public" @selected(old('profile_visibility', $reworkPrivacySettings?->profile_visibility) === 'public')>{{ __('ui.visibility_public') }}</option><option value="registered" @selected(old('profile_visibility', $reworkPrivacySettings?->profile_visibility) === 'registered')>{{ __('ui.visibility_registered') }}</option><option value="private" @selected(old('profile_visibility', $reworkPrivacySettings?->profile_visibility) === 'private')>{{ __('ui.visibility_private') }}</option></select>@error('profile_visibility')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
<label><span>{{ __('ui.allow_messages_from') }}</span><select name="allow_messages_from"><option value="everyone" @selected(old('allow_messages_from', $reworkPrivacySettings?->allow_messages_from) === 'everyone')>{{ __('ui.allow_messages_everyone') }}</option><option value="registered" @selected(old('allow_messages_from', $reworkPrivacySettings?->allow_messages_from) === 'registered')>{{ __('ui.allow_messages_registered') }}</option><option value="following" @selected(old('allow_messages_from', $reworkPrivacySettings?->allow_messages_from) === 'following')>{{ __('ui.allow_messages_following') }}</option><option value="nobody" @selected(old('allow_messages_from', $reworkPrivacySettings?->allow_messages_from) === 'nobody')>{{ __('ui.allow_messages_nobody') }}</option></select>@error('allow_messages_from')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
</div>
<div class="settings-toggle-list compact">
@foreach($reworkPrivacyToggles as $field => $meta)
<label class="settings-toggle-row" for="rework-privacy-{{ $field }}"><input id="rework-privacy-{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $reworkPrivacySettings?->{$field})) type="checkbox"/><span></span><div><strong>{{ $meta['title'] }}</strong><p>{{ $meta['text'] }}</p></div></label>
@endforeach
</div>
<div class="settings-form-actions"><button class="settings-inline-btn" type="submit">{{ __('ui.save_changes') }}</button></div>
</form>
</section>
<section class="settings-panel @if($reworkSettingsActiveTab === 'blocked') is-active @endif" data-settings-panel="blocked">
<div class="settings-section-head">
<span>{{ __('ui.privacy') }}</span>
<h3>{{ __('ui.account_blocked_users') }}</h3>
<p>{{ __('ui.blocked_users_privacy_text') }}</p>
</div>
<form action="{{ route('settings.privacy.blocks.store') }}" method="post">
@csrf
<div class="settings-form-grid blocked-form">
<label><span>{{ __('ui.username') }}</span><input name="username" value="{{ old('username') }}" placeholder="z. B. huntername" type="text"/>@error('username')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
<label><span>{{ __('ui.reason_optional') }}</span><input maxlength="120" name="reason" value="{{ old('reason') }}" placeholder="{{ __('ui.rework_settings_block_reason_placeholder') }}" type="text"/>@error('reason')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
<button class="settings-inline-btn" type="submit">{{ __('ui.rework_settings_block_user') }}</button>
</div>
</form>
<div class="blocked-list">
@forelse($reworkBlockedUsers as $block)
<div class="blocked-item"><div><strong>{{ $block->blockedUser?->name ?? __('ui.deleted_user') }}</strong><span>{{ $block->blockedUser?->username ? '@'.$block->blockedUser->username : __('ui.unknown') }}</span>@if($block->reason)<span>{{ $block->reason }}</span>@endif</div><form action="{{ route('settings.privacy.blocks.destroy', $block) }}" method="post">@csrf @method('DELETE')<button type="submit">{{ __('ui.rework_settings_unblock_user') }}</button></form></div>
@empty
<div class="blocked-empty">{{ __('ui.no_blocked_users') }}</div>
@endforelse
</div>
</section>
<section class="settings-panel @if($reworkSettingsActiveTab === 'security') is-active @endif" data-settings-panel="security">
<div class="settings-section-head">
<span>{{ __('ui.security') }}</span>
<h3>{{ __('ui.account_security_info') }}</h3>
<p>{{ __('ui.account_security_banner_text') }}</p>
</div>
<form action="{{ route('settings.security.password') }}" method="post">
@csrf
<div class="settings-form-grid">
<label><span>{{ __('ui.current_password') }}</span><input autocomplete="current-password" name="current_password" type="password"/>@error('current_password')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
<label><span>{{ __('ui.new_password') }}</span><input autocomplete="new-password" name="password" type="password"/>@error('password')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
<label><span>{{ __('ui.confirm_new_password') }}</span><input autocomplete="new-password" name="password_confirmation" type="password"/></label>
</div>
<div class="settings-form-actions"><button class="settings-inline-btn" type="submit">{{ __('ui.change_password_now') }}</button></div>
</form>
<div class="settings-action-grid">
<a href="{{ route('settings.security.index') }}"><i aria-hidden="true" class="ph ph-device-mobile-camera ph-icon"></i><strong>{{ __('ui.two_factor_authentication') }}</strong><span>{{ $reworkTwoFactorEnabled ? __('ui.two_factor_enabled_intro', ['count' => $reworkTwoFactorRecoveryCount]) : __('ui.two_factor_disabled_intro') }}</span></a>
<a href="{{ route('settings.security.export') }}"><i aria-hidden="true" class="ph ph-download-simple ph-icon"></i><strong>{{ __('ui.data_export') }}</strong><span>{{ __('ui.download_data_export') }}</span></a>
<a class="danger" href="{{ route('settings.security.index') }}"><i aria-hidden="true" class="ph ph-warning ph-icon"></i><strong>{{ __('ui.account_deletion') }}</strong><span>{{ $reworkDeletionRequest && $reworkDeletionRequest->isPending() ? __('ui.account_deletion_pending', ['date' => $reworkDeletionRequest->scheduled_for?->format('d.m.Y H:i')]) : __('ui.account_deletion_intro') }}</span></a>
</div>
</section>
</div>
<footer class="settings-modal-footer">
<button class="settings-cancel" data-settings-modal-close="" type="button">{{ __('ui.cancel') }}</button>
<button class="settings-save" data-settings-active-submit="" type="button">{{ __('ui.save_changes') }}</button>
</footer>
</section>
</div>
@include('themes.socialite.partials.chat-tabs')
