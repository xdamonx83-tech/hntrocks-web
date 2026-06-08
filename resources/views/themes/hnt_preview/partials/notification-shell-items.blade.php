@forelse ($previewNotifications as $notification)
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
    @endphp
    <article class="hnt-notification-shell-item {{ $notification->isUnread() ? 'is-unread' : 'is-read' }}" data-hnt-notification-item>
        <span class="hnt-notification-shell-avatar" aria-hidden="true">
            <img src="{{ $avatarUrl }}" alt="">
        </span>
        <div class="hnt-notification-shell-copy">
            <div class="hnt-notification-shell-title-row">
                <h3>{{ $notificationTitle }}</h3>
                @if ($notification->isUnread())
                    <span>{{ __('ui.notification_unread') }}</span>
                @endif
            </div>
            @if ($notificationBody)
                <p>{{ $notificationBody }}</p>
            @endif
            <small>
                {{ $actorName }} ·
                <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->diffForHumans() }}</time>
            </small>
        </div>
        <form class="hnt-notification-shell-item-action" method="post" action="{{ route('notifications.read', $notification) }}" data-hnt-notification-read>
            @csrf
            <button type="submit" title="{{ $hasAction ? __('ui.notification_open') : __('ui.notification_mark_read') }}" aria-label="{{ $hasAction ? __('ui.notification_open') : __('ui.notification_mark_read') }}">
                <i class="ph ph-caret-right" aria-hidden="true"></i>
            </button>
        </form>
    </article>
@empty
    <div class="hnt-notification-shell-empty">
        <i class="ph ph-bell" aria-hidden="true"></i>
        <h3>{{ __('ui.notifications_empty_title') }}</h3>
        <p>{{ __('ui.notifications_empty_text') }}</p>
    </div>
@endforelse
