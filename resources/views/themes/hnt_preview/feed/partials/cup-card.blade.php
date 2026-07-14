@php
    /** @var \App\Models\FeedCupCard $cupCard */
    $cup = $cupCard->cup;
    $teamSize = max(1, min(3, (int) ($cup?->team_size ?: 1)));
    $modeLabel = match ($teamSize) {
        1 => __('hnt_cup_crosspost.solo'),
        2 => __('hnt_cup_crosspost.duo'),
        3 => __('hnt_cup_crosspost.trio'),
    };
    $platforms = collect($cup?->allowedPlatforms() ?? [])
        ->map(fn (string $platform): string => match ($platform) {
            'PlayStation' => 'PS5',
            default => $platform,
        })
        ->implode(' / ');
    $platforms = $platforms !== '' ? $platforms : __('hnt_cup_crosspost.all_platforms');
    $dateLabel = $cup?->starts_at?->translatedFormat('d.m.') ?: __('hnt_cup_crosspost.date_open');
@endphp

@once
    <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/dashboard-feed/cup-card.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/cup-card.css')) ?: time() }}">
@endonce

@if($cup)
<section class="hnt-feed-cup-card" aria-label="{{ __('hnt_cup_crosspost.card_aria', ['title' => $cup->title]) }}">
    <div class="hnt-feed-cup-card-copy">
        <span>{{ __('hnt_cup_crosspost.kicker') }}</span>
        <h3>{{ $cup->title }}</h3>
        <p>{{ $modeLabel }} · {{ $platforms }} · {{ $dateLabel }}</p>
    </div>
    <a class="hnt-feed-cup-card-action" href="{{ route('cups.show', $cup) }}">
        {{ __('hnt_cup_crosspost.details') }}
    </a>
</section>
@endif
