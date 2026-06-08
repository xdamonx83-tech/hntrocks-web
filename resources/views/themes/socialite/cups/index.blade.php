@extends('themes.socialite.layouts.app')

@section('title', __('ui.cup_index_title'))
@section('meta_description', __('ui.cup_index_banner_text'))
@section('canonical_url', route('cups.index'))
@section('robots', request()->query() ? 'noindex,follow' : 'index,follow')
@section('og_type', 'website')

@push('head')
    @php
        $cupIndexItems = $cups->getCollection()->take(12)->values()->map(function ($cup, int $index): array {
            return array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('cups.show', $cup),
                'name' => $cup->title,
                'description' => \Illuminate\Support\Str::limit(strip_tags($cup->displaySummary()), 150, ''),
            ], static fn ($value) => $value !== null && $value !== '');
        })->all();

        $cupIndexStructuredData = [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([
                array_filter([
                    '@type' => 'CollectionPage',
                    '@id' => route('cups.index') . '#collection',
                    'url' => route('cups.index'),
                    'name' => __('ui.cup_index_title'),
                    'description' => __('ui.cup_index_banner_text'),
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        '@id' => rtrim((string) config('app.url', 'https://hnt.rocks'), '/') . '/#website',
                    ],
                    'mainEntity' => $cupIndexItems ? [
                        '@type' => 'ItemList',
                        'numberOfItems' => count($cupIndexItems),
                        'itemListElement' => $cupIndexItems,
                    ] : null,
                ], static fn ($value) => $value !== null && $value !== ''),
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('cups.index') . '#breadcrumb',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'HNT.rocks', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.cups'), 'item' => route('cups.index')],
                    ],
                ],
            ])),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($cupIndexStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    $viewer = auth()->user();
    $canCreateCups = $viewer?->isAdmin() === true;
    $filters = $filters ?? [];
    $activeStatus = $filters['status'] ?? '';
    $mineActive = (bool) ($filters['mine'] ?? false);
    $baseFilterParams = request()->except(['page', 'status', 'q', 'mine']);
    $statusUrl = fn (?string $status) => route('cups.index', array_filter(array_merge($baseFilterParams, ['status' => $status]), fn ($value) => $value !== null && $value !== ''));
    $mineUrl = $viewer ? route('cups.index', array_filter(array_merge(request()->except(['page', 'status']), ['mine' => 1]), fn ($value) => $value !== null && $value !== '')) : route('login');
    $cupsCollection = $cups->getCollection();
    $featuredCups = $cupsCollection->take(8);
    $upcomingCups = $cupsCollection->filter(fn ($cup) => in_array($cup->status, ['active', 'planned'], true))->values();
    if ($upcomingCups->isEmpty()) {
        $upcomingCups = $cupsCollection;
    }

    $fallbackEventImage = static function (int $index, string $type = 'img'): string {
        if ($type === 'listing') {
            return asset('assets/socialite/images/events/listing-' . ((($index % 6) + 1)) . '.jpg');
        }

        return asset('assets/socialite/images/events/img-' . ((($index % 4) + 1)) . '.jpg');
    };

    $cupImage = static function ($cup, int $index, string $type = 'img') use ($fallbackEventImage): string {
        return $cup->cover_path ? $cup->coverUrl() : $fallbackEventImage($index, $type);
    };

    $cupDateLine = static function ($cup): string {
        if ($cup->starts_at) {
            return $cup->starts_at->translatedFormat('D d.m.Y · H:i');
        }

        if ($cup->registration_closes_at) {
            return __('ui.cup_registration_closes_at_short', ['date' => $cup->registration_closes_at->translatedFormat('d.m.Y')]);
        }

        return $cup->statusLabel();
    };

    $cupLocationLine = static function ($cup): string {
        return collect([$cup->platform ?: __('ui.cup_platform_open'), $cup->region ?: __('ui.cup_region_open')])->filter()->implode(' · ');
    };

    $statusColor = static function ($cup): string {
        return match ($cup->status) {
            'active' => 'text-teal-600',
            'planned' => 'text-blue-600',
            'finished' => 'text-red-600',
            default => 'text-gray-500',
        };
    };

    $hubCards = [
        [
            'title' => __('ui.hall_of_fame'),
            'text' => __('ui.cup_socialite_hub_hof'),
            'url' => route('hall-of-fame.index'),
        ],
        [
            'title' => __('ui.cup_status_active'),
            'text' => __('ui.cup_socialite_hub_active'),
            'url' => $statusUrl('active'),
        ],
        [
            'title' => __('ui.cup_status_planned'),
            'text' => __('ui.cup_socialite_hub_planned'),
            'url' => $statusUrl('planned'),
        ],
        [
            'title' => __('ui.cup_socialite_widget_scoring_title'),
            'text' => __('ui.cup_socialite_widget_scoring_text_short'),
            'url' => route('cups.index'),
        ],
        [
            'title' => __('ui.cup_socialite_widget_fairplay_title'),
            'text' => __('ui.cup_socialite_widget_fairplay_text_short'),
            'url' => route('cups.index'),
        ],
        [
            'title' => __('ui.cup_only_mine'),
            'text' => __('ui.cup_socialite_hub_mine'),
            'url' => $mineUrl,
        ],
    ];
@endphp

<div class="2xl:max-w-[1220px] max-w-[1065px] mx-auto">
    <div class="page-heading">
        <h1 class="page-title">{{ __('ui.cup_index_banner_title', ['count' => $cups->total()]) }}</h1>

        <nav class="nav__underline">
            <ul class="group">
                <li class="{{ ! $mineActive && $activeStatus === '' ? 'uk-active' : '' }}"><a href="{{ $statusUrl(null) }}">{{ __('ui.cup_filter_all') }}</a></li>
                <li class="{{ ! $mineActive && $activeStatus === 'active' ? 'uk-active' : '' }}"><a href="{{ $statusUrl('active') }}">{{ __('ui.cup_status_active') }}</a></li>
                <li class="{{ $mineActive ? 'uk-active' : '' }}"><a href="{{ $mineUrl }}">{{ __('ui.cup_only_mine') }}</a></li>
            </ul>
        </nav>
    </div>

    <section class="box p-5 mb-6 hnt-seo-intro-card">
        <div class="grid md:grid-cols-2 gap-4 items-start">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-primary mb-2">{{ __('ui.cup_seo_intro_kicker') }}</p>
                <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.cup_seo_intro_title') }}</h2>
                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-white/70">{{ __('ui.cup_seo_intro_text') }}</p>
            </div>
            <div class="grid sm:grid-cols-3 gap-2 text-sm">
                <a href="{{ $statusUrl('active') }}" class="rounded-xl bg-secondery p-4 dark:bg-white/5">
                    <span class="block text-gray-500 dark:text-white/60">{{ __('ui.cup_status_active') }}</span>
                    <strong class="mt-1 block text-black dark:text-white">{{ $cupsCollection->where('status', 'active')->count() }}</strong>
                </a>
                <a href="{{ route('hall-of-fame.index') }}" class="rounded-xl bg-secondery p-4 dark:bg-white/5">
                    <span class="block text-gray-500 dark:text-white/60">{{ __('ui.hall_of_fame') }}</span>
                    <strong class="mt-1 block text-black dark:text-white">{{ __('ui.cup_seo_winners_short') }}</strong>
                </a>
                <a href="{{ route('loadout-challenges.index') }}" class="rounded-xl bg-secondery p-4 dark:bg-white/5">
                    <span class="block text-gray-500 dark:text-white/60">{{ __('ui.loadout_nav') }}</span>
                    <strong class="mt-1 block text-black dark:text-white">{{ __('ui.cup_seo_challenges_short') }}</strong>
                </a>
            </div>
        </div>
    </section>

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-500/10 dark:text-green-200">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-500/10 dark:text-red-200">
            <div>{{ __('ui.please_check') }}</div>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('cups.index') }}" class="mb-6 grid gap-3 sm:grid-cols-[1fr_auto_auto]">
        @if ($activeStatus !== '')
            <input type="hidden" name="status" value="{{ $activeStatus }}">
        @endif
        @if ($mineActive)
            <input type="hidden" name="mine" value="1">
        @endif
        <div class="relative">
            <ion-icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-500"></ion-icon>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ui.cup_socialite_search_placeholder') }}" class="w-full !pl-11 !pr-4 !h-12 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5">
        </div>
        <select name="platform" onchange="this.form.submit()" class="w-full sm:w-56 !h-12 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5">
            <option value="">{{ __('ui.cup_all_platforms') }}</option>
            @foreach (['PC', 'PlayStation', 'Xbox', 'PC / PlayStation / Xbox'] as $option)
                <option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button type="submit" class="button bg-primary text-white !h-12 px-5">{{ __('ui.search') }}</button>
    </form>

    @if ($featuredCups->isNotEmpty())
        <div class="relative" tabindex="-1" uk-slider="finite:true">
            <div class="uk-slider-container pb-1">
                <ul class="uk-slider-items grid-small">
                    @foreach ($featuredCups as $featuredCup)
                        @php
                            $cupUrl = route('cups.show', $featuredCup);
                            $teamsCount = (int) ($featuredCup->active_teams_count ?? 0);
                            $pendingCount = (int) ($featuredCup->pending_submissions_count ?? 0);
                        @endphp
                        <li class="lg:w-1/4 sm:w-1/3 w-1/2">
                            <div class="card">
                                <a href="{{ $cupUrl }}">
                                    <div class="card-media h-32">
                                        <img src="{{ $cupImage($featuredCup, $loop->index) }}" alt="{{ __('ui.cup_cover_alt', ['title' => $featuredCup->title]) }}">
                                        <div class="card-overly"></div>
                                    </div>
                                </a>
                                <div class="card-body">
                                    <p class="text-xs font-medium {{ $statusColor($featuredCup) }} mb-1">{{ $cupDateLine($featuredCup) }}</p>
                                    <a href="{{ $cupUrl }}"><h4 class="card-title text-sm">{{ $featuredCup->title }}</h4></a>
                                    <a href="{{ $cupUrl }}"><p class="card-text text-black mt-2 dark:text-white/80">{{ $cupLocationLine($featuredCup) }}</p></a>
                                    <div class="card-list-info text-xs mt-1">
                                        <div>{{ $teamsCount }} {{ __('ui.cup_card_teams') }}</div>
                                        <div class="md:block hidden">·</div>
                                        <div>{{ $pendingCount }} {{ __('ui.cup_card_reviews') }}</div>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="{{ $cupUrl }}" class="button bg-primary text-white flex-1">{{ __('ui.cup_view') }}</a>
                                        <a href="{{ $cupUrl }}" class="button bg-secondery !w-auto dark:bg-white/10" aria-label="{{ __('ui.cup_view') }}"><ion-icon name="arrow-redo" class="text-lg"></ion-icon></a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            <a class="nav-prev !top-20" href="#" uk-slider-item="previous"><ion-icon name="chevron-back" class="text-2xl"></ion-icon></a>
            <a class="nav-next !top-20" href="#" uk-slider-item="next"><ion-icon name="chevron-forward" class="text-2xl"></ion-icon></a>
        </div>
    @endif

    <div class="sm:my-6 my-3 flex items-center justify-between md:mt-10">
        <div>
            <h2 class="text-xl font-semibold text-black dark:text-white">{{ __('ui.cup_socialite_hub_title') }}</h2>
            <p class="font-normal text-sm text-gray-500 leading-6 dark:text-white/70">{{ __('ui.cup_socialite_hub_text') }}</p>
        </div>
        <a href="{{ route('hall-of-fame.index') }}" class="text-blue-500 sm:block hidden text-sm">{{ __('ui.see_all') }}</a>
    </div>

    <div class="mt-4" tabindex="-1" uk-slider="finite:true">
        <div class="uk-slider-container pb-1">
            <ul class="uk-slider-items grid-small">
                @foreach ($hubCards as $hubCard)
                    <li class="md:w-1/5 sm:w-1/3 w-1/2">
                        <a href="{{ $hubCard['url'] }}">
                            <div class="relative rounded-lg overflow-hidden">
                                <img src="{{ $fallbackEventImage($loop->index, 'listing') }}" alt="{{ $hubCard['title'] }}" class="h-36 w-full object-cover">
                                <div class="w-full bottom-0 absolute left-0 bg-gradient-to-t from-black/60 pt-10">
                                    <div class="text-white p-5">
                                        <div class="text-sm font-light">{{ $hubCard['text'] }}</div>
                                        <div class="text-lg leading-5 mt-1.5">{{ $hubCard['title'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <a class="nav-prev" href="#" uk-slider-item="previous"><ion-icon name="chevron-back" class="text-2xl"></ion-icon></a>
        <a class="nav-next" href="#" uk-slider-item="next"><ion-icon name="chevron-forward" class="text-2xl"></ion-icon></a>
    </div>

    <div class="flex items-center justify-between text-black dark:text-white py-3 mt-6">
        <h3 class="text-xl font-semibold">{{ __('ui.cup_socialite_upcoming_title') }}</h3>
        @if ($canCreateCups)
            <a href="{{ route('cups.create') }}" class="text-sm text-blue-500">{{ __('ui.cup_create_title') }}</a>
        @else
            <a href="{{ route('hall-of-fame.index') }}" class="text-sm text-blue-500">{{ __('ui.hall_of_fame') }}</a>
        @endif
    </div>

    @if ($upcomingCups->isEmpty())
        <div class="card p-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-secondery dark:bg-white/10">
                <ion-icon name="trophy-outline" class="text-3xl text-gray-500"></ion-icon>
            </div>
            <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.cup_empty_title') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-white/70">{{ __('ui.cup_empty_text') }}</p>
            @if ($canCreateCups)
                <a class="button bg-primary text-white !w-auto mt-5" href="{{ route('cups.create') }}">{{ __('ui.cup_create_title') }}</a>
            @endif
        </div>
    @else
        <div class="grid lg:grid-cols-4 md:grid-cols-3 sm:grid-cols-2 gap-2.5 mt-4">
            @foreach ($upcomingCups as $cup)
                @php
                    $cupUrl = route('cups.show', $cup);
                    $teamsCount = (int) ($cup->active_teams_count ?? 0);
                    $pendingCount = (int) ($cup->pending_submissions_count ?? 0);
                    $summary = Str::limit($cup->displaySummary(), 74);
                @endphp
                <div class="card">
                    <a href="{{ $cupUrl }}">
                        <div class="card-media h-32">
                            <img src="{{ $cupImage($cup, $loop->index + 2) }}" alt="{{ __('ui.cup_cover_alt', ['title' => $cup->title]) }}">
                            <div class="card-overly"></div>
                        </div>
                    </a>
                    <div class="card-body">
                        <p class="text-xs font-medium {{ $statusColor($cup) }} mb-1">{{ $cupDateLine($cup) }}</p>
                        <a href="{{ $cupUrl }}"><h4 class="card-title text-sm">{{ $cup->title }}</h4></a>
                        <a href="{{ $cupUrl }}"><p class="card-text text-black mt-2 dark:text-white/80">{{ $cupLocationLine($cup) }}</p></a>
                        <p class="text-xs text-gray-500 mt-2 leading-5 dark:text-white/70">{{ $summary }}</p>
                        <div class="card-list-info text-xs mt-1">
                            <div>{{ $cup->modeLabel() }}</div>
                            <div class="md:block hidden">·</div>
                            <div>{{ $teamsCount }} {{ __('ui.cup_card_teams') }}</div>
                            @if ($pendingCount > 0)
                                <div class="md:block hidden">·</div>
                                <div>{{ $pendingCount }} {{ __('ui.cup_card_reviews') }}</div>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ $cupUrl }}" class="button bg-primary text-white flex-1">{{ __('ui.cup_view') }}</a>
                            <a href="{{ $cupUrl }}" class="button bg-secondery !w-auto dark:bg-white/10" aria-label="{{ __('ui.cup_view') }}"><ion-icon name="arrow-redo" class="text-lg"></ion-icon></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($cups->hasMorePages())
        <div class="flex justify-center my-8">
            <a href="{{ $cups->nextPageUrl() }}" class="bg-white py-2 px-5 rounded-full shadow-md font-semibold text-sm dark:bg-dark2">{{ __('ui.load_more') }}</a>
        </div>
    @endif
</div>
@endsection
