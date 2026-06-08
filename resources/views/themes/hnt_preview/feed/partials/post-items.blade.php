@forelse($socialitePosts as $post)
    @include('themes.hnt_preview.feed.partials.post-card', ['post' => $post])
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
