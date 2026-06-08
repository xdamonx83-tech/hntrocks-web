<div class="hnt-comment-modal-scope" data-hnt-lightbox-scope>
@php
    /** @var \App\Models\FeedPost $post */
    $author = $post->user;
    $authorName = $author?->name ?: ($author?->username ?: __('ui.preview_hnt_hunter'));
    $authorHandle = $author?->username ? '@' . $author->username : '@hntrocks';
    $authorUrl = $author
        ? (((int) $author->id === (int) auth()->id()) ? route('profile.show') : route('profile.public', $author))
        : route('feed.index');
    $bodyHtml = \App\Support\FeedTextRenderer::render($post->body);
    $mediaItems = $post->relationLoaded('media') ? $post->media : collect();
    $firstMedia = $mediaItems->first();
    $comments = $post->relationLoaded('comments') ? $post->comments->sortBy('created_at')->values() : collect();
    $rootComments = $comments->whereNull('parent_id')->values();
    $replyGroups = $comments->whereNotNull('parent_id')->groupBy('parent_id');
    $reactionCount = (int) ($post->reactions_count ?? ($post->relationLoaded('reactions') ? $post->reactions->count() : 0));
    $commentCount = (int) ($post->comments_count ?? $comments->count());
    $viewerReaction = $post->relationLoaded('viewerReaction') ? $post->viewerReaction : null;
@endphp

<div class="post-modal-post-head hnt-comment-modal-post-head">
    <div class="post-author">
        <a class="avatar avatar-sm hnt-avatar-shell" href="{{ $authorUrl }}">
            @if($author)
                <img src="{{ $author->avatarUrl() }}" alt="{{ $authorName }}">
            @endif
        </a>
        <div class="author-info">
            <a class="hnt-author-link" href="{{ $authorUrl }}"><strong>{{ $authorName }}</strong></a>
            <span>{{ $authorHandle }} · {{ optional($post->created_at)->diffForHumans() }} · {{ $post->visibilityLabel() }}</span>
        </div>
    </div>
    <a class="btn-following hnt-comment-modal-open-post" href="{{ $post->permalink() }}">{{ __('ui.preview_open') }}</a>
</div>

@if($bodyHtml !== '')
    <div class="hnt-comment-modal-copy hnt-post-text" data-hnt-modal-post-copy>{!! $bodyHtml !!}</div>
@endif

@if($mediaItems->count() > 1)
    <div class="post-modal-media hnt-post-media-carousel hnt-comment-modal-media" data-hnt-media-carousel aria-label="{{ __('ui.preview_post_media_aria') }}">
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
    <div class="post-modal-media hnt-comment-modal-media hnt-post-media-single">
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
            <a class="hnt-post-media-file" href="{{ $mediaUrl }}" target="_blank" rel="noopener">{{ __('ui.preview_open_file') }}</a>
        @endif
    </div>
@endif

<div class="post-modal-actions hnt-comment-modal-actions">
    <div class="action-group">
        <form method="post" action="{{ route('feed.reactions.toggle', $post) }}" class="hnt-inline-form" data-hnt-simple-like-form data-like-target-type="post" data-like-target-id="{{ $post->id }}">
            @csrf
            <input type="hidden" name="type" value="like">
            <button class="action-btn {{ $viewerReaction ? 'active' : '' }}" type="submit" aria-label="{{ __('ui.preview_like_aria') }}" data-hnt-simple-like-button data-liked="{{ $viewerReaction ? '1' : '0' }}">
                <i class="ph ph-heart" aria-hidden="true"></i>
                <span data-hnt-simple-like-count data-like-target-type="post" data-like-target-id="{{ $post->id }}">{{ number_format($reactionCount) }}</span>
            </button>
        </form>
        <span class="action-btn" aria-label="{{ __('ui.comments') }}">
            <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
            <span data-hnt-modal-comment-count>{{ number_format($commentCount) }}</span>
        </span>
    </div>
    <button class="hnt-like-summary-button {{ $reactionCount > 0 ? '' : 'is-empty' }}" type="button" data-hnt-likes-open data-likes-url="{{ route('feed.reactions.index', $post) }}" data-likes-title="{{ __('ui.preview_likes_title') }}" data-like-target-type="post" data-like-target-id="{{ $post->id }}" @disabled($reactionCount <= 0)>
        <i class="ph ph-heart" aria-hidden="true"></i>
        <span data-hnt-like-summary-count data-like-target-type="post" data-like-target-id="{{ $post->id }}">{{ number_format($reactionCount) }}</span>
        <span>{{ __('ui.preview_comment_likes') }}</span>
    </button>
</div>

<div class="post-modal-comments hnt-comment-modal-comments" data-hnt-comment-list>
    @forelse($rootComments as $comment)
        @include('themes.hnt_preview.feed.partials.comment-item', [
            'post' => $post,
            'comment' => $comment,
            'replies' => $replyGroups->get($comment->id, collect()),
            'reportedFeedKeys' => $reportedFeedKeys ?? collect(),
        ])
    @empty
        <div class="hnt-comment-empty" data-hnt-comment-empty>
            <strong>{{ __('ui.no_comments_yet') }}</strong>
            <span>{{ __('ui.preview_comment_empty_prompt') }}</span>
        </div>
    @endforelse
</div>

</div>
