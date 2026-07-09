@php
    $reworkSidebarRoute = request()->route()?->getName() ?? '';

    $reworkSidebarIsActive = static function (array $patterns) use ($reworkSidebarRoute): bool {
        foreach ($patterns as $pattern) {
            if (\Illuminate\Support\Str::is($pattern, $reworkSidebarRoute)) {
                return true;
            }
        }

        return false;
    };

    $reworkSidebarUrl = static function (string $routeName, string $fallback = '#'): string {
        return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : url($fallback);
    };

    $viewer = auth()->user();
    $viewer?->loadMissing('profile');

    $defaultAvatar = asset('assets/vikinger/img/default-avatar.svg');
    $sidebarAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $sidebarName = $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter');

    $feedActive = $reworkSidebarIsActive(['feed.*']);
    $searchActive = $reworkSidebarIsActive(['search.*']);
    $membersActive = $reworkSidebarIsActive(['members.*']);
    $dashboardActive = $feedActive || $searchActive || $membersActive;
    $lfgActive = $reworkSidebarIsActive(['lfg.*', 'team-lfg.*']);
    $momentsActive = $reworkSidebarIsActive(['moments.*']);
    $cupsActive = $reworkSidebarIsActive(['cups.*', 'hall-of-fame.*', 'cup-feedback.*', 'cup-ideas.*']);
    $profileActive = $reworkSidebarIsActive(['profile.*']);
    $settingsActive = $reworkSidebarIsActive(['account.settings.*', 'settings.*']);
    $reworkSidebarSettingsUrl = $reworkSidebarUrl('account.settings.edit', '/account/settings');
    $reworkSidebarV2StyleVersion = @filemtime(public_path('assets/themes/rework/sidebar-v2.css')) ?: time();
    $reworkSidebarV2TuneVersion = @filemtime(public_path('assets/themes/rework/sidebar-v2-tune.css')) ?: time();
    $reworkPolishVersion = @filemtime(public_path('assets/themes/rework/rework-polish.css')) ?: time();
    $reworkSidebarV2ScriptVersion = @filemtime(public_path('assets/themes/rework/sidebar-v2.js')) ?: time();
@endphp

<link href="{{ asset('assets/themes/rework/sidebar-v2.css') }}?v={{ $reworkSidebarV2StyleVersion }}" rel="stylesheet">
<link href="{{ asset('assets/themes/rework/sidebar-v2-tune.css') }}?v={{ $reworkSidebarV2TuneVersion }}" rel="stylesheet">
<link href="{{ asset('assets/themes/rework/rework-polish.css') }}?v={{ $reworkPolishVersion }}" rel="stylesheet">

<aside class="sidebar sidebar-v2 is-expanded" id="appSidebar" data-rework-sidebar aria-label="{{ __('ui.sidebar_navigation') }}">
    <div class="rail-bg" aria-hidden="true"></div>

    <div class="streetlights" aria-hidden="true">
        <span class="red"></span>
        <span class="yellow"></span>
        <span class="green"></span>
    </div>

    <button class="side-arrow" type="button" id="sidebarToggle" data-sidebar-toggle aria-label="Collapse sidebar" aria-expanded="true">
        <i aria-hidden="true" class="ph ph-caret-left arrow-collapse"></i>
        <i aria-hidden="true" class="ph ph-caret-right arrow-expand"></i>
    </button>

    <header class="profile">
        <img src="{{ $sidebarAvatar }}" alt="{{ $sidebarName }}" class="profile-avatar" data-rework-profile-avatar>
        <div class="profile-copy">
            <p>HNT Hunter</p>
            <strong>{{ $sidebarName }}</strong>
        </div>
    </header>

    <span class="divider divider-top" aria-hidden="true"></span>

    <nav class="main-menu" aria-label="{{ __('ui.main_navigation') }}">
        <div class="section-title">Main</div>

        <div class="main-list">
            <a class="menu-item dashboard {{ $dashboardActive ? 'menu-active is-current' : '' }}" href="{{ $reworkSidebarUrl('feed.index', '/feed') }}" id="dashboardToggle" data-route="dashboard" data-sidebar-submenu-toggle aria-controls="dashboardSubmenu" aria-expanded="true" data-sidebar-tooltip="Dashboard" @if($dashboardActive) aria-current="page" @endif>
                <span class="lights" aria-hidden="true"></span>
                <i aria-hidden="true" class="ph-bold ph-squares-four icon dashboard-icon"></i>
                <span class="menu-text">Dashboard</span>
                <i aria-hidden="true" class="ph ph-caret-up icon chevron-up"></i>
            </a>

            <div class="details-block" id="dashboardSubmenu">
                <svg class="tree-lines" viewBox="0 0 16 112" aria-hidden="true">
                    <path d="M1 0v20c0 5.5 4.5 10 10 10h4"/>
                    <path d="M1 30v40c0 5.5 4.5 10 10 10h4"/>
                    <path d="M1 80v22c0 5.5 4.5 10 10 10h4"/>
                </svg>
                <div class="sub-list" role="list">
                    <a href="{{ $reworkSidebarUrl('feed.index', '/feed') }}" @class(['sub-item', 'sub-active' => $feedActive]) data-route="feed" @if($feedActive) aria-current="page" @endif>@if($feedActive)<span class="sub-light" aria-hidden="true"></span>@endif{{ __('ui.feed') }}</a>
                    <a href="{{ $reworkSidebarUrl('search.index', '/search') }}" @class(['sub-item', 'sub-active' => $searchActive]) data-route="search" @if($searchActive) aria-current="page" @endif>@if($searchActive)<span class="sub-light" aria-hidden="true"></span>@endif{{ __('ui.search') }}</a>
                    <a href="{{ $reworkSidebarUrl('members.index', '/members') }}" @class(['sub-item', 'sub-active' => $membersActive]) data-route="members" @if($membersActive) aria-current="page" @endif>@if($membersActive)<span class="sub-light" aria-hidden="true"></span>@endif{{ __('ui.members') }}</a>
                </div>
            </div>

            <a class="menu-item {{ $lfgActive ? 'is-current' : '' }}" href="{{ $reworkSidebarUrl('lfg.index', '/lfg') }}" data-route="lfg" data-sidebar-tooltip="{{ __('ui.lfg') }}" @if($lfgActive) aria-current="page" @endif>
                <i aria-hidden="true" class="ph ph-crosshair icon"></i>
                <span class="menu-text">{{ __('ui.lfg') }}</span>
            </a>

            <a class="menu-item {{ $momentsActive ? 'is-current' : '' }}" href="{{ $reworkSidebarUrl('moments.index', '/moments') }}" data-route="moments" data-sidebar-tooltip="{{ __('ui.moments') }}" @if($momentsActive) aria-current="page" @endif>
                <i aria-hidden="true" class="ph ph-play-circle icon"></i>
                <span class="menu-text">{{ __('ui.moments') }}</span>
            </a>

            <a class="menu-item {{ $cupsActive ? 'is-current' : '' }}" href="{{ $reworkSidebarUrl('cups.index', '/cups') }}" data-route="cups" data-sidebar-tooltip="{{ __('ui.cups') }}" @if($cupsActive) aria-current="page" @endif>
                <i aria-hidden="true" class="ph ph-trophy icon"></i>
                <span class="menu-text">{{ __('ui.cups') }}</span>
            </a>
        </div>
    </nav>

    <span class="divider divider-mid" aria-hidden="true"></span>

    <nav class="account-menu" aria-label="{{ __('ui.account_navigation') }}">
        <div class="section-title account-title">Account</div>

        <div class="account-list">
            <a class="menu-item account-item {{ $profileActive ? 'is-current' : '' }}" href="{{ $reworkSidebarUrl('profile.show', '/profile') }}" data-route="profile" data-sidebar-tooltip="{{ __('ui.my_profile') }}" @if($profileActive) aria-current="page" @endif>
                <i aria-hidden="true" class="ph ph-user-circle icon"></i>
                <span class="menu-text">{{ __('ui.my_profile') }}</span>
            </a>

            <a class="menu-item account-item {{ $settingsActive ? 'is-current' : '' }}" data-settings-modal-open href="{{ $reworkSidebarSettingsUrl }}" data-route="settings" data-sidebar-tooltip="{{ __('ui.settings') }}" @if($settingsActive) aria-current="page" @endif>
                <i aria-hidden="true" class="ph ph-gear-six icon"></i>
                <span class="menu-text">{{ __('ui.settings') }}</span>
            </a>

            <a class="menu-item account-item logout-item" href="#" data-route="logout" data-sidebar-tooltip="{{ __('ui.logout') }}" onclick="event.preventDefault(); this.closest('.app')?.querySelector('[data-rework-sidebar-logout]')?.submit();">
                <i aria-hidden="true" class="ph ph-sign-out icon"></i>
                <span class="menu-text">{{ __('ui.logout') }}</span>
            </a>
        </div>
    </nav>

    <form action="{{ route('logout') }}" data-rework-sidebar-logout method="post" hidden>
        @csrf
    </form>
</aside>

<script defer src="{{ asset('assets/themes/rework/sidebar-v2.js') }}?v={{ $reworkSidebarV2ScriptVersion }}"></script>
