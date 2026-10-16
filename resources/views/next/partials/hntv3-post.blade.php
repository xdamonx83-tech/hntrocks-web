@php
    $author = $post->user;
    $authorName = $author?->name ?: 'Hunter';
    $authorAvatar = $author?->avatarUrl() ?: '/build/next/hntv3-assets/avatar-hnt.webp';
    $attachments = $post->media;
    $firstImage = $attachments->first(fn ($item) => $item->isImage());
    $firstVideo = $attachments->first(fn ($item) => $item->isVideo());
    $previewMedia = $firstImage ?: $firstVideo;
    $isMedia = $previewMedia !== null;
    $isBookmarked = (bool) $post->viewerBookmark;
    $likeCount = (int) ($post->reactions_count ?? 0);
    $commentCount = (int) ($post->comments_count ?? 0);
    $minutesAgo = max(0, (int) ($post->created_at?->diffInMinutes(now()) ?? 0));
    $postLink = $post->permalink();
    $previewComments = $post->comments->take(100);
    $previewComments->loadCount('reactions');
    $previewComments->load('viewerReaction');
    $commentsForPreview = $previewComments
        ->map(fn ($comment) => [
            'id' => (string) $comment->id,
            'name' => (string) ($comment->user?->name ?: 'Hunter'),
            'initials' => mb_strtoupper(mb_substr((string) ($comment->user?->name ?: 'H'), 0, 2)),
            'time' => $comment->created_at?->diffForHumans() ?: '',
            'message' => (string) ($comment->body ?: ''),
            'parentId' => $comment->parent_id ? (string) $comment->parent_id : null,
            'reactionsCount' => (int) ($comment->reactions_count ?? 0),
            'viewerLiked' => (bool) $comment->viewerReaction,
        ])->values()->all();
@endphp
<article class="feed-post {{ $isMedia ? 'media-post' : 'text-post' }} {{ $isBookmarked ? 'user-saved' : '' }}"
    id="post-{{ $post->id }}"
    data-next-feed-post="{{ $post->id }}"
    data-following="{{ $feedFilter === 'friends' ? '1' : '0' }}"
    data-likes="{{ $likeCount }}"
    data-time="{{ $minutesAgo }}"
    data-post-type="{{ $firstVideo && !$firstImage ? 'clip' : ($isMedia ? 'media' : 'text') }}"
    data-post-category="story"
    data-post-format="{{ $firstVideo && !$firstImage ? 'clip' : ($isMedia ? 'image' : 'text') }}"
    data-permalink="{{ $postLink }}">
    <div class="post-head">
        <span class="post-avatar"><img src="{{ $authorAvatar }}" alt="{{ $authorName }}" loading="lazy"></span>
        <div class="post-author">
            <div class="author-line"><strong>{{ $authorName }}</strong>
                @if($post->is_pinned)
                    <span class="official-pill">ANGEHEFTET</span>
                @endif
            </div>
            <span>
                @if($post->team){{ $post->team->name }} <i class="meta-sep"></i>@endif
                {{ $post->created_at?->diffForHumans() ?: 'Gerade eben' }}
            </span>
        </div>
        <button type="button" class="post-more-btn" data-more aria-label="Beitragsoptionen" aria-expanded="false">
            <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-dots-three"></use></svg>
        </button>
        <div class="post-more-menu" hidden>
            <button type="button" data-save>Beitrag merken (Vorschau)</button>
            <button type="button" data-share>Link teilen</button>
            <a href="{{ $postLink }}" class="hnt-live-permalink">Original öffnen</a>
        </div>
    </div>
    @if($post->body)
        <p class="post-caption">{{ $post->body }}</p>
    @endif
    @if($post->shared_post_id)
        @php
            $original = $post->sharedPost;
            $originalVisible = $original && $original->status === 'published' && $original->canBeViewedBy(auth()->user());
            $originalAuthor = $originalVisible ? $original->user : null;
            $originalAttachments = $originalVisible ? $original->media : collect();
            $originalImage = $originalAttachments->first(fn ($media) => $media->isImage());
            $originalVideo = $originalAttachments->first(fn ($media) => $media->isVideo());
            $originalLink = $originalVisible ? $original->permalink() : null;
        @endphp
        @if($originalVisible)
            <div class="hnt-share-embed hnt-live-share-embed" data-original-post="{{ $original->id }}">
                @if($originalImage)
                    <a class="hnt-live-share-media-link" href="{{ $originalLink }}" aria-label="Originalaufnahme öffnen">
                        <div class="hnt-share-original-media">
                            <img src="{{ $originalImage->url() }}" loading="lazy" alt="Aufnahme von {{ $originalAuthor?->name ?: 'Hunter' }}">
                            @if($originalAttachments->count() > 1)
                                <span class="hnt-share-media-stamp">{{ $originalAttachments->count() }} MEDIEN</span>
                            @endif
                        </div>
                    </a>
                @elseif($originalVideo)
                    <div class="hnt-share-original-media hnt-live-share-video">
                        <video controls playsinline preload="metadata" src="{{ $originalVideo->url() }}" aria-label="Clip von {{ $originalAuthor?->name ?: 'Hunter' }}"></video>
                    </div>
                @endif
                <div class="hnt-share-original-body">
                    <div class="hnt-share-original-head">
                        <img class="hnt-share-author-avatar" loading="lazy" src="{{ $originalAuthor?->avatarUrl() ?: '/build/next/hntv3-assets/avatar-hnt.webp' }}" alt="">
                        <div class="hnt-share-original-author">
                            <strong>{{ $originalAuthor?->name ?: 'Hunter' }}</strong>
                            <small>{{ $original->created_at?->diffForHumans() ?: 'Originalbeitrag' }}</small>
                        </div>
                        <span class="hnt-share-kind">{{ $originalImage ? 'CAPTURE' : ($originalVideo ? 'CLIP' : 'BEITRAG') }}</span>
                    </div>
                    @if($original->body)
                        <p class="hnt-share-original-caption">{{ $original->body }}</p>
                    @elseif(!$originalImage && !$originalVideo)
                        <p class="hnt-share-original-caption">Originalbeitrag aus der HNT.ROCKS Community</p>
                    @endif
                    <div class="hnt-share-original-meta">
                        <span>HNT.ROCKS · Community</span><span>ORIGINALBEITRAG</span>
                    </div>
                </div>
                <a class="hnt-share-visit" href="{{ $originalLink }}">
                    Originalbeitrag ansehen
                    <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg>
                </a>
            </div>
        @else
            <div class="hnt-live-shared">Dieser Originalbeitrag ist nicht mehr verfügbar.</div>
        @endif
    @endif
    @if($previewMedia)
        @if($firstImage)
            <div class="media-art">
                <img src="{{ $firstImage->url() }}" alt="Aufnahme von {{ $authorName }}" loading="lazy">
                <span class="media-art-tag">
                    <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-image"></use></svg>
                    {{ $attachments->count() > 1 ? $attachments->count().' MEDIEN' : 'COMMUNITY CAPTURE' }}
                </span>
            </div>
        @elseif($firstVideo)
            <div class="media-art hnt-live-video">
                <video-player class="hnt-videojs-player" aria-label="Videobeitrag von {{ $authorName }}">
  <video-neutral-skin>
    <video playsinline preload="metadata" src="{{ $firstVideo->url() }}" aria-label="Videobeitrag von {{ $authorName }}">
      Dein Browser unterstützt die Videowiedergabe nicht. <a href="{{ $firstVideo->url() }}">Video öffnen</a>
    </video>
  </video-neutral-skin>
</video-player>
                <span class="media-art-tag">
                    <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-film-strip"></use></svg>
                    COMMUNITY CLIP
                </span>
            </div>
        @endif
        @foreach($attachments as $media)
            @if($firstImage && $media->id !== $firstImage->id && $media->isImage())
                <div data-post-media hidden><img src="{{ $media->url() }}" loading="lazy" alt="Weiteres Bild von {{ $authorName }}"></div>
            @endif
        @endforeach
    @endif
    @if($post->poll)
        <div class="hnt-live-shared">Umfrage · Ergebnisse und Abstimmung werden im nächsten Schritt angebunden.</div>
    @endif
    <div class="post-actions">
        <div class="post-actions-primary">
            <button type="button" class="post-action like-btn {{ $post->viewerReaction ? 'is-liked' : '' }}" data-like aria-pressed="{{ $post->viewerReaction ? 'true' : 'false' }}" aria-label="Beitrag gefällt mir">
                <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-heart"></use></svg>
                <span class="like-count">{{ $likeCount }}</span>
            </button>
            <button type="button" class="post-action" data-comments-toggle aria-expanded="false" aria-label="Kommentare anzeigen">
                <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-chat-circle-dots"></use></svg>
                <span class="comment-count">{{ $commentCount }}</span>
            </button>
            <button type="button" class="post-action" data-share>
                <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-share-fat"></use></svg>
                <span>Teilen</span>
            </button>
        </div>
        <button type="button" class="post-action post-save {{ $isBookmarked ? 'is-saved' : '' }}" data-save aria-pressed="{{ $isBookmarked ? 'true' : 'false' }}">
            <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-bookmark-simple"></use></svg><span>{{ $isBookmarked ? 'Gemerkt' : 'Merken' }}</span>
        </button>
    </div>
    <div class="post-comment-box" hidden>
        <div class="comment-preview"><span class="small-avatar avatar-initial">HN</span><span>Kommentare aus HNT.ROCKS ansehen.</span></div>
        <form class="comment-form">
            <input aria-label="Kommentar schreiben" maxlength="280" placeholder="Kommentare schreiben folgt später" required>
            <button type="submit" aria-label="Kommentar senden"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-paper-plane-tilt"></use></svg></button>
        </form>
        <div class="comment-feed" aria-live="polite"></div>
    </div>
    <script type="application/json" class="hnt-live-comments">@json($commentsForPreview)</script>
</article>
