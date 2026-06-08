@php
    $currentUser = auth()->user();
    $displayName = $currentUser?->name ?: ($currentUser?->username ?: 'Admin');
    $username = $currentUser?->username ? '@'.$currentUser->username : 'HNT.rocks';
    $avatarUrl = $currentUser?->avatarUrl();
    $safeRoute = static function (string $routeName, string $fallback = '/') : string {
        return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : url($fallback);
    };
    $languageRoute = static function (string $locale) : string {
        return \Illuminate\Support\Facades\Route::has('locale.switch')
            ? route('locale.switch', ['locale' => $locale])
            : url('/language/'.$locale);
    };
    $currentLocale = app()->getLocale();
    $isActive = static function (array|string $patterns): bool {
        foreach ((array) $patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }
        return false;
    };
@endphp

<aside class="sidebar-left" aria-label="{{ __('ui.sidebar_navigation') }}">
    <div class="logo-section">
        <a class="logo-brand" href="{{ $safeRoute('feed.index', '/feed') }}" aria-label="{{ __('ui.feed') }}">
            
            <span>HNT.rocks</span>
        </a>
        <button class="icon-btn sidebar-toggle" type="button" aria-label="{{ __('ui.preview_nav_collapse_sidebar') }}" aria-expanded="true">
            <i class="ph ph-caret-left" aria-hidden="true"></i>
        </button>
    </div>

    <div class="user-profile" id="profileTrigger" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
        <span class="avatar avatar-md hnt-avatar-shell">
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $displayName }}">
            @endif
        </span>
        <div class="user-info">
            <span class="name">{{ $displayName }}</span>
        </div>
        <i class="ph ph-caret-down chevron" aria-hidden="true"></i>
    </div>

    <div class="profile-menu" id="profileMenu" aria-hidden="true">
        <a href="{{ $safeRoute('profile.show', '/profile') }}">{{ __('ui.preview_nav_open_profile') }}</a>
        <a href="{{ $safeRoute('profile.edit', '/profile/edit') }}">{{ __('ui.preview_nav_edit_profile') }}</a>
        <a href="{{ $safeRoute('account.settings.edit', '/account/settings') }}">{{ __('ui.preview_nav_settings') }}</a>
        <div class="profile-menu-language" aria-label="{{ __('ui.auth_language_switch_label') }}">
            <span class="profile-menu-language-title">{{ __('ui.language') }}</span>
            <span class="profile-menu-language-options" role="group" aria-label="{{ __('ui.auth_language_switch_label') }}">
                <a href="{{ $languageRoute('de') }}" lang="de" hreflang="de" @class(['profile-menu-language-pill', 'is-active' => $currentLocale === 'de']) @if($currentLocale === 'de') aria-current="true" @endif>DE</a>
                <a href="{{ $languageRoute('en') }}" lang="en" hreflang="en" @class(['profile-menu-language-pill', 'is-active' => $currentLocale === 'en']) @if($currentLocale === 'en') aria-current="true" @endif>EN</a>
            </span>
        </div>
        <form method="post" action="{{ $safeRoute('logout', '/logout') }}">
            @csrf
            <button type="submit">{{ __('ui.preview_nav_logout') }}</button>
        </form>
    </div>

    <nav class="main-nav">
        <a href="{{ $safeRoute('feed.index', '/feed') }}" @class(['nav-item', 'active' => $isActive(['feed.*'])])>
            @if($isActive(['feed.*']))<div class="active-indicator"></div>@endif
            <i class="ph ph-newspaper" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_feed') }}</span>
        </a>

        <a href="{{ $safeRoute('members.index', '/members') }}" @class(['nav-item', 'active' => $isActive(['members.*'])])>
            @if($isActive(['members.*']))<div class="active-indicator"></div>@endif
            <i class="ph ph-users" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_members') }}</span>
        </a>

        <a href="{{ $safeRoute('moments.index', '/moments') }}" @class(['nav-item', 'active' => $isActive(['moments.*'])])>
            @if($isActive(['moments.*']))<div class="active-indicator"></div>@endif
            <i class="ph ph-play-circle" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_moments') }}</span>
        </a>

        <div class="nav-spacer"></div>

        @php
            $previewSidebarUnreadNotifications = $currentUser
                ? $currentUser->notificationItems()->standard()->unread()->count()
                : 0;
            $previewSidebarPendingFriendRequests = $currentUser
                ? \App\Models\Friendship::query()
                    ->where('recipient_id', $currentUser->id)
                    ->where('status', \App\Models\Friendship::STATUS_PENDING)
                    ->count()
                : 0;
            $previewSidebarUnreadMessages = $currentUser && method_exists($currentUser, 'unreadMessagesCount')
                ? $currentUser->unreadMessagesCount()
                : 0;
        @endphp

        <a href="{{ $safeRoute('notifications.index', '/notifications') }}" @class(['nav-item', 'active' => $isActive(['notifications.*'])]) data-hnt-notifications-open aria-controls="hntNotificationShell" aria-expanded="false">
            @if($isActive(['notifications.*']))<div class="active-indicator"></div>@endif
            <i class="ph ph-bell" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_notifications') }}</span>
            <span class="badge" data-hnt-notification-count aria-live="polite" @if($previewSidebarUnreadNotifications <= 0) hidden @endif>{{ $previewSidebarUnreadNotifications > 99 ? '99+' : $previewSidebarUnreadNotifications }}</span>
        </a>

        <a href="{{ $safeRoute('members.index', '/members') }}?relationship=pending" @class(['nav-item', 'nav-friend-requests', 'active' => request()->routeIs('members.*') && request('relationship') === 'pending']) data-hnt-friend-requests-open aria-controls="hntFriendRequestShell" aria-expanded="false">
            @if(request()->routeIs('members.*') && request('relationship') === 'pending')<div class="active-indicator"></div>@endif
            <i class="ph ph-user-plus nav-friend-requests-icon" aria-hidden="true"></i>
            <span>{{ __('ui.friend_requests') }}</span>
            <span class="badge" data-hnt-friend-request-count aria-live="polite" @if($previewSidebarPendingFriendRequests <= 0) hidden @endif>{{ $previewSidebarPendingFriendRequests > 99 ? '99+' : $previewSidebarPendingFriendRequests }}</span>
        </a>

        <a href="{{ $safeRoute('messages.index', '/messages') }}" @class(['nav-item', 'active' => $isActive(['messages.*'])]) data-hnt-messages-open aria-controls="hntMessageShell" aria-expanded="false">
            @if($isActive(['messages.*']))<div class="active-indicator"></div>@endif
            <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_messages') }}</span>
            <span class="badge" data-hnt-message-count aria-live="polite" @if($previewSidebarUnreadMessages <= 0) hidden @endif>{{ $previewSidebarUnreadMessages > 99 ? '99+' : $previewSidebarUnreadMessages }}</span>
        </a>

        <div class="nav-spacer nav-spacer-small"></div>

        <a href="{{ $safeRoute('profile.show', '/profile') }}" @class(['nav-item', 'active' => $isActive(['profile.*', 'account.settings.*', 'settings.*'])])>
            @if($isActive(['profile.*', 'account.settings.*', 'settings.*']))<div class="active-indicator"></div>@endif
            <i class="ph ph-user-circle" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_profile') }}</span>
        </a>

        <div class="nav-dropdown" data-nav-group="cups">
            <button @class(['nav-item', 'nav-dropdown-toggle', 'active' => $isActive(['cups.*', 'hall-of-fame.*', 'cup-feedback.*', 'cup-ideas.*'])]) type="button" aria-expanded="false">
                @if($isActive(['cups.*', 'hall-of-fame.*', 'cup-feedback.*', 'cup-ideas.*']))<div class="active-indicator"></div>@endif
                <i class="ph ph-trophy" aria-hidden="true"></i>
                <span>{{ __('ui.preview_nav_cups') }}</span>
                <i class="ph ph-caret-down nav-dropdown-chev" aria-hidden="true"></i>
            </button>
            <div class="nav-submenu">
                <button class="nav-submenu-close" type="button" aria-label="{{ __('ui.preview_nav_close_menu') }}"><i class="ph ph-x" aria-hidden="true"></i></button>
                <a href="{{ $safeRoute('cups.index', '/cups') }}" @class(['nav-subitem', 'active' => request()->routeIs('cups.index')])>{{ __('ui.preview_nav_cups_overview') }}</a>
                <a href="{{ $safeRoute('hall-of-fame.index', '/hall-of-fame') }}" @class(['nav-subitem', 'active' => request()->routeIs('hall-of-fame.*')])>{{ __('ui.hall_of_fame') }}</a>
                <a href="{{ $safeRoute('cup-feedback.create', '/cup-feedback') }}" @class(['nav-subitem', 'active' => request()->routeIs('cup-feedback.*')])>{{ __('ui.preview_nav_cup_feedback') }}</a>
                <a href="{{ $safeRoute('cup-ideas.index', '/cup-ideas') }}" @class(['nav-subitem', 'active' => request()->routeIs('cup-ideas.*')])>{{ __('ui.preview_nav_cup_ideas') }}</a>
            </div>
        </div>

        <div class="nav-dropdown" data-nav-group="lfg">
            <button @class(['nav-item', 'nav-dropdown-toggle', 'active' => $isActive(['lfg.*'])]) type="button" aria-expanded="false">
                @if($isActive(['lfg.*']))<div class="active-indicator"></div>@endif
                <i class="ph ph-crosshair" aria-hidden="true"></i>
                <span>{{ __('ui.preview_nav_lfg') }}</span>
                <i class="ph ph-caret-down nav-dropdown-chev" aria-hidden="true"></i>
            </button>
            <div class="nav-submenu">
                <button class="nav-submenu-close" type="button" aria-label="{{ __('ui.preview_nav_close_menu') }}"><i class="ph ph-x" aria-hidden="true"></i></button>
                <a href="{{ $safeRoute('lfg.index', '/lfg') }}" class="nav-subitem">{{ __('ui.preview_nav_lfg_find') }}</a>
                <a href="{{ $safeRoute('lfg.create', '/lfg/create') }}" class="nav-subitem" data-hnt-lfg-create-open>{{ __('ui.lfg_create') }}</a>
            </div>
        </div>

        <div class="nav-dropdown" data-nav-group="crowns">
            <button @class(['nav-item', 'nav-dropdown-toggle', 'active' => $isActive(['crowns.*'])]) type="button" aria-expanded="false">
                @if($isActive(['crowns.*']))<div class="active-indicator"></div>@endif
                <i class="ph ph-crown-simple" aria-hidden="true"></i>
                <span>{{ __('ui.preview_nav_crowns') }}</span>
                <i class="ph ph-caret-down nav-dropdown-chev" aria-hidden="true"></i>
            </button>
            <div class="nav-submenu">
                <button class="nav-submenu-close" type="button" aria-label="{{ __('ui.preview_nav_close_menu') }}"><i class="ph ph-x" aria-hidden="true"></i></button>
                <a href="{{ $safeRoute('crowns.index', '/crowns') }}" class="nav-subitem">{{ __('ui.preview_nav_crowns_wallet') }}</a>
                <a href="{{ $safeRoute('crowns.inventory', '/crowns/inventory') }}" class="nav-subitem">{{ __('ui.preview_nav_crowns_inventory') }}</a>
                <a href="{{ $safeRoute('crowns.shop', '/crowns/shop') }}" class="nav-subitem">{{ __('ui.preview_nav_shop') }}</a>
                <a href="{{ $safeRoute('crowns.history', '/crowns/history') }}" class="nav-subitem">{{ __('ui.preview_nav_crowns_history') }}</a>
            </div>
        </div>

        <a href="{{ $safeRoute('gamification.index', '/gamification') }}" @class(['nav-item', 'active' => $isActive(['gamification.*'])])>
            @if($isActive(['gamification.*']))<div class="active-indicator"></div>@endif
            <i class="ph ph-medal" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_badges') }}</span>
        </a>

        <a href="{{ $safeRoute('trophy-room.index', '/trophy-room') }}" @class(['nav-item', 'active' => $isActive(['trophy-room.*'])])>
            @if($isActive(['trophy-room.*']))<div class="active-indicator"></div>@endif
            <i class="ph ph-cube" aria-hidden="true"></i>
            <span>{{ __('ui.trophy_room_title') }}</span>
        </a>
    </nav>

    <div class="bottom-nav">
        <div class="nav-dropdown nav-dropdown-legal" data-nav-group="legal">
            <button @class(['nav-item', 'nav-dropdown-toggle', 'active' => $isActive(['legal.*'])]) type="button" aria-expanded="false">
                @if($isActive(['legal.*']))<div class="active-indicator"></div>@endif
                <i class="ph ph-scales" aria-hidden="true"></i>
                <span>{{ __('ui.legal_navigation') }}</span>
                <i class="ph ph-caret-down nav-dropdown-chev" aria-hidden="true"></i>
            </button>
            <div class="nav-submenu nav-submenu-legal">
                <button class="nav-submenu-close" type="button" aria-label="{{ __('ui.preview_nav_close_menu') }}"><i class="ph ph-x" aria-hidden="true"></i></button>
                <a href="{{ $safeRoute('legal.impressum', '/impressum') }}" @class(['nav-subitem', 'active' => request()->routeIs('legal.impressum')])>{{ __('ui.legal_impressum') }}</a>
                <a href="{{ $safeRoute('legal.datenschutz', '/datenschutz') }}" @class(['nav-subitem', 'active' => request()->routeIs('legal.datenschutz')])>{{ __('ui.legal_datenschutz') }}</a>
                <a href="{{ $safeRoute('legal.nutzungsbedingungen', '/nutzungsbedingungen') }}" @class(['nav-subitem', 'active' => request()->routeIs('legal.nutzungsbedingungen')])>{{ __('ui.legal_nutzungsbedingungen') }}</a>
                <a href="{{ $safeRoute('legal.netiquette', '/netiquette') }}" @class(['nav-subitem', 'active' => request()->routeIs('legal.netiquette')])>{{ __('ui.legal_netiquette') }}</a>
                <a href="{{ $safeRoute('legal.account_deletion', '/account-deletion') }}" @class(['nav-subitem', 'active' => request()->routeIs('legal.account_deletion')])>{{ __('ui.legal_account_deletion') }}</a>
                <a href="{{ $safeRoute('legal.child_safety', '/child-safety-standards') }}" @class(['nav-subitem', 'active' => request()->routeIs('legal.child_safety')])>{{ __('ui.legal_child_safety') }}</a>
                <button type="button" class="nav-subitem nav-subitem-button" data-hh-cookie-settings-open>{{ __('ui.cookie_settings') }}</button>
            </div>
        </div>
    </div>

    <button class="btn-create" type="button" id="openComposer">{{ __('ui.preview_nav_create_post') }}</button>
</aside>
