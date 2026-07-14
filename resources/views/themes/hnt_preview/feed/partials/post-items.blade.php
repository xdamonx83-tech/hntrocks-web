@php
    $feedPostItems = method_exists($socialitePosts, 'getCollection')
        ? $socialitePosts->getCollection()
        : collect($socialitePosts);

    $feedPostIds = $feedPostItems->pluck('id')->filter()->all();

    $newsCardsByPostId = \App\Models\FeedNewsCard::query()
        ->whereIn('feed_post_id', $feedPostIds)
        ->get()
        ->keyBy('feed_post_id');

    $cupCardsByPostId = \App\Models\FeedCupCard::query()
        ->whereIn('feed_post_id', $feedPostIds)
        ->with(['cup.owner'])
        ->get()
        ->keyBy('feed_post_id');
@endphp

@forelse($socialitePosts as $post)
    @include('themes.hnt_preview.feed.partials.post-card', [
        'post' => $post,
        'newsCard' => $newsCardsByPostId->get($post->id, false),
        'cupCard' => $cupCardsByPostId->get($post->id, false),
    ])
@empty
    <article class="post hnt-preview-empty-card">
        <div class="post-header">
            <div class="post-author">
                <span class="avatar avatar-sm placeholder-avatar-1"></span>
                <div class="author-info">
                    <strong>HNT.rocks</strong>
                    <span>{{ __('ui.preview_nav_feed') }}</span>
                </div>
            </div>
        </div>
        <p class="post-text">{{ __('ui.preview_feed_no_entries') }}</p>
        <button class="btn-create" type="button" id="openComposerEmpty">{{ __('ui.preview_nav_create_post') }}</button>
    </article>
@endforelse

@if(method_exists($socialitePosts, 'hasMorePages') && $socialitePosts->hasMorePages())
    <div class="hnt-preview-load-more" data-hnt-feed-load-more>
        <a class="btn-create" href="{{ $socialitePosts->nextPageUrl() }}" data-hnt-feed-load-more-link>{{ __('ui.preview_feed_load_more') }}</a>
    </div>
@endif
