@extends('layouts.app')

@section('title', __('ui.notifications') . ' · hnt.rocks')

@section('content')
<div class="hh-profile-notifications-page">
    <div class="section-banner hh-account-hub-banner">
        <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="" aria-hidden="true">
        <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
        <p class="section-banner-text">{{ __('ui.notifications_banner_text') }}</p>
    </div>

    <div class="grid grid-3-9 medium-space hh-account-hub-grid hh-profile-notifications-grid">
        @include('teams.partials.account-sidebar', [
            'activeSection' => 'profile',
            'activeLink' => 'notifications',
        ])

        <div class="account-hub-content">
            <div class="section-header hh-profile-notifications-section-header">
                <div class="section-header-info">
                    <p class="section-pretitle">{{ __('ui.my_profile') }}</p>
                    <h2 class="section-title">{{ __('ui.notifications') }}</h2>
                </div>

                <div class="section-header-actions hh-profile-notifications-header-actions">
                    <p class="section-header-action hh-profile-notifications-stat"><span class="highlighted">{{ $totalNotifications }}</span> {{ __('ui.notifications_total') }}</p>
                    <p class="section-header-action hh-profile-notifications-stat"><span class="highlighted">{{ $unreadCount }}</span> {{ __('ui.notifications_unread_label') }}</p>

                    @if ($unreadCount > 0)
                        <form class="hh-profile-notifications-read-all" method="post" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button class="hh-profile-notifications-text-action" type="submit">{{ __('ui.mark_all_read') }}</button>
                        </form>
                    @endif

                    <a class="hh-profile-notifications-text-action" href="{{ route('account.settings.edit') }}">{{ __('ui.settings') }}</a>
                </div>
            </div>

            @if (session('status'))
                <div class="hh-alert hh-alert-success hh-profile-notifications-alert">{{ session('status') }}</div>
            @endif

            <div class="hh-profile-notifications-list">
                @forelse ($notifications as $notification)
                    @include('notifications.partials.notification-item', ['notification' => $notification])
                @empty
                    <div class="hh-profile-notifications-empty">
                        <i class="hh-profile-notifications-empty-icon hh-ph-action-icon ph ph-bell" aria-hidden="true"></i>
                        <p class="hh-profile-notifications-empty-title">{{ __('ui.notifications_empty_title') }}</p>
                        <p class="hh-profile-notifications-empty-text">{{ __('ui.notifications_empty_text') }}</p>
                    </div>
                @endforelse
            </div>

            @if ($hasMoreNotifications && $nextNotificationsUrl)
                <div class="hh-profile-notifications-load-wrap">
                    <a class="button secondary hh-profile-notifications-load" href="{{ $nextNotificationsUrl }}">{{ __('ui.notifications_load_more') }}</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
