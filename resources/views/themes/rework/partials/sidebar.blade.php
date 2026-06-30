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

    $reworkSidebarItems = [
        [
            'label' => 'Feed',
            'icon' => 'ph ph-house',
            'url' => $reworkSidebarUrl('feed.index'),
            'active' => $reworkSidebarIsActive(['feed.*']),
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
            'active' => $reworkSidebarIsActive(['crowns.shop', 'crowns.shop.*']),
        ],
        [
            'label' => 'Cups',
            'icon' => 'ph ph-trophy',
            'url' => $reworkSidebarUrl('cups.index'),
            'active' => $reworkSidebarIsActive(['cups.*', 'hall-of-fame.*', 'loadout-challenges.*']),
        ],
        [
            'label' => __('ui.profile'),
            'icon' => 'ph ph-user',
            'url' => $reworkSidebarUrl('profile.show'),
            'active' => $reworkSidebarIsActive(['profile.*']),
        ],
    ];

    $reworkSidebarSettingsUrl = $reworkSidebarUrl('account.settings.edit');
@endphp

<aside class="sidebar">
    <div class="logo"><strong>HNT.</strong><span>ROCKS</span></div>
    <button aria-expanded="false" aria-label="{{ __('ui.rework_nav_expand_sidebar') }}" class="sidebar-toggle" data-sidebar-toggle="" type="button"><span></span><span></span></button>

    <nav aria-label="{{ __('ui.rework_nav_main_aria') }}" class="nav">
        @foreach($reworkSidebarItems as $item)
            <a @class([
                    $item['class'] ?? null,
                    'active' => $item['active'],
                ])
                href="{{ $item['url'] }}"
                @if($item['active']) aria-current="page" @endif
            >
                <i aria-hidden="true" class="{{ $item['icon'] }} ph-icon"></i><span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="nav-bottom">
        <div class="nav-divider"></div>
        <a data-settings-modal-open="" href="{{ $reworkSidebarSettingsUrl }}"><i aria-hidden="true" class="ph ph-gear-six ph-icon"></i><span>{{ __('ui.settings') }}</span></a>
        <a href="#" onclick="event.preventDefault(); this.closest('.app')?.querySelector('[data-rework-sidebar-logout]')?.submit();"><i aria-hidden="true" class="ph ph-sign-out ph-icon"></i><span>{{ __('ui.logout') }}</span></a>
        <form action="{{ route('logout') }}" data-rework-sidebar-logout method="post" hidden>
            @csrf
        </form>
    </div>
</aside>
