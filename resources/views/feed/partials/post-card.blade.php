@php
    $viewer = auth()->user();
    $viewerReaction = $post->viewerReaction;
    $viewerReactionType = $viewerReaction?->type;
    $viewerBookmarked = (bool) $post->viewerBookmark;
    $canEdit = $post->user_id === $viewer?->id;
    $canPinPost = $viewer?->isAdmin() === true;
    $mediaItems = $post->media;
    $hasMedia = $mediaItems->isNotEmpty();
    $hasVideo = $hasMedia && $mediaItems->contains(fn ($media) => $media->isVideo());
    $imageMediaItems = $mediaItems->filter(fn ($media) => $media->isImage())->values();
    $imageMediaCount = $imageMediaItems->count();
    $visibleMediaItems = $mediaItems->take(5)->values();
    $visibleMediaCount = $visibleMediaItems->count();
    $hiddenMediaCount = max($mediaItems->count() - $visibleMediaCount, 0);
    $postKind = $hasVideo ? __('ui.post_kind_video') : ($hasMedia ? ($imageMediaCount > 1 ? __('ui.post_kind_photos') : __('ui.post_kind_photo')) : __('ui.post_kind_update'));
    $postTeam = $post->team;
    $sharedPost = $post->sharedPost;
    $isSharedPost = $sharedPost !== null;
    $sharePreviewPost = $sharedPost ?: $post;
    $shareModalId = 'hh-share-modal-' . $post->id;
    $profileRoute = $post->user_id === $viewer?->id ? route('profile.show') : route('profile.public', $post->user);
    $showComments = $showComments ?? false;
    $showAllComments = $showAllComments ?? false;
    $reactionTypes = ['like' => 'Like', 'love' => 'Love', 'dislike' => 'Dislike', 'happy' => 'Happy', 'funny' => 'Funny', 'wow' => 'Wow', 'angry' => 'Angry', 'sad' => 'Sad'];
    $reactionCount = (int) ($post->reactions_count ?? 0);
    $commentCount = (int) ($post->comments_count ?? 0);
    $bookmarkCount = (int) ($post->bookmarks_count ?? 0);
    $shareCount = (int) ($post->shares_count ?? 0);
    $reactionGroups = $post->relationLoaded('reactions') ? $post->reactions->groupBy('type')->map->count() : collect();
    $topReactionTypes = $reactionGroups->sortDesc()->keys()->take(3);
    $primaryReactionType = $viewerReactionType ?: ($topReactionTypes->first() ?: 'like');
    $allComments = $post->comments;
    $rootCommentQuery = $allComments->whereNull('parent_id')->values();
    $rootComments = $showAllComments ? $rootCommentQuery : $rootCommentQuery->take(3);
    $hiddenRootComments = $showAllComments ? collect() : $rootCommentQuery->slice(3)->values();
    $hiddenRootCommentCount = $hiddenRootComments->count();
    $repliesByParent = $allComments->whereNotNull('parent_id')->groupBy('parent_id');
    $displayedCommentCount = $rootComments->count() + $allComments->whereIn('parent_id', $rootComments->pluck('id'))->count();
    $mentionContext = $post->isTeamPost() ? 'team_feed' : 'feed';
    $commentMentionContext = $post->isTeamPost() ? 'team_feed_comment' : 'feed_comment';
    $mentionTeamId = $post->isTeamPost() ? $post->team_id : null;
    $reportedFeedKeys = $reportedFeedKeys ?? collect();
    $postAlreadyReported = $reportedFeedKeys->has('feed_post:' . $post->id);
    $feedTranslationService = app(\App\Services\Translation\FeedTranslationService::class);
    $postTranslationMeta = $feedTranslationService->translationMeta($post->body, $post->source_language ?? null, app()->getLocale());
    $postAiLabelVisible = $post->hasVisibleAiContentLabel();
    $backgroundStyleClass = $post->backgroundStyleClass();
    $feelingMeta = $post->feelingMeta();
    $poll = $post->poll;
    $pollOptions = $poll?->options ?? collect();
    $pollVotes = $poll?->votes ?? collect();
    $pollTotalVotes = $pollVotes->count();
    $viewerPollVote = $viewer ? $pollVotes->firstWhere('user_id', $viewer->id) : null;
    $viewerPollOptionId = $viewerPollVote?->feed_post_poll_option_id;
    $gifPayload = $post->gifPayload();
@endphp

<article id="post-{{ $post->id }}" class="widget-box no-padding hh-vk-feed-post hh-vk-feed-post-clean {{ $post->is_pinned ? 'hh-feed-post-is-pinned' : '' }}" data-hh-feed-post-card="{{ $post->id }}" data-hh-feed-post-author="{{ $post->user->name }}" data-hh-feed-post-time="{{ $post->created_at->diffForHumans() }}" data-hh-feed-post-avatar="{{ $post->user->avatarUrl() }}" data-hh-feed-post-profile="{{ $profileRoute }}" data-hh-feed-post-level="{{ $post->user->level ?? 1 }}" data-hh-mention-context="{{ $mentionContext }}" @if($mentionTeamId) data-hh-mention-team-id="{{ $mentionTeamId }}" @endif>
    <div class="widget-box-settings">
            <details class="post-settings-wrap hh-vk-settings-menu">
                <summary class="post-settings">
                    <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                </summary>

                <div class="simple-dropdown widget-box-post-settings-dropdown hh-vk-settings-panel">
                    @if ($canEdit)
                        <button class="simple-dropdown-link hh-dropdown-button" type="button" data-hh-post-edit-open data-post-id="{{ $post->id }}">{{ __('ui.post_edit') }}</button>

                        <form method="post" action="{{ route('feed.destroy', $post) }}" onsubmit="return confirm('{{ __('ui.post_delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="simple-dropdown-link hh-dropdown-button" type="submit">{{ __('ui.post_delete') }}</button>
                        </form>
                    @endif

                    @if ($canPinPost)
                        <form method="post" action="{{ route('feed.pin', $post) }}">
                            @csrf
                            <button class="simple-dropdown-link hh-dropdown-button" type="submit">
                                {{ $post->is_pinned ? __('ui.post_unpin') : __('ui.post_pin') }}
                            </button>
                        </form>
                    @endif

                    @if (! $canEdit && ! $canPinPost)
                        <button class="simple-dropdown-link hh-dropdown-button hh-report-menu-link {{ $postAlreadyReported ? 'is-reported' : '' }}" type="button" data-hh-report-open data-hh-report-type="feed_post" data-hh-report-id="{{ $post->id }}" data-hh-report-label="{{ __('ui.post_report_label', ['name' => $post->user->name]) }}" data-hh-report-reported="{{ $postAlreadyReported ? '1' : '0' }}" aria-disabled="{{ $postAlreadyReported ? 'true' : 'false' }}">
                            {{ $postAlreadyReported ? __('ui.post_reported') : __('ui.post_report') }}
                        </button>
                    @endif
                </div>
            </details>
        </div>

    <div class="widget-box-status">
        <div class="widget-box-status-content hh-vk-post-body-content">
            <div class="user-status">
                <a class="user-status-avatar" href="{{ $profileRoute }}">
                    <div class="user-avatar small no-outline">
                        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $post->user->avatarUrl() }}"></div></div>
                        <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                        <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                        <div class="user-avatar-badge">
                            <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                            <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                            <p class="user-avatar-badge-text">{{ $post->user->level ?? 1 }}</p>
                        </div>
                    </div>
                </a>

                <p class="user-status-title medium">
                    <a class="bold" href="{{ $profileRoute }}">{{ $post->user->name }}</a>
                    @if ($isSharedPost)
                        {{ __('ui.post_shared_a') }} <span class="bold">{{ __('ui.post_post') }}</span>
                        @if ($postTeam)
                            {{ __('ui.post_in_team') }} <a class="bold" href="{{ route('teams.show', $postTeam) }}">{{ $postTeam->name }}</a>
                        @endif
                    @elseif ($postTeam)
                        {{ __('ui.post_posted_in_team') }} <a class="bold" href="{{ route('teams.show', $postTeam) }}">{{ $postTeam->name }}</a>
                    @else
                        {{ __('ui.posted_a') }} <span class="bold">{{ $postKind }}</span>
                    @endif
                </p>
                <p class="user-status-text small">
                    {{ $post->created_at->diffForHumans() }} · {{ $post->visibilityLabel() }}
                    @if ($feelingMeta)
                        · <span class="hh-feed-feeling-inline">{{ __('ui.feed_feeling_prefix') }} <span>{{ $feelingMeta['emoji'] }} {{ $feelingMeta['label'] }}</span></span>
                    @endif
                    @if ($post->is_pinned)
                        · <span class="hh-feed-pinned-meta"><i class="hh-ph-action-icon ph ph-push-pin" aria-hidden="true"></i> {{ __('ui.pinned') }}</span>
                    @endif
                </p>
            </div>

            @if ($backgroundStyleClass && $post->body)
                <div class="hh-feed-text-background-card {{ $backgroundStyleClass }}" data-hh-post-body-text="{{ $post->id }}">
                    <p>{!! \App\Support\MentionRenderer::render($post->body ?? '') !!}</p>
                </div>
            @else
                <p class="widget-box-status-text" data-hh-post-body-text="{{ $post->id }}" @if (! $post->body) hidden @endif>{!! \App\Support\MentionRenderer::render($post->body ?? '') !!}</p>
            @endif

            @if ($poll)
                <div class="hh-feed-poll-card" data-hh-feed-poll="{{ $poll->id }}">
                    <div class="hh-feed-poll-card__head">
                        <span class="hh-feed-poll-card__kicker"><i class="hh-ph-action-icon ph ph-list-checks" aria-hidden="true"></i> {{ __('ui.feed_poll') }}</span>
                        <strong>{{ $poll->question ?: __('ui.feed_poll_default_question') }}</strong>
                    </div>

                    <div class="hh-feed-poll-options">
                        @foreach ($pollOptions as $pollOption)
                            @php
                                $pollOptionVotes = $pollOption->relationLoaded('votes') ? $pollOption->votes->count() : 0;
                                $pollPercent = $pollTotalVotes > 0 ? round(($pollOptionVotes / $pollTotalVotes) * 100) : 0;
                                $pollSelected = $viewerPollOptionId && (int) $viewerPollOptionId === (int) $pollOption->id;
                            @endphp
                            <form method="post" action="{{ route('feed.poll.vote', $post) }}" class="hh-feed-poll-option-form">
                                @csrf
                                <input type="hidden" name="poll_option_id" value="{{ $pollOption->id }}">
                                <button type="submit" class="hh-feed-poll-option {{ $pollSelected ? 'is-selected' : '' }}">
                                    <span class="hh-feed-poll-option__bar" style="width: {{ $pollPercent }}%"></span>
                                    <span class="hh-feed-poll-option__label">{{ $pollOption->body }}</span>
                                    <span class="hh-feed-poll-option__meta">{{ $pollPercent }}%</span>
                                </button>
                            </form>
                        @endforeach
                    </div>

                    <p class="hh-feed-poll-card__total">{{ __('ui.feed_poll_total_votes', ['count' => $pollTotalVotes]) }}</p>
                </div>
            @endif

            @if ($gifPayload)
                <div class="hh-feed-gif-card">
                    <img src="{{ $gifPayload['gif_url'] }}" alt="{{ $gifPayload['title'] ?: __('ui.feed_gif') }}" loading="lazy">
                    <div class="hh-feed-gif-card__meta">
                        <span>{{ $gifPayload['title'] ?: __('ui.feed_gif') }}</span>
                        <small>{{ __('ui.feed_gif_powered_by') }}</small>
                    </div>
                </div>
            @endif

            @if ($postTranslationMeta['should_offer'])
                <div class="hh-feed-translation" data-hh-translation-wrap>
                    <button class="hh-feed-translation-toggle" type="button" data-hh-translation-trigger data-url="{{ route('feed.translation.post', $post) }}" data-locale="{{ $postTranslationMeta['target_locale'] }}" data-label-default="{{ __('ui.translation_show') }}" data-label-loading="{{ __('ui.translation_loading') }}" data-label-error="{{ __('ui.translation_error') }}">
                        <i class="hh-ph-action-icon ph ph-translate" aria-hidden="true"></i>
                        <span>{{ __('ui.translation_show') }}</span>
                    </button>
                    <div class="hh-feed-translation-result" data-hh-translation-result hidden></div>
                </div>
            @endif

            @if ($canEdit)
                <form class="hh-post-inline-edit" method="post" action="{{ route('feed.update', $post) }}" data-hh-post-inline-edit="{{ $post->id }}" data-post-id="{{ $post->id }}" hidden>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="visibility" value="{{ $post->isTeamPost() ? 'team' : $post->visibility }}">
                    <input type="hidden" name="background_style" value="{{ $post->background_style }}">
                    <input type="hidden" name="feeling_key" value="{{ $post->feeling_key }}">

                    <div class="hh-post-inline-edit__field">
                        <textarea name="body" maxlength="5000" rows="7" required data-hh-post-edit-body data-hh-mention-context="{{ $mentionContext }}" @if($mentionTeamId) data-hh-mention-team-id="{{ $mentionTeamId }}" @endif>{{ $post->body }}</textarea>
                    </div>

                    <div class="hh-post-inline-edit__meta">
                        <span class="hh-post-inline-edit__counter" data-hh-post-edit-count>0/5000</span>
                    </div>

                    <div class="hh-post-inline-edit__actions">
                        <button class="button small void hh-post-inline-edit__discard" type="button" data-hh-post-edit-cancel>{{ __('ui.discard') }}</button>
                        <button class="button small secondary hh-post-inline-edit__save" type="submit">{{ __('ui.save_changes') }}</button>
                    </div>
                </form>
            @endif

            @if ($sharedPost)
                @include('feed.partials.shared-post-preview', ['sharedPost' => $sharedPost, 'viewer' => $viewer])
            @endif
        </div>

        @if ($hasMedia)
            <div class="hh-vk-feed-media hh-vk-feed-media-count-{{ min($visibleMediaCount, 5) }} {{ $hiddenMediaCount > 0 ? 'hh-vk-feed-media-has-more' : '' }}" data-hh-feed-media-gallery="{{ $post->id }}" data-hh-feed-media-total="{{ $mediaItems->count() }}">
                @foreach ($visibleMediaItems as $mediaIndex => $media)
                    @php
                        $mediaUrl = $media->url();
                        $mediaAlt = $media->original_name ?: __('ui.feed');
                        $isLastVisibleMedia = $mediaIndex === ($visibleMediaCount - 1);
                    @endphp

                    <figure class="hh-vk-feed-media-item hh-vk-feed-media-item-{{ $mediaIndex + 1 }}">
                        @if ($media->isImage())
                            <button class="hh-vk-feed-media-trigger hh-vk-feed-media-source" type="button" data-hh-feed-media-open data-post-id="{{ $post->id }}" data-media-index="{{ $mediaIndex }}" data-media-kind="image" data-media-src="{{ $mediaUrl }}" data-media-alt="{{ $mediaAlt }}">
                                <img src="{{ $mediaUrl }}" alt="{{ $mediaAlt }}">
                                @if ($hiddenMediaCount > 0 && $isLastVisibleMedia)
                                    <span class="hh-vk-feed-media-more">+{{ $hiddenMediaCount }}</span>
                                @endif
                            </button>
                        @elseif ($media->isVideo())
                            <div class="hh-vk-feed-media-trigger hh-vk-feed-media-source hh-vk-feed-media-video-inline">
                                <div class="hh-feed-video-player" data-hh-feed-video-player>
                                    <video src="{{ $mediaUrl }}" playsinline preload="metadata" data-hh-feed-video></video>

                                    <button class="hh-feed-video-center-play" type="button" data-hh-feed-video-toggle aria-label="{{ __('ui.feed_video_play') }}" title="{{ __('ui.feed_video_play') }}">
                                        <i class="hh-ph-action-icon ph ph-play" aria-hidden="true"></i>
                                    </button>

                                    <div class="hh-feed-video-controls" data-hh-feed-video-controls>
                                        <button class="hh-feed-video-control" type="button" data-hh-feed-video-toggle aria-label="{{ __('ui.feed_video_play') }}" title="{{ __('ui.feed_video_play') }}">
                                            <i class="hh-ph-action-icon ph ph-play" aria-hidden="true" data-hh-feed-video-play-icon></i>
                                        </button>

                                        <button class="hh-feed-video-control" type="button" data-hh-feed-video-mute aria-label="{{ __('ui.feed_video_mute') }}" title="{{ __('ui.feed_video_mute') }}">
                                            <i class="hh-ph-action-icon ph ph-speaker-high" aria-hidden="true" data-hh-feed-video-mute-icon></i>
                                        </button>

                                        <button class="hh-feed-video-progress" type="button" data-hh-feed-video-progress aria-label="{{ __('ui.feed_video_seek') }}">
                                            <span class="hh-feed-video-progress-track">
                                                <span class="hh-feed-video-progress-fill" data-hh-feed-video-progress-fill></span>
                                            </span>
                                        </button>

                                        <span class="hh-feed-video-time" data-hh-feed-video-time>0:00</span>

                                        <button class="hh-feed-video-control" type="button" data-hh-feed-video-fullscreen aria-label="{{ __('ui.feed_video_fullscreen') }}" title="{{ __('ui.feed_video_fullscreen') }}">
                                            <i class="hh-ph-action-icon ph ph-corners-out" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>

                                @if ($hiddenMediaCount > 0 && $isLastVisibleMedia)
                                    <span class="hh-vk-feed-media-more">+{{ $hiddenMediaCount }}</span>
                                @endif
                            </div>
                        @else
                            <a class="button small white" href="{{ $mediaUrl }}" target="_blank" rel="noopener">{{ __('ui.open_file') }}</a>
                        @endif
                    </figure>
                @endforeach

                @foreach ($mediaItems->slice(5)->values() as $hiddenIndex => $media)
                    @if ($media->isImage())
                        <button class="hh-vk-feed-media-source hh-vk-feed-media-hidden-source" type="button" hidden data-hh-feed-media-open data-post-id="{{ $post->id }}" data-media-index="{{ $hiddenIndex + 5 }}" data-media-kind="image" data-media-src="{{ $media->url() }}" data-media-alt="{{ $media->original_name ?: __('ui.feed') }}"></button>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="widget-box-status-content hh-vk-post-meta-content">
            <div class="tag-list hh-post-tag-list">
                @if ($post->is_pinned)
                    <span class="tag-item secondary hh-feed-pinned-tag"><i class="hh-ph-action-icon ph ph-push-pin" aria-hidden="true"></i> {{ __('ui.pinned') }}</span>
                @endif
                @if ($feelingMeta)
                    <span class="tag-item secondary hh-feed-feeling-tag">{{ $feelingMeta['emoji'] }} {{ $feelingMeta['label'] }}</span>
                @endif
                <span class="tag-item secondary">Hunt</span>
                <span class="tag-item secondary">Community</span>
                @if ($hasMedia)
                    <span class="tag-item secondary">Media</span>
                @endif
                @if ($postAiLabelVisible)
                    <span class="tag-item secondary hh-ai-content-label"><i class="hh-ph-action-icon ph ph-sparkle" aria-hidden="true"></i> {{ __('ui.ai_content_public_label') }}</span>
                @endif
            </div>

            <div class="content-actions hh-vk-content-actions-clean">
                <div class="content-action">
                    <div class="meta-line">
                        <div class="meta-line-list reaction-item-list" data-hh-reaction-icons="{{ $post->id }}">
                            @foreach ($topReactionTypes as $topReactionType)
                                <div class="reaction-item">
                                    <img class="reaction-image reaction-item-dropdown-trigger" src="{{ asset('assets/vikinger/img/reaction/' . $topReactionType . '.png') }}" alt="{{ $reactionTypes[$topReactionType] ?? 'Reaction' }}">
                                    <div class="simple-dropdown padded reaction-item-dropdown">
                                        <p class="simple-dropdown-text"><img class="reaction" src="{{ asset('assets/vikinger/img/reaction/' . $topReactionType . '.png') }}" alt="{{ $reactionTypes[$topReactionType] ?? 'Reaction' }}"> <span class="bold">{{ $reactionTypes[$topReactionType] ?? ucfirst($topReactionType) }}</span></p>
                                        <p class="simple-dropdown-text">{{ (int) ($reactionGroups[$topReactionType] ?? 0) }} {{ __('ui.players') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="meta-line-text" data-hh-reaction-count="{{ $post->id }}">{{ $reactionCount }}</p>
                    </div>
                </div>

                <div class="content-action">
                    <div class="meta-line"><button class="meta-line-link hh-comment-open-button" type="button" data-hh-open-comments data-post-id="{{ $post->id }}" data-hh-post-comment-count="{{ $post->id }}">{{ trans_choice('ui.comment_count', $commentCount, ['count' => $commentCount]) }}</button></div>
                    <div class="meta-line"><p class="meta-line-text">{{ $shareCount }} {{ __('ui.shares') }}</p></div>
                </div>
            </div>
        </div>
    </div>

    <div class="post-options hh-vk-post-options hh-vk-post-options-clean">
        <div class="post-option-wrap hh-reaction-hover-zone">
            <button class="post-option reaction-options-dropdown-trigger hh-post-option-button hh-feed-reaction-action {{ $viewerReactionType ? 'active' : '' }}" type="button" data-hh-feed-reaction-action data-post-id="{{ $post->id }}" data-url="{{ route('feed.reactions.toggle', $post) }}" data-type="like" data-mode="toggle" data-current-type="{{ $viewerReactionType ?: '' }}">
                <span class="hh-current-reaction-icon" data-hh-current-reaction-icon="{{ $post->id }}">
                    @if ($viewerReactionType)
                        <img class="hh-current-reaction-image" src="{{ asset('assets/vikinger/img/reaction/' . $viewerReactionType . '.png') }}" alt="{{ $reactionTypes[$viewerReactionType] ?? 'Reaction' }}">
                    @else
                        <i class="post-option-icon hh-ph-action-icon ph ph-thumbs-up" aria-hidden="true"></i>
                    @endif
                </span>
                <p class="post-option-text" data-hh-reaction-label="{{ $post->id }}">{{ $viewerReactionType ? ($reactionTypes[$viewerReactionType] ?? 'React!') : 'React!' }}</p>
            </button>

            <div class="reaction-options reaction-options-dropdown hh-reaction-options-picker">
                @foreach ($reactionTypes as $reaction => $label)
                    <button class="reaction-option text-tooltip-tft hh-reaction-option-button hh-feed-reaction-action" data-title="{{ $label }}" type="button" data-hh-feed-reaction-action data-post-id="{{ $post->id }}" data-url="{{ route('feed.reactions.toggle', $post) }}" data-type="{{ $reaction }}" data-mode="set">
                        <img class="reaction-option-image" src="{{ asset('assets/vikinger/img/reaction/' . $reaction . '.png') }}" alt="{{ $label }}">
                    </button>
                @endforeach
            </div>
        </div>

        <button class="post-option hh-post-option-link hh-post-comment-button" type="button" data-hh-open-comments data-post-id="{{ $post->id }}">
            <i class="post-option-icon hh-ph-action-icon ph ph-chat-circle-text" aria-hidden="true"></i>
            <p class="post-option-text">{{ __('ui.feed_comment_title') }}</p>
        </button>

        <div class="post-option-wrap hh-share-option-wrap">
            <button class="post-option hh-post-option-button hh-share-dialog-trigger" type="button" data-hh-share-open data-hh-share-dialog="{{ $shareModalId }}">
                <i class="post-option-icon hh-ph-action-icon ph ph-share-network" aria-hidden="true"></i>
                <p class="post-option-text">{{ __('ui.share') }}</p>
            </button>

            <div class="hh-share-modal-layer" id="{{ $shareModalId }}" aria-labelledby="{{ $shareModalId }}-title" role="dialog" aria-modal="true" hidden>
                <div class="xm-popup_container share-box-popup animate-slide-down xm-popup_container-premade hh-share-popup">
                    <button class="xm-popup_close-button hh-share-dialog-close" type="button" data-hh-share-close aria-label="{{ __('ui.share_close_aria') }}">
                        <svg viewBox="0 0 12 12" preserveAspectRatio="xMinYMin meet" class="xm-popup_close-button-icon">
                            <path d="M12,9.6L9.6,12L6,8.399L2.4,12L0,9.6L3.6,6L0,2.4L2.4,0L6,3.6L9.6,0L12,2.4L8.399,6L12,9.6z"></path>
                        </svg>
                    </button>

                    <div class="quick-post hh-share-quick-post">
                        <form class="hh-share-dialog-form" method="post" action="{{ route('feed.share', $post) }}">
                            @csrf

                            <div class="quick-post-header hh-share-quick-header">
                                <div class="quick-post-header-filters-wrap hh-share-header-wrap">
                                    <div class="user-status">
                                        <div class="user-status-avatar">
                                            <div class="user-avatar small no-outline no-border">
                                                <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $viewer->avatarUrl() }}"></div></div>
                                                <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                                                <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                                                <div class="user-avatar-badge">
                                                    <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                                                    <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                                                    <p class="user-avatar-badge-text">{{ $viewer->level ?? 1 }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="user-status-title medium" id="{{ $shareModalId }}-title"><span class="bold">{{ $viewer->name }}</span></p>
                                        <p class="user-status-text small">Status Update</p>
                                    </div>

                                    <div class="quick-post-header-filters hh-share-post-in-filter" aria-hidden="true">
                                        <div class="form-row split">
                                            <div class="form-item">
                                                <div class="form-select">
                                                    <label>{{ __('ui.post_in') }}</label>
                                                    <select tabindex="-1">
                                                        <option>{{ __('ui.share_my_profile') }}</option>
                                                    </select>
                                                    <svg class="icon-small-arrow form-select-icon"><use xlink:href="#svg-small-arrow"></use></svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="quick-post-body with-share-data hh-share-quick-body">
                                <div class="form">
                                    <div class="form-row">
                                        <div class="form-item">
                                            <div class="form-textarea hh-share-dialog-textarea">
                                                <textarea name="body" maxlength="1000" rows="5" data-hh-mention-context="{{ $mentionContext }}" @if($mentionTeamId) data-hh-mention-team-id="{{ $mentionTeamId }}" @endif placeholder="{{ __('ui.share_placeholder', ['name' => $viewer->name, 'target' => $post->isTeamPost() ? __('ui.share_target_team_member') : __('ui.share_target_friend')]) }}">{{ old('body') }}</textarea>
                                                <div class="form-textarea-footer">
                                                    <div class="form-textarea-limit"><p class="form-textarea-limit-text">0/1000</p></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="hh-share-dialog-preview-wrap">
                                    @include('feed.partials.shared-post-preview', ['sharedPost' => $sharePreviewPost, 'viewer' => $viewer])
                                </div>
                            </div>

                            <div class="quick-post-footer-wrap hh-share-footer-wrap">
                                <div class="quick-post-footer">
                                    <div class="quick-post-footer-actions"></div>
                                    <div class="quick-post-footer-actions">
                                        <button class="button small void hh-share-dialog-discard" type="button" data-hh-share-close>{{ __('ui.discard') }}</button>
                                        <button class="button small secondary" type="submit">{{ __('ui.share') }}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @once
                <script>
                    document.addEventListener('click', function (event) {
                        const openTrigger = event.target.closest('[data-hh-share-open]');

                        if (openTrigger) {
                            const modal = document.getElementById(openTrigger.getAttribute('data-hh-share-dialog'));
                            if (!modal) return;

                            if (modal.parentElement !== document.body) {
                                document.body.appendChild(modal);
                            }

                            modal.hidden = false;
                            modal.classList.add('is-open');
                            document.documentElement.classList.add('hh-share-dialog-is-open');

                            const textarea = modal.querySelector('textarea[name="body"]');
                            if (textarea) {
                                window.setTimeout(function () { textarea.focus(); }, 40);
                            }
                            return;
                        }

                        const closeTrigger = event.target.closest('[data-hh-share-close]');

                        if (closeTrigger) {
                            const modal = closeTrigger.closest('.hh-share-modal-layer');
                            if (modal) {
                                modal.classList.remove('is-open');
                                modal.hidden = true;
                            }
                            document.documentElement.classList.remove('hh-share-dialog-is-open');
                            return;
                        }

                        if (event.target && event.target.matches('.hh-share-modal-layer')) {
                            event.target.classList.remove('is-open');
                            event.target.hidden = true;
                            document.documentElement.classList.remove('hh-share-dialog-is-open');
                        }
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key !== 'Escape') return;

                        const modal = document.querySelector('.hh-share-modal-layer.is-open');
                        if (!modal) return;

                        modal.classList.remove('is-open');
                        modal.hidden = true;
                        document.documentElement.classList.remove('hh-share-dialog-is-open');
                    });
                </script>
            @endonce
        </div>
    </div>

    <span class="hh-feed-media-comments-anchor" data-hh-media-comments-anchor="{{ $post->id }}" hidden></span>
    <div id="comments-{{ $post->id }}" class="post-comment-list hh-vk-comment-list-clean {{ $showComments ? 'is-open' : 'is-collapsed' }}" data-hh-comment-list-post="{{ $post->id }}">
        <div class="hh-comment-composer-home" data-hh-comment-home="{{ $post->id }}">
            <div class="post-comment-form hh-vk-reply-first" data-hh-comment-composer="{{ $post->id }}">
                <div class="user-avatar small no-outline">
                    <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ auth()->user()->avatarUrl() }}"></div></div>
                    <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                    <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                    <div class="user-avatar-badge">
                        <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                        <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                        <p class="user-avatar-badge-text">{{ auth()->user()->level ?? 1 }}</p>
                    </div>
                </div>

                <form class="form" method="post" action="{{ route('feed.comments.store', $post) }}" data-hh-comment-form="{{ $post->id }}" data-post-id="{{ $post->id }}" data-root-id="">
                    @csrf
                    <input type="hidden" name="parent_id" value="" data-hh-comment-parent="{{ $post->id }}">
                    <p class="hh-comment-reply-context" data-hh-reply-context="{{ $post->id }}" hidden></p>
                    <div class="form-row">
                        <div class="form-item">
                            <div class="form-input small">
                                <label for="post-reply-{{ $post->id }}">{{ __('ui.your_reply') }}</label>
                                <input type="text" id="post-reply-{{ $post->id }}" name="body" maxlength="2000" required data-hh-mention-context="{{ $commentMentionContext }}" @if($mentionTeamId) data-hh-mention-team-id="{{ $mentionTeamId }}" @endif>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @foreach ($rootComments as $comment)
            @include('feed.partials.comment-item', [
                'post' => $post,
                'comment' => $comment,
                'viewer' => $viewer,
                'reactionTypes' => $reactionTypes,
                'isReply' => false,
                'replies' => $repliesByParent->get($comment->id, collect())->sortBy('created_at'),
            ])
        @endforeach

        @if ($hiddenRootCommentCount > 0)
            @php
                $hiddenCommentCountMarkup = '<span class="hh-comment-load-replies-count">' . $hiddenRootCommentCount . '</span>';
            @endphp
            <button class="post-comment-heading hh-comment-load-more-comments" type="button" data-hh-load-more-comments data-post-id="{{ $post->id }}" data-comment-count="{{ $hiddenRootCommentCount }}" aria-expanded="false">
                {!! trans_choice('ui.comment_load_more_comments', $hiddenRootCommentCount, ['count' => $hiddenCommentCountMarkup]) !!}
            </button>

            <div class="hh-comment-hidden-roots" data-hh-hidden-comments="{{ $post->id }}" hidden>
                @foreach ($hiddenRootComments as $comment)
                    @include('feed.partials.comment-item', [
                        'post' => $post,
                        'comment' => $comment,
                        'viewer' => $viewer,
                        'reactionTypes' => $reactionTypes,
                        'isReply' => false,
                        'replies' => $repliesByParent->get($comment->id, collect())->sortBy('created_at'),
                    ])
                @endforeach
            </div>
        @endif
    </div>
</article>
