@extends('layouts.app')

@section('title', $profileUser->username . ' ' . __('ui.profile_title_suffix'))

@section('content')
@php
    $profile = $profileUser->profile;
    $completion = \App\Support\ProfileCompletion::score($profileUser);
    $profileUrl = $isOwnProfile ? route('profile.show') : route('profile.public', $profileUser);
    $aboutUrl = $isOwnProfile ? route('profile.about') : route('profile.about.public', $profileUser);
    $friendsUrl = $isOwnProfile ? route('profile.friends') : route('profile.friends.public', $profileUser);
    $badgesUrl = $isOwnProfile ? route('profile.badges') : route('profile.badges.public', $profileUser);
    $teamsUrl = $isOwnProfile ? route('profile.teams') : route('profile.teams.public', $profileUser);
    $nextLevelXp = max(250, (int) ($profileUser->level ?: 1) * 250);
    $levelProgress = min(100, (int) round(((int) ($profileUser->xp_total ?? 0) % $nextLevelXp) / $nextLevelXp * 100));
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

<section class="hh-profile-vikinger">
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
                        <div class="profile-header-social-link"><a class="social-link discord" href="#profile-contact" aria-label="Discord"><i class="hh-ph-brand-icon ph ph-discord-logo" aria-hidden="true"></i></a></div>
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
            <a class="section-menu-item active" href="{{ $profileUrl }}#profile-timeline"><i class="section-menu-item-icon hh-ph-action-icon ph ph-clock-counter-clockwise" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_section_timeline') }}</p></a>
            <a class="section-menu-item" href="{{ $friendsUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-user" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.friends') }}</p></a>
            <a class="section-menu-item" href="{{ $badgesUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-medal" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_badges_stat') }}</p></a>
            <a class="section-menu-item" href="{{ $teamsUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_teams_title') }}</p></a>
            <a class="section-menu-item" href="{{ $profileUrl }}#profile-contact"><i class="section-menu-item-icon hh-ph-action-icon ph ph-chats-circle" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_section_contact') }}</p></a>
        </div>
    </nav>

    <div class="grid grid-3-6-3 mobile-prefer-content hh-profile-vikinger-grid">
        <div class="grid-column">
            <div id="profile-about" class="widget-box">
                <div class="widget-box-settings"><div class="post-settings-wrap"><div class="post-settings"><i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i></div></div></div>
                <p class="widget-box-title">{{ __('ui.profile_about_me') }}</p>
                <div class="widget-box-content">
                    @if ($profile?->bio)
                        <p class="paragraph">{{ $profile->bio }}</p>
                    @else
                        <p class="paragraph">{{ __('ui.profile_no_bio') }}</p>
                    @endif
                    <div class="information-line-list">
                        <div class="information-line"><p class="information-line-title">{{ __('ui.profile_joined') }}</p><p class="information-line-text">{{ $profileUser->created_at?->format('d.m.Y') ?: __('ui.profile_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">Level</p><p class="information-line-text">{{ $profileUser->level ?? 1 }}</p></div>
                        <div class="information-line"><p class="information-line-title">XP</p><p class="information-line-text">{{ $profileUser->xp_total ?? 0 }}</p></div>
                    </div>
                </div>
            </div>

            <div class="widget-box">
                <p class="widget-box-title">{{ __('ui.profile_player_profile') }}</p>
                <div class="widget-box-content">
                    <div class="information-line-list">
                        <div class="information-line"><p class="information-line-title">{{ __('ui.platform') }}</p><p class="information-line-text">{{ $profile?->platform ?: __('ui.profile_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.profile_playstyle') }}</p><p class="information-line-text">{{ $profile?->playstyle ?: __('ui.profile_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.region') }}</p><p class="information-line-text">{{ $profile?->region ?: __('ui.profile_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.profile_language') }}</p><p class="information-line-text">{{ $profile?->language ?: __('ui.profile_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.profile_role') }}</p><p class="information-line-text">{{ $profile?->hunt_role ?: __('ui.profile_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">LFG</p><p class="information-line-text">{{ $profile?->is_lfg_available ? __('ui.profile_lfg_open') : __('ui.profile_lfg_not_marked') }}</p></div>
                    </div>
                </div>
            </div>

            <div id="profile-badges" class="widget-box hh-profile-vk-badges-widget">
                <div class="widget-box-settings">
                    <div class="post-settings-wrap">
                        <div class="post-settings widget-box-post-settings-dropdown-trigger">
                            <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                        </div>
                        <div class="simple-dropdown widget-box-post-settings-dropdown">
                            <a class="simple-dropdown-link" href="{{ $badgesUrl }}">{{ __('ui.profile_view_all_badges') }}</a>
                        </div>
                    </div>
                </div>
                <p class="widget-box-title">Badges <span class="highlighted">{{ $profileUser->badges_count ?? 0 }}</span></p>
                <div class="widget-box-content">
                    @if ($latestBadges->isNotEmpty())
                        <div class="hh-profile-vk-badge-grid">
                            @foreach ($latestBadges as $badge)
                                <a class="hh-profile-vk-badge-item text-tooltip-tft" data-title="{{ $badge->name }}" href="{{ $badgesUrl }}" aria-label="{{ $badge->name }}">
                                    @if($badge->iconUrl())
                                        <img src="{{ $badge->iconUrl() }}" alt="{{ $badge->name }}">
                                    @else
                                        <span>{{ $badge->icon ?: '◆' }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="widget-box-text">{{ __('ui.profile_no_badges_unlocked') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div id="profile-timeline" class="grid-column">
            <div class="hh-feed-list-vikinger hh-profile-post-list">
                @forelse ($profilePosts as $post)
                    <div id="profile-post-{{ $post->id }}" class="hh-profile-post-anchor">
                        @include('feed.partials.post-card', ['post' => $post])
                    </div>
                @empty
                    <div class="widget-box">
                        <p class="widget-box-title">{{ __('ui.profile_no_timeline_title') }}</p>
                        <p class="widget-box-text">{{ $isOwnProfile ? __('ui.profile_no_timeline_own') : __('ui.profile_no_timeline_user', ['name' => $profileUser->username]) }}</p>
                        @if ($isOwnProfile)
                            <a class="widget-box-button button small secondary" href="{{ route('feed.index') }}">{{ __('ui.profile_create_first_post') }}</a>
                        @endif
                    </div>
                @endforelse
            </div>

            @if (($profilePostsTotal ?? 0) > ($profilePostsShown ?? 0))
                @php
                    $profilePostQueryParams = array_merge(request()->query(), ['profile_posts_page' => ($profilePostPage ?? 1) + 1]);
                    $profileMoreUrl = $profileUrl . '?' . http_build_query($profilePostQueryParams) . ($profileNextPostId ? '#profile-post-' . $profileNextPostId : '#profile-timeline');
                @endphp
                <div class="widget-box hh-profile-load-more-card">
                    <p class="widget-box-text">{{ __('ui.profile_posts_shown', ['shown' => $profilePostsShown, 'total' => $profilePostsTotal]) }}</p>
                    <a class="widget-box-button button small secondary" href="{{ $profileMoreUrl }}">{{ __('ui.load_more') }}</a>
                </div>
            @elseif (($profilePostsTotal ?? 0) > ($profilePostsPerPage ?? 5))
                <div class="widget-box hh-profile-load-more-card">
                    <p class="widget-box-text">{{ __('ui.profile_all_posts_shown', ['total' => $profilePostsTotal]) }}</p>
                </div>
            @endif
        </div>

        <div class="grid-column">
            <div class="widget-box hh-profile-completion-card hh-profile-progress-status-card">
                <p class="widget-box-title">Profilfortschritt</p>
                <div class="widget-box-content">
                    <div class="progress-arc-summary hh-progress-summary-fallback">
                        <div class="progress-arc-wrap hh-progress-arc-plain" style="--hh-progress: {{ $completion }};"><div class="hh-progress-arc-value">{{ $completion }}%</div></div>
                        <div class="progress-arc-summary-info"><p class="progress-arc-summary-title">{{ $completion >= 100 ? __('ui.profile_complete') : __('ui.profile_needs_work') }}</p><p class="progress-arc-summary-subtitle">{{ __('ui.profile_completion_subtitle') }}</p><p class="progress-arc-summary-text">{{ __('ui.profile_completion_text') }}</p></div>
                    </div>

                    <div class="hh-profile-status-inline">
                        <p class="hh-profile-status-inline-title">{{ __('ui.profile_status') }}</p>
                        <p class="hh-profile-status-inline-text">{{ $profile?->headline ?: __('ui.profile_no_headline_long') }}</p>
                        <div class="progress-stat hh-profile-level-progress">
                            <div class="bar-progress-wrap">
                                <p class="bar-progress-info"><span>Level {{ $profileUser->level ?? 1 }}</span><span>{{ $profileUser->xp_total ?? 0 }} XP</span></p>
                            </div>
                            <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $levelProgress }}%"></span></div>
                        </div>
                    </div>
                </div>
                @if ($isOwnProfile)
                    <a class="widget-box-button button small white" href="{{ route('profile.edit') }}">{{ __('ui.profile_complete_profile') }}</a>
                @endif
            </div>

            <div id="profile-teams" class="widget-box hh-profile-vk-teams-widget">
                <div class="widget-box-settings">
                    <div class="post-settings-wrap">
                        <div class="post-settings widget-box-post-settings-dropdown-trigger">
                            <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                        </div>
                        <div class="simple-dropdown widget-box-post-settings-dropdown">
                            <a class="simple-dropdown-link" href="{{ $teamsUrl }}">{{ __('ui.profile_view_all_teams') }}</a>
                        </div>
                    </div>
                </div>
                <p class="widget-box-title">Teams</p>
                <div class="widget-box-content">
                    @if ($profileTeams->isNotEmpty())
                        <div class="user-status-list hh-profile-vk-user-list">
                            @foreach ($profileTeams as $team)
                                <div class="user-status request-small">
                                    <a class="user-status-avatar" href="{{ route('teams.show', $team) }}">
                                        <div class="user-avatar small no-border">
                                            <div class="user-avatar-content">
                                                <div class="hexagon-image-40-44" data-src="{{ $team->avatarUrl() }}"></div>
                                            </div>
                                        </div>
                                    </a>
                                    <p class="user-status-title"><a class="bold" href="{{ route('teams.show', $team) }}">{{ $team->name }}</a></p>
                                    <p class="user-status-text small">{{ __('ui.members_count', ['count' => $team->active_members_count]) }}</p>
                                    <div class="action-request-list">
                                        <a class="action-request accept" href="{{ route('teams.show', $team) }}" aria-label="{{ __('ui.profile_view_team') }}">
                                            <i class="action-request-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="widget-box-text">{{ __('ui.profile_no_active_teams') }}</p>
                    @endif
                </div>
            </div>


            <div id="profile-friends" class="widget-box hh-profile-vk-friends-widget">
                <div class="widget-box-settings">
                    <div class="post-settings-wrap">
                        <div class="post-settings widget-box-post-settings-dropdown-trigger">
                            <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                        </div>
                        <div class="simple-dropdown widget-box-post-settings-dropdown">
                            <a class="simple-dropdown-link" href="{{ $friendsUrl }}">{{ __('ui.profile_view_all_friends') }}</a>
                        </div>
                    </div>
                </div>
                <p class="widget-box-title">{{ __('ui.friends') }} <span class="highlighted">{{ $profileFriendsCount ?? 0 }}</span></p>
                <div class="widget-box-content">
                    @if ($profileFriendsPreview->isNotEmpty())
                        <div class="user-status-list hh-profile-vk-user-list">
                            @foreach ($profileFriendsPreview as $friend)
                                <div class="user-status request-small">
                                    <a class="user-status-avatar" href="{{ route('profile.public', $friend) }}">
                                        <div class="user-avatar small no-outline">
                                            <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $friend->avatarUrl() }}"></div></div>
                                            <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                                            <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                                            <div class="user-avatar-badge">
                                                <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                                                <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                                                <p class="user-avatar-badge-text">{{ $friend->level ?? 1 }}</p>
                                            </div>
                                        </div>
                                    </a>
                                    <p class="user-status-title"><a class="bold" href="{{ route('profile.public', $friend) }}">{{ $friend->name }}</a></p>
                                    <p class="user-status-text small">{{ __('ui.profile_mutual_friends', ['count' => (int) ($friend->common_friends_count ?? 0)]) }}</p>
                                    <div class="action-request-list">
                                        <a class="action-request accept" href="{{ route('profile.public', $friend) }}" aria-label="{{ __('ui.profile_view_profile') }}">
                                            <i class="action-request-icon hh-ph-action-icon ph ph-user-plus" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="widget-box-text">{{ __('ui.profile_no_friends_visible') }}</p>
                    @endif
                </div>
                <a class="widget-box-button button small secondary" href="{{ $friendsUrl }}">{{ __('ui.profile_view_all_friends') }}</a>
            </div>

            <div id="profile-quests" class="widget-box hh-profile-vk-open-quests-widget">
                <div class="widget-box-settings">
                    <div class="post-settings-wrap">
                        <div class="post-settings widget-box-post-settings-dropdown-trigger">
                            <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                        </div>
                        <div class="simple-dropdown widget-box-post-settings-dropdown">
                            <a class="simple-dropdown-link" href="{{ route('gamification.index') }}">{{ __('ui.profile_view_all_quests') }}</a>
                        </div>
                    </div>
                </div>
                <p class="widget-box-title">Open Quests</p>
                <div class="widget-box-content">
                    @if ($profileQuestPreview->isNotEmpty())
                        <div class="quest-preview-list hh-feed-shortcut-list">
                            @foreach ($profileQuestPreview as $quest)
                                @php
                                    $questProgress = $quest->progress->first();
                                    $questCount = min((int) ($questProgress?->progress_count ?? 0), (int) $quest->target_count);
                                    $questPercent = (int) min(100, round(($questCount / max(1, (int) $quest->target_count)) * 100));
                                @endphp
                                <a class="quest-preview" href="{{ route('gamification.index') }}">
                                    <div class="quest-preview-info">
                                        <img class="quest-preview-image" src="{{ asset('assets/vikinger/img/quest/openq-s.png') }}" alt="Quest">
                                        <p class="quest-preview-title">{{ $quest->name }}</p>
                                        <p class="quest-preview-text">{{ $quest->description ?: $questCount.' / '.$quest->target_count.' · +'.$quest->xp_reward.' XP' }}</p>
                                    </div>
                                    <div class="progress-stat">
                                        <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $questPercent }}%"></span></div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="widget-box-text">{{ __('ui.no_open_quests') }}</p>
                    @endif
                </div>
                <a class="widget-box-button button small white" href="{{ route('gamification.index') }}">{{ __('ui.view_all') }}</a>
            </div>

            <div id="profile-contact" class="widget-box">
                <p class="widget-box-title">{{ __('ui.profile_contact_links') }}</p>
                <div class="widget-box-content">
                    <div class="information-line-list">
                        <div class="information-line"><p class="information-line-title">Discord</p><p class="information-line-text">{{ $profile?->discord_name ?: __('ui.profile_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">Steam</p><p class="information-line-text">@if ($profile?->steam_url)<a href="{{ $profile->steam_url }}" rel="nofollow noopener" target="_blank">{{ __('ui.profile_open_steam') }}</a>@else {{ __('ui.profile_not_set') }} @endif</p></div>
                        <div class="information-line"><p class="information-line-title">Twitch</p><p class="information-line-text">@if ($profile?->twitch_url)<a href="{{ $profile->twitch_url }}" rel="nofollow noopener" target="_blank">{{ __('ui.profile_open_twitch') }}</a>@else {{ __('ui.profile_not_set') }} @endif</p></div>
                        <div class="information-line"><p class="information-line-title">YouTube</p><p class="information-line-text">@if ($profile?->youtube_url)<a href="{{ $profile->youtube_url }}" rel="nofollow noopener" target="_blank">{{ __('ui.profile_open_youtube') }}</a>@else {{ __('ui.profile_not_set') }} @endif</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
