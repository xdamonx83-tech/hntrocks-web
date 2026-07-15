@php
    $profilePostsLive = collect($profilePosts ?? []);
    $profilePostIds = $profilePostsLive->pluck('id')->filter()->all();

    $profileNewsCardsByPostId = $profilePostIds === []
        ? collect()
        : \App\Models\FeedNewsCard::query()
            ->whereIn('feed_post_id', $profilePostIds)
            ->get()
            ->keyBy('feed_post_id');

    $profileCupCardsByPostId = $profilePostIds !== [] && \Illuminate\Support\Facades\Schema::hasTable('feed_cup_cards')
        ? \App\Models\FeedCupCard::query()
            ->whereIn('feed_post_id', $profilePostIds)
            ->with(['cup.owner'])
            ->get()
            ->keyBy('feed_post_id')
        : collect();
@endphp
<section class="profile-tab-panel active" data-profile-panel="posts" id="profileTabPosts" role="tabpanel">
<div class="post-list profile-post-list">
@forelse($profilePostsLive as $post)
    @include('themes.hnt_preview.feed.partials.structured-post-card', [
        'post' => $post,
        'newsCard' => $profileNewsCardsByPostId->get($post->id, false),
        'cupCard' => $profileCupCardsByPostId->get($post->id, false),
    ])
@empty
<div class="profile-empty-state profile-post-empty">
<strong>{{ __('hnt_preview.profile.no_posts') }}</strong>
<p>{{ __('hnt_preview.profile.published_posts_here') }}</p>
@if($isOwnProfile)
<button id="openEmptyPostComposer" type="button"><svg><use href="#i-plus"></use></svg> {{ __('hnt_preview.profile.first_post') }}</button>
@endif
</div>
@endforelse
@if(($profilePostsTotal ?? $profilePostsLive->count()) > $profilePostsLive->count())
<a class="profile-post-more-link" href="{{ request()->fullUrlWithQuery(['profile_posts_page' => (int) ($profilePostPage ?? 1) + 1]) }}">{{ __('hnt_preview.profile.load_more') }} <svg><use href="#i-arrow"></use></svg></a>
@endif
</div>
</section>
