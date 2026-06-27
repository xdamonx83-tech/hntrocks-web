@php
    $defaultAvatar = $defaultAvatar ?? asset('assets/vikinger/img/default-avatar.svg');
    $notificationsUrl = $notificationsUrl ?? route('notifications.index');
@endphp

@forelse($headerNotifications as $notification)
    @php
        $actor = $notification->actor;
        $avatarUrl = method_exists($notification, 'displayActorAvatarUrl')
            ? $notification->displayActorAvatarUrl()
            : ($actor?->avatarUrl() ?: $defaultAvatar);
        $notificationTargetUrl = $notification->actionUrl() ?: $notificationsUrl;
    @endphp
    <a class="dropdown-item {{ $notification->isUnread() ? 'unread' : '' }}" href="{{ $notificationTargetUrl }}" data-rework-notification-read data-read-url="{{ route('notifications.read', $notification) }}" data-target-url="{{ $notificationTargetUrl }}">
        <img alt="" src="{{ $avatarUrl }}"/>
        <span><strong>{{ $notification->displayTitle() }}</strong><small>{{ $notification->displayBody() ?: __('ui.notifications') }}</small></span>
        <em>{{ $notification->created_at?->diffForHumans() }}</em>
    </a>
@empty
    <div class="dropdown-empty"><i aria-hidden="true" class="ph ph-bell ph-icon"></i><span>{{ __('ui.no_notifications') }}</span></div>
@endforelse
