@php
    abort_unless(auth()->check() && \App\Support\HntTheme::previewActive(auth()->user()), 404);

    $viewer = auth()->user();
    $viewerId = (int) $viewer->id;
    $isEnglish = app()->getLocale() === 'en';

    $copy = $isEnglish ? [
        'for_you' => 'For you',
        'following' => 'Following',
        'moment' => 'Moment',
        'previous' => 'Previous moment',
        'next' => 'Next moment',
        'scroll' => 'Scroll or swipe',
        'pause' => 'Pause moment',
        'play' => 'Play moment',
        'mute' => 'Mute',
        'unmute' => 'Unmute',
        'like' => 'Like',
        'comments' => 'Comments',
        'share' => 'Share',
        'save' => 'Save',
        'saved' => 'Saved',
        'follow' => 'Add friend',
        'requested' => 'Request sent',
        'close' => 'Close',
        'comments_title' => 'Comments',
        'discussion' => 'Discussion',
        'relevant' => 'Relevant',
        'comment_placeholder' => 'Write a comment …',
        'reply' => 'Reply',
        'no_comments' => 'No comments yet. Start the conversation.',
        'no_following' => 'No moments from your friends are available right now.',
        'original_sound' => 'Original sound',
        'loading' => 'Saving …',
        'error' => 'The action could not be completed.',
        'copied' => 'Moment link copied.',
        'published' => 'Published',
    ] : [
        'for_you' => 'Für dich',
        'following' => 'Folge ich',
        'moment' => 'Moment',
        'previous' => 'Vorheriger Moment',
        'next' => 'Nächster Moment',
        'scroll' => 'Scrollen oder wischen',
        'pause' => 'Moment pausieren',
        'play' => 'Moment abspielen',
        'mute' => 'Ton ausschalten',
        'unmute' => 'Ton einschalten',
        'like' => 'Gefällt mir',
        'comments' => 'Kommentare',
        'share' => 'Teilen',
        'save' => 'Speichern',
        'saved' => 'Gespeichert',
        'follow' => 'Freund hinzufügen',
        'requested' => 'Anfrage gesendet',
        'close' => 'Schließen',
        'comments_title' => 'Kommentare',
        'discussion' => 'Diskussion',
        'relevant' => 'Relevant',
        'comment_placeholder' => 'Kommentar schreiben …',
        'reply' => 'Antworten',
        'no_comments' => 'Noch keine Kommentare. Starte die Unterhaltung.',
        'no_following' => 'Aktuell sind keine Moments deiner Freunde verfügbar.',
        'original_sound' => 'Originalton',
        'loading' => 'Wird gespeichert …',
        'error' => 'Die Aktion konnte nicht ausgeführt werden.',
        'copied' => 'Moment-Link kopiert.',
        'published' => 'Veröffentlicht',
    ];

    $friendships = \App\Models\Friendship::query()
        ->forUser($viewer)
        ->whereIn('status', [\App\Models\Friendship::STATUS_ACCEPTED, \App\Models\Friendship::STATUS_PENDING])
        ->get();

    $friendStatus = [];
    foreach ($friendships as $friendship) {
        $otherId = (int) $friendship->user_one_id === $viewerId
            ? (int) $friendship->user_two_id
            : (int) $friendship->user_one_id;
        $friendStatus[$otherId] = $friendship->status;
    }

    $viewerReaction = static function ($query) use ($viewerId): void {
        $query->where('user_id', $viewerId)->where('type', 'like');
    };

    $moments = \App\Models\Moment::query()
        ->with([
            'user.profile',
            'media',
            'cover',
            'reactions' => $viewerReaction,
            'bookmarks' => fn ($query) => $query->where('user_id', $viewerId),
            'comments' => function ($query) use ($viewerReaction): void {
                $query
                    ->whereNull('parent_id')
                    ->with([
                        'user.profile',
                        'reactions' => $viewerReaction,
                        'replies' => function ($replyQuery) use ($viewerReaction): void {
                            $replyQuery
                                ->with(['user.profile', 'reactions' => $viewerReaction])
                                ->oldest();
                        },
                    ])
                    ->latest();
            },
        ])
        ->withCount([
            'comments',
            'reactions as likes_count' => fn ($query) => $query->where('type', 'like'),
            'bookmarks',
        ])
        ->published()
        ->latest('published_at')
        ->latest('id')
        ->limit(10)
        ->get();

    if (isset($moment) && $moment && ! $moments->contains('id', $moment->id)) {
        $selectedMoment = \App\Models\Moment::query()
            ->with([
                'user.profile', 'media', 'cover',
                'reactions' => $viewerReaction,
                'bookmarks' => fn ($query) => $query->where('user_id', $viewerId),
                'comments' => function ($query) use ($viewerReaction): void {
                    $query->whereNull('parent_id')->with([
                        'user.profile',
                        'reactions' => $viewerReaction,
                        'replies' => fn ($replyQuery) => $replyQuery->with(['user.profile', 'reactions' => $viewerReaction])->oldest(),
                    ])->latest();
                },
            ])
            ->withCount([
                'comments',
                'reactions as likes_count' => fn ($query) => $query->where('type', 'like'),
                'bookmarks',
            ])
            ->find($moment->id);

        if ($selectedMoment) {
            $moments->prepend($selectedMoment);
        }
    } elseif (isset($moment) && $moment) {
        $moments = $moments->sortByDesc(fn ($item) => (int) $item->id === (int) $moment->id)->values();
    }

    $formatCount = static fn (int $value): string => number_format($value, 0, ',', '.');
    $formatDuration = static function (?int $seconds): string {
        $seconds = max(0, (int) $seconds);
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>HNT.ROCKS — Moments</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-moments/moments-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-live.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="moments">
@include('themes.hnt_preview.partials.icons')
<svg aria-hidden="true" class="svg-defs">
<symbol id="i-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></symbol>
<symbol id="i-pause" viewBox="0 0 24 24"><path d="M8 5v14M16 5v14"></path></symbol>
<symbol id="i-volume" viewBox="0 0 24 24"><path d="M5 10v4h4l5 4V6L9 10H5Z"></path><path d="M17 9a4 4 0 0 1 0 6M19 6a8 8 0 0 1 0 12"></path></symbol>
<symbol id="i-volume-off" viewBox="0 0 24 24"><path d="M5 10v4h4l5 4V6L9 10H5Z"></path><path d="m17 9 5 5M22 9l-5 5"></path></symbol>
</svg>

<main class="app-shell moments-shell">
@include('themes.hnt_preview.partials.header')

<section class="moments-stage">
<header class="moments-floating-head">
<div><span>HNT.ROCKS</span><strong>Moments</strong></div>
<nav aria-label="Moments Feed">
<button class="active" data-moments-feed="foryou" type="button">{{ $copy['for_you'] }}</button>
<button data-moments-feed="following" type="button">{{ $copy['following'] }}</button>
</nav>
<a class="moments-create-button" href="{{ route('moments.create') }}"><svg><use href="#i-plus"></use></svg>{{ $copy['moment'] }}</a>
</header>

<div aria-label="Moments Feed" class="moments-scroll" id="momentsScroll" tabindex="0">
@forelse($moments as $index => $item)
@php
    $author = $item->user;
    $authorName = $author?->name ?: ($author?->username ?: 'HNT Hunter');
    $handle = $author?->username ? '@'.$author->username : '@hunter';
    $avatar = $author?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $profileUrl = $author ? route('profile.public', $author) : '#';
    $title = trim((string) ($item->caption ?: ''));
    $description = trim((string) ($item->description ?: ''));
    if ($title === '') {
        $title = $description !== '' ? \Illuminate\Support\Str::limit($description, 95) : 'HNT Moment';
    }
    $poster = $item->coverUrl();
    $video = $item->mediaUrl();
    $likes = (int) $item->likes_count;
    $commentsCount = (int) $item->comments_count;
    $bookmarks = (int) $item->bookmarks_count;
    $liked = $item->reactions->isNotEmpty();
    $saved = $item->bookmarks->isNotEmpty();
    $authorId = (int) ($author?->id ?? 0);
    $friendshipState = $friendStatus[$authorId] ?? null;
    $isFollowing = $authorId === $viewerId || $friendshipState === \App\Models\Friendship::STATUS_ACCEPTED;
    $canRequestFriend = $authorId > 0 && $authorId !== $viewerId && $friendshipState === null;

    preg_match_all('/#[\pL\pN_\-]+/u', $title.' '.$description, $tagMatches);
    $tags = collect($tagMatches[0] ?? [])->unique()->take(4)->implode(' ');
    if ($tags === '') $tags = '#HNTMoment';

    $commentPayload = $item->comments->map(function ($comment) use ($item, $viewer, $viewerId) {
        $makePayload = function ($entry) use ($item, $viewer, $viewerId) {
            $user = $entry->user;
            return [
                'id' => (int) $entry->id,
                'parent_id' => $entry->parent_id ? (int) $entry->parent_id : null,
                'body' => (string) $entry->body,
                'likes_count' => (int) $entry->likes_count,
                'liked' => $entry->reactions->isNotEmpty(),
                'created_at' => $entry->created_at?->diffForHumans() ?: ($viewer->locale === 'en' ? 'just now' : 'gerade eben'),
                'author' => [
                    'name' => $user?->name ?: ($user?->username ?: 'HNT Hunter'),
                    'handle' => $user?->username ? '@'.$user->username : '@hunter',
                    'avatar' => $user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                ],
                'reaction_url' => route('moments.comments.reactions.toggle', $entry),
                'delete_url' => route('moments.comments.destroy', $entry),
                'can_delete' => (int) $entry->user_id === $viewerId || (int) $item->user_id === $viewerId || $viewer->isAdmin(),
            ];
        };

        $payload = $makePayload($comment);
        $payload['replies'] = $comment->replies->map($makePayload)->values()->all();
        return $payload;
    })->values()->all();
@endphp
<article
    class="moment-slide social-post {{ $index === 0 ? 'is-active' : '' }}"
    data-index="{{ $index }}"
    data-moment-id="{{ $item->id }}"
    data-following="{{ $isFollowing ? '1' : '0' }}"
    data-like-url="{{ route('moments.reactions.toggle', $item) }}"
    data-bookmark-url="{{ route('moments.bookmarks.toggle', $item) }}"
    data-comment-url="{{ route('moments.comments.store', $item) }}"
    data-share-url="{{ route('moments.show', $item) }}"
    data-profile-url="{{ $profileUrl }}"
    data-author-name="{{ $authorName }}"
    data-author-handle="{{ $handle }}"
    data-author-avatar="{{ $avatar }}"
    data-caption="{{ $title }}"
    data-description="{{ $description }}"
>
<script class="moment-comments-data" type="application/json">@json($commentPayload)</script>
<div class="moment-ambient" style="--moment-poster:url('{{ $poster }}')"></div>
<section class="moment-video-card">
@if($video)
<video class="moment-poster" autoplay muted loop playsinline preload="metadata" poster="{{ $poster }}"><source src="{{ $video }}" type="{{ $item->media?->mime_type ?: 'video/mp4' }}"></video>
@else
<div class="moment-poster moment-poster-fallback"><span>HNT.ROCKS</span></div>
@endif
<div class="moment-video-shade"></div>
<header class="moment-video-head">
<span class="moment-live-label">HNT MOMENT</span>
<div><span>{{ str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) }} / {{ str_pad((string)count($moments), 2, '0', STR_PAD_LEFT) }}</span><button aria-label="{{ $copy['mute'] }}" class="moment-sound-toggle" type="button"><svg><use href="#i-volume"></use></svg></button></div>
</header>
<button aria-label="{{ $copy['pause'] }}" class="moment-play-toggle" type="button"><svg><use href="#i-pause"></use></svg></button>
<div class="moment-progress"><i></i></div>
<div class="moment-caption">
<a class="moment-author-line" href="{{ $profileUrl }}"><img alt="{{ $authorName }}" src="{{ $avatar }}"><div><strong>{{ $authorName }}</strong><span>{{ $handle }} · {{ $item->published_at?->diffForHumans() }}</span></div></a>
<h2>{{ $title }}</h2>
@if($description !== '' && $description !== $title)<p>{{ \Illuminate\Support\Str::limit($description, 180) }}</p>@endif
<div class="moment-tags">{{ $tags }}</div>
<div class="moment-sound"><svg><use href="#i-volume"></use></svg><span>{{ $copy['original_sound'] }} · {{ $authorName }}</span></div>
</div>
<aside class="moment-action-rail">
<div class="moment-creator-action"><a href="{{ $profileUrl }}"><img alt="{{ $authorName }}" src="{{ $avatar }}"></a>
@if($canRequestFriend)
<button aria-label="{{ $authorName }} {{ $copy['follow'] }}" class="moment-follow-button" data-friend-url="{{ route('friends.store', $author) }}" type="button"><svg><use href="#i-plus"></use></svg></button>
@elseif($friendshipState === \App\Models\Friendship::STATUS_PENDING)
<button aria-label="{{ $copy['requested'] }}" class="moment-follow-button is-requested" disabled type="button"><svg><use href="#i-check"></use></svg></button>
@endif
</div>
<button aria-label="{{ $copy['like'] }}" class="moment-action moment-live-like {{ $liked ? 'liked' : '' }}" type="button"><span><svg><use href="#i-heart"></use></svg></span><b data-live-like-count>{{ $formatCount($likes) }}</b></button>
<button aria-label="{{ $copy['comments'] }}" class="moment-action moment-live-comments" type="button"><span><svg><use href="#i-comment"></use></svg></span><b data-live-comment-count>{{ $formatCount($commentsCount) }}</b></button>
<button aria-label="{{ $copy['share'] }}" class="moment-action moment-live-share" type="button"><span><svg><use href="#i-share"></use></svg></span><b>{{ $copy['share'] }}</b></button>
<button aria-label="{{ $saved ? $copy['saved'] : $copy['save'] }}" class="moment-action moment-live-save {{ $saved ? 'saved' : '' }}" type="button"><span><svg><use href="#i-bookmark"></use></svg></span><b data-live-save-label>{{ $saved ? $copy['saved'] : $copy['save'] }}</b><i data-live-bookmark-count hidden>{{ $bookmarks }}</i></button>
</aside>
<span class="moment-duration">{{ $formatDuration($item->duration_seconds) }}</span>
</section>
</article>
@empty
<section class="moments-empty-real"><strong>Moments</strong><p>{{ $copy['no_following'] }}</p><a href="{{ route('moments.create') }}">+ {{ $copy['moment'] }}</a></section>
@endforelse
</div>

<div class="moments-filter-empty" id="momentsFilterEmpty" hidden><strong>{{ $copy['following'] }}</strong><p>{{ $copy['no_following'] }}</p></div>
<aside class="moments-navigation"><button aria-label="{{ $copy['previous'] }}" id="previousMoment" type="button"><svg><use href="#i-chevron"></use></svg></button><span id="momentPosition">1 / {{ max(1, count($moments)) }}</span><button aria-label="{{ $copy['next'] }}" id="nextMoment" type="button"><svg><use href="#i-chevron"></use></svg></button></aside>
<div aria-hidden="true" class="moments-page-dots" id="momentDots">@foreach($moments as $index => $unused)<i class="{{ $index === 0 ? 'active' : '' }}"></i>@endforeach</div>
<div class="moments-swipe-hint"><svg><use href="#i-chevron"></use></svg><span>{{ $copy['scroll'] }}</span><svg><use href="#i-chevron"></use></svg></div>
</section>

<div aria-hidden="true" class="comments-modal-backdrop" id="momentsCommentsModal">
<section aria-modal="true" class="comments-modal" role="dialog">
<header class="comments-modal-head"><div><span>HNT.ROCKS</span><div class="comments-title-row"><h2>{{ $copy['comments_title'] }}</h2><small id="momentsCommentsCount">0</small></div></div><button aria-label="{{ $copy['close'] }}" class="comments-close" id="momentsCommentsClose" type="button"><svg><use href="#i-x"></use></svg></button></header>
<div class="comments-modal-content">
<article class="comments-post-context"><img alt="" id="momentsCommentsAvatar" src="{{ asset('assets/vikinger/img/default-avatar.svg') }}"><div class="comments-post-copy"><div><strong id="momentsCommentsAuthor">HNT Hunter</strong><span id="momentsCommentsMeta">@hunter</span></div><p id="momentsCommentsExcerpt"></p></div><span class="comments-post-badge">Moment</span></article>
<div class="comments-toolbar"><strong>{{ $copy['discussion'] }}</strong><button type="button">{{ $copy['relevant'] }} <svg><use href="#i-chevron"></use></svg></button></div>
<section class="comments-list" id="momentsCommentsList"></section>
</div>
<form class="comments-composer" id="momentsCommentsComposer"><img alt="{{ $viewer->name }}" src="{{ $viewer->avatarUrl() }}"><div class="comments-input-shell"><textarea id="momentsCommentsInput" maxlength="1000" placeholder="{{ $copy['comment_placeholder'] }}" rows="1"></textarea><input id="momentsCommentParent" type="hidden"><div class="comments-compose-actions"><div><button type="button"><svg><use href="#i-smile"></use></svg></button><button type="button"><svg><use href="#i-image"></use></svg></button></div><span id="momentsCommentsCounter">0/1000</span><button class="comments-send" type="submit"><svg><use href="#i-send"></use></svg></button></div></div></form>
</section>
</div>

<div class="toast" id="toast"></div>
<script>window.HNT_MOMENTS_COPY = @json($copy);</script>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-moments/moments-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-moments/moments-live.js')) ?: time() }}"></script>
</body>
</html>
