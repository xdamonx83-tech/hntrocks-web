@php
    $viewer = auth()->user();
    $socialiteFeedFilter = $socialiteFeedFilter ?? 'all';
    $reworkAsset = fn (string $path): string => \App\Support\HntTheme::asset($path, 'rework');
    $reworkStyleVersion = @filemtime(public_path('assets/themes/rework/styles.css')) ?: time();
    $reworkScriptVersion = @filemtime(public_path('assets/themes/rework/script.js')) ?: time();
    $socialiteChatTabsVersion = @filemtime(public_path('assets/socialite/js/hnt-socialite-chat-tabs.js')) ?: time();
    $formatCount = fn (int $count): string => number_format($count);
    $feedFilterUrl = function (string $filterKey): string {
        $query = request()->query();
        unset($query['page'], $query['fragment']);

        if ($filterKey === 'all') {
            unset($query['filter']);
        } else {
            $query['filter'] = $filterKey;
        }

        return route('feed.index', $query);
    };
    $memberProfileUrl = function ($member): string {
        if (! $member?->username) {
            return '#';
        }

        return (int) $member->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $member);
    };
    $postAuthorUrl = function ($author): string {
        if (! $author?->username) {
            return '#';
        }

        return (int) $author->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $author);
    };
    $crownsSummary = $socialiteCrownsSummary ?? ['balance' => 0, 'enabled' => false];
    $profileStats = $socialiteProfileStats ?? [];
    $marksBalance = (int) ($crownsSummary['balance'] ?? 0);
    $shopUrl = \Illuminate\Support\Facades\Route::has('crowns.shop') ? route('crowns.shop') : null;
    $membersUrl = \Illuminate\Support\Facades\Route::has('members.index') ? route('members.index') : null;
    $currentLocale = app()->getLocale() === 'en' ? 'en' : 'de';
    $notificationsUrl = route('notifications.index');
    $messagesUrl = route('messages.index');
    $defaultAvatar = asset('assets/vikinger/img/default-avatar.svg');
    $headerAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $headerName = $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter');
    $headerHandle = $viewer?->username ? '@'.$viewer->username : __('ui.members');
    $viewer?->loadMissing('profile');
    $viewerProfile = $viewer?->profile;
    $viewerAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $viewerCover = $viewer?->coverUrl() ?: asset('assets/vikinger/img/default-cover.svg');
    $viewerCompletion = $viewer ? \App\Support\ProfileCompletion::score($viewer) : 0;
    $profileVisibility = old('profile_visibility', $viewerProfile?->profile_visibility ?? 'public');
    $profilePlatform = old('platform', $viewerProfile?->platform);
    $profilePlaystyle = old('playstyle', $viewerProfile?->playstyle);
    $profileRegion = old('region', $viewerProfile?->region);
    $profileLanguage = old('language', $viewerProfile?->language);
    $profileIsLfgAvailable = (bool) old('is_lfg_available', $viewerProfile?->is_lfg_available);
    $reworkProfileOptions = [
        'platform' => ['PC', 'PlayStation', 'Xbox', 'Crossplay'],
        'playstyle' => [
            'Tactical' => __('ui.playstyle_tactical'),
            'Aggressive' => __('ui.playstyle_aggressive'),
            'Beginner friendly' => __('ui.playstyle_beginner'),
            'Casual' => 'Casual',
            'Competitive' => 'Competitive',
        ],
        'region' => ['EU', 'US East', 'US West', 'Asia', 'Oceania'],
        'language' => ['Deutsch', 'English', 'Francais', 'Espanol', 'Other'],
    ];
    $headerNotificationsUnread = $viewer
        ? $viewer->notificationItems()->standard()->unread()->count()
        : 0;
    $headerNotifications = $viewer
        ? $viewer->notificationItems()
            ->standard()
            ->with('actor.profile')
            ->orderByRaw('read_at is not null')
            ->latest()
            ->limit(5)
            ->get()
        : collect();
    $headerFriendRequests = $viewer
        ? \App\Models\Friendship::query()
            ->where('recipient_id', $viewer->id)
            ->where('status', \App\Models\Friendship::STATUS_PENDING)
            ->with('requester.profile')
            ->latest()
            ->limit(5)
            ->get()
        : collect();
    $headerFriendRequestCount = $viewer
        ? \App\Models\Friendship::query()
            ->where('recipient_id', $viewer->id)
            ->where('status', \App\Models\Friendship::STATUS_PENDING)
            ->count()
        : 0;
    $headerMessageConversations = $viewer
        ? \App\Models\Conversation::query()
            ->forUser($viewer)
            ->where('type', 'private')
            ->with(['users.profile', 'users.privacySettings', 'latestMessage.user'])
            ->latest('updated_at')
            ->limit(5)
            ->get()
        : collect();
    $headerMessagesUnread = $viewer && method_exists($viewer, 'unreadMessagesCount')
        ? $viewer->unreadMessagesCount()
        : 0;
    $highlightScore = function ($post): int {
        return (int) ($post?->reactions_count ?? 0)
            + (int) ($post?->comments_count ?? 0)
            + (int) ($post?->shares_count ?? 0);
    };
    $feedFilters = [
        'all' => 'All',
        'friends' => 'Freunde',
        'media' => 'Medien',
        'mentions' => 'Mentions',
    ];
    $reworkNotificationSettings = $reworkNotificationSettings ?? null;
    $reworkNotificationGroups = $reworkNotificationGroups ?? [];
    $reworkPrivacySettings = $reworkPrivacySettings ?? null;
    $reworkBlockedUsers = $reworkBlockedUsers ?? collect();
    $reworkTwoFactorEnabled = (bool) ($reworkTwoFactorEnabled ?? false);
    $reworkTwoFactorRecoveryCount = (int) ($reworkTwoFactorRecoveryCount ?? 0);
    $reworkDeletionRequest = $reworkDeletionRequest ?? null;
    $reworkPrivacyToggles = [
        'allow_team_invites' => ['title' => __('ui.allow_team_invites'), 'text' => __('ui.allow_team_invites_text')],
        'allow_lfg_invites' => ['title' => __('ui.allow_lfg_invites'), 'text' => __('ui.allow_lfg_invites_text')],
        'show_online_status' => ['title' => __('ui.show_online_status'), 'text' => __('ui.show_online_status_text')],
        'show_activity_feed' => ['title' => __('ui.show_activity_feed'), 'text' => __('ui.show_activity_feed_text')],
        'show_gamification' => ['title' => __('ui.show_gamification'), 'text' => __('ui.show_gamification_text')],
        'data_usage_consent' => ['title' => __('ui.data_usage_consent'), 'text' => __('ui.data_usage_consent_text')],
    ];
    $reworkSettingsFields = array_merge(
        array_keys($reworkNotificationGroups),
        ['profile_visibility', 'allow_messages_from', 'username', 'reason', 'current_password', 'password', 'delete_confirmation', 'code'],
        array_keys($reworkPrivacyToggles)
    );
    $reworkSettingsHasErrors = collect($reworkSettingsFields)->contains(fn ($field) => $errors->has($field));
    $reworkSettingsStatusMessages = [
        __('ui.notification_settings_saved'),
        __('ui.privacy_settings_saved'),
        __('ui.user_blocked_status'),
        __('ui.user_unblocked_status'),
        __('ui.security_password_updated'),
        __('ui.two_factor_setup_started'),
        __('ui.account_deletion_cancelled_status'),
    ];
    $reworkSettingsShouldOpen = $reworkSettingsHasErrors || (session('status') && in_array(session('status'), $reworkSettingsStatusMessages, true));
    $reworkSettingsActiveTab = 'notifications';
    if (collect(['profile_visibility', 'allow_messages_from', 'allow_team_invites', 'allow_lfg_invites', 'show_online_status', 'show_activity_feed', 'show_gamification', 'data_usage_consent'])->contains(fn ($field) => $errors->has($field)) || session('status') === __('ui.privacy_settings_saved')) {
        $reworkSettingsActiveTab = 'privacy';
    } elseif (collect(['username', 'reason'])->contains(fn ($field) => $errors->has($field)) || in_array(session('status'), [__('ui.user_blocked_status'), __('ui.user_unblocked_status')], true)) {
        $reworkSettingsActiveTab = 'blocked';
    } elseif (collect(['current_password', 'password', 'code', 'delete_confirmation'])->contains(fn ($field) => $errors->has($field)) || in_array(session('status'), [__('ui.security_password_updated'), __('ui.two_factor_setup_started'), __('ui.account_deletion_cancelled_status')], true)) {
        $reworkSettingsActiveTab = 'security';
    }
@endphp
<!DOCTYPE html>

<html lang="{{ $currentLocale }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>HNT.rocks Rework Feed Preview</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@400;500;600;700&amp;family=Bakbak+One&amp;family=Montserrat:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/regular/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/bold/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/fill/style.css" rel="stylesheet"/>
<link href="{{ \App\Support\HntTheme::asset('styles.css', 'rework') }}?v={{ $reworkStyleVersion }}" rel="stylesheet"/>
<style>
/* 111: inline mobile guard, independent from cached external CSS */
@media (hover: none) and (pointer: coarse), (max-width: 1100px) {
  .right-col,
  .right-col *,
  body > .rework-profile-late-sticky-clone,
  body > .rework-profile-late-sticky-clone * {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    position: static !important;
    width: 0 !important;
    height: 0 !important;
    max-width: 0 !important;
    max-height: 0 !important;
    overflow: hidden !important;
    transform: none !important;
    animation: none !important;
    transition: none !important;
  }

  .content-grid {
    display: block !important;
    grid-template-columns: minmax(0, 1fr) !important;
  }

  .left-col {
    width: 100% !important;
    max-width: none !important;
  }
}
</style>
</head>
<body>
<div class="app">
<aside class="sidebar">
<div class="logo"><strong>HNT.</strong><span>ROCKS</span></div>
<button aria-expanded="false" aria-label="Sidebar erweitern" class="sidebar-toggle" data-sidebar-toggle="" type="button"><span></span><span></span></button>
<nav aria-label="Hauptnavigation" class="nav">
<a class="active" href="{{ route('feed.index') }}"><i aria-hidden="true" class="ph ph-house ph-icon"></i><span>Feed</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-game-controller ph-icon"></i><span>Games</span></a><a href="#"><i aria-hidden="true" class="ph ph-map-trifold ph-icon"></i><span>Maps</span></a>
<a class="thin" href="#"><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i><span>Hunt</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-chart-bar ph-icon"></i><span>Gamification</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i><span>Shop</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-trophy ph-icon"></i><span>Cups</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>Profil</span></a>
</nav>
<div class="nav-bottom">
<div class="nav-divider"></div>
<a data-settings-modal-open="" href="#"><i aria-hidden="true" class="ph ph-gear-six ph-icon"></i><span>Einstellungen</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-sign-out ph-icon"></i><span>Logout</span></a>
</div>
</aside>
<main class="main">
<header class="topbar">
<a class="search-box" href="#"><i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i><span>Search</span></a>
<div class="top-actions" data-rework-header-live data-rework-live-badges-url="{{ route('socialite.header.live-badges') }}" data-rework-live-notifications-url="{{ route('socialite.header.notifications', ['variant' => 'rework']) }}" data-rework-live-messages-url="{{ route('socialite.header.messages', ['variant' => 'rework']) }}" data-rework-live-friend-requests-url="{{ route('socialite.header.friend-requests', ['variant' => 'rework']) }}">
<div class="action-menu notification-menu" data-rework-header-menu="notifications">
<a aria-expanded="false" aria-label="{{ __('ui.notifications') }}" class="action-btn" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-bell ph-icon"></i>@if($headerNotificationsUnread > 0)<span class="action-count" data-rework-notification-count>{{ $headerNotificationsUnread > 99 ? '99+' : $headerNotificationsUnread }}</span>@endif</a>
<div class="top-dropdown notification-dropdown" data-dropdown-panel="">
<div class="dropdown-head">
<div>
<strong>{{ __('ui.notifications') }}</strong>
<span data-rework-notification-summary data-label="{{ __('ui.notifications_unread_label') }}">{{ __('ui.notifications_unread_label') }}: {{ number_format($headerNotificationsUnread) }}</span>
</div>
<a href="{{ $notificationsUrl }}">{{ __('ui.notifications_total') }}</a>
</div>
<div class="dropdown-list rework-dropdown-scroll" data-rework-notification-list>
@include('themes.rework.feed.partials.header-notifications', ['headerNotifications' => $headerNotifications, 'notificationsUrl' => $notificationsUrl, 'defaultAvatar' => $defaultAvatar])
</div>
<a class="dropdown-footer" href="{{ $notificationsUrl }}">{{ __('ui.view_all_notifications') }}</a>
</div>
</div>
<div class="action-menu friend-request-menu" data-rework-header-menu="friendRequests">
<a aria-expanded="false" aria-label="{{ __('ui.friend_requests') }}" class="action-btn" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-user-plus ph-icon"></i>@if($headerFriendRequestCount > 0)<span class="action-count" data-rework-friend-request-count>{{ $headerFriendRequestCount > 99 ? '99+' : $headerFriendRequestCount }}</span>@endif</a>
<div class="top-dropdown friend-request-dropdown" data-dropdown-panel="">
<div class="dropdown-head">
<div>
<strong>{{ __('ui.friend_requests') }}</strong>
<span data-rework-friend-request-summary data-label="{{ __('ui.notifications_total') }}">{{ number_format($headerFriendRequestCount) }} {{ __('ui.notifications_total') }}</span>
</div>
<a href="{{ route('profile.friends') }}">{{ __('ui.notifications_total') }}</a>
</div>
<div class="dropdown-list request-list rework-dropdown-scroll" data-rework-friend-request-list data-empty-label="{{ __('ui.friend_requests_empty') }}">
@include('themes.rework.feed.partials.header-friend-requests', ['headerFriendRequests' => $headerFriendRequests, 'defaultAvatar' => $defaultAvatar])
</div>
<a class="dropdown-footer" href="{{ route('profile.friends') }}">{{ __('ui.more_friend_requests') }}</a>
</div>
</div>
<div class="action-menu message-menu" data-rework-header-menu="messages">
<a aria-expanded="false" aria-label="{{ __('ui.messages') }}" class="action-btn" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-chat-circle-dots ph-icon"></i>@if($headerMessagesUnread > 0)<span class="action-count" data-rework-message-count>{{ $headerMessagesUnread > 99 ? '99+' : $headerMessagesUnread }}</span>@endif</a>
<div class="top-dropdown message-dropdown" data-dropdown-panel="">
<div class="dropdown-head">
<div>
<strong>{{ __('ui.messages') }}</strong>
<span data-rework-message-summary data-label="{{ __('ui.notifications_unread_label') }}">{{ __('ui.notifications_unread_label') }}: {{ number_format($headerMessagesUnread) }}</span>
</div>
<a href="{{ $messagesUrl }}">{{ __('ui.messages_total') }}</a>
</div>
<div class="dropdown-list rework-dropdown-scroll" data-rework-message-list>
@include('themes.rework.feed.partials.header-messages', ['headerMessageConversations' => $headerMessageConversations, 'viewer' => $viewer, 'defaultAvatar' => $defaultAvatar])
</div>
<a class="dropdown-footer" href="{{ $messagesUrl }}">{{ __('ui.view_all_messages') }}</a>
</div>
</div>
<div class="action-menu user-menu">
<a aria-expanded="false" aria-label="User menu" class="avatar-wrap" data-dropdown-toggle="" href="#"><img alt="{{ $headerName }}" class="header-avatar" data-rework-profile-avatar src="{{ $headerAvatar }}"/></a>
<div class="top-dropdown user-dropdown" data-dropdown-panel="">
<div class="user-dropdown-head">
<img alt="{{ $headerName }}" data-rework-profile-avatar src="{{ $headerAvatar }}"/>
<div>
<strong data-rework-profile-name>{{ $headerName }}</strong>
<span>{{ $headerHandle }} &middot; {{ number_format($marksBalance) }} {{ __('ui.crowns_label') }}</span>
</div>
</div>
<div class="user-menu-list">
<a href="{{ route('profile.show') }}"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>{{ __('ui.my_profile') }}</span></a>
<a data-profile-edit-modal-open="" href="{{ route('profile.edit') }}"><i aria-hidden="true" class="ph ph-pencil-simple ph-icon"></i><span>{{ __('ui.edit_profile') }}</span></a>
<a data-settings-modal-open="" href="{{ route('account.settings.edit') }}"><i aria-hidden="true" class="ph ph-gear-six ph-icon"></i><span>{{ __('ui.settings') }}</span></a>
<div class="user-menu-language">
<span><i aria-hidden="true" class="ph ph-globe-hemisphere-west ph-icon"></i>{{ __('ui.language') }}</span>
<div>
<a class="{{ $currentLocale === 'de' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'de') }}" lang="de" hreflang="de" @if($currentLocale === 'de') aria-current="true" @endif>{{ __('ui.language_german') }}</a>
<a class="{{ $currentLocale === 'en' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'en') }}" lang="en" hreflang="en" @if($currentLocale === 'en') aria-current="true" @endif>{{ __('ui.language_english') }}</a>
</div>
</div>
@if($shopUrl)
<a href="{{ $shopUrl }}"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i><span>{{ __('ui.crowns_shop_kicker') }}</span></a>
@endif
</div>
<form action="{{ route('logout') }}" method="post" class="user-logout-form">
@csrf
<button class="user-logout" type="submit"><i aria-hidden="true" class="ph ph-sign-out ph-icon"></i>{{ __('ui.logout') }}</button>
</form>
</div>
</div>
</div>
</header>
<section class="content-grid">
<div class="left-col">
<section class="card hero-card">
<div class="eyebrow">Newsfeed</div>
<h1>Check What Your Friends Up To!</h1>
<p>Conveniently customize proactive web services for leveraged without continually aggregate frictionless ou well-structured HNT activity..</p>
<div aria-label="{{ __('ui.rework_post_composer_open') }}" class="composer-mini" data-post-composer-open="" role="button" tabindex="0">
<span>{{ __('ui.rework_composer_prompt', ['name' => $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter')]) }}</span>
<span class="spacer"></span>
<a class="square-icon" data-post-composer-open="" href="#"><i aria-hidden="true" class="ph ph-image ph-icon"></i></a>
<a class="square-icon" data-post-composer-open="" href="#"><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></a>
<a class="btn" data-post-composer-open="" href="#">{{ __('ui.rework_post_create') }}</a>
</div>
</section>
<nav class="rework-feed-filters" aria-label="Feed Filter">
@foreach($feedFilters as $filterKey => $filterLabel)
<a @class(['active' => $socialiteFeedFilter === $filterKey]) href="{{ $feedFilterUrl($filterKey) }}">{{ $filterLabel }}</a>
@endforeach
</nav>
<div data-rework-post-stream>
@include('themes.rework.feed.partials.post-items', ['socialitePosts' => $socialitePosts, 'reportedFeedKeys' => $reportedFeedKeys ?? collect()])
</div>
@if(method_exists($socialitePosts, 'hasMorePages') && $socialitePosts->hasMorePages())
<div class="rework-load-more-wrap" data-rework-load-more-wrap>
<button
    class="btn rework-load-more"
    type="button"
    data-rework-load-more
    data-next-url="{{ $socialitePosts->nextPageUrl() }}"
    data-loading-label="Lädt..."
    data-ready-label="Weitere Posts laden"
    data-error-label="Erneut versuchen"
>
    <span data-rework-load-more-label>Weitere Posts laden</span>
</button>
</div>
@endif
</div>
<aside class="right-col">
<section class="profile-card card">
<div class="profile-top"><strong data-rework-profile-name>{{ $viewer?->name ?: 'HNT Hunter' }}</strong></div>
<div class="profile-main">
<img alt="{{ __('ui.crowns_label') }}" class="mark" src="{{ $reworkAsset('images/bounty-marks.png') }}"/>
<div class="levels">
<span class="level-badge">{{ __('ui.level') }} {{ $viewer?->level ?? 1 }}</span>
<span class="level-badge">{{ number_format((int) ($profileStats['xp'] ?? ($viewer?->xp_total ?? 0))) }} XP</span>
</div>
</div>
<div class="balance">
<div><strong>{{ number_format($marksBalance) }}</strong><span>{{ __('ui.crowns_label') }}</span></div>
<div class="profile-buttons">
@if($shopUrl)
<a class="btn light" href="{{ $shopUrl }}">{{ __('ui.crowns_shop_kicker') }}</a>
@endif
</div>
</div>
<div class="profile-stat-grid">
<span><strong>{{ number_format((int) ($profileStats['posts'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_posts') }}</em></span>
<span><strong>{{ number_format((int) ($profileStats['reactions'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_reactions') }}</em></span>
<span><strong>{{ number_format((int) ($profileStats['comments'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_comments') }}</em></span>
<span><strong>{{ number_format((int) ($profileStats['moments'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_moments') }}</em></span>
<span><strong>{{ number_format((int) ($profileStats['friends'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_friends') }}</em></span>
<span><strong>{{ number_format((int) ($profileStats['lfg'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_lfg') }}</em></span>
</div>
</section>
<section class="side-card suggested">
<div class="side-head"><h2>{{ __('ui.rework_suggested_for_you') }}</h2>@if($membersUrl)<a href="{{ $membersUrl }}">{{ __('ui.see_all') }}</a>@endif</div>
<div class="suggestion-list">
@forelse(($socialiteMembers ?? collect())->take(3) as $member)
<div class="suggestion">
<a class="suggestion-avatar" href="{{ $memberProfileUrl($member) }}"><img alt="{{ $member->name }}" src="{{ $member->avatarUrl() }}"/></a>
<div class="suggestion-info"><a href="{{ $memberProfileUrl($member) }}"><strong>{{ $member->name }}</strong></a><span>{{ $member->username ? '@'.$member->username : 'HNT Hunter' }}</span></div>
<form action="{{ route('friends.store', $member) }}" class="rework-friend-request-form" data-rework-friend-request-form method="post" data-requested-label="{{ __('ui.requested') }}" data-failed-label="{{ __('ui.rework_friend_request_failed') }}">
@csrf
<button class="btn light" type="submit">{{ __('ui.profile_add_friend_clean') }}</button>
</form>
</div>
@empty
<div class="side-empty">{{ __('ui.rework_no_suggestions') }}</div>
@endforelse
</div>
</section>
<section class="side-card highlights">
<div class="side-head"><h2>{{ __('ui.rework_current_highlights') }}</h2></div>
<div class="highlight-list">
@if($socialiteHighlightTopPost)
<a class="highlight-card" href="{{ $socialiteHighlightTopPost->permalink() }}">
<span class="highlight-badge">{{ __('ui.rework_top_post') }}</span>
<strong>{{ $socialiteHighlightTopPost->excerpt(90) ?: __('ui.rework_top_post_empty') }}</strong>
<small>{{ __('ui.rework_feed_author', ['author' => $socialiteHighlightTopPost->user?->name ?: ($socialiteHighlightTopPost->user?->username ?: 'HNT Hunter')]) }}</small>
<span class="highlight-meta"><i aria-hidden="true" class="ph ph-heart ph-icon"></i>{{ number_format((int) ($socialiteHighlightTopPost->reactions_count ?? 0)) }} <i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>{{ number_format((int) ($socialiteHighlightTopPost->comments_count ?? 0)) }} <i aria-hidden="true" class="ph ph-share-network ph-icon"></i>{{ number_format((int) ($socialiteHighlightTopPost->shares_count ?? 0)) }} <em>{{ trans_choice('ui.rework_interactions', $highlightScore($socialiteHighlightTopPost), ['count' => $highlightScore($socialiteHighlightTopPost)]) }}</em></span>
</a>
@endif
@if($socialiteHighlightLfg)
<a class="highlight-card" href="{{ route('lfg.show', $socialiteHighlightLfg) }}">
<span class="highlight-badge">{{ __('ui.rework_new_lfg') }}</span>
<strong>{{ $socialiteHighlightLfg->title }}</strong>
<small>{{ $socialiteHighlightLfg->user?->name ?: ($socialiteHighlightLfg->user?->username ?: 'HNT Hunter') }}</small>
<span class="highlight-pills">
@foreach(array_slice($socialiteHighlightLfg->displayTags(), 0, 3) as $tag)
<em>{{ $tag }}</em>
@endforeach
<em>{{ $socialiteHighlightLfg->statusLabel() }}</em>
</span>
</a>
@endif
@if($socialiteHighlightCup)
<a class="highlight-card" href="{{ route('cups.show', $socialiteHighlightCup) }}">
<span class="highlight-badge">{{ __('ui.rework_active_cup') }}</span>
<strong>{{ $socialiteHighlightCup->title }}</strong>
<small>{{ $socialiteHighlightCup->displaySummary() }}</small>
<span class="highlight-pills"><em>{{ $socialiteHighlightCup->statusLabel() }}</em><em>{{ trans_choice('ui.rework_cup_team_count', (int) ($socialiteHighlightCup->active_teams_count ?? 0), ['count' => (int) ($socialiteHighlightCup->active_teams_count ?? 0)]) }}</em></span>
</a>
@endif
@if(! $socialiteHighlightTopPost && ! $socialiteHighlightLfg && ! $socialiteHighlightCup)
<div class="side-empty">{{ __('ui.rework_no_highlights') }}</div>
@endif
</div>
</section>
</aside>
</section>
</main>
</div>
<div aria-hidden="true" class="modal-backdrop" data-comment-modal="" data-rework-report-url="{{ route('reports.store') }}">
<section aria-labelledby="comment-modal-title" aria-modal="true" class="comment-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<button aria-label="{{ __('ui.preview_post_modal_close_aria') }}" class="modal-close post-composer-close" data-comment-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
<div class="comment-modal-layout">
<div class="comment-modal-post">
<div class="modal-post-head">
<a data-rework-modal-author-url href="#"><img alt="" src="{{ asset('assets/vikinger/img/default-avatar.svg') }}"/></a>
<div>
<a data-rework-modal-author-url href="#"><strong data-rework-modal-author></strong></a>
<span data-rework-modal-meta></span>
</div>
</div>
<div class="modal-post-media" data-rework-modal-media hidden></div>
<div class="modal-post-body" data-rework-modal-body hidden></div>
<div class="modal-post-stats" data-rework-modal-stats></div>
</div>
<div class="comment-modal-panel">
<div class="comment-modal-head">
<div>
<span>{{ __('ui.feed_post') }}</span>
<h2 id="comment-modal-title">{{ __('ui.comments') }}</h2>
</div>
<strong data-rework-modal-comment-count>0</strong>
</div>
<div class="comment-thread" data-rework-modal-comments></div>
<form class="modal-composer" data-rework-comment-form method="post">
<img alt="{{ $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter') }}" src="{{ $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<input name="body" placeholder="{{ __('ui.rework_comment_placeholder') }}" autocomplete="off" type="text" data-rework-comment-input/>
<input accept="image/*,video/*" data-rework-comment-media-input multiple name="media[]" type="file" hidden>
<button class="square-icon" data-rework-comment-media-trigger type="button" aria-label="{{ __('ui.preview_comment_add_image') }}"><i aria-hidden="true" class="ph ph-image ph-icon"></i></button>
<button class="square-icon" data-rework-emoji-toggle type="button" aria-label="{{ __('ui.preview_emoji_button') }}"><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></button>
<button class="btn" data-rework-comment-submit type="submit">{{ __('ui.send') }}</button>
<div class="rework-emoji-picker rework-comment-emoji-picker" data-rework-emoji-picker hidden></div>
<div class="rework-comment-media-preview" data-rework-comment-media-preview hidden></div>
<p class="rework-comment-status" data-rework-comment-status hidden></p>
<input name="parent_id" type="hidden" data-rework-comment-parent>
</form>
</div>
</div>
</section>
</div>
<div
    aria-hidden="true"
    class="modal-backdrop rework-report-backdrop"
    data-rework-report-modal=""
    data-label-sending="{{ __('ui.js_i18n_sending') }}"
    data-label-report-failed="{{ __('ui.report_could_not_be_sent') }}"
    data-label-report-success="{{ __('ui.report_success') }}"
    data-label-report-default="{{ __('ui.preview_report_default_label') }}"
    data-label-reported="{{ __('ui.preview_comment_reported_short') }}"
>
<section aria-labelledby="rework-report-title" aria-modal="true" class="post-composer-modal rework-report-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<header class="post-composer-header">
<div class="post-composer-titleblock">
<span class="composer-eyebrow"><span aria-hidden="true" class="composer-dot"></span>{{ __('ui.rework_report_kicker') }}</span>
<h2 id="rework-report-title">{{ __('ui.preview_report_title') }}</h2>
<p data-rework-report-label>{{ __('ui.preview_report_intro') }}</p>
</div>
<button aria-label="{{ __('ui.preview_report_close_aria') }}" class="post-composer-close" data-rework-report-close type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<form action="{{ route('reports.store') }}" class="rework-report-form" data-rework-report-form method="post">
@csrf
<input name="type" type="hidden" data-rework-report-type>
<input name="id" type="hidden" data-rework-report-id>
<div class="post-composer-body rework-report-body">
<label class="rework-report-field">
<span>{{ __('ui.preview_report_reason') }}</span>
<select name="reason" required>
<option value="spam">{{ __('ui.report_reason_spam_title') }}</option>
<option value="abuse">{{ __('ui.preview_report_reason_abuse') }}</option>
<option value="hate">{{ __('ui.report_reason_hate_title') }}</option>
<option value="nsfw">{{ __('ui.preview_report_reason_nsfw') }}</option>
<option value="fraud">{{ __('ui.report_reason_fraud_title') }}</option>
<option value="cheating">{{ __('ui.preview_report_reason_cheating') }}</option>
<option value="privacy">{{ __('ui.report_reason_privacy_title') }}</option>
<option value="other">{{ __('ui.preview_report_reason_other') }}</option>
</select>
</label>
<label class="rework-report-field">
<span>{{ __('ui.preview_report_details_optional') }}</span>
<textarea maxlength="2000" name="body" placeholder="{{ __('ui.preview_report_body_placeholder') }}" rows="4"></textarea>
</label>
<p class="rework-report-status" data-rework-report-status hidden></p>
</div>
<footer class="post-composer-footer">
<button class="composer-cancel" data-rework-report-close type="button">{{ __('ui.preview_action_cancel') }}</button>
<button class="composer-submit" data-rework-report-submit type="submit">{{ __('ui.preview_report_submit_short') }}</button>
</footer>
</form>
</section>
</div>
<div
    aria-hidden="true"
    class="modal-backdrop reactions-backdrop"
    data-reactions-modal=""
    data-label-reaction="{{ __('ui.reaction_like') }}"
    data-label-reactions="{{ __('ui.rework_reactions') }}"
    data-label-no-reactions="{{ __('ui.rework_no_reactions') }}"
    data-label-reactions-failed="{{ __('ui.rework_reactions_failed') }}"
>
<section aria-labelledby="reactions-modal-title" aria-modal="true" class="reactions-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<button aria-label="{{ __('ui.preview_likes_close_aria') }}" class="modal-close post-composer-close" data-reactions-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
<header class="reactions-modal-head">
<div>
<span>Feed</span>
<h2 id="reactions-modal-title">{{ __('ui.rework_reactions') }}</h2>
</div>
<strong data-reactions-total>0 {{ __('ui.rework_reactions') }}</strong>
</header>
<div class="reactions-stats" data-reactions-stats></div>
<div class="reactions-list" data-reactions-list>
<div class="comment-empty-state">{{ __('ui.rework_no_reactions') }}</div>
</div>
</section>
</div>
<div aria-hidden="true" class="modal-backdrop post-composer-backdrop" data-post-composer-modal="">
<form action="{{ route('feed.store') }}" data-rework-post-composer-form enctype="multipart/form-data" id="reworkPostComposerForm" method="post" hidden>
@csrf
<input name="background_style" type="hidden" value="none">
<input data-rework-composer-visibility-input name="visibility" type="hidden" value="public">
<input data-rework-composer-ai-input name="ai_generated" type="hidden" value="0">
<input data-rework-composer-feeling-input name="feeling_key" type="hidden" value="none">
<input accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" data-rework-composer-file-input id="reworkComposerMedia" multiple name="media[]" type="file">
</form>
<section aria-labelledby="post-composer-title" aria-modal="true" class="post-composer-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<header class="post-composer-header">
<div class="post-composer-titleblock">
<span class="composer-eyebrow"><span aria-hidden="true" class="composer-dot"></span>HNT FEED</span>
<h2 id="post-composer-title">Post erstellen</h2>
<p>Teile etwas mit der HNT-Community.</p>
</div>
<button aria-label="Post erstellen schließen" class="post-composer-close" data-post-composer-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<div class="post-composer-body">
<div class="post-composer-author-row">
<div class="post-composer-author">
<img alt="{{ $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter') }}" src="{{ $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}">
<div>
<strong>{{ $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter') }}</strong>
<span>Community · HNT Feed</span>
</div>
</div>
<a class="audience-pill" data-rework-composer-audience href="#">Community <span><i aria-hidden="true" class="ph ph-caret-down ph-icon"></i></span></a>
<div class="rework-composer-audience-menu" data-rework-composer-audience-menu hidden>
<button data-rework-composer-audience-option="public" type="button"><strong>Community</strong><span>Alle eingeloggten Hunter</span></button>
<button data-rework-composer-audience-option="followers" type="button"><strong>Freunde</strong><span>Nur dein Netzwerk</span></button>
<button data-rework-composer-audience-option="private" type="button"><strong>Privat</strong><span>Nur du</span></button>
</div>
</div>
<div class="post-composer-textbox">
<textarea data-rework-composer-textarea form="reworkPostComposerForm" maxlength="5000" name="body" placeholder="Was gibt es Neues im Bayou?"></textarea>
<div class="composer-textbox-footer">
<div aria-hidden="true" class="composer-ghost-actions">
<span></span><span></span><span></span>
</div>
<a aria-label="Emoji hinzufügen" class="composer-emoji" data-rework-composer-emoji href="#"><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></a>
</div>
</div>
<div class="post-composer-tools">
<a data-rework-composer-media-trigger href="#"><span><i aria-hidden="true" class="ph ph-plus ph-icon"></i></span>Medien</a>
<a data-rework-composer-feeling href="#"><span><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></span>Gefühl</a>
<a data-rework-composer-poll href="#"><span><i aria-hidden="true" class="ph ph-question ph-icon"></i></span>Umfrage</a>
<a data-rework-composer-ai-toggle href="#"><span class="composer-check"><i aria-hidden="true" class="ph ph-square ph-icon"></i></span>KI-Inhalt</a>
</div>
<div class="rework-composer-addons" data-rework-composer-addons>
<div class="rework-composer-media-preview" data-rework-composer-media-preview hidden></div>
<div class="rework-composer-selected-feeling" data-rework-composer-feeling-selected hidden></div>
<div class="rework-composer-feeling-panel" data-rework-composer-feeling-panel hidden>
<button data-rework-composer-feeling-option="happy" type="button">😄 Happy</button>
<button data-rework-composer-feeling-option="excited" type="button">🔥 Hype</button>
<button data-rework-composer-feeling-option="focused" type="button">🎯 Fokus</button>
<button data-rework-composer-feeling-option="chill" type="button">😎 Chill</button>
<button data-rework-composer-feeling-option="tired" type="button">💀 Müde</button>
<button data-rework-composer-feeling-option="salty" type="button">🧂 Salty</button>
</div>
<div class="rework-composer-poll-panel" data-rework-composer-poll-panel hidden>
<label><span>Frage</span><input form="reworkPostComposerForm" maxlength="180" name="poll_question" placeholder="Was möchtest du wissen?" type="text"></label>
<label><span>Antwort 1</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 1" type="text"></label>
<label><span>Antwort 2</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 2" type="text"></label>
<label><span>Antwort 3</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 3 optional" type="text"></label>
<label><span>Antwort 4</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 4 optional" type="text"></label>
</div>
</div>
</div>
<footer class="post-composer-footer">
<a class="composer-cancel" data-post-composer-close="" href="#">Abbrechen</a>
<a class="composer-submit" data-rework-composer-submit href="#">Posten</a>
</footer>
</section>
</div>
<div
    aria-hidden="true"
    class="modal-backdrop profile-edit-backdrop"
    data-profile-edit-modal=""
    data-label-saved="{{ __('ui.profile_saved') }}"
    data-label-save-failed="{{ __('ui.profile_save_failed') }}"
    data-label-validation="{{ __('ui.profile_validation_error') }}"
>
<section aria-labelledby="profile-edit-modal-title" aria-modal="true" class="settings-modal profile-edit-modal settings-has-footer-submit" role="dialog">
<header class="settings-modal-header">
<div>
<span class="settings-eyebrow">{{ __('ui.my_profile') }}</span>
<h2 id="profile-edit-modal-title">{{ __('ui.edit_profile') }}</h2>
<p>{{ __('ui.profile_edit_modal_intro') }}</p>
</div>
<button aria-label="{{ __('ui.close') }}" class="settings-close" data-profile-edit-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<nav aria-label="{{ __('ui.edit_profile') }}" class="settings-tabs profile-edit-tabs">
<button class="is-active" data-profile-edit-tab="basic" type="button"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>{{ __('ui.profile_basic_info') }}</span></button>
<button data-profile-edit-tab="game" type="button"><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i><span>{{ __('ui.profile_game_info') }}</span></button>
<button data-profile-edit-tab="social" type="button"><i aria-hidden="true" class="ph ph-link ph-icon"></i><span>{{ __('ui.profile_social_links') }}</span></button>
<button data-profile-edit-tab="media" type="button"><i aria-hidden="true" class="ph ph-image ph-icon"></i><span>{{ __('ui.profile_media') }}</span></button>
</nav>
<form action="{{ route('profile.update') }}" data-profile-edit-form enctype="multipart/form-data" method="post" novalidate>
@csrf
@method('PUT')
<div class="settings-modal-body profile-edit-modal-body">
<div class="profile-edit-summary">
<div class="profile-edit-cover" style="background-image: linear-gradient(180deg, rgba(17,17,15,.08), rgba(17,17,15,.76)), url('{{ $viewerCover }}');" data-profile-edit-cover-preview></div>
<div class="profile-edit-summary-row">
<img alt="{{ $headerName }}" data-profile-edit-avatar-preview data-rework-profile-avatar src="{{ $viewerAvatar }}">
<div>
<strong data-profile-edit-name-preview data-rework-profile-name>{{ old('name', $viewer?->name) ?: $headerName }}</strong>
<span data-profile-edit-headline-preview data-rework-profile-headline>{{ old('headline', $viewerProfile?->headline) ?: __('ui.profile_no_headline') }}</span>
</div>
<em data-profile-edit-completion>{{ $viewerCompletion }}%</em>
</div>
</div>
<p class="settings-status profile-edit-status" data-profile-edit-status hidden></p>
<section class="settings-panel is-active" data-profile-edit-panel="basic">
<div class="settings-section-head">
<span>{{ __('ui.my_profile') }}</span>
<h3>{{ __('ui.profile_basic_info') }}</h3>
<p>{{ __('ui.profile_completion_text') }}</p>
</div>
<div class="settings-form-grid">
<label><span>{{ __('ui.display_name') }}</span><input autocomplete="name" maxlength="80" name="name" required type="text" value="{{ old('name', $viewer?->name) }}"/><small class="settings-field-error" data-profile-edit-error="name"></small></label>
<label><span>{{ __('ui.profile_headline') }}</span><input maxlength="120" name="headline" type="text" value="{{ old('headline', $viewerProfile?->headline) }}"/><small class="settings-field-error" data-profile-edit-error="headline"></small></label>
<label class="profile-edit-wide"><span>{{ __('ui.profile_bio') }}</span><textarea maxlength="1200" name="bio" rows="4">{{ old('bio', $viewerProfile?->bio) }}</textarea><small class="settings-field-error" data-profile-edit-error="bio"></small></label>
<label><span>{{ __('ui.profile_visibility') }}</span><select name="profile_visibility" required><option value="public" @selected($profileVisibility === 'public')>{{ __('ui.visibility_public') }}</option><option value="registered" @selected($profileVisibility === 'registered')>{{ __('ui.visibility_registered') }}</option><option value="private" @selected($profileVisibility === 'private')>{{ __('ui.visibility_private') }}</option></select><small class="settings-field-error" data-profile-edit-error="profile_visibility"></small></label>
</div>
<label class="settings-toggle-row profile-edit-lfg" for="rework-profile-lfg"><input id="rework-profile-lfg" name="is_lfg_available" value="1" @checked($profileIsLfgAvailable) type="checkbox"/><span></span><div><strong>{{ __('ui.profile_lfg_available') }}</strong><p>{{ __('ui.preview_profile_edit_lfg_available_hint') }}</p></div></label>
</section>
<section class="settings-panel" data-profile-edit-panel="game">
<div class="settings-section-head">
<span>Hunt</span>
<h3>{{ __('ui.profile_game_info') }}</h3>
<p>{{ __('ui.preview_profile_edit_hunt_text') }}</p>
</div>
<div class="settings-form-grid">
<label><span>{{ __('ui.platform') }}</span><select name="platform"><option value="">{{ __('ui.platform_open') }}</option>@foreach($reworkProfileOptions['platform'] as $option)<option value="{{ $option }}" @selected($profilePlatform === $option)>{{ $option }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="platform"></small></label>
<label><span>{{ __('ui.playstyle') }}</span><select name="playstyle"><option value="">{{ __('ui.playstyle_open') }}</option>@foreach($reworkProfileOptions['playstyle'] as $value => $label)<option value="{{ $value }}" @selected($profilePlaystyle === $value || $profilePlaystyle === $label)>{{ $label }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="playstyle"></small></label>
<label><span>{{ __('ui.region') }}</span><select name="region"><option value="">{{ __('ui.preview_profile_edit_region_open') }}</option>@foreach($reworkProfileOptions['region'] as $option)<option value="{{ $option }}" @selected($profileRegion === $option)>{{ $option }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="region"></small></label>
<label><span>{{ __('ui.language') }}</span><select name="language"><option value="">{{ __('ui.preview_profile_edit_language_open') }}</option>@foreach($reworkProfileOptions['language'] as $option)<option value="{{ $option }}" @selected($profileLanguage === $option)>{{ $option }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="language"></small></label>
<label><span>{{ __('ui.hunt_role') }}</span><input maxlength="60" name="hunt_role" type="text" value="{{ old('hunt_role', $viewerProfile?->hunt_role) }}"/><small class="settings-field-error" data-profile-edit-error="hunt_role"></small></label>
<label><span>Discord</span><input maxlength="80" name="discord_name" type="text" value="{{ old('discord_name', $viewerProfile?->discord_name) }}"/><small class="settings-field-error" data-profile-edit-error="discord_name"></small></label>
</div>
</section>
<section class="settings-panel" data-profile-edit-panel="social">
<div class="settings-section-head">
<span>{{ __('ui.profile_social_links') }}</span>
<h3>{{ __('ui.profile_social_links') }}</h3>
<p>{{ __('ui.preview_profile_edit_social_text') }}</p>
</div>
<div class="settings-form-grid">
<label><span>Steam URL</span><input maxlength="255" name="steam_url" placeholder="https://steamcommunity.com/id/..." type="url" value="{{ old('steam_url', $viewerProfile?->steam_url) }}"/><small class="settings-field-error" data-profile-edit-error="steam_url"></small></label>
<label><span>Twitch URL</span><input maxlength="255" name="twitch_url" placeholder="https://www.twitch.tv/..." type="url" value="{{ old('twitch_url', $viewerProfile?->twitch_url) }}"/><small class="settings-field-error" data-profile-edit-error="twitch_url"></small></label>
<label><span>YouTube URL</span><input maxlength="255" name="youtube_url" placeholder="https://www.youtube.com/@..." type="url" value="{{ old('youtube_url', $viewerProfile?->youtube_url) }}"/><small class="settings-field-error" data-profile-edit-error="youtube_url"></small></label>
</div>
</section>
<section class="settings-panel" data-profile-edit-panel="media">
<div class="settings-section-head">
<span>{{ __('ui.profile_media') }}</span>
<h3>{{ __('ui.profile_media') }}</h3>
<p>{{ __('ui.profile_upload_hint') }}</p>
</div>
<div class="profile-edit-upload-grid">
<label class="profile-edit-upload" for="rework-profile-avatar"><span>{{ __('ui.profile_avatar') }}</span><img alt="{{ __('ui.profile_avatar') }}" data-profile-edit-avatar-preview src="{{ $viewerAvatar }}"><input accept="image/jpeg,image/png,image/webp" id="rework-profile-avatar" name="avatar" type="file" data-profile-edit-file="avatar"><em>{{ __('ui.avatar_upload_hint') }}</em><small class="settings-field-error" data-profile-edit-error="avatar"></small></label>
<label class="profile-edit-upload" for="rework-profile-cover"><span>{{ __('ui.profile_cover') }}</span><div style="background-image: url('{{ $viewerCover }}');" data-profile-edit-cover-preview></div><input accept="image/jpeg,image/png,image/webp" id="rework-profile-cover" name="cover" type="file" data-profile-edit-file="cover"><em>{{ __('ui.cover_upload_hint') }}</em><small class="settings-field-error" data-profile-edit-error="cover"></small></label>
</div>
</section>
</div>
<footer class="settings-modal-footer">
<button class="settings-cancel" data-profile-edit-modal-close="" type="button">{{ __('ui.cancel') }}</button>
<button class="settings-save" data-profile-edit-submit="" type="submit">{{ __('ui.save_changes') }}</button>
</footer>
</form>
</section>
</div>
<div aria-hidden="{{ $reworkSettingsShouldOpen ? 'false' : 'true' }}" class="modal-backdrop settings-backdrop @if($reworkSettingsShouldOpen) is-open @endif" data-settings-modal="" @if($reworkSettingsShouldOpen) data-settings-modal-autopen="1" @endif>
<section aria-labelledby="settings-modal-title" aria-modal="true" class="settings-modal settings-has-footer-submit" role="dialog">
<header class="settings-modal-header">
<div>
<span class="settings-eyebrow">{{ __('ui.account') }}</span>
<h2 id="settings-modal-title">{{ __('ui.settings') }}</h2>
<p>{{ __('ui.rework_settings_modal_intro') }}</p>
</div>
<button aria-label="{{ __('ui.rework_settings_close_aria') }}" class="settings-close" data-settings-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<nav aria-label="{{ __('ui.rework_settings_tabs_aria') }}" class="settings-tabs">
<button class="@if($reworkSettingsActiveTab === 'notifications') is-active @endif" data-settings-tab="notifications" type="button"><i aria-hidden="true" class="ph ph-bell ph-icon"></i><span>{{ __('ui.notification_settings') }}</span></button>
<button class="@if($reworkSettingsActiveTab === 'privacy') is-active @endif" data-settings-tab="privacy" type="button"><i aria-hidden="true" class="ph ph-shield-check ph-icon"></i><span>{{ __('ui.privacy') }}</span></button>
<button class="@if($reworkSettingsActiveTab === 'blocked') is-active @endif" data-settings-tab="blocked" type="button"><i aria-hidden="true" class="ph ph-prohibit ph-icon"></i><span>{{ __('ui.account_blocked_users') }}</span></button>
<button class="@if($reworkSettingsActiveTab === 'security') is-active @endif" data-settings-tab="security" type="button"><i aria-hidden="true" class="ph ph-lock-key ph-icon"></i><span>{{ __('ui.security') }}</span></button>
</nav>
<div class="settings-modal-body">
@if (session('status') && in_array(session('status'), $reworkSettingsStatusMessages, true))
<div class="settings-status">{{ session('status') }}</div>
@endif
@if ($reworkSettingsHasErrors)
<div class="settings-status is-error">{{ __('ui.profile_validation_error') }}</div>
@endif
<section class="settings-panel @if($reworkSettingsActiveTab === 'notifications') is-active @endif" data-settings-panel="notifications">
<div class="settings-section-head">
<span>Notification Center</span>
<h3>{{ __('ui.notification_settings') }}</h3>
<p>{{ __('ui.notification_settings_intro') }}</p>
</div>
<form action="{{ route('account.settings.update') }}" method="post">
@csrf
@method('PUT')
<div class="settings-toggle-list">
@foreach($reworkNotificationGroups as $field => $meta)
<label class="settings-toggle-row" for="rework-notification-{{ $field }}"><input id="rework-notification-{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $reworkNotificationSettings?->{$field})) type="checkbox"/><span></span><div><strong>{{ $meta['title'] }}</strong><p>{{ $meta['text'] }}</p></div></label>
@endforeach
</div>
<div class="settings-form-actions"><button class="settings-inline-btn" type="submit">{{ __('ui.save_changes') }}</button></div>
</form>
</section>
<section class="settings-panel @if($reworkSettingsActiveTab === 'privacy') is-active @endif" data-settings-panel="privacy">
<div class="settings-section-head">
<span>{{ __('ui.privacy') }}</span>
<h3>{{ __('ui.contact_and_profile_visibility') }}</h3>
<p>{{ __('ui.privacy_settings_intro') }}</p>
</div>
<form action="{{ route('settings.privacy.update') }}" method="post">
@csrf
@method('PUT')
<div class="settings-form-grid">
<label><span>{{ __('ui.profile_visibility') }}</span><select name="profile_visibility"><option value="public" @selected(old('profile_visibility', $reworkPrivacySettings?->profile_visibility) === 'public')>{{ __('ui.visibility_public') }}</option><option value="registered" @selected(old('profile_visibility', $reworkPrivacySettings?->profile_visibility) === 'registered')>{{ __('ui.visibility_registered') }}</option><option value="private" @selected(old('profile_visibility', $reworkPrivacySettings?->profile_visibility) === 'private')>{{ __('ui.visibility_private') }}</option></select>@error('profile_visibility')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
<label><span>{{ __('ui.allow_messages_from') }}</span><select name="allow_messages_from"><option value="everyone" @selected(old('allow_messages_from', $reworkPrivacySettings?->allow_messages_from) === 'everyone')>{{ __('ui.allow_messages_everyone') }}</option><option value="registered" @selected(old('allow_messages_from', $reworkPrivacySettings?->allow_messages_from) === 'registered')>{{ __('ui.allow_messages_registered') }}</option><option value="following" @selected(old('allow_messages_from', $reworkPrivacySettings?->allow_messages_from) === 'following')>{{ __('ui.allow_messages_following') }}</option><option value="nobody" @selected(old('allow_messages_from', $reworkPrivacySettings?->allow_messages_from) === 'nobody')>{{ __('ui.allow_messages_nobody') }}</option></select>@error('allow_messages_from')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
</div>
<div class="settings-toggle-list compact">
@foreach($reworkPrivacyToggles as $field => $meta)
<label class="settings-toggle-row" for="rework-privacy-{{ $field }}"><input id="rework-privacy-{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $reworkPrivacySettings?->{$field})) type="checkbox"/><span></span><div><strong>{{ $meta['title'] }}</strong><p>{{ $meta['text'] }}</p></div></label>
@endforeach
</div>
<div class="settings-form-actions"><button class="settings-inline-btn" type="submit">{{ __('ui.save_changes') }}</button></div>
</form>
</section>
<section class="settings-panel @if($reworkSettingsActiveTab === 'blocked') is-active @endif" data-settings-panel="blocked">
<div class="settings-section-head">
<span>{{ __('ui.privacy') }}</span>
<h3>{{ __('ui.account_blocked_users') }}</h3>
<p>{{ __('ui.blocked_users_privacy_text') }}</p>
</div>
<form action="{{ route('settings.privacy.blocks.store') }}" method="post">
@csrf
<div class="settings-form-grid blocked-form">
<label><span>{{ __('ui.username') }}</span><input name="username" value="{{ old('username') }}" placeholder="z. B. huntername" type="text"/>@error('username')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
<label><span>{{ __('ui.reason_optional') }}</span><input maxlength="120" name="reason" value="{{ old('reason') }}" placeholder="{{ __('ui.rework_settings_block_reason_placeholder') }}" type="text"/>@error('reason')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
<button class="settings-inline-btn" type="submit">{{ __('ui.rework_settings_block_user') }}</button>
</div>
</form>
<div class="blocked-list">
@forelse($reworkBlockedUsers as $block)
<div class="blocked-item"><div><strong>{{ $block->blockedUser?->name ?? __('ui.deleted_user') }}</strong><span>{{ $block->blockedUser?->username ? '@'.$block->blockedUser->username : __('ui.unknown') }}</span>@if($block->reason)<span>{{ $block->reason }}</span>@endif</div><form action="{{ route('settings.privacy.blocks.destroy', $block) }}" method="post">@csrf @method('DELETE')<button type="submit">{{ __('ui.rework_settings_unblock_user') }}</button></form></div>
@empty
<div class="blocked-empty">{{ __('ui.no_blocked_users') }}</div>
@endforelse
</div>
</section>
<section class="settings-panel @if($reworkSettingsActiveTab === 'security') is-active @endif" data-settings-panel="security">
<div class="settings-section-head">
<span>{{ __('ui.security') }}</span>
<h3>{{ __('ui.account_security_info') }}</h3>
<p>{{ __('ui.account_security_banner_text') }}</p>
</div>
<form action="{{ route('settings.security.password') }}" method="post">
@csrf
<div class="settings-form-grid">
<label><span>{{ __('ui.current_password') }}</span><input autocomplete="current-password" name="current_password" type="password"/>@error('current_password')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
<label><span>{{ __('ui.new_password') }}</span><input autocomplete="new-password" name="password" type="password"/>@error('password')<small class="settings-field-error">{{ $message }}</small>@enderror</label>
<label><span>{{ __('ui.confirm_new_password') }}</span><input autocomplete="new-password" name="password_confirmation" type="password"/></label>
</div>
<div class="settings-form-actions"><button class="settings-inline-btn" type="submit">{{ __('ui.change_password_now') }}</button></div>
</form>
<div class="settings-action-grid">
<a href="{{ route('settings.security.index') }}"><i aria-hidden="true" class="ph ph-device-mobile-camera ph-icon"></i><strong>{{ __('ui.two_factor_authentication') }}</strong><span>{{ $reworkTwoFactorEnabled ? __('ui.two_factor_enabled_intro', ['count' => $reworkTwoFactorRecoveryCount]) : __('ui.two_factor_disabled_intro') }}</span></a>
<a href="{{ route('settings.security.export') }}"><i aria-hidden="true" class="ph ph-download-simple ph-icon"></i><strong>{{ __('ui.data_export') }}</strong><span>{{ __('ui.download_data_export') }}</span></a>
<a class="danger" href="{{ route('settings.security.index') }}"><i aria-hidden="true" class="ph ph-warning ph-icon"></i><strong>{{ __('ui.account_deletion') }}</strong><span>{{ $reworkDeletionRequest && $reworkDeletionRequest->isPending() ? __('ui.account_deletion_pending', ['date' => $reworkDeletionRequest->scheduled_for?->format('d.m.Y H:i')]) : __('ui.account_deletion_intro') }}</span></a>
</div>
</section>
</div>
<footer class="settings-modal-footer">
<button class="settings-cancel" data-settings-modal-close="" type="button">{{ __('ui.cancel') }}</button>
<button class="settings-save" data-settings-active-submit="" type="button">{{ __('ui.save_changes') }}</button>
</footer>
</section>
</div>
@include('themes.socialite.partials.chat-tabs')
<script defer src="{{ \App\Support\HntTheme::asset('script.js', 'rework') }}?v={{ $reworkScriptVersion }}"></script>
<script defer src="{{ asset('assets/socialite/js/hnt-socialite-chat-tabs.js') }}?v={{ $socialiteChatTabsVersion }}"></script>
</body>
</html>
