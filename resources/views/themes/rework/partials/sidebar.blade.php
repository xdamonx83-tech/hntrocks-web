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

    $reworkSidebarMainItems = [
        [
            'label' => 'Feed',
            'icon' => 'ph ph-house',
            'url' => $reworkSidebarUrl('feed.index'),
            'active' => $reworkSidebarIsActive(['feed.*']),
        ],
        [
            'label' => __('ui.members'),
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
            'class' => 'thin',
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

    $reworkSidebarProfileItem = [
        'label' => __('ui.profile'),
        'icon' => 'ph ph-user',
        'url' => $reworkSidebarUrl('profile.show'),
        'active' => $reworkSidebarIsActive(['profile.*']),
    ];

    $reworkSidebarSettingsUrl = $reworkSidebarUrl('account.settings.edit');
    $reworkSidebarV2StyleVersion = @filemtime(public_path('assets/themes/rework/sidebar-v2.css')) ?: time();
    $reworkSidebarV2ScriptVersion = @filemtime(public_path('assets/themes/rework/sidebar-v2.js')) ?: time();
@endphp

<link href="{{ asset('assets/themes/rework/sidebar-v2.css') }}?v={{ $reworkSidebarV2StyleVersion }}" rel="stylesheet">

<aside class="sidebar sidebar-v2" data-rework-sidebar>
    <div class="sidebar-v2-window-dots" aria-hidden="true">
        <span class="sidebar-v2-dot sidebar-v2-dot-red"></span>
        <span class="sidebar-v2-dot sidebar-v2-dot-yellow"></span>
        <span class="sidebar-v2-dot sidebar-v2-dot-green"></span>
    </div>

    <div class="sidebar-v2-brand">
        <a class="sidebar-v2-logo-mark" href="{{ $reworkSidebarUrl('feed.index') }}" aria-label="HNT.rocks">
            <strong>H</strong>
        </a>
        <a class="sidebar-v2-brand-text" href="{{ $reworkSidebarUrl('feed.index') }}" aria-label="HNT.rocks">
            <strong>HNT.</strong>
            <span>ROCKS</span>
        </a>
    </div>

    <button aria-expanded="true" aria-label="{{ __('ui.rework_nav_expand_sidebar') }}" class="sidebar-toggle sidebar-v2-toggle" data-sidebar-toggle type="button">
        <i aria-hidden="true" class="ph ph-caret-left"></i>
    </button>

    <nav aria-label="{{ __('ui.rework_nav_main_aria') }}" class="nav sidebar-v2-nav">
        <span class="sidebar-v2-section-label">Menu</span>

        @foreach($reworkSidebarMainItems as $item)
            <a @class([
                    $item['class'] ?? null,
                    'sidebar-v2-link',
                    'active' => $item['active'],
                ])
                href="{{ $item['url'] }}"
                data-sidebar-tooltip="{{ $item['label'] }}"
                @if($item['active']) aria-current="page" @endif
            >
                <i aria-hidden="true" class="{{ $item['icon'] }} ph-icon"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="nav-bottom sidebar-v2-bottom">
        <div class="nav-divider sidebar-v2-divider"></div>

        <nav aria-label="Account" class="sidebar-v2-account-nav">
            <a @class([
                    'sidebar-v2-link',
                    'active' => $reworkSidebarProfileItem['active'],
                ])
                href="{{ $reworkSidebarProfileItem['url'] }}"
                data-sidebar-tooltip="{{ $reworkSidebarProfileItem['label'] }}"
                @if($reworkSidebarProfileItem['active']) aria-current="page" @endif
            >
                <i aria-hidden="true" class="{{ $reworkSidebarProfileItem['icon'] }} ph-icon"></i>
                <span>{{ $reworkSidebarProfileItem['label'] }}</span>
            </a>

            <a class="sidebar-v2-link" data-settings-modal-open href="{{ $reworkSidebarSettingsUrl }}" data-sidebar-tooltip="{{ __('ui.settings') }}">
                <i aria-hidden="true" class="ph ph-gear-six ph-icon"></i>
                <span>{{ __('ui.settings') }}</span>
            </a>

            <a class="sidebar-v2-link sidebar-v2-logout" href="#" data-sidebar-tooltip="{{ __('ui.logout') }}" onclick="event.preventDefault(); this.closest('.app')?.querySelector('[data-rework-sidebar-logout]')?.submit();">
                <i aria-hidden="true" class="ph ph-sign-out ph-icon"></i>
                <span>{{ __('ui.logout') }}</span>
            </a>
        </nav>

        <form action="{{ route('logout') }}" data-rework-sidebar-logout method="post" hidden>
            @csrf
        </form>
    </div>
</aside>

<script defer src="{{ asset('assets/themes/rework/sidebar-v2.js') }}?v={{ $reworkSidebarV2ScriptVersion }}"></script>
