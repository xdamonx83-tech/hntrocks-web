@php
    $profilePostsLive = collect($profilePosts ?? []);
@endphp
<section class="profile-tab-panel active" data-profile-panel="posts" id="profileTabPosts" role="tabpanel">
<div class="post-list profile-post-list">
@forelse($profilePostsLive as $post)
@php
    $postAuthor = $post->user ?: $profileUser;
    $postAuthorName = trim((string) ($postAuthor?->name ?: $postAuthor?->username ?: $profileDisplayName));
    $postAuthorHandle = $postAuthor?->username ? '@'.$postAuthor->username : '@hunter';
    $postAuthorAvatar = $postAuthor?->avatarUrl() ?: $profileAvatarUrl;
    $postAuthorUrl = $postAuthor?->username
        ? ((int) $postAuthor->id === (int) auth()->id() ? route('profile.show') : route('profile.public', $postAuthor))
        : '#';
    $postUrl = $post->permalink();
    $postMedia = $post->relationLoaded('media') ? $post->media->values() : collect();
    $postMediaCount = min(4, $postMedia->count());
    $postPoll = $post->relationLoaded('poll') ? $post->poll : null;
    $postPollOptions = $postPoll && $postPoll->relationLoaded('options') ? $postPoll->options : collect();
    $postPollVotes = $postPoll && $postPoll->relationLoaded('votes') ? $postPoll->votes : collect();
    $postPollVoteTotal = $postPollVotes->count();
    $postReactionCount = (int) ($post->reactions_count ?? ($post->relationLoaded('reactions') ? $post->reactions->count() : 0));
    $postCommentCount = (int) ($post->comments_count ?? ($post->relationLoaded('comments') ? $post->comments->count() : 0));
    $postReactionActive = $post->relationLoaded('viewerReaction') && (bool) $post->viewerReaction;
    $postBookmarkActive = $post->relationLoaded('viewerBookmark') && (bool) $post->viewerBookmark;
    $postFeeling = method_exists($post, 'feelingMeta') ? $post->feelingMeta() : null;
    $postGif = method_exists($post, 'gifPayload') ? $post->gifPayload() : null;
    $postHasVideo = $postMedia->contains(fn ($media) => $media->isVideo());
    $postHasImage = $postMedia->contains(fn ($media) => $media->isImage()) || $postGif;
    $postBadge = $postPoll
        ? __('hnt_preview.profile.poll')
        : ($postHasVideo
            ? __('hnt_preview.profile.video')
            : ($postHasImage ? __('hnt_preview.profile.image') : __('hnt_preview.profile.post')));
    $postBadgeClass = $postPoll ? 'cup' : ($postHasVideo ? 'moment' : ($postHasImage ? '' : 'discussion'));
@endphp
<article class="social-post real-feed-post profile-real-post"
         data-profile-post-id="{{ $post->id }}"
         data-real-feed-post="{{ $post->id }}"
         data-real-permalink="{{ $postUrl }}"
         data-feed-translation-url="{{ route('feed.translation.post', $post) }}">
<header class="post-head">
<a class="real-feed-author-avatar" href="{{ $postAuthorUrl }}"><img alt="{{ $postAuthorName }}" src="{{ $postAuthorAvatar }}"/></a>
<div class="post-author">
<a href="{{ $postAuthorUrl }}"><strong>{{ $postAuthorName }}</strong></a>
<span>{{ $postAuthorHandle }} · {{ $post->created_at?->diffForHumans() }} · {{ $post->visibilityLabel() }}@if($postFeeling) · {{ trim(($postFeeling['emoji'] ?? '').' '.($postFeeling['label'] ?? '')) }}@endif</span>
</div>
<span class="post-badge {{ $postBadgeClass }}">{{ $postBadge }}</span>
<a aria-label="{{ __('hnt_preview.profile.open_post') }}" class="post-more" href="{{ $postUrl }}"><svg><use href="#i-more"></use></svg></a>
</header>
<div class="post-body">
@if(trim((string) $post->body) !== '')
<p>{!! \App\Support\FeedTextRenderer::render($post->body) !!}</p>
@endif
@if($post->isSharedPost() && $post->sharedPost)
<a class="profile-shared-post" href="{{ $post->sharedPost->permalink() }}">
<span>{{ __('hnt_preview.profile.shared_post') }}</span>
<strong>{{ $post->sharedPost->user?->name ?: 'HNT Hunter' }}</strong>
<small>{{ $post->sharedPost->excerpt(180) ?: __('hnt_preview.profile.open_post') }}</small>
</a>
@endif
@if($postMedia->isNotEmpty())
<div class="profile-real-media-grid real-post-media-grid profile-real-media-count-{{ $postMediaCount }} real-post-media-count-{{ $postMediaCount }}">
@foreach($postMedia->take(4) as $media)
@php $mediaUrl = $media->url(); @endphp
<a class="profile-real-media-item real-post-media-item {{ $media->isVideo() ? 'is-video real-post-video-item' : '' }}" href="{{ $postUrl }}">
@if($media->isImage())
<img alt="{{ $media->original_name ?: __('hnt_preview.profile.post_image') }}" loading="lazy" src="{{ $mediaUrl }}"/>
@elseif($media->isVideo())
<video controls muted playsinline preload="metadata"><source src="{{ $mediaUrl }}" type="{{ $media->mime_type }}"/></video>
@else
<span>{{ __('hnt_preview.profile.open_file') }}</span>
@endif
@if($loop->last && $postMedia->count() > 4)
<b class="profile-real-media-more real-post-media-more">+{{ $postMedia->count() - 4 }}</b>
@endif
</a>
@endforeach
</div>
@elseif($postGif)
<a class="profile-real-media-grid real-post-media-grid profile-real-media-count-1 real-post-media-count-1" href="{{ $postUrl }}">
<span class="profile-real-media-item real-post-media-item"><img alt="{{ $postGif['title'] ?: 'GIF' }}" loading="lazy" src="{{ $postGif['gif_url'] }}"/></span>
</a>
@endif
@if($postPoll && $postPollOptions->isNotEmpty())
<div class="profile-real-poll real-feed-poll">
<strong class="real-feed-poll-question">{{ $postPoll->question }}</strong>
@foreach($postPollOptions as $option)
@php
    $optionVotes = $option->relationLoaded('votes') ? $option->votes->count() : 0;
    $optionPercent = $postPollVoteTotal > 0 ? (int) round(($optionVotes / $postPollVoteTotal) * 100) : 0;
@endphp
<a href="{{ $postUrl }}"><span>{{ $option->body }}</span><b>{{ $optionPercent }}%</b><i style="width:{{ $optionPercent }}%"></i></a>
@endforeach
<small>{{ $postPollVoteTotal }} {{ $postPollVoteTotal === 1 ? __('hnt_preview.profile.vote') : __('hnt_preview.profile.votes') }}</small>
</div>
@endif
</div>
<footer class="post-actions">
<button class="like-button {{ $postReactionActive ? 'liked is-active' : '' }}" data-profile-like-url="{{ route('feed.reactions.toggle', $post) }}" type="button"><svg><use href="#i-heart"></use></svg><span>{{ $profileFormatCount($postReactionCount) }}</span></button>
<button aria-label="{{ __('hnt_preview.profile.open_comments') }}" class="comment-button" data-real-preview-comments type="button"><svg><use href="#i-comment"></use></svg><span>{{ $profileFormatCount($postCommentCount) }}</span></button>
<button data-profile-share-url="{{ $postUrl }}" type="button"><svg><use href="#i-share"></use></svg><span>{{ __('hnt_preview.profile.share') }}</span></button>
<button aria-label="{{ __('hnt_preview.profile.post') }} {{ $postBookmarkActive ? __('hnt_preview.profile.saved') : __('hnt_preview.profile.save') }}" class="profile-post-save save-button {{ $postBookmarkActive ? 'saved is-active' : '' }}" data-profile-bookmark-url="{{ route('feed.bookmarks.toggle', $post) }}" type="button"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
@empty
<div class="profile-empty-state profile-post-empty">
<strong>{{ __('hnt_preview.profile.no_posts') }}</strong>
<p>{{ __('hnt_preview.profile.published_posts_here') }}</p>
@if($isOwnProfile)
<button id="openEmptyPostComposer" type="button"><svg><use href="#i-plus"></use></svg> {{ __('hnt_preview.profile.first_post') }}</button>
@endif
</div>
@endforelse
@if(($profilePostsTotal ?? $profilePostsLive->count()) > $profilePostsLive->count())
<a class="profile-post-more-link" href="{{ request()->fullUrlWithQuery(['profile_posts_page' => (int) ($profilePostPage ?? 1) + 1]) }}">{{ __('hnt_preview.profile.load_more') }} <svg><use href="#i-arrow"></use></svg></a>
@endif
</div>
</section>
