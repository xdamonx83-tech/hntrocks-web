@php
    $active = $active ?? '';
@endphp

<nav class="settings-tabs profile-edit-tabs hnt-settings-tabs" aria-label="{{ __('ui.account_navigation') }}">
    <a href="{{ route('account.settings.edit') }}" @class(['active' => $active === 'notifications'])>{{ __('ui.notification_settings') }}</a>
    <a href="{{ route('settings.privacy.edit') }}" @class(['active' => $active === 'privacy'])>{{ __('ui.privacy') }}</a>
    <a href="{{ route('settings.privacy.blocks') }}" @class(['active' => $active === 'blocks'])>{{ __('ui.account_blocked_users') }}</a>
    <a href="{{ route('settings.security.index') }}" @class(['active' => $active === 'security'])>{{ __('ui.security') }}</a>
</nav>
