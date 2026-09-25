@php
    $adminNav = [
        'MAIN' => [
            ['label' => 'Dashboard', 'route' => 'admin.index', 'active' => 'admin.index', 'icon' => 'category'],
            ['label' => 'Nutzer', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'profile-2user'],
            ['label' => 'Reports', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'icon' => 'danger'],
            ['label' => 'Feedback & Tickets', 'route' => 'admin.feedback-tickets.index', 'active' => 'admin.feedback-tickets.*', 'icon' => 'message-question'],
        ],
        'COMMUNITY' => [
            ['label' => 'Arcade-Spiele', 'route' => 'admin.arcade-games.index', 'active' => 'admin.arcade-games.*', 'icon' => 'game'],
            ['label' => 'Cup-Feedback', 'route' => 'admin.cup-feedback.index', 'active' => 'admin.cup-feedback.*', 'icon' => 'message-question'],
            ['label' => 'Cup-Ideen', 'route' => 'admin.cup-ideas.index', 'active' => 'admin.cup-ideas.*', 'icon' => 'award'],
            ['label' => 'Cup-Einreichungen', 'route' => 'admin.cup-submissions.index', 'active' => 'admin.cup-submissions.*', 'icon' => 'document-text'],
            ['label' => 'Moment der Woche', 'route' => 'admin.moment-of-week.index', 'active' => 'admin.moment-of-week.*', 'icon' => 'award'],
            ['label' => 'Inhalte', 'route' => 'admin.content.index', 'active' => 'admin.content.*', 'icon' => 'document-text'],
            ['label' => 'Guides', 'route' => 'admin.guides.index', 'active' => 'admin.guides.*', 'icon' => 'document-text'],
            ['label' => 'Hunt-News', 'route' => 'admin.hunt-news.index', 'active' => 'admin.hunt-news.*', 'icon' => 'document-text'],
            ['label' => 'HNT Maps', 'route' => 'admin.maps.index', 'active' => ['admin.maps.index', 'admin.maps.markers*'], 'icon' => 'map-1'],
            ['label' => 'Kassenspots', 'route' => 'admin.maps.cash-spots.index', 'active' => 'admin.maps.cash-spots.*', 'icon' => 'map-1'],
            ['label' => 'HNT-Aufträge', 'route' => 'admin.contracts.index', 'active' => 'admin.contracts.*', 'icon' => 'document-text'],
            ['label' => 'Loadout-Challenges', 'route' => 'admin.loadout-challenges.index', 'active' => 'admin.loadout-challenges.*', 'icon' => 'game'],
            ['label' => 'Badges & Quests', 'route' => 'admin.gamification.index', 'active' => 'admin.gamification.*', 'icon' => 'award'],
        ],
        'SYSTEM' => [
            ['label' => 'Externe Links', 'route' => 'admin.outbound-links.index', 'active' => 'admin.outbound-links.*', 'icon' => 'link-2'],
            ['label' => 'Kampagnenlinks', 'route' => 'admin.campaign-links.index', 'active' => 'admin.campaign-links.*', 'icon' => 'link-2'],
            ['label' => 'Navigation', 'route' => 'admin.navigation.index', 'active' => 'admin.navigation.*', 'icon' => 'category'],
            ['label' => 'App Remote Config', 'route' => 'admin.app-remote-config.index', 'active' => 'admin.app-remote-config.*', 'icon' => 'setting-2'],
            ['label' => 'Website Appearance', 'route' => 'admin.appearance.index', 'active' => 'admin.appearance.*', 'icon' => 'monitor'],
            ['label' => 'Feed Cards', 'route' => 'admin.app-remote-feed-cards.index', 'active' => 'admin.app-remote-feed-cards.*', 'icon' => 'document-text'],
            ['label' => 'App Push', 'route' => 'admin.app-push.index', 'active' => 'admin.app-push.*', 'icon' => 'notification'],
            ['label' => 'Theme Preview', 'route' => 'admin.theme-preview.index', 'active' => 'admin.theme-preview.*', 'icon' => 'monitor'],
            ['label' => 'Template-Mapping', 'route' => 'admin.vikinger-mapping.index', 'active' => 'admin.vikinger-mapping.*', 'icon' => 'document-text'],
        ],
    ];
@endphp

<nav class="hnt-admin-nav">
    @foreach($adminNav as $section => $items)
        <section class="hnt-admin-nav-section" aria-label="{{ $section }}">
            <span class="hnt-admin-nav-title">{{ $section }}</span>
            @foreach($items as $item)
                @if(\Illuminate\Support\Facades\Route::has($item['route']))
                    <a href="{{ route($item['route']) }}" @class(['is-active' => request()->routeIs(...(array) $item['active'])])>
                        <svg class="hnt-admin-nav-icon" aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#{{ $item['icon'] }}"></use></svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </section>
    @endforeach
</nav>
