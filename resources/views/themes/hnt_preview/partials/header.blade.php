@php
    $headerViewer = auth()->user();
    $headerName = $headerViewer?->name ?: ($headerViewer?->username ?: 'HNT Hunter');
    $headerHandle = $headerViewer?->username ? '@'.$headerViewer->username : '@hunter';
    $headerAvatar = $headerViewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $headerLevel = max(1, (int) ($headerViewer?->level ?? 1));
    $headerRocks = (int) ($headerViewer?->crownWallet?->balance ?? 0);
    $headerIsFeed = request()->routeIs('feed.index');
    $headerIsTeams = request()->routeIs('teams.*');
    $headerIsGuides = request()->routeIs('guides.*');
    $headerIsRocks = request()->routeIs('rocks.*', 'crowns.*');
    $headerLocaleIsEnglish = app()->getLocale() === 'en';
@endphp

@once
<link
    data-hnt-shared-topbar-style
    href="{{ asset('assets/themes/hnt_preview/fuckingdropdown.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/fuckingdropdown.css')) ?: time() }}"
    rel="stylesheet"
>
@endonce

<header
    class="site-header"
    data-hnt-shared-header
    @if($headerViewer)
        data-hnt-header-endpoint="{{ route('feed.index') }}"
    @endif
>
<a aria-label="HNT.rocks {{ __('hnt_preview.header.feed') }}" class="brand" href="{{ route('feed.index') }}">
<svg aria-hidden="true" class="brand-mark" viewbox="0 0 44 34">
<path d="M8.2 5.5c4.4-4.4 10.8-4.2 14.5.1-1 4.7-4.2 8-8.8 9.3-3.9-1.4-6.2-4.7-5.7-9.4Z"></path>
<path d="M22.1 8.1c5.9-.2 10.2 4 10.4 9.2-3.4 3.2-8 4-12.3 2.1-2-3.8-1.3-8.2 1.9-11.3Z"></path>
<path d="M14.3 18.3c3.5-3.5 8.5-3.8 12.2-.9.4 4.7-1.8 8.5-6.1 10.5-4-.7-6.6-4.1-6.1-9.6Z"></path>
<circle cx="18.8" cy="15.5" r="3.4"></circle>
</svg>
<span>HNT.ROCKS</span>
</a>
<nav aria-label="{{ __('hnt_preview.header.main_navigation') }}" class="main-nav hnt-main-nav">
<a class="{{ $headerIsFeed ? 'active' : '' }}" data-page="feed" href="{{ route('feed.index') }}">{{ __('hnt_preview.header.feed') }}</a>
<div class="main-nav-item nav-community">
<button aria-controls="communityNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button"><span>{{ __('hnt_preview.header.community') }}</span><svg><use href="#i-chevron"></use></svg></button>
<section class="main-nav-dropdown align-left" id="communityNavDropdown" role="menu">
<header><span>{{ __('hnt_preview.header.community_eyebrow') }}</span><strong>{{ __('hnt_preview.header.community_title') }}</strong></header>
<div class="main-nav-menu-grid">
<button data-navigation-label="Mitglieder" role="menuitem" type="button"><span class="main-nav-menu-icon"><svg><use href="#i-users"></use></svg></span><span><strong>{{ __('hnt_preview.header.members') }}</strong><small>{{ __('hnt_preview.header.members_text') }}</small></span></button>
<button data-navigation-label="Freunde" role="menuitem" type="button"><span class="main-nav-menu-icon"><svg><use href="#i-user"></use></svg></span><span><strong>{{ __('hnt_preview.header.friends') }}</strong><small>{{ __('hnt_preview.header.friends_text') }}</small></span></button>
<button data-navigation-label="Hashtags" role="menuitem" type="button"><span class="main-nav-menu-icon"><svg><use href="#i-comment"></use></svg></span><span><strong>{{ __('hnt_preview.header.hashtags') }}</strong><small>{{ __('hnt_preview.header.hashtags_text') }}</small></span></button>
</div>
</section>
</div>
<div class="main-nav-item nav-lfg">
<button aria-controls="lfgNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button"><span>LFG</span><svg><use href="#i-chevron"></use></svg></button>
<section class="main-nav-dropdown" id="lfgNavDropdown" role="menu">
<header><span>LOOKING FOR GROUP</span><strong>{{ __('hnt_preview.header.lfg_title') }}</strong></header>
<div class="main-nav-menu-grid">
<button data-navigation-label="LFG finden" role="menuitem" type="button"><span class="main-nav-menu-icon"><svg><use href="#i-search"></use></svg></span><span><strong>{{ __('hnt_preview.header.lfg_find') }}</strong><small>{{ __('hnt_preview.header.lfg_find_text') }}</small></span></button>
<button data-navigation-label="LFG erstellen" role="menuitem" type="button"><span class="main-nav-menu-icon yellow"><svg><use href="#i-plus"></use></svg></span><span><strong>{{ __('hnt_preview.header.lfg_create') }}</strong><small>{{ __('hnt_preview.header.lfg_create_text') }}</small></span></button>
<button aria-disabled="true" data-unavailable="1" role="menuitem" type="button"><span class="main-nav-menu-icon green"><svg><use href="#i-users"></use></svg></span><span><strong>{{ __('hnt_preview.header.ready_lobbies') }}</strong><small>{{ __('hnt_preview.header.ready_lobbies_text') }}</small></span><em>{{ __('hnt_preview.header.soon') }}</em></button>
</div>
</section>
</div>
<a class="main-nav-trigger main-nav-direct" href="{{ route('moments.index') }}"><span>{{ __('hnt_preview.header.moments') }}</span></a>
<div class="main-nav-item nav-cups">
<button aria-controls="cupsNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button"><span>{{ __('hnt_preview.header.cups') }}</span><svg><use href="#i-chevron"></use></svg></button>
<section class="main-nav-dropdown" id="cupsNavDropdown" role="menu">
<header><span>{{ __('hnt_preview.header.cups_eyebrow') }}</span><strong>{{ __('hnt_preview.header.cups_title') }}</strong></header>
<div class="main-nav-menu-grid">
<button data-navigation-label="Aktive Cups" role="menuitem" type="button"><span class="main-nav-menu-icon yellow"><svg><use href="#i-folder"></use></svg></span><span><strong>{{ __('hnt_preview.header.active_cups') }}</strong><small>{{ __('hnt_preview.header.active_cups_text') }}</small></span></button>
<button data-navigation-label="Hall of Fame" role="menuitem" type="button"><span class="main-nav-menu-icon green"><svg><use href="#i-check"></use></svg></span><span><strong>{{ __('hnt_preview.header.hall_of_fame') }}</strong><small>{{ __('hnt_preview.header.hall_of_fame_text') }}</small></span></button>
</div>
</section>
</div>
<a class="main-nav-trigger main-nav-direct {{ $headerIsTeams ? 'active' : '' }}" href="{{ route('teams.index') }}"><span>{{ __('hnt_preview.header.teams') }}</span></a>
<div class="main-nav-item nav-contracts">
<button aria-controls="contractsNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button"><span>{{ __('hnt_preview.header.contracts') }}</span><svg><use href="#i-chevron"></use></svg></button>
<section class="main-nav-dropdown" id="contractsNavDropdown" role="menu">
<header><span>{{ __('hnt_preview.header.progress_eyebrow') }}</span><strong>{{ __('hnt_preview.header.contracts_title') }}</strong></header>
<div class="main-nav-menu-grid">
<button data-navigation-label="Wochenaufträge" role="menuitem" type="button"><span class="main-nav-menu-icon yellow"><svg><use href="#i-check"></use></svg></span><span><strong>{{ __('hnt_preview.header.weekly_contracts') }}</strong><small>{{ __('hnt_preview.header.weekly_contracts_text') }}</small></span></button>
<button data-navigation-label="Quests" role="menuitem" type="button"><span class="main-nav-menu-icon"><svg><use href="#i-sliders"></use></svg></span><span><strong>{{ __('hnt_preview.header.quests') }}</strong><small>{{ __('hnt_preview.header.quests_text') }}</small></span></button>
<button data-navigation-label="Loadout Challenges" role="menuitem" type="button"><span class="main-nav-menu-icon purple"><svg><use href="#i-briefcase"></use></svg></span><span><strong>{{ __('hnt_preview.header.loadout_challenges') }}</strong><small>{{ __('hnt_preview.header.loadout_challenges_text') }}</small></span></button>
<button data-navigation-label="Badges" role="menuitem" type="button"><span class="main-nav-menu-icon green"><svg><use href="#i-bookmark"></use></svg></span><span><strong>{{ __('hnt_preview.header.badges') }}</strong><small>{{ __('hnt_preview.header.badges_text') }}</small></span></button>
</div>
</section>
</div>
<div class="main-nav-item nav-more {{ $headerIsRocks ? 'is-current' : '' }}">
<button aria-controls="moreNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger {{ ($headerIsGuides || $headerIsRocks) ? 'is-current' : '' }}" type="button"><span>{{ __('hnt_preview.header.more') }}</span><svg><use href="#i-chevron"></use></svg></button>
<section class="main-nav-dropdown align-right" id="moreNavDropdown" role="menu">
<header><span>{{ __('hnt_preview.header.more_eyebrow') }}</span><strong>{{ __('hnt_preview.header.more_title') }}</strong></header>
<div class="main-nav-menu-grid">
<a class="{{ $headerIsRocks ? 'is-active' : '' }}" data-navigation-label="Shop &amp; Inventar" href="{{ route('rocks.index') }}" role="menuitem"><span class="main-nav-menu-icon yellow"><svg><use href="#i-folder"></use></svg></span><span><strong>{{ __('hnt_preview.header.shop_inventory') }}</strong><small>{{ __('hnt_preview.header.shop_inventory_text') }}</small></span></a>
<a data-navigation-label="Maps" href="{{ route('maps.index') }}" role="menuitem"><span class="main-nav-menu-icon"><svg viewBox="0 0 24 24"><path d="m3 6 5-2 8 3 5-2v13l-5 2-8-3-5 2z"></path><path d="M8 4v13M16 7v13"></path></svg></span><span><strong>Maps</strong><small>{{ $headerLocaleIsEnglish ? 'Interactive Hunt maps and community spots' : 'Interaktive Hunt-Karten und Community-Spots' }}</small></span></a>
<a data-navigation-label="Guides" href="{{ route('guides.index') }}" role="menuitem"><span class="main-nav-menu-icon"><svg><use href="#i-bookmark"></use></svg></span><span><strong>{{ __('hnt_preview.header.guides') }}</strong><small>{{ __('hnt_preview.header.guides_text') }}</small></span></a>
<button aria-disabled="true" data-unavailable="1" role="menuitem" type="button"><span class="main-nav-menu-icon purple"><svg><use href="#i-comment"></use></svg></span><span><strong>{{ __('hnt_preview.header.polls') }}</strong><small>{{ __('hnt_preview.header.polls_text') }}</small></span></button>
<button data-navigation-label="Aktivitätsverlauf" role="menuitem" type="button"><span class="main-nav-menu-icon green"><svg><use href="#i-arrow"></use></svg></span><span><strong>{{ __('hnt_preview.header.activity_history') }}</strong><small>{{ __('hnt_preview.header.activity_history_text') }}</small></span></button>
</div>
</section>
</div>
</nav>
<div class="header-actions">
@if($headerIsFeed)
<button aria-label="{{ __('hnt_preview.header.open_stats') }}" class="mobile-stats-trigger" id="mobileStatsTrigger" type="button"><svg><use href="#i-sliders"></use></svg><span>{{ __('hnt_preview.header.stats') }}</span></button>
@else
<a aria-label="{{ __('hnt_preview.header.open_feed') }}" class="mobile-stats-trigger profile-mobile-feed-link" href="{{ route('feed.index') }}"><svg><use href="#i-arrow"></use></svg><span>{{ __('hnt_preview.header.feed') }}</span></a>
@endif
@if($headerViewer)
<div class="header-action-menu settings-menu">
<button aria-controls="settingsDropdown" aria-expanded="false" aria-haspopup="true" aria-label="{{ __('hnt_preview.header.open_settings') }}" class="settings-button header-dropdown-trigger" id="settingsMenuTrigger"><svg><use href="#i-settings"></use></svg><span>{{ __('hnt_preview.header.settings') }}</span></button>
<section aria-labelledby="settingsMenuTrigger" class="header-dropdown settings-dropdown" id="settingsDropdown" role="menu">
<header class="header-dropdown-head"><div><span>HNT.ROCKS</span><strong>{{ __('hnt_preview.header.settings') }}</strong></div><small>{{ __('hnt_preview.header.personal') }}</small></header>
<div class="header-menu-list">
<a href="{{ route('account.settings.edit') }}" role="menuitem"><span class="header-menu-icon"><svg><use href="#i-settings"></use></svg></span><span><strong>{{ __('hnt_preview.header.general') }}</strong><small>{{ __('hnt_preview.header.general_text') }}</small></span><svg class="header-menu-arrow"><use href="#i-arrow"></use></svg></a>
</div>
</section>
</div>
<div class="header-action-menu friends-menu">
<button aria-controls="friendsDropdown" aria-expanded="false" aria-haspopup="true" aria-label="{{ __('hnt_preview.header.open_friend_requests') }}" class="header-circle header-dropdown-trigger" id="friendsMenuTrigger"><svg><use href="#i-users"></use></svg><span class="header-action-badge is-empty" data-header-badge="friends">0</span></button>
<section aria-labelledby="friendsMenuTrigger" class="header-dropdown requests-dropdown" id="friendsDropdown" role="menu"><header class="header-dropdown-head"><div><span>{{ __('hnt_preview.header.community_eyebrow') }}</span><strong>{{ __('hnt_preview.header.friend_requests') }}</strong></div><small data-dropdown-count="friends">{{ __('hnt_preview.header.open_count', ['count' => 0]) }}</small></header><div class="header-request-list"><div class="header-live-state"><strong>{{ __('hnt_preview.header.friend_requests') }}</strong>{{ __('hnt_preview.header.loading_real_data') }}</div></div><a class="header-dropdown-footer" href="{{ route('profile.friends') }}">{{ __('hnt_preview.header.view_all_requests') }} <svg><use href="#i-arrow"></use></svg></a></section>
</div>
<div class="header-action-menu messages-menu">
<button aria-controls="messagesDropdown" aria-expanded="false" aria-haspopup="true" aria-label="{{ __('hnt_preview.header.open_messages') }}" class="header-circle header-dropdown-trigger" data-hnt-messages-open id="messagesMenuTrigger"><svg><use href="#i-comment"></use></svg><span class="header-action-badge is-empty" data-header-badge="messages">0</span></button>
<section aria-labelledby="messagesMenuTrigger" class="header-dropdown messages-dropdown" id="messagesDropdown" role="menu"><header class="header-dropdown-head"><div><span>{{ __('hnt_preview.header.inbox') }}</span><strong>{{ __('hnt_preview.header.messages') }}</strong></div><small data-dropdown-count="messages">{{ __('hnt_preview.header.unread_count', ['count' => 0]) }}</small></header><div class="header-message-list"><div class="header-live-state"><strong>{{ __('hnt_preview.header.messages') }}</strong>{{ __('hnt_preview.header.loading_real_data') }}</div></div><a class="header-dropdown-footer" href="{{ route('messages.index') }}">{{ __('hnt_preview.header.view_all_messages') }} <svg><use href="#i-arrow"></use></svg></a></section>
</div>
<div class="header-action-menu notifications-menu">
<button aria-controls="notificationsDropdown" aria-expanded="false" aria-haspopup="true" aria-label="{{ __('hnt_preview.header.open_notifications') }}" class="header-circle header-dropdown-trigger" id="notificationsMenuTrigger"><svg><use href="#i-bell"></use></svg><span class="header-action-badge is-empty" data-header-badge="notifications">0</span></button>
<section aria-labelledby="notificationsMenuTrigger" class="header-dropdown notifications-dropdown" id="notificationsDropdown" role="menu"><header class="header-dropdown-head"><div><span>{{ __('hnt_preview.header.activity') }}</span><strong>{{ __('hnt_preview.header.notifications') }}</strong></div><button class="header-mark-all" type="button">{{ __('hnt_preview.header.mark_all_read') }}</button></header><div class="header-notification-list"><div class="header-live-state"><strong>{{ __('hnt_preview.header.notifications') }}</strong>{{ __('hnt_preview.header.loading_real_data') }}</div></div><a class="header-dropdown-footer" href="{{ route('notifications.index') }}">{{ __('hnt_preview.header.view_all_notifications') }} <svg><use href="#i-arrow"></use></svg></a></section>
</div>
<div class="header-action-menu profile-menu">
<button aria-controls="profileDropdown" aria-expanded="false" aria-haspopup="true" aria-label="{{ __('hnt_preview.header.open_profile') }}" class="header-circle header-dropdown-trigger" id="profileMenuTrigger"><svg><use href="#i-user"></use></svg></button>
<section aria-labelledby="profileMenuTrigger" class="header-dropdown profile-dropdown" id="profileDropdown" role="menu">
<div class="header-profile-summary"><div class="header-profile-avatar"><img alt="{{ $headerName }}" src="{{ $headerAvatar }}"/><i></i></div><div><strong>{{ $headerName }}</strong><span>{{ $headerHandle }} · {{ __('hnt_preview.header.level', ['level' => $headerLevel]) }}</span></div></div>
<div class="header-profile-stats"><span><strong>{{ number_format($headerRocks, 0, ',', '.') }}</strong><small>{{ __('hnt_preview.header.rocks') }}</small></span><span><strong>—</strong><small>{{ __('hnt_preview.header.friends') }}</small></span><span><strong>—</strong><small>{{ __('hnt_preview.header.posts') }}</small></span></div>
<div class="header-menu-list profile-menu-list">
<a href="{{ route('profile.show') }}" role="menuitem"><span class="header-menu-icon"><svg><use href="#i-user"></use></svg></span><span><strong>{{ __('hnt_preview.header.my_profile') }}</strong><small>{{ __('hnt_preview.header.my_profile_text') }}</small></span><svg class="header-menu-arrow"><use href="#i-arrow"></use></svg></a>
<a href="{{ route('profile.edit') }}" role="menuitem"><span class="header-menu-icon"><svg><use href="#i-sliders"></use></svg></span><span><strong>{{ __('hnt_preview.header.edit_profile') }}</strong><small>{{ __('hnt_preview.header.edit_profile_text') }}</small></span><svg class="header-menu-arrow"><use href="#i-arrow"></use></svg></a>
<a href="{{ route('crowns.inventory') }}" role="menuitem"><span class="header-menu-icon"><svg><use href="#i-folder"></use></svg></span><span><strong>{{ __('hnt_preview.header.inventory') }}</strong><small>{{ __('hnt_preview.header.inventory_text') }}</small></span><svg class="header-menu-arrow"><use href="#i-arrow"></use></svg></a>
</div>
<form action="{{ route('logout') }}" method="post">@csrf<button class="header-profile-logout" type="submit">{{ __('hnt_preview.header.logout') }}</button></form>
</section>
</div>
@else
<a class="settings-button" href="{{ route('login') }}">{{ __('hnt_preview.header.login') }}</a>
@endif
</div>
</header>

@once
<script>
window.HNT_PREVIEW_I18N = @json(trans('hnt_preview'));
window.HNT_DASHBOARD_HEADER_ENDPOINT = @json(route('feed.index'));
</script>
@auth
<script src="{{ asset('assets/themes/hnt_preview/fuckingdropdown.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/fuckingdropdown.js')) ?: time() }}"></script>
@endauth
@endonce

@auth
@once
<div class="hnt-chat-tabs-shell" data-hnt-chat-tabs-shell aria-live="polite"></div>
<script>
(() => {
    if (!document.querySelector('.feed-shell')) {
        const compatibilityRoot = document.createElement('div');
        compatibilityRoot.className = 'feed-shell hnt-header-runtime-root';
        compatibilityRoot.hidden = true;
        compatibilityRoot.setAttribute('aria-hidden', 'true');
        compatibilityRoot.style.display = 'none';
        document.body.appendChild(compatibilityRoot);
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('.header-message-list a.header-message-item');
        if (!link || link.hasAttribute('data-hnt-chat-tab-open')) return;

        try {
            const target = new URL(link.getAttribute('href') || '', window.location.origin);
            const match = target.pathname.match(/^\/messages\/(\d+)\/?$/);
            if (!match) return;

            link.setAttribute('data-hnt-chat-tab-open', '');
            link.setAttribute('data-hnt-chat-conversation-id', match[1]);
            link.setAttribute('data-hnt-chat-tab-url', target.pathname.replace(/\/$/, '') + '/chat-tab');
        } catch (_error) {
            // Keep the normal link as a safe fallback.
        }
    }, true);

    if (!document.querySelector('link[data-hnt-comms-style]')) {
        const style = document.createElement('link');
        style.rel = 'stylesheet';
        style.href = @json(asset('assets/themes/hnt_preview/comms-dock.css').'?v='.(@filemtime(public_path('assets/themes/hnt_preview/comms-dock.css')) ?: time()));
        style.dataset.hntCommsStyle = '1';
        document.head.appendChild(style);
    }
    if (!document.querySelector('link[data-hnt-phosphor-icons]')) {
        const icons = document.createElement('link');
        icons.rel = 'stylesheet';
        icons.href = 'https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css';
        icons.dataset.hntPhosphorIcons = '1';
        document.head.appendChild(icons);
    }
})();
</script>
<script src="{{ asset('assets/themes/hnt_preview/comms-core.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/comms-core.js')) ?: time() }}" defer></script>
<script src="{{ asset('assets/themes/hnt_preview/comms-dock.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/comms-dock.js')) ?: time() }}" defer></script>
@endonce
@endauth
