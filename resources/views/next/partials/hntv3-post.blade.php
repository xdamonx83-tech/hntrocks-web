@php
    $author = $post->user;
    $authorName = $author?->name ?: 'Hunter';
    $authorAvatar = $author?->avatarUrl() ?: '/build/next/hntv3-assets/avatar-hnt.webp';
    $attachments = $post->media;
    $firstImage = $attachments->first(fn ($item) => $item->isImage());
    $firstVideo = $attachments->first(fn ($item) => $item->isVideo());
    $previewMedia = $firstImage ?: $firstVideo;
    $isMedia = $previewMedia !== null;
    $likeCount = (int) ($post->reactions_count ?? 0);
    $commentCount = (int) ($post->comments_count ?? 0);
    $minutesAgo = max(0, (int) ($post->created_at?->diffInMinutes(now()) ?? 0));
    $postLink = $post->permalink();
    $commentsForPreview = $post->comments
        ->take(100)
        ->map(fn ($comment) => [
            'id' => (string) $comment->id,
            'name' => (string) ($comment->user?->name ?: 'Hunter'),
            'initials' => mb_strtoupper(mb_substr((string) ($comment->user?->name ?: 'H'), 0, 2)),
            'time' => $comment->created_at?->diffForHumans() ?: '',
            'message' => (string) ($comment->body ?: ''),
            'parentId' => $comment->parent_id ? (string) $comment->parent_id : null,
        ])->values()->all();
@endphp
<article class="feed-post {{ $isMedia ? 'media-post' : 'text-post' }}"
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
    @if($post->sharedPost)
        <div class="hnt-live-shared">↗ Geteilt von {{ $post->sharedPost->user?->name ?? 'Hunter' }}</div>
    @endif
    @if($post->body)
        <p class="post-caption">{{ $post->body }}</p>
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
                <video src="{{ $firstVideo->url() }}" controls playsinline preload="metadata"></video>
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
            <button type="button" class="post-action like-btn" data-like aria-pressed="{{ $post->viewerReaction ? 'true' : 'false' }}" aria-label="Beitrag gefällt mir">
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
        <button type="button" class="post-action post-save" data-save aria-pressed="{{ $post->viewerBookmark ? 'true' : 'false' }}">
            <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-bookmark-simple"></use></svg><span>Merken</span>
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
