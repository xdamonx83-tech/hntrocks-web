@php
    $notificationIcons = [
        'feed_comments' => 'i-comment',
        'feed_reactions' => 'i-heart',
        'friends' => 'i-users',
        'teams' => 'i-user',
        'lfg' => 'i-search',
        'gamification' => 'i-plus',
        'moments' => 'i-image',
        'cups' => 'i-folder',
        'referrals' => 'i-share',
    ];
    $allNotificationsEnabled = collect(array_keys($notificationGroups))
        ->every(fn (string $field): bool => (bool) old($field, $settings->{$field}));
@endphp
<section class="settings-panel" data-settings-panel="notifications" data-real-settings-panel hidden>
<div class="settings-section-intro">
<span>{{ __('settings.notifications_eyebrow') }}</span>
<h3>{{ __('settings.notifications_title') }}</h3>
<p>{{ __('settings.notifications_intro') }}</p>
</div>
@if (session('status'))
<article class="settings-choice-card full" role="status">
<div>
<span>{{ __('settings.saved_eyebrow') }}</span>
<h3>{{ session('status') }}</h3>
</div>
</article>
@endif
<form id="settingsNotificationsForm" method="POST" action="{{ route('account.settings.update') }}" data-save-title="{{ __('settings.notifications_footer_title') }}" data-save-hint="{{ __('settings.notifications_footer_hint') }}">
@csrf
@method('PUT')
<input type="hidden" name="settings_section" value="notifications">
<article class="settings-master-row">
<div>
<span>{{ __('settings.notifications_master_eyebrow') }}</span>
<h3>{{ __('settings.notifications_master_title') }}</h3>
<p>{{ __('settings.notifications_master_text') }}</p>
</div>
<label class="settings-big-toggle" for="notificationMaster">
<input id="notificationMaster" type="checkbox" @checked($allNotificationsEnabled)>
<i></i>
</label>
</article>
<div class="settings-notification-grid">
@foreach ($notificationGroups as $field => $group)
<label class="settings-notification-card" for="notification-{{ $field }}">
<span class="settings-notification-icon"><svg><use href="#{{ $notificationIcons[$field] ?? 'i-bell' }}"></use></svg></span>
<span><strong>{{ $group['title'] }}</strong><small>{{ $group['text'] }}</small></span>
<input id="notification-{{ $field }}" class="notification-toggle" name="{{ $field }}" type="checkbox" value="1" @checked(old($field, $settings->{$field}))>
<i></i>
</label>
@endforeach
<article class="settings-notification-card is-placeholder" aria-disabled="true">
<span class="settings-notification-icon"><svg><use href="#i-bookmark"></use></svg></span>
<span><strong>{{ __('settings.notifications_guides_title') }}</strong><small>{{ __('settings.notifications_guides_text') }}</small></span>
<span class="settings-planned-badge">{{ __('settings.planned_badge') }}</span>
</article>
<article class="settings-notification-card is-placeholder" aria-disabled="true">
<span class="settings-notification-icon"><svg><use href="#i-sliders"></use></svg></span>
<span><strong>{{ __('settings.notifications_polls_title') }}</strong><small>{{ __('settings.notifications_polls_text') }}</small></span>
<span class="settings-planned-badge">{{ __('settings.planned_badge') }}</span>
</article>
</div>
</form>
</section>
