@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.cup_ideas_meta_title'))
@section('robots', 'index,follow')
@section('main_class', 'feed-main cup-feedback-main cup-ideas-main')

@php
    use Illuminate\Support\Str;

    $sortUrl = static fn (string $sort) => route('cup-ideas.index', array_filter([
        'category' => $selectedCategory,
        'status' => $selectedStatus,
        'sort' => $sort,
    ], static fn ($value) => $value !== null && $value !== ''));

    $dateFormat = app()->getLocale() === 'de' ? 'd.m.Y' : 'M j, Y';
@endphp

@section('content')
    <div class="cup-feedback-shell cup-ideas-shell" id="js-cup-ideas-page">
        @if (session('status'))
            <div class="hnt-crowns-alert success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="hnt-crowns-alert warning">
                <strong>{{ __('ui.please_check') }}</strong>
                <ul style="margin-top: 8px; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="feedback-hero cup-ideas-hero" aria-label="{{ __('ui.cup_ideas_title') }}">
            <div class="feedback-hero-glow"></div>
            <div class="feedback-hero-copy">
                <span class="feedback-kicker">
                    <i class="ph ph-lightbulb" aria-hidden="true"></i>
                    {{ __('ui.cup_ideas_kicker') }}
                </span>
                <h1>{{ __('ui.cup_ideas_hero_title') }}</h1>
                <p>{{ __('ui.cup_ideas_hero_text') }}</p>

                <div class="cup-ideas-hero-stats" aria-label="{{ __('ui.cup_ideas_title') }}">
                    <div><strong>{{ $stats['total'] }}</strong><span>{{ __('ui.cup_ideas_stat_total') }}</span></div>
                    <div><strong>{{ $stats['planned'] }}</strong><span>{{ __('ui.cup_ideas_stat_planned') }}</span></div>
                    <div><strong>{{ $stats['implemented'] }}</strong><span>{{ __('ui.cup_ideas_stat_implemented') }}</span></div>
                </div>
            </div>
        </section>

        <nav class="gamification-tabs cup-ideas-tabs" aria-label="{{ __('ui.cup_ideas_title') }}">
            <a class="active" href="#cup-ideas-list">{{ __('ui.cup_ideas_tab_ideas') }} <span>{{ $ideas->total() }}</span></a>
            <a href="#cup-ideas-create">{{ __('ui.cup_ideas_tab_submit') }}</a>
            <a href="{{ route('cup-feedback.create') }}">{{ __('ui.cup_feedback') }}</a>
        </nav>

        <section class="feedback-section cup-ideas-filter-section">
            <div class="feedback-section-title">
                <span class="feedback-section-icon">
                    <i class="ph ph-funnel" aria-hidden="true"></i>
                </span>
                <div>
                    <h2>{{ __('ui.filter') }}</h2>
                    <p>{{ __('ui.cup_ideas_help_1') }}</p>
                </div>
            </div>

            <form method="get" action="{{ route('cup-ideas.index') }}" class="feedback-form-grid two cup-ideas-filter-form">
                <label class="feedback-field" for="category">
                    <span>{{ __('ui.cup_ideas_filter_category') }}</span>
                    <select id="category" name="category">
                        <option value="">{{ __('ui.cup_ideas_filter_all_categories') }}</option>
                        @foreach ($categoryOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="feedback-field" for="status">
                    <span>{{ __('ui.cup_ideas_filter_status') }}</span>
                    <select id="status" name="status">
                        <option value="">{{ __('ui.cup_ideas_filter_all_statuses') }}</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <input type="hidden" name="sort" value="{{ $selectedSort }}">

                <div class="cup-ideas-filter-actions">
                    <button type="submit" class="btn-create">{{ __('ui.filter') }}</button>
                    <a class="btn-following" href="{{ route('cup-ideas.index') }}">{{ __('ui.reset') }}</a>
                </div>
            </form>

            <div class="cup-ideas-sort-row" aria-label="{{ __('ui.cup_ideas_title') }}">
                <a href="{{ $sortUrl('top') }}" @class(['active' => $selectedSort === 'top'])>{{ __('ui.cup_ideas_sort_top') }}</a>
                <a href="{{ $sortUrl('new') }}" @class(['active' => $selectedSort === 'new'])>{{ __('ui.cup_ideas_sort_new') }}</a>
            </div>
        </section>

        <section id="cup-ideas-list" class="cup-ideas-list" aria-label="{{ __('ui.cup_ideas_tab_ideas') }}">
            @forelse ($ideas as $idea)
                @php
                    $hasVoted = filled($idea->viewerVote);
                    $author = $idea->user;
                    $avatar = $author ? $author->avatarUrl() : asset('assets/vikinger/img/default-avatar.svg');
                    $statusClass = Str::slug($idea->status);
                @endphp

                <article class="cup-idea-card {{ $idea->is_featured ? 'is-featured' : '' }}">
                    <div class="cup-idea-vote">
                        @auth
                            <form method="post" action="{{ route('cup-ideas.vote', $idea) }}">
                                @csrf
                                <button type="submit" @class(['active' => $hasVoted]) aria-label="{{ __('ui.cup_ideas_vote') }}">
                                    <span>▲</span>
                                    <strong>{{ $idea->votes_count }}</strong>
                                    <small>{{ __('ui.cup_ideas_votes') }}</small>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" aria-label="{{ __('ui.login') }}">
                                <span>▲</span>
                                <strong>{{ $idea->votes_count }}</strong>
                                <small>{{ __('ui.cup_ideas_votes') }}</small>
                            </a>
                        @endauth
                    </div>

                    <div class="cup-idea-body">
                        <div class="cup-idea-meta">
                            <span>{{ $idea->categoryLabel() }}</span>
                            <span class="status {{ $statusClass }}">{{ $idea->statusLabel() }}</span>
                            @if ($idea->cup)
                                <span>{{ $idea->cup->title }}</span>
                            @endif
                            @if ($idea->is_featured)
                                <span class="featured">{{ __('ui.cup_ideas_featured') }}</span>
                            @endif
                        </div>

                        <h2>{{ $idea->title }}</h2>
                        <p>{{ Str::limit($idea->description, 260) }}</p>

                        <div class="cup-idea-author">
                            <img src="{{ $avatar }}" alt="">
                            <span>{{ $author?->name ?? __('ui.unknown_user') }}</span>
                            <small>{{ $idea->created_at?->format($dateFormat) }}</small>
                        </div>
                    </div>
                </article>
            @empty
                <article class="gamification-empty-card">
                    <h2>{{ __('ui.cup_ideas_empty_title') }}</h2>
                    <p>{{ __('ui.cup_ideas_empty_text') }}</p>
                </article>
            @endforelse
        </section>

        @if ($ideas->hasPages())
            <div class="members-load-wrap">
                @if ($ideas->previousPageUrl())
                    <a class="btn-following" href="{{ $ideas->previousPageUrl() }}">{{ __('ui.preview_pagination_back') }}</a>
                @endif
                @if ($ideas->nextPageUrl())
                    <a class="btn-create" href="{{ $ideas->nextPageUrl() }}">{{ __('ui.preview_pagination_load_more') }}</a>
                @endif
            </div>
        @endif

        <section id="cup-ideas-create" class="feedback-section feedback-ticket-section cup-ideas-create-section">
            <div class="feedback-section-title">
                <span class="feedback-section-icon">
                    <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                </span>
                <div>
                    <h2>{{ __('ui.cup_ideas_submit_title') }}</h2>
                    <p>{{ __('ui.cup_ideas_submit_text') }}</p>
                </div>
            </div>

            @auth
                <form method="post" action="{{ route('cup-ideas.store') }}" class="feedback-form-grid one cup-ideas-create-form">
                    @csrf

                    <label class="feedback-field" for="idea-title">
                        <span>{{ __('ui.cup_ideas_field_title') }}</span>
                        <input id="idea-title" type="text" name="title" value="{{ old('title') }}" required maxlength="160" placeholder="{{ __('ui.cup_ideas_field_title_placeholder') }}">
                    </label>

                    <div class="feedback-form-grid two">
                        <label class="feedback-field" for="idea-category">
                            <span>{{ __('ui.cup_ideas_field_category') }}</span>
                            <select id="idea-category" name="category" required>
                                @foreach ($categoryOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="feedback-field" for="idea-cup">
                            <span>{{ __('ui.cup_ideas_field_cup') }}</span>
                            <select id="idea-cup" name="cup_id">
                                <option value="">{{ __('ui.cup_ideas_field_cup_optional') }}</option>
                                @foreach ($cups as $cup)
                                    <option value="{{ $cup->id }}" @selected((string) old('cup_id') === (string) $cup->id)>{{ $cup->title }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <label class="feedback-field" for="idea-description">
                        <span>{{ __('ui.cup_ideas_field_description') }}</span>
                        <textarea id="idea-description" name="description" rows="7" required maxlength="6000" placeholder="{{ __('ui.cup_ideas_field_description_placeholder') }}">{{ old('description') }}</textarea>
                    </label>

                    <div class="feedback-actions">
                        <p class="post-text" style="margin: 0;">{{ __('ui.cup_ideas_help_2') }}</p>
                        <button type="submit" class="btn-create">{{ __('ui.cup_ideas_submit_button') }}</button>
                    </div>
                </form>
            @else
                <div class="cup-ideas-login-box">
                    <a href="{{ route('login') }}" class="btn-create">{{ __('ui.login_to_submit') }}</a>
                    <a href="{{ route('register') }}" class="btn-following">{{ __('ui.register') }}</a>
                </div>
            @endauth
        </section>

        <section class="hall-note-grid" style="margin-top: 34px;">
            <article class="hall-note-item">
                <i class="ph ph-star" aria-hidden="true"></i>
                <div>
                    <h3>{{ __('ui.cup_ideas_help_title') }}</h3>
                    <p>{{ __('ui.cup_ideas_help_1') }}</p>
                </div>
            </article>

            <article class="hall-note-item">
                <i class="ph ph-minus" aria-hidden="true"></i>
                <div>
                    <h3>{{ __('ui.cup_feedback') }}</h3>
                    <p>{{ __('ui.cup_ideas_help_2') }}</p>
                </div>
            </article>
        </section>
    </div>
@endsection
