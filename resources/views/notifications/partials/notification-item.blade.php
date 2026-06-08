@php
    $actor = $notification->actor;
    $notificationTitle = $notification->displayTitle();
    $notificationBody = $notification->displayBody();
    $actorName = method_exists($notification, 'displayActorName')
        ? $notification->displayActorName()
        : ($actor?->name ?? __('ui.hnt_rocks_system'));
    $avatarUrl = method_exists($notification, 'displayActorAvatarUrl')
        ? $notification->displayActorAvatarUrl()
        : ($actor?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg'));
    $hasAction = filled($notification->actionUrl());
    $type = strtolower((string) $notification->type);
    $icon = 'notification';

    if (str_contains($type, 'comment') || str_contains($type, 'reply') || str_contains($type, 'antwort')) {
        $icon = 'comment';
    } elseif (str_contains($type, 'reaction') || str_contains($type, 'like') || str_contains($type, 'love')) {
        $icon = 'thumbs-up';
    } elseif (str_contains($type, 'share')) {
        $icon = 'share';
    } elseif (str_contains($type, 'message')) {
        $icon = 'messages';
    }
@endphp

<article class="hh-profile-notification-item {{ $notification->isUnread() ? 'is-unread' : 'is-read' }}">
    <div class="hh-profile-notification-avatar" aria-hidden="true">
        @if ($actor)
            <a class="hh-profile-notification-avatar-link" href="{{ route('profile.public', $actor) }}" tabindex="-1">
                <img class="hh-profile-notification-avatar-img" src="{{ $avatarUrl }}" alt="">
            </a>
        @else
            <span class="hh-profile-notification-avatar-link">
                <img class="hh-profile-notification-avatar-img" src="{{ $avatarUrl }}" alt="">
            </span>
        @endif
    </div>

    <div class="hh-profile-notification-content">
        <div class="hh-profile-notification-title-row">
            <p class="hh-profile-notification-title">{{ $notificationTitle }}</p>
            @if ($notification->isUnread())
                <span class="hh-profile-notification-badge">{{ __('ui.notification_unread') }}</span>
            @endif
        </div>

        @if ($notificationBody)
            <p class="hh-profile-notification-text">{{ $notificationBody }}</p>
        @endif

        <p class="hh-profile-notification-meta">
            <span>{{ $actorName }}</span>
            <span aria-hidden="true">•</span>
            <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->diffForHumans() }}</time>
        </p>
    </div>

    <form class="hh-profile-notification-action" method="post" action="{{ route('notifications.read', $notification) }}">
        @csrf
        <button class="hh-profile-notification-action-button" type="submit" title="{{ $hasAction ? __('ui.notification_open') : __('ui.notification_mark_read') }}" aria-label="{{ $hasAction ? __('ui.notification_open') : __('ui.notification_mark_read') }}">
            <svg class="hh-profile-notification-action-icon icon-{{ $icon }}"><use xlink:href="#svg-{{ $icon }}"></use></svg>
        </button>
    </form>
</article>
