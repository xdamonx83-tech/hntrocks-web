@extends('layouts.app')

@section('title', $team->name.' · hnt.rocks Team')

@section('content')
@php
    $teamFeedMediaMaxMb = (int) ceil(((int) config('hunthub.upload_limits.team_feed_media_kb', 102400)) / 1024);
    $teamFeedMediaMaxFiles = (int) config('hunthub.upload_limits.team_feed_media_count', 12);
    $activeMembers = $team->members->where('status', 'active')->values();
    $pendingMembers = $team->members->where('status', 'pending')->values();
    $teamOrganizers = $activeMembers->filter(fn ($member) => in_array($member->role, ['owner', 'officer'], true))->values();
    $teamFriendshipMap = $teamFriendshipMap ?? collect();
    $teamLfgPosts = $teamLfgPosts ?? collect();
    $teamLfgOpenCount = $teamLfgOpenCount ?? 0;
    $teamFeedPosts = $teamFeedPosts ?? collect();
    $canPostToTeam = (bool) ($canPostToTeam ?? false);
    $isRecruiting = $team->recruitment_status === 'open';
    $isPrivate = $team->visibility === 'private';
    $teamCreatedDate = $team->created_at ? $team->created_at->format('d.m.Y') : '—';
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

<section class="hh-team-vikinger">
    <div class="profile-header v2 hh-team-profile-header">
        <figure class="profile-header-cover liquid">
            <img src="{{ $team->coverUrl() }}" alt="{{ __('ui.team_cover') }}: {{ $team->name }}">
        </figure>

        <div class="profile-header-info">
            <div class="user-short-description big">
                <a class="user-short-description-avatar user-avatar big no-stats" href="{{ route('teams.show', $team) }}">
                    <div class="user-avatar-border"><div class="hexagon-148-164"></div></div>
                    <div class="user-avatar-content">
                        <div class="hexagon-image-124-136" data-src="{{ $team->avatarUrl() }}"></div>
                    </div>
                </a>

                <a class="user-short-description-avatar user-short-description-avatar-mobile user-avatar medium no-stats" href="{{ route('teams.show', $team) }}">
                    <div class="user-avatar-border"><div class="hexagon-120-130"></div></div>
                    <div class="user-avatar-content">
                        <div class="hexagon-image-100-110" data-src="{{ $team->avatarUrl() }}"></div>
                    </div>
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
                <div class="user-stat big"><p class="user-stat-title">{{ $teamLfgOpenCount }}</p><p class="user-stat-text">Team-LFG</p></div>
                <div class="user-stat big"><p class="user-stat-title">{{ $pendingMembers->count() }}</p><p class="user-stat-text">{{ __('ui.team_requests') }}</p></div>
            </div>

            <div class="tag-sticker {{ $isRecruiting ? 'hh-team-tag-open' : 'hh-team-tag-closed' }}" title="{{ $team->recruitmentLabel() }}">
                <i class="tag-sticker-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
            </div>

            <div class="profile-header-info-actions hh-team-header-actions">
                @if ($canManage)
                    <a class="profile-header-info-action button secondary text-tooltip-tft" href="{{ route('teams.manage') }}" title="{{ __('ui.manage_teams') }}" data-title="{{ __('ui.manage_teams') }}">
                        <i class="hh-ph-action-icon ph ph-gear-six" aria-hidden="true"></i>
                    </a>
                    <a class="profile-header-info-action button text-tooltip-tft" href="{{ route('teams.edit', $team) }}" title="{{ __('ui.team_edit') }}" data-title="{{ __('ui.team_edit') }}">
                        <i class="hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                    </a>
                @elseif (! $viewerMembership && $isRecruiting)
                    <a class="profile-header-info-action button secondary text-tooltip-tft" href="#team-join" title="{{ __('ui.team_join_action') }}" data-title="{{ __('ui.team_join_action') }}">
                        <i class="hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                    </a>
                @elseif ($viewerMembership?->status === 'pending')
                    <span class="profile-header-info-action button white hh-team-state-button">{{ __('ui.team_request_pending') }}</span>
                @elseif ($viewerMembership && $viewerMembership->role !== 'owner')
                    <form method="post" action="{{ route('teams.leave', $team) }}" onsubmit="return confirm('{{ __('ui.team_leave_confirm') }}')">
                        @csrf
                        <button class="profile-header-info-action button white text-tooltip-tft" type="submit" title="{{ __('ui.team_leave') }}" data-title="{{ __('ui.team_leave') }}">
                            <i class="hh-ph-action-icon ph ph-x" aria-hidden="true"></i>
                        </button>
                    </form>
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
            <a class="section-menu-item active" href="#team-timeline"><i class="section-menu-item-icon hh-ph-action-icon ph ph-clock-counter-clockwise" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.team_timeline') }}</p></a>
            <a class="section-menu-item" href="#team-info"><i class="section-menu-item-icon hh-ph-action-icon ph ph-info" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.team_info_section') }}</p></a>
            <a class="section-menu-item" href="{{ route('teams.members', $team) }}"><i class="section-menu-item-icon hh-ph-action-icon ph ph-users" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.team_members') }}</p></a>
            <a class="section-menu-item" href="#team-lfg"><i class="section-menu-item-icon hh-ph-action-icon ph ph-trophy" aria-hidden="true"></i><p class="section-menu-item-text">Team-LFG</p></a>
            @if ($canManage)
                <a class="section-menu-item" href="#team-requests"><i class="section-menu-item-icon hh-ph-action-icon ph ph-chats-circle" aria-hidden="true"></i><p class="section-menu-item-text">{{ __('ui.team_requests') }}</p></a>
            @endif
        </div>
    </nav>

    <div class="grid grid-3-6-3 mobile-prefer-content hh-team-vikinger-grid">
        <div class="grid-column">
            <div id="team-info" class="widget-box hh-team-widget hh-team-info-widget">
                <p class="widget-box-title">{{ __('ui.team_info') }}</p>
                <div class="widget-box-content">
                    <p class="paragraph hh-team-info-description">
                        @if ($team->description)
                            {!! nl2br(e($team->description)) !!}
                        @else
                            {{ __('ui.team_no_description') }}
                        @endif
                    </p>

                    <div class="information-line-list hh-team-info-lines">
                        <div class="information-line"><p class="information-line-title">{{ __('ui.created') }}</p><p class="information-line-text">{{ $teamCreatedDate }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.type') }}</p><p class="information-line-text">{{ $team->visibilityLabel() }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.status') }}</p><p class="information-line-text">{{ $team->recruitmentLabel() }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.platform') }}</p><p class="information-line-text">{{ $team->platform ?: __('ui.no_information') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.region') }}</p><p class="information-line-text">{{ $team->region ?: __('ui.no_information') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.language') }}</p><p class="information-line-text">{{ $team->language ?: __('ui.no_information') }}</p></div>
                    </div>
                </div>
            </div>

            <div id="team-members" class="widget-box hh-team-widget hh-team-members-widget">
                <div class="widget-box-settings">
                    <div class="post-settings-wrap">
                        <div class="post-settings widget-box-post-settings-dropdown-trigger"><i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i></div>
                        <div class="simple-dropdown widget-box-post-settings-dropdown"><a class="simple-dropdown-link" href="{{ route('teams.members', $team) }}">{{ __('ui.all_members') }}</a></div>
                    </div>
                </div>
                <p class="widget-box-title">{{ __('ui.team_members') }} <span class="highlighted">{{ $activeMembers->count() }}</span></p>
                <div class="widget-box-content">
                    <div class="user-status-list hh-team-member-status-list">
                        @forelse ($activeMembers->take(5) as $member)
                            @php
                                $memberFriendship = $teamFriendshipMap->get($member->user_id);
                                $memberFriendCount = $member->user->friendsCount();
                                $showAddFriend = auth()->id() !== $member->user_id && (! $memberFriendship || $memberFriendship->isDeclined());
                            @endphp
                            <div class="user-status request-small hh-team-member-status">
                                <a class="user-status-avatar" href="{{ route('profile.public', $member->user) }}">
                                    <div class="user-avatar small no-outline">
                                        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $member->user->avatarUrl() }}"></div></div>
                                        <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                                        <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                                        <div class="user-avatar-badge">
                                            <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                                            <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                                            <p class="user-avatar-badge-text">{{ $member->user->level ?? 1 }}</p>
                                        </div>
                                    </div>
                                </a>
                                <p class="user-status-title">
                                    <a class="bold" href="{{ route('profile.public', $member->user) }}">{{ $member->user->name }}</a>
                                </p>
                                <p class="user-status-text small">
                                    <a href="{{ route('profile.public', $member->user) }}">{{ '@'.$member->user->username }}</a>
                                    · {{ $member->roleLabel() }}
                                    · {{ $memberFriendCount }} {{ $memberFriendCount === 1 ? __('ui.friend_singular') : __('ui.friend_plural') }}
                                </p>

                                @if ($showAddFriend)
                                    <div class="action-request-list">
                                        <form method="post" action="{{ route('friends.store', $member->user) }}">
                                            @csrf
                                            <button class="action-request accept text-tooltip-tft" type="submit" title="{{ __('ui.profile_add_friend') }}" data-title="{{ __('ui.profile_add_friend') }}" aria-label="{{ __('ui.profile_add_friend') }}">
                                                <i class="action-request-icon hh-ph-action-icon ph ph-user-plus" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="widget-box-text">{{ __('ui.team_active_members_empty') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="widget-box hh-team-widget hh-team-organizers-widget">
                <p class="widget-box-title">{{ __('ui.team_organizers') }}</p>
                <div class="widget-box-content">
                    <div class="user-status-list hh-team-organizer-status-list">
                        @forelse ($teamOrganizers->take(4) as $member)
                            <div class="user-status hh-team-organizer-status">
                                <a class="user-status-avatar" href="{{ route('profile.public', $member->user) }}">
                                    <div class="user-avatar small no-outline">
                                        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $member->user->avatarUrl() }}"></div></div>
                                        <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                                        <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                                        <div class="user-avatar-badge">
                                            <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                                            <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                                            <p class="user-avatar-badge-text">{{ $member->user->level ?? 1 }}</p>
                                        </div>
                                    </div>
                                </a>
                                <p class="user-status-title"><a class="bold" href="{{ route('profile.public', $member->user) }}">{{ $member->user->name }}</a></p>
                                <p class="user-status-text small">{{ $member->roleLabel() }}</p>
                            </div>
                        @empty
                            <p class="widget-box-text">{{ __('ui.team_organizers_empty') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @if (! $viewerMembership && $isRecruiting)
                <div id="team-join" class="widget-box hh-team-join-widget">
                    <p class="widget-box-title">{{ __('ui.join_team') }}</p>
                    <div class="widget-box-content">
                        <p class="widget-box-text">{{ __('ui.team_join_intro') }}</p>
                        <form method="post" action="{{ route('teams.join', $team) }}" class="hh-team-join-form">
                            @csrf
                            <div class="form-input small full textarea">
                                <textarea id="message" name="message" rows="4" maxlength="500" placeholder="{{ __('ui.team_join_placeholder') }}"></textarea>
                            </div>
                            <button class="button secondary full" type="submit">{{ __('ui.send_request') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div id="team-timeline" class="grid-column">
            @if ($canPostToTeam)
                <div class="quick-post hh-quick-post-live hh-quick-post-vikinger-finalize hh-team-feed-composer">
                    <div class="quick-post-header">
                        <div class="option-items">
                            <div class="option-item active">
                                <i class="option-item-icon hh-ph-action-icon ph ph-note-pencil" aria-hidden="true"></i>
                                <p class="option-item-title">{{ __('ui.post_type_status') }}</p>
                            </div>

                            <div class="option-item">
                                <i class="option-item-icon hh-ph-action-icon ph ph-article" aria-hidden="true"></i>
                                <p class="option-item-title">{{ __('ui.post_type_blog') }}</p>
                            </div>

                            <div class="option-item">
                                <i class="option-item-icon hh-ph-action-icon ph ph-chart-pie-slice" aria-hidden="true"></i>
                                <p class="option-item-title">{{ __('ui.post_type_poll') }}</p>
                            </div>
                        </div>
                    </div>

                    <form class="form hh-team-feed-form" method="post" action="{{ route('teams.feed.store', $team) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="quick-post-body">
                            <div class="form-row">
                                <div class="form-item">
                                    <div class="form-textarea">
                                        <textarea id="team-feed-body" name="body" rows="4" maxlength="1000" data-hh-team-feed-counter="team-feed-limit" data-hh-mention-context="team_feed" data-hh-mention-team-id="{{ $team->id }}" placeholder="{{ __('ui.team_feed_placeholder', ['name' => auth()->user()->name, 'team' => $team->name]) }}">{{ old('body') }}</textarea>
                                        <p id="team-feed-limit" class="form-textarea-limit-text" data-hh-team-feed-limit>1000/1000</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hh-team-feed-media-preview" data-hh-team-feed-preview hidden>
                            <div class="hh-team-feed-media-preview-list" data-hh-team-feed-preview-list></div>
                        </div>

                        <div class="hh-ai-disclosure-toggle" data-hh-team-feed-ai-disclosure hidden>
                            <label class="hh-ai-disclosure-switch">
                                <input type="checkbox" name="ai_generated" value="1" data-hh-team-feed-ai-disclosure-input @checked(old('ai_generated'))>
                                <span class="hh-ai-disclosure-slider" aria-hidden="true"></span>
                                <span class="hh-ai-disclosure-copy">
                                    <strong>{{ __('ui.ai_content_toggle_label') }}</strong>
                                    <small>{{ __('ui.ai_content_toggle_hint') }}</small>
                                </span>
                            </label>
                        </div>

                        <div class="quick-post-footer">
                            <div class="quick-post-footer-actions">
                                <label class="quick-post-footer-action text-tooltip-tft-medium hh-team-feed-upload" data-title="{{ __('ui.attach_image_video') }}" for="team-feed-media" data-hh-team-feed-add-media>
                                    <i class="quick-post-footer-action-icon hh-ph-action-icon ph ph-camera" aria-hidden="true"></i>
                                </label>

                                <span class="quick-post-footer-action text-tooltip-tft-medium" data-title="{{ __('ui.attach_video') }}">
                                    <i class="quick-post-footer-action-icon hh-ph-action-icon ph ph-gif" aria-hidden="true"></i>
                                </span>

                                <span class="quick-post-footer-action text-tooltip-tft-medium" data-title="{{ __('ui.tags_later') }}">
                                    <i class="quick-post-footer-action-icon hh-ph-action-icon ph ph-tag" aria-hidden="true"></i>
                                </span>
                            </div>

                            <div class="quick-post-footer-actions">
                                <input id="team-feed-media" class="hh-visually-hidden" type="file" name="media[]" accept="image/*,video/mp4,video/webm,video/quicktime" multiple data-hh-team-feed-media-input data-hh-media-max-files="{{ $teamFeedMediaMaxFiles }}" data-hh-media-max-mb="{{ $teamFeedMediaMaxMb }}">
                                <button class="button small void" type="reset">{{ __('ui.discard') }}</button>
                                <button class="button small secondary" type="submit">{{ __('ui.post') }}</button>
                            </div>
                            <p class="hh-feed-upload-hint hh-team-feed-upload-hint">{{ __('ui.feed_media_upload_hint', ['count' => $teamFeedMediaMaxFiles, 'limit' => $teamFeedMediaMaxMb]) }}</p>
                        </div>
                    </form>
                </div>
            @endif

            <div class="hh-team-feed-list">
                @forelse ($teamFeedPosts as $post)
                    @include('feed.partials.post-card', ['post' => $post, 'showComments' => false])
                @empty
                    <div class="widget-box hh-team-empty-feed">
                        <p class="widget-box-title">{{ __('ui.team_updates_empty_title') }}</p>
                        <div class="widget-box-content"><p class="paragraph">{{ __('ui.team_updates_empty_text') }}</p></div>
                    </div>
                @endforelse
            </div>


            <div id="team-lfg" class="widget-box">
                <div class="widget-box-actions">
                    <div class="widget-box-action"><p class="widget-box-title">Team-LFG</p></div>
                    <div class="widget-box-action"><a class="button small white" href="{{ route('team-lfg.index') }}">{{ __('ui.view_all') }}</a></div>
                </div>
                <div class="widget-box-content">
                    <div class="hh-team-lfg-list">
                        @forelse ($teamLfgPosts as $post)
                            <article class="hh-team-lfg-item">
                                <div>
                                    <p class="hh-team-lfg-kicker">{{ $post->typeLabel() }} · {{ $post->statusLabel() }}</p>
                                    <h3><a href="{{ route('team-lfg.show', $post) }}">{{ $post->title }}</a></h3>
                                    <p>{{ str($post->body)->limit(150) }}</p>
                                    <div class="hh-team-lfg-meta">
                                        @foreach (array_filter([$post->platform, $post->playstyle, $post->region, $post->language, $post->voiceLabel()]) as $tag)
                                            <span>{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <a class="button small secondary" href="{{ route('team-lfg.show', $post) }}">{{ __('ui.open') }}</a>
                            </article>
                        @empty
                            <p class="widget-box-text">{{ __('ui.team_lfg_empty_text') }}</p>
                            @if ($canManage)
                                <a class="button small secondary" href="{{ route('team-lfg.create') }}">{{ __('ui.team_lfg_create') }}</a>
                            @endif
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-column">
            <div class="widget-box hh-team-stats-widget">
                <p class="widget-box-title">{{ __('ui.team_status') }}</p>
                <div class="widget-box-content">
                    <div class="progress-arc-summary hh-progress-summary-fallback">
                        <div class="progress-arc-wrap hh-progress-arc-plain" style="--hh-progress: {{ $isRecruiting ? 100 : 35 }};"><div class="hh-progress-arc-value">{{ $isRecruiting ? __('ui.open_short') : __('ui.closed_short') }}</div></div>
                        <div class="progress-arc-summary-info">
                            <p class="progress-arc-summary-title">{{ $team->recruitmentLabel() }}</p>
                            <p class="progress-arc-summary-subtitle">{{ __('ui.recruiting') }}</p>
                            <p class="progress-arc-summary-text">{{ $isRecruiting ? __('ui.team_recruiting_open_text') : __('ui.team_recruiting_closed_text') }}</p>
                        </div>
                    </div>
                </div>
                @if ($canManage)
                    <a class="widget-box-button button small white" href="{{ route('teams.edit', $team) }}">{{ __('ui.team_edit') }}</a>
                @endif
            </div>

            @if ($canManage)
                <div id="team-requests" class="widget-box">
                    <p class="widget-box-title">{{ __('ui.join_requests') }} <span class="highlighted">{{ $pendingMembers->count() }}</span></p>
                    <div class="widget-box-content">
                        <div class="hh-team-request-list">
                            @forelse ($pendingMembers as $member)
                                <div class="hh-team-request-card">
                                    <a class="user-status request-small hh-team-member-status" href="{{ route('profile.public', $member->user) }}">
                                        <div class="user-status-avatar">
                                            <div class="user-avatar small no-outline"><div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $member->user->avatarUrl() }}" style="background-image:url('{{ $member->user->avatarUrl() }}');"></div></div></div>
                                        </div>
                                        <p class="user-status-title"><span class="bold">{{ $member->user->name }}</span></p>
                                        <p class="user-status-text small">{{ $member->message ?: __('ui.no_message_given') }}</p>
                                    </a>
                                    <div class="hh-team-request-actions">
                                        <form method="post" action="{{ route('teams.requests.accept', [$team, $member]) }}">@csrf<button class="button small secondary" type="submit">{{ __('ui.accept') }}</button></form>
                                        <form method="post" action="{{ route('teams.requests.reject', [$team, $member]) }}">@csrf<button class="button small white" type="submit">{{ __('ui.reject') }}</button></form>
                                    </div>
                                </div>
                            @empty
                                <p class="widget-box-text">{{ __('ui.join_requests_empty') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            <div class="widget-box">
                <p class="widget-box-title">{{ __('ui.team_rules') }}</p>
                <div class="widget-box-content">
                    <ol class="ordered-item-list hh-team-rules-list">
                        <li class="ordered-item"><p class="ordered-item-bullet">01-</p><p class="ordered-item-text">{{ __('ui.team_rule_respect') }}</p></li>
                        <li class="ordered-item"><p class="ordered-item-bullet">02-</p><p class="ordered-item-text">{{ __('ui.team_rule_no_hate') }}</p></li>
                        <li class="ordered-item"><p class="ordered-item-bullet">03-</p><p class="ordered-item-text">{{ __('ui.team_rule_team_first') }}</p></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
