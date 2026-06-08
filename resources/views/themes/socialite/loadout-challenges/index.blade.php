@extends('themes.socialite.layouts.app')

@section('title', __('ui.loadout_meta_title'))
@section('meta_description', __('ui.loadout_meta_description'))
@section('canonical_url', route('loadout-challenges.index'))
@section('robots', request()->query() ? 'noindex,follow' : 'index,follow')
@section('og_type', 'website')

@push('head')
    @php
        $loadoutIndexItems = $challenges->take(12)->values()->map(function ($challenge, int $index): array {
            return array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('loadout-challenges.show', $challenge),
                'name' => $challenge->title,
                'description' => \Illuminate\Support\Str::limit(strip_tags($challenge->summary ?: $challenge->description ?: __('ui.loadout_meta_description')), 150, ''),
            ], static fn ($value) => $value !== null && $value !== '');
        })->all();

        $loadoutIndexStructuredData = [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([
                array_filter([
                    '@type' => 'CollectionPage',
                    '@id' => route('loadout-challenges.index') . '#collection',
                    'url' => route('loadout-challenges.index'),
                    'name' => __('ui.loadout_meta_title'),
                    'description' => __('ui.loadout_meta_description'),
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        '@id' => rtrim((string) config('app.url', 'https://hnt.rocks'), '/') . '/#website',
                    ],
                    'mainEntity' => $loadoutIndexItems ? [
                        '@type' => 'ItemList',
                        'numberOfItems' => count($loadoutIndexItems),
                        'itemListElement' => $loadoutIndexItems,
                    ] : null,
                ], static fn ($value) => $value !== null && $value !== ''),
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('loadout-challenges.index') . '#breadcrumb',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'HNT.rocks', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.loadout_title'), 'item' => route('loadout-challenges.index')],
                    ],
                ],
            ])),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($loadoutIndexStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-loadout-challenges-page">
    <div class="flex-1 min-w-0">
    <div class="page-heading">
        <h1 class="page-title">{{ __('ui.loadout_title') }}</h1>
        <nav class="nav__underline">
            <ul class="group">
                <li @class(['uk-active' => $selectedStatus === null])><a href="{{ route('loadout-challenges.index') }}">{{ __('ui.all') }}</a></li>
                <li @class(['uk-active' => $selectedStatus === 'running'])><a href="{{ route('loadout-challenges.index', ['status' => 'running']) }}">{{ __('ui.loadout_runtime_running') }}</a></li>
                <li @class(['uk-active' => $selectedStatus === 'planned'])><a href="{{ route('loadout-challenges.index', ['status' => 'planned']) }}">{{ __('ui.loadout_runtime_planned') }}</a></li>
                <li @class(['uk-active' => $selectedStatus === 'ended'])><a href="{{ route('loadout-challenges.index', ['status' => 'ended']) }}">{{ __('ui.loadout_runtime_ended') }}</a></li>
            </ul>
        </nav>
    </div>

    @if(session('status'))
        <div class="box p-4 mb-5 hnt-loadout-status">{{ session('status') }}</div>
    @endif

    <section class="hnt-loadout-hero box overflow-hidden mb-6">
        <div class="hnt-loadout-hero-glow"></div>
        <div class="hnt-loadout-hero-content">
            <div>
                <span class="hnt-loadout-kicker">{{ __('ui.loadout_kicker') }}</span>
                <h2>{{ __('ui.loadout_hero_title') }}</h2>
                <p>{{ __('ui.loadout_hero_text') }}</p>
            </div>
            <div class="hnt-loadout-hero-stats">
                <div><strong>{{ $stats['running'] }}</strong><span>{{ __('ui.loadout_runtime_running') }}</span></div>
                <div><strong>{{ $stats['planned'] }}</strong><span>{{ __('ui.loadout_runtime_planned') }}</span></div>
                <div><strong>{{ $stats['accepted'] }}</strong><span>{{ __('ui.loadout_accepted_runs') }}</span></div>
            </div>
        </div>
    </section>

    <section class="box p-5 mb-6 hnt-loadout-seo-copy">
        <div class="grid md:grid-cols-2 gap-4 items-start">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-primary mb-2">{{ __('ui.loadout_seo_kicker') }}</p>
                <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.loadout_seo_title') }}</h2>
                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-white/70">{{ __('ui.loadout_seo_text') }}</p>
            </div>
            <div class="grid sm:grid-cols-2 gap-2 text-sm">
                <a href="{{ route('cups.index') }}" class="rounded-xl bg-secondery p-4 dark:bg-white/5">
                    <span class="block text-gray-500 dark:text-white/60">{{ __('ui.cups') }}</span>
                    <strong class="mt-1 block text-black dark:text-white">{{ __('ui.loadout_seo_cups_link') }}</strong>
                </a>
                <a href="{{ route('moment-of-week.index') }}" class="rounded-xl bg-secondery p-4 dark:bg-white/5">
                    <span class="block text-gray-500 dark:text-white/60">{{ __('ui.moment_week_title') }}</span>
                    <strong class="mt-1 block text-black dark:text-white">{{ __('ui.loadout_seo_moment_link') }}</strong>
                </a>
            </div>
        </div>
    </section>

    <section class="hnt-loadout-grid">
        @forelse($challenges as $challenge)
            <article class="hnt-loadout-card {{ $challenge->canAcceptSubmissions() ? 'is-running' : '' }}">
                <div class="hnt-loadout-card-head">
                    <span>{{ $challenge->runtimeStatusLabel() }}</span>
                    @if($challenge->xp_reward > 0)
                        <strong>+{{ number_format($challenge->xp_reward, 0, ',', '.') }} XP</strong>
                    @endif
                </div>
                <h2><a href="{{ route('loadout-challenges.show', $challenge) }}">{{ $challenge->title }}</a></h2>
                <p>{{ $challenge->summary ?: __('ui.loadout_no_summary') }}</p>
                <div class="hnt-loadout-meta">
                    <span>{{ $challenge->accepted_submissions_count }} {{ __('ui.loadout_accepted_short') }}</span>
                    <span>{{ $challenge->submissions_count }} {{ __('ui.loadout_submissions_short') }}</span>
                    @if($challenge->starts_at)<span>{{ __('ui.loadout_starts') }} {{ $challenge->starts_at->format('d.m.Y') }}</span>@endif
                    @if($challenge->ends_at)<span>{{ __('ui.loadout_ends') }} {{ $challenge->ends_at->format('d.m.Y') }}</span>@endif
                </div>
                <a class="hnt-loadout-button" href="{{ route('loadout-challenges.show', $challenge) }}">{{ __('ui.loadout_open') }}</a>
            </article>
        @empty
            <section class="box p-6 hnt-loadout-empty">
                <h2>{{ __('ui.loadout_empty_title') }}</h2>
                <p>{{ __('ui.loadout_empty_text') }}</p>
            </section>
        @endforelse
    </section>
    </div>
</div>
@endsection
