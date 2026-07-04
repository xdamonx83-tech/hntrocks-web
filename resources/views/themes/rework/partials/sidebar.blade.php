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
        return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : $fallback;
    };

    $viewer = auth()->user();
    $viewer?->loadMissing('profile');

    $defaultAvatar = asset('assets/vikinger/img/default-avatar.svg');
    $sidebarAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $sidebarName = $viewer?->name ?: ($viewer?->username ?: 'Andrew Smith');

    $feedActive = $reworkSidebarIsActive(['feed.*']);
    $profileActive = $reworkSidebarIsActive(['profile.*']);
    $reworkSidebarSettingsUrl = $reworkSidebarUrl('account.settings.edit');
    $reworkSidebarV2StyleVersion = @filemtime(public_path('assets/themes/rework/sidebar-v2.css')) ?: time();
    $reworkSidebarV2TuneVersion = @filemtime(public_path('assets/themes/rework/sidebar-v2-tune.css')) ?: time();
    $reworkSidebarV2ScriptVersion = @filemtime(public_path('assets/themes/rework/sidebar-v2.js')) ?: time();
@endphp

<link href="{{ asset('assets/themes/rework/sidebar-v2.css') }}?v={{ $reworkSidebarV2StyleVersion }}" rel="stylesheet">
<link href="{{ asset('assets/themes/rework/sidebar-v2-tune.css') }}?v={{ $reworkSidebarV2TuneVersion }}" rel="stylesheet">

<aside class="sidebar sidebar-v2 is-expanded" id="appSidebar" data-rework-sidebar aria-label="Main sidebar">
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
            <p>Product Designer</p>
            <strong>{{ $sidebarName }}</strong>
        </div>
    </header>

    <span class="divider divider-top" aria-hidden="true"></span>

    <nav class="main-menu" aria-label="Demo sidebar navigation">
        <div class="section-title">Main</div>

        <div class="main-list">
            <a class="menu-item menu-active dashboard {{ $feedActive ? 'is-current' : '' }}" href="{{ $reworkSidebarUrl('feed.index') }}" id="dashboardToggle" data-route="dashboard" @if($feedActive) aria-current="page" @endif>
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
                    <a href="{{ $reworkSidebarUrl('feed.index') }}" class="sub-item" data-route="activity">Activity</a>
                    <a href="{{ $reworkSidebarUrl('feed.index') }}" class="sub-item" data-route="traffic">Trafic</a>
                    <a href="{{ $reworkSidebarUrl('feed.index') }}" class="sub-item sub-active" data-route="statistic"><span class="sub-light" aria-hidden="true"></span>Statistic</a>
                </div>
            </div>

            <a class="menu-item" href="#" data-route="invoices" data-sidebar-tooltip="Invoices">
                <i aria-hidden="true" class="ph ph-files icon"></i>
                <span class="menu-text">Invoices</span>
            </a>

            <a class="menu-item" href="#" data-route="wallet" data-sidebar-tooltip="Wallet">
                <i aria-hidden="true" class="ph ph-wallet icon"></i>
                <span class="menu-text">Wallet</span>
            </a>

            <a class="menu-item" href="#" data-route="notification" data-sidebar-tooltip="Notification">
                <i aria-hidden="true" class="ph ph-bell icon"></i>
                <span class="menu-text">Notification</span>
            </a>
        </div>
    </nav>

    <span class="divider divider-mid" aria-hidden="true"></span>

    <nav class="account-menu" aria-label="Account">
        <div class="section-title account-title">Account</div>

        <div class="account-list">
            <a class="menu-item account-item {{ $profileActive ? 'is-current' : '' }}" href="{{ $reworkSidebarUrl('profile.show') }}" data-route="user-menu" data-sidebar-tooltip="User Menu" @if($profileActive) aria-current="page" @endif>
                <i aria-hidden="true" class="ph ph-user-circle icon"></i>
                <span class="menu-text">User Menu</span>
            </a>

            <a class="menu-item account-item" data-settings-modal-open href="{{ $reworkSidebarSettingsUrl }}" data-route="settings" data-sidebar-tooltip="Einstellungen">
                <i aria-hidden="true" class="ph ph-gear-six icon"></i>
                <span class="menu-text">Einstellungen</span>
            </a>

            <a class="menu-item account-item logout-item" href="#" data-route="logout" data-sidebar-tooltip="Logout" onclick="event.preventDefault(); this.closest('.app')?.querySelector('[data-rework-sidebar-logout]')?.submit();">
                <i aria-hidden="true" class="ph ph-sign-out icon"></i>
                <span class="menu-text">Logout</span>
            </a>
        </div>
    </nav>

    <form action="{{ route('logout') }}" data-rework-sidebar-logout method="post" hidden>
        @csrf
    </form>
</aside>

<script defer src="{{ asset('assets/themes/rework/sidebar-v2.js') }}?v={{ $reworkSidebarV2ScriptVersion }}"></script>
