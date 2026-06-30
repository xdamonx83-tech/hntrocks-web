@php
    $viewer = auth()->user();
    $viewer?->loadMissing('profile');

    $defaultAvatar = asset('assets/vikinger/img/default-avatar.svg');
    $headerAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $headerName = $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter');
    $headerHandle = $viewer?->username ? '@'.$viewer->username : __('ui.members');
    $currentLocale = app()->getLocale() === 'en' ? 'en' : 'de';

    $marksBalance = (int) (
        ($socialiteCrownsSummary['balance'] ?? null)
        ?? ($viewer?->crownWallet?->balance ?? 0)
    );

    $shopUrl = \Illuminate\Support\Facades\Route::has('crowns.shop') ? route('crowns.shop') : null;
    $notificationsUrl = \Illuminate\Support\Facades\Route::has('notifications.index') ? route('notifications.index') : '#';
    $messagesUrl = \Illuminate\Support\Facades\Route::has('messages.index') ? route('messages.index') : '#';

    $headerNotificationsUnread = ($viewer && method_exists($viewer, 'notificationItems'))
        ? $viewer->notificationItems()->standard()->unread()->count()
        : 0;

    $headerNotifications = ($viewer && method_exists($viewer, 'notificationItems'))
        ? $viewer->notificationItems()
            ->standard()
            ->with('actor.profile')
            ->orderByRaw('read_at is not null')
            ->latest()
            ->limit(5)
            ->get()
        : collect();

    $headerFriendRequests = $viewer
        ? \App\Models\Friendship::query()
            ->where('recipient_id', $viewer->id)
            ->where('status', \App\Models\Friendship::STATUS_PENDING)
            ->with('requester.profile')
            ->latest()
            ->limit(5)
            ->get()
        : collect();

    $headerFriendRequestCount = $viewer
        ? \App\Models\Friendship::query()
            ->where('recipient_id', $viewer->id)
            ->where('status', \App\Models\Friendship::STATUS_PENDING)
            ->count()
        : 0;

    $headerMessageConversations = $viewer
        ? \App\Models\Conversation::query()
            ->forUser($viewer)
            ->where('type', 'private')
            ->with(['users.profile', 'users.privacySettings', 'latestMessage.user'])
            ->latest('updated_at')
            ->limit(5)
            ->get()
        : collect();

    $headerMessagesUnread = $viewer && method_exists($viewer, 'unreadMessagesCount')
        ? $viewer->unreadMessagesCount()
        : 0;

    $headerLiveBadgesUrl = \Illuminate\Support\Facades\Route::has('socialite.header.live-badges')
        ? route('socialite.header.live-badges')
        : null;

    $headerLiveNotificationsUrl = \Illuminate\Support\Facades\Route::has('socialite.header.notifications')
        ? route('socialite.header.notifications', ['variant' => 'rework'])
        : null;

    $headerLiveMessagesUrl = \Illuminate\Support\Facades\Route::has('socialite.header.messages')
        ? route('socialite.header.messages', ['variant' => 'rework'])
        : null;

    $headerLiveFriendRequestsUrl = \Illuminate\Support\Facades\Route::has('socialite.header.friend-requests')
        ? route('socialite.header.friend-requests', ['variant' => 'rework'])
        : null;
@endphp

<header class="topbar">
    <form
    action="{{ route('search.index') }}"
    class="search-box rework-global-search"
    data-rework-global-search
    data-suggest-url="{{ \Illuminate\Support\Facades\Route::has('socialite.header.search') ? route('socialite.header.search') : '' }}"
    method="get"
    role="search"
    autocomplete="off"
>
    <label class="sr-only" for="rework-global-search-input">{{ __('ui.search') }}</label>
    <i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i>
    <input id="rework-global-search-input" name="q" placeholder="Search" type="search" value="{{ request('q') }}" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" aria-autocomplete="list">
    <button aria-label="{{ __('ui.search') }}" type="submit"><i aria-hidden="true" class="ph ph-arrow-right ph-icon"></i></button>
    <div class="rework-search-suggestions" data-rework-global-search-panel hidden>
        <div class="rework-search-suggestions-list" data-rework-global-search-list></div>
        <a class="rework-search-suggestions-footer" data-rework-global-search-all href="{{ route('search.index') }}">{{ __('ui.rework_search_all_results') }}</a>
    </div>
</form>

    <div
        class="top-actions"
        data-rework-header-live
        @if($headerLiveBadgesUrl) data-rework-live-badges-url="{{ $headerLiveBadgesUrl }}" @endif
        @if($headerLiveNotificationsUrl) data-rework-live-notifications-url="{{ $headerLiveNotificationsUrl }}" @endif
        @if($headerLiveMessagesUrl) data-rework-live-messages-url="{{ $headerLiveMessagesUrl }}" @endif
        @if($headerLiveFriendRequestsUrl) data-rework-live-friend-requests-url="{{ $headerLiveFriendRequestsUrl }}" @endif
    >
        <div class="action-menu notification-menu" data-rework-header-menu="notifications">
            <a aria-expanded="false" aria-label="{{ __('ui.notifications') }}" class="action-btn" data-dropdown-toggle="" href="#">
                <i aria-hidden="true" class="ph ph-bell ph-icon"></i>
                @if($headerNotificationsUnread > 0)
                    <span class="action-count" data-rework-notification-count>{{ $headerNotificationsUnread > 99 ? '99+' : $headerNotificationsUnread }}</span>
                @endif
            </a>
            <div class="top-dropdown notification-dropdown" data-dropdown-panel="">
                <div class="dropdown-head">
                    <div>
                        <strong>{{ __('ui.notifications') }}</strong>
                        <span data-rework-notification-summary data-label="{{ __('ui.notifications_unread_label') }}">{{ __('ui.notifications_unread_label') }}: {{ number_format($headerNotificationsUnread) }}</span>
                    </div>
                    <a href="{{ $notificationsUrl }}">{{ __('ui.notifications_total') }}</a>
                </div>
                <div class="dropdown-list rework-dropdown-scroll" data-rework-notification-list>
                    @include('themes.rework.feed.partials.header-notifications', ['headerNotifications' => $headerNotifications, 'notificationsUrl' => $notificationsUrl, 'defaultAvatar' => $defaultAvatar])
                </div>
                <a class="dropdown-footer" href="{{ $notificationsUrl }}">{{ __('ui.view_all_notifications') }}</a>
            </div>
        </div>

        <div class="action-menu friend-request-menu" data-rework-header-menu="friendRequests">
            <a aria-expanded="false" aria-label="{{ __('ui.friend_requests') }}" class="action-btn" data-dropdown-toggle="" href="#">
                <i aria-hidden="true" class="ph ph-user-plus ph-icon"></i>
                @if($headerFriendRequestCount > 0)
                    <span class="action-count" data-rework-friend-request-count>{{ $headerFriendRequestCount > 99 ? '99+' : $headerFriendRequestCount }}</span>
                @endif
            </a>
            <div class="top-dropdown friend-request-dropdown" data-dropdown-panel="">
                <div class="dropdown-head">
                    <div>
                        <strong>{{ __('ui.friend_requests') }}</strong>
                        <span data-rework-friend-request-summary data-label="{{ __('ui.notifications_total') }}">{{ number_format($headerFriendRequestCount) }} {{ __('ui.notifications_total') }}</span>
                    </div>
                    <a href="{{ \Illuminate\Support\Facades\Route::has('profile.friends') ? route('profile.friends') : '#' }}">{{ __('ui.notifications_total') }}</a>
                </div>
                <div class="dropdown-list request-list rework-dropdown-scroll" data-rework-friend-request-list data-empty-label="{{ __('ui.friend_requests_empty') }}">
                    @include('themes.rework.feed.partials.header-friend-requests', ['headerFriendRequests' => $headerFriendRequests, 'defaultAvatar' => $defaultAvatar])
                </div>
                <a class="dropdown-footer" href="{{ \Illuminate\Support\Facades\Route::has('profile.friends') ? route('profile.friends') : '#' }}">{{ __('ui.more_friend_requests') }}</a>
            </div>
        </div>

        <div class="action-menu message-menu" data-rework-header-menu="messages">
            <a aria-expanded="false" aria-label="{{ __('ui.messages') }}" class="action-btn" data-dropdown-toggle="" href="#">
                <i aria-hidden="true" class="ph ph-chat-circle-dots ph-icon"></i>
                @if($headerMessagesUnread > 0)
                    <span class="action-count" data-rework-message-count>{{ $headerMessagesUnread > 99 ? '99+' : $headerMessagesUnread }}</span>
                @endif
            </a>
            <div class="top-dropdown message-dropdown" data-dropdown-panel="">
                <div class="dropdown-head">
                    <div>
                        <strong>{{ __('ui.messages') }}</strong>
                        <span data-rework-message-summary data-label="{{ __('ui.notifications_unread_label') }}">{{ __('ui.notifications_unread_label') }}: {{ number_format($headerMessagesUnread) }}</span>
                    </div>
                    <a href="{{ $messagesUrl }}">{{ __('ui.messages_total') }}</a>
                </div>
                <div class="dropdown-list rework-dropdown-scroll" data-rework-message-list>
                    @include('themes.rework.feed.partials.header-messages', ['headerMessageConversations' => $headerMessageConversations, 'viewer' => $viewer, 'defaultAvatar' => $defaultAvatar])
                </div>
                <a class="dropdown-footer" href="{{ $messagesUrl }}">{{ __('ui.view_all_messages') }}</a>
            </div>
        </div>

        <div class="action-menu user-menu">
            <a aria-expanded="false" aria-label="{{ __('ui.rework_topbar_user_menu') }}" class="avatar-wrap" data-dropdown-toggle="" href="#">
                <img alt="{{ $headerName }}" class="header-avatar" data-rework-profile-avatar src="{{ $headerAvatar }}"/>
            </a>
            <div class="top-dropdown user-dropdown" data-dropdown-panel="">
                <div class="user-dropdown-head">
                    <img alt="{{ $headerName }}" data-rework-profile-avatar src="{{ $headerAvatar }}"/>
                    <div>
                        <strong data-rework-profile-name>{{ $headerName }}</strong>
                        <span>{{ $headerHandle }} &middot; {{ number_format($marksBalance) }} {{ __('ui.crowns_label') }}</span>
                    </div>
                </div>
                <div class="user-menu-list">
                    <a href="{{ \Illuminate\Support\Facades\Route::has('profile.show') ? route('profile.show') : '#' }}"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>{{ __('ui.my_profile') }}</span></a>
                    <a data-profile-edit-modal-open="" href="{{ \Illuminate\Support\Facades\Route::has('profile.edit') ? route('profile.edit') : '#' }}"><i aria-hidden="true" class="ph ph-pencil-simple ph-icon"></i><span>{{ __('ui.edit_profile') }}</span></a>
                    <a data-settings-modal-open="" href="{{ \Illuminate\Support\Facades\Route::has('account.settings.edit') ? route('account.settings.edit') : '#' }}"><i aria-hidden="true" class="ph ph-gear-six ph-icon"></i><span>{{ __('ui.settings') }}</span></a>

                    @if(\Illuminate\Support\Facades\Route::has('locale.switch'))
                        <div class="user-menu-language">
                            <span><i aria-hidden="true" class="ph ph-globe-hemisphere-west ph-icon"></i>{{ __('ui.language') }}</span>
                            <div>
                                <a class="{{ $currentLocale === 'de' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'de') }}" lang="de" hreflang="de" @if($currentLocale === 'de') aria-current="true" @endif>{{ __('ui.language_german') }}</a>
                                <a class="{{ $currentLocale === 'en' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'en') }}" lang="en" hreflang="en" @if($currentLocale === 'en') aria-current="true" @endif>{{ __('ui.language_english') }}</a>
                            </div>
                        </div>
                    @endif

                    @if($shopUrl)
                        <a href="{{ $shopUrl }}"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i><span>{{ __('ui.crowns_shop_kicker') }}</span></a>
                    @endif
                </div>
                <form action="{{ route('logout') }}" method="post" class="user-logout-form">
                    @csrf
                    <button class="user-logout" type="submit"><i aria-hidden="true" class="ph ph-sign-out ph-icon"></i>{{ __('ui.logout') }}</button>
                </form>
            </div>
        </div>
    </div>
</header>
