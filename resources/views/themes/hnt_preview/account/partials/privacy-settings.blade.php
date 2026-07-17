<section class="settings-panel" data-settings-panel="privacy" data-real-settings-panel hidden>
<div class="settings-section-intro">
<span>{{ __('settings.privacy_eyebrow') }}</span>
<h3>{{ __('settings.privacy_title') }}</h3>
<p>{{ __('settings.privacy_intro') }}</p>
</div>
@if (session('status'))
<article class="settings-choice-card full" role="status">
<div>
<span>{{ __('settings.saved_eyebrow') }}</span>
<h3>{{ session('status') }}</h3>
</div>
</article>
@endif
<form id="settingsPrivacyForm" method="POST" action="{{ route('settings.privacy.update') }}" data-save-title="{{ __('settings.privacy_footer_title') }}" data-save-hint="{{ __('settings.privacy_footer_hint') }}">
@csrf
@method('PUT')
<input type="hidden" name="settings_section" value="privacy">
<div class="settings-privacy-selects">
<label class="settings-field-card" for="privacyVisibility">
<span>{{ __('ui.profile_visibility') }}</span>
<select id="privacyVisibility" name="profile_visibility" required>
<option value="public" @selected(old('profile_visibility', $privacySettings->profile_visibility) === 'public')>{{ __('ui.visibility_public') }}</option>
<option value="registered" @selected(old('profile_visibility', $privacySettings->profile_visibility) === 'registered')>{{ __('ui.visibility_registered') }}</option>
<option value="private" @selected(old('profile_visibility', $privacySettings->profile_visibility) === 'private')>{{ __('ui.visibility_private') }}</option>
</select>
<small>{{ __('settings.privacy_profile_hint') }}</small>
@error('profile_visibility')<small role="alert">{{ $message }}</small>@enderror
</label>
<label class="settings-field-card is-placeholder" aria-disabled="true" for="privacyMessages">
<span>{{ __('ui.allow_messages_from') }}</span>
<select id="privacyMessages" disabled>
<option value="everyone" @selected($privacySettings->allow_messages_from === 'everyone')>{{ __('ui.allow_messages_everyone') }}</option>
<option value="registered" @selected($privacySettings->allow_messages_from === 'registered')>{{ __('ui.allow_messages_registered') }}</option>
<option value="following" @selected($privacySettings->allow_messages_from === 'following')>{{ __('ui.allow_messages_following') }}</option>
<option value="nobody" @selected($privacySettings->allow_messages_from === 'nobody')>{{ __('ui.allow_messages_nobody') }}</option>
</select>
<small><b class="settings-inline-planned">{{ __('settings.planned_badge') }}</b>{{ __('settings.privacy_messages_planned_hint') }}</small>
</label>
</div>
<div class="settings-row-list privacy">
<article class="settings-switch-row is-placeholder" aria-disabled="true">
<span class="settings-row-icon"><svg><use href="#i-users"></use></svg></span>
<span><strong>{{ __('ui.allow_team_invites') }}</strong><small>{{ __('ui.allow_team_invites_text') }}</small></span>
<span class="settings-planned-badge">{{ __('settings.planned_badge') }}</span>
</article>
<article class="settings-switch-row is-placeholder" aria-disabled="true">
<span class="settings-row-icon"><svg><use href="#i-search"></use></svg></span>
<span><strong>{{ __('ui.allow_lfg_invites') }}</strong><small>{{ __('ui.allow_lfg_invites_text') }}</small></span>
<span class="settings-planned-badge">{{ __('settings.planned_badge') }}</span>
</article>
<label class="settings-switch-row" for="privacyOnlineStatus">
<span class="settings-row-icon"><svg><use href="#i-eye"></use></svg></span>
<span><strong>{{ __('ui.show_online_status') }}</strong><small>{{ __('ui.show_online_status_text') }}</small></span>
<input id="privacyOnlineStatus" name="show_online_status" type="checkbox" value="1" @checked(old('show_online_status', $privacySettings->show_online_status))>
<i></i>
</label>
<label class="settings-switch-row" for="privacyActivityFeed">
<span class="settings-row-icon"><svg><use href="#i-bookmark"></use></svg></span>
<span><strong>{{ __('ui.show_activity_feed') }}</strong><small>{{ __('ui.show_activity_feed_text') }}</small></span>
<input id="privacyActivityFeed" name="show_activity_feed" type="checkbox" value="1" @checked(old('show_activity_feed', $privacySettings->show_activity_feed))>
<i></i>
</label>
<article class="settings-switch-row is-placeholder" aria-disabled="true">
<span class="settings-row-icon"><svg><use href="#i-plus"></use></svg></span>
<span><strong>{{ __('ui.show_gamification') }}</strong><small>{{ __('ui.show_gamification_text') }}</small></span>
<span class="settings-planned-badge">{{ __('settings.planned_badge') }}</span>
</article>
<article class="settings-switch-row is-placeholder" aria-disabled="true">
<span class="settings-row-icon"><svg><use href="#i-sliders"></use></svg></span>
<span><strong>{{ __('ui.data_usage_consent') }}</strong><small>{{ __('ui.data_usage_consent_text') }}</small></span>
<span class="settings-planned-badge">{{ __('settings.planned_badge') }}</span>
</article>
</div>
</form>
</section>
