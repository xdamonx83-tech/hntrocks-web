@php
    $viewer = auth()->user();
    $profile = $profileUser->profile;
    $displayName = trim((string) ($profileUser->name ?: $profileUser->username ?: 'HNT Hunter'));
    $username = trim((string) ($profileUser->username ?: 'hunter'));
    $headline = trim((string) ($profile?->headline ?: $profile?->hunt_role ?: $profile?->playstyle ?: ''));
    $bio = trim((string) ($profile?->bio ?: ''));
    $avatarUrl = $profileUser->avatarUrl();
    $coverUrl = $profileUser->coverUrl();
    $level = max(1, (int) ($profileUser->level ?? 1));
    $xpTotal = max(0, (int) ($profileUser->xp_total ?? 0));
    $nextLevelXp = max(250, $level * 250);
    $levelProgress = min(100, (int) round(($xpTotal % $nextLevelXp) / $nextLevelXp * 100));
    $friendsCount = (int) ($profileFriendsCount ?? $profileUser->friendsCount());
    $postsCount = (int) ($profileUser->visible_feed_posts_count ?? $profilePostsTotal ?? 0);
    $badgesCount = (int) ($profileUser->badges_count ?? 0);
    $momentsCount = (int) ($profileUser->moments_count ?? 0);
    $commentsCount = (int) ($profileUser->feed_comments_count ?? 0);
    $activeTeamsCount = (int) ($profileUser->active_teams_count ?? 0);
    $activeSection = $activeSection ?? 'timeline';
    $currentLocale = app()->getLocale() === 'en' ? 'en' : 'de';
    $defaultAvatar = asset('assets/vikinger/img/default-avatar.svg');
    $viewerAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $viewerCover = $viewer?->coverUrl() ?: asset('assets/vikinger/img/default-cover.svg');
    $headerAvatar = $viewerAvatar;
    $headerName = $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter');
    $headerHandle = $viewer?->username ? '@'.$viewer->username : __('ui.members');
    $reworkAsset = fn (string $path): string => \App\Support\HntTheme::asset($path, 'rework');
    $reworkStyleVersion = @filemtime(public_path('assets/themes/rework/styles.css')) ?: time();
    $reworkScriptVersion = @filemtime(public_path('assets/themes/rework/script.js')) ?: time();
    $socialiteChatTabsVersion = @filemtime(public_path('assets/socialite/js/hnt-socialite-chat-tabs.js')) ?: time();
    $formatCount = fn (int $count): string => number_format($count);
    $crownsSummary = $socialiteCrownsSummary ?? ['balance' => 0, 'enabled' => false];
    $marksBalance = (int) ($crownsSummary['balance'] ?? 0);
    $shopUrl = \Illuminate\Support\Facades\Route::has('crowns.shop') ? route('crowns.shop') : null;
    $notificationsUrl = \Illuminate\Support\Facades\Route::has('notifications.index') ? route('notifications.index') : '#';
    $messagesUrl = \Illuminate\Support\Facades\Route::has('messages.index') ? route('messages.index') : '#';
    $profileCompletionScore = \App\Support\ProfileCompletion::score($profileUser);
    $sectionUrl = function (string $section) use ($isOwnProfile, $profileUser): string {
        $routes = [
            'timeline' => ['own' => 'profile.show', 'public' => 'profile.public', 'fallback' => '/profile'],
            'about' => ['own' => 'profile.about', 'public' => 'profile.about.public', 'fallback' => '/profile/about'],
            'friends' => ['own' => 'profile.friends', 'public' => 'profile.friends.public', 'fallback' => '/profile/friends'],
            'badges' => ['own' => 'profile.badges', 'public' => 'profile.badges.public', 'fallback' => '/profile/badges'],
            'trophies' => ['own' => 'profile.trophies', 'public' => 'profile.trophies.public', 'fallback' => '/profile/trophies'],
            'teams' => ['own' => 'profile.teams', 'public' => 'profile.teams.public', 'fallback' => '/profile/teams'],
        ];
        $meta = $routes[$section] ?? $routes['timeline'];
        if (! \Illuminate\Support\Facades\Route::has($isOwnProfile ? $meta['own'] : $meta['public'])) {
            return url($meta['fallback']);
        }

        return $isOwnProfile ? route($meta['own']) : route($meta['public'], $profileUser);
    };
    $tabItems = [
        'timeline' => ['label' => __('ui.profile_section_timeline'), 'icon' => 'ph-image', 'count' => $postsCount],
        'about' => ['label' => __('ui.profile_section_info'), 'icon' => 'ph-user', 'count' => null],
        'friends' => ['label' => __('ui.preview_profile_tab_friends'), 'icon' => 'ph-users-three', 'count' => $friendsCount],
        'trophies' => ['label' => __('ui.preview_profile_tab_trophies'), 'icon' => 'ph-trophy', 'count' => $momentsCount],
        'badges' => ['label' => __('ui.preview_profile_tab_badges'), 'icon' => 'ph-seal-check', 'count' => $badgesCount],
    ];
    $infoRows = collect([
        __('ui.profile_bio') => $bio,
        __('ui.platform') => $profile?->platform,
        __('ui.region') => $profile?->region,
        __('ui.language') => $profile?->language,
        __('ui.playstyle') => $profile?->playstyle,
        __('ui.hunt_role') => $profile?->hunt_role,
        __('ui.profile_lfg_available') => $profile?->is_lfg_available ? __('ui.profile_lfg_active') : null,
        'Discord' => $profile?->discord_name,
        'Steam' => $profile?->steam_url,
        'Twitch' => $profile?->twitch_url,
        'YouTube' => $profile?->youtube_url,
    ])->filter(fn ($value) => filled($value));
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>{{ $displayName }} - {{ __('ui.profile_title_suffix') }} - HNT.rocks</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@400;500;600;700&amp;family=Bakbak+One&amp;family=Montserrat:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/regular/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/bold/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/fill/style.css" rel="stylesheet"/>
<link href="{{ \App\Support\HntTheme::asset('styles.css', 'rework') }}?v={{ $reworkStyleVersion }}" rel="stylesheet"/>
</head>
<body class="profile-page">
<div class="app">
<aside class="sidebar">
<div class="logo"><strong>HNT.</strong><span>ROCKS</span></div>
<button aria-expanded="false" aria-label="Sidebar erweitern" class="sidebar-toggle" data-sidebar-toggle="" type="button"><span></span><span></span></button>
<nav aria-label="Hauptnavigation" class="nav">
<a href="{{ route('feed.index') }}"><i aria-hidden="true" class="ph ph-house ph-icon"></i><span>Feed</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-game-controller ph-icon"></i><span>Games</span></a>
<a href="{{ \Illuminate\Support\Facades\Route::has('maps.index') ? route('maps.index') : '#' }}"><i aria-hidden="true" class="ph ph-map-trifold ph-icon"></i><span>Maps</span></a>
<a class="thin" href="#"><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i><span>Hunt</span></a>
<a href="{{ \Illuminate\Support\Facades\Route::has('gamification.index') ? route('gamification.index') : '#' }}"><i aria-hidden="true" class="ph ph-chart-bar ph-icon"></i><span>Gamification</span></a>
<a href="{{ $shopUrl ?: '#' }}"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i><span>Shop</span></a>
<a href="{{ \Illuminate\Support\Facades\Route::has('cups.index') ? route('cups.index') : '#' }}"><i aria-hidden="true" class="ph ph-trophy ph-icon"></i><span>Cups</span></a>
<a class="active" href="{{ route('profile.show') }}"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>{{ __('ui.profile') }}</span></a>
</nav>
<div class="nav-bottom">
<div class="nav-divider"></div>
@auth<a data-settings-modal-open="" href="{{ route('account.settings.edit') }}"><i aria-hidden="true" class="ph ph-gear-six ph-icon"></i><span>{{ __('ui.settings') }}</span></a>@endauth
@auth<form action="{{ route('logout') }}" method="post">@csrf<button class="user-logout" type="submit"><i aria-hidden="true" class="ph ph-sign-out ph-icon"></i><span>{{ __('ui.logout') }}</span></button></form>@endauth
</div>
</aside>
<main class="main">
<header class="topbar">
<a class="search-box" href="#"><i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i><span>Search</span></a>
@auth
<div class="top-actions" data-rework-header-live data-rework-live-badges-url="{{ route('socialite.header.live-badges') }}" data-rework-live-notifications-url="{{ route('socialite.header.notifications', ['variant' => 'rework']) }}" data-rework-live-messages-url="{{ route('socialite.header.messages', ['variant' => 'rework']) }}" data-rework-live-friend-requests-url="{{ route('socialite.header.friend-requests', ['variant' => 'rework']) }}">
<div class="action-menu notification-menu" data-rework-header-menu="notifications">
<a aria-expanded="false" aria-label="{{ __('ui.notifications') }}" class="action-btn" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-bell ph-icon"></i>@if($headerNotificationsUnread > 0)<span class="action-count" data-rework-notification-count>{{ $headerNotificationsUnread > 99 ? '99+' : $headerNotificationsUnread }}</span>@endif</a>
<div class="top-dropdown notification-dropdown" data-dropdown-panel=""><div class="dropdown-head"><div><strong>{{ __('ui.notifications') }}</strong><span data-rework-notification-summary data-label="{{ __('ui.notifications_unread_label') }}">{{ __('ui.notifications_unread_label') }}: {{ $formatCount((int) $headerNotificationsUnread) }}</span></div><a href="{{ $notificationsUrl }}">{{ __('ui.notifications_total') }}</a></div><div class="dropdown-list rework-dropdown-scroll" data-rework-notification-list>@include('themes.rework.feed.partials.header-notifications', ['headerNotifications' => $headerNotifications, 'notificationsUrl' => $notificationsUrl, 'defaultAvatar' => $defaultAvatar])</div><a class="dropdown-footer" href="{{ $notificationsUrl }}">{{ __('ui.view_all_notifications') }}</a></div>
</div>
<div class="action-menu friend-request-menu" data-rework-header-menu="friendRequests">
<a aria-expanded="false" aria-label="{{ __('ui.friend_requests') }}" class="action-btn" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-user-plus ph-icon"></i>@if($headerFriendRequestCount > 0)<span class="action-count" data-rework-friend-request-count>{{ $headerFriendRequestCount > 99 ? '99+' : $headerFriendRequestCount }}</span>@endif</a>
<div class="top-dropdown friend-request-dropdown" data-dropdown-panel=""><div class="dropdown-head"><div><strong>{{ __('ui.friend_requests') }}</strong><span data-rework-friend-request-summary data-label="{{ __('ui.notifications_total') }}">{{ $formatCount((int) $headerFriendRequestCount) }} {{ __('ui.notifications_total') }}</span></div><a href="{{ route('profile.friends') }}">{{ __('ui.notifications_total') }}</a></div><div class="dropdown-list request-list rework-dropdown-scroll" data-rework-friend-request-list data-empty-label="{{ __('ui.friend_requests_empty') }}">@include('themes.rework.feed.partials.header-friend-requests', ['headerFriendRequests' => $headerFriendRequests, 'defaultAvatar' => $defaultAvatar])</div><a class="dropdown-footer" href="{{ route('profile.friends') }}">{{ __('ui.more_friend_requests') }}</a></div>
</div>
<div class="action-menu message-menu" data-rework-header-menu="messages">
<a aria-expanded="false" aria-label="{{ __('ui.messages') }}" class="action-btn" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-chat-circle-dots ph-icon"></i>@if($headerMessagesUnread > 0)<span class="action-count" data-rework-message-count>{{ $headerMessagesUnread > 99 ? '99+' : $headerMessagesUnread }}</span>@endif</a>
<div class="top-dropdown message-dropdown" data-dropdown-panel=""><div class="dropdown-head"><div><strong>{{ __('ui.messages') }}</strong><span data-rework-message-summary data-label="{{ __('ui.notifications_unread_label') }}">{{ __('ui.notifications_unread_label') }}: {{ $formatCount((int) $headerMessagesUnread) }}</span></div><a href="{{ $messagesUrl }}">{{ __('ui.messages_total') }}</a></div><div class="dropdown-list rework-dropdown-scroll" data-rework-message-list>@include('themes.rework.feed.partials.header-messages', ['headerMessageConversations' => $headerMessageConversations, 'viewer' => $viewer, 'defaultAvatar' => $defaultAvatar])</div><a class="dropdown-footer" href="{{ $messagesUrl }}">{{ __('ui.view_all_messages') }}</a></div>
</div>
<div class="action-menu user-menu">
<a aria-expanded="false" aria-label="User menu" class="avatar-wrap" data-dropdown-toggle="" href="#"><img alt="{{ $headerName }}" class="header-avatar" data-rework-profile-avatar src="{{ $headerAvatar }}"/></a>
<div class="top-dropdown user-dropdown" data-dropdown-panel=""><div class="user-dropdown-head"><img alt="{{ $headerName }}" data-rework-profile-avatar src="{{ $headerAvatar }}"/><div><strong data-rework-profile-name>{{ $headerName }}</strong><span>{{ $headerHandle }} &middot; {{ $formatCount($marksBalance) }} {{ __('ui.crowns_label') }}</span></div></div><div class="user-menu-list"><a href="{{ route('profile.show') }}"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>{{ __('ui.my_profile') }}</span></a><a data-profile-edit-modal-open="" href="{{ route('profile.edit') }}"><i aria-hidden="true" class="ph ph-pencil-simple ph-icon"></i><span>{{ __('ui.edit_profile') }}</span></a><a data-settings-modal-open="" href="{{ route('account.settings.edit') }}"><i aria-hidden="true" class="ph ph-gear-six ph-icon"></i><span>{{ __('ui.settings') }}</span></a>@if($shopUrl)<a href="{{ $shopUrl }}"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i><span>{{ __('ui.crowns_shop_kicker') }}</span></a>@endif</div><form action="{{ route('logout') }}" method="post" class="user-logout-form">@csrf<button class="user-logout" type="submit"><i aria-hidden="true" class="ph ph-sign-out ph-icon"></i>{{ __('ui.logout') }}</button></form></div>
</div>
</div>
@else
<div class="top-actions"><a class="btn light" href="{{ route('login') }}">{{ __('ui.login') }}</a></div>
@endauth
</header>
<section class="content-grid">
<div class="left-col">
<section class="card hnt-profile-hero hnt-profile-hero-v2">
<div class="hnt-profile-cover" data-rework-profile-cover style="background-image: url('{{ $coverUrl }}');">
<img alt="{{ __('ui.profile_cover_alt', ['name' => $displayName]) }}" data-rework-profile-cover-image src="{{ $coverUrl }}"/>
@if($isOwnProfile)
<button type="button" class="profile-share-btn rework-profile-media-btn" data-rework-profile-media-upload="cover" aria-label="{{ __('ui.preview_profile_edit_cover_aria') }}"><i aria-hidden="true" class="ph ph-camera ph-icon"></i></button>
@endif
</div>
<div class="hnt-profile-mainline">
<div class="hnt-profile-stats hnt-profile-stats-left">
<div><strong>{{ $formatCount($postsCount) }}</strong><span>{{ __('ui.profile_posts_stat') }}</span></div>
<div><strong>{{ $formatCount($friendsCount) }}</strong><span>{{ __('ui.rework_profile_friends') }}</span></div>
</div>
<div class="hnt-profile-identity">
<div class="profile-avatar-badge profile-avatar-hunt">
<img alt="{{ $displayName }}" data-rework-profile-avatar src="{{ $avatarUrl }}"/>
@if($isOwnProfile)<button type="button" class="rework-profile-avatar-upload" data-rework-profile-media-upload="avatar" aria-label="{{ __('ui.preview_profile_edit_avatar_aria') }}"><i aria-hidden="true" class="ph ph-camera ph-icon"></i></button>@else<span>{{ __('ui.preview_profile_level_short') }} {{ $level }}</span>@endif
</div>
<h1 data-rework-profile-name>{{ $displayName }}</h1>
<p>{{ '@'.$username }}@if($headline !== '') &middot; <span data-rework-profile-headline>{{ $headline }}</span>@endif</p>
<div class="hnt-profile-actions">
@if($isOwnProfile)
<a class="btn ghost" data-profile-edit-modal-open="" href="{{ route('profile.edit') }}">{{ __('ui.edit_profile') }}</a>
@else
@if($profileCanRequestFriend)
<form action="{{ route('friends.store', $profileUser) }}" method="post">@csrf<button class="btn" type="submit">{{ __('ui.profile_add_friend_clean') }}</button></form>
@elseif($friendship?->isAccepted())
<form action="{{ route('friends.destroy', $friendship) }}" method="post">@csrf @method('DELETE')<button class="btn ghost" type="submit">{{ __('ui.preview_profile_friend_connected') }}</button></form>
@elseif($friendship?->isPending())
<span class="btn ghost profile-action-disabled">{{ $viewer && $friendship->isRequester($viewer) ? __('ui.preview_profile_friend_requested') : __('ui.preview_profile_friend_open') }}</span>
@endif
@if($profileCanMessage)
<a class="btn light" href="{{ route('messages.with-user', $profileUser) }}">{{ __('ui.profile_message_singular') }}</a>
@elseif(! $viewer)
<a class="btn light" href="{{ route('login') }}">{{ __('ui.preview_profile_login') }}</a>
@endif
@endif
</div>
</div>
<div class="hnt-profile-quick-info">
<p>{{ $bio !== '' ? \Illuminate\Support\Str::limit($bio, 180) : ($headline !== '' ? $headline : __('ui.profile_no_bio')) }}</p>
<div class="profile-chip-row">
@if($profile?->platform)<span>{{ $profile->platform }}</span>@endif
@if($profile?->region)<span>{{ $profile->region }}</span>@endif
@if($profile?->language)<span>{{ $profile->language }}</span>@endif
@if($profile?->is_lfg_available)<span class="filled">{{ __('ui.profile_lfg_active') }}</span>@endif
<em>{{ $formatCount($commentsCount) }} {{ __('ui.rework_profile_comments') }}</em>
</div>
</div>
</div>
<div class="hnt-profile-progress"><span>{{ __('ui.level') }} {{ $level }}</span><div class="profile-progress-track"><i style="width: {{ $levelProgress }}%"></i><strong>{{ $levelProgress }}%</strong></div></div>
</section>

<section class="card hnt-profile-tabs-card">
<div aria-label="{{ __('ui.profile_sections_aria') }}" class="hnt-profile-tabs">
@foreach($tabItems as $section => $item)
<a @class(['active' => $activeSection === $section]) href="{{ $sectionUrl($section) }}"><i aria-hidden="true" class="ph {{ $item['icon'] }} ph-icon"></i>{{ $item['label'] }}@if(! is_null($item['count'])) <span>{{ $formatCount((int) $item['count']) }}</span>@endif</a>
@endforeach
@if($isOwnProfile)<a class="profile-create-post" href="{{ route('feed.index') }}" data-post-composer-open="">{{ __('ui.profile_create_first_post') }}</a>@endif
</div>
</section>

@if($activeSection === 'timeline')
<div class="profile-tab-panel is-active">
@forelse($profilePosts as $post)
@include('themes.rework.feed.partials.post-card', ['post' => $post, 'reportedFeedKeys' => $reportedFeedKeys ?? collect()])
@empty
<section class="card profile-info-panel">
<h2>{{ __('ui.profile_no_timeline_title') }}</h2>
<p>{{ $isOwnProfile ? __('ui.profile_timeline_empty_own') : __('ui.profile_timeline_empty_user', ['name' => $displayName]) }}</p>
@if($isOwnProfile)<a class="btn light" href="{{ route('feed.index') }}">{{ __('ui.profile_create_first_post') }}</a>@endif
</section>
@endforelse
</div>
@elseif($activeSection === 'about')
<section class="card profile-info-panel">
<h2>{{ __('ui.profile_about_me') }}</h2>
<p>{{ $bio !== '' ? $bio : ($isOwnProfile ? __('ui.profile_about_empty_own') : __('ui.profile_about_empty_user')) }}</p>
<div class="profile-info-grid">
@forelse($infoRows as $label => $value)
<div><strong>{{ $label }}</strong><span>{{ $value }}</span></div>
@empty
<div><strong>{{ __('ui.profile_info') }}</strong><span>{{ __('ui.profile_not_set') }}</span></div>
@endforelse
</div>
</section>
@elseif($activeSection === 'friends')
<section class="card profile-list-panel">
<div class="profile-section-head"><h2>{{ __('ui.profile_friends_of', ['name' => $displayName]) }}</h2><span>{{ $formatCount($friendsCount) }}</span></div>
<div class="profile-friends-grid">
@forelse($profileFriendsPreview as $friend)
<a href="{{ route('profile.public', $friend) }}" class="profile-friend-card"><img src="{{ $friend->avatarUrl() }}" alt="{{ $friend->name ?: $friend->username }}"><div><strong>{{ $friend->name ?: $friend->username }}</strong><span>{{ '@'.$friend->username }} &middot; {{ __('ui.profile_mutual_friends', ['count' => (int) ($friend->common_friends_count ?? 0)]) }}</span></div></a>
@empty
<p>{{ __('ui.profile_no_friends_visible') }}</p>
@endforelse
</div>
</section>
@elseif($activeSection === 'badges')
<section class="card profile-badges-panel">
<div class="profile-section-head"><h2>{{ __('ui.profile_badges_of', ['name' => $displayName]) }}</h2><span>{{ $formatCount($badgesCount) }}</span></div>
<div class="badges-grid">
@forelse($latestBadges as $badge)
@php($badgeIconUrl = method_exists($badge, 'iconUrl') ? $badge->iconUrl() : null)
<article class="badge-card"><div class="badge-medal">@if($badgeIconUrl)<img src="{{ $badgeIconUrl }}" alt="{{ $badge->name }}">@else<span>{{ $badge->icon ?: '*' }}</span>@endif</div><h3>{{ $badge->name }}</h3><p>{{ $badge->description ?: __('ui.preview_profile_unlocked') }}</p><div><span>{{ method_exists($badge, 'rarityLabel') ? $badge->rarityLabel() : ucfirst((string) ($badge->rarity ?: 'normal')) }}</span>@if((int) ($badge->xp_reward ?? 0) > 0)<span>{{ (int) $badge->xp_reward }} XP</span>@endif</div></article>
@empty
<p>{{ __('ui.profile_no_badges_unlocked') }}</p>
@endforelse
</div>
</section>
@else
<section class="card profile-moments-panel">
<div class="profile-section-head"><h2>{{ __('ui.preview_profile_trophies_title') }}</h2><span>{{ $formatCount($momentsCount + $activeTeamsCount) }}</span></div>
<div class="profile-info-grid">
<div><strong>{{ $formatCount($momentsCount) }}</strong><span>{{ __('ui.rework_profile_moments') }}</span></div>
<div><strong>{{ $formatCount($activeTeamsCount) }}</strong><span>{{ __('ui.profile_teams_title') }}</span></div>
<div><strong>{{ $formatCount((int) ($trophyCabinet['stats']['cup_points'] ?? 0)) }}</strong><span>{{ __('ui.preview_profile_cup_points') }}</span></div>
<div><strong>{{ $formatCount((int) ($trophyCabinet['stats']['badges'] ?? 0)) }}</strong><span>{{ __('ui.profile_badges_stat') }}</span></div>
</div>
</section>
@endif
</div>
@include('themes.rework.profile.partials.right-widgets')
</section>
</main>
</div>
@if($isOwnProfile)
<input type="file" accept="image/jpeg,image/png,image/webp" hidden data-rework-profile-media-input="avatar">
<input type="file" accept="image/jpeg,image/png,image/webp" hidden data-rework-profile-media-input="cover">
<p class="settings-status rework-profile-media-status" data-rework-profile-media-status data-rework-profile-media-url="{{ route('profile.media.update') }}" hidden></p>
@include('themes.rework.profile.partials.profile-edit-modal')
@endif
<div aria-hidden="true" class="modal-backdrop" data-comment-modal="" data-rework-report-url="{{ \Illuminate\Support\Facades\Route::has('reports.store') ? route('reports.store') : '#' }}">
<section aria-labelledby="comment-modal-title" aria-modal="true" class="comment-modal" role="dialog"><button aria-label="{{ __('ui.preview_post_modal_close_aria') }}" class="modal-close post-composer-close" data-comment-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button><div class="comment-modal-layout"><div class="comment-modal-post"><div class="modal-post-head"><a data-rework-modal-author-url href="#"><img alt="" src="{{ $defaultAvatar }}"/></a><div><a data-rework-modal-author-url href="#"><strong data-rework-modal-author></strong></a><span data-rework-modal-meta></span></div></div><div class="modal-post-media" hidden></div><div class="modal-post-body"></div><div class="modal-post-stats"></div></div><div class="comment-modal-panel"><header class="comment-modal-head"><strong id="comment-modal-title">{{ __('ui.comments') }}</strong></header><div class="comment-thread"></div></div></div></section>
</div>
@include('themes.socialite.partials.chat-tabs')
<script defer src="{{ \App\Support\HntTheme::asset('script.js', 'rework') }}?v={{ $reworkScriptVersion }}"></script>
<script defer src="{{ asset('assets/socialite/js/hnt-socialite-chat-tabs.js') }}?v={{ $socialiteChatTabsVersion }}"></script>
</body>
</html>
