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
    $postPoll = $post->relationLoaded('poll') ? $post->poll : null;
    $postPollOptions = $postPoll && $postPoll->relationLoaded('options') ? $postPoll->options : collect();
    $postPollVotes = $postPoll && $postPoll->relationLoaded('votes') ? $postPoll->votes : collect();
    $postPollVoteTotal = $postPollVotes->count();
    $postReactionCount = (int) ($post->reactions_count ?? ($post->relationLoaded('reactions') ? $post->reactions->count() : 0));
    $postCommentCount = (int) ($post->comments_count ?? ($post->relationLoaded('comments') ? $post->comments->count() : 0));
    $postBookmarkActive = $post->relationLoaded('viewerBookmark') && (bool) $post->viewerBookmark;
    $postFeeling = method_exists($post, 'feelingMeta') ? $post->feelingMeta() : null;
    $postGif = method_exists($post, 'gifPayload') ? $post->gifPayload() : null;
    $postBadge = $postPoll
        ? 'Umfrage'
        : ($postMedia->contains(fn ($media) => $media->isVideo()) ? 'Video' : ($postMedia->isNotEmpty() || $postGif ? 'Bild' : 'Beitrag'));
@endphp
<article class="social-post profile-real-post" data-profile-post-id="{{ $post->id }}">
<header class="post-head">
<img alt="{{ $postAuthorName }}" src="{{ $postAuthorAvatar }}"/>
<div class="post-author">
<strong>{{ $postAuthorName }}</strong>
<span>{{ $postAuthorHandle }} · {{ $post->created_at?->diffForHumans() }}@if($postFeeling) · {{ trim(($postFeeling['emoji'] ?? '').' '.($postFeeling['label'] ?? '')) }}@endif</span>
</div>
<span class="post-badge">{{ $postBadge }}</span>
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
<div class="profile-real-media-grid profile-real-media-count-{{ min(4, $postMedia->count()) }}">
@foreach($postMedia->take(4) as $media)
@php $mediaUrl = $media->url(); @endphp
<a class="profile-real-media-item {{ $media->isVideo() ? 'is-video' : '' }}" href="{{ $postUrl }}">
@if($media->isImage())
<img alt="{{ $media->original_name ?: 'Beitragsbild' }}" loading="lazy" src="{{ $mediaUrl }}"/>
@elseif($media->isVideo())
<video controls playsinline preload="metadata"><source src="{{ $mediaUrl }}" type="{{ $media->mime_type }}"/></video>
@else
<span>Datei öffnen</span>
@endif
@if($loop->last && $postMedia->count() > 4)
<b class="profile-real-media-more">+{{ $postMedia->count() - 4 }}</b>
@endif
</a>
@endforeach
</div>
@elseif($postGif)
<a class="profile-real-media-grid profile-real-media-count-1" href="{{ $postUrl }}">
<span class="profile-real-media-item"><img alt="{{ $postGif['title'] ?: 'GIF' }}" loading="lazy" src="{{ $postGif['gif_url'] }}"/></span>
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
<a class="{{ $post->viewerReaction ? 'is-active' : '' }}" href="{{ $postUrl }}"><svg><use href="#i-heart"></use></svg><span>{{ $profileFormatCount($postReactionCount) }}</span></a>
<a href="{{ $postUrl }}"><svg><use href="#i-comment"></use></svg><span>{{ $profileFormatCount($postCommentCount) }}</span></a>
<button data-profile-share-url="{{ $postUrl }}" type="button"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<a aria-label="Beitrag {{ $postBookmarkActive ? 'gespeichert' : 'speichern' }}" class="profile-post-save {{ $postBookmarkActive ? 'is-active' : '' }}" href="{{ $postUrl }}"><svg><use href="#i-bookmark"></use></svg></a>
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
