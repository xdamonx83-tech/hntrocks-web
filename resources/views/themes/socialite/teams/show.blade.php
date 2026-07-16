@php
    $viewer = auth()->user();
    $activeMembers = $team->members->where('status', 'active')->values();
    $organizers = $activeMembers->filter(fn ($member) => in_array($member->role, ['owner', 'officer'], true))->values();
    $viewerMembership = $viewerMembership ?? null;
    $canManage = (bool) ($canManage ?? false);
    $canPostToTeam = (bool) ($canPostToTeam ?? false);
    $activeTeamSection = in_array($activeTeamSection ?? 'overview', ['overview', 'posts', 'members'], true)
        ? $activeTeamSection
        : 'overview';
    $teamPageMembers = $teamPageMembers ?? null;
    $filters = $filters ?? [];
    $friendCounts = $friendCounts ?? [];
    $friendshipMap = $friendshipMap ?? collect();
    $reportedFeedKeys = $reportedFeedKeys ?? collect();
    $socialitePosts = $teamFeedPosts ?? collect();
    $isPublic = $team->visibility === 'public';
    $isRecruiting = $team->recruitment_status === 'open';
    $createdLabel = $team->created_at
        ? $team->created_at->locale(app()->getLocale())->translatedFormat('F Y')
        : __('hnt_team_detail.unknown');
    $profileUrl = static function ($user): string {
        if (! $user?->username) {
            return route('members.index');
        }

        return (int) $user->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $user);
    };
    $roleLabel = static fn (string $role): string => match ($role) {
        'owner' => __('hnt_team_detail.owner'),
        'officer' => __('hnt_team_detail.officer'),
        default => __('hnt_team_detail.member'),
    };
    $sectionTitle = match ($activeTeamSection) {
        'posts' => __('hnt_team_detail.posts_title'),
        'members' => __('hnt_team_detail.members_title'),
        default => __('hnt_team_detail.overview_title'),
    };
    $sectionIntro = match ($activeTeamSection) {
        'posts' => __('hnt_team_detail.posts_intro'),
        'members' => __('hnt_team_detail.members_intro', ['team' => $team->name]),
        default => __('hnt_team_detail.overview_intro', ['team' => $team->name]),
    };
    $detailCssVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/team-detail.css')) ?: time();
    $detailJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-teams/team-detail.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta content="noindex,nofollow" name="robots"/>
<title>{{ __('hnt_team_detail.title', ['team' => $team->name]) }} · HNT.ROCKS</title>
<meta name="description" content="{{ __('hnt_team_detail.meta_description', ['team' => $team->name]) }}"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/styles.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/styles.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/theme-colors.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/theme-colors.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-comments.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-comments.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-likes.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-likes.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-media-viewer.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-media-viewer.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-compose.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-compose.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/cup-card.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/cup-card.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-teams/team-detail.css') }}?v={{ $detailCssVersion }}" rel="stylesheet"/>
</head>
<body class="hnt-preview-body" data-page="team-detail">
@include('themes.hnt_preview.partials.icons')
<main class="app-shell feed-shell team-detail-page-shell">
@include('themes.hnt_preview.partials.header')
<section class="feed-stage team-detail-stage">
<div class="feed-scroll" id="feedScroll">
<div class="team-detail-frame">
<header class="team-detail-heading">
<div>
<a class="team-detail-back" href="{{ route('teams.index') }}"><svg><use href="#i-arrow"></use></svg>{{ __('hnt_team_detail.back_to_teams') }}</a>
<span>{{ __('hnt_team_detail.eyebrow') }}</span>
<h1>{{ $team->name }}</h1>
<p>{{ $team->tagline ?: __('ui.team_default_tagline') }}</p>
<div class="team-detail-tags">
<span class="is-active"><i></i>{{ __('hnt_team_detail.status_active') }}</span>
<span>{{ $isPublic ? __('hnt_team_detail.visibility_public') : __('hnt_team_detail.visibility_private') }}</span>
<span>{{ $isRecruiting ? __('hnt_team_detail.recruiting_open') : __('hnt_team_detail.recruiting_closed') }}</span>
@if($team->platform)<span>{{ $team->platform }}</span>@endif
@if($team->region)<span>{{ $team->region }}</span>@endif
</div>
</div>
<div class="team-detail-heading-stats">
<article><strong>{{ number_format((int) $team->members_count) }}</strong><span>{{ trans_choice('hnt_team_detail.member_count', (int) $team->members_count, ['count' => (int) $team->members_count]) }}</span></article>
<article><strong>{{ number_format((int) $team->posts_count) }}</strong><span>{{ trans_choice('hnt_team_detail.post_count', (int) $team->posts_count, ['count' => (int) $team->posts_count]) }}</span></article>
@if($canManage)<article><strong>{{ number_format((int) $team->pending_count) }}</strong><span>{{ trans_choice('hnt_team_detail.request_count', (int) $team->pending_count, ['count' => (int) $team->pending_count]) }}</span></article>@endif
</div>
</header>

@if(session('status'))<div class="team-detail-flash is-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="team-detail-flash is-error">{{ $errors->first() }}</div>@endif

<section class="team-detail-workspace">
<aside class="team-detail-left">
<article class="team-detail-identity">
<div class="team-detail-cover">
<img src="{{ $team->coverUrl() }}" alt="{{ __('hnt_team_detail.team_cover_alt', ['team' => $team->name]) }}"/>
</div>
<div class="team-detail-identity-main">
<img class="team-detail-avatar" src="{{ $team->avatarUrl() }}" alt="{{ __('hnt_team_detail.team_avatar_alt', ['team' => $team->name]) }}"/>
<div><strong>{{ $team->name }}</strong><span>{{ __('hnt_team_detail.team_handle', ['slug' => $team->slug]) }}</span></div>
</div>
<p>{{ $team->tagline ?: __('ui.team_default_tagline') }}</p>
<div class="team-detail-avatar-row">
<div>
@foreach($activeMembers->take(5) as $member)
<img src="{{ $member->user->avatarUrl() }}" alt="{{ $member->user->name }}"/>
@endforeach
</div>
<strong>{{ trans_choice('hnt_team_detail.member_count', (int) $team->members_count, ['count' => (int) $team->members_count]) }}</strong>
</div>
<div class="team-detail-primary-actions">
@if($canManage)
<a href="{{ route('teams.edit', $team) }}">{{ __('hnt_team_detail.edit_team') }}</a>
<a href="{{ route('teams.manage') }}">{{ __('hnt_team_detail.manage_members') }}</a>
@elseif(!$viewerMembership && $isRecruiting)
<form method="post" action="{{ route('teams.join', $team) }}">@csrf<button type="submit">{{ __('hnt_team_detail.join_team') }}</button></form>
@elseif($viewerMembership?->status === 'pending')
<span>{{ __('hnt_team_detail.request_pending') }}</span>
@elseif($viewerMembership && $viewerMembership->role !== 'owner')
<form method="post" action="{{ route('teams.leave', $team) }}" data-team-detail-leave-form data-confirm="{{ __('hnt_team_detail.leave_confirm') }}">@csrf<button type="submit">{{ __('hnt_team_detail.leave_team') }}</button></form>
@endif
</div>
</article>

<article class="team-detail-facts">
<header><span>{{ __('hnt_team_detail.facts_title') }}</span></header>
<dl>
<div><dt>{{ __('hnt_team_detail.platform') }}</dt><dd>{{ $team->platform ?: __('hnt_team_detail.unknown') }}</dd></div>
<div><dt>{{ __('hnt_team_detail.region') }}</dt><dd>{{ $team->region ?: __('hnt_team_detail.unknown') }}</dd></div>
<div><dt>{{ __('hnt_team_detail.playstyle') }}</dt><dd>{{ $team->playstyle ?: __('hnt_team_detail.unknown') }}</dd></div>
<div><dt>{{ __('hnt_team_detail.language') }}</dt><dd>{{ $team->language ?: __('hnt_team_detail.unknown') }}</dd></div>
<div><dt>{{ __('hnt_team_detail.created') }}</dt><dd>{{ $createdLabel }}</dd></div>
<div><dt>{{ __('hnt_team_detail.visibility') }}</dt><dd>{{ $isPublic ? __('hnt_team_detail.visibility_public') : __('hnt_team_detail.visibility_private') }}</dd></div>
</dl>
</article>

<nav class="team-detail-quick-nav" aria-label="{{ __('hnt_team_detail.section_navigation') }}">
<a class="{{ $activeTeamSection === 'overview' ? 'is-active' : '' }}" href="{{ route('teams.show', $team) }}"><svg><use href="#i-sliders"></use></svg><span>{{ __('hnt_team_detail.tab_overview') }}</span></a>
<a class="{{ $activeTeamSection === 'posts' ? 'is-active' : '' }}" href="{{ route('teams.info', $team) }}"><svg><use href="#i-comment"></use></svg><span>{{ __('hnt_team_detail.tab_posts') }}</span><i>{{ (int) $team->posts_count }}</i></a>
<a class="{{ $activeTeamSection === 'members' ? 'is-active' : '' }}" href="{{ route('teams.members', $team) }}"><svg><use href="#i-users"></use></svg><span>{{ __('hnt_team_detail.tab_members') }}</span><i>{{ (int) $team->members_count }}</i></a>
</nav>
</aside>

<section class="team-detail-main">
<header class="team-detail-main-head">
<div><span>{{ $team->name }}</span><h2>{{ $sectionTitle }}</h2><p>{{ $sectionIntro }}</p></div>
<nav aria-label="{{ __('hnt_team_detail.section_navigation') }}">
<a class="{{ $activeTeamSection === 'overview' ? 'is-active' : '' }}" href="{{ route('teams.show', $team) }}">{{ __('hnt_team_detail.tab_overview') }}</a>
<a class="{{ $activeTeamSection === 'posts' ? 'is-active' : '' }}" href="{{ route('teams.info', $team) }}">{{ __('hnt_team_detail.tab_posts') }} <i>{{ (int) $team->posts_count }}</i></a>
<a class="{{ $activeTeamSection === 'members' ? 'is-active' : '' }}" href="{{ route('teams.members', $team) }}">{{ __('hnt_team_detail.tab_members') }} <i>{{ (int) $team->members_count }}</i></a>
</nav>
</header>

@if($activeTeamSection === 'overview')
<div class="team-overview-layout">
<article class="team-overview-about">
<span>{{ __('hnt_team_detail.about_title') }}</span>
<h3>{{ $team->name }}</h3>
<p>{!! nl2br(e($team->description ?: __('hnt_team_detail.no_description'))) !!}</p>
</article>
<article class="team-overview-status">
<span>{{ __('hnt_team_detail.recruiting') }}</span>
<strong>{{ $isRecruiting ? __('hnt_team_detail.recruiting_open') : __('hnt_team_detail.recruiting_closed') }}</strong>
<p>{{ $isPublic ? __('hnt_team_detail.visibility_public') : __('hnt_team_detail.private_notice') }}</p>
</article>
<section class="team-overview-data">
@foreach([
    __('hnt_team_detail.platform') => $team->platform,
    __('hnt_team_detail.region') => $team->region,
    __('hnt_team_detail.language') => $team->language,
    __('hnt_team_detail.playstyle') => $team->playstyle,
] as $label => $value)
<article><span>{{ $label }}</span><strong>{{ $value ?: __('hnt_team_detail.unknown') }}</strong></article>
@endforeach
</section>
<article class="team-overview-organizers">
<header><div><span>{{ __('hnt_team_detail.leadership_intro') }}</span><h3>{{ __('hnt_team_detail.leadership_title') }}</h3></div></header>
<div>
@forelse($organizers as $member)
<a href="{{ $profileUrl($member->user) }}"><img src="{{ $member->user->avatarUrl() }}" alt="{{ $member->user->name }}"/><span><strong>{{ $member->user->name }}</strong><small>{{ '@'.$member->user->username }} · {{ $roleLabel($member->role) }}</small></span><svg><use href="#i-arrow"></use></svg></a>
@empty<p>{{ __('hnt_team_detail.organizers_empty') }}</p>@endforelse
</div>
</article>
</div>
@elseif($activeTeamSection === 'posts')
<div class="team-feed-section">
@if($canPostToTeam)
<button class="team-feed-composer-trigger" id="openComposer" type="button" aria-label="{{ __('hnt_team_detail.create_post_aria', ['team' => $team->name]) }}">
<img src="{{ $viewer->avatarUrl() }}" alt="{{ $viewer->name }}"/>
<span><strong>{{ __('hnt_team_detail.create_post') }}</strong><small>{{ __('hnt_team_detail.composer_placeholder', ['team' => $team->name]) }}</small></span>
<svg><use href="#i-plus"></use></svg>
</button>
@endif
<div class="post-list team-feed-list">
@if($socialitePosts->count() === 0)
<article class="team-feed-empty"><span><svg><use href="#i-comment"></use></svg></span><h3>{{ __('hnt_team_detail.feed_empty_title') }}</h3><p>{{ __('hnt_team_detail.feed_empty_text') }}</p></article>
@else
@include('themes.hnt_preview.feed.partials.post-items', ['socialitePosts' => $socialitePosts, 'reportedFeedKeys' => $reportedFeedKeys])
@endif
</div>
@if(method_exists($socialitePosts, 'links'))<div class="team-detail-pagination">{{ $socialitePosts->links() }}</div>@endif
</div>
@else
<div class="team-members-section">
<form class="team-members-filter" method="get" action="{{ route('teams.members', $team) }}">
<label><span>{{ __('hnt_team_detail.member_search') }}</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('hnt_team_detail.member_search_placeholder') }}"/></label>
<label><span>{{ __('hnt_team_detail.role_filter') }}</span><select name="role">
<option value="all" @selected(($filters['role'] ?? 'all') === 'all')>{{ __('hnt_team_detail.all_roles') }}</option>
<option value="owner" @selected(($filters['role'] ?? 'all') === 'owner')>{{ __('hnt_team_detail.owner') }}</option>
<option value="officer" @selected(($filters['role'] ?? 'all') === 'officer')>{{ __('hnt_team_detail.officer') }}</option>
<option value="member" @selected(($filters['role'] ?? 'all') === 'member')>{{ __('hnt_team_detail.member') }}</option>
</select></label>
<button type="submit">{{ __('hnt_team_detail.apply_filter') }}</button>
</form>
<div class="team-members-grid">
@forelse(($teamPageMembers?->getCollection() ?? $activeMembers) as $member)
@php
    $memberUser = $member->user;
    $friendship = $friendshipMap->get((int) $member->user_id);
    $isViewer = (int) $member->user_id === (int) auth()->id();
@endphp
<article>
<header><a href="{{ $profileUrl($memberUser) }}" aria-label="{{ __('hnt_team_detail.profile_action', ['name' => $memberUser->name]) }}"><img src="{{ $memberUser->avatarUrl() }}" alt="{{ $memberUser->name }}"/></a><span>{{ $roleLabel($member->role) }}</span></header>
<h3><a href="{{ $profileUrl($memberUser) }}">{{ $memberUser->name }}</a></h3>
<p>{{ '@'.$memberUser->username }}</p>
<dl>
<div><dt>{{ __('hnt_team_detail.created') }}</dt><dd>{{ $member->joined_at ? $member->joined_at->locale(app()->getLocale())->translatedFormat('d. M Y') : __('hnt_team_detail.member_since_unknown') }}</dd></div>
@if(array_key_exists((int) $member->user_id, $friendCounts))<div><dt>{{ __('ui.friends') }}</dt><dd>{{ trans_choice('hnt_team_detail.mutual_friend_count', (int) $friendCounts[(int) $member->user_id], ['count' => (int) $friendCounts[(int) $member->user_id]]) }}</dd></div>@endif
</dl>
@unless($isViewer)
<div class="team-members-friend-actions">
@if(!$friendship || $friendship->isDeclined())
<form method="post" action="{{ route('friends.store', $memberUser) }}">@csrf<button type="submit"><svg><use href="#i-plus"></use></svg>{{ __('hnt_team_detail.add_friend') }}</button></form>
@elseif($friendship->isPending() && $friendship->isRequester($viewer))
<form method="post" action="{{ route('friends.destroy', $friendship) }}">@csrf @method('DELETE')<button type="submit"><svg><use href="#i-x"></use></svg>{{ __('hnt_team_detail.cancel_friend_request') }}</button></form>
@elseif($friendship->isPending() && $friendship->isRecipient($viewer))
<form method="post" action="{{ route('friends.accept', $friendship) }}">@csrf<button type="submit"><svg><use href="#i-check"></use></svg>{{ __('hnt_team_detail.accept_friend_request') }}</button></form>
<form method="post" action="{{ route('friends.decline', $friendship) }}">@csrf<button type="submit"><svg><use href="#i-x"></use></svg>{{ __('hnt_team_detail.decline_friend_request') }}</button></form>
@endif
</div>
@endunless
<a class="team-members-profile-link" href="{{ $profileUrl($memberUser) }}">{{ __('hnt_team_detail.profile_action', ['name' => $memberUser->name]) }}<svg><use href="#i-arrow"></use></svg></a>
</article>
@empty
<article class="team-members-empty"><h3>{{ __('hnt_team_detail.members_empty_title') }}</h3><p>{{ __('hnt_team_detail.members_empty_text') }}</p></article>
@endforelse
</div>
@if($teamPageMembers)<div class="team-detail-pagination">{{ $teamPageMembers->links() }}</div>@endif
</div>
@endif
</section>

<aside class="team-detail-right">
<article class="team-detail-members-preview">
<header><div><span>{{ trans_choice('hnt_team_detail.member_count', (int) $team->members_count, ['count' => (int) $team->members_count]) }}</span><h2>{{ __('hnt_team_detail.members_preview_title') }}</h2></div><a href="{{ route('teams.members', $team) }}">{{ __('hnt_team_detail.members_preview_action') }}</a></header>
<div>
@forelse($activeMembers->take(6) as $member)
<a href="{{ $profileUrl($member->user) }}"><img src="{{ $member->user->avatarUrl() }}" alt="{{ $member->user->name }}"/><span><strong>{{ $member->user->name }}</strong><small>{{ '@'.$member->user->username }} · {{ $roleLabel($member->role) }}</small></span></a>
@empty<p>{{ __('hnt_team_detail.active_members_empty') }}</p>@endforelse
</div>
</article>

<article class="team-detail-actions">
<header><span>{{ __('hnt_team_detail.more_actions') }}</span></header>
<nav>
<a href="{{ route('teams.members', $team) }}"><svg><use href="#i-users"></use></svg>{{ __('hnt_team_detail.view_members') }}</a>
@if($canManage)
<a href="{{ route('teams.edit', $team) }}"><svg><use href="#i-settings"></use></svg>{{ __('hnt_team_detail.edit_team') }}</a>
<a href="{{ route('teams.invitations') }}"><svg><use href="#i-user"></use></svg>{{ __('hnt_team_detail.manage_requests') }}</a>
@else
<button type="button" data-hnt-report-open data-report-type="team" data-report-id="{{ $team->id }}" data-report-label="{{ __('hnt_team_detail.report_label', ['team' => $team->name]) }}"><svg><use href="#i-more"></use></svg>{{ __('hnt_team_detail.report_team') }}</button>
@endif
</nav>
</article>
</aside>
</section>
</div>
</div>
</section>
<div class="toast" id="toast"></div>
</main>

@auth
@if($canPostToTeam)
@include('themes.hnt_preview.partials.composer-modal', ['composerAction' => route('teams.feed.store', $team), 'composerContext' => $team])
@endif
@include('themes.hnt_preview.partials.post-modal')
@include('themes.hnt_preview.partials.report-modal')
@include('themes.hnt_preview.partials.likes-modal')
@include('themes.hnt_preview.partials.lightbox-modal')
@endauth
<script>
window.HNT_PREVIEW_LOCALE = @json(str_replace('_', '-', app()->getLocale()));
window.HNT_PREVIEW_USER_ID = @json(auth()->id());
window.HNT_PREVIEW_I18N = Object.assign({}, @json(trans('ui')), @json(trans('hnt_preview')));
window.HNT_DASHBOARD_HEADER_ENDPOINT = @json(route('feed.index'));
window.HNT_PREVIEW_LIVE_BADGES = {
    endpoint: @json(route('socialite.header.live-badges')),
    notificationsEndpoint: @json(route('socialite.header.notifications')),
    messagesEndpoint: @json(route('socialite.header.messages')),
    friendRequestsEndpoint: @json(route('socialite.header.friend-requests')),
    interval: 8000,
    shellInterval: 5000,
    chatTabInterval: 4500
};
</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/preview-shell.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/preview-shell.js')) ?: time() }}" defer></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-functionality.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-functionality.js')) ?: time() }}" defer></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-translation.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-translation.js')) ?: time() }}" defer></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/shared-video-player.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/shared-video-player.js')) ?: time() }}" defer></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-moment-preview.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-moment-preview.js')) ?: time() }}" defer></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-teams/team-detail.js') }}?v={{ $detailJsVersion }}" defer></script>
</body>
</html>
