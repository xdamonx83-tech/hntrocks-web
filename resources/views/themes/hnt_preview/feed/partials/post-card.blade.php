@php
    /** @var \App\Models\FeedPost $post */
    $author = $post->user;
    $authorName = $author?->name ?: ($author?->username ?: __('ui.preview_hnt_hunter'));
    $authorHandle = $author?->username ? '@' . $author->username : '@hntrocks';
    $authorUrl = $author
        ? (((int) $author->id === (int) auth()->id()) ? route('profile.show') : route('profile.public', $author))
        : route('feed.index');
    $postUrl = $post->permalink();
    $commentModalUrl = $postUrl . (str_contains($postUrl, '?') ? '&' : '?') . 'hnt_preview_comments=1';
    $bodyHtml = \App\Support\FeedTextRenderer::render($post->body);
    $internalLinkPreviews = \App\Support\FeedTextRenderer::internalLinkPreviews($post->body, 1);
    $postHashtags = \App\Support\Hashtag::labelsForText($post->body, 6);
    $mediaItems = $post->relationLoaded('media') ? $post->media : collect();
    $hasMedia = $mediaItems->isNotEmpty();
    $firstMedia = $mediaItems->first();
    $reactionCount = (int) ($post->reactions_count ?? ($post->relationLoaded('reactions') ? $post->reactions->count() : 0));
    $commentCount = (int) ($post->comments_count ?? ($post->relationLoaded('comments') ? $post->comments->count() : 0));
    $shareCount = (int) ($post->shares_count ?? 0);
    $shareTitleSource = trim(strip_tags((string) $post->body));
    $shareTitle = $shareTitleSource !== ''
        ? \Illuminate\Support\Str::limit($shareTitleSource, 80)
        : __('ui.preview_feed_share_title_fallback', ['name' => $authorName]);
    $shareText = __('ui.preview_share_default_text');
    $viewerReaction = $post->relationLoaded('viewerReaction') ? $post->viewerReaction : null;
    $viewerBookmarked = $post->relationLoaded('viewerBookmark') && $post->viewerBookmark;
    $feelingMeta = $post->feelingMeta();
    $poll = $post->relationLoaded('poll') ? $post->poll : null;
    $pollOptions = $poll && $poll->relationLoaded('options') ? $poll->options : collect();
    $pollVotes = $poll && $poll->relationLoaded('votes') ? $poll->votes : collect();
    $pollTotalVotes = $pollVotes->count();
    $viewerPollVote = auth()->check() ? $pollVotes->firstWhere('user_id', auth()->id()) : null;
    $viewerPollOptionId = $viewerPollVote?->feed_post_poll_option_id;
    $viewer = auth()->user();
    $isOwnPost = $author && $viewer && (int) $author->id === (int) $viewer->id;
    $friendship = null;
    $friendButtonLabel = __('ui.preview_profile_friend_add');
    $reportedFeedKeys = $reportedFeedKeys ?? collect();
    $postAlreadyReported = method_exists($reportedFeedKeys, 'has')
        ? $reportedFeedKeys->has('feed_post:' . $post->id)
        : false;
    $translationService = app(\App\Services\Translation\FeedTranslationService::class);
    $translationMeta = $translationService->translationMeta($post->body, $post->source_language, app()->getLocale());
    $postTranslationTarget = $translationMeta['target_locale'] ?? app()->getLocale();
    $postTranslationShouldOffer = (bool) ($translationMeta['should_offer'] ?? false);

    $cupTeam = $post->relationLoaded('cupTeam') ? $post->cupTeam : $post->cupTeam;
    $showCupTeamQuickComments = $cupTeam && $cupTeam->cup && ! $post->isTeamPost();
    $cupTeamQuickComments = $showCupTeamQuickComments ? [
        __('ui.cup_team_activity_quick_need_one'),
        __('ui.cup_team_activity_quick_full'),
        __('ui.cup_team_activity_quick_platform'),
    ] : [];

    if ($author && $viewer && ! $isOwnPost) {
        static $hntPreviewFriendshipCache = [];
        $friendshipCacheKey = (string) $author->id;

        if (! array_key_exists($friendshipCacheKey, $hntPreviewFriendshipCache)) {
            $hntPreviewFriendshipCache[$friendshipCacheKey] = $viewer->friendshipWith($author);
        }

        $friendship = $hntPreviewFriendshipCache[$friendshipCacheKey];

        if ($friendship?->isAccepted()) {
            $friendButtonLabel = __('ui.preview_profile_friend_connected');
        } elseif ($friendship?->isPending()) {
            $friendButtonLabel = $friendship->isRequester($viewer) ? __('ui.preview_profile_friend_requested') : __('ui.preview_profile_friend_open');
        }
    }
@endphp

<article class="post hnt-preview-feed-post {{ $hasMedia ? 'has-media' : 'is-text-only' }}" id="post-{{ $post->id }}" data-hnt-preview-post data-hnt-lightbox-scope data-post-id="{{ $post->id }}" data-post-update-url="{{ route('feed.update', $post) }}" data-post-delete-url="{{ route('feed.destroy', $post) }}" data-post-visibility="{{ $post->visibility }}">
    <div class="post-header">
        <div class="post-author">
            <a class="avatar avatar-sm hnt-avatar-shell" href="{{ $authorUrl }}">
                @if($author)
                    <img src="{{ $author->avatarUrl() }}" alt="{{ $authorName }}">
                @endif
            </a>
            <div class="author-info">
                <a class="hnt-author-link" href="{{ $authorUrl }}"><strong>{{ $authorName }}</strong></a>
                <span class="hnt-post-meta-line">
                    {{ $authorHandle }} · {{ optional($post->created_at)->diffForHumans() }}
                    @if($postTranslationShouldOffer)
                        · <button class="hnt-post-translate-link" type="button" data-hnt-post-translate data-translation-url="{{ route('feed.translation.post', $post) }}" data-translation-target="{{ $postTranslationTarget }}" data-show-label="{{ __('ui.translation_action_short') }}" data-hide-label="{{ __('ui.translation_hide') }}" data-loading-label="{{ __('ui.translation_loading') }}" data-error-label="{{ __('ui.translation_error') }}">{{ __('ui.translation_action_short') }}</button>
                    @endif
                    · {{ $post->visibilityLabel() }}
                </span>
            </div>
        </div>
        <div class="post-header-actions hnt-post-header-actions">
            @if($post->is_pinned)
                <span class="icon-btn-green" title="{{ __('ui.preview_post_pinned') }}"><i class="ph ph-push-pin" aria-hidden="true"></i></span>
            @endif

            @if($author && ! $isOwnPost)
                <button class="icon-btn-green hnt-post-report-button {{ $postAlreadyReported ? 'is-reported' : '' }}" type="button" title="{{ $postAlreadyReported ? __('ui.preview_post_reported') : __('ui.preview_post_report') }}" aria-label="{{ $postAlreadyReported ? __('ui.preview_report_already_reported_aria') : __('ui.preview_post_report_aria') }}" data-hnt-report-open data-report-type="feed_post" data-report-id="{{ $post->id }}" data-report-label="{{ __('ui.preview_post_report_label', ['name' => $authorName]) }}" data-report-reported="{{ $postAlreadyReported ? '1' : '0' }}" @disabled($postAlreadyReported)>
                    <i class="ph ph-shield-check" aria-hidden="true"></i>
                </button>

                @if($friendship?->isAccepted() || $friendship?->isPending())
                    <span class="btn-following hnt-post-friend-button is-static">{{ $friendButtonLabel }}</span>
                @else
                    <form method="post" action="{{ route('friends.store', $author) }}" class="hnt-inline-form hnt-post-friend-form">
                        @csrf
                        <button class="btn-following hnt-post-friend-button" type="submit">{{ $friendButtonLabel }}</button>
                    </form>
                @endif
            @else
                <div class="hnt-post-options" data-hnt-post-options>
                    <button class="btn-following hnt-post-options-toggle" type="button" aria-expanded="false" data-hnt-post-options-toggle>
                        <span>{{ __('ui.preview_post_manage') }}</span>
                        <i class="ph ph-caret-down" aria-hidden="true"></i>
                    </button>
                    <div class="nav-submenu hnt-post-options-menu" aria-hidden="true">
                        <button class="hnt-post-options-close" type="button" aria-label="{{ __('ui.preview_nav_close_menu') }}" data-hnt-post-options-close><i class="ph ph-x" aria-hidden="true"></i></button>
                        <button class="nav-subitem hnt-post-option-action" type="button" data-hnt-post-edit-open>{{ __('ui.preview_post_edit') }}</button>
                        <form method="post" action="{{ route('feed.destroy', $post) }}" class="hnt-post-delete-form" data-hnt-post-delete-form>
                            @csrf
                            @method('DELETE')
                            <button class="nav-subitem hnt-post-option-action hnt-post-option-danger" type="submit">{{ __('ui.preview_post_delete') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($post->isSharedPost() && $post->sharedPost)
        <div class="hnt-preview-shared-note">{{ __('ui.preview_post_shared_by', ['name' => $post->sharedPost->user?->name ?: __('ui.preview_hnt_hunter')]) }}</div>
    @endif

    @if($mediaItems->count() > 1)
        <div class="post-image-placeholder hnt-post-media-carousel" data-hnt-media-carousel aria-label="{{ __('ui.preview_post_media_aria') }}">
            @foreach($mediaItems as $media)
                @php
                    $mediaUrl = $media->url();
                @endphp
                <div class="hnt-post-media-slide {{ $loop->first ? 'is-active' : '' }}" data-hnt-media-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                    @if($media->isImage())
                        <a href="{{ $mediaUrl }}" data-hnt-lightbox-trigger data-hnt-lightbox-src="{{ $mediaUrl }}" data-hnt-lightbox-alt="{{ $media->original_name ?: __('ui.preview_feed_medium_alt') }}"><img src="{{ $mediaUrl }}" alt="{{ $media->original_name ?: __('ui.preview_feed_medium_alt') }}"></a>
                    @elseif($media->isVideo())
                        <div class="hnt-video-player" data-hnt-video-player>
                            <video muted playsinline preload="metadata" data-hnt-video><source src="{{ $mediaUrl }}" type="{{ $media->mime_type }}"></video>
                            <button class="hnt-video-center-play" type="button" data-hnt-video-toggle aria-label="{{ __('ui.preview_video_play') }}"><i class="ph ph-play" aria-hidden="true"></i></button>
                            <div class="hnt-video-controls">
                                <button class="hnt-video-control" type="button" data-hnt-video-toggle aria-label="{{ __('ui.preview_video_play') }}"><i class="ph ph-play" aria-hidden="true" data-hnt-video-icon></i></button>
                                <span class="hnt-video-time" data-hnt-video-time>0:00</span>
                                <input class="hnt-video-seek" type="range" min="0" max="100" value="0" step="0.1" data-hnt-video-seek aria-label="{{ __('ui.preview_video_progress') }}">
                                <button class="hnt-video-control" type="button" data-hnt-video-mute aria-label="{{ __('ui.preview_video_mute') }}"><i class="ph ph-speaker-high" aria-hidden="true"></i></button>
                                <button class="hnt-video-control" type="button" data-hnt-video-fullscreen aria-label="{{ __('ui.preview_video_fullscreen') }}"><i class="ph ph-corners-out" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    @else
                        <a class="hnt-post-media-file" href="{{ $mediaUrl }}" target="_blank" rel="noopener">{{ __('ui.preview_open_file') }}</a>
                    @endif
                </div>
            @endforeach
            <button class="hnt-post-media-nav hnt-post-media-prev" type="button" data-hnt-media-prev aria-label="{{ __('ui.preview_lightbox_prev_aria') }}">
                <i class="ph ph-caret-left" aria-hidden="true"></i>
            </button>
            <button class="hnt-post-media-nav hnt-post-media-next" type="button" data-hnt-media-next aria-label="{{ __('ui.preview_next_media') }}">
                <i class="ph ph-caret-right" aria-hidden="true"></i>
            </button>
            <span class="hnt-post-media-count"><span data-hnt-media-current>1</span> / {{ $mediaItems->count() }}</span>
        </div>
    @elseif($firstMedia)
        @php
            $mediaUrl = $firstMedia->url();
        @endphp
        <div class="post-image-placeholder hnt-post-media-single">
            @if($firstMedia->isImage())
                <a href="{{ $mediaUrl }}" data-hnt-lightbox-trigger data-hnt-lightbox-src="{{ $mediaUrl }}" data-hnt-lightbox-alt="{{ $firstMedia->original_name ?: __('ui.preview_feed_medium_alt') }}"><img src="{{ $mediaUrl }}" alt="{{ $firstMedia->original_name ?: __('ui.preview_feed_medium_alt') }}"></a>
            @elseif($firstMedia->isVideo())
                <div class="hnt-video-player" data-hnt-video-player>
                    <video muted playsinline preload="metadata" data-hnt-video><source src="{{ $mediaUrl }}" type="{{ $firstMedia->mime_type }}"></video>
                    <button class="hnt-video-center-play" type="button" data-hnt-video-toggle aria-label="{{ __('ui.preview_video_play') }}"><i class="ph ph-play" aria-hidden="true"></i></button>
                    <div class="hnt-video-controls">
                        <button class="hnt-video-control" type="button" data-hnt-video-toggle aria-label="{{ __('ui.preview_video_play') }}"><i class="ph ph-play" aria-hidden="true" data-hnt-video-icon></i></button>
                        <span class="hnt-video-time" data-hnt-video-time>0:00</span>
                        <input class="hnt-video-seek" type="range" min="0" max="100" value="0" step="0.1" data-hnt-video-seek aria-label="{{ __('ui.preview_video_progress') }}">
                        <button class="hnt-video-control" type="button" data-hnt-video-mute aria-label="{{ __('ui.preview_video_mute') }}"><i class="ph ph-speaker-high" aria-hidden="true"></i></button>
                        <button class="hnt-video-control" type="button" data-hnt-video-fullscreen aria-label="{{ __('ui.preview_video_fullscreen') }}"><i class="ph ph-corners-out" aria-hidden="true"></i></button>
                    </div>
                </div>
            @else
                <a href="{{ $mediaUrl }}" target="_blank" rel="noopener">{{ __('ui.preview_open_file') }}</a>
            @endif
        </div>
    @endif

    @if($hasMedia)
        <div class="post-actions hnt-post-actions hnt-post-actions-media">
            <div class="action-group">
                <form method="post" action="{{ route('feed.reactions.toggle', $post) }}" class="hnt-inline-form" data-hnt-simple-like-form data-like-target-type="post" data-like-target-id="{{ $post->id }}">
                    @csrf
                    <input type="hidden" name="type" value="like">
                    <button class="action-btn {{ $viewerReaction ? 'active' : '' }}" type="submit" aria-label="{{ __('ui.preview_like_aria') }}" data-hnt-simple-like-button data-liked="{{ $viewerReaction ? '1' : '0' }}">
                        <i class="ph ph-heart" aria-hidden="true"></i>
                        <span data-hnt-simple-like-count data-like-target-type="post" data-like-target-id="{{ $post->id }}">{{ number_format($reactionCount) }}</span>
                    </button>
                </form>
                <button class="action-btn" type="button" aria-label="{{ __('ui.preview_comments_aria') }}" data-post-modal-open data-post-id="{{ $post->id }}" data-post-modal-url="{{ $commentModalUrl }}" data-post-comment-store-url="{{ route('feed.comments.store', $post) }}">
                    <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
                    <span data-hnt-post-comment-count="{{ $post->id }}">{{ number_format($commentCount) }}</span>
                </button>
                <button class="action-btn hnt-share-button" type="button" aria-label="{{ __('ui.preview_share_post_aria') }}" title="{{ __('ui.preview_share_button_title') }}" data-hnt-native-share-button data-share-target-id="{{ $post->id }}" data-share-url="{{ $postUrl }}" data-share-title="{{ e($shareTitle) }}" data-share-text="{{ e($shareText) }}">
                    <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
                    <span data-hnt-share-count data-share-target-id="{{ $post->id }}">{{ $shareCount > 0 ? number_format($shareCount) : '' }}</span>
                </button>
            </div>
            <form method="post" action="{{ route('feed.bookmarks.toggle', $post) }}" class="hnt-inline-form" data-hnt-bookmark-form data-bookmark-target-id="{{ $post->id }}">
                @csrf
                <button class="action-btn hnt-bookmark-icon-button {{ $viewerBookmarked ? 'active' : '' }}" type="submit" aria-label="{{ $viewerBookmarked ? __('ui.preview_bookmark_remove_aria') : __('ui.preview_bookmark_save_aria') }}" title="{{ $viewerBookmarked ? __('ui.preview_saved') : __('ui.preview_save') }}" data-hnt-bookmark-button data-bookmarked="{{ $viewerBookmarked ? '1' : '0' }}">
                    <i class="ph ph-bookmark-simple" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    @endif

    <div class="hnt-post-body-wrap" data-hnt-post-body-wrap @if($bodyHtml === '') hidden @endif>
        <div class="post-text hnt-post-text" data-hnt-read-more data-hnt-post-body data-raw-body="{{ e((string) $post->body) }}">{!! $bodyHtml !!}</div>
    </div>

    @if($postTranslationShouldOffer)
        <div class="hnt-post-translation" data-hnt-post-translation hidden>
            <div class="hnt-post-translation-meta" data-hnt-post-translation-meta>{{ __('ui.translation_loading') }}</div>
            <div class="post-text hnt-post-text hnt-post-translation-body" data-hnt-read-more data-hnt-post-translation-body></div>
        </div>
    @endif

    @if($feelingMeta)
        <div class="hnt-preview-post-feeling">
            <span class="hnt-preview-post-feeling-icon">{{ $feelingMeta['emoji'] }}</span>
            <span>{{ __('ui.feed_feeling_prefix') }} <strong>{{ $feelingMeta['label'] }}</strong></span>
        </div>
    @endif

    @if($showCupTeamQuickComments && count($cupTeamQuickComments) > 0)
        <div class="hnt-cup-team-activity-actions" aria-label="{{ __('ui.cup_team_activity_quick_actions_aria') }}">
            <span class="hnt-cup-team-activity-actions-label">{{ __('ui.cup_team_activity_quick_actions_label') }}</span>
            <div class="hnt-cup-team-activity-action-list">
                @foreach($cupTeamQuickComments as $quickComment)
                    <button class="btn-following hnt-cup-team-activity-quick-comment" type="button" data-post-modal-open data-post-id="{{ $post->id }}" data-post-modal-url="{{ $commentModalUrl }}" data-post-comment-store-url="{{ route('feed.comments.store', $post) }}" data-post-comment-template="{{ $quickComment }}">{{ $quickComment }}</button>
                @endforeach
            </div>
        </div>
    @endif

    @php
        $pollHtml = '';

        if ($poll && $pollOptions->count()) {
            $pollRows = [];

            foreach ($pollOptions as $pollOptionItem) {
                $optionId = (int) data_get($pollOptionItem, 'id', 0);

                if ($optionId <= 0) {
                    continue;
                }

                $optionBody = trim((string) data_get($pollOptionItem, 'body', ''));
                $optionVotes = $pollVotes->where('feed_post_poll_option_id', $optionId)->count();
                $optionPercent = $pollTotalVotes > 0 ? (int) round(($optionVotes / max(1, $pollTotalVotes)) * 100) : 0;

                $pollRows[] = [
                    'id' => $optionId,
                    'body' => $optionBody !== '' ? $optionBody : __('ui.feed_poll_option_fallback'),
                    'percent' => max(0, min(100, $optionPercent)),
                    'selected' => $viewerPollOptionId && (int) $viewerPollOptionId === $optionId,
                ];
            }

            if (count($pollRows) > 0) {
                $pollHtml .= '<section class="hnt-preview-poll-card" data-hnt-preview-poll="' . e((string) $poll->id) . '">';
                $pollHtml .= '<header class="hnt-preview-poll-head">';
                $pollHtml .= '<span>' . e(__('ui.feed_poll')) . '</span>';
                $pollHtml .= '<strong>' . e($poll->question ?: __('ui.feed_poll_default_question')) . '</strong>';
                $pollHtml .= '</header>';
                $pollHtml .= '<div class="hnt-preview-poll-options">';

                foreach ($pollRows as $pollRow) {
                    $selectedClass = $pollRow['selected'] ? ' is-selected' : '';
                    $pollHtml .= '<form method="post" action="' . e(route('feed.poll.vote', $post)) . '" class="hnt-preview-poll-form">';
                    $pollHtml .= csrf_field();
                    $pollHtml .= '<input type="hidden" name="poll_option_id" value="' . e((string) $pollRow['id']) . '">';
                    $pollHtml .= '<button class="hnt-preview-poll-option' . $selectedClass . '" type="submit">';
                    $pollHtml .= '<span class="hnt-preview-poll-bar" style="width: ' . e((string) $pollRow['percent']) . '%"></span>';
                    $pollHtml .= '<span class="hnt-preview-poll-label">' . e($pollRow['body']) . '</span>';
                    $pollHtml .= '<span class="hnt-preview-poll-meta">' . e((string) $pollRow['percent']) . '%</span>';
                    $pollHtml .= '</button>';
                    $pollHtml .= '</form>';
                }

                $pollHtml .= '</div>';
                $pollHtml .= '<p class="hnt-preview-poll-total">' . e(__('ui.feed_poll_total_votes', ['count' => $pollTotalVotes])) . '</p>';
                $pollHtml .= '</section>';
            }
        }
    @endphp
    {!! $pollHtml !!}

    @if($isOwnPost)
        <form class="hnt-post-edit-form" method="post" action="{{ route('feed.update', $post) }}" data-hnt-post-edit-form hidden>
            @csrf
            @method('PUT')
            <input type="hidden" name="visibility" value="{{ $post->visibility }}">
            <textarea name="body" maxlength="5000" required data-hnt-post-edit-textarea>{{ (string) $post->body }}</textarea>
            <p class="hnt-post-edit-status" data-hnt-post-edit-status hidden></p>
            <div class="hnt-post-edit-actions">
                <button class="btn-following" type="button" data-hnt-post-edit-cancel>{{ __('ui.preview_action_cancel') }}</button>
                <button class="btn-create" type="submit" data-hnt-post-edit-submit>{{ __('ui.preview_action_save') }}</button>
            </div>
        </form>
    @endif

    <div data-hnt-internal-link-previews>
        @foreach($internalLinkPreviews as $preview)
            <a class="hnt-internal-link-preview" href="{{ $preview['url'] }}">
                <span class="hnt-internal-link-icon" aria-hidden="true">
                    <i class="ph ph-trophy" aria-hidden="true"></i>
                </span>
                <span class="hnt-internal-link-copy">
                    <span>{{ $preview['label'] }}</span>
                    <strong>{{ $preview['title'] }}</strong>
                    <em>{{ $preview['description'] }}</em>
                    <small>{{ $preview['display_url'] }}</small>
                </span>
            </a>
        @endforeach
    </div>

    <div class="post-tags">
        @if($post->team)
            <a class="tag" href="{{ route('teams.show', $post->team) }}">#{{ $post->team->name }}</a>
        @endif
        @foreach($postHashtags as $hashtag)
            <a class="tag hnt-hashtag-chip" href="{{ $hashtag['url'] }}">{{ $hashtag['label'] }}</a>
        @endforeach
        @if($post->ai_user_declared || $post->ai_detected_possible)
            <span class="tag">{{ __('ui.ai_content_public_label') }}</span>
        @endif
        <span class="tag">{{ $post->visibilityLabel() }}</span>
    </div>

    @unless($hasMedia)
        <div class="post-actions hnt-post-actions hnt-post-actions-text">
            <div class="action-group">
                <form method="post" action="{{ route('feed.reactions.toggle', $post) }}" class="hnt-inline-form" data-hnt-simple-like-form data-like-target-type="post" data-like-target-id="{{ $post->id }}">
                    @csrf
                    <input type="hidden" name="type" value="like">
                    <button class="action-btn {{ $viewerReaction ? 'active' : '' }}" type="submit" aria-label="{{ __('ui.preview_like_aria') }}" data-hnt-simple-like-button data-liked="{{ $viewerReaction ? '1' : '0' }}">
                        <i class="ph ph-heart" aria-hidden="true"></i>
                        <span data-hnt-simple-like-count data-like-target-type="post" data-like-target-id="{{ $post->id }}">{{ number_format($reactionCount) }}</span>
                    </button>
                </form>
                <button class="action-btn" type="button" aria-label="{{ __('ui.preview_comments_aria') }}" data-post-modal-open data-post-id="{{ $post->id }}" data-post-modal-url="{{ $commentModalUrl }}" data-post-comment-store-url="{{ route('feed.comments.store', $post) }}">
                    <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
                    <span data-hnt-post-comment-count="{{ $post->id }}">{{ number_format($commentCount) }}</span>
                </button>
                <button class="action-btn hnt-share-button" type="button" aria-label="{{ __('ui.preview_share_post_aria') }}" title="{{ __('ui.preview_share_button_title') }}" data-hnt-native-share-button data-share-target-id="{{ $post->id }}" data-share-url="{{ $postUrl }}" data-share-title="{{ e($shareTitle) }}" data-share-text="{{ e($shareText) }}">
                    <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
                    <span data-hnt-share-count data-share-target-id="{{ $post->id }}">{{ $shareCount > 0 ? number_format($shareCount) : '' }}</span>
                </button>
            </div>
            <form method="post" action="{{ route('feed.bookmarks.toggle', $post) }}" class="hnt-inline-form" data-hnt-bookmark-form data-bookmark-target-id="{{ $post->id }}">
                @csrf
                <button class="action-btn hnt-bookmark-icon-button {{ $viewerBookmarked ? 'active' : '' }}" type="submit" aria-label="{{ $viewerBookmarked ? __('ui.preview_bookmark_remove_aria') : __('ui.preview_bookmark_save_aria') }}" title="{{ $viewerBookmarked ? __('ui.preview_saved') : __('ui.preview_save') }}" data-hnt-bookmark-button data-bookmarked="{{ $viewerBookmarked ? '1' : '0' }}">
                    <i class="ph ph-bookmark-simple" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    @endunless
</article>
