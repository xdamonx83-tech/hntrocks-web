@extends('layouts.app')

@section('title', $team->name.' · ' . __('ui.team_members_title_suffix') . ' · hnt.rocks')

@section('content')
@php
    $isRecruiting = $team->recruitment_status === 'open';
    $isPrivate = $team->visibility === 'private';
    $roleFilter = $filters['role'] ?? 'all';
    $canManageRoles = ($viewerMembership?->role === 'owner');
@endphp

@if (session('status'))
    <div class="hh-toast-stack" aria-live="polite" aria-atomic="true">
        <div class="hh-toast hh-toast-success">
            <i class="hh-toast-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="hh-alert hh-alert-danger">
        <strong>{{ __('ui.please_check') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="hh-team-vikinger hh-team-members-page">
    <div class="profile-header v2 hh-team-profile-header">
        <figure class="profile-header-cover liquid">
            <img src="{{ $team->coverUrl() }}" alt="{{ __('ui.team_cover') }}: {{ $team->name }}">
        </figure>

        <div class="profile-header-info">
            <div class="user-short-description big">
                <a class="user-short-description-avatar user-avatar big no-stats" href="{{ route('teams.show', $team) }}">
                    <div class="user-avatar-border"><div class="hexagon-148-164"></div></div>
                    <div class="user-avatar-content"><div class="hexagon-image-124-136" data-src="{{ $team->avatarUrl() }}"></div></div>
                </a>

                <a class="user-short-description-avatar user-short-description-avatar-mobile user-avatar medium no-stats" href="{{ route('teams.show', $team) }}">
                    <div class="user-avatar-border"><div class="hexagon-120-130"></div></div>
                    <div class="user-avatar-content"><div class="hexagon-image-100-110" data-src="{{ $team->avatarUrl() }}"></div></div>
                </a>

                <p class="user-short-description-title"><a href="{{ route('teams.show', $team) }}">{{ $team->name }}</a></p>
                <p class="user-short-description-text">{{ $team->tagline ?: __('ui.team_default_tagline') }}</p>
            </div>

            <div class="user-stats">
                <div class="user-stat big">
                    <div class="user-stat-icon">
                        <i class="hh-ph-action-icon ph ph-globe" aria-hidden="true"></i>
                    </div>
                    <p class="user-stat-text">{{ $isPrivate ? __('ui.visibility_private') : __('ui.visibility_public') }}</p>
                </div>
                <div class="user-stat big"><p class="user-stat-title">{{ $team->members_count }}</p><p class="user-stat-text">{{ __('ui.team_members') }}</p></div>
                <div class="user-stat big"><p class="user-stat-title">{{ $team->pending_count }}</p><p class="user-stat-text">{{ __('ui.team_requests') }}</p></div>
                <div class="user-stat big"><p class="user-stat-title">{{ $team->recruitmentLabel() }}</p><p class="user-stat-text">{{ __('ui.recruiting') }}</p></div>
            </div>

            <div class="tag-sticker {{ $isRecruiting ? 'hh-team-tag-open' : 'hh-team-tag-closed' }}" title="{{ $team->recruitmentLabel() }}">
                <i class="tag-sticker-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
            </div>

            <div class="profile-header-info-actions hh-team-header-actions">
                <a class="profile-header-info-action button white text-tooltip-tft" href="{{ route('teams.show', $team) }}" title="{{ __('ui.back_to_team') }}" data-title="{{ __('ui.back_to_team') }}">
                    <svg class="icon-back-arrow"><use xlink:href="#svg-back-arrow"></use></svg>
                </a>

                @if ($canManage)
                    <a class="profile-header-info-action button secondary text-tooltip-tft" href="{{ route('teams.manage') }}" title="{{ __('ui.manage_teams') }}" data-title="{{ __('ui.manage_teams') }}">
                        <i class="hh-ph-action-icon ph ph-gear-six" aria-hidden="true"></i>
                    </a>
                    <a class="profile-header-info-action button text-tooltip-tft" href="{{ route('teams.edit', $team) }}" title="{{ __('ui.team_edit') }}" data-title="{{ __('ui.team_edit') }}">
                        <i class="hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                    </a>
                @endif

                @unless ($canManage)
                    <button class="profile-header-info-action button white hh-profile-action-icon-button hh-report-profile-action text-tooltip-tft" type="button" title="{{ __('ui.report_team') }}" data-title="{{ __('ui.report_team') }}" aria-label="{{ __('ui.report_team') }}" data-hh-report-open data-hh-report-type="team" data-hh-report-id="{{ $team->id }}" data-hh-report-label="Team: {{ $team->name }}">
                        <i class="hh-profile-action-icon hh-ph-action-icon ph ph-warning-octagon" aria-hidden="true"></i>
                        <span class="hh-report-button-label">{{ __('ui.report') }}</span>
                    </button>
                @endunless
            </div>
        </div>
    </div>

    <nav class="section-navigation hh-team-section-navigation" aria-label="{{ __('ui.team_section_navigation') }}">
        <div class="section-menu secondary">
            <a class="section-menu-item" href="{{ route('teams.show', $team) }}#team-timeline"><i class="section-menu-item-icon hh-ph-action-icon ph ph-clock-counter-clockwise" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.team_timeline') }}</p></a>
            <a class="section-menu-item" href="{{ route('teams.show', $team) }}#team-info"><i class="section-menu-item-icon hh-ph-action-icon ph ph-info" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.team_info_section') }}</p></a>
            <a class="section-menu-item active" href="{{ route('teams.members', $team) }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-users" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.team_members') }}</p></a>
            <a class="section-menu-item" href="{{ route('teams.show', $team) }}#team-lfg"><i class="section-menu-item-icon hh-ph-action-icon ph ph-trophy" aria-hidden="true"></i><p class="section-menu-item-text">Team-LFG</p></a>
            @if ($canManage)
                <a class="section-menu-item" href="{{ route('teams.show', $team) }}#team-requests"><i class="section-menu-item-icon hh-ph-action-icon ph ph-chats-circle" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.team_requests') }}</p></a>
            @endif
        </div>
    </nav>

    <section class="section hh-team-members-section">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.browse_team') }}</p>
                <h2 class="section-title">{{ __('ui.team_members') }} <span class="highlighted secondary">{{ $team->members_count }}</span></h2>
            </div>
        </div>

        <div class="section-filters-bar v1 hh-team-members-filterbar">
            <div class="section-filters-bar-actions">
                <form class="form hh-team-members-filter" method="get" action="{{ route('teams.members', $team) }}">
                    <div class="form-input small with-button hh-team-members-search-input">
                        <label for="team-members-search">{{ __('ui.team_members_search') }}</label>
                        <input type="text" id="team-members-search" name="q" value="{{ $filters['q'] ?? '' }}">
                        <button class="button primary" type="submit" aria-label="{{ __('ui.search') }}">
                            <i class="hh-ph-action-icon ph ph-magnifying-glass" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="form-select hh-team-members-role-select">
                        <label for="team-members-role">{{ __('ui.role') }}</label>
                        <select id="team-members-role" name="role" onchange="this.form.submit()">
                            <option value="all" @selected($roleFilter === 'all')>{{ __('ui.all_roles') }}</option>
                            <option value="owner" @selected($roleFilter === 'owner')>{{ __('ui.role_owner') }}</option>
                            <option value="officer" @selected($roleFilter === 'officer')>{{ __('ui.role_officer') }}</option>
                            <option value="member" @selected($roleFilter === 'member')>{{ __('ui.role_member') }}</option>
                        </select>
                        <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                    </div>
                </form>

                <div class="filter-tabs hh-team-members-role-tabs">
                    <a class="filter-tab {{ $roleFilter === 'all' ? 'active' : '' }}" href="{{ route('teams.members', array_merge([$team], request()->except(['role', 'page']), ['role' => 'all'])) }}"><p class="filter-tab-text">{{ __('ui.all') }}</p></a>
                    <a class="filter-tab {{ $roleFilter === 'owner' ? 'active' : '' }}" href="{{ route('teams.members', array_merge([$team], request()->except(['role', 'page']), ['role' => 'owner'])) }}"><p class="filter-tab-text">{{ __('ui.role_owner') }}</p></a>
                    <a class="filter-tab {{ $roleFilter === 'officer' ? 'active' : '' }}" href="{{ route('teams.members', array_merge([$team], request()->except(['role', 'page']), ['role' => 'officer'])) }}"><p class="filter-tab-text">{{ __('ui.role_officer') }}</p></a>
                    <a class="filter-tab {{ $roleFilter === 'member' ? 'active' : '' }}" href="{{ route('teams.members', array_merge([$team], request()->except(['role', 'page']), ['role' => 'member'])) }}"><p class="filter-tab-text">{{ __('ui.team_members') }}</p></a>
                </div>
            </div>

            <div class="section-filters-bar-actions">
                <div class="view-actions">
                    <div class="view-action text-tooltip-tft-medium active" data-title="{{ __('ui.big_grid') }}"><svg class="view-action-icon icon-big-grid-view"><use xlink:href="#svg-big-grid-view"></use></svg></div>
                    <div class="view-action text-tooltip-tft-medium" data-title="{{ __('ui.small_grid') }}"><svg class="view-action-icon icon-small-grid-view"><use xlink:href="#svg-small-grid-view"></use></svg></div>
                    <div class="view-action text-tooltip-tft-medium" data-title="{{ __('ui.list') }}"><svg class="view-action-icon icon-list-grid-view"><use xlink:href="#svg-list-grid-view"></use></svg></div>
                </div>
            </div>
        </div>

        @if ($members->isEmpty())
            <div class="widget-box hh-empty-state">
                <p class="widget-box-title">{{ __('ui.team_members_empty_title') }}</p>
                <p class="widget-box-text">{{ __('ui.team_members_empty_text') }}</p>
                <a class="button small secondary" href="{{ route('teams.show', $team) }}">{{ __('ui.back_to_team') }}</a>
            </div>
        @else
            <div class="grid grid-4-4-4 centered hh-team-members-page-grid">
                @foreach ($members as $member)
                    @php
                        $user = $member->user;
                        $profile = $user->profile;
                        $friendship = $friendshipMap->get($user->id);
                        $isOwnCard = auth()->id() === $user->id;
                        $memberUrl = $isOwnCard ? route('profile.show') : route('profile.public', $user);
                        $memberFriendCount = $friendCounts[$user->id] ?? 0;
                        $memberBadges = $user->badges->take(5);
                        $remainingBadges = max(0, (int) ($user->badges_count ?? 0) - $memberBadges->count());
                        $showAddFriend = ! $isOwnCard && (! $friendship || $friendship->isDeclined());
                    @endphp

                    <div class="user-preview hh-team-member-card">
                        <figure class="user-preview-cover liquid">
                            <img src="{{ $user->coverUrl() }}" alt="Cover von {{ $user->username }}">
                        </figure>

                        <div class="user-preview-info">
                            <div class="user-short-description">
                                <a class="user-short-description-avatar user-avatar medium" href="{{ $memberUrl }}">
                                    <div class="user-avatar-border"><div class="hexagon-120-132"></div></div>
                                    <div class="user-avatar-content"><div class="hexagon-image-82-90" data-src="{{ $user->avatarUrl() }}"></div></div>
                                    <div class="user-avatar-progress"><div class="hexagon-progress-100-110"></div></div>
                                    <div class="user-avatar-progress-border"><div class="hexagon-border-100-110"></div></div>
                                    <div class="user-avatar-badge">
                                        <div class="user-avatar-badge-border"><div class="hexagon-32-36"></div></div>
                                        <div class="user-avatar-badge-content"><div class="hexagon-dark-26-28"></div></div>
                                        <p class="user-avatar-badge-text">{{ $user->level ?? 1 }}</p>
                                    </div>
                                </a>

                                <p class="user-short-description-title"><a href="{{ $memberUrl }}">{{ $user->name }}</a></p>
                                <p class="user-short-description-text"><a href="{{ $memberUrl }}">{{ '@'.$user->username }}</a></p>
                                <p class="hh-team-member-role-line">{{ $member->roleLabel() }} · {{ $memberFriendCount }} {{ $memberFriendCount === 1 ? __('ui.friend_singular') : __('ui.friend_plural') }}</p>
                            </div>

                            <div class="badge-list small hh-team-member-badge-list">
                                @forelse ($memberBadges as $badge)
                                    <div class="badge-item text-tooltip-tft" data-title="{{ $badge->name }}">
                                        @if($badge->iconUrl())
                                            <img class="hh-team-member-badge-icon" src="{{ $badge->iconUrl() }}" alt="{{ $badge->name }}">
                                        @else
                                            <span class="hh-team-member-badge-fallback">{{ $badge->icon ?: '◆' }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <div class="badge-item text-tooltip-tft" data-title="{{ __('ui.no_badges') }}">
                                        <img src="{{ asset('assets/vikinger/img/badge/blank-s.png') }}" alt="{{ __('ui.no_badges_alt') }}">
                                    </div>
                                @endforelse

                                @if ($remainingBadges > 0)
                                    <a class="badge-item" href="{{ $memberUrl }}#profile-badges">
                                        <img src="{{ asset('assets/vikinger/img/badge/blank-s.png') }}" alt="{{ __('ui.more_badges_alt') }}">
                                        <p class="badge-item-text">+{{ $remainingBadges }}</p>
                                    </a>
                                @endif
                            </div>

                            <div class="user-preview-stats-slides">
                                <div class="user-preview-stats-slide">
                                    <div class="user-stats">
                                        <div class="user-stat"><p class="user-stat-title">{{ $user->visible_feed_posts_count ?? 0 }}</p><p class="user-stat-text">{{ __('ui.posts') }}</p></div>
                                        <div class="user-stat"><p class="user-stat-title">{{ $memberFriendCount }}</p><p class="user-stat-text">{{ __('ui.friends') }}</p></div>
                                        <div class="user-stat"><p class="user-stat-title">{{ $user->active_teams_count ?? 0 }}</p><p class="user-stat-text">Teams</p></div>
                                    </div>
                                </div>

                                <div class="user-preview-stats-slide hh-team-member-about-slide">
                                    <p class="user-preview-text">{{ $profile?->headline ?: __('ui.no_headline') }}</p>
                                    <div class="hh-team-member-mini-tags">
                                        <span>{{ $profile?->platform ?: __('ui.platform_open') }}</span>
                                        <span>{{ $profile?->playstyle ?: __('ui.playstyle_open') }}</span>
                                        <span>{{ $profile?->region ?: __('ui.region_open') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="user-preview-actions hh-team-member-actions">
                                @if ($isOwnCard)
                                    <a class="button secondary" href="{{ route('profile.show') }}">{{ __('ui.my_profile') }}</a>
                                    <a class="button primary" href="{{ route('profile.edit') }}">{{ __('ui.edit') }}</a>
                                @elseif ($showAddFriend)
                                    <form method="post" action="{{ route('friends.store', $user) }}">
                                        @csrf
                                        <button class="button secondary" type="submit">{{ __('ui.profile_add_friend') }}</button>
                                    </form>
                                    <a class="button primary" href="{{ $memberUrl }}">{{ __('ui.profile') }}</a>
                                @elseif ($friendship?->isPending())
                                    <a class="button secondary" href="{{ $memberUrl }}">{{ __('ui.profile') }}</a>
                                    <span class="button white hh-team-member-state-button">{{ __('ui.team_request_pending') }}</span>
                                @elseif ($friendship?->isAccepted())
                                    <a class="button secondary" href="{{ $memberUrl }}">{{ __('ui.profile') }}</a>
                                    <span class="button white hh-team-member-state-button">{{ __('ui.friends_active') }}</span>
                                @else
                                    <a class="button secondary" href="{{ $memberUrl }}">{{ __('ui.profile') }}</a>
                                @endif
                            </div>

                            @if ($canManageRoles && ! $isOwnCard && $member->role !== 'owner')
                                <div class="hh-team-member-admin-actions" aria-label="{{ __('ui.manage_team_role') }}">
                                    @if ($member->role === 'officer')
                                        <form method="post" action="{{ route('teams.members.demote', [$team, $member]) }}">
                                            @csrf
                                            <button class="button white full" type="submit" title="{{ __('ui.remove_officer') }}">
                                                <i class="button-icon hh-ph-action-icon ph ph-user-minus" aria-hidden="true"></i>
                                                <span>{{ __('ui.remove_officer') }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('teams.members.promote', [$team, $member]) }}">
                                            @csrf
                                            <button class="button secondary full" type="submit" title="{{ __('ui.make_officer') }}">
                                                <svg class="button-icon icon-star"><use xlink:href="#svg-star"></use></svg>
                                                <span>{{ __('ui.make_officer') }}</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($members->hasPages())
                <nav class="section-pager-bar hh-vk-pager hh-team-members-pager" aria-label="{{ __('ui.team_members_pages') }}">
                    <div class="section-pager">
                        @if ($members->onFirstPage())
                            <span class="section-pager-item disabled">{{ __('ui.previous') }}</span>
                        @else
                            <a class="section-pager-item" href="{{ $members->previousPageUrl() }}">{{ __('ui.previous') }}</a>
                        @endif

                        <span class="section-pager-item active">{{ $members->currentPage() }}</span>

                        @if ($members->hasMorePages())
                            <a class="section-pager-item" href="{{ $members->nextPageUrl() }}">{{ __('ui.next') }}</a>
                        @else
                            <span class="section-pager-item disabled">{{ __('ui.next') }}</span>
                        @endif
                    </div>
                </nav>
            @endif
        @endif
    </section>
</section>
@endsection
