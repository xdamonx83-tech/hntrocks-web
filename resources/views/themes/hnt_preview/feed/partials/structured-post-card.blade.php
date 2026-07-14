@php
    /** @var \App\Models\FeedPost $post */
    $newsCard = $newsCard ?? \App\Models\FeedNewsCard::query()
        ->where('feed_post_id', $post->id)
        ->first();

    $cupCard = $cupCard ?? (
        \Illuminate\Support\Facades\Schema::hasTable('feed_cup_cards')
            ? \App\Models\FeedCupCard::query()
                ->where('feed_post_id', $post->id)
                ->with(['cup.owner'])
                ->first()
            : null
    );

    $postMarkup = view('themes.hnt_preview.feed.partials.post-card', array_filter([
        'post' => $post,
        'newsCard' => $newsCard,
        'reportedFeedKeys' => $reportedFeedKeys ?? null,
    ], static fn ($value): bool => $value !== null))->render();

    if ($cupCard && $cupCard->cup) {
        $cupCardMarkup = view('themes.hnt_preview.feed.partials.cup-card', [
            'cupCard' => $cupCard,
            'post' => $post,
        ])->render();

        $postMarkup = \Illuminate\Support\Str::replaceFirst(
            'class="post hnt-preview-feed-post',
            'class="post hnt-preview-feed-post is-cup-crosspost',
            $postMarkup
        );
        $postMarkup = \Illuminate\Support\Str::replaceFirst(
            '<div class="post-header-actions hnt-post-header-actions">',
            '<div class="post-header-actions hnt-post-header-actions"><span class="hnt-cup-crosspost-badge">'.e(__('hnt_cup_crosspost.cup')).'</span>',
            $postMarkup
        );
        $postMarkup = \Illuminate\Support\Str::replaceFirst(
            '<div class="post-tags">',
            $cupCardMarkup.'<div class="post-tags">',
            $postMarkup
        );
    }
@endphp

{!! $postMarkup !!}
