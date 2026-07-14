@php
    /** @var \App\Models\FeedNewsCard $newsCard */
    $newsHighlights = collect($newsCard->highlights ?? [])
        ->map(fn ($item): string => trim((string) $item))
        ->filter()
        ->take(5)
        ->values();
    $newsPublishedAt = $post->created_at;
    $newsPublishedLabel = app()->getLocale() === 'de'
        ? ($newsPublishedAt?->isToday() ? 'Heute veröffentlicht' : 'Veröffentlicht am '.optional($newsPublishedAt)->format('d.m.Y'))
        : ($newsPublishedAt?->isToday() ? 'Published today' : 'Published '.optional($newsPublishedAt)->format('M j, Y'));
@endphp

@once
    <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/dashboard-feed/news-card.css') }}?v=1">
@endonce

<section class="hnt-feed-news-card hnt-feed-news-card--{{ $newsCard->publisher }}" aria-label="{{ $newsCard->publisherName() }}">
    <div class="hnt-feed-news-card-intro">
        <span>{{ $newsCard->publisherKicker() }}</span>
        <h3>{{ $newsCard->headline }}</h3>
        <small>{{ $newsPublishedLabel }}</small>
    </div>

    @if($newsHighlights->isNotEmpty())
        <ul class="hnt-feed-news-card-highlights">
            @foreach($newsHighlights as $highlight)
                <li><i aria-hidden="true"></i><span>{{ $highlight }}</span></li>
            @endforeach
        </ul>
    @endif
</section>
