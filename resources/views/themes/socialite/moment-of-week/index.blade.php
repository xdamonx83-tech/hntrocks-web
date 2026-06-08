@extends('themes.socialite.layouts.app')

@section('title', __('ui.moment_week_meta_title'))
@section('meta_description', __('ui.moment_week_meta_description'))
@section('canonical_url', route('moment-of-week.index'))
@section('og_type', 'website')

@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        '@id' => route('moment-of-week.index') . '#collection',
        'url' => route('moment-of-week.index'),
        'name' => __('ui.moment_week_meta_title'),
        'description' => __('ui.moment_week_meta_description'),
        'isPartOf' => [
            '@type' => 'WebSite',
            '@id' => rtrim((string) config('app.url', 'https://hnt.rocks'), '/') . '/#website',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
@php
    $currentMoment = $currentSpotlight?->moment;
    $currentAuthor = $currentMoment?->user;
    $currentAuthorAvatar = $currentAuthor?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
@endphp

<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-moment-week-page">
    <div class="flex-1 min-w-0">
        <div class="page-heading">
            <h1 class="page-title">{{ __('ui.moment_week_title') }}</h1>
            <nav class="nav__underline">
                <ul class="group">
                    <li class="uk-active"><a href="#moment-week-current">{{ __('ui.moment_week_tab_current') }}</a></li>
                    <li><a href="#moment-week-archive">{{ __('ui.moment_week_tab_archive') }}</a></li>
                    <li><a href="{{ route('moments.create') }}">{{ __('ui.moment_week_submit_moment') }}</a></li>
                </ul>
            </nav>
        </div>

        <section id="moment-week-current" class="hnt-moment-week-hero box overflow-hidden mb-6">
            <div class="hnt-moment-week-bg"></div>
            @if($currentSpotlight && $currentMoment)
                <div class="hnt-moment-week-feature">
                    <a href="{{ route('moments.show', $currentMoment) }}" class="hnt-moment-week-cover" aria-label="{{ __('ui.open') }}">
                        <img src="{{ $currentMoment->coverUrl() }}" alt="{{ $currentMoment->caption ?: __('ui.moment_week_title') }}">
                        <span class="hnt-moment-week-play">▶</span>
                    </a>
                    <div class="hnt-moment-week-copy">
                        <span class="hnt-moment-week-kicker">{{ __('ui.moment_week_kicker') }}</span>
                        <h2>{{ $currentSpotlight->title ?: ($currentMoment->caption ?: __('ui.moment_week_untitled')) }}</h2>
                        <div class="hnt-moment-week-author">
                            <img src="{{ $currentAuthorAvatar }}" alt="{{ $currentAuthor?->name ?? __('ui.unknown_user') }}">
                            <div>
                                <strong>{{ $currentAuthor?->name ?? __('ui.unknown_user') }}</strong>
                                <span>{{ '@'.($currentAuthor?->username ?? 'unknown') }} · {{ $currentSpotlight->dateLabel() }}</span>
                            </div>
                        </div>
                        @if($currentSpotlight->note)
                            <p>{{ $currentSpotlight->note }}</p>
                        @elseif($currentMoment->description)
                            <p>{{ \Illuminate\Support\Str::limit($currentMoment->description, 220) }}</p>
                        @else
                            <p>{{ __('ui.moment_week_default_text') }}</p>
                        @endif
                        <div class="hnt-moment-week-stats">
                            <span><strong>{{ number_format((int) $currentMoment->likes_count, 0, ',', '.') }}</strong>{{ __('ui.likes') }}</span>
                            <span><strong>{{ number_format((int) $currentMoment->comments_count, 0, ',', '.') }}</strong>{{ __('ui.comments') }}</span>
                            <span><strong>{{ number_format((int) $currentMoment->views_count, 0, ',', '.') }}</strong>{{ __('ui.views') }}</span>
                        </div>
                        <a href="{{ route('moments.show', $currentMoment) }}" class="hnt-moment-week-button">{{ __('ui.moment_week_watch') }}</a>
                    </div>
                </div>
            @else
                <div class="hnt-moment-week-empty">
                    <span class="hnt-moment-week-kicker">{{ __('ui.moment_week_kicker') }}</span>
                    <h2>{{ __('ui.moment_week_empty_title') }}</h2>
                    <p>{{ __('ui.moment_week_empty_text') }}</p>
                    <a href="{{ route('moments.create') }}" class="hnt-moment-week-button">{{ __('ui.moment_week_submit_moment') }}</a>
                </div>
            @endif
        </section>

        <section class="box p-5 sm:p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide" style="color:#e0b45a;">{{ __('ui.moment_week_candidates_kicker') }}</p>
                    <h2 class="mt-1 text-xl font-bold text-black dark:text-white">{{ __('ui.moment_week_candidates_title') }}</h2>
                </div>
                <a href="{{ route('moments.index') }}" class="text-sm font-bold" style="color:#e0b45a;">{{ __('ui.moments') }}</a>
            </div>

            <div class="hnt-moment-week-grid">
                @forelse($topMoments as $moment)
                    @php($author = $moment->user)
                    <article class="hnt-moment-mini-card">
                        <a href="{{ route('moments.show', $moment) }}">
                            <img src="{{ $moment->coverUrl() }}" alt="{{ $moment->caption ?: __('ui.moments') }}">
                            <span>▶</span>
                        </a>
                        <div>
                            <h3>{{ \Illuminate\Support\Str::limit($moment->caption ?: __('ui.moment_week_untitled'), 55) }}</h3>
                            <p>{{ $author?->name ?? __('ui.unknown_user') }}</p>
                            <small>{{ (int) $moment->likes_count }} {{ __('ui.likes') }} · {{ (int) $moment->comments_count }} {{ __('ui.comments') }}</small>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-gray-400">{{ __('ui.moment_week_no_candidates') }}</p>
                @endforelse
            </div>
        </section>

        <section id="moment-week-archive" class="box p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-black dark:text-white">{{ __('ui.moment_week_archive_title') }}</h2>
                <p class="mt-1 text-sm text-gray-400">{{ __('ui.moment_week_archive_text') }}</p>
            </div>

            <div class="hnt-moment-week-archive">
                @forelse($archive as $spotlight)
                    @php($moment = $spotlight->moment)
                    @if($moment)
                        <article class="hnt-moment-archive-card">
                            <a href="{{ route('moments.show', $moment) }}">
                                <img src="{{ $moment->coverUrl() }}" alt="{{ $spotlight->title ?: $moment->caption ?: __('ui.moment_week_title') }}">
                            </a>
                            <div>
                                <span>{{ $spotlight->dateLabel() }}</span>
                                <h3>{{ $spotlight->title ?: ($moment->caption ?: __('ui.moment_week_untitled')) }}</h3>
                                <p>{{ $moment->user?->name ?? __('ui.unknown_user') }}</p>
                            </div>
                        </article>
                    @endif
                @empty
                    <div class="hnt-moment-week-empty-small">{{ __('ui.moment_week_archive_empty') }}</div>
                @endforelse
            </div>

            <div class="mt-5">
                {{ $archive->links() }}
            </div>
        </section>
    </div>

    <aside class="w-full lg:w-80 space-y-4">
        <div class="box p-5 hnt-moment-week-side">
            <h3>{{ __('ui.moment_week_how_title') }}</h3>
            <ul>
                <li>{{ __('ui.moment_week_how_1') }}</li>
                <li>{{ __('ui.moment_week_how_2') }}</li>
                <li>{{ __('ui.moment_week_how_3') }}</li>
            </ul>
        </div>
        <div class="box p-5 hnt-moment-week-side">
            <h3>{{ __('ui.moment_week_create_title') }}</h3>
            <p>{{ __('ui.moment_week_create_text') }}</p>
            <a href="{{ route('moments.create') }}" class="hnt-moment-week-button w-full justify-center">{{ __('ui.moment_week_submit_moment') }}</a>
        </div>
    </aside>
</div>
@endsection
