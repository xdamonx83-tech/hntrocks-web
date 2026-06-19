@php
    $currentUser = auth()->user();
    $displayName = $currentUser?->name ?: ($currentUser?->username ?: 'HNT.rocks');
    $avatarUrl = $currentUser?->avatarUrl();
    $safeRoute = static function (string $routeName, string $fallback = '/') : string {
        return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : url($fallback);
    };
    $isActive = static function (array|string $patterns): bool {
        foreach ((array) $patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }
        return false;
    };
    $mobileUnreadNotifications = $currentUser
        ? $currentUser->notificationItems()->standard()->unread()->count()
        : 0;
    $mobileUnreadMessages = $currentUser && method_exists($currentUser, 'unreadMessagesCount')
        ? $currentUser->unreadMessagesCount()
        : 0;
@endphp

<header class="hnt-mobile-topbar" aria-label="{{ __('ui.preview_mobile_topbar') }}">
    <a class="hnt-mobile-brand" href="{{ $currentUser ? $safeRoute('feed.index', '/feed') : $safeRoute('cups.index', '/cups') }}" aria-label="HNT.rocks">
        <i class="ph ph-star" aria-hidden="true"></i>
        <span>HNT.rocks</span>
    </a>
    <div class="hnt-mobile-top-actions">
        @auth
        <button class="hnt-mobile-icon-btn" type="button" data-hnt-messages-open aria-controls="hntMessageShell" aria-expanded="false" aria-label="{{ __('ui.preview_nav_messages') }}">
            <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
            <span class="hnt-mobile-badge" data-hnt-message-count aria-live="polite" @if($mobileUnreadMessages <= 0) hidden @endif>{{ $mobileUnreadMessages > 99 ? '99+' : $mobileUnreadMessages }}</span>
        </button>
        <button class="hnt-mobile-icon-btn" type="button" data-hnt-notifications-open aria-controls="hntNotificationShell" aria-expanded="false" aria-label="{{ __('ui.preview_nav_notifications') }}">
            <i class="ph ph-bell" aria-hidden="true"></i>
            <span class="hnt-mobile-badge" data-hnt-notification-count aria-live="polite" @if($mobileUnreadNotifications <= 0) hidden @endif>{{ $mobileUnreadNotifications > 99 ? '99+' : $mobileUnreadNotifications }}</span>
        </button>
        <button class="hnt-mobile-icon-btn is-primary" type="button" data-hnt-composer-open aria-label="{{ __('ui.preview_nav_create_post') }}">
            <i class="ph ph-plus" aria-hidden="true"></i>
        </button>
        @else
        <a class="hnt-mobile-icon-btn is-primary" href="{{ $safeRoute('login', '/login') }}" aria-label="{{ __('ui.login_submit') }}">
            <i class="ph ph-sign-in" aria-hidden="true"></i>
        </a>
        @endauth
    </div>
</header>

<nav class="hnt-mobile-bottom-bar" aria-label="{{ __('ui.preview_mobile_bottom_nav') }}">
    @auth
    <a href="{{ $safeRoute('feed.index', '/feed') }}" @class(['hnt-mobile-bottom-item', 'active' => $isActive(['feed.*'])])>
        <i class="ph ph-list" aria-hidden="true"></i>
        <span>{{ __('ui.preview_nav_feed') }}</span>
    </a>
    <a href="{{ $safeRoute('lfg.index', '/lfg') }}" @class(['hnt-mobile-bottom-item', 'active' => $isActive(['lfg.*'])])>
        <i class="ph ph-user-plus" aria-hidden="true"></i>
        <span>{{ __('ui.preview_nav_lfg') }}</span>
    </a>
    <a href="{{ $safeRoute('moments.index', '/moments') }}" @class(['hnt-mobile-bottom-item', 'active' => $isActive(['moments.*'])])>
        <i class="ph ph-film-strip" aria-hidden="true"></i>
        <span>{{ __('ui.preview_nav_moments') }}</span>
    </a>
    <a href="{{ $safeRoute('profile.show', '/profile') }}" @class(['hnt-mobile-bottom-item', 'active' => $isActive(['profile.*', 'account.settings.*', 'settings.*'])])>
        <i class="ph ph-user-circle" aria-hidden="true"></i>
        <span>{{ __('ui.preview_nav_profile') }}</span>
    </a>
    <button class="hnt-mobile-bottom-item" type="button" data-hnt-mobile-menu-open aria-controls="hntMobileMenuShell" aria-expanded="false">
        <i class="ph ph-list" aria-hidden="true"></i>
        <span>{{ __('ui.preview_nav_menu') }}</span>
    </button>
    @else
    <a href="{{ $safeRoute('cups.index', '/cups') }}" @class(['hnt-mobile-bottom-item', 'active' => $isActive(['cups.*'])])>
        <i class="ph ph-trophy" aria-hidden="true"></i>
        <span>{{ __('ui.preview_nav_cups') }}</span>
    </a>
    <a href="{{ $safeRoute('login', '/login') }}" class="hnt-mobile-bottom-item">
        <i class="ph ph-sign-in" aria-hidden="true"></i>
        <span>{{ __('ui.login_submit') }}</span>
    </a>
    <a href="{{ $safeRoute('register', '/register') }}" class="hnt-mobile-bottom-item">
        <i class="ph ph-user-plus" aria-hidden="true"></i>
        <span>{{ __('ui.register') }}</span>
    </a>
    <button class="hnt-mobile-bottom-item" type="button" data-hnt-mobile-menu-open aria-controls="hntMobileMenuShell" aria-expanded="false">
        <i class="ph ph-list" aria-hidden="true"></i>
        <span>{{ __('ui.legal_navigation') }}</span>
    </button>
    @endauth
</nav>

<div class="hnt-mobile-menu-backdrop" data-hnt-mobile-menu-close hidden></div>
<section class="hnt-mobile-menu-shell" id="hntMobileMenuShell" data-hnt-mobile-menu-shell aria-hidden="true" aria-label="{{ __('ui.preview_nav_menu') }}">
    <div class="hnt-mobile-menu-head">
        <div>
            <span>{{ __('ui.preview_mobile_more') }}</span>
            <h2>{{ __('ui.preview_nav_menu') }}</h2>
        </div>
        <button class="hnt-mobile-icon-btn" type="button" data-hnt-mobile-menu-close aria-label="{{ __('ui.preview_nav_close_menu') }}">
            <i class="ph ph-x" aria-hidden="true"></i>
        </button>
    </div>

    @auth
    <div class="hnt-mobile-menu-user">
        <span class="avatar avatar-sm hnt-avatar-shell">
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $displayName }}">
            @endif
        </span>
        <div>
            <strong>{{ $displayName }}</strong>
            <a href="{{ $safeRoute('profile.edit', '/profile/edit') }}">{{ __('ui.preview_nav_edit_profile') }}</a>
        </div>
    </div>

    <div class="hnt-mobile-menu-grid">
        <a href="{{ $safeRoute('members.index', '/members') }}">
            <i class="ph ph-users-three" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_members') }}</span>
        </a>
        <a href="{{ $safeRoute('cups.index', '/cups') }}">
            <i class="ph ph-trophy" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_cups') }}</span>
        </a>
        <a href="{{ $safeRoute('hall-of-fame.index', '/hall-of-fame') }}">
            <i class="ph ph-star" aria-hidden="true"></i>
            <span>{{ __('ui.hall_of_fame') }}</span>
        </a>
        <a href="{{ $safeRoute('gamification.index', '/gamification') }}">
            <i class="ph ph-star" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_badges') }}</span>
        </a>
        <a href="{{ $safeRoute('crowns.index', '/crowns') }}">
            <i class="ph ph-crown-simple" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_crowns') }}</span>
        </a>
        <a href="{{ $safeRoute('contracts.index', '/contracts') }}">
            <i class="ph ph-headset" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_contracts') }}</span>
        </a>
        <a href="{{ $safeRoute('loadout-challenges.index', '/loadout-challenges') }}">
            <i class="ph ph-file-text" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_challenges') }}</span>
        </a>
        <a href="{{ $safeRoute('account.settings.edit', '/account/settings') }}">
            <i class="ph ph-gear-six" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_settings') }}</span>
        </a>
    </div>

    <div class="hnt-mobile-menu-links">
        <a href="{{ $safeRoute('cup-feedback.create', '/cup-feedback') }}">{{ __('ui.preview_nav_cup_feedback') }}</a>
        <a href="{{ $safeRoute('cup-ideas.index', '/cup-ideas') }}">{{ __('ui.preview_nav_cup_ideas') }}</a>
        <a href="{{ $safeRoute('crowns.shop', '/crowns/shop') }}">{{ __('ui.preview_nav_shop') }}</a>
        <a href="{{ $safeRoute('crowns.inventory', '/crowns/inventory') }}">{{ __('ui.preview_nav_crowns_inventory') }}</a>
    </div>
    @else
    <div class="hnt-mobile-menu-user">
        <span class="avatar avatar-sm hnt-avatar-shell"><i class="ph ph-sign-in" aria-hidden="true"></i></span>
        <div>
            <strong>HNT.rocks</strong>
            <span>{{ __('ui.guest_sidebar_text') }}</span>
        </div>
    </div>
    <div class="hnt-mobile-menu-grid">
        <a href="{{ $safeRoute('cups.index', '/cups') }}">
            <i class="ph ph-trophy" aria-hidden="true"></i>
            <span>{{ __('ui.preview_nav_cups') }}</span>
        </a>
        <a href="{{ $safeRoute('login', '/login') }}">
            <i class="ph ph-sign-in" aria-hidden="true"></i>
            <span>{{ __('ui.login_submit') }}</span>
        </a>
        <a href="{{ $safeRoute('register', '/register') }}">
            <i class="ph ph-user-plus" aria-hidden="true"></i>
            <span>{{ __('ui.register') }}</span>
        </a>
    </div>
    @endauth

    <div class="hnt-mobile-menu-legal" aria-label="{{ __('ui.legal_navigation') }}">
        <span class="hnt-mobile-menu-legal-title">
            <i class="ph ph-scales" aria-hidden="true"></i>
            {{ __('ui.legal_navigation') }}
        </span>
        <div class="hnt-mobile-menu-links hnt-mobile-menu-legal-links">
            <a href="{{ $safeRoute('legal.impressum', '/impressum') }}">{{ __('ui.legal_impressum') }}</a>
            <a href="{{ $safeRoute('legal.datenschutz', '/datenschutz') }}">{{ __('ui.legal_datenschutz') }}</a>
            <a href="{{ $safeRoute('legal.nutzungsbedingungen', '/nutzungsbedingungen') }}">{{ __('ui.legal_nutzungsbedingungen') }}</a>
            <a href="{{ $safeRoute('legal.netiquette', '/netiquette') }}">{{ __('ui.legal_netiquette') }}</a>
            <a href="{{ $safeRoute('legal.account_deletion', '/account-deletion') }}">{{ __('ui.legal_account_deletion') }}</a>
            <a href="{{ $safeRoute('legal.child_safety', '/child-safety-standards') }}">{{ __('ui.legal_child_safety') }}</a>
            <button type="button" class="hnt-mobile-menu-link-button" data-hh-cookie-settings-open>{{ __('ui.cookie_settings') }}</button>
        </div>
    </div>
</section>
