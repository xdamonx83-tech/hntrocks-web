@php
    $previewNotificationUser = auth()->user();
    $previewNotifications = collect();
    $previewNotificationsTotal = 0;
    $previewNotificationsUnread = 0;

    if ($previewNotificationUser) {
        $previewNotificationsTotal = $previewNotificationUser->notificationItems()->standard()->count();
        $previewNotificationsUnread = $previewNotificationUser->notificationItems()->standard()->unread()->count();
        $previewNotifications = $previewNotificationUser
            ->notificationItems()
            ->standard()
            ->with('actor.profile')
            ->latest()
            ->limit(40)
            ->get();
    }
@endphp

<aside class="hnt-notification-shell" id="hntNotificationShell" aria-label="{{ __('ui.notifications') }}" aria-hidden="true">
    <div class="hnt-notification-shell-head">
        <div>
            <span class="hnt-notification-shell-kicker">{{ __('ui.account') }}</span>
            <h2>{{ __('ui.notifications') }}</h2>
        </div>
        <div class="hnt-notification-shell-actions">
            <a class="hnt-notification-shell-icon" href="{{ route('account.settings.edit') }}" aria-label="{{ __('ui.settings') }}">
                <i class="ph ph-gear-six" aria-hidden="true"></i>
            </a>
            <button class="hnt-notification-shell-icon" type="button" data-hnt-notifications-close aria-label="{{ __('ui.close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="hnt-notification-shell-meta">
        <span><strong data-hnt-notifications-unread-count>{{ $previewNotificationsUnread }}</strong> {{ __('ui.notifications_unread_label') }}</span>
        <span><strong data-hnt-notifications-total-count>{{ $previewNotificationsTotal }}</strong> {{ __('ui.notifications_total') }}</span>
    </div>

    <form class="hnt-notification-shell-read-all" method="post" action="{{ route('notifications.read-all') }}" data-hnt-notification-read-all @if ($previewNotificationsUnread <= 0) hidden @endif>
        @csrf
        <button type="submit">{{ __('ui.mark_all_read') }}</button>
    </form>

    <div class="hnt-notification-shell-list" data-hnt-notification-shell-scroll>
        @include('themes.hnt_preview.partials.notification-shell-items', [
            'previewNotifications' => $previewNotifications,
        ])
    </div>

    <div class="hnt-notification-shell-foot">
        <a href="{{ route('notifications.index') }}">{{ __('ui.view_all_notifications') }}</a>
        <a href="{{ route('account.settings.edit') }}">{{ __('ui.settings') }}</a>
    </div>
</aside>
