<section class="settings-panel" data-settings-panel="blocked" hidden>
<div class="settings-section-intro">
<span>{{ __('settings.blocked_eyebrow') }}</span>
<h3>{{ __('settings.blocked_title') }}</h3>
<p>{{ __('settings.blocked_intro') }}</p>
</div>
@if (session('status'))
<article class="settings-choice-card full" role="status">
<div>
<span>{{ __('settings.saved_eyebrow') }}</span>
<h3>{{ session('status') }}</h3>
</div>
</article>
@endif
<form class="settings-block-form" id="settingsBlockUserForm" method="POST" action="{{ route('settings.privacy.blocks.store') }}">
@csrf
<input type="hidden" name="settings_section" value="blocked">
<label for="blockUsername">
<span>{{ __('settings.blocked_username') }}</span>
<input id="blockUsername" name="username" type="text" value="{{ old('username') }}" maxlength="32" placeholder="{{ __('ui.block_username_placeholder') }}" autocomplete="off" required>
@error('username')<small class="settings-block-error" role="alert">{{ $message }}</small>@enderror
</label>
<label for="blockReason">
<span>{{ __('settings.blocked_note') }}</span>
<input id="blockReason" name="reason" type="text" value="{{ old('reason') }}" maxlength="120" placeholder="{{ __('ui.block_note_placeholder') }}">
@error('reason')<small class="settings-block-error" role="alert">{{ $message }}</small>@enderror
</label>
<button type="submit">{{ __('settings.blocked_submit') }}</button>
</form>
<div class="settings-blocked-head">
<div><span>{{ __('settings.blocked_current_eyebrow') }}</span><h3>{{ __('settings.blocked_current_title') }}</h3></div>
<strong id="blockedCountMain">{{ $blockedUsers->count() }}</strong>
</div>
<div class="settings-blocked-list" id="blockedUsersList">
@forelse ($blockedUsers as $block)
@php($blockedUser = $block->blockedUser)
<article>
@if ($blockedUser)
<img src="{{ $blockedUser->avatarUrl() }}" alt="">
@else
<span class="settings-generated-avatar">?</span>
@endif
<div>
<strong>{{ $blockedUser?->name ?: ($blockedUser?->username ?? __('ui.deleted_user')) }}</strong>
<span>&#64;{{ $blockedUser?->username ?? __('ui.unknown') }}</span>
<small>{{ $block->reason ?: __('settings.blocked_no_note') }}</small>
</div>
<form class="settings-blocked-unblock-form" method="POST" action="{{ route('settings.privacy.blocks.destroy', $block) }}">
@csrf
@method('DELETE')
<input type="hidden" name="settings_section" value="blocked">
<button class="settings-real-unblock-button" type="submit">{{ __('settings.blocked_unblock') }}</button>
</form>
</article>
@empty
<p class="settings-blocked-empty">{{ __('settings.blocked_empty') }}</p>
@endforelse
</div>
<article class="settings-choice-card full settings-block-coverage-active">
<div>
<span>{{ __('settings.blocked_coverage_eyebrow') }}</span>
<h3>{{ __('settings.blocked_coverage_title') }}</h3>
<p>{{ __('settings.blocked_coverage_text') }}</p>
</div>
<span class="settings-planned-badge">{{ __('settings.blocked_coverage_badge') }}</span>
</article>
<article class="settings-choice-card full settings-block-coverage-placeholder" aria-disabled="true">
<div>
<span>{{ __('settings.blocked_future_eyebrow') }}</span>
<h3>{{ __('settings.blocked_future_title') }}</h3>
<p>{{ __('settings.blocked_future_text') }}</p>
</div>
<span class="settings-planned-badge">{{ __('settings.planned_badge') }}</span>
</article>
</section>
