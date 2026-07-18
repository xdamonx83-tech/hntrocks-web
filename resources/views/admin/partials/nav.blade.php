@php
    $adminNav = [
        ['label' => 'Übersicht', 'route' => 'admin.index', 'active' => 'admin.index', 'icon' => '01'],
        ['label' => 'Nutzer', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'NU'],
        ['label' => 'Reports', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'icon' => 'RP'],
        ['label' => 'Feedback & Tickets', 'route' => 'admin.feedback-tickets.index', 'active' => 'admin.feedback-tickets.*', 'icon' => 'FT'],
        ['label' => 'Cup-Feedback', 'route' => 'admin.cup-feedback.index', 'active' => 'admin.cup-feedback.*', 'icon' => 'CF'],
        ['label' => 'Cup-Ideen', 'route' => 'admin.cup-ideas.index', 'active' => 'admin.cup-ideas.*', 'icon' => 'CI'],
        ['label' => 'Moment der Woche', 'route' => 'admin.moment-of-week.index', 'active' => 'admin.moment-of-week.*', 'icon' => 'MW'],
        ['label' => 'Inhalte', 'route' => 'admin.content.index', 'active' => 'admin.content.*', 'icon' => 'IN'],
        ['label' => 'Guides', 'route' => 'admin.guides.index', 'active' => 'admin.guides.*', 'icon' => 'GU'],
        ['label' => 'News-Zentrale', 'route' => 'admin.hunt-news.index', 'active' => 'admin.hunt-news.*', 'icon' => 'NZ'],
        ['label' => 'HNT Maps', 'route' => 'admin.maps.index', 'active' => ['admin.maps.index', 'admin.maps.markers*'], 'icon' => 'MP'],
        ['label' => 'Kassenspots', 'route' => 'admin.maps.cash-spots.index', 'active' => 'admin.maps.cash-spots.*', 'icon' => 'KS'],
        ['label' => 'HNT-Aufträge', 'route' => 'admin.contracts.index', 'active' => 'admin.contracts.*', 'icon' => 'AU'],
        ['label' => 'Loadout-Challenges', 'route' => 'admin.loadout-challenges.index', 'active' => 'admin.loadout-challenges.*', 'icon' => 'LC'],
        ['label' => 'Externe Links', 'route' => 'admin.outbound-links.index', 'active' => 'admin.outbound-links.*', 'icon' => 'OL'],
        ['label' => 'Kampagnenlinks', 'route' => 'admin.campaign-links.index', 'active' => 'admin.campaign-links.*', 'icon' => 'GO'],
        ['label' => 'Badges & Quests', 'route' => 'admin.gamification.index', 'active' => 'admin.gamification.*', 'icon' => 'BQ'],
        ['label' => 'Navigation', 'route' => 'admin.navigation.index', 'active' => 'admin.navigation.*', 'icon' => 'NV'],
        ['label' => 'App Remote Config', 'route' => 'admin.app-remote-config.index', 'active' => 'admin.app-remote-config.*', 'icon' => 'RC'],
        ['label' => 'Feed Cards', 'route' => 'admin.app-remote-feed-cards.index', 'active' => 'admin.app-remote-feed-cards.*', 'icon' => 'FC'],
        ['label' => 'App Push', 'route' => 'admin.app-push.index', 'active' => 'admin.app-push.*', 'icon' => 'PS'],
        ['label' => 'Theme Preview', 'route' => 'admin.theme-preview.index', 'active' => 'admin.theme-preview.*', 'icon' => 'TP'],
        ['label' => 'Template-Mapping', 'route' => 'admin.vikinger-mapping.index', 'active' => 'admin.vikinger-mapping.*', 'icon' => 'TM'],
    ];
@endphp

<nav class="hnt-admin-nav">
    <span class="hnt-admin-nav-title">Main Menu</span>
    @foreach($adminNav as $item)
        @if(\Illuminate\Support\Facades\Route::has($item['route']))
            <a href="{{ route($item['route']) }}" @class(['is-active' => request()->routeIs(...(array) $item['active'])])>
                <span class="hnt-admin-nav-icon">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endif
    @endforeach
</nav>
