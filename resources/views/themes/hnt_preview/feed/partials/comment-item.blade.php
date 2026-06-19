@php
    /** @var \App\Models\FeedComment $comment */
    $commentUser = $comment->user;
    $commentUserName = $commentUser?->name ?: ($commentUser?->username ?: __('ui.preview_hnt_hunter'));
    $commentUserUrl = $commentUser
        ? (((int) $commentUser->id === (int) auth()->id()) ? route('profile.show') : route('profile.public', $commentUser))
        : route('feed.index');
    $commentReactionCount = $comment->relationLoaded('reactions') ? $comment->reactions->count() : 0;
    $commentViewerReaction = $comment->relationLoaded('viewerReaction') ? $comment->viewerReaction : null;
    $reportedFeedKeys = $reportedFeedKeys ?? collect();
    $commentAlreadyReported = method_exists($reportedFeedKeys, 'has') ? $reportedFeedKeys->has('feed_comment:' . $comment->id) : false;
    $viewer = auth()->user();
    $viewerId = (int) ($viewer?->id ?? 0);
    $viewerIsAdmin = $viewer && method_exists($viewer, 'isAdmin') && $viewer->isAdmin();
    $isOwnComment = $viewerId > 0 && (int) $comment->user_id === $viewerId;
    $canDeleteComment = $isOwnComment || $viewerIsAdmin || ($viewerId > 0 && isset($post) && (int) $post->user_id === $viewerId);
    $isReplyComment = ! empty($comment->parent_id);
    $commentDepth = (int) ($commentDepth ?? ($isReplyComment ? 1 : 0));
    $commentMediaItems = $comment->relationLoaded('media')
        ? $comment->media->filter(fn ($media) => $media->isImage())->take(4)->values()
        : collect();
@endphp

<div class="post-comment hnt-comment-modal-item {{ $isReplyComment ? 'is-reply' : 'is-root' }}" id="comment-{{ $comment->id }}" data-hnt-comment-item="{{ $comment->id }}" data-hnt-comment-depth="{{ $commentDepth }}">
    <a class="avatar avatar-sm hnt-avatar-shell" href="{{ $commentUserUrl }}">
        @if($commentUser)
            <img src="{{ $commentUser->avatarUrl() }}" alt="{{ $commentUserName }}">
        @endif
    </a>
    <div class="hnt-comment-modal-main">
        <div class="post-comment-bubble hnt-comment-modal-bubble">
            <div class="hnt-comment-modal-headline">
                <a href="{{ $commentUserUrl }}">{{ $commentUserName }}</a>
                <span>{{ optional($comment->created_at)->diffForHumans() }}</span>
            </div>
            <div class="hnt-comment-modal-body" data-hnt-comment-body>{!! \App\Support\FeedTextRenderer::render($comment->body) !!}</div>
            @if($commentMediaItems->isNotEmpty())
                <div class="hnt-comment-media-grid hnt-comment-media-count-{{ $commentMediaItems->count() }}" data-hnt-lightbox-scope>
                    @foreach($commentMediaItems as $media)
                        @php
                            $mediaUrl = $media->url();
                            $mediaAlt = $media->original_name ?: __('ui.preview_comment_image_preview');
                        @endphp
                        <a class="hnt-comment-media-thumb" href="{{ $mediaUrl }}" data-hnt-lightbox-trigger data-hnt-lightbox-src="{{ $mediaUrl }}" data-hnt-lightbox-alt="{{ $mediaAlt }}" aria-label="{{ $mediaAlt }}">
                            <img src="{{ $mediaUrl }}" alt="{{ $mediaAlt }}" loading="lazy">
                        </a>
                    @endforeach
                </div>
            @endif
            @if($isOwnComment)
                <form class="hnt-comment-edit-form" action="{{ route('feed.comments.update', $comment) }}" method="post" data-hnt-comment-edit-form hidden>
                    @csrf
                    @method('PATCH')
                    <textarea name="body" rows="3" data-hnt-comment-edit-textarea>{{ $comment->body }}</textarea>
                    <p class="hnt-comment-edit-status" data-hnt-comment-edit-status hidden></p>
                    <div class="hnt-comment-edit-actions">
                        <button class="btn-following hnt-comment-edit-cancel" type="button" data-hnt-comment-edit-cancel>{{ __('ui.preview_action_cancel') }}</button>
                        <button class="btn-create hnt-comment-edit-save" type="submit" data-hnt-comment-edit-submit>{{ __('ui.preview_action_save') }}</button>
                    </div>
                </form>
            @endif
        </div>
        <div class="post-comment-meta hnt-comment-modal-meta hnt-comment-icon-actions">
            <form method="post" action="{{ route('feed.comments.reactions.toggle', $comment) }}" class="hnt-inline-form" data-hnt-simple-like-form data-like-target-type="comment" data-like-target-id="{{ $comment->id }}">
                @csrf
                <input type="hidden" name="type" value="like">
                <button class="hnt-comment-action-icon hnt-comment-like-button {{ $commentViewerReaction ? 'active' : '' }}" type="submit" data-hnt-simple-like-button data-liked="{{ $commentViewerReaction ? '1' : '0' }}" data-tooltip="{{ __('ui.preview_comment_like') }}" aria-label="{{ __('ui.preview_comment_like') }}" aria-pressed="{{ $commentViewerReaction ? 'true' : 'false' }}">
                    <i class="ph ph-heart" aria-hidden="true"></i>
                    <span data-hnt-simple-like-count data-like-target-type="comment" data-like-target-id="{{ $comment->id }}">{{ number_format($commentReactionCount) }}</span>
                </button>
            </form>
            <button class="hnt-comment-action-icon" type="button" data-hnt-comment-reply-toggle data-tooltip="{{ __('ui.preview_comment_reply') }}" aria-label="{{ __('ui.preview_comment_reply') }}" aria-expanded="false">
                <i class="ph ph-arrow-bend-up-left" aria-hidden="true"></i>
            </button>
            @if($isOwnComment)
                <button class="hnt-comment-action-icon" type="button" data-hnt-comment-edit-open data-tooltip="{{ __('ui.preview_action_edit') }}" aria-label="{{ __('ui.preview_comment_edit') }}">
                    <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                </button>
            @endif
            @if($canDeleteComment)
                <button class="hnt-comment-action-icon hnt-comment-delete-button" type="button" data-hnt-comment-delete data-comment-id="{{ $comment->id }}" data-delete-url="{{ route('feed.comments.destroy', $comment) }}" data-tooltip="{{ __('ui.preview_post_delete') }}" aria-label="{{ __('ui.preview_comment_delete') }}">
                    <i class="ph ph-trash" aria-hidden="true"></i>
                </button>
            @endif
            @if($isOwnComment)
                <span class="hnt-comment-action-icon is-static" data-tooltip="{{ __('ui.preview_comment_own') }}" aria-label="{{ __('ui.preview_comment_own') }}">
                    <i class="ph ph-check" aria-hidden="true"></i>
                </span>
            @else
                <button class="hnt-comment-action-icon hnt-comment-report-link {{ $commentAlreadyReported ? 'is-reported' : '' }}" type="button" data-hnt-report-open data-report-type="feed_comment" data-report-id="{{ $comment->id }}" data-report-label="{{ __('ui.preview_comment_report_label', ['name' => $commentUserName]) }}" data-report-reported="{{ $commentAlreadyReported ? '1' : '0' }}" data-tooltip="{{ $commentAlreadyReported ? __('ui.preview_comment_reported_short') : __('ui.preview_post_report') }}" aria-label="{{ $commentAlreadyReported ? __('ui.preview_comment_reported') : __('ui.preview_comment_report') }}" @disabled($commentAlreadyReported)>
                    <i class="ph ph-shield-check" aria-hidden="true"></i>
                </button>
            @endif
            @if($commentReactionCount > 0)
                <button class="hnt-comment-like-summary" type="button" data-hnt-likes-open data-likes-url="{{ route('feed.comments.reactions.index', $comment) }}" data-likes-title="{{ __('ui.preview_likes_title') }}" data-like-target-type="comment" data-like-target-id="{{ $comment->id }}" data-tooltip="{{ __('ui.preview_comment_view_likes') }}">
                    <span data-hnt-like-summary-count data-like-target-type="comment" data-like-target-id="{{ $comment->id }}">{{ number_format($commentReactionCount) }}</span> {{ __('ui.preview_comment_likes') }}
                </button>
            @else
                <button class="hnt-comment-like-summary is-empty" type="button" data-hnt-likes-open data-likes-url="{{ route('feed.comments.reactions.index', $comment) }}" data-likes-title="{{ __('ui.preview_likes_title') }}" data-like-target-type="comment" data-like-target-id="{{ $comment->id }}" data-tooltip="{{ __('ui.preview_comment_view_likes') }}" disabled>
                    <span data-hnt-like-summary-count data-like-target-type="comment" data-like-target-id="{{ $comment->id }}">0</span> {{ __('ui.preview_comment_likes') }}
                </button>
            @endif
        </div>

        @if(isset($post))
            <form class="hnt-comment-reply-composer" action="{{ route('feed.comments.store', $post) }}" method="post" data-hnt-comment-reply-form hidden>
                @csrf
                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                <div class="hnt-comment-reply-input">
                    <textarea name="body" rows="2" maxlength="50000" placeholder="{{ __('ui.reply_to_user', ['name' => $commentUserName]) }}" data-hnt-comment-reply-textarea></textarea>
                    <input type="file" name="media[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden data-hnt-comment-media-input>
                    <div class="hnt-comment-media-preview hnt-comment-reply-media-preview" data-hnt-comment-media-preview hidden></div>
                    <button class="hnt-comment-media-button hnt-comment-reply-media-button" type="button" data-hnt-comment-media-trigger aria-label="{{ __('ui.preview_comment_add_image') }}" title="{{ __('ui.preview_comment_add_image') }}">
                        <i class="ph ph-image-square" aria-hidden="true"></i>
                    </button>
                    <button class="post-modal-send hnt-comment-reply-submit" type="submit" aria-label="{{ __('ui.preview_post_modal_send_aria') }}" data-hnt-comment-reply-submit>
                        <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="hnt-comment-modal-status hnt-comment-reply-status" data-hnt-comment-reply-status hidden></p>
            </form>
        @endif

        @if(($replies ?? collect())->isNotEmpty())
            <div class="hnt-comment-modal-replies">
                @foreach($replies as $reply)
                    @include('themes.hnt_preview.feed.partials.comment-item', [
                        'post' => $post,
                        'comment' => $reply,
                        'replies' => collect(),
                        'reportedFeedKeys' => $reportedFeedKeys,
                        'commentDepth' => $commentDepth + 1,
                    ])
                @endforeach
            </div>
        @endif
    </div>
</div>
