@extends('layouts.app')

@section('title', __('ui.feed'))

@section('content')
@php
    $viewer = auth()->user();
    $completion = \App\Support\ProfileCompletion::score($viewer);
    $badgesCount = $viewer->badges()->count();
    $completedQuests = $viewer->completedQuestProgress()->count();
    $nextLevel = $viewer->xpToNextLevel();
    $ownPostsCount = $viewer->feedPosts()->count();
    $featuredBadges = $featuredBadges ?? collect();
    $publicRecruitingTeams = $publicRecruitingTeams ?? collect();
    $friendsActivityItems = $friendsActivityItems ?? collect();
    $sidebarMembers = \App\Models\User::query()
        ->where('id', '!=', $viewer->id)
        ->latest()
        ->limit(5)
        ->get();
    $sidebarMemberSuggestions = \App\Models\User::query()
        ->where('id', '!=', $viewer->id)
        ->latest()
        ->limit(24)
        ->get()
        ->filter(function ($member) use ($viewer) {
            $friendship = $viewer->friendshipWith($member);

            return ! $friendship?->isAccepted();
        })
        ->take(5)
        ->values();
    $reactionStats = $reactionStats ?? collect();
    $feedMediaMaxMb = (int) ceil(((int) config('hunthub.upload_limits.feed_media_kb', 102400)) / 1024);
    $feedMediaMaxFiles = (int) config('hunthub.upload_limits.feed_media_count', 12);
    $feedFilter = $feedFilter ?? request('filter', 'all');
    $reactionStatSlides = array_chunk(['like' => 'Likes', 'love' => 'Love', 'happy' => 'Happy', 'wow' => 'Wow', 'dislike' => 'Dislikes', 'funny' => 'Funny', 'angry' => 'Angry', 'sad' => 'Sad'], 4, true);
    $feedFeelings = \App\Models\FeedPost::allowedFeelings();
@endphp

<div class="section-banner hh-feed-section-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/newsfeed-icon.png') }}" alt="{{ __('ui.feed') }}">
    <p class="section-banner-title">{{ __('ui.feed') }}</p>
    <p class="section-banner-text">{{ __('ui.feed_banner_text') }}</p>
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

<div class="grid grid-3-6-3 mobile-prefer-content hh-feed-vikinger-grid hh-feed-vikinger-grid-finalize">
    <div class="grid-column">
        <div class="widget-box hh-feed-profile-widget">
            <div class="progress-arc-summary">
                <div class="progress-arc-wrap hh-progress-arc-plain" style="--hh-progress: {{ $completion }};">
                    <div class="hh-progress-arc-value">{{ $completion }}%</div>
                </div>

                <div class="progress-arc-summary-info">
                    <p class="progress-arc-summary-title">{{ __('ui.profile_progress') }}</p>
                    <p class="progress-arc-summary-subtitle">{{ $viewer->name }}</p>
                    <p class="progress-arc-summary-text">{{ __('ui.feed_profile_progress_text') }}</p>
                </div>
            </div>

            <div class="achievement-status-list">
                <div class="achievement-status">
                    <p class="achievement-status-progress">{{ $completedQuests }}</p>
                    <div class="achievement-status-info">
                        <p class="achievement-status-title">{{ __('ui.quests') }}</p>
                        <p class="achievement-status-text">{{ __('ui.completed') }}</p>
                    </div>
                    <img class="achievement-status-image" src="{{ asset('assets/vikinger/img/badge/completedq-s.png') }}" alt="{{ __('ui.quests') }}">
                </div>

                <div class="achievement-status">
                    <p class="achievement-status-progress">{{ $badgesCount }}</p>
                    <div class="achievement-status-info">
                        <p class="achievement-status-title">Badges</p>
                        <p class="achievement-status-text">{{ __('ui.unlocked') }}</p>
                    </div>
                    <img class="achievement-status-image" src="{{ asset('assets/vikinger/img/badge/unlocked-badge.png') }}" alt="Badges">
                </div>
            </div>
        </div>

        <div class="widget-box hh-widget-slider" data-hh-widget-slider>
            <div class="widget-box-controls">
                <div class="slider-controls">
                    <button class="slider-control left" type="button" data-hh-slider-prev aria-label="{{ __('ui.previous_badge') }}"><svg class="slider-control-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg></button>
                    <button class="slider-control right" type="button" data-hh-slider-next aria-label="{{ __('ui.next_badge') }}"><svg class="slider-control-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg></button>
                </div>
            </div>
            <p class="widget-box-title">{{ __('ui.featured_badges') }}</p>

            <div class="widget-box-content hh-widget-slider-viewport">
                <div class="hh-widget-slider-track" data-hh-slider-track>
                    @forelse ($featuredBadges as $featuredBadge)
                        @php
                            $badge = $featuredBadge['badge'];
                            $quest = $featuredBadge['quest'];
                            $badgeImage = $featuredBadge['image_url'];
                            $badgeIcon = $featuredBadge['icon'];
                            $badgeXp = (int) $featuredBadge['xp'];
                            $badgePercent = (int) $featuredBadge['percent'];
                            $badgeCount = (int) $featuredBadge['count'];
                            $badgeTarget = max(1, (int) $featuredBadge['target']);
                        @endphp
                        <div class="hh-widget-slide">
                            <div class="badge-item-stat void hh-feed-featured-badge">
                                <p class="text-sticker"><i class="text-sticker-icon hh-ph-action-icon ph ph-plus" aria-hidden="true"></i> {{ $badgeXp > 0 ? '+'.$badgeXp.' Exp' : 'Badge' }}</p>
                                @if ($badgeImage)
                                    <img class="badge-item-stat-image" src="{{ $badgeImage }}" alt="{{ $badge->name }}">
                                @elseif ($badgeIcon)
                                    <div class="badge-item-stat-image hh-feed-featured-badge-glyph" aria-hidden="true">{{ $badgeIcon }}</div>
                                @else
                                    <img class="badge-item-stat-image" src="{{ asset('assets/vikinger/img/badge/badge-empty.png') }}" alt="{{ $badge->name }}">
                                @endif
                                <p class="badge-item-stat-title">{{ $badge->name }}</p>
                                <p class="badge-item-stat-text">{{ $quest->description ?: ($badge->description ?: __('ui.badge_unlock_task')) }}</p>
                                <div class="progress-stat medium">
                                    <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $badgePercent }}%"></span></div>
                                    <div class="bar-progress-wrap"><p class="bar-progress-info negative center"><span>{{ $badgeCount }}/{{ $badgeTarget }}</span></p></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="hh-widget-slide">
                            <div class="badge-item-stat void hh-feed-featured-badge">
                                <p class="text-sticker"><i class="text-sticker-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i> {{ __('ui.done') }}</p>
                                <img class="badge-item-stat-image" src="{{ asset('assets/vikinger/img/badge/unlocked-badge.png') }}" alt="Badges">
                                <p class="badge-item-stat-title">{{ __('ui.feed_all_badge_goals_done') }}</p>
                                <p class="badge-item-stat-text">{{ __('ui.feed_no_open_badge_tasks') }}</p>
                                <div class="progress-stat medium">
                                    <div class="progress-stat-bar hh-static-progress"><span style="width: 100%"></span></div>
                                    <div class="bar-progress-wrap"><p class="bar-progress-info negative center"><span>{{ $badgesCount }} {{ __('ui.unlocked_count') }}</span></p></div>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="widget-box hh-feed-vikinger-member-widget">
            <div class="widget-box-settings">
                <div class="post-settings-wrap">
                    <div class="post-settings widget-box-post-settings-dropdown-trigger">
                        <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                    </div>
                    <div class="simple-dropdown widget-box-post-settings-dropdown">
                        <a class="simple-dropdown-link" href="{{ route('members.index') }}">{{ __('ui.all_members') }}</a>
                    </div>
                </div>
            </div>
            <p class="widget-box-title">{{ __('ui.members') }}</p>

            <div class="widget-box-content">
                <div class="user-status-list hh-feed-sidebar-users">
                    @forelse ($sidebarMemberSuggestions as $member)
                        @php
                            $friendship = $viewer->friendshipWith($member);
                        @endphp
                        <div class="user-status request-small">
                            <a class="user-status-avatar" href="{{ route('profile.public', $member) }}">
                                <div class="user-avatar small no-outline">
                                    <div class="user-avatar-content">
                                        <div class="hexagon-image-30-32" data-src="{{ $member->avatarUrl() }}"></div>
                                    </div>
                                    <div class="user-avatar-progress">
                                        <div class="hexagon-progress-40-44"></div>
                                    </div>
                                    <div class="user-avatar-progress-border">
                                        <div class="hexagon-border-40-44"></div>
                                    </div>
                                    <div class="user-avatar-badge">
                                        <div class="user-avatar-badge-border">
                                            <div class="hexagon-22-24"></div>
                                        </div>
                                        <div class="user-avatar-badge-content">
                                            <div class="hexagon-dark-16-18"></div>
                                        </div>
                                        <p class="user-avatar-badge-text">{{ $member->level ?? 1 }}</p>
                                    </div>
                                </div>
                            </a>

                            <p class="user-status-title"><a class="bold" href="{{ route('profile.public', $member) }}">{{ $member->name }}</a></p>
                            <p class="user-status-text small">{{ __('ui.level') }} {{ $member->level ?? 1 }}</p>

                            @unless ($friendship)
                                <div class="action-request-list">
                                    <form class="hh-feed-friend-form" method="post" action="{{ route('friends.store', $member) }}">
                                        @csrf
                                        <button class="action-request accept hh-feed-friend-action" type="submit" aria-label="{{ __('ui.friend_request_send') }}">
                                            <i class="action-request-icon hh-ph-action-icon ph ph-user-plus" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            @endunless
                        </div>
                    @empty
                        <p class="widget-box-text">{{ __('ui.no_new_players_found') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="widget-box hh-feed-vikinger-quests-widget">
            <div class="widget-box-settings">
                <div class="post-settings-wrap">
                    <div class="post-settings widget-box-post-settings-dropdown-trigger">
                        <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                    </div>
                    <div class="simple-dropdown widget-box-post-settings-dropdown">
                        <a class="simple-dropdown-link" href="{{ route('gamification.index') }}">{{ __('ui.all_quests') }}</a>
                    </div>
                </div>
            </div>
            <p class="widget-box-title">{{ __('ui.open_quests') }}</p>

            @php
                $hhQuestProfileProgress = min(100, max(8, $completion));
                $hhQuestSocialProgress = min(100, max(8, $ownPostsCount * 12));
                $hhQuestBuffedProgress = min(100, max(6, (int) (($viewer->xp_total ?? 0) / max(1, (($viewer->level ?? 1) * 250)) * 100)));
                $hhQuestPeopleProgress = min(100, max(0, $viewer->friendsCount() * 20));
                $hhQuestTeamsProgress = min(100, max(8, $viewer->activeTeams()->count() * 34));
            @endphp

            <div class="widget-box-content">
                <div class="quest-preview-list hh-feed-shortcut-list">
                    @if ($hhQuestProfileProgress < 100)
                    <a class="quest-preview" href="{{ route('profile.edit') }}">
                        <div class="quest-preview-info">
                            <img class="quest-preview-image" src="{{ asset('assets/vikinger/img/quest/openq-s.png') }}" alt="Quest">
                            <p class="quest-preview-title">Nothing to hide</p>
                            <p class="quest-preview-text">{{ __('ui.feed_quest_profile') }}</p>
                        </div>
                        <div class="progress-stat">
                            <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $hhQuestProfileProgress }}%"></span></div>
                        </div>
                    </a>
                    @endif

                    @if ($hhQuestSocialProgress < 100)
                    <a class="quest-preview" href="{{ route('feed.index') }}">
                        <div class="quest-preview-info">
                            <img class="quest-preview-image" src="{{ asset('assets/vikinger/img/quest/openq-s.png') }}" alt="Quest">
                            <p class="quest-preview-title">Social King</p>
                            <p class="quest-preview-text">{{ __('ui.feed_quest_share') }}</p>
                        </div>
                        <div class="progress-stat">
                            <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $hhQuestSocialProgress }}%"></span></div>
                        </div>
                    </a>
                    @endif

                    @if ($hhQuestBuffedProgress < 100)
                    <a class="quest-preview" href="{{ route('feed.index') }}">
                        <div class="quest-preview-info">
                            <img class="quest-preview-image" src="{{ asset('assets/vikinger/img/quest/openq-s.png') }}" alt="Quest">
                            <p class="quest-preview-title">Buffed Profile</p>
                            <p class="quest-preview-text">{{ __('ui.feed_quest_active') }}</p>
                        </div>
                        <div class="progress-stat">
                            <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $hhQuestBuffedProgress }}%"></span></div>
                        </div>
                    </a>
                    @endif

                    @if ($hhQuestPeopleProgress < 100)
                    <a class="quest-preview" href="{{ route('members.index') }}">
                        <div class="quest-preview-info">
                            <img class="quest-preview-image" src="{{ asset('assets/vikinger/img/quest/openq-s.png') }}" alt="Quest">
                            <p class="quest-preview-title">Hear the People</p>
                            <p class="quest-preview-text">{{ __('ui.feed_quest_network') }}</p>
                        </div>
                        <div class="progress-stat">
                            <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $hhQuestPeopleProgress }}%"></span></div>
                        </div>
                    </a>
                    @endif

                    @if ($hhQuestTeamsProgress < 100)
                    <a class="quest-preview" href="{{ route('teams.index') }}">
                        <div class="quest-preview-info">
                            <img class="quest-preview-image" src="{{ asset('assets/vikinger/img/quest/openq-s.png') }}" alt="Quest">
                            <p class="quest-preview-title">Store Manager</p>
                            <p class="quest-preview-text">{{ __('ui.feed_quest_team') }}</p>
                        </div>
                        <div class="progress-stat">
                            <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $hhQuestTeamsProgress }}%"></span></div>
                        </div>
                    </a>
                    @endif

                    @if ($hhQuestProfileProgress >= 100 && $hhQuestSocialProgress >= 100 && $hhQuestBuffedProgress >= 100 && $hhQuestPeopleProgress >= 100 && $hhQuestTeamsProgress >= 100)
                        <p class="widget-box-text">{{ __('ui.no_open_quests') }}</p>
                    @endif
                </div>
            </div>

            <a class="widget-box-button button small white" href="{{ route('gamification.index') }}">{{ __('ui.view_all') }}</a>
        </div>
    </div>

    <div class="grid-column">
        <div class="quick-post hh-quick-post-live hh-quick-post-vikinger-finalize hh-feed-composer">
            <div class="quick-post-header">
                <div class="option-items">
                        <div class="option-item active" role="button" tabindex="0" data-hh-feed-composer-status-tab>
                            <i class="option-item-icon hh-ph-action-icon ph ph-note-pencil" aria-hidden="true"></i>
                            <p class="option-item-title">{{ __('ui.status') }}</p>
                        </div>
                        <div class="option-item" role="button" tabindex="0" data-hh-feed-composer-panel-toggle="feeling">
                            <i class="option-item-icon hh-ph-action-icon ph ph-smiley" aria-hidden="true"></i>
                            <p class="option-item-title">{{ __('ui.feed_feeling') }}</p>
                        </div>
                        <label class="option-item hh-option-file-trigger" for="feed_media">
                            <i class="option-item-icon hh-ph-action-icon ph ph-camera" aria-hidden="true"></i>
                            <p class="option-item-title">{{ __('ui.media') }}</p>
                        </label>
                        <div class="option-item">
                            <i class="option-item-icon hh-ph-action-icon ph ph-lock" aria-hidden="true"></i>
                            <p class="option-item-title">{{ __('ui.feed_privacy') }}</p>
                        </div>
                        <div class="option-item" role="button" tabindex="0" data-hh-feed-composer-panel-toggle="background">
                            <i class="option-item-icon hh-ph-action-icon ph ph-palette" aria-hidden="true"></i>
                            <p class="option-item-title">{{ __('ui.feed_background') }}</p>
                        </div>
                        <div class="option-item" role="button" tabindex="0" data-hh-feed-composer-panel-toggle="poll">
                            <i class="option-item-icon hh-ph-action-icon ph ph-list-checks" aria-hidden="true"></i>
                            <p class="option-item-title">{{ __('ui.feed_poll') }}</p>
                        </div>
                        <div class="option-item" role="button" tabindex="0" data-hh-feed-composer-panel-toggle="gif">
                            <i class="option-item-icon hh-ph-action-icon ph ph-gif" aria-hidden="true"></i>
                            <p class="option-item-title">{{ __('ui.feed_gif') }}</p>
                        </div>
                    </div>
            </div>

            <form class="form hh-feed-form" method="post" action="{{ route('feed.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="quick-post-body">
                    <div class="form">
                        <div class="form-row">
                            <div class="form-item">
                                <div class="form-textarea">
                                    <textarea id="feed_body" name="body" maxlength="5000" data-hh-feed-counter="feed-body-limit" data-hh-mention-context="feed" placeholder="{{ __('ui.feed_placeholder', ['name' => $viewer->name]) }}">{{ old('body') }}</textarea>
                                    <p id="feed-body-limit" class="form-textarea-limit-text" data-hh-feed-limit>5000/5000</p>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="gif_provider" value="{{ old('gif_provider') }}" data-hh-feed-gif-provider>
                        <input type="hidden" name="gif_id" value="{{ old('gif_id') }}" data-hh-feed-gif-id>
                        <input type="hidden" name="gif_url" value="{{ old('gif_url') }}" data-hh-feed-gif-url>
                        <input type="hidden" name="gif_preview_url" value="{{ old('gif_preview_url') }}" data-hh-feed-gif-preview-url>
                        <input type="hidden" name="gif_title" value="{{ old('gif_title') }}" data-hh-feed-gif-title>
                        <input type="hidden" name="gif_source_url" value="{{ old('gif_source_url') }}" data-hh-feed-gif-source-url>

                        <input id="feed_media" class="hh-visually-hidden" type="file" name="media[]" accept="image/*,video/mp4,video/webm,video/quicktime" multiple data-hh-feed-media-input data-hh-media-max-files="{{ $feedMediaMaxFiles }}" data-hh-media-max-mb="{{ $feedMediaMaxMb }}">
                        <p class="hh-feed-upload-hint">{{ __('ui.feed_media_upload_hint', ['count' => $feedMediaMaxFiles, 'limit' => $feedMediaMaxMb]) }}</p>

                        <div class="hh-feed-extra-panel hh-feed-extra-panel--background" data-hh-feed-composer-panel="background" hidden>
                            <div class="hh-feed-background-picker" aria-label="{{ __('ui.feed_background') }}">
                                <span class="hh-feed-background-picker__label">{{ __('ui.feed_background') }}</span>
                                @foreach (['none', 'bayou', 'blood', 'gold', 'night'] as $feedBackgroundStyle)
                                    <label class="hh-feed-background-swatch hh-feed-bg-swatch-{{ $feedBackgroundStyle }}" title="{{ __('ui.feed_background_' . $feedBackgroundStyle) }}">
                                        <input type="radio" name="background_style" value="{{ $feedBackgroundStyle }}" @checked(old('background_style', 'none') === $feedBackgroundStyle)>
                                        <span>{{ __('ui.feed_background_' . $feedBackgroundStyle) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="hh-feed-extra-panel hh-feed-extra-panel--feeling" data-hh-feed-composer-panel="feeling" hidden>
                            <div class="hh-feed-feeling-picker" aria-label="{{ __('ui.feed_feeling') }}">
                                <span class="hh-feed-feeling-picker__label">{{ __('ui.feed_feeling') }}</span>
                                <label class="hh-feed-feeling-chip">
                                    <input type="radio" name="feeling_key" value="none" @checked(old('feeling_key', 'none') === 'none')>
                                    <span>{{ __('ui.feed_feeling_none') }}</span>
                                </label>
                                @foreach ($feedFeelings as $feedFeelingKey => $feedFeeling)
                                    <label class="hh-feed-feeling-chip">
                                        <input type="radio" name="feeling_key" value="{{ $feedFeelingKey }}" @checked(old('feeling_key') === $feedFeelingKey)>
                                        <span>{{ $feedFeeling['emoji'] }} {{ __('ui.feed_feeling_' . $feedFeelingKey) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="hh-feed-extra-panel hh-feed-extra-panel--poll" data-hh-feed-composer-panel="poll" hidden>
                            <div class="hh-feed-poll-composer">
                                <div class="hh-feed-poll-composer__head">
                                    <strong>{{ __('ui.feed_poll') }}</strong>
                                    <span>{{ __('ui.feed_poll_composer_hint') }}</span>
                                </div>
                                <input class="hh-feed-poll-input" type="text" name="poll_question" maxlength="180" value="{{ old('poll_question') }}" placeholder="{{ __('ui.feed_poll_question_placeholder') }}">
                                <div class="hh-feed-poll-composer__options">
                                    @for ($pollOptionIndex = 0; $pollOptionIndex < 4; $pollOptionIndex++)
                                        <input class="hh-feed-poll-input" type="text" name="poll_options[{{ $pollOptionIndex }}]" maxlength="180" value="{{ old('poll_options.' . $pollOptionIndex) }}" placeholder="{{ __('ui.feed_poll_option_placeholder', ['number' => $pollOptionIndex + 1]) }}">
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <div class="hh-feed-extra-panel hh-feed-extra-panel--gif" data-hh-feed-composer-panel="gif" hidden>
                            <div class="hh-feed-gif-composer" data-hh-feed-gif-composer data-search-url="{{ route('feed.gifs.search') }}" data-trending-url="{{ route('feed.gifs.trending') }}">
                                <div class="hh-feed-gif-composer__head">
                                    <strong>{{ __('ui.feed_gif') }}</strong>
                                    <span>{{ __('ui.feed_gif_hint') }}</span>
                                </div>
                                <div class="hh-feed-gif-composer__search">
                                    <input class="hh-feed-poll-input" type="search" maxlength="80" placeholder="{{ __('ui.feed_gif_search_placeholder') }}" data-hh-feed-gif-query>
                                    <button class="button small secondary" type="button" data-hh-feed-gif-search>{{ __('ui.feed_gif_search') }}</button>
                                </div>
                                <div class="hh-feed-gif-selected" data-hh-feed-gif-selected hidden>
                                    <img src="" alt="{{ __('ui.feed_gif_selected') }}" data-hh-feed-gif-selected-image>
                                    <button class="button small void" type="button" data-hh-feed-gif-clear>{{ __('ui.feed_gif_remove') }}</button>
                                </div>
                                <div class="hh-feed-gif-results" data-hh-feed-gif-results></div>
                                <p class="hh-feed-gif-attribution">{{ __('ui.feed_gif_powered_by') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hh-feed-media-preview" data-hh-feed-preview hidden>
                    <div class="hh-feed-media-preview-list" data-hh-feed-preview-list></div>
                </div>

                <div class="hh-ai-disclosure-toggle" data-hh-feed-ai-disclosure hidden>
                    <label class="hh-ai-disclosure-switch">
                        <input type="checkbox" name="ai_generated" value="1" data-hh-feed-ai-disclosure-input @checked(old('ai_generated'))>
                        <span class="hh-ai-disclosure-slider" aria-hidden="true"></span>
                        <span class="hh-ai-disclosure-copy">
                            <strong>{{ __('ui.ai_content_toggle_label') }}</strong>
                            <small>{{ __('ui.ai_content_toggle_hint') }}</small>
                        </span>
                    </label>
                </div>

                <div class="quick-post-footer">
                    <div class="quick-post-footer-actions hh-quick-post-footer-meta">
                        <div class="quick-post-footer-action text-tooltip-tft-medium" role="button" tabindex="0" data-title="{{ __('ui.feed_feeling') }}" data-hh-feed-composer-panel-toggle="feeling">
                            <i class="quick-post-footer-action-icon hh-ph-action-icon ph ph-smiley" aria-hidden="true"></i>
                        </div>

                        <label class="quick-post-footer-action text-tooltip-tft-medium" data-title="{{ __('ui.feed_attach_media') }}" for="feed_media">
                            <i class="quick-post-footer-action-icon hh-ph-action-icon ph ph-camera" aria-hidden="true"></i>
                        </label>

                        <div class="quick-post-footer-action text-tooltip-tft-medium" role="button" tabindex="0" data-title="{{ __('ui.feed_background') }}" data-hh-feed-composer-panel-toggle="background">
                            <i class="quick-post-footer-action-icon hh-ph-action-icon ph ph-palette" aria-hidden="true"></i>
                        </div>

                        <div class="quick-post-footer-action text-tooltip-tft-medium" role="button" tabindex="0" data-title="{{ __('ui.feed_gif') }}" data-hh-feed-composer-panel-toggle="gif">
                            <i class="quick-post-footer-action-icon hh-ph-action-icon ph ph-gif" aria-hidden="true"></i>
                        </div>

                        <div class="quick-post-footer-action text-tooltip-tft-medium" role="button" tabindex="0" data-title="{{ __('ui.feed_poll') }}" data-hh-feed-composer-panel-toggle="poll">
                            <i class="quick-post-footer-action-icon hh-ph-action-icon ph ph-list-checks" aria-hidden="true"></i>
                        </div>

                        <div class="form-select hh-vk-visibility-select">
                            <select id="feed_visibility" name="visibility">
                                <option value="public" @selected(old('visibility', 'public') === 'public')>{{ __('ui.visibility_public') }}</option>
                                <option value="followers" @selected(old('visibility') === 'followers')>{{ __('ui.followers') }}</option>
                                <option value="private" @selected(old('visibility') === 'private')>{{ __('ui.visibility_private_me') }}</option>
                            </select>
                            <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                        </div>
                    </div>

                    <div class="quick-post-footer-actions">
                        <button class="button small void" type="reset">{{ __('ui.discard') }}</button>
                        <button class="button small secondary hh-button-reset" type="submit">{{ __('ui.post_submit') }}</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="simple-tab-items hh-feed-tabs hh-vk-feed-tabs-finalize">
            <form class="form" method="get" action="{{ route('feed.index') }}">
                <div class="form-select">
                    <select id="newsfeed-filter-category" name="filter" onchange="this.form.submit()">
                        <option value="all" @selected($feedFilter === 'all')>{{ __('ui.all_updates') }}</option>
                        <option value="mentions" @selected($feedFilter === 'mentions')>Mentions</option>
                        <option value="friends" @selected($feedFilter === 'friends')>{{ __('ui.friends') }}</option>
                        <option value="teams" @selected($feedFilter === 'teams')>Teams</option>
                        <option value="media" @selected($feedFilter === 'media')>{{ __('ui.media') }}</option>
                    </select>
                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                </div>
            </form>

            <a class="simple-tab-item {{ $feedFilter === 'all' ? 'active' : '' }}" href="{{ route('feed.index') }}">{{ __('ui.all_updates') }}</a>
            <a class="simple-tab-item {{ $feedFilter === 'mentions' ? 'active' : '' }}" href="{{ route('feed.index', ['filter' => 'mentions']) }}">Mentions</a>
            <a class="simple-tab-item {{ $feedFilter === 'friends' ? 'active' : '' }}" href="{{ route('feed.index', ['filter' => 'friends']) }}">{{ __('ui.friends') }}</a>
            <a class="simple-tab-item {{ $feedFilter === 'teams' ? 'active' : '' }}" href="{{ route('feed.index', ['filter' => 'teams']) }}">Teams</a>
            <a class="simple-tab-item {{ $feedFilter === 'media' ? 'active' : '' }}" href="{{ route('feed.index', ['filter' => 'media']) }}">{{ __('ui.media') }}</a>
        </div>

        <div class="hh-feed-list-vikinger">
            @forelse ($posts as $post)
                @if (($feedPage ?? 1) > 1 && ($postsPerLoad ?? 10) > 0 && $loop->iteration === ((($feedPage ?? 1) - 1) * ($postsPerLoad ?? 10)) + 1)
                    <span id="hh-feed-page-{{ $feedPage }}" class="hh-feed-page-anchor" aria-hidden="true"></span>
                @endif

                @include('feed.partials.post-card', ['post' => $post])
            @empty
                <div class="widget-box">
                    <p class="widget-box-title">{{ __('ui.feed_empty_title') }}</p>
                    <p class="widget-box-text">
                        @switch($feedFilter)
                            @case('mentions')
                                {{ __('ui.feed_empty_mentions') }}
                                @break
                            @case('friends')
                                {{ __('ui.feed_empty_friends') }}
                                @break
                            @case('teams')
                                {{ __('ui.feed_empty_teams') }}
                                @break
                            @case('media')
                                {{ __('ui.feed_empty_media') }}
                                @break
                            @default
                                {{ __('ui.feed_empty_all') }}
                        @endswitch
                    </p>
                </div>
            @endforelse
        </div>

        @if (($hasMoreFeedPosts ?? false) || (($feedPage ?? 1) > 1))
            <div class="section-pager-bar hh-vk-pager hh-feed-load-more-bar">
                <p class="hh-feed-load-more-info">
                    {{ __('ui.feed_posts_shown', ['shown' => $posts->count(), 'total' => $posts->total()]) }}
                </p>

                <div class="hh-feed-load-more-actions">
                    @if ($hasMoreFeedPosts ?? false)
                        <a class="button small secondary hh-feed-load-more-button" href="{{ $nextFeedPageUrl }}#hh-feed-page-{{ ($feedPage ?? 1) + 1 }}">{{ __('ui.load_more') }}</a>
                    @endif

                    @if (($feedPage ?? 1) > 1)
                        <a class="button small white hh-feed-load-more-reset" href="{{ $resetFeedPageUrl }}">{{ __('ui.reduce_again') }}</a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="grid-column">
        <div class="stats-box stat-posts-created hh-feed-stats-box">
            <div class="stats-box-value-wrap">
                <p class="stats-box-value">{{ $posts->total() }}</p>
                <div class="stats-box-diff"><div class="stats-box-diff-icon positive"><i class="hh-ph-action-icon ph ph-plus" aria-hidden="true"></i></div><p class="stats-box-diff-value">Feed</p></div>
            </div>
            <p class="stats-box-title">{{ __('ui.posts_created') }}</p>
            <p class="stats-box-text">{{ __('ui.visible_in_newsfeed') }}</p>
        </div>

        <div class="widget-box hh-widget-slider" data-hh-widget-slider>
            <div class="widget-box-controls">
                <div class="slider-controls">
                    <button class="slider-control left" type="button" data-hh-slider-prev aria-label="{{ __('ui.previous_reactions') }}"><svg class="slider-control-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg></button>
                    <button class="slider-control right" type="button" data-hh-slider-next aria-label="{{ __('ui.more_reactions') }}"><svg class="slider-control-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg></button>
                </div>
            </div>
            <p class="widget-box-title">{{ __('ui.reactions_received') }}</p>

            <div class="widget-box-content hh-widget-slider-viewport">
                <div class="hh-widget-slider-track" data-hh-slider-track>
                    @foreach ($reactionStatSlides as $reactionSlide)
                        <div class="hh-widget-slide">
                            <div class="reaction-stats-list">
                                @foreach ($reactionSlide as $reactionType => $reactionLabel)
                                    <div class="reaction-stat" data-hh-reaction-stat="{{ $reactionType }}">
                                        <img class="reaction-stat-image" src="{{ asset('assets/vikinger/img/reaction/' . $reactionType . '.png') }}" alt="{{ $reactionLabel }}">
                                        <p class="reaction-stat-title">{{ (int) ($reactionStats[$reactionType] ?? 0) }}</p>
                                        <p class="reaction-stat-text">{{ $reactionLabel }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="widget-box hh-feed-vikinger-activity-widget">
            <div class="widget-box-settings">
                <div class="post-settings-wrap">
                    <div class="post-settings widget-box-post-settings-dropdown-trigger">
                        <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                    </div>
                    <div class="simple-dropdown widget-box-post-settings-dropdown">
                        <a class="simple-dropdown-link" href="{{ route('feed.index', ['filter' => 'friends']) }}">{{ __('ui.open_friends_feed') }}</a>
                    </div>
                </div>
            </div>
            <p class="widget-box-title">{{ __('ui.friends_activity') }}</p>
            <div class="widget-box-content">
                <div class="user-status-list hh-feed-activity-list">
                    @forelse ($friendsActivityItems as $activityItem)
                        @php
                            $activityUser = $activityItem['user'];
                            $activityUrl = $activityItem['url'] ?? route('feed.index', ['filter' => 'friends']);
                            $activityDate = $activityItem['date'] ?? null;
                        @endphp
                        <div class="user-status">
                            <a class="user-status-avatar" href="{{ route('profile.public', $activityUser) }}">
                                <div class="user-avatar small no-outline">
                                    <div class="user-avatar-content">
                                        <div class="hexagon-image-30-32" data-src="{{ $activityUser->avatarUrl() }}"></div>
                                    </div>
                                    <div class="user-avatar-progress">
                                        <div class="hexagon-progress-40-44"></div>
                                    </div>
                                    <div class="user-avatar-progress-border">
                                        <div class="hexagon-border-40-44"></div>
                                    </div>
                                    <div class="user-avatar-badge">
                                        <div class="user-avatar-badge-border">
                                            <div class="hexagon-22-24"></div>
                                        </div>
                                        <div class="user-avatar-badge-content">
                                            <div class="hexagon-dark-16-18"></div>
                                        </div>
                                        <p class="user-avatar-badge-text">{{ $activityUser->level ?? 1 }}</p>
                                    </div>
                                </div>
                            </a>
                            <p class="user-status-title"><a class="bold" href="{{ route('profile.public', $activityUser) }}">{{ $activityUser->name }}</a> {{ $activityItem['action'] }} <a class="highlighted" href="{{ $activityUrl }}">{{ $activityItem['title'] }}</a></p>
                            <p class="user-status-timestamp">{{ $activityDate ? $activityDate->diffForHumans() : __('ui.just_now') }}</p>
                        </div>
                    @empty
                        <p class="widget-box-text">{{ __('ui.no_friend_activity') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="widget-box hh-feed-vikinger-groups-widget">
            <div class="widget-box-settings">
                <div class="post-settings-wrap">
                    <div class="post-settings widget-box-post-settings-dropdown-trigger">
                        <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                    </div>
                    <div class="simple-dropdown widget-box-post-settings-dropdown">
                        <a class="simple-dropdown-link" href="{{ route('teams.index') }}">{{ __('ui.all_teams') }}</a>
                    </div>
                </div>
            </div>
            <p class="widget-box-title">Teams</p>
            <div class="widget-box-content">
                <div class="user-status-list hh-feed-group-list">
                    @forelse ($publicRecruitingTeams->take(5) as $team)
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
                                <a class="action-request accept" href="{{ route('teams.show', $team) }}" aria-label="{{ __('ui.view_team') }}">
                                    <i class="action-request-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="user-status request-small">
                            <a class="user-status-avatar" href="{{ route('teams.index') }}">
                                <div class="user-avatar small no-border">
                                    <div class="user-avatar-content">
                                        <div class="hexagon-image-40-44" data-src="{{ asset('assets/vikinger/img/avatar/01-social.png') }}"></div>
                                    </div>
                                </div>
                            </a>
                            <p class="user-status-title"><a class="bold" href="{{ route('teams.index') }}">{{ __('ui.discover_teams') }}</a></p>
                            <p class="user-status-text small">{{ __('ui.no_public_teams') }}</p>
                            <div class="action-request-list">
                                <a class="action-request accept" href="{{ route('teams.index') }}" aria-label="{{ __('ui.teams_view') }}">
                                    <i class="action-request-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
