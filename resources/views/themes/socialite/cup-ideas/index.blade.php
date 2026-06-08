@extends('themes.socialite.layouts.app')

@section('title', __('ui.cup_ideas_meta_title'))
@section('meta_description', __('ui.cup_ideas_meta_description'))
@section('canonical_url', route('cup-ideas.index'))
@section('og_type', 'website')

@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        '@id' => route('cup-ideas.index') . '#collection',
        'url' => route('cup-ideas.index'),
        'name' => __('ui.cup_ideas_meta_title'),
        'description' => __('ui.cup_ideas_meta_description'),
        'isPartOf' => [
            '@type' => 'WebSite',
            '@id' => rtrim((string) config('app.url', 'https://hnt.rocks'), '/') . '/#website',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
@php
    $sortUrl = static fn (string $sort) => route('cup-ideas.index', array_filter([
        'category' => $selectedCategory,
        'status' => $selectedStatus,
        'sort' => $sort,
    ]));
@endphp

<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-cup-ideas-page">
    <div class="flex-1 min-w-0">
        <div class="page-heading">
            <h1 class="page-title">{{ __('ui.cup_ideas_title') }}</h1>
            <nav class="nav__underline">
                <ul class="group">
                    <li class="uk-active"><a href="#cup-ideas-list">{{ __('ui.cup_ideas_tab_ideas') }}</a></li>
                    <li><a href="#cup-ideas-create">{{ __('ui.cup_ideas_tab_submit') }}</a></li>
                    <li><a href="{{ route('cup-feedback.create') }}">{{ __('ui.cup_feedback') }}</a></li>
                </ul>
            </nav>
        </div>

        @if(session('status'))
            <div class="hnt-ideas-alert mb-5">{{ session('status') }}</div>
        @endif

        <section class="hnt-ideas-hero box overflow-hidden mb-6">
            <div class="hnt-ideas-hero-bg"></div>
            <div class="hnt-ideas-hero-content">
                <div>
                    <span class="hnt-ideas-kicker">{{ __('ui.cup_ideas_kicker') }}</span>
                    <h2>{{ __('ui.cup_ideas_hero_title') }}</h2>
                    <p>{{ __('ui.cup_ideas_hero_text') }}</p>
                </div>
                <div class="hnt-ideas-stats">
                    <div><strong>{{ $stats['total'] }}</strong><span>{{ __('ui.cup_ideas_stat_total') }}</span></div>
                    <div><strong>{{ $stats['planned'] }}</strong><span>{{ __('ui.cup_ideas_stat_planned') }}</span></div>
                    <div><strong>{{ $stats['implemented'] }}</strong><span>{{ __('ui.cup_ideas_stat_implemented') }}</span></div>
                </div>
            </div>
        </section>

        <section class="box p-4 sm:p-5 mb-6">
            <form method="get" action="{{ route('cup-ideas.index') }}" class="hnt-ideas-filter">
                <div>
                    <label>{{ __('ui.cup_ideas_filter_category') }}</label>
                    <select name="category">
                        <option value="">{{ __('ui.cup_ideas_filter_all_categories') }}</option>
                        @foreach($categoryOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>{{ __('ui.cup_ideas_filter_status') }}</label>
                    <select name="status">
                        <option value="">{{ __('ui.cup_ideas_filter_all_statuses') }}</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="sort" value="{{ $selectedSort }}">
                <button type="submit">{{ __('ui.filter') }}</button>
                <a href="{{ route('cup-ideas.index') }}">{{ __('ui.reset') }}</a>
            </form>
            <div class="hnt-ideas-sort mt-4">
                <a href="{{ $sortUrl('top') }}" class="{{ $selectedSort === 'top' ? 'is-active' : '' }}">{{ __('ui.cup_ideas_sort_top') }}</a>
                <a href="{{ $sortUrl('new') }}" class="{{ $selectedSort === 'new' ? 'is-active' : '' }}">{{ __('ui.cup_ideas_sort_new') }}</a>
            </div>
        </section>

        <section id="cup-ideas-list" class="hnt-ideas-list">
            @forelse($ideas as $idea)
                @php
                    $hasVoted = filled($idea->viewerVote);
                    $author = $idea->user;
                    $avatar = $author ? $author->avatarUrl() : asset('assets/vikinger/img/default-avatar.svg');
                @endphp
                <article class="hnt-idea-card {{ $idea->is_featured ? 'is-featured' : '' }}">
                    <div class="hnt-idea-vote">
                        @auth
                            <form method="post" action="{{ route('cup-ideas.vote', $idea) }}">
                                @csrf
                                <button type="submit" class="{{ $hasVoted ? 'is-active' : '' }}" aria-label="{{ __('ui.cup_ideas_vote') }}">
                                    <span>▲</span>
                                    <strong>{{ $idea->votes_count }}</strong>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" aria-label="{{ __('ui.login') }}">
                                <span>▲</span>
                                <strong>{{ $idea->votes_count }}</strong>
                            </a>
                        @endauth
                    </div>
                    <div class="hnt-idea-body">
                        <div class="hnt-idea-meta">
                            <span>{{ $idea->categoryLabel() }}</span>
                            <span>{{ $idea->statusLabel() }}</span>
                            @if($idea->cup)
                                <span>{{ $idea->cup->title }}</span>
                            @endif
                            @if($idea->is_featured)
                                <span class="is-featured-label">{{ __('ui.cup_ideas_featured') }}</span>
                            @endif
                        </div>
                        <h3>{{ $idea->title }}</h3>
                        <p>{{ $idea->description }}</p>
                        <div class="hnt-idea-author">
                            <img src="{{ $avatar }}" alt="">
                            <span>{{ $author?->name ?? __('ui.unknown_user') }}</span>
                            <small>{{ $idea->created_at?->format('d.m.Y') }}</small>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card p-8 text-center">
                    <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.cup_ideas_empty_title') }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-white/70">{{ __('ui.cup_ideas_empty_text') }}</p>
                </div>
            @endforelse
        </section>

        <div class="hh-pagination mt-6">{{ $ideas->links() }}</div>
    </div>

    <aside class="2xl:w-[380px] lg:w-[350px] w-full">
        <section id="cup-ideas-create" class="box p-5 sm:p-6 hnt-ideas-form-card">
            <h2>{{ __('ui.cup_ideas_submit_title') }}</h2>
            <p>{{ __('ui.cup_ideas_submit_text') }}</p>

            @if($errors->any())
                <div class="hnt-ideas-error">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @auth
                <form method="post" action="{{ route('cup-ideas.store') }}" class="hnt-ideas-form">
                    @csrf
                    <div>
                        <label>{{ __('ui.cup_ideas_field_title') }}</label>
                        <input type="text" name="title" value="{{ old('title') }}" required maxlength="160" placeholder="{{ __('ui.cup_ideas_field_title_placeholder') }}">
                    </div>
                    <div>
                        <label>{{ __('ui.cup_ideas_field_category') }}</label>
                        <select name="category" required>
                            @foreach($categoryOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>{{ __('ui.cup_ideas_field_cup') }}</label>
                        <select name="cup_id">
                            <option value="">{{ __('ui.cup_ideas_field_cup_optional') }}</option>
                            @foreach($cups as $cup)
                                <option value="{{ $cup->id }}" @selected((string) old('cup_id') === (string) $cup->id)>{{ $cup->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>{{ __('ui.cup_ideas_field_description') }}</label>
                        <textarea name="description" rows="7" required maxlength="6000" placeholder="{{ __('ui.cup_ideas_field_description_placeholder') }}">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit">{{ __('ui.cup_ideas_submit_button') }}</button>
                </form>
            @else
                <div class="hnt-ideas-form">
                    <a href="{{ route('login') }}" class="hnt-ideas-login-button">{{ __('ui.login_to_submit') }}</a>
                    <a href="{{ route('register') }}" class="hnt-ideas-register-link">{{ __('ui.register') }}</a>
                </div>
            @endauth
        </section>

        <section class="box p-5 mt-5 hnt-ideas-help">
            <h3>{{ __('ui.cup_ideas_help_title') }}</h3>
            <ul>
                <li>{{ __('ui.cup_ideas_help_1') }}</li>
                <li>{{ __('ui.cup_ideas_help_2') }}</li>
                <li>{{ __('ui.cup_ideas_help_3') }}</li>
            </ul>
        </section>
    </aside>
</div>
@endsection
