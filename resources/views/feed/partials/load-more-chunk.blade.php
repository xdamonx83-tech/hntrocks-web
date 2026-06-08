<div data-hh-feed-chunk>
    @foreach ($posts as $post)
        @include('feed.partials.post-card', ['post' => $post])
    @endforeach
</div>

@if (($hasMoreFeedPosts ?? false) || (($feedPage ?? 1) > 1))
    <div class="section-pager-bar hh-vk-pager hh-feed-load-more-bar" data-hh-feed-pager>
        <p class="hh-feed-load-more-info">
            {{ __('ui.feed_posts_shown', ['shown' => $postsDisplayedCount ?? $posts->count(), 'total' => $posts->total()]) }}
        </p>

        <div class="hh-feed-load-more-actions">
            @if ($hasMoreFeedPosts ?? false)
                <a class="button small secondary hh-feed-load-more-button" href="{{ $nextFeedPageUrl }}" data-hh-feed-load-more>{{ __('ui.load_more') }}</a>
            @endif

            @if (($feedPage ?? 1) > 1)
                <a class="button small white hh-feed-load-more-reset" href="{{ $resetFeedPageUrl }}">{{ __('ui.reduce_again') }}</a>
            @endif
        </div>
    </div>
@endif
