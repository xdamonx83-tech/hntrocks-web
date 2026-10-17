@php
    $author = $post->user;
    $authorName = $author?->name ?: 'Hunter';
    // Verify identity against the actual HNT.ROCKS account, never admin rights or display name.
    $isOfficialAuthor = (int) ($author?->id ?? 0) === 2 && (string) ($author?->username ?? '') === 'hntrocks';
    $platform = mb_strtolower(trim((string) ($author?->profile?->platform ?? '')));
    $authorSubtitle = \App\Http\Controllers\Next\NextText::get(
        $isOfficialAuthor ? 'community_story' : match ($platform) {
            'pc' => 'platform_pc', 'xbox' => 'platform_xbox',
            'playstation', 'ps', 'psn' => 'platform_playstation',
            'crossplay' => 'platform_crossplay', default => 'platform_unknown'
        }
    );
    $authorAvatar = $author?->avatarUrl() ?: '/build/next/hntv3-assets/avatar-hnt.webp';
    $liveFeeling = $post->feelingMeta();
    $localizedFeeling = $liveFeeling ? \App\Http\Controllers\Next\NextText::get($liveFeeling['key']) : null;
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
            'avatar' => $comment->user?->avatarUrl(),
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
    data-post-has-poll="{{ $post->poll ? '1' : '0' }}"
    data-post-format="{{ $firstVideo && !$firstImage ? 'clip' : ($isMedia ? 'image' : 'text') }}"
    data-permalink="{{ $postLink }}"
    data-post-owner="{{ (int) auth()->id() === (int) $post->user_id ? '1' : '0' }}"
    data-post-visibility="{{ $post->visibility }}"
    data-post-body="{{ $post->body ?? '' }}">
    <div class="post-head">
        <span class="post-avatar"><img src="{{ $authorAvatar }}" alt="{{ $authorName }}" loading="lazy"></span>
        <div class="post-author">
            <div class="author-line"><strong>{{ $authorName }}</strong>
                @if($liveFeeling)
                    <span class="hnt-author-feeling hnt-live-feeling" data-feeling-key="{{ $liveFeeling['key'] }}" title="{{ str_replace(':feeling', (string) $localizedFeeling, \App\Http\Controllers\Next\NextText::get('feeling_title')) }}">
                        <span class="hnt-feed-feeling-emoji" aria-hidden="true">{{ $liveFeeling['emoji'] }}</span>
                        <span>{{ $localizedFeeling }}</span>
                    </span>
                @endif
                @if($isOfficialAuthor)
                    <span class="official-pill"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-seal-check"></use></svg> {{ \App\Http\Controllers\Next\NextText::get('official') }}</span>
                @endif
                @if($post->is_pinned)
                    <span class="hnt-pinned-pill">{{ \App\Http\Controllers\Next\NextText::get('pinned') }}</span>
                @endif
            </div>
            <span>{{ $authorSubtitle }} <i class="meta-sep"></i> {{ $post->created_at?->diffForHumans() ?: 'Gerade eben' }}</span>
        </div>
        <button type="button" class="post-more-btn" data-more aria-label="{{ \App\Http\Controllers\Next\NextText::get('post_options') }}" aria-expanded="false">
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

    @if($post->poll)
        @php
            $livePoll = $post->poll;
            $livePoll->loadMissing(['options.votes','votes']);
            $liveVotesCount = $livePoll->votes->count();
            $myLiveVote = $livePoll->votes->firstWhere('user_id', auth()->id());
            $myLiveOption = $myLiveVote?->feed_post_poll_option_id;
            $isLivePollClosed = $livePoll->isClosed();
            $liveShowResults = $isLivePollClosed || $myLiveOption !== null;
        @endphp
        <section class="hnt-feed-poll hnt-live-poll" data-live-poll-post="{{ $post->id }}" data-poll-closed="{{ $isLivePollClosed ? '1' : '0' }}">
            <div class="hnt-feed-poll-top">
                <span class="hnt-feed-poll-label">COMMUNITY-UMFRAGE</span>
                <span class="hnt-feed-poll-duration">{{ $isLivePollClosed ? 'Abgeschlossen' : ($livePoll->closed_at ? 'Endet '.$livePoll->closed_at->diffForHumans() : 'Offene Umfrage') }}</span>
            </div>
            @if($livePoll->question)<h3 class="hnt-feed-poll-question">{{ $livePoll->question }}</h3>@endif
            <div class="hnt-feed-poll-options">
                @foreach($livePoll->options as $option)
                    @php
                        $optionVotes = $option->votes->count();
                        $optionPercent = $liveVotesCount > 0 ? (int) round($optionVotes / $liveVotesCount * 100) : 0;
                        $pickedOption = $myLiveOption !== null && (int)$myLiveOption === (int)$option->id;
                    @endphp
                    <button type="button" class="hnt-feed-poll-option {{ $liveShowResults ? 'show-results' : '' }} {{ $pickedOption ? 'is-selected' : '' }}"
                        data-live-poll-option="{{ $option->id }}" {{ $liveShowResults ? 'disabled' : '' }} aria-label="Abstimmen für {{ $option->body }}">
                        <span class="hnt-feed-poll-bar" style="width:{{ $liveShowResults ? $optionPercent : 0 }}%"></span>
                        <span class="hnt-feed-poll-option-line"><span class="hnt-feed-poll-name">{{ $option->body }}</span>
                            <span class="hnt-feed-poll-percent">{{ $liveShowResults ? $optionPercent.'%' : '' }}</span></span>
                    </button>
                @endforeach
            </div>
            <div class="hnt-feed-poll-foot">
                <span>{{ $liveVotesCount }} {{ $liveVotesCount === 1 ? 'Stimme' : 'Stimmen' }} · {{ $isLivePollClosed ? 'Umfrage beendet' : ($myLiveOption !== null ? 'Deine Stimme wurde gezählt' : 'Einmal abstimmen') }}</span>
                @if($liveShowResults)
                    <button type="button" class="hnt-poll-voters-trigger" data-poll-voters="{{ $post->id }}" aria-haspopup="dialog">Abstimmungsdetails <svg aria-hidden="true" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 5l7 7-7 7"/></svg></button>
                @endif
            </div>
        </section>
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
