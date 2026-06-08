@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.notifications') . ' · hnt.rocks')
@section('main_class', 'notifications-main')

@section('content')
@php
    $unreadCount = (int) ($unreadCount ?? 0);
    $totalNotifications = (int) ($totalNotifications ?? 0);
@endphp

<div class="notifications-shell-page">
    <header class="notifications-hero">
        <div class="notifications-hero-copy">
            <span class="notifications-kicker">{{ __('ui.notification_center') }}</span>
            <h1>{{ __('ui.notifications') }}</h1>
            <p>{{ __('ui.notification_center_text') }}</p>
        </div>

        <div class="notifications-hero-actions">
            @if ($unreadCount > 0)
                <form method="post" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="btn-create" type="submit">
                        <i class="ph ph-checks" aria-hidden="true"></i>
                        <span>{{ __('ui.mark_all_read') }}</span>
                    </button>
                </form>
            @endif

            <a class="notifications-secondary-action" href="{{ route('account.settings.edit') }}">
                <i class="ph ph-sliders-horizontal" aria-hidden="true"></i>
                <span>{{ __('ui.settings') }}</span>
            </a>
        </div>

        <div class="notifications-hero-stats" aria-label="{{ __('ui.notifications') }}">
            <div>
                <strong>{{ number_format($totalNotifications, 0, ',', '.') }}</strong>
                <span>{{ __('ui.notifications_total') }}</span>
            </div>
            <div>
                <strong>{{ number_format($unreadCount, 0, ',', '.') }}</strong>
                <span>{{ __('ui.notifications_unread_label') }}</span>
            </div>
        </div>
    </header>

    @if (session('status'))
        <div class="hnt-preview-alert success">{{ session('status') }}</div>
    @endif

    <section class="notifications-list-card" aria-label="{{ __('ui.notifications') }}">
        @forelse ($notifications as $notification)
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
                $icon = 'bell';

                if (str_contains($type, 'comment') || str_contains($type, 'reply')) {
                    $icon = 'chat-circle-text';
                } elseif (str_contains($type, 'reaction') || str_contains($type, 'like')) {
                    $icon = 'heart';
                } elseif (str_contains($type, 'mention')) {
                    $icon = 'at';
                } elseif (str_contains($type, 'message')) {
                    $icon = 'chats-circle';
                } elseif (str_contains($type, 'cup')) {
                    $icon = 'trophy';
                } elseif (str_contains($type, 'badge') || str_contains($type, 'quest') || str_contains($type, 'referral')) {
                    $icon = 'seal-check';
                } elseif (str_contains($type, 'lfg')) {
                    $icon = 'users-three';
                }
            @endphp

            <article @class(['notification-row', 'is-unread' => $notification->isUnread(), 'is-read' => ! $notification->isUnread()])>
                <div class="notification-row-icon" aria-hidden="true">
                    <i class="ph ph-{{ $icon }}"></i>
                </div>

                <a class="notification-row-avatar" href="{{ $actor ? route('profile.public', $actor) : '#' }}" @if(! $actor) tabindex="-1" aria-hidden="true" @endif>
                    <img src="{{ $avatarUrl }}" alt="">
                </a>

                <div class="notification-row-copy">
                    <div class="notification-row-titleline">
                        <h2>{{ $notificationTitle }}</h2>
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

                <form class="notification-row-action" method="post" action="{{ route('notifications.read', $notification) }}">
                    @csrf
                    <button type="submit" title="{{ $hasAction ? __('ui.notification_open') : __('ui.notification_mark_read') }}" aria-label="{{ $hasAction ? __('ui.notification_open') : __('ui.notification_mark_read') }}">
                        <i class="ph ph-caret-right" aria-hidden="true"></i>
                    </button>
                </form>
            </article>
        @empty
            <div class="notifications-empty-card">
                <i class="ph ph-bell-slash" aria-hidden="true"></i>
                <h2>{{ __('ui.notifications_empty_title') }}</h2>
                <p>{{ __('ui.notifications_empty_text') }}</p>
            </div>
        @endforelse
    </section>

    @if ($hasMoreNotifications && $nextNotificationsUrl)
        <div class="notifications-load-wrap">
            <a class="btn-create" href="{{ $nextNotificationsUrl }}">
                <i class="ph ph-arrow-down" aria-hidden="true"></i>
                <span>{{ __('ui.notifications_load_more') }}</span>
            </a>
        </div>
    @endif
</div>
@endsection
