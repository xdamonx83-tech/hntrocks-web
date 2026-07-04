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
    $sidebarName = $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter');
    $sidebarHandle = $viewer?->username ? '@'.$viewer->username : 'HNT.rocks';

    $reworkSidebarMainItems = [
        [
            'label' => 'Players',
            'icon' => 'ph ph-users-three',
            'url' => $reworkSidebarUrl('members.index'),
            'active' => $reworkSidebarIsActive(['members.*']),
        ],
        [
            'label' => 'Games',
            'icon' => 'ph ph-game-controller',
            'url' => '#',
            'active' => false,
        ],
        [
            'label' => 'Maps',
            'icon' => 'ph ph-map-trifold',
            'url' => $reworkSidebarUrl('maps.index'),
            'active' => $reworkSidebarIsActive(['maps.*']),
        ],
        [
            'label' => 'Hunt',
            'icon' => 'ph ph-crosshair',
            'url' => '#',
            'active' => false,
        ],
        [
            'label' => 'Gamification',
            'icon' => 'ph ph-chart-bar',
            'url' => $reworkSidebarUrl('gamification.index'),
            'active' => $reworkSidebarIsActive(['gamification.*']),
        ],
        [
            'label' => 'Shop',
            'icon' => 'ph ph-storefront',
            'url' => $reworkSidebarUrl('crowns.shop'),
            'active' => $reworkSidebarIsActive(['crowns.index', 'crowns.history', 'crowns.inventory', 'crowns.shop', 'crowns.shop.*']),
        ],
        [
            'label' => 'Cups',
            'icon' => 'ph ph-trophy',
            'url' => $reworkSidebarUrl('cups.index'),
            'active' => $reworkSidebarIsActive(['cups.*', 'hall-of-fame.*', 'loadout-challenges.*']),
        ],
    ];

    $feedActive = $reworkSidebarIsActive(['feed.*']);
    $reworkSidebarSettingsUrl = $reworkSidebarUrl('account.settings.edit');
    $reworkSidebarV2StyleVersion = @filemtime(public_path('assets/themes/rework/sidebar-v2.css')) ?: time();
    $reworkSidebarV2ScriptVersion = @filemtime(public_path('assets/themes/rework/sidebar-v2.js')) ?: time();
@endphp

<link href="{{ asset('assets/themes/rework/sidebar-v2.css') }}?v={{ $reworkSidebarV2StyleVersion }}" rel="stylesheet">

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
            <p>{{ $sidebarHandle }}</p>
            <strong>{{ $sidebarName }}</strong>
        </div>
    </header>

    <span class="divider divider-top" aria-hidden="true"></span>

    <nav class="main-menu" aria-label="{{ __('ui.rework_nav_main_aria') }}">
        <div class="section-title">Main</div>

        <div class="main-list">
            <a class="menu-item menu-active dashboard {{ $feedActive ? 'is-current' : '' }}" href="{{ $reworkSidebarUrl('feed.index') }}" id="dashboardToggle" data-route="feed" @if($feedActive) aria-current="page" @endif>
                <span class="lights" aria-hidden="true"></span>
                <i aria-hidden="true" class="ph ph-house icon"></i>
                <span class="menu-text">Feed</span>
                <i aria-hidden="true" class="ph ph-caret-up icon chevron-up"></i>
            </a>

            <div class="details-block" id="dashboardSubmenu">
                <svg class="tree-lines" viewBox="0 0 16 112" aria-hidden="true">
                    <path d="M1 0v20c0 5.5 4.5 10 10 10h4"/>
                    <path d="M1 30v40c0 5.5 4.5 10 10 10h4"/>
                    <path d="M1 80v22c0 5.5 4.5 10 10 10h4"/>
                </svg>
                <div class="sub-list" role="list">
                    <a href="{{ $reworkSidebarUrl('feed.index') }}" class="sub-item sub-active" data-route="feed-all"><span class="sub-light" aria-hidden="true"></span>All</a>
                    <a href="{{ $reworkSidebarUrl('feed.index') }}?filter=friends" class="sub-item" data-route="feed-friends">Friends</a>
                    <a href="{{ $reworkSidebarUrl('feed.index') }}?filter=media" class="sub-item" data-route="feed-media">Media</a>
                </div>
            </div>

            @foreach($reworkSidebarMainItems as $item)
                <a @class([
                        'menu-item',
                        'is-current' => $item['active'],
                    ])
                    href="{{ $item['url'] }}"
                    data-route="{{ \Illuminate\Support\Str::slug($item['label']) }}"
                    data-sidebar-tooltip="{{ $item['label'] }}"
                    @if($item['active']) aria-current="page" @endif
                >
                    <i aria-hidden="true" class="{{ $item['icon'] }} icon"></i>
                    <span class="menu-text">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <span class="divider divider-mid" aria-hidden="true"></span>

    <nav class="account-menu" aria-label="Account">
        <div class="account-head">
            <span>Account</span>
        </div>

        <a class="account-row {{ $reworkSidebarIsActive(['profile.*']) ? 'is-current' : '' }}" href="{{ $reworkSidebarUrl('profile.show') }}" data-sidebar-tooltip="{{ __('ui.profile') }}">
            <i aria-hidden="true" class="ph ph-user icon"></i>
            <span>{{ __('ui.profile') }}</span>
        </a>

        <a class="account-row" data-settings-modal-open href="{{ $reworkSidebarSettingsUrl }}" data-sidebar-tooltip="{{ __('ui.settings') }}">
            <i aria-hidden="true" class="ph ph-gear-six icon"></i>
            <span>{{ __('ui.settings') }}</span>
        </a>

        <a class="account-row logout-row" href="#" data-sidebar-tooltip="{{ __('ui.logout') }}" onclick="event.preventDefault(); this.closest('.app')?.querySelector('[data-rework-sidebar-logout]')?.submit();">
            <i aria-hidden="true" class="ph ph-sign-out icon"></i>
            <span>{{ __('ui.logout') }}</span>
        </a>
    </nav>

    <section class="promo-card" aria-label="Create LFG">
        <div class="promo-text">
            <h2>Let&apos;s hunt!</h2>
            <p>Find hunters or create your next session.</p>
        </div>
        <a class="orange-button" href="{{ $reworkSidebarUrl('lfg.create', $reworkSidebarUrl('lfg.index', '#')) }}">
            <i aria-hidden="true" class="ph ph-plus"></i>
            <span>Create LFG</span>
        </a>
    </section>

    <a class="small-add" href="{{ $reworkSidebarUrl('lfg.create', $reworkSidebarUrl('lfg.index', '#')) }}" aria-label="Create LFG">
        <i aria-hidden="true" class="ph ph-plus"></i>
    </a>

    <form action="{{ route('logout') }}" data-rework-sidebar-logout method="post" hidden>
        @csrf
    </form>
</aside>

<script defer src="{{ asset('assets/themes/rework/sidebar-v2.js') }}?v={{ $reworkSidebarV2ScriptVersion }}"></script>
