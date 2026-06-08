@extends('layouts.app')

@section('title', __('ui.members_title'))

@section('content')
<div class="section-banner hh-members-section-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/members-icon.png') }}" alt="{{ __('ui.members') }}">
    <p class="section-banner-title">{{ __('ui.members_banner_title', ['count' => $members->total()]) }}</p>
    <p class="section-banner-text">{{ __('ui.members_banner_text') }}</p>
</div>

@if (session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
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

<div class="section-filters-bar v1 hh-members-filterbar">
    <div class="section-filters-bar-actions">
        <form class="form hh-members-vikinger-filter" method="GET" action="{{ route('members.index') }}">
            <input type="hidden" name="relationship" value="{{ $filters['relationship'] ?? 'all' }}">

            <div class="form-input small with-button hh-members-search-input">
                <label for="members-search">{{ __('ui.members_search_label') }}</label>
                <input type="text" id="members-search" name="q" value="{{ $filters['q'] ?? '' }}">
                <button class="button primary" type="submit" aria-label="{{ __('ui.search') }}">
                    <i class="hh-ph-action-icon ph ph-magnifying-glass" aria-hidden="true"></i>
                </button>
            </div>

            <div class="form-select hh-members-filter-select">
                <label for="members-platform">{{ __('ui.platform') }}</label>
                <select id="members-platform" name="platform" onchange="this.form.submit()">
                    <option value="">{{ __('ui.members_platform_all') }}</option>
                    @foreach ($filterOptions['platforms'] as $option)
                        <option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
            </div>

            <div class="form-select hh-members-filter-select">
                <label for="members-playstyle">{{ __('ui.playstyle') }}</label>
                <select id="members-playstyle" name="playstyle" onchange="this.form.submit()">
                    <option value="">{{ __('ui.members_all_playstyles') }}</option>
                    @foreach ($filterOptions['playstyles'] as $option)
                        <option value="{{ $option }}" @selected(($filters['playstyle'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
            </div>

            <label class="hh-members-lfg-toggle">
                <input type="checkbox" name="lfg" value="1" @checked(($filters['lfg'] ?? '') === '1') onchange="this.form.submit()">
                <span>{{ __('ui.members_lfg_only') }}</span>
            </label>
        </form>

        <div class="filter-tabs hh-member-relation-tabs">
            <a class="filter-tab {{ ($filters['relationship'] ?? 'all') === 'all' ? 'active' : '' }}" href="{{ route('members.index', array_merge(request()->except(['relationship', 'page']), ['relationship' => 'all'])) }}">
                <p class="filter-tab-text">{{ __('ui.members_all_players') }}</p>
            </a>
            <a class="filter-tab {{ ($filters['relationship'] ?? '') === 'friends' ? 'active' : '' }}" href="{{ route('members.index', array_merge(request()->except(['relationship', 'page']), ['relationship' => 'friends'])) }}">
                <p class="filter-tab-text">{{ __('ui.friends') }} {{ $relationshipCounts['friends'] ? '(' . $relationshipCounts['friends'] . ')' : '' }}</p>
            </a>
            <a class="filter-tab {{ ($filters['relationship'] ?? '') === 'pending' ? 'active' : '' }}" href="{{ route('members.index', array_merge(request()->except(['relationship', 'page']), ['relationship' => 'pending'])) }}">
                <p class="filter-tab-text">{{ __('ui.members_requests') }} {{ $relationshipCounts['pending'] ? '(' . $relationshipCounts['pending'] . ')' : '' }}</p>
            </a>
        </div>
    </div>

    <div class="section-filters-bar-actions">
        <div class="view-actions">
            <div class="view-action text-tooltip-tft-medium active" data-title="Big Grid"><svg class="view-action-icon icon-big-grid-view"><use xlink:href="#svg-big-grid-view"></use></svg></div>
            <div class="view-action text-tooltip-tft-medium" data-title="Small Grid"><svg class="view-action-icon icon-small-grid-view"><use xlink:href="#svg-small-grid-view"></use></svg></div>
            <div class="view-action text-tooltip-tft-medium" data-title="List"><svg class="view-action-icon icon-list-grid-view"><use xlink:href="#svg-list-grid-view"></use></svg></div>
        </div>
    </div>
</div>

@if ($members->isEmpty())
    <div class="widget-box hh-empty-state">
        <p class="widget-box-title">{{ __('ui.members_empty_title') }}</p>
        <p class="widget-box-text">{{ __('ui.members_empty_text') }}</p>
    </div>
@else
    <div class="grid grid-4-4-4 centered hh-members-vikinger-grid">
        @foreach ($members as $member)
            @php
                $profile = $member->profile;
                $friendship = $friendshipMap->get($member->id);
                $isOwnCard = auth()->id() === $member->id;
                $memberUrl = $isOwnCard ? route('profile.show') : route('profile.public', $member);
                $memberFriendCount = $friendCounts[$member->id] ?? 0;
                $memberBadges = $member->badges->take(5);
                $remainingBadges = max(0, (int) ($member->badges_count ?? 0) - $memberBadges->count());
            @endphp

            <div class="user-preview hh-member-user-preview">
                <figure class="user-preview-cover liquid">
                    <img src="{{ $member->coverUrl() }}" alt="{{ __('ui.profile_cover_alt', ['name' => $member->username]) }}">
                </figure>

                <div class="user-preview-info">
                    <div class="user-short-description">
                        <a class="user-short-description-avatar user-avatar medium" href="{{ $memberUrl }}">
                            <div class="user-avatar-border"><div class="hexagon-120-132"></div></div>
                            <div class="user-avatar-content"><div class="hexagon-image-82-90" data-src="{{ $member->avatarUrl() }}"></div></div>
                            <div class="user-avatar-progress"><div class="hexagon-progress-100-110"></div></div>
                            <div class="user-avatar-progress-border"><div class="hexagon-border-100-110"></div></div>
                            <div class="user-avatar-badge">
                                <div class="user-avatar-badge-border"><div class="hexagon-32-36"></div></div>
                                <div class="user-avatar-badge-content"><div class="hexagon-dark-26-28"></div></div>
                                <p class="user-avatar-badge-text">{{ $member->level ?? 1 }}</p>
                            </div>
                        </a>

                        <p class="user-short-description-title"><a href="{{ $memberUrl }}">{{ $member->name }}</a></p>
                        <p class="user-short-description-text"><a href="{{ $memberUrl }}">{{ '@'.$member->username }}</a></p>
                    </div>

                    <div class="badge-list small hh-member-badge-list">
                        @forelse ($memberBadges as $badge)
                            <div class="badge-item text-tooltip-tft" data-title="{{ $badge->name }}">
                                @if($badge->iconUrl())
                                    <img class="hh-member-badge-icon" src="{{ $badge->iconUrl() }}" alt="{{ $badge->name }}">
                                @else
                                    <span class="hh-member-badge-fallback">{{ $badge->icon ?: '◆' }}</span>
                                @endif
                            </div>
                        @empty
                            <div class="badge-item text-tooltip-tft" data-title="{{ __('ui.members_no_badges') }}">
                                <img src="{{ asset('assets/vikinger/img/badge/blank-s.png') }}" alt="{{ __('ui.members_no_badges_alt') }}">
                            </div>
                        @endforelse

                        @if ($remainingBadges > 0)
                            <a class="badge-item" href="{{ $memberUrl }}#profile-badges">
                                <img src="{{ asset('assets/vikinger/img/badge/blank-s.png') }}" alt="{{ __('ui.members_more_badges') }}">
                                <p class="badge-item-text">+{{ $remainingBadges }}</p>
                            </a>
                        @endif
                    </div>

                    <div class="user-preview-stats-slides">
                        <div class="user-preview-stats-slide">
                            <div class="user-stats">
                                <div class="user-stat"><p class="user-stat-title">{{ $member->visible_feed_posts_count ?? 0 }}</p><p class="user-stat-text">{{ __('ui.profile_posts_stat') }}</p></div>
                                <div class="user-stat"><p class="user-stat-title">{{ $memberFriendCount }}</p><p class="user-stat-text">{{ __('ui.friends') }}</p></div>
                                <div class="user-stat"><p class="user-stat-title">{{ $member->active_teams_count ?? 0 }}</p><p class="user-stat-text">{{ __('ui.profile_teams_title') }}</p></div>
                            </div>
                        </div>

                        <div class="user-preview-stats-slide hh-member-about-slide">
                            <p class="user-preview-text">{{ $profile?->headline ?: __('ui.members_no_headline') }}</p>
                            <div class="hh-member-mini-tags">
                                <span>{{ $profile?->platform ?: __('ui.members_platform_open') }}</span>
                                <span>{{ $profile?->playstyle ?: __('ui.members_playstyle_open') }}</span>
                                <span>{{ $profile?->region ?: __('ui.members_region_open') }}</span>
                                <span class="{{ $profile?->is_lfg_available ? 'is-active' : '' }}">{{ $profile?->is_lfg_available ? __('ui.members_lfg_open') : __('ui.members_lfg_not_marked') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="user-preview-social-links-wrap hh-member-social-wrap">
                        <div class="user-preview-social-links hh-member-social-links">
                            @if ($profile?->steam_url)
                                <div class="user-preview-social-link"><a class="social-link small steam" href="{{ $profile->steam_url }}" target="_blank" rel="nofollow noopener"><i class="social-link-icon hh-ph-brand-icon ph ph-steam-logo" aria-hidden="true"></i></a></div>
                            @endif
                            @if ($profile?->twitch_url)
                                <div class="user-preview-social-link"><a class="social-link small twitch" href="{{ $profile->twitch_url }}" target="_blank" rel="nofollow noopener"><i class="social-link-icon hh-ph-brand-icon ph ph-twitch-logo" aria-hidden="true"></i></a></div>
                            @endif
                            @if ($profile?->youtube_url)
                                <div class="user-preview-social-link"><a class="social-link small youtube" href="{{ $profile->youtube_url }}" target="_blank" rel="nofollow noopener"><i class="social-link-icon hh-ph-brand-icon ph ph-youtube-logo" aria-hidden="true"></i></a></div>
                            @endif
                            @if ($profile?->discord_name)
                                <div class="user-preview-social-link"><a class="social-link small discord" href="{{ $memberUrl }}#profile-contact"><i class="social-link-icon hh-ph-brand-icon ph ph-discord-logo" aria-hidden="true"></i></a></div>
                            @endif
                        </div>
                    </div>

                    <div class="user-preview-actions hh-member-actions">
                        @if ($isOwnCard)
                            <a class="button secondary" href="{{ route('profile.show') }}">{{ __('ui.members_my_profile') }}</a>
                            <a class="button primary" href="{{ route('profile.edit') }}">{{ __('ui.members_edit') }}</a>
                        @elseif (! $friendship || $friendship->isDeclined())
                            <form method="post" action="{{ route('friends.store', $member) }}">
                                @csrf
                                <button class="button secondary" type="submit">{{ __('ui.profile_add_friend') }}</button>
                            </form>
                            <a class="button primary" href="{{ route('messages.index') }}">{{ __('ui.profile_message_singular') }}</a>
                        @elseif ($friendship->isPending() && $friendship->isRequester(auth()->user()))
                            <form method="post" action="{{ route('friends.destroy', $friendship) }}">
                                @csrf
                                @method('DELETE')
                                <button class="button secondary" type="submit">{{ __('ui.members_friend_request_sent') }}</button>
                            </form>
                            <a class="button primary" href="{{ route('messages.index') }}">{{ __('ui.profile_message_singular') }}</a>
                        @elseif ($friendship->isPending() && $friendship->isRecipient(auth()->user()))
                            <form method="post" action="{{ route('friends.accept', $friendship) }}">
                                @csrf
                                <button class="button secondary" type="submit">{{ __('ui.profile_accept') }}</button>
                            </form>
                            <form method="post" action="{{ route('friends.decline', $friendship) }}">
                                @csrf
                                <button class="button primary" type="submit">{{ __('ui.profile_decline') }}</button>
                            </form>
                        @elseif ($friendship->isAccepted())
                            <form method="post" action="{{ route('friends.destroy', $friendship) }}" onsubmit="return confirm('{{ __('ui.profile_friend_remove_confirm') }}');">
                                @csrf
                                @method('DELETE')
                                <button class="button secondary" type="submit">{{ __('ui.members_friends_active') }}</button>
                            </form>
                            <a class="button primary" href="{{ route('messages.index') }}">{{ __('ui.profile_message_singular') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($members->hasPages())
        <nav class="section-pager-bar hh-vk-pager hh-members-pager" aria-label="{{ __('ui.members_pages_aria') }}">
            <div class="section-pager">
                @if ($members->onFirstPage())
                    <span class="section-pager-item disabled">{{ __('ui.pagination_previous') }}</span>
                @else
                    <a class="section-pager-item" href="{{ $members->previousPageUrl() }}">{{ __('ui.pagination_previous') }}</a>
                @endif

                <span class="section-pager-item active">{{ $members->currentPage() }}</span>

                @if ($members->hasMorePages())
                    <a class="section-pager-item" href="{{ $members->nextPageUrl() }}">{{ __('ui.pagination_next') }}</a>
                @else
                    <span class="section-pager-item disabled">{{ __('ui.pagination_next') }}</span>
                @endif
            </div>
        </nav>
    @endif
@endif
@endsection
