@php
    $user = $generalSettings['user'];
    $locale = $generalSettings['locale'];
    $memberSince = $user->created_at
        ? ($locale === 'en'
            ? $user->created_at->locale('en')->translatedFormat('M j, Y')
            : $user->created_at->locale('de')->translatedFormat('d.m.Y'))
        : __('ui.unknown');
@endphp
<section class="settings-panel active" data-settings-panel="general" data-real-settings-panel>
<div class="settings-section-intro">
<span>{{ __('settings.general_eyebrow') }}</span>
<h3>{{ __('settings.general_title') }}</h3>
<p>{{ __('settings.general_intro') }}</p>
</div>
@if (session('status'))
<article class="settings-choice-card full" role="status">
<div>
<span>{{ __('settings.saved_eyebrow') }}</span>
<h3>{{ session('status') }}</h3>
</div>
</article>
@endif
<form id="settingsGeneralForm" method="POST" action="{{ route('account.settings.general.update') }}">
@csrf
@method('PUT')
<div class="settings-general-grid">
<label class="settings-field-card" for="settingsLanguage">
<span>{{ __('settings.language') }}</span>
<select id="settingsLanguage" name="locale" required>
<option value="de" @selected(old('locale', $locale) === 'de')>{{ __('settings.language_de') }}</option>
<option value="en" @selected(old('locale', $locale) === 'en')>{{ __('settings.language_en') }}</option>
</select>
<small>{{ __('settings.language_hint') }}</small>
@error('locale')<small role="alert">{{ $message }}</small>@enderror
</label>
<label class="settings-field-card" for="settingsUsername">
<span>{{ __('settings.username') }}</span>
<input id="settingsUsername" type="text" value="{{ $user->username }}" readonly>
<small>{{ __('settings.username_hint') }}</small>
</label>
<label class="settings-field-card" for="settingsEmail">
<span>{{ __('settings.email') }}</span>
<input id="settingsEmail" type="email" value="{{ $user->email }}" readonly>
<small>{{ __('settings.email_hint') }}</small>
</label>
<label class="settings-field-card" for="settingsEmailStatus">
<span>{{ __('settings.email_status') }}</span>
<input id="settingsEmailStatus" type="text" value="{{ $user->email_verified_at ? __('settings.email_verified') : __('settings.email_unverified') }}" readonly>
<small>{{ __('settings.member_since') }} {{ $memberSince }} · {{ __('settings.account_data_hint') }}</small>
</label>
</div>
</form>
</section>
