@forelse($socialitePosts as $post)
    @include('themes.socialite.feed.partials.post-card', ['post' => $post])
@empty
    <div class="bg-white rounded-xl shadow-sm text-sm font-medium border1 dark:bg-dark2 p-6 text-center">
        <ion-icon name="newspaper-outline" class="text-4xl text-gray-400"></ion-icon>
        <h3 class="mt-3 font-bold text-black dark:text-white">{{ __('ui.no_posts_found') }}</h3>
        <p class="mt-1 text-gray-500 dark:text-white/70">This preview will show real public feed posts once they exist.</p>
    </div>
@endforelse
