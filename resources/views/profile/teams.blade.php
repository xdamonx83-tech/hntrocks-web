@extends('layouts.app')

@section('title', $profileUser->username . ' ' . __('ui.profile_teams_title_suffix'))

@section('content')
@php
    $profile = $profileUser->profile;
    $profileUrl = $isOwnProfile ? route('profile.show') : route('profile.public', $profileUser);
    $aboutUrl = $isOwnProfile ? route('profile.about') : route('profile.about.public', $profileUser);
    $friendsUrl = $isOwnProfile ? route('profile.friends') : route('profile.friends.public', $profileUser);
    $badgesUrl = $isOwnProfile ? route('profile.badges') : route('profile.badges.public', $profileUser);
    $teamsUrl = $isOwnProfile ? route('profile.teams') : route('profile.teams.public', $profileUser);
    $socialCount = collect([$profile?->discord_name, $profile?->twitch_url, $profile?->youtube_url, $profile?->steam_url])->filter()->count();
    $viewer = auth()->user();
@endphp

@if (session('status'))
    <div class="hh-toast-stack" aria-live="polite" aria-atomic="true">
        <div class="hh-toast hh-toast-success">
            <i class="hh-toast-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    </div>
@endif

<section class="hh-profile-vikinger hh-profile-teams-page">
    <div class="profile-header">
        <figure class="profile-header-cover liquid">
            <img src="{{ $profileUser->coverUrl() }}" alt="{{ __('ui.profile_cover_alt', ['name' => $profileUser->username]) }}">
        </figure>

        <div class="profile-header-info">
            <div class="user-short-description big">
                <a class="user-short-description-avatar user-avatar big" href="{{ $profileUrl }}">
                    <div class="user-avatar-border"><div class="hexagon-148-164"></div></div>
                    <div class="user-avatar-content"><div class="hexagon-image-100-110" data-src="{{ $profileUser->avatarUrl() }}"></div></div>
                    <div class="user-avatar-progress"><div class="hexagon-progress-124-136"></div></div>
                    <div class="user-avatar-progress-border"><div class="hexagon-border-124-136"></div></div>
                    <div class="user-avatar-badge">
                        <div class="user-avatar-badge-border"><div class="hexagon-40-44"></div></div>
                        <div class="user-avatar-badge-content"><div class="hexagon-dark-32-34"></div></div>
                        <p class="user-avatar-badge-text">{{ $profileUser->level ?? 1 }}</p>
                    </div>
                </a>

                <a class="user-short-description-avatar user-short-description-avatar-mobile user-avatar medium" href="{{ $profileUrl }}">
                    <div class="user-avatar-border"><div class="hexagon-120-132"></div></div>
                    <div class="user-avatar-content"><div class="hexagon-image-82-90" data-src="{{ $profileUser->avatarUrl() }}"></div></div>
                    <div class="user-avatar-progress"><div class="hexagon-progress-100-110"></div></div>
                    <div class="user-avatar-progress-border"><div class="hexagon-border-100-110"></div></div>
                    <div class="user-avatar-badge">
                        <div class="user-avatar-badge-border"><div class="hexagon-32-36"></div></div>
                        <div class="user-avatar-badge-content"><div class="hexagon-dark-26-28"></div></div>
                        <p class="user-avatar-badge-text">{{ $profileUser->level ?? 1 }}</p>
                    </div>
                </a>

                <p class="user-short-description-title"><a href="{{ $profileUrl }}">{{ $profileUser->name }}</a></p>
                <p class="user-short-description-text">
                    <a href="{{ $profileUrl }}">{{ '@'.$profileUser->username }}</a>
                    @if ($profile?->headline)
                        <span class="hh-profile-headline-separator">·</span>
                        <span>{{ $profile->headline }}</span>
                    @endif
                </p>
            </div>

            <div class="profile-header-social-links-wrap">
                <div class="profile-header-social-links hh-profile-social-links-static">
                    @if ($profile?->twitch_url)
                        <div class="profile-header-social-link"><a class="social-link twitch" href="{{ $profile->twitch_url }}" rel="nofollow noopener" target="_blank" aria-label="Twitch"><i class="hh-ph-brand-icon ph ph-twitch-logo" aria-hidden="true"></i></a></div>
                    @endif
                    @if ($profile?->youtube_url)
                        <div class="profile-header-social-link"><a class="social-link youtube" href="{{ $profile->youtube_url }}" rel="nofollow noopener" target="_blank" aria-label="YouTube"><i class="hh-ph-brand-icon ph ph-youtube-logo" aria-hidden="true"></i></a></div>
                    @endif
                    @if ($profile?->discord_name)
                        <div class="profile-header-social-link"><a class="social-link discord" href="{{ $profileUrl }}#profile-contact" aria-label="Discord"><i class="hh-ph-brand-icon ph ph-discord-logo" aria-hidden="true"></i></a></div>
                    @endif
                    @if ($socialCount === 0)
                        <p class="hh-profile-empty-social">{{ __('ui.profile_no_social_links') }}</p>
                    @endif
                </div>
            </div>

            <div class="user-stats">
                <div class="user-stat big"><p class="user-stat-title">{{ $profileUser->visible_feed_posts_count ?? 0 }}</p><p class="user-stat-text">{{ __('ui.profile_posts_stat') }}</p></div>
                <div class="user-stat big"><p class="user-stat-title">{{ $profileUser->active_teams_count ?? 0 }}</p><p class="user-stat-text">{{ __('ui.profile_teams_title') }}</p></div>
                <div class="user-stat big"><p class="user-stat-title">{{ $profileUser->badges_count ?? 0 }}</p><p class="user-stat-text">{{ __('ui.profile_badges_stat') }}</p></div>
                <div class="user-stat big"><p class="user-stat-title">{{ $profileFriendsCount ?? 0 }}</p><p class="user-stat-text">{{ __('ui.friends') }}</p></div>
            </div>

            <div class="profile-header-info-actions hh-profile-friend-actions">
                @if ($isOwnProfile)
                    <a class="profile-header-info-action button secondary" href="{{ route('profile.edit') }}">{{ __('ui.profile_edit') }}</a>
                    <a class="profile-header-info-action button primary" href="{{ route('feed.index') }}">{{ __('ui.profile_to_feed') }}</a>
                @else
                    <div class="hh-profile-action-cluster">
                        @if (! $friendship || $friendship->isDeclined())
                            <form method="post" action="{{ route('friends.store', $profileUser) }}">
                                @csrf
                                <button class="profile-header-info-action button secondary hh-profile-action-main" type="submit">{{ __('ui.profile_add_friend') }}</button>
                            </form>
                        @elseif ($friendship->isPending() && $friendship->isRequester($viewer))
                            <form method="post" action="{{ route('friends.destroy', $friendship) }}" onsubmit="return confirm('{{ __('ui.profile_friend_request_withdraw_confirm') }}');">
                                @csrf
                                @method('DELETE')
                                <button class="profile-header-info-action button secondary hh-profile-action-main hh-profile-action-state" type="submit" title="{{ __('ui.profile_friend_request_withdraw') }}">
                                    <i class="hh-profile-action-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
                                    {{ __('ui.profile_friend_request_sent') }}
                                </button>
                            </form>
                        @elseif ($friendship->isPending() && $friendship->isRecipient($viewer))
                            <form method="post" action="{{ route('friends.accept', $friendship) }}">
                                @csrf
                                <button class="profile-header-info-action button secondary hh-profile-action-icon-button" type="submit" title="{{ __('ui.profile_friend_request_accept') }}" aria-label="{{ __('ui.profile_friend_request_accept') }}">
                                    <i class="hh-profile-action-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
                                </button>
                            </form>
                            <form method="post" action="{{ route('friends.decline', $friendship) }}">
                                @csrf
                                <button class="profile-header-info-action button white hh-profile-action-icon-button" type="submit" title="{{ __('ui.profile_friend_request_decline') }}" aria-label="{{ __('ui.profile_friend_request_decline') }}">
                                    <i class="hh-profile-action-icon hh-ph-action-icon ph ph-x" aria-hidden="true"></i>
                                </button>
                            </form>
                        @elseif ($friendship->isAccepted())
                            <form method="post" action="{{ route('friends.destroy', $friendship) }}" onsubmit="return confirm('{{ __('ui.profile_friend_remove_confirm') }}');">
                                @csrf
                                @method('DELETE')
                                <button class="profile-header-info-action button secondary hh-profile-action-main hh-profile-action-state" type="submit" title="{{ __('ui.profile_friend_remove') }}">
                                    <i class="hh-profile-action-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
                                    {{ __('ui.friends') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    <a class="profile-header-info-action button primary hh-profile-message-action" href="{{ route('messages.with-user', $profileUser) }}" data-hh-profile-message-drawer data-recipient-username="{{ $profileUser->username }}">{{ __('ui.profile_message') }}</a>
                    <button class="profile-header-info-action button white hh-profile-action-icon-button hh-report-profile-action text-tooltip-tft" type="button" title="{{ __('ui.profile_report') }}" data-title="{{ __('ui.profile_report') }}" aria-label="{{ __('ui.profile_report') }}" data-hh-report-open data-hh-report-type="user" data-hh-report-id="{{ $profileUser->id }}" data-hh-report-label="{{ __('ui.profile_report_label', ['name' => $profileUser->name]) }}">
                        <i class="hh-profile-action-icon hh-ph-action-icon ph ph-warning-octagon" aria-hidden="true"></i>
                        <span class="hh-report-button-label">{{ __('ui.profile_report_button') }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <nav class="section-navigation hh-profile-section-navigation" aria-label="{{ __('ui.profile_sections_aria') }}">
        <div class="section-menu">
            <a class="section-menu-item" href="{{ $aboutUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-user" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_section_info') }}</p></a>
            <a class="section-menu-item" href="{{ $profileUrl }}#profile-timeline"><i class="section-menu-item-icon hh-ph-action-icon ph ph-clock-counter-clockwise" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_section_timeline') }}</p></a>
            <a class="section-menu-item" href="{{ $friendsUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-user" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.friends') }}</p></a>
            <a class="section-menu-item" href="{{ $badgesUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-medal" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_badges_stat') }}</p></a>
            <a class="section-menu-item active" href="{{ $teamsUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_teams_title') }}</p></a>
            <a class="section-menu-item" href="{{ $profileUrl }}#profile-contact"><i class="section-menu-item-icon hh-ph-action-icon ph ph-chats-circle" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_section_contact') }}</p></a>
        </div>
    </nav>

    <section class="section hh-profile-teams-section">
        @php
            $activeSort = $filters['sort'] ?? 'newest';
            $baseFilterParams = request()->except(['page', 'sort']);
            $formAction = $isOwnProfile ? route('profile.teams') : route('profile.teams.public', $profileUser);
            $sortUrl = function (string $sort) use ($isOwnProfile, $profileUser, $baseFilterParams) {
                $params = array_merge($baseFilterParams, ['sort' => $sort]);

                return $isOwnProfile
                    ? route('profile.teams', $params)
                    : route('profile.teams.public', array_merge(['user' => $profileUser], $params));
            };
        @endphp

        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.profile_teams_of', ['name' => $profileUser->name]) }}</p>
                <h2 class="section-title">Teams <span class="highlighted">{{ $profileTeams->total() }}</span></h2>
            </div>
        </div>

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

        <div class="section-filters-bar v1 hh-profile-teams-filterbar">
            <div class="section-filters-bar-actions">
                <form class="form hh-profile-teams-filter" method="GET" action="{{ $formAction }}">
                    <div class="form-input small with-button hh-profile-teams-search-input">
                        <label for="profile-teams-search">{{ __('ui.profile_teams_search') }}</label>
                        <input type="text" id="profile-teams-search" name="q" value="{{ $filters['q'] ?? '' }}">
                        <button class="button primary" type="submit" aria-label="{{ __('ui.search') }}">
                            <i class="hh-ph-action-icon ph ph-magnifying-glass" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="form-select hh-profile-teams-sort-select">
                        <label for="profile-teams-sort">Sortieren</label>
                        <select id="profile-teams-sort" name="sort" onchange="this.form.submit()">
                            <option value="newest" @selected($activeSort === 'newest')>Zuletzt beigetreten</option>
                            <option value="members" @selected($activeSort === 'members')>Meiste Mitglieder</option>
                            <option value="name" @selected($activeSort === 'name')>Alphabetisch</option>
                        </select>
                        <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                    </div>
                </form>

                <div class="filter-tabs hh-profile-team-sort-tabs">
                    <a class="filter-tab {{ $activeSort === 'newest' ? 'active' : '' }}" href="{{ $sortUrl('newest') }}">
                        <p class="filter-tab-text">Neueste</p>
                    </a>
                    <a class="filter-tab {{ $activeSort === 'members' ? 'active' : '' }}" href="{{ $sortUrl('members') }}">
                        <p class="filter-tab-text">Mitglieder</p>
                    </a>
                    <a class="filter-tab {{ $activeSort === 'name' ? 'active' : '' }}" href="{{ $sortUrl('name') }}">
                        <p class="filter-tab-text">A–Z</p>
                    </a>
                </div>
            </div>

            <div class="section-filters-bar-actions hh-profile-teams-summary">
                <p class="section-filters-bar-title">{{ __('ui.profile_active_team_memberships') }}</p>
                <p class="section-filters-bar-text">
                    @if ($profileTeams->total() > 0)
                        <span class="bold">{{ $profileTeams->firstItem() ?? 0 }}–{{ $profileTeams->lastItem() ?? 0 }}</span>
                        <span>&nbsp;von&nbsp;</span>
                        <span class="highlighted">{{ $profileTeams->total() }}</span>
                        <span>&nbsp;{{ __('ui.profile_teams_visible') }}</span>
                    @else
                        {{ __('ui.profile_no_teams_visible') }}
                    @endif
                </p>
            </div>
        </div>

        @if ($profileTeams->count() > 0)
            <div class="grid grid-4-4-4 centered hh-profile-teams-grid">
                @foreach ($profileTeams as $team)
                    @php
                        $teamUrl = route('teams.show', $team);
                        $memberCount = (int) ($team->members_count ?? $team->activeMembers()->count());
                        $postsCount = (int) ($team->posts_count ?? 0);
                        $openLfgCount = (int) ($team->open_lfg_posts_count ?? 0);
                        $isPrivate = $team->visibility === 'private';
                        $isRecruiting = $team->recruitment_status === 'open';
                        $activeMembers = $team->relationLoaded('activeMembers') ? $team->activeMembers->take(6) : collect();
                        $visibleMembers = $activeMembers->take(5);
                        $remainingMembers = max(0, $memberCount - $visibleMembers->count());
                        $viewerMembership = $viewerTeamMemberships->get((int) $team->id);
                        $profileRoleLabel = match ($team->pivot?->role) {
                            'owner' => 'Owner',
                            'officer' => 'Officer',
                            default => 'Mitglied',
                        };
                    @endphp

                    <div class="user-preview hh-profile-team-card">
                        <figure class="user-preview-cover liquid">
                            <img src="{{ $team->coverUrl() }}" alt="Cover von {{ $team->name }}">
                        </figure>

                        <div class="user-preview-info">
                            <div class="tag-sticker {{ $isPrivate ? 'hh-team-visibility-private' : 'hh-team-visibility-public' }}" title="{{ $team->visibilityLabel() }}">
                                <svg class="tag-sticker-icon {{ $isPrivate ? 'icon-private' : 'icon-public' }}">
                                    <use xlink:href="{{ $isPrivate ? '#svg-private' : '#svg-public' }}"></use>
                                </svg>
                            </div>

                            <div class="user-short-description">
                                <a class="user-short-description-avatar user-avatar medium no-stats" href="{{ $teamUrl }}">
                                    <div class="user-avatar-border"><div class="hexagon-120-130"></div></div>
                                    <div class="user-avatar-content"><div class="hexagon-image-100-110" data-src="{{ $team->avatarUrl() }}"></div></div>
                                </a>

                                <p class="user-short-description-title"><a href="{{ $teamUrl }}">{{ $team->name }}</a></p>
                                <p class="user-short-description-text">{{ $team->tagline ?: 'hnt.rocks Team' }}</p>
                            </div>

                            <div class="user-stats hh-profile-team-card-stats">
                                <div class="user-stat"><p class="user-stat-title">{{ $memberCount }}</p><p class="user-stat-text">Mitglieder</p></div>
                                <div class="user-stat"><p class="user-stat-title">{{ $postsCount }}</p><p class="user-stat-text">{{ __('ui.profile_posts_stat') }}</p></div>
                                <div class="user-stat"><p class="user-stat-title">{{ $openLfgCount }}</p><p class="user-stat-text">LFG</p></div>
                            </div>

                            <div class="hh-profile-team-card-meta">
                                <span>{{ $profileRoleLabel }}</span>
                                <span>{{ $team->visibilityLabel() }}</span>
                                <span>{{ $team->recruitmentLabel() }}</span>
                                @foreach (array_filter([$team->platform, $team->playstyle, $team->region, $team->language]) as $tag)
                                    <span>{{ $tag }}</span>
                                @endforeach
                            </div>

                            <p class="hh-profile-team-description">
                                {{ $team->description ? \Illuminate\Support\Str::limit($team->description, 105) : __('ui.profile_team_no_description') }}
                            </p>

                            <div class="user-avatar-list medium reverse centered hh-profile-team-avatar-list">
                                @forelse ($visibleMembers as $member)
                                    @php($memberUser = $member->user)
                                    <a class="user-avatar smaller no-stats" href="{{ $memberUser ? route('profile.public', $memberUser) : $teamUrl }}" title="{{ $memberUser?->name }}">
                                        <div class="user-avatar-border"><div class="hexagon-34-36"></div></div>
                                        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $memberUser?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg') }}"></div></div>
                                    </a>
                                @empty
                                    <div class="user-avatar smaller no-stats hh-profile-team-empty-avatar">
                                        <div class="user-avatar-border"><div class="hexagon-34-36"></div></div>
                                        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ asset('assets/vikinger/img/default-avatar.svg') }}"></div></div>
                                    </div>
                                @endforelse

                                @if ($remainingMembers > 0)
                                    <a class="user-avatar smaller no-stats" href="{{ route('teams.members', $team) }}" title="{{ __('ui.team_members') }}">
                                        <div class="user-avatar-border"><div class="hexagon-34-36"></div></div>
                                        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $team->avatarUrl() }}"></div></div>
                                        <div class="user-avatar-overlay"><div class="hexagon-overlay-30-32"></div></div>
                                        <div class="user-avatar-overlay-content"><p class="user-avatar-overlay-content-text">+{{ $remainingMembers }}</p></div>
                                    </a>
                                @endif
                            </div>

                            <div class="user-preview-actions hh-profile-team-card-actions">
                                <a class="button secondary full" href="{{ $teamUrl }}">
                                    <i class="button-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                                    {{ __('ui.profile_view_team') }}
                                </a>

                                @if (! $viewerMembership && $isRecruiting && ! $isPrivate)
                                    <form method="post" action="{{ route('teams.join', $team) }}">
                                        @csrf
                                        <button class="button primary full" type="submit">
                                            <i class="button-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                                            Beitreten
                                        </button>
                                    </form>
                                @elseif ($viewerMembership && $viewerMembership->status === 'pending')
                                    <button class="button white full" type="button" disabled>
                                        <i class="button-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
                                        {{ __('ui.profile_request_sent') }}
                                    </button>
                                @elseif ($viewerMembership && $viewerMembership->status === 'active')
                                    <span class="button white full hh-profile-team-state">
                                        <i class="button-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
                                        Mitglied
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($profileTeams->hasPages())
                <nav class="hh-pagination hh-profile-teams-pagination" aria-label="{{ __('ui.profile_teams_navigation') }}">
                    @if ($profileTeams->onFirstPage())
                        <span class="is-disabled">{{ __('ui.pagination_previous') }}</span>
                    @else
                        <a href="{{ $profileTeams->previousPageUrl() }}">{{ __('ui.pagination_previous') }}</a>
                    @endif

                    <span>{{ __('ui.profile_page_x_of_y', ['current' => $profileTeams->currentPage(), 'last' => $profileTeams->lastPage()]) }}</span>

                    @if ($profileTeams->hasMorePages())
                        <a href="{{ $profileTeams->nextPageUrl() }}">{{ __('ui.pagination_next') }}</a>
                    @else
                        <span class="is-disabled">{{ __('ui.pagination_next') }}</span>
                    @endif
                </nav>
            @endif
        @else
            <div class="widget-box hh-profile-teams-empty">
                <p class="widget-box-title">{{ __('ui.profile_no_teams_visible') }}</p>
                <p class="widget-box-text">
                    @if ($filters['q'] ?? false)
                        {{ __('ui.profile_no_team_results') }}
                    @else
                        {{ __('ui.profile_no_teams_empty_text') }}
                    @endif
                </p>
                @if ($isOwnProfile)
                    <a class="widget-box-button button small secondary" href="{{ route('teams.index') }}">{{ __('ui.discover_teams') }}</a>
                @endif
            </div>
        @endif
    </section>
</section>
@endsection
