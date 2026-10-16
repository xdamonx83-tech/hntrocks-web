@php
    $author = $post->user;
    $authorName = $author?->name ?? 'Hunter';
    $authorAvatar = $author?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $postMedia = $post->media ?? collect();
    $firstMedia = $postMedia->first();
    $postLink = route('feed.show', $post);
    $reactionCount = (int) ($post->reactions_count ?? 0);
    $commentCount = (int) ($post->comments_count ?? 0);
    $isMedia = (bool) $firstMedia;
@endphp
<article class="feed-post {{ $isMedia ? 'media-post' : 'text-post' }}"
    id="post-{{ $post->id }}" data-next-feed-post="{{ $post->id }}"
    data-post-id="{{ $post->id }}"
    data-likes="{{ $reactionCount }}"
    data-time="{{ $post->created_at?->timestamp ?? 0 }}"
    data-share-url="{{ $postLink }}"
    data-post-type="{{ $isMedia ? 'media' : 'text' }}"
    data-post-category="story"
    data-post-format="{{ $isMedia ? ($firstMedia->isVideo() ? 'video' : 'image') : 'text' }}">
    <div class="post-head">
        <span class="post-avatar"><img src="{{ $authorAvatar }}" alt="{{ $authorName }}"></span>
        <div class="post-author">
            <div class="author-line">
                <strong>{{ $authorName }}</strong>
                @if($post->is_pinned)
                    <span class="official-pill">ANGEHEFTET</span>
                @endif
            </div>
            <span>
                @if($post->team){{ $post->team->name }} <i class="meta-sep"></i>@endif
                {{ $post->created_at?->diffForHumans() }}
            </span>
        </div>
        <a class="post-more-btn" href="{{ $postLink }}" title="Originalbeitrag öffnen" aria-label="Beitrag öffnen">
            <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-dots-three"></use></svg>
        </a>
    </div>

    @if($post->body)
        <p class="post-caption">{{ $post->body }}</p>
    @endif

    @if($post->sharedPost)
        <div class="hnt-next-message">Geteilter Beitrag von {{ $post->sharedPost->user?->name ?? 'Hunter' }} – <a href="{{ route('feed.show',$post->sharedPost) }}">Original ansehen ↗</a></div>
    @endif

    @if($firstMedia)
        <div class="media-art">
            @if($firstMedia->isImage())
                <button type="button" class="hnt-next-media-btn" data-next-media data-media-type="image" data-media-url="{{ $firstMedia->url() }}">
                    <img src="{{ $firstMedia->url() }}" loading="lazy" alt="Bild von {{ $authorName }}">
                </button>
            @elseif($firstMedia->isVideo())
                <button type="button" class="hnt-next-media-btn" data-next-media data-media-type="video" data-media-url="{{ $firstMedia->url() }}">
                    <video src="{{ $firstMedia->url() }}" muted playsinline preload="metadata"></video>
                </button>
            @endif
            <span class="media-art-tag">
                <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-image"></use></svg>
                {{ $postMedia->count() > 1 ? $postMedia->count().' MEDIEN' : 'COMMUNITY CAPTURE' }}
            </span>
        </div>
    @endif

    <div class="post-actions">
        <div class="post-actions-primary">
            <button type="button" class="post-action like-btn" data-next-like-preview aria-label="Gefällt mir – noch in Vorbereitung">
                <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-heart"></use></svg>
                <span class="like-count">{{ $reactionCount }}</span>
            </button>
            <button type="button" class="post-action" data-next-comments aria-label="Kommentare anzeigen">
                <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-chat-circle-dots"></use></svg>
                <span class="comment-count">{{ $commentCount }}</span>
            </button>
            <button type="button" class="post-action" data-next-share>
                <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-share-fat"></use></svg>
                <span>Teilen</span>
            </button>
        </div>
        <a href="{{ $postLink }}" class="post-action post-save" aria-label="Zum Originalbeitrag" title="Zum bestehenden Feed">
            <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg>
            <span>Original</span>
        </a>
    </div>
    <template class="hnt-next-comments-template">
        @forelse($post->comments->take(25) as $comment)
            <div class="hnt-next-comment">
                <strong>{{ $comment->user?->name ?? 'Hunter' }}</strong>
                <p>{{ $comment->body ?? '' }}</p>
            </div>
        @empty
            <div class="hnt-next-no-comments">Zu diesem Beitrag gibt es noch keine Kommentare.</div>
        @endforelse
    </template>
</article>
