@php
    $viewer = $viewer ?? auth()->user();
    $reactionTypes = $reactionTypes ?? ['like' => 'Like', 'love' => 'Love', 'dislike' => 'Dislike', 'happy' => 'Happy', 'funny' => 'Funny', 'wow' => 'Wow', 'angry' => 'Angry', 'sad' => 'Sad'];
    $isReply = (bool) ($isReply ?? false);
    $rootComment = $rootComment ?? $comment;
    $replies = $replies ?? collect();
    $commentRoute = $comment->user_id === $viewer?->id ? route('profile.show') : route('profile.public', $comment->user);
    $commentViewerReactionType = $comment->viewerReaction?->type;
    $commentReactionCount = $comment->relationLoaded('reactions') ? $comment->reactions->count() : $comment->reactions()->count();
    $replyCount = $replies->count();
    $commentMentionContext = $post->isTeamPost() ? 'team_feed_comment' : 'feed_comment';
    $mentionTeamId = $post->isTeamPost() ? $post->team_id : null;
    $reportedFeedKeys = $reportedFeedKeys ?? collect();
    $commentAlreadyReported = $reportedFeedKeys->has('feed_comment:' . $comment->id);
    $feedTranslationService = app(\App\Services\Translation\FeedTranslationService::class);
    $commentTranslationMeta = $feedTranslationService->translationMeta($comment->body, $comment->source_language ?? null, app()->getLocale());
@endphp

<div id="comment-{{ $comment->id }}" class="post-comment hh-vk-comment-item-fixed {{ $isReply ? 'reply-2 hh-vk-comment-reply' : 'hh-vk-comment-root' }}" data-hh-comment-id="{{ $comment->id }}" data-hh-comment-root="{{ $isReply ? '' : $comment->id }}" data-hh-mention-context="{{ $commentMentionContext }}" @if($mentionTeamId) data-hh-mention-team-id="{{ $mentionTeamId }}" @endif>
    <a class="user-avatar small no-outline" href="{{ $commentRoute }}">
        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $comment->user->avatarUrl() }}"></div></div>
        <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
        <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
        <div class="user-avatar-badge">
            <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
            <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
            <p class="user-avatar-badge-text">{{ $comment->user->level ?? 1 }}</p>
        </div>
    </a>

    <div class="hh-vk-comment-main">
        <p class="post-comment-text"><a class="post-comment-text-author" href="{{ $commentRoute }}">{{ $comment->user->name }}</a> <span data-hh-comment-body-text="{{ $comment->id }}">{!! \App\Support\MentionRenderer::render($comment->body) !!}</span></p>

        @if ($commentTranslationMeta['should_offer'])
            <div class="hh-feed-translation hh-feed-translation--comment" data-hh-translation-wrap>
                <button class="hh-feed-translation-toggle" type="button" data-hh-translation-trigger data-url="{{ route('feed.translation.comment', $comment) }}" data-locale="{{ $commentTranslationMeta['target_locale'] }}" data-label-default="{{ __('ui.translation_show') }}" data-label-loading="{{ __('ui.translation_loading') }}" data-label-error="{{ __('ui.translation_error') }}">
                    <i class="hh-ph-action-icon ph ph-translate" aria-hidden="true"></i>
                    <span>{{ __('ui.translation_show') }}</span>
                </button>
                <div class="hh-feed-translation-result" data-hh-translation-result hidden></div>
            </div>
        @endif

        <div class="content-actions">
            <div class="content-action hh-comment-action-row">
                <div class="meta-line hh-comment-reaction-hover-zone">
                    <button class="meta-line-link light hh-comment-reaction-button {{ $commentViewerReactionType ? 'active' : '' }}" type="button" data-hh-comment-reaction-action data-hh-comment-reaction-main="{{ $comment->id }}" data-comment-id="{{ $comment->id }}" data-url="{{ route('feed.comments.reactions.toggle', $comment) }}" data-type="{{ $commentViewerReactionType ?: 'like' }}" data-mode="toggle" data-current-type="{{ $commentViewerReactionType ?: '' }}">
                        <span class="hh-comment-current-reaction-icon" data-hh-comment-current-reaction-icon="{{ $comment->id }}">
                            @if ($commentViewerReactionType)
                                <img class="hh-comment-current-reaction-image" src="{{ asset('assets/vikinger/img/reaction/' . $commentViewerReactionType . '.png') }}" alt="{{ $reactionTypes[$commentViewerReactionType] ?? 'Reaction' }}">
                            @endif
                        </span>
                        <span data-hh-comment-reaction-label="{{ $comment->id }}">{{ $commentViewerReactionType ? ($reactionTypes[$commentViewerReactionType] ?? 'Like') : 'Like' }}</span>
                        <span class="hh-comment-reaction-count" data-hh-comment-reaction-count="{{ $comment->id }}">{{ $commentReactionCount > 0 ? $commentReactionCount : '' }}</span>
                    </button>

                    <div class="reaction-options reaction-options-dropdown hh-reaction-options-picker hh-comment-reaction-options-picker">
                        @foreach ($reactionTypes as $reaction => $label)
                            <button class="reaction-option text-tooltip-tft hh-reaction-option-button hh-comment-reaction-option-button" data-title="{{ $label }}" type="button" data-hh-comment-reaction-action data-comment-id="{{ $comment->id }}" data-url="{{ route('feed.comments.reactions.toggle', $comment) }}" data-type="{{ $reaction }}" data-mode="set">
                                <img class="reaction-option-image" src="{{ asset('assets/vikinger/img/reaction/' . $reaction . '.png') }}" alt="{{ $label }}">
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="meta-line"><button class="meta-line-link light hh-comment-reply-button" type="button" data-hh-comment-reply data-post-id="{{ $post->id }}" data-parent-id="{{ $rootComment->id }}" data-root-id="{{ $rootComment->id }}" data-target="post-reply-comment-{{ $rootComment->id }}" data-user-name="{{ $comment->user->name }}">Reply</button></div>
                <div class="meta-line"><p class="meta-line-timestamp">{{ $comment->created_at->diffForHumans() }}</p></div>

                @if ($comment->user_id !== auth()->id())
                    <div class="meta-line">
                        <button class="meta-line-link light hh-comment-report-button {{ $commentAlreadyReported ? 'is-reported' : '' }}" type="button" data-hh-report-open data-hh-report-type="feed_comment" data-hh-report-id="{{ $comment->id }}" data-hh-report-label="{{ __('ui.comment_report_label', ['name' => $comment->user->name]) }}" data-hh-report-reported="{{ $commentAlreadyReported ? '1' : '0' }}" aria-disabled="{{ $commentAlreadyReported ? 'true' : 'false' }}" title="{{ $commentAlreadyReported ? __('ui.already_reported') : __('ui.comment_report') }}">
                            <i class="hh-ph-action-icon ph ph-warning-octagon" aria-hidden="true"></i>
                            <span>{{ $commentAlreadyReported ? __('ui.reported_short') : __('ui.report_short') }}</span>
                        </button>
                    </div>
                @endif

                @if ($comment->user_id === auth()->id() || $post->user_id === auth()->id())
                    <div class="meta-line settings hh-comment-settings">
                        <div class="post-settings-wrap hh-comment-settings-wrap">
                            <button class="post-settings hh-comment-settings-toggle" type="button" aria-haspopup="true" aria-expanded="false" data-hh-comment-settings-toggle>
                                <i class="post-settings-icon hh-ph-action-icon ph ph-dots-three" aria-hidden="true"></i>
                            </button>
                            <div class="simple-dropdown hh-comment-settings-dropdown" data-hh-comment-settings-menu>
                                @if ($comment->user_id === auth()->id())
                                    <button class="simple-dropdown-link hh-comment-settings-action" type="button" data-hh-comment-edit data-comment-id="{{ $comment->id }}" data-url="{{ route('feed.comments.update', $comment) }}" data-current-body="{{ $comment->body }}">{{ __('ui.js_i18n_edit') }}</button>
                                @endif
                                <form method="post" action="{{ route('feed.comments.destroy', $comment) }}" data-hh-comment-delete-form data-comment-id="{{ $comment->id }}" data-post-id="{{ $post->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="simple-dropdown-link hh-comment-settings-action" type="submit">{{ __('ui.js_i18n_delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @unless($isReply)
            <div class="hh-comment-reply-slot" data-hh-comment-reply-slot="{{ $comment->id }}">
                <div class="post-comment-form hh-vk-reply-first hh-vk-inline-reply-form" data-hh-comment-inline-composer="{{ $comment->id }}" hidden>
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

                    <form class="form hh-vk-inline-reply-form-inner" method="post" action="{{ route('feed.comments.store', $post) }}" data-hh-comment-form="{{ $post->id }}-{{ $comment->id }}" data-post-id="{{ $post->id }}" data-root-id="{{ $comment->id }}">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}" data-hh-comment-parent="{{ $post->id }}-{{ $comment->id }}">
                        <p class="hh-comment-reply-context" data-hh-inline-reply-context="{{ $comment->id }}" hidden>{{ __('ui.js_i18n_reply_to', ['name' => $comment->user->name]) }}</p>
                        <div class="form-row">
                            <div class="form-item">
                                <div class="form-input small">
                                    <label for="post-reply-comment-{{ $comment->id }}">{{ __('ui.your_reply') }}</label>
                                    <input type="text" id="post-reply-comment-{{ $comment->id }}" name="body" maxlength="2000" required data-hh-comment-counter="post-reply-comment-count-{{ $comment->id }}" data-hh-mention-context="{{ $commentMentionContext }}" @if($mentionTeamId) data-hh-mention-team-id="{{ $mentionTeamId }}" @endif>
                                </div>
                            </div>
                        </div>
                        <div class="hh-vk-inline-reply-meta">
                            <span class="hh-vk-inline-reply-count" id="post-reply-comment-count-{{ $comment->id }}">2000/2000</span>
                        </div>
                    </form>
                </div>
            </div>

            @if ($replyCount > 0)
                @php
                    $replyCountMarkup = '<span class="hh-comment-load-replies-count">' . $replyCount . '</span>';
                @endphp
                <button class="hh-comment-load-replies" type="button" data-hh-load-comment-replies data-replies-root="{{ $comment->id }}" data-reply-count="{{ $replyCount }}" aria-expanded="false">
                    {!! trans_choice('ui.comment_load_replies', $replyCount, ['count' => $replyCountMarkup]) !!}
                </button>
            @endif

            <div class="hh-vk-comment-replies {{ $replyCount > 0 ? 'is-collapsed' : '' }}" data-hh-comment-replies-root="{{ $comment->id }}" @if ($replyCount > 0) hidden @endif>
                @foreach ($replies as $reply)
                    @include('feed.partials.comment-item', ['post' => $post, 'comment' => $reply, 'viewer' => $viewer, 'reactionTypes' => $reactionTypes, 'isReply' => true, 'rootComment' => $comment])
                @endforeach
            </div>
        @endunless
    </div>
</div>
