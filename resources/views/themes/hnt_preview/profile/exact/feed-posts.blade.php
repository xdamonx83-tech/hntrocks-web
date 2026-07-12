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
    $postBadge = $postPoll ? 'Umfrage' : ($postHasVideo ? 'Video' : ($postHasImage ? 'Bild' : 'Beitrag'));
    $postBadgeClass = $postPoll ? 'cup' : ($postHasVideo ? 'moment' : ($postHasImage ? '' : 'discussion'));
@endphp
<article class="social-post real-feed-post profile-real-post" data-profile-post-id="{{ $post->id }}" data-real-feed-post="{{ $post->id }}" data-real-permalink="{{ $postUrl }}">
<header class="post-head">
<img alt="{{ $postAuthorName }}" src="{{ $postAuthorAvatar }}"/>
<div class="post-author">
<strong>{{ $postAuthorName }}</strong>
<span>{{ $postAuthorHandle }} · {{ $post->created_at?->diffForHumans() }}@if($postFeeling) · {{ trim(($postFeeling['emoji'] ?? '').' '.($postFeeling['label'] ?? '')) }}@endif</span>
</div>
<span class="post-badge {{ $postBadgeClass }}">{{ $postBadge }}</span>
<a aria-label="Beitrag öffnen" class="post-more" href="{{ $postUrl }}"><svg><use href="#i-arrow"></use></svg></a>
</header>
<div class="post-body">
@if(trim((string) $post->body) !== '')
<p>{!! \App\Support\FeedTextRenderer::render($post->body) !!}</p>
@endif
@if($post->isSharedPost() && $post->sharedPost)
<a class="profile-shared-post" href="{{ $post->sharedPost->permalink() }}">
<span>GETEILTER BEITRAG</span>
<strong>{{ $post->sharedPost->user?->name ?: 'HNT Hunter' }}</strong>
<small>{{ $post->sharedPost->excerpt(180) ?: 'Beitrag öffnen' }}</small>
</a>
@endif
@if($postMedia->isNotEmpty())
<div class="profile-real-media-grid real-post-media-grid profile-real-media-count-{{ $postMediaCount }} real-post-media-count-{{ $postMediaCount }}">
@foreach($postMedia->take(4) as $media)
@php $mediaUrl = $media->url(); @endphp
<a class="profile-real-media-item real-post-media-item {{ $media->isVideo() ? 'is-video real-post-video-item' : '' }}" href="{{ $postUrl }}">
@if($media->isImage())
<img alt="{{ $media->original_name ?: 'Beitragsbild' }}" loading="lazy" src="{{ $mediaUrl }}"/>
@elseif($media->isVideo())
<video controls playsinline preload="metadata"><source src="{{ $mediaUrl }}" type="{{ $media->mime_type }}"/></video>
@else
<span>Datei öffnen</span>
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
<div class="profile-real-poll">
<strong>{{ $postPoll->question }}</strong>
@foreach($postPollOptions as $option)
@php
    $optionVotes = $option->relationLoaded('votes') ? $option->votes->count() : 0;
    $optionPercent = $postPollVoteTotal > 0 ? (int) round(($optionVotes / $postPollVoteTotal) * 100) : 0;
@endphp
<a href="{{ $postUrl }}"><span>{{ $option->body }}</span><b>{{ $optionPercent }}%</b><i style="width:{{ $optionPercent }}%"></i></a>
@endforeach
<small>{{ $postPollVoteTotal }} {{ $postPollVoteTotal === 1 ? 'Stimme' : 'Stimmen' }}</small>
</div>
@endif
</div>
<footer class="post-actions">
<button class="like-button {{ $postReactionActive ? 'liked is-active' : '' }}" data-profile-like-url="{{ route('feed.reactions.toggle', $post) }}" type="button"><svg><use href="#i-heart"></use></svg><span>{{ $profileFormatCount($postReactionCount) }}</span></button>
<button aria-label="Kommentare öffnen" class="comment-button" data-real-preview-comments type="button"><svg><use href="#i-comment"></use></svg><span>{{ $profileFormatCount($postCommentCount) }}</span></button>
<button data-profile-share-url="{{ $postUrl }}" type="button"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button aria-label="Beitrag {{ $postBookmarkActive ? 'gespeichert' : 'speichern' }}" class="profile-post-save save-button {{ $postBookmarkActive ? 'saved is-active' : '' }}" data-profile-bookmark-url="{{ route('feed.bookmarks.toggle', $post) }}" type="button"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
@empty
<div class="profile-empty-state profile-post-empty">
<strong>Noch keine Beiträge</strong>
<p>Veröffentlichte Posts erscheinen hier.</p>
@if($isOwnProfile)
<button id="openEmptyPostComposer" type="button"><svg><use href="#i-plus"></use></svg> Ersten Post erstellen</button>
@endif
</div>
@endforelse
@if(($profilePostsTotal ?? $profilePostsLive->count()) > $profilePostsLive->count())
<a class="profile-post-more-link" href="{{ request()->fullUrlWithQuery(['profile_posts_page' => (int) ($profilePostPage ?? 1) + 1]) }}">Weitere Beiträge laden <svg><use href="#i-arrow"></use></svg></a>
@endif
</div>
</section>
