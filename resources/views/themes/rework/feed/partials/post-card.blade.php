@php
    $viewer = auth()->user();
    $reportedFeedKeys = $reportedFeedKeys ?? collect();
    $isOwnPost = $viewer && (int) $post->user_id === (int) $viewer->id;
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
    $mediaPayload = $mediaItems
        ->map(function ($media) use ($authorName): array {
            return [
                'id' => (int) $media->id,
                'url' => $media->url(),
                'type' => $media->isVideo() ? 'video' : 'image',
                'alt' => $media->original_name ?: 'Feed media by '.$authorName,
            ];
        })
        ->filter(fn (array $item): bool => ! empty($item['url']))
        ->values()
        ->all();
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
    $feelingMeta = method_exists($post, 'feelingMeta') ? $post->feelingMeta() : null;
    $poll = $post->relationLoaded('poll') ? $post->poll : null;
    $pollOptions = $poll && $poll->relationLoaded('options') ? $poll->options->values() : collect();
    $pollVotes = $poll && $poll->relationLoaded('votes') ? $poll->votes : collect();
    $pollTotalVotes = $pollVotes->count();
    $viewerPollVote = $viewer && $poll ? $pollVotes->firstWhere('user_id', $viewer->id) : null;
    $viewerPollOptionId = $viewerPollVote?->feed_post_poll_option_id;
    $plainBody = trim(preg_replace('/\s+/u', ' ', strip_tags($body)));
    $readMoreLimit = $firstMediaUrl ? 260 : 520;
    $shouldReadMore = mb_strlen($plainBody) > $readMoreLimit;
    $reactionUsers = ($post->relationLoaded('reactions') ? $post->reactions : collect())
        ->take(3)
        ->filter(fn ($reaction) => $reaction->user !== null)
        ->map(function ($reaction): array {
            $reactionUser = $reaction->user;

            return [
                'name' => $reactionUser?->name ?: 'HNT Hunter',
                'avatar' => $reactionUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
            ];
        })
        ->values()
        ->all();
    $firstReactionName = $reactionUsers[0]['name'] ?? null;
    $remainingReactionCount = max($reactionCount - 1, 0);
    $reactionSummary = $reactionCount <= 0
        ? __('ui.rework_no_reactions')
        : ($firstReactionName
            ? __('ui.rework_liked_by', ['name' => $firstReactionName, 'others' => $remainingReactionCount > 0 ? __('ui.rework_liked_by_others', ['count' => $formatCount($remainingReactionCount)]) : ''])
            : __('ui.rework_reaction_count', ['count' => $formatCount($reactionCount)]));
    $allComments = ($post->relationLoaded('comments') ? $post->comments : collect())->sortBy('created_at')->values();
    $commentsById = $allComments->keyBy(fn ($item) => (int) $item->id);
    $rootIdFor = function ($comment) use ($commentsById): int {
        $parentId = (int) ($comment->parent_id ?? 0);
        $guard = 0;

        while ($parentId > 0 && $commentsById->has($parentId) && $guard < 10) {
            $parent = $commentsById->get($parentId);
            $nextParentId = (int) ($parent->parent_id ?? 0);

            if ($nextParentId < 1) {
                return (int) $parent->id;
            }

            $parentId = $nextParentId;
            $guard++;
        }

        return $parentId > 0 ? $parentId : (int) $comment->id;
    };
    $commentPayload = function ($comment) use ($post, $viewer, $reportedFeedKeys, $rootIdFor): array {
            $commentAuthor = $comment->user;
            $viewerId = (int) ($viewer?->id ?? 0);
            $viewerIsAdmin = $viewer && method_exists($viewer, 'isAdmin') && $viewer->isAdmin();
            $isOwnComment = $viewerId > 0 && (int) $comment->user_id === $viewerId;
            $canDeleteComment = $isOwnComment || $viewerIsAdmin || ($viewerId > 0 && (int) $post->user_id === $viewerId);
            $commentAlreadyReported = ($reportedFeedKeys ?? collect())->has('feed_comment:' . $comment->id);
            $commentAuthorName = $commentAuthor?->name ?: ($commentAuthor?->username ?: 'HNT Hunter');
            $commentAuthorUrl = $commentAuthor
                ? (((int) $commentAuthor->id === $viewerId) ? route('profile.show') : route('profile.public', $commentAuthor))
                : '#';

            return [
                'id' => (int) $comment->id,
                'post_id' => (int) $post->id,
                'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
                'root_id' => $rootIdFor($comment),
                'is_reply' => ! empty($comment->parent_id),
                'author' => $commentAuthorName,
                'avatar' => $commentAuthor?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                'profile_url' => $commentAuthorUrl,
                'time' => $comment->created_at?->diffForHumans() ?: 'now',
                'body' => (string) $comment->body,
                'body_html' => \App\Support\FeedTextRenderer::render((string) $comment->body),
                'media' => ($comment->relationLoaded('media') ? $comment->media : collect())
                    ->map(fn ($media): array => [
                        'url' => $media->url(),
                        'type' => $media->isImage() ? 'image' : ($media->isVideo() ? 'video' : 'file'),
                        'alt' => $media->original_name ?: __('ui.preview_comment_image_preview'),
                    ])
                    ->filter(fn (array $item): bool => ! empty($item['url']))
                    ->values()
                    ->all(),
                'reaction_count' => $comment->relationLoaded('reactions') ? $comment->reactions->count() : 0,
                'viewer_reacted' => (bool) ($comment->relationLoaded('viewerReaction') ? $comment->viewerReaction : null),
                'can_edit' => $isOwnComment,
                'can_delete' => $canDeleteComment,
                'can_report' => ! $isOwnComment && ! $commentAlreadyReported,
                'reported' => $commentAlreadyReported,
                'routes' => [
                    'store' => route('feed.comments.store', $post),
                    'reaction' => route('feed.comments.reactions.toggle', $comment),
                    'update' => $isOwnComment ? route('feed.comments.update', $comment) : null,
                    'delete' => $canDeleteComment ? route('feed.comments.destroy', $comment) : null,
                    'reactions' => route('feed.comments.reactions.index', $comment),
                ],
            ];
    };
    $rootComments = $allComments
        ->filter(fn ($item) => empty($item->parent_id) || ! $commentsById->has((int) $item->parent_id))
        ->values();
    $repliesByRoot = $allComments
        ->filter(fn ($item) => ! empty($item->parent_id) && $commentsById->has((int) $item->parent_id))
        ->groupBy(fn ($item) => $rootIdFor($item));
    $commentThreads = $rootComments
        ->map(fn ($root): array => [
            'root' => $commentPayload($root),
            'replies' => ($repliesByRoot->get((int) $root->id, collect()))->values()->map($commentPayload)->all(),
        ])
        ->values()
        ->all();
    $postContext = [
        'id' => (int) $post->id,
        'author' => $authorName,
        'author_avatar' => $authorAvatar,
        'author_url' => $postAuthorUrl($author),
        'post_url' => $postUrl,
        'meta' => trim($authorMeta.' - '.($post->team?->name ?: $visibilityLabel)),
        'body_html' => $body !== '' ? $bodyHtml : '',
        'feeling' => $feelingMeta,
        'media_url' => $firstMediaUrl,
        'media_type' => $firstMediaType,
        'media_alt' => $firstMediaAlt,
        'media_items' => $mediaPayload,
        'likes' => $reactionCount,
        'likes_label' => $formatCount($reactionCount),
        'comments' => $commentCount,
        'comments_label' => $formatCount($commentCount),
        'shares' => $shareCount,
        'shares_label' => $formatCount($shareCount),
        'reacted' => $viewerReacted,
        'reaction_url' => $reactionUrl,
        'reactions_url' => $reactionsUrl,
        'comment_store_url' => route('feed.comments.store', $post),
        'can_report' => ! $postAlreadyReported && (int) $post->user_id !== (int) auth()->id(),
        'reported' => $postAlreadyReported,
        'report' => [
            'type' => 'feed_post',
            'id' => (int) $post->id,
            'label' => __('ui.preview_post_report_label', ['name' => $authorName]),
        ],
        'comments_preview' => $commentThreads,
        'viewer' => [
            'name' => $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter'),
            'avatar' => $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
        ],
        'labels' => [
            'comments' => __('ui.comments'),
            'reactions' => __('ui.rework_reactions'),
            'no_reactions' => __('ui.rework_no_reactions'),
            'shares' => __('ui.rework_shares'),
            'replies' => __('ui.preview_comment_reply'),
            'reply' => __('ui.preview_comment_reply'),
            'reply_to' => __('ui.js_i18n_reply_to', ['name' => '__name__']),
            'write_comment' => __('ui.rework_comment_placeholder'),
            'send' => __('ui.send'),
            'sending' => __('ui.js_i18n_sending'),
            'save' => __('ui.preview_action_save'),
            'cancel' => __('ui.preview_action_cancel'),
            'edit' => __('ui.preview_comment_edit'),
            'delete' => __('ui.preview_comment_delete'),
            'report' => __('ui.preview_comment_report'),
            'post_report' => __('ui.preview_post_report'),
            'reported' => __('ui.preview_comment_reported_short'),
            'comment_report_label' => __('ui.preview_comment_report_label', ['name' => '__name__']),
            'like' => __('ui.preview_comment_like'),
            'no_comments' => __('ui.no_comments_yet'),
            'media' => __('ui.rework_media'),
            'previous_media' => __('ui.rework_previous_media'),
            'next_media' => __('ui.rework_next_media'),
            'add_media' => __('ui.preview_comment_add_image'),
            'remove_media' => __('ui.rework_remove_media'),
            'delete_confirm' => __('ui.preview_comment_delete_confirm'),
            'send_failed' => __('ui.preview_comment_send_failed'),
            'save_failed' => __('ui.preview_comment_save_failed'),
            'delete_failed' => __('ui.preview_comment_delete_failed'),
            'reaction_failed' => __('ui.rework_reaction_failed'),
            'action_failed' => __('ui.rework_action_failed'),
            'report_success' => __('ui.report_success'),
            'report_failed' => __('ui.report_could_not_be_sent'),
        ],
        'poll' => $poll ? [
            'question' => $poll->question,
            'vote_url' => route('feed.poll.vote', $post),
            'total_votes' => $pollTotalVotes,
            'options' => $pollOptions->map(function ($option) use ($pollTotalVotes, $viewerPollOptionId): array {
                $votes = $option->relationLoaded('votes') ? $option->votes->count() : 0;

                return [
                    'id' => (int) $option->id,
                    'body' => (string) $option->body,
                    'votes' => $votes,
                    'percent' => $pollTotalVotes > 0 ? (int) round(($votes / max(1, $pollTotalVotes)) * 100) : 0,
                    'selected' => $viewerPollOptionId && (int) $viewerPollOptionId === (int) $option->id,
                ];
            })->values()->all(),
        ] : null,
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
<div class="post-user"><a href="{{ $postAuthorUrl($author) }}"><strong>{{ $authorName }}</strong></a><span>{{ $authorMeta }} - {{ $post->team?->name ?: $visibilityLabel }}</span></div>
<a class="btn large" href="{{ $postUrl }}">{{ __('ui.rework_open') }}</a>
<div class="post-options action-menu">
<a aria-expanded="false" aria-label="{{ __('ui.rework_post_options_open') }}" class="more" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-dots-three ph-icon"></i></a>
<div aria-label="{{ __('ui.rework_post_options') }}" class="post-dropdown" role="menu">
<a href="{{ $postUrl }}" role="menuitem"><span><i aria-hidden="true" class="ph ph-arrow-square-out ph-icon"></i></span><strong>{{ __('ui.rework_post_open') }}</strong></a>
<a href="{{ $postAuthorUrl($author) }}" role="menuitem"><span><i aria-hidden="true" class="ph ph-user ph-icon"></i></span><strong>{{ __('ui.rework_profile_open') }}</strong></a>
@if($isOwnPost)
<a
    href="#"
    role="menuitem"
    data-rework-post-edit-open
    data-update-url="{{ route('feed.update', $post) }}"
    data-post-body="{{ e($body) }}"
    data-post-visibility="{{ $post->isTeamPost() ? 'team' : $post->visibility }}"
    data-post-background-style="{{ $post->background_style ?: 'none' }}"
    data-post-feeling-key="{{ $post->feeling_key ?: 'none' }}"
><span><i aria-hidden="true" class="ph ph-pencil-simple ph-icon"></i></span><strong>{{ __('ui.preview_action_edit') }}</strong></a>
<a href="#" role="menuitem" data-rework-post-delete-trigger data-delete-form="rework-post-delete-{{ $post->id }}"><span><i aria-hidden="true" class="ph ph-trash ph-icon"></i></span><strong>{{ __('ui.preview_comment_delete') }}</strong></a>
<form id="rework-post-delete-{{ $post->id }}" action="{{ route('feed.destroy', $post) }}" method="post" hidden>
@csrf
@method('DELETE')
</form>
@endif
<a href="#" role="menuitem"><span><i aria-hidden="true" class="ph ph-bookmark-simple ph-icon"></i></span><strong>{{ __('ui.rework_save_post') }}</strong></a>
@if(! $postAlreadyReported && (int) $post->user_id !== (int) auth()->id())
<a
    href="#"
    role="menuitem"
    data-rework-report-open
    data-report-type="feed_post"
    data-report-id="{{ $post->id }}"
    data-report-label="{{ __('ui.preview_post_report_label', ['name' => $authorName]) }}"
><span><i aria-hidden="true" class="ph ph-flag ph-icon"></i></span><strong>{{ __('ui.preview_post_report') }}</strong></a>
@endif
</div>
</div>
</header>
@if($feelingMeta || ($poll && $pollOptions->isNotEmpty()))
<div class="rework-post-extras">
@if($feelingMeta)
<div class="rework-post-feeling">
<span>{{ $feelingMeta['emoji'] ?? '✨' }}</span>
<strong>{{ $authorName }}</strong>
<em>{{ __('ui.rework_feels_with_label', ['label' => $feelingMeta['label'] ?? __('ui.rework_feeling_ready')]) }}</em>
</div>
@endif
@if($poll && $pollOptions->isNotEmpty())
<div class="rework-post-poll">
@if(filled($poll->question))
<strong class="rework-post-poll-question">{{ $poll->question }}</strong>
@endif
<div class="rework-post-poll-options">
@foreach($pollOptions as $option)
@php
    $optionVotes = $option->relationLoaded('votes') ? $option->votes->count() : 0;
    $optionPercent = $pollTotalVotes > 0 ? (int) round(($optionVotes / max(1, $pollTotalVotes)) * 100) : 0;
    $optionSelected = $viewerPollOptionId && (int) $viewerPollOptionId === (int) $option->id;
@endphp
<form action="{{ route('feed.poll.vote', $post) }}" class="rework-post-poll-option-form" method="post">
@csrf
<input name="poll_option_id" type="hidden" value="{{ $option->id }}">
<button class="rework-post-poll-option {{ $optionSelected ? 'is-selected' : '' }}" type="submit">
<span class="rework-post-poll-option-bar" style="width: {{ $optionPercent }}%"></span>
<span class="rework-post-poll-option-head">
<span>{{ $option->body }}</span>
<em>{{ $optionPercent }}%</em>
</span>
</button>
</form>
@endforeach
</div>
<span class="rework-post-poll-total">{{ __('ui.rework_poll_votes', ['count' => number_format((int) $pollTotalVotes)]) }}</span>
</div>
@endif
</div>
@endif
@if($firstMedia && $firstMediaUrl)
<div class="post-media">
@if($firstMedia->isVideo())
<video controls playsinline preload="metadata" src="{{ $firstMediaUrl }}"></video>
@else
<button aria-label="{{ __('ui.rework_post_image_open') }}" class="post-media-trigger" data-comment-modal-open type="button">
<img alt="{{ $firstMediaAlt }}" src="{{ $firstMediaUrl }}"/>
</button>
@endif
<div class="game-pill">{{ $extraMediaCount > 0 ? '+'.$extraMediaCount.' Medien' : 'Feed Media' }}</div>
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
<a aria-label="{{ __('ui.rework_comments_open') }}" data-comment-modal-open href="#"><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i></a>
<a aria-label="{{ __('ui.rework_share') }}" href="#"><i aria-hidden="true" class="ph ph-paper-plane-tilt ph-icon"></i></a>
<a aria-label="{{ __('ui.rework_save_post') }}" href="#"><i aria-hidden="true" class="ph ph-bookmark-simple ph-icon"></i></a>
</div>
@if($post->ai_user_declared || $post->ai_detected_possible || $post->admin_confirmed_ai)
<div class="ai-pill"><img alt="" src="{{ $reworkAsset('images/bounty-mark.png') }}"/>KI-Inhalt</div>
@endif
<div class="metrics">
<span class="metric"><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>{{ __('ui.rework_comment_count', ['count' => $formatCount($commentCount)]) }}</span>
<span class="metric"><i aria-hidden="true" class="ph ph-share-network ph-icon"></i>{{ $formatCount($shareCount) }} Shares</span>
</div>
</div>
<button
    class="liked {{ $reactionCount > 0 ? '' : 'is-empty' }}"
    data-rework-reactions-open
    data-reactions-url="{{ $reactionsUrl }}"
    data-viewer-name="{{ $viewer?->name ?: 'Du' }}"
    data-viewer-avatar="{{ $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"
    type="button"
>
<div class="liked-avatars {{ count($reactionUsers) === 1 ? 'is-single' : '' }}">
@foreach($reactionUsers as $reactionUser)
<img alt="{{ $reactionUser['name'] }}" src="{{ $reactionUser['avatar'] }}"/>
@endforeach
</div>
<span data-rework-like-summary>{{ $reactionSummary }}</span>
</button>
@if($body !== '')
<div class="post-text rework-post-body {{ $shouldReadMore ? 'is-collapsed can-expand' : 'is-static' }}" data-rework-post-body>
<div class="rework-post-body-content">{!! $bodyHtml !!}</div>
@if($shouldReadMore)
<button class="rework-read-more" data-rework-read-more data-more-label="{{ __('ui.rework_read_more') }}" data-less-label="{{ __('ui.rework_read_less') }}" type="button">{{ __('ui.rework_read_more') }}</button>
@endif
</div>
@endif
<div class="comment-row">
<img alt="" src="{{ $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<input class="comment-input" data-comment-modal-open placeholder="{{ __('ui.rework_comment_placeholder') }}" readonly type="text"/>
<a class="btn comment-send-icon" data-comment-modal-open href="#" aria-label="{{ __('ui.preview_comment_reply') }}"><i aria-hidden="true" class="ph ph-paper-plane-tilt ph-icon"></i><span>{{ __('ui.preview_comment_reply') }}</span></a>
</div>
</article>
