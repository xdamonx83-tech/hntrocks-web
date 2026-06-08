@extends('layouts.app')

@section('title', $profileUser->username . ' Info')

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

<section class="hh-profile-vikinger hh-profile-about-shell">
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
                        <div class="profile-header-social-link"><a class="social-link discord" href="{{ $aboutUrl }}#profile-contact" aria-label="Discord"><i class="hh-ph-brand-icon ph ph-discord-logo" aria-hidden="true"></i></a></div>
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
            <a class="section-menu-item active" href="{{ $aboutUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-user" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_section_info') }}</p></a>
            <a class="section-menu-item" href="{{ $profileUrl }}#profile-timeline"><i class="section-menu-item-icon hh-ph-action-icon ph ph-clock-counter-clockwise" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_section_timeline') }}</p></a>
            <a class="section-menu-item" href="{{ $friendsUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-user" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.friends') }}</p></a>
            <a class="section-menu-item" href="{{ $badgesUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-medal" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_badges_stat') }}</p></a>
            <a class="section-menu-item" href="{{ $teamsUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_teams_title') }}</p></a>
            <a class="section-menu-item" href="{{ $aboutUrl }}#profile-contact"><i class="section-menu-item-icon hh-ph-action-icon ph ph-chats-circle" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_section_contact') }}</p></a>
        </div>
    </nav>


    <section class="section hh-profile-about-page">
        @php
            $profile = $profileUser->profile;
            $completion = \App\Support\ProfileCompletion::score($profileUser);
            $joinedAt = $profileUser->created_at?->format('d.m.Y') ?? '—';
            $lastPostDate = $lastPost?->created_at?->diffForHumans() ?? __('ui.profile_last_post_empty');
            $lastFriendDate = $lastFriendship?->accepted_at?->diffForHumans() ?? __('ui.profile_last_friend_empty');
            $latestBadgeIcon = $latestBadge?->iconUrl() ?: asset('assets/vikinger/img/badge/unlocked-badge.png');
            $profileVisibilityLabel = match ($profile?->profile_visibility) {
                'private' => __('ui.profile_visibility_private_short'),
                'registered' => 'Registrierte Nutzer',
                default => __('ui.profile_visibility_public_short'),
            };
            $lfgLabel = $profile?->is_lfg_available ? __('ui.profile_lfg_active') : __('ui.profile_lfg_inactive');
            $aboutText = filled($profile?->bio)
                ? $profile->bio
                : ($isOwnProfile
                    ? __('ui.profile_about_empty_own')
                    : __('ui.profile_about_empty_user'));
        @endphp


        <div class="grid grid-3-6-3 hh-profile-about-grid">
            <div class="grid-column">
                <div class="widget-box" id="profile-about">
                    @if ($isOwnProfile)
                        <div class="widget-box-settings">
                            <div class="post-settings-wrap">
                                <div class="post-settings widget-box-post-settings-dropdown-trigger">
                                    <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                                </div>
                                <div class="simple-dropdown widget-box-post-settings-dropdown">
                                    <a class="simple-dropdown-link" href="{{ route('profile.edit') }}">{{ __('ui.profile_edit') }}</a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <p class="widget-box-title">About Me</p>

                    <div class="widget-box-content">
                        <p class="paragraph">{!! nl2br(e($aboutText)) !!}</p>

                        <div class="information-line-list">
                            <div class="information-line">
                                <p class="information-line-title">{{ __('ui.profile_joined') }}</p>
                                <p class="information-line-text">{{ $joinedAt }}</p>
                            </div>

                            <div class="information-line">
                                <p class="information-line-title">Username</p>
                                <p class="information-line-text">{{ '@'.$profileUser->username }}</p>
                            </div>

                            <div class="information-line">
                                <p class="information-line-title">Status</p>
                                <p class="information-line-text">{{ $lfgLabel }}</p>
                            </div>

                            <div class="information-line">
                                <p class="information-line-title">Sichtbarkeit</p>
                                <p class="information-line-text">{{ $profileVisibilityLabel }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="widget-box" id="profile-contact">
                    @if ($isOwnProfile)
                        <div class="widget-box-settings">
                            <div class="post-settings-wrap">
                                <div class="post-settings widget-box-post-settings-dropdown-trigger">
                                    <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                                </div>
                                <div class="simple-dropdown widget-box-post-settings-dropdown">
                                    <a class="simple-dropdown-link" href="{{ route('profile.edit') }}">Links bearbeiten</a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <p class="widget-box-title">{{ __('ui.profile_contact_links') }}</p>

                    <div class="widget-box-content">
                        <div class="information-line-list">
                            <div class="information-line">
                                <p class="information-line-title">Discord</p>
                                <p class="information-line-text">{{ $profile?->discord_name ?: 'Nicht angegeben' }}</p>
                            </div>

                            <div class="information-line">
                                <p class="information-line-title">Steam</p>
                                <p class="information-line-text">
                                    @if ($profile?->steam_url)
                                        <a href="{{ $profile->steam_url }}" rel="nofollow noopener" target="_blank">{{ __('ui.profile_open_steam') }}</a>
                                    @else
                                        Nicht angegeben
                                    @endif
                                </p>
                            </div>

                            <div class="information-line">
                                <p class="information-line-title">Twitch</p>
                                <p class="information-line-text">
                                    @if ($profile?->twitch_url)
                                        <a href="{{ $profile->twitch_url }}" rel="nofollow noopener" target="_blank">{{ __('ui.profile_open_twitch') }}</a>
                                    @else
                                        Nicht angegeben
                                    @endif
                                </p>
                            </div>

                            <div class="information-line">
                                <p class="information-line-title">YouTube</p>
                                <p class="information-line-text">
                                    @if ($profile?->youtube_url)
                                        <a href="{{ $profile->youtube_url }}" rel="nofollow noopener" target="_blank">{{ __('ui.profile_open_youtube') }}</a>
                                    @else
                                        Nicht angegeben
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-column">
                <div class="widget-box">
                    @if ($isOwnProfile)
                        <div class="widget-box-settings">
                            <div class="post-settings-wrap">
                                <div class="post-settings widget-box-post-settings-dropdown-trigger">
                                    <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                                </div>
                                <div class="simple-dropdown widget-box-post-settings-dropdown">
                                    <a class="simple-dropdown-link" href="{{ route('profile.edit') }}">Spielprofil bearbeiten</a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <p class="widget-box-title">{{ __('ui.profile_hunt_profile') }}</p>

                    <div class="widget-box-content">
                        <div class="information-block-list hh-profile-about-block-list">
                            <div class="information-block">
                                <p class="information-block-title">Headline</p>
                                <p class="information-block-text">{{ $profile?->headline ?: __('ui.profile_headline_empty') }}</p>
                            </div>

                            <div class="information-block">
                                <p class="information-block-title">{{ __('ui.platform') }}</p>
                                <p class="information-block-text">{{ $profile?->platform ?: 'Nicht angegeben' }}</p>
                            </div>

                            <div class="information-block">
                                <p class="information-block-title">{{ __('ui.profile_playstyle') }}</p>
                                <p class="information-block-text">{{ $profile?->playstyle ?: 'Nicht angegeben' }}</p>
                            </div>

                            <div class="information-block">
                                <p class="information-block-title">{{ __('ui.profile_hunt_role') }}</p>
                                <p class="information-block-text">{{ $profile?->hunt_role ?: 'Nicht angegeben' }}</p>
                            </div>

                            <div class="information-block">
                                <p class="information-block-title">{{ __('ui.profile_region_language') }}</p>
                                <p class="information-block-text">
                                    {{ $profile?->region ?: __('ui.profile_region_open') }} · {{ $profile?->language ?: __('ui.profile_language_open') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="widget-box">
                    <p class="widget-box-title">{{ __('ui.profile_activity') }}</p>

                    <div class="widget-box-content">
                        <div class="timeline-information-list hh-profile-about-timeline">
                            <div class="timeline-information">
                                <p class="timeline-information-title">Letzter Feed-Post</p>
                                <p class="timeline-information-date">{{ $lastPostDate }}</p>
                                <p class="timeline-information-text">
                                    @if ($lastPost)
                                        {{ $lastPost->excerpt(160) }}
                                    @else
                                        {{ __('ui.profile_no_visible_posts') }}
                                    @endif
                                </p>
                            </div>

                            <div class="timeline-information">
                                <p class="timeline-information-title">{{ __('ui.profile_last_friendship') }}</p>
                                <p class="timeline-information-date">{{ $lastFriendDate }}</p>
                                <p class="timeline-information-text">{{ __('ui.profile_confirmed_friends_text', ['count' => $profileFriendsCount]) }}</p>
                            </div>

                            <div class="timeline-information">
                                <p class="timeline-information-title">Teams</p>
                                <p class="timeline-information-date">{{ $profileUser->active_teams_count ?? 0 }} aktive Mitgliedschaften</p>
                                <p class="timeline-information-text">{{ __('ui.profile_teams_timeline_text') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-column">
                <div class="widget-box hh-profile-about-progress-widget">
                    <div class="progress-arc-summary">
                        <div class="progress-arc-wrap hh-profile-about-progress-ring">
                            <div class="hh-profile-about-progress-circle" style="--hh-profile-about-progress: {{ $completion }}%;" aria-hidden="true">
                                <span>{{ $completion }}%</span>
                            </div>
                        </div>

                        <div class="progress-arc-summary-info">
                            <p class="progress-arc-summary-title">Profilfortschritt</p>
                            <p class="progress-arc-summary-subtitle">{{ $profileUser->name }}</p>
                            <p class="progress-arc-summary-text">{{ __('ui.profile_completion_about_text') }}</p>
                        </div>
                    </div>

                    <div class="achievement-status-list">
                        <div class="achievement-status">
                            <p class="achievement-status-progress">{{ $profileCompletedQuestCount }}/{{ max(1, $activeQuestCount) }}</p>
                            <div class="achievement-status-info">
                                <p class="achievement-status-title">Quests</p>
                                <p class="achievement-status-text">Abgeschlossen</p>
                            </div>
                            <img class="achievement-status-image" src="{{ asset('assets/vikinger/img/badge/completedq-s.png') }}" alt="Quests">
                        </div>

                        <div class="achievement-status">
                            <p class="achievement-status-progress">{{ $profileUser->badges_count ?? 0 }}</p>
                            <div class="achievement-status-info">
                                <p class="achievement-status-title">Badges</p>
                                <p class="achievement-status-text">Freigeschaltet</p>
                            </div>
                            <img class="achievement-status-image" src="{{ $latestBadgeIcon }}" alt="Badges">
                        </div>
                    </div>
                </div>

                <div class="widget-box">
                    <p class="widget-box-title">Mehr Stats</p>

                    <div class="widget-box-content">
                        <div class="stat-block-list">
                            <div class="stat-block">
                                <div class="stat-block-decoration">
                                    <i class="stat-block-decoration-icon hh-ph-action-icon ph ph-user" aria-hidden="true"></i>
                                </div>
                                <div class="stat-block-info">
                                    <p class="stat-block-title">{{ __('ui.friends') }}</p>
                                    <p class="stat-block-text">{{ $profileFriendsCount }} {{ __('ui.profile_confirmed') }}</p>
                                </div>
                            </div>

                            <div class="stat-block">
                                <div class="stat-block-decoration">
                                    <i class="stat-block-decoration-icon hh-ph-action-icon ph ph-note-pencil" aria-hidden="true"></i>
                                </div>
                                <div class="stat-block-info">
                                    <p class="stat-block-title">Letzter Post</p>
                                    <p class="stat-block-text">{{ $lastPostDate }}</p>
                                </div>
                            </div>

                            <div class="stat-block">
                                <div class="stat-block-decoration">
                                    <i class="stat-block-decoration-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                                </div>
                                <div class="stat-block-info">
                                    <p class="stat-block-title">Teams</p>
                                    <p class="stat-block-text">{{ $profileUser->active_teams_count ?? 0 }} aktiv</p>
                                </div>
                            </div>

                            <div class="stat-block">
                                <div class="stat-block-decoration">
                                    <i class="stat-block-decoration-icon hh-ph-action-icon ph ph-medal" aria-hidden="true"></i>
                                </div>
                                <div class="stat-block-info">
                                    <p class="stat-block-title">Level</p>
                                    <p class="stat-block-text">Level {{ $profileUser->level ?? 1 }} · {{ $profileUser->xp_total ?? 0 }} XP</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</section>
@endsection
