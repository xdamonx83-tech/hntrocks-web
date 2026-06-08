@auth
    @php
        $hhSidebarUser = auth()->user();
        $hhSidebarPostCount = $hhSidebarUser->feedPosts()->count();
        $hhSidebarTeamCount = $hhSidebarUser->activeTeams()->count();
        $hhSidebarBadgeCount = $hhSidebarUser->badges()->count();
        $hhSidebarBadges = $hhSidebarUser->badges()
            ->orderByPivot('awarded_at', 'desc')
            ->orderBy('badges.sort_order')
            ->limit(4)
            ->get();
        $hhSidebarCompletedQuestCount = $hhSidebarUser->questProgress()->whereNotNull('completed_at')->count();
        $hhSidebarLevel = max(1, (int) ($hhSidebarUser->level ?: 1));
        $hhSidebarProfile = $hhSidebarUser->profile;
        $hhSidebarHeadline = $hhSidebarProfile?->headline ?: ('LV. ' . $hhSidebarLevel . ' · ' . ((int) ($hhSidebarUser->xp_total ?: 0)) . ' XP');

        $hhAllItems = app(\App\Services\Navigation\SidebarMenuService::class)->itemsForUser($hhSidebarUser);
        $hhNavigationItems = array_values(array_filter($hhAllItems, fn ($item) => ($item['section'] ?? 'main') === 'main'));
    @endphp

    <nav id="navigation-widget-small" class="navigation-widget navigation-widget-desktop closed sidebar left delayed hh-vikinger-sidebar-small" aria-label="{{ __('ui.sidebar_navigation') }}">
        <a class="user-avatar small no-outline online hh-sidebar-template-avatar" href="{{ route('profile.show') }}">
            <div class="user-avatar-content">
                <div class="hexagon-image-30-32" data-src="{{ $hhSidebarUser->avatarUrl() }}"></div>
            </div>
            <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
            <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
            <div class="user-avatar-badge">
                <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                <p class="user-avatar-badge-text">{{ $hhSidebarLevel }}</p>
            </div>
        </a>

        <ul class="menu small">
            @foreach ($hhNavigationItems as $item)
                <li class="menu-item {{ ! empty($item['match']) && request()->routeIs($item['match']) ? 'active' : '' }}">
                    <a class="menu-item-link text-tooltip-tfr" href="{{ $item['url'] }}" data-title="{{ $item['label'] }}" @if(! empty($item['external'])) target="_blank" rel="noopener" @endif>
                        <i class="menu-item-link-icon hh-ph-sidebar-icon ph ph-{{ $item['phosphor'] ?? 'circle' }}" aria-hidden="true"></i>
                        @if (($item['count'] ?? 0) > 0)
                            <span class="hh-sidebar-count">{{ $item['count'] }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <nav id="navigation-widget" class="navigation-widget navigation-widget-desktop sidebar left hidden delayed hh-vikinger-sidebar-full" data-simplebar aria-label="{{ __('ui.sidebar_navigation') }}">
        <figure class="navigation-widget-cover liquid hh-navigation-cover">
            <img src="{{ $hhSidebarUser->coverUrl() }}" alt="Cover von {{ $hhSidebarUser->username }}">
        </figure>

        <div class="user-short-description hh-sidebar-user-card">
            <a class="user-short-description-avatar user-avatar medium" href="{{ route('profile.show') }}">
                <div class="user-avatar-border"><div class="hexagon-120-132"></div></div>
                <div class="user-avatar-content"><div class="hexagon-image-82-90" data-src="{{ $hhSidebarUser->avatarUrl() }}"></div></div>
                <div class="user-avatar-progress"><div class="hexagon-progress-100-110"></div></div>
                <div class="user-avatar-progress-border"><div class="hexagon-border-100-110"></div></div>
                <div class="user-avatar-badge">
                    <div class="user-avatar-badge-border"><div class="hexagon-32-36"></div></div>
                    <div class="user-avatar-badge-content"><div class="hexagon-dark-26-28"></div></div>
                    <p class="user-avatar-badge-text">{{ $hhSidebarLevel }}</p>
                </div>
            </a>

            <p class="user-short-description-title"><a href="{{ route('profile.show') }}">{{ $hhSidebarUser->username }}</a></p>
            <p class="user-short-description-text"><a href="{{ route('profile.show') }}">{{ $hhSidebarHeadline }}</a></p>
        </div>

        <div class="badge-list small hh-sidebar-badge-list" aria-label="Badges">
            @forelse ($hhSidebarBadges as $badge)
                <a class="badge-item hh-sidebar-real-badge text-tooltip-tft" href="{{ route('gamification.index') }}" data-title="{{ $badge->name }}">
                    @if ($badge->iconUrl())
                        <img class="hh-sidebar-badge-custom" src="{{ $badge->iconUrl() }}" alt="{{ $badge->name }}">
                    @else
                        <img src="{{ asset('assets/vikinger/img/badge/blank-s.png') }}" alt="{{ $badge->name }}">
                        <p class="badge-item-text">{{ $badge->icon ?: strtoupper(substr($badge->name, 0, 1)) }}</p>
                    @endif
                </a>
            @empty
                <a class="badge-item hh-sidebar-real-badge is-empty text-tooltip-tft" href="{{ route('gamification.index') }}" data-title="{{ __('ui.no_badges_yet') }}">
                    <img src="{{ asset('assets/vikinger/img/badge/blank-s.png') }}" alt="{{ __('ui.no_badges_yet') }}">
                    <p class="badge-item-text">0</p>
                </a>
            @endforelse

            @if ($hhSidebarBadgeCount > $hhSidebarBadges->count())
                <a class="badge-item hh-sidebar-real-badge" href="{{ route('gamification.index') }}">
                    <img src="{{ asset('assets/vikinger/img/badge/blank-s.png') }}" alt="Weitere Badges">
                    <p class="badge-item-text">+{{ $hhSidebarBadgeCount - $hhSidebarBadges->count() }}</p>
                </a>
            @endif
        </div>

        <a class="hh-sidebar-quest-pill" href="{{ route('gamification.index') }}">
            <span>Quests</span>
            <strong>{{ $hhSidebarCompletedQuestCount }}</strong>
            <small>erledigt</small>
        </a>

        <div class="user-stats hh-sidebar-user-stats">
            <div class="user-stat"><p class="user-stat-title">{{ $hhSidebarPostCount }}</p><p class="user-stat-text">Posts</p></div>
            <div class="user-stat"><p class="user-stat-title">{{ $hhSidebarTeamCount }}</p><p class="user-stat-text">{{ __('ui.teams') }}</p></div>
            <div class="user-stat"><p class="user-stat-title">{{ $hhSidebarBadgeCount }}</p><p class="user-stat-text">Badges</p></div>
        </div>

        <ul class="menu hh-sidebar-main-menu">
            @foreach ($hhAllItems as $item)
                <li class="menu-item {{ ! empty($item['match']) && request()->routeIs($item['match']) ? 'active' : '' }}">
                    <a class="menu-item-link" href="{{ $item['url'] }}" @if(! empty($item['external'])) target="_blank" rel="noopener" @endif>
                        <i class="menu-item-link-icon hh-ph-sidebar-icon ph ph-{{ $item['phosphor'] ?? 'circle' }}" aria-hidden="true"></i>
                        {{ $item['label'] }}
                        @if (($item['count'] ?? 0) > 0)
                            <span class="hh-sidebar-count hh-sidebar-count-inline">{{ $item['count'] }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <nav id="navigation-widget-mobile" class="navigation-widget navigation-widget-mobile sidebar left hidden hh-vikinger-mobile-sidebar" data-simplebar aria-label="{{ __('ui.sidebar_navigation') }}">
        <button type="button" class="navigation-widget-close-button hh-icon-reset" aria-label="{{ __('ui.close') }}">
            <svg class="navigation-widget-close-button-icon icon-back-arrow"><use xlink:href="#svg-back-arrow"></use></svg>
        </button>

        <div class="navigation-widget-info-wrap">
            <div class="navigation-widget-info">
                <a class="user-avatar small no-outline" href="{{ route('profile.show') }}">
                    <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $hhSidebarUser->avatarUrl() }}"></div></div>
                    <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                    <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                    <div class="user-avatar-badge">
                        <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                        <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                        <p class="user-avatar-badge-text">{{ $hhSidebarLevel }}</p>
                    </div>
                </a>
                <p class="navigation-widget-info-title"><a href="{{ route('profile.show') }}">{{ $hhSidebarUser->username }}</a></p>
                <p class="navigation-widget-info-text">{{ $hhSidebarHeadline }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="hh-mobile-logout-form">
                @csrf
                <button type="submit" class="navigation-widget-info-button button small secondary hh-menu-button-link">{{ __('ui.logout') }}</button>
            </form>
        </div>

        <p class="navigation-widget-section-title">{{ __('ui.sections') }}</p>

        <ul class="menu">
            @foreach ($hhAllItems as $item)
                <li class="menu-item {{ ! empty($item['match']) && request()->routeIs($item['match']) ? 'active' : '' }}">
                    <a class="menu-item-link" href="{{ $item['url'] }}" @if(! empty($item['external'])) target="_blank" rel="noopener" @endif>
                        <i class="menu-item-link-icon hh-ph-sidebar-icon ph ph-{{ $item['phosphor'] ?? 'circle' }}" aria-hidden="true"></i>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endauth
