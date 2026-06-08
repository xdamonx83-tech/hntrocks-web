@extends('layouts.app')

@section('title', $profileUser->username . ' ' . __('ui.profile_friends_title_suffix'))

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

<section class="hh-profile-vikinger hh-profile-friends-page">
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
            <a class="section-menu-item active" href="{{ $friendsUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-user" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.friends') }}</p></a>
            <a class="section-menu-item" href="{{ $badgesUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-medal" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_badges_stat') }}</p></a>
            <a class="section-menu-item" href="{{ $teamsUrl }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_teams_title') }}</p></a>
            <a class="section-menu-item" href="{{ $profileUrl }}#profile-contact"><i class="section-menu-item-icon hh-ph-action-icon ph ph-chats-circle" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.profile_section_contact') }}</p></a>
        </div>
    </nav>

    <section class="section hh-profile-friends-section">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.profile_friends_of', ['name' => $profileUser->name]) }}</p>
                <h2 class="section-title">{{ __('ui.friends') }} <span class="highlighted">{{ $profileFriendsCount ?? 0 }}</span></h2>
            </div>
        </div>

        <div class="section-filters-bar v1 hh-profile-friends-summarybar">
            <div class="section-filters-bar-actions">
                <p class="section-filters-bar-title">{{ __('ui.profile_all_confirmed_friends') }}</p>
                <p class="section-filters-bar-text">
                    @if (($profileFriendsCount ?? 0) > 0)
                        <span class="bold">{{ $profileFriends->firstItem() ?? 0 }}–{{ $profileFriends->lastItem() ?? 0 }}</span>
                        <span>&nbsp;von&nbsp;</span>
                        <span class="highlighted">{{ $profileFriendsCount }}</span>
                        <span>&nbsp;{{ __('ui.profile_friends_visible') }}</span>
                    @else
                        {{ __('ui.profile_no_friends_visible') }}
                    @endif
                </p>
            </div>
        </div>

        @if ($profileFriends->count() > 0)
            <div class="grid grid-4-4-4 centered hh-profile-friends-grid">
                @foreach ($profileFriends as $friend)
                    @php
                        $friendProfile = $friend->profile;
                        $friendUrl = route('profile.public', $friend);
                        $cardFriendship = $viewerFriendships[(int) $friend->id] ?? null;
                        $isViewerCard = $viewer?->is($friend) ?? false;
                        $friendDescription = $friendProfile?->headline ?: $friendProfile?->bio;
                    @endphp

                    <div class="user-preview hh-profile-friend-card">
                        <figure class="user-preview-cover liquid">
                            <img src="{{ $friend->coverUrl() }}" alt="{{ __('ui.profile_cover_alt', ['name' => $friend->username]) }}">
                        </figure>

                        <div class="user-preview-info">
                            <div class="user-short-description">
                                <a class="user-short-description-avatar user-avatar medium" href="{{ $friendUrl }}">
                                    <div class="user-avatar-border"><div class="hexagon-120-132"></div></div>
                                    <div class="user-avatar-content"><div class="hexagon-image-82-90" data-src="{{ $friend->avatarUrl() }}"></div></div>
                                    <div class="user-avatar-progress"><div class="hexagon-progress-100-110"></div></div>
                                    <div class="user-avatar-progress-border"><div class="hexagon-border-100-110"></div></div>
                                    <div class="user-avatar-badge">
                                        <div class="user-avatar-badge-border"><div class="hexagon-32-36"></div></div>
                                        <div class="user-avatar-badge-content"><div class="hexagon-dark-26-28"></div></div>
                                        <p class="user-avatar-badge-text">{{ $friend->level ?? 1 }}</p>
                                    </div>
                                </a>

                                <p class="user-short-description-title"><a href="{{ $friendUrl }}">{{ $friend->name }}</a></p>
                                <p class="user-short-description-text"><a href="{{ $friendUrl }}">{{ '@'.$friend->username }}</a></p>
                            </div>

                            <div class="user-preview-stats-slides hh-profile-friend-stats-slides">
                                <div class="user-preview-stats-slide">
                                    <div class="user-stats">
                                        <div class="user-stat"><p class="user-stat-title">{{ $friend->visible_feed_posts_count ?? 0 }}</p><p class="user-stat-text">{{ __('ui.profile_posts_stat') }}</p></div>
                                        <div class="user-stat"><p class="user-stat-title">{{ (int) ($friend->common_friends_count ?? 0) }}</p><p class="user-stat-text">{{ __('ui.profile_common') }}</p></div>
                                        <div class="user-stat"><p class="user-stat-title">{{ $friend->active_teams_count ?? 0 }}</p><p class="user-stat-text">{{ __('ui.profile_teams_title') }}</p></div>
                                    </div>
                                </div>
                            </div>

                            <p class="user-preview-text hh-profile-friend-bio">
                                {{ $friendDescription ? \Illuminate\Support\Str::limit($friendDescription, 135) : __('ui.profile_no_short_description') }}
                            </p>

                            <div class="user-preview-actions hh-profile-friend-card-actions">
                                @if ($isViewerCard)
                                    <a class="button white" href="{{ route('profile.show') }}">{{ __('ui.profile_your_profile') }}</a>
                                @else
                                    @if (! $cardFriendship || $cardFriendship->isDeclined())
                                        <form method="post" action="{{ route('friends.store', $friend) }}">
                                            @csrf
                                            <button class="button secondary" type="submit">{{ __('ui.profile_add_friend') }}</button>
                                        </form>
                                    @elseif ($cardFriendship->isPending() && $cardFriendship->isRequester($viewer))
                                        <form method="post" action="{{ route('friends.destroy', $cardFriendship) }}" onsubmit="return confirm('{{ __('ui.profile_friend_request_withdraw_confirm') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button secondary" type="submit">{{ __('ui.profile_friend_request_sent') }}</button>
                                        </form>
                                    @elseif ($cardFriendship->isPending() && $cardFriendship->isRecipient($viewer))
                                        <form method="post" action="{{ route('friends.accept', $cardFriendship) }}">
                                            @csrf
                                            <button class="button secondary" type="submit">{{ __('ui.profile_accept') }}</button>
                                        </form>
                                        <form method="post" action="{{ route('friends.decline', $cardFriendship) }}">
                                            @csrf
                                            <button class="button white" type="submit">{{ __('ui.profile_decline') }}</button>
                                        </form>
                                    @elseif ($cardFriendship->isAccepted())
                                        <form method="post" action="{{ route('friends.destroy', $cardFriendship) }}" onsubmit="return confirm('{{ __('ui.profile_friend_remove_confirm') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button white" type="submit">{{ __('ui.profile_remove_friends') }}</button>
                                        </form>
                                    @endif

                                    <a class="button primary" href="{{ route('messages.with-user', $friend) }}" data-hh-profile-message-drawer data-recipient-username="{{ $friend->username }}">{{ __('ui.profile_message_singular') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($profileFriends->hasPages())
                <nav class="hh-pagination hh-profile-friends-pagination" aria-label="{{ __('ui.profile_friends_navigation') }}">
                    @if ($profileFriends->onFirstPage())
                        <span class="is-disabled">{{ __('ui.pagination_previous') }}</span>
                    @else
                        <a href="{{ $profileFriends->previousPageUrl() }}">{{ __('ui.pagination_previous') }}</a>
                    @endif

                    <span>{{ __('ui.profile_page_x_of_y', ['current' => $profileFriends->currentPage(), 'last' => $profileFriends->lastPage()]) }}</span>

                    @if ($profileFriends->hasMorePages())
                        <a href="{{ $profileFriends->nextPageUrl() }}">{{ __('ui.pagination_next') }}</a>
                    @else
                        <span class="is-disabled">{{ __('ui.pagination_next') }}</span>
                    @endif
                </nav>
            @endif
        @else
            <div class="widget-box hh-profile-friends-empty">
                <p class="widget-box-title">{{ __('ui.profile_no_friends_visible') }}</p>
                <p class="widget-box-text">{{ __('ui.profile_friends_empty_text') }}</p>
                @if ($isOwnProfile)
                    <a class="widget-box-button button small secondary" href="{{ route('members.index') }}">{{ __('ui.profile_discover_members') }}</a>
                @endif
            </div>
        @endif
    </section>
</section>
@endsection
