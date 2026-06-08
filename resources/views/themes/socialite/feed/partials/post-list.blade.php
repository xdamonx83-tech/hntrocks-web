<div data-socialite-post-list>
    <div class="space-y-4 md:space-y-5" data-socialite-post-stream>
        @include('themes.socialite.feed.partials.post-items', ['socialitePosts' => $socialitePosts])
    </div>

    @if(method_exists($socialitePosts, 'hasMorePages') && $socialitePosts->hasMorePages())
        <div class="pt-6 text-center" data-socialite-load-more-wrap>
            <button
                type="button"
                class="hnt-feed-load-more-button inline-flex items-center justify-center gap-3 rounded-full px-7 py-3 text-sm font-bold transition disabled:cursor-not-allowed disabled:opacity-60"
                data-socialite-load-more
                data-next-url="{{ $socialitePosts->nextPageUrl() }}"
                data-loading-label="{{ __('ui.loading') }}"
                data-try-again-label="{{ __('ui.try_again') }}"
            >
                <span class="hnt-feed-load-more-icon" aria-hidden="true">
                    <ion-icon name="add-outline"></ion-icon>
                </span>
                <span data-socialite-load-more-label>{{ __('ui.feed_load_more_posts') }}</span>
            </button>
        </div>
    @endif
</div>
