@php
    $viewer = auth()->user();
    $reworkAsset = fn (string $path): string => \App\Support\HntTheme::asset($path, 'rework');
    $formatCount = fn (int $count): string => number_format($count);
    $postAuthorUrl = function ($author): string {
        if (! $author?->username) {
            return '#';
        }

        return (int) $author->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $author);
    };
    $author = $post->user;
    $authorName = $author?->name ?: 'HNT Hunter';
    $authorAvatar = $author?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $authorMeta = trim(($author?->username ? '@'.$author->username.' - ' : '') . ($post->created_at?->diffForHumans() ?: 'now'));
    $visibilityLabel = method_exists($post, 'visibilityLabel') ? $post->visibilityLabel() : ucfirst((string) ($post->visibility ?: 'public'));
    $mediaItems = $post->relationLoaded('media') ? $post->media->values() : collect();
    $firstMedia = $mediaItems->first();
    $firstMediaUrl = $firstMedia ? $firstMedia->url() : null;
    $firstMediaType = $firstMedia?->isVideo() ? 'video' : ($firstMedia ? 'image' : null);
    $firstMediaAlt = $firstMedia?->original_name ?: 'Feed media by '.$authorName;
    $extraMediaCount = max($mediaItems->count() - 1, 0);
    $reactionCount = (int) ($post->reactions_count ?? ($post->relationLoaded('reactions') ? $post->reactions->count() : 0));
    $commentCount = (int) ($post->comments_count ?? ($post->relationLoaded('comments') ? $post->comments->count() : 0));
    $shareCount = (int) ($post->shares_count ?? 0);
    $viewerReaction = $post->relationLoaded('viewerReaction') ? $post->viewerReaction : null;
    $viewerReacted = $viewerReaction !== null;
    $reactionUrl = route('feed.reactions.toggle', $post);
    $reactionsUrl = route('feed.reactions.index', $post);
    $postAlreadyReported = ($reportedFeedKeys ?? collect())->has('feed_post:' . $post->id);
    $postUrl = route('feed.show', $post);
    $body = trim((string) $post->body);
    $bodyHtml = \App\Support\FeedTextRenderer::render($body);
    $reactionUsers = ($post->relationLoaded('reactions') ? $post->reactions : collect())
        ->filter(fn ($reaction) => $reaction->relationLoaded('user') && $reaction->user !== null)
        ->take(3)
        ->map(function ($reaction): array {
            $reactionUser = $reaction->user;

            return [
                'name' => $reactionUser?->name ?: 'HNT Hunter',
                'avatar' => $reactionUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
            ];
        })
        ->values()
        ->all();
    $commentsPreview = ($post->relationLoaded('comments') ? $post->comments : collect())
        ->take(6)
        ->map(function ($comment): array {
            $commentAuthor = $comment->user;

            return [
                'author' => $commentAuthor?->name ?: 'HNT Hunter',
                'avatar' => $commentAuthor?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                'time' => $comment->created_at?->diffForHumans() ?: 'now',
                'body_html' => \App\Support\FeedTextRenderer::render((string) $comment->body),
            ];
        })
        ->values()
        ->all();
    $postContext = [
        'id' => (int) $post->id,
        'author' => $authorName,
        'author_avatar' => $authorAvatar,
        'meta' => trim($authorMeta.' - '.($post->team?->name ?: $visibilityLabel)),
        'body_html' => $body !== '' ? $bodyHtml : '',
        'media_url' => $firstMediaUrl,
        'media_type' => $firstMediaType,
        'media_alt' => $firstMediaAlt,
        'likes' => $reactionCount,
        'likes_label' => $formatCount($reactionCount),
        'comments' => $commentCount,
        'comments_label' => $formatCount($commentCount),
        'shares' => $shareCount,
        'shares_label' => $formatCount($shareCount),
        'reacted' => $viewerReacted,
        'reaction_url' => $reactionUrl,
        'reactions_url' => $reactionsUrl,
        'comments_preview' => $commentsPreview,
    ];
@endphp

<article
    class="card post-card {{ $firstMediaUrl ? 'has-media' : 'has-no-media' }} {{ $body !== '' ? 'has-body' : 'has-no-body' }}"
    id="post-{{ $post->id }}"
    data-rework-post-card
>
<script type="application/json" data-rework-post-context>{!! json_encode($postContext, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
<header class="post-head">
<a href="{{ $postAuthorUrl($author) }}"><img alt="{{ $authorName }}" class="avatar" src="{{ $authorAvatar }}"/></a>
<div class="post-user"><strong>{{ $authorName }}</strong><span>{{ $authorMeta }} - {{ $post->team?->name ?: $visibilityLabel }}</span></div>
<a class="btn large" href="{{ $postUrl }}">Öffnen</a>
<div class="post-options action-menu">
<a aria-expanded="false" aria-label="Post-Optionen öffnen" class="more" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-dots-three ph-icon"></i></a>
<div aria-label="Post-Optionen" class="post-dropdown" role="menu">
<a href="{{ $postUrl }}" role="menuitem"><span><i aria-hidden="true" class="ph ph-arrow-square-out ph-icon"></i></span><strong>Post öffnen</strong></a>
<a href="{{ $postAuthorUrl($author) }}" role="menuitem"><span><i aria-hidden="true" class="ph ph-user ph-icon"></i></span><strong>Profil öffnen</strong></a>
<a href="#" role="menuitem"><span><i aria-hidden="true" class="ph ph-bookmark-simple ph-icon"></i></span><strong>Merken</strong></a>
@if(! $postAlreadyReported && (int) $post->user_id !== (int) auth()->id())
<a href="#" role="menuitem"><span><i aria-hidden="true" class="ph ph-flag ph-icon"></i></span><strong>Melden</strong></a>
@endif
</div>
</div>
</header>
@if($firstMedia && $firstMediaUrl)
<div class="post-media">
@if($firstMedia->isVideo())
<video controls playsinline preload="metadata" src="{{ $firstMediaUrl }}"></video>
@else
<button aria-label="Post mit Bild öffnen" class="post-media-trigger" data-comment-modal-open type="button">
<img alt="{{ $firstMediaAlt }}" src="{{ $firstMediaUrl }}"/>
</button>
@endif
<div class="game-pill"><img alt="" src="{{ $reworkAsset('images/bounty-mark.png') }}"/>{{ $extraMediaCount > 0 ? '+'.$extraMediaCount.' Medien' : 'Feed Media' }}</div>
</div>
@endif
<div class="post-actions">
<div class="icons">
<button
    aria-label="Like"
    aria-pressed="{{ $viewerReacted ? 'true' : 'false' }}"
    class="rework-icon-action {{ $viewerReacted ? 'is-active' : '' }}"
    data-rework-like-toggle
    data-reaction-url="{{ $reactionUrl }}"
    data-reaction-type="like"
    data-reaction-count="{{ $reactionCount }}"
    type="button"
><i aria-hidden="true" class="ph ph-heart ph-icon"></i></button>
<a aria-label="Kommentare öffnen" data-comment-modal-open href="#"><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i></a>
<a aria-label="Teilen" href="#"><i aria-hidden="true" class="ph ph-paper-plane-tilt ph-icon"></i></a>
<a aria-label="Merken" href="#"><i aria-hidden="true" class="ph ph-bookmark-simple ph-icon"></i></a>
</div>
@if($post->ai_user_declared || $post->ai_detected_possible || $post->admin_confirmed_ai)
<div class="ai-pill"><img alt="" src="{{ $reworkAsset('images/bounty-mark.png') }}"/>KI-Inhalt</div>
@endif
<div class="metrics">
<span class="metric"><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>{{ $formatCount($commentCount) }} Kommentare</span>
<span class="metric"><i aria-hidden="true" class="ph ph-share-network ph-icon"></i>{{ $formatCount($shareCount) }} Shares</span>
</div>
</div>
<button
    class="liked {{ $reactionCount > 0 ? '' : 'is-empty' }}"
    data-rework-reactions-open
    data-reactions-url="{{ $reactionsUrl }}"
    type="button"
>
<div class="liked-avatars">
@foreach($reactionUsers as $reactionUser)
<img alt="{{ $reactionUser['name'] }}" src="{{ $reactionUser['avatar'] }}"/>
@endforeach
</div>
<span data-rework-like-summary>{{ $reactionCount > 0 ? $formatCount($reactionCount).' Reaktionen' : 'Noch keine Reaktionen' }}</span>
</button>
@if($body !== '')
<div class="post-text rework-post-body is-collapsed" data-rework-post-body>
<div class="rework-post-body-content">{!! $bodyHtml !!}</div>
<button class="rework-read-more" data-rework-read-more data-more-label="Mehr lesen" data-less-label="Weniger lesen" hidden type="button">Mehr lesen</button>
</div>
@endif
<div class="comment-row">
<img alt="" src="{{ $viewer?->avatarUrl() ?: $reworkAsset('images/comment-avatar.png') }}"/>
<input class="comment-input" data-comment-modal-open placeholder="Post a comment.." readonly type="text"/>
<a class="btn" data-comment-modal-open href="#">Antworten</a>
</div>
</article>
