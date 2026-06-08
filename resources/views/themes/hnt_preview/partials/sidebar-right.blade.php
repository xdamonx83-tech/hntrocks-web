<aside class="sidebar-right" aria-label="{{ __('ui.preview_right_widgets_aria') }}">
    @php
        $rightSidebarMoments = collect();
        $popularSidebarHashtags = collect();
        $rightSidebarTopPost = null;
        $rightSidebarLatestLfg = null;
        $rightSidebarActiveCup = null;

        try {
            if (class_exists(\App\Models\Moment::class)) {
                $rightSidebarMoments = \App\Models\Moment::query()
                    ->with(['user', 'cover', 'media'])
                    ->published()
                    ->latest('published_at')
                    ->latest('created_at')
                    ->limit(8)
                    ->get();
            }
        } catch (\Throwable $exception) {
            $rightSidebarMoments = collect();
        }

        try {
            $hashtagTexts = collect();

            if (class_exists(\App\Models\FeedPost::class)) {
                $hashtagTexts = $hashtagTexts->merge(
                    \App\Models\FeedPost::query()
                        ->where('status', 'published')
                        ->where('visibility', '!=', 'private')
                        ->whereNotNull('body')
                        ->latest('created_at')
                        ->limit(180)
                        ->pluck('body')
                );
            }

            if (class_exists(\App\Models\Moment::class)) {
                \App\Models\Moment::query()
                    ->published()
                    ->latest('published_at')
                    ->latest('created_at')
                    ->limit(160)
                    ->get(['caption', 'description'])
                    ->each(function ($moment) use (&$hashtagTexts): void {
                        $hashtagTexts->push($moment->caption);
                        $hashtagTexts->push($moment->description);
                    });
            }

            if (class_exists(\App\Models\LfgPost::class)) {
                \App\Models\LfgPost::query()
                    ->whereIn('status', ['open', 'full'])
                    ->where('visibility', 'public')
                    ->latest('created_at')
                    ->limit(160)
                    ->get(['title', 'body'])
                    ->each(function ($lfgPost) use (&$hashtagTexts): void {
                        $hashtagTexts->push($lfgPost->title);
                        $hashtagTexts->push($lfgPost->body);
                    });
            }

            $popularSidebarHashtags = $hashtagTexts
                ->flatMap(fn ($text): array => \App\Support\Hashtag::extract($text))
                ->filter()
                ->countBy()
                ->sortDesc()
                ->take(12)
                ->map(fn (int $count, string $tag): array => [
                    'tag' => $tag,
                    'label' => '#'.$tag,
                    'count' => $count,
                    'url' => route('hashtags.show', $tag),
                ])
                ->values();
        } catch (\Throwable $exception) {
            $popularSidebarHashtags = collect();
        }


        try {
            if (class_exists(\App\Models\FeedPost::class)) {
                $rightSidebarTopPost = \App\Models\FeedPost::query()
                    ->with(['user', 'media.mediaAsset'])
                    ->withCount(['reactions', 'comments', 'bookmarks'])
                    ->where('status', 'published')
                    ->where('visibility', '!=', 'private')
                    ->latest('created_at')
                    ->limit(80)
                    ->get()
                    ->sortByDesc(function ($post): int {
                        return ((int) $post->reactions_count * 3)
                            + ((int) $post->comments_count * 2)
                            + ((int) $post->bookmarks_count)
                            + ($post->created_at?->gt(now()->subDays(7)) ? 1 : 0);
                    })
                    ->first();
            }
        } catch (\Throwable $exception) {
            $rightSidebarTopPost = null;
        }

        try {
            if (class_exists(\App\Models\LfgPost::class)) {
                $rightSidebarLatestLfg = \App\Models\LfgPost::query()
                    ->with('user')
                    ->whereIn('status', ['open', 'full'])
                    ->where('visibility', 'public')
                    ->latest('created_at')
                    ->first();
            }
        } catch (\Throwable $exception) {
            $rightSidebarLatestLfg = null;
        }

        try {
            if (class_exists(\App\Models\Cup::class)) {
                $rightSidebarActiveCup = \App\Models\Cup::query()
                    ->visible()
                    ->withCount(['activeTeams', 'submissions'])
                    ->whereIn('status', ['active', 'planned'])
                    ->orderByRaw("case when status = 'active' then 0 else 1 end")
                    ->orderByRaw('coalesce(starts_at, registration_opens_at, created_at) asc')
                    ->first();
            }
        } catch (\Throwable $exception) {
            $rightSidebarActiveCup = null;
        }
    @endphp

    <section class="widget">
        <div class="widget-header">
            <h2>Moments <span class="highlight-pink">live</span></h2>
            <div class="nav-arrows">
                <button class="arrow-btn" type="button" data-moments-prev aria-label="{{ __('ui.preview_right_moments_prev') }}"><i class="ph ph-caret-left" aria-hidden="true"></i></button>
                <button class="arrow-btn active" type="button" data-moments-next aria-label="{{ __('ui.preview_right_moments_next') }}"><i class="ph ph-caret-right" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="moments-list" id="momentsList">
            @forelse($rightSidebarMoments as $index => $moment)
                @php
                    $momentLabel = $moment->caption ?: $moment->description ?: ($moment->user?->name ?? $moment->user?->username ?? 'Moment');
                    $momentTitle = trim(strip_tags((string) $momentLabel)) ?: 'Moment';
                    $momentPreview = $moment->coverUrl();
                @endphp
                <a class="moment-item" href="{{ route('moments.show', $moment) }}" title="{{ $momentTitle }}" aria-label="{{ $momentTitle }}">
                    <span class="moment-ring gradient-{{ ($index % 4) + 1 }}">
                        <img class="moment-avatar" src="{{ $momentPreview }}" alt="" loading="lazy">
                    </span>
                    <span>{{ \Illuminate\Support\Str::limit($momentTitle, 10) }}</span>
                </a>
            @empty
                @foreach(['Bayou', 'Quick', 'Solo', 'Crown', 'Hunt', 'Clip'] as $index => $label)
                    <div class="moment-item">
                        <div class="moment-ring gradient-{{ ($index % 4) + 1 }}">
                            <div class="moment-avatar placeholder-avatar-{{ ($index % 8) + 2 }}"></div>
                        </div>
                        <span>{{ $label }}</span>
                    </div>
                @endforeach
            @endforelse
        </div>
    </section>

    <section class="widget">
        <div class="widget-header">
            <h2>{{ __('ui.preview_right_tags_title') }}</h2>
        </div>
        <div class="tags-container">
            @forelse($popularSidebarHashtags as $hashtag)
                <a class="tag" href="{{ $hashtag['url'] }}" title="{{ $hashtag['label'] }} · {{ $hashtag['count'] }}">{{ $hashtag['label'] }}</a>
            @empty
                @foreach(['QuickCup', 'BayouBlood', 'Loadout', 'ConsoleCup', 'HallOfFame'] as $fallbackTag)
                    <a class="tag" href="{{ route('hashtags.show', \App\Support\Hashtag::normalize($fallbackTag)) }}">#{{ $fallbackTag }}</a>
                @endforeach
            @endforelse
        </div>
    </section>

    <section class="widget">
        <div class="widget-header">
            <h2>{{ __('ui.preview_right_highlights_title') }}</h2>
        </div>

        @if($rightSidebarTopPost)
            @php
                $postAuthor = $rightSidebarTopPost->user?->name ?: $rightSidebarTopPost->user?->username ?: 'HNT.rocks';
                $postScore = (int) $rightSidebarTopPost->reactions_count + (int) $rightSidebarTopPost->comments_count + (int) $rightSidebarTopPost->bookmarks_count;
                $postMediaItems = $rightSidebarTopPost->relationLoaded('media') ? $rightSidebarTopPost->media : collect();
                $postPreviewMedia = $postMediaItems->first(fn ($media): bool => $media->isImage());
                $postBackgroundUrl = $postPreviewMedia?->url() ?: ($rightSidebarTopPost->gif_preview_url ?: $rightSidebarTopPost->gif_url);
            @endphp
            <a class="event-card event-bg-1 sidebar-highlight-card sidebar-highlight-link {{ $postBackgroundUrl ? 'has-bg' : '' }}" href="{{ route('feed.show', $rightSidebarTopPost) }}" @if($postBackgroundUrl) style="--sidebar-highlight-image: url('{{ e($postBackgroundUrl) }}');" @endif>
                <div class="sidebar-highlight-head">
                    <div class="event-brand">
                        <i class="ph ph-star logo-icon-inline" aria-hidden="true"></i>
                        {{ __('ui.preview_right_top_post_meta', ['author' => $postAuthor]) }}
                    </div>
                    <span class="event-badge badge-orange">{{ __('ui.preview_right_top_post_badge') }}</span>
                </div>
                <h3>{{ $rightSidebarTopPost->excerpt(82) ?: __('ui.preview_right_top_post_empty') }}</h3>
                <p class="event-time">{{ trans_choice('ui.preview_right_top_post_score', $postScore, ['count' => $postScore]) }}</p>
                <div class="event-footer sidebar-highlight-metrics">
                    <span>♡ {{ (int) $rightSidebarTopPost->reactions_count }}</span>
                    <span>☰ {{ (int) $rightSidebarTopPost->comments_count }}</span>
                    <span>⌑ {{ (int) $rightSidebarTopPost->bookmarks_count }}</span>
                </div>
            </a>
        @endif

        @if($rightSidebarLatestLfg)
            @php($lfgBackgroundUrl = $rightSidebarLatestLfg->coverUrl())
            <a class="event-card event-bg-2 sidebar-highlight-card sidebar-highlight-link {{ $lfgBackgroundUrl ? 'has-bg' : '' }}" href="{{ route('lfg.show', $rightSidebarLatestLfg) }}" @if($lfgBackgroundUrl) style="--sidebar-highlight-image: url('{{ e($lfgBackgroundUrl) }}');" @endif>
                <div class="sidebar-highlight-head">
                    <div class="event-brand">{{ $rightSidebarLatestLfg->user?->name ?: $rightSidebarLatestLfg->user?->username ?: 'HNT.rocks' }}</div>
                    <span class="event-badge badge-orange">{{ __('ui.preview_right_latest_lfg_badge') }}</span>
                </div>
                <h3>{{ \Illuminate\Support\Str::limit($rightSidebarLatestLfg->title, 72) }}</h3>
                <p class="event-time">{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $rightSidebarLatestLfg->body)), 92) ?: __('ui.preview_right_latest_lfg_empty') }}</p>
                <div class="event-footer sidebar-highlight-tags">
                    @foreach(array_slice($rightSidebarLatestLfg->displayTags(), 0, 3) as $tag)
                        <span>{{ $tag }}</span>
                    @endforeach
                    <strong>{{ $rightSidebarLatestLfg->statusLabel() }}</strong>
                </div>
            </a>
        @endif

        @if($rightSidebarActiveCup)
            @php($cupBackgroundUrl = $rightSidebarActiveCup->coverUrl())
            <a class="event-card event-bg-1 sidebar-highlight-card sidebar-highlight-link sidebar-highlight-cup {{ $cupBackgroundUrl ? 'has-bg' : '' }}" href="{{ route('cups.show', $rightSidebarActiveCup) }}" @if($cupBackgroundUrl) style="--sidebar-highlight-image: url('{{ e($cupBackgroundUrl) }}');" @endif>
                <div class="sidebar-highlight-head">
                    <div class="event-brand">{{ $rightSidebarActiveCup->statusLabel() }}</div>
                    <span class="event-badge badge-orange">{{ __('ui.preview_right_active_cup_badge') }}</span>
                </div>
                <h3>{{ \Illuminate\Support\Str::limit($rightSidebarActiveCup->title, 72) }}</h3>
                <p class="event-time">{{ \Illuminate\Support\Str::limit($rightSidebarActiveCup->displaySummary(), 92) }}</p>
                <div class="event-footer sidebar-highlight-tags">
                    <span>{{ $rightSidebarActiveCup->modeLabel() }}</span>
                    <span>{{ trans_choice('ui.preview_right_cup_participants', (int) $rightSidebarActiveCup->active_teams_count, ['count' => (int) $rightSidebarActiveCup->active_teams_count]) }}</span>
                </div>
            </a>
        @endif

        @unless($rightSidebarTopPost || $rightSidebarLatestLfg || $rightSidebarActiveCup)
            <article class="event-card event-bg-2 sidebar-highlight-card">
                <div class="event-brand">HNT.rocks</div>
                <h3>{{ __('ui.preview_right_no_highlights_title') }}</h3>
                <p class="event-time">{{ __('ui.preview_right_no_highlights_text') }}</p>
            </article>
        @endunless
    </section>
</aside>
