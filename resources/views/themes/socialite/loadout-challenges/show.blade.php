@extends('themes.socialite.layouts.app')

@section('title', $challenge->title.' · HNT.rocks')
@section('meta_description', $challenge->summary ?: __('ui.loadout_meta_description'))
@section('canonical_url', route('loadout-challenges.show', $challenge))
@section('og_type', 'article')
@section('robots', 'index,follow')

@push('head')
    @php
        $loadoutStructuredData = [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([
                array_filter([
                    '@type' => 'CreativeWork',
                    '@id' => route('loadout-challenges.show', $challenge) . '#challenge',
                    'url' => route('loadout-challenges.show', $challenge),
                    'name' => $challenge->title,
                    'description' => \Illuminate\Support\Str::limit(strip_tags($challenge->summary ?: $challenge->description ?: __('ui.loadout_meta_description')), 300, ''),
                    'datePublished' => $challenge->created_at?->toIso8601String(),
                    'dateModified' => $challenge->updated_at?->toIso8601String(),
                    'mainEntityOfPage' => route('loadout-challenges.show', $challenge),
                    'isAccessibleForFree' => true,
                    'about' => ['@type' => 'VideoGame', 'name' => 'Hunt: Showdown'],
                    'interactionStatistic' => [
                        '@type' => 'InteractionCounter',
                        'interactionType' => 'https://schema.org/SubmitAction',
                        'userInteractionCount' => (int) $challenge->accepted_submissions_count,
                    ],
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        '@id' => rtrim((string) config('app.url', 'https://hnt.rocks'), '/') . '/#website',
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'HNT.rocks',
                        'url' => rtrim((string) config('app.url', 'https://hnt.rocks'), '/') . '/',
                    ],
                ], static fn ($value) => $value !== null && $value !== ''),
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('loadout-challenges.show', $challenge) . '#breadcrumb',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'HNT.rocks', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.loadout_title'), 'item' => route('loadout-challenges.index')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $challenge->title, 'item' => route('loadout-challenges.show', $challenge)],
                    ],
                ],
            ])),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($loadoutStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-loadout-challenge-show">
    <div class="flex-1 min-w-0">
    <div class="page-heading">
        <h1 class="page-title">{{ $challenge->title }}</h1>
        <nav class="nav__underline">
            <ul class="group">
                <li><a href="{{ route('loadout-challenges.index') }}">{{ __('ui.loadout_title') }}</a></li>
                <li class="uk-active"><a href="#challenge-details">{{ __('ui.details') }}</a></li>
            </ul>
        </nav>
    </div>

    @if(session('status'))
        <div class="box p-4 mb-5 hnt-loadout-status">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="box p-4 mb-5 hnt-loadout-error">
            <strong>{{ __('ui.validation_error_title') }}</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="hnt-loadout-detail-hero box overflow-hidden mb-6" id="challenge-details">
        <div class="hnt-loadout-hero-glow"></div>
        <div class="hnt-loadout-detail-content">
            <div>
                <span class="hnt-loadout-kicker">{{ $challenge->runtimeStatusLabel() }}</span>
                <h2>{{ $challenge->title }}</h2>
                <p>{{ $challenge->summary ?: __('ui.loadout_no_summary') }}</p>
                <div class="hnt-loadout-meta is-large">
                    @if($challenge->starts_at)<span>{{ __('ui.loadout_starts') }} {{ $challenge->starts_at->format('d.m.Y H:i') }}</span>@endif
                    @if($challenge->ends_at)<span>{{ __('ui.loadout_ends') }} {{ $challenge->ends_at->format('d.m.Y H:i') }}</span>@endif
                    @if($challenge->xp_reward > 0)<span>+{{ number_format($challenge->xp_reward, 0, ',', '.') }} XP</span>@endif
                    @if($challenge->badge_slug)<span>{{ __('ui.badge') }}: {{ $challenge->badge_slug }}</span>@endif
                </div>
            </div>
            <div class="hnt-loadout-detail-stats">
                <div><strong>{{ $challenge->accepted_submissions_count }}</strong><span>{{ __('ui.loadout_accepted_runs') }}</span></div>
                <div><strong>{{ $challenge->submissions_count }}</strong><span>{{ __('ui.loadout_submissions_short') }}</span></div>
            </div>
        </div>
    </section>

    <div class="hnt-loadout-two-column">
        <div class="hnt-loadout-main">
            <section class="box p-5 sm:p-6 hnt-loadout-copy">
                <h2>{{ __('ui.loadout_description') }}</h2>
                <p>{{ $challenge->description ?: __('ui.loadout_no_description') }}</p>
            </section>

            <section class="box p-5 sm:p-6 hnt-loadout-copy">
                <h2>{{ __('ui.loadout_rules') }}</h2>
                <p>{{ $challenge->rules ?: __('ui.loadout_no_rules') }}</p>
            </section>

            <section class="box p-5 sm:p-6 hnt-loadout-copy">
                <h2>{{ __('ui.loadout_allowed_loadout') }}</h2>
                <p>{{ $challenge->loadout_notes ?: __('ui.loadout_no_loadout_notes') }}</p>
            </section>

            <section class="box p-5 sm:p-6 hnt-loadout-copy">
                <h2>{{ __('ui.loadout_accepted_latest') }}</h2>
                <div class="hnt-loadout-submission-list">
                    @forelse($acceptedSubmissions as $submission)
                        <article class="hnt-loadout-submission-card">
                            <div>
                                <strong>{{ $submission->user?->name ?? $submission->user?->username ?? __('ui.unknown_user') }}</strong>
                                <span>{{ $submission->reviewed_at?->format('d.m.Y H:i') ?? $submission->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            @if($submission->outcome)<p class="hnt-loadout-outcome">{{ $submission->outcome }}</p>@endif
                            <p>{{ $submission->body }}</p>
                            @if($submission->media)
                                <a href="{{ $submission->media->url() }}" target="_blank" rel="noopener">{{ __('ui.loadout_open_proof') }}</a>
                            @endif
                        </article>
                    @empty
                        <p>{{ __('ui.loadout_no_accepted_submissions') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="hnt-loadout-side">
            <section class="box p-5 sm:p-6 hnt-loadout-submit-box">
                <h2>{{ __('ui.loadout_submit_title') }}</h2>
                <p>{{ __('ui.loadout_submit_text') }}</p>

                @auth
                    @if($challenge->canAcceptSubmissions())
                        <form method="post" action="{{ route('loadout-challenges.submissions.store', $challenge) }}" enctype="multipart/form-data">
                            @csrf
                            <label>{{ __('ui.loadout_submission_outcome') }}</label>
                            <input type="text" name="outcome" value="{{ old('outcome') }}" maxlength="80" placeholder="{{ __('ui.loadout_submission_outcome_placeholder') }}">

                            <label>{{ __('ui.loadout_submission_body') }}</label>
                            <textarea name="body" rows="6" required minlength="8" maxlength="4000" placeholder="{{ __('ui.loadout_submission_body_placeholder') }}">{{ old('body') }}</textarea>

                            <label>{{ __('ui.loadout_submission_proof') }}</label>
                            <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm">
                            <small>{{ __('ui.loadout_submission_proof_hint', ['mb' => $mediaLimitMb]) }}</small>

                            <button class="hnt-loadout-button is-full" type="submit">{{ __('ui.loadout_submit_button') }}</button>
                        </form>
                    @else
                        <div class="hnt-loadout-closed">{{ __('ui.loadout_submission_closed') }}</div>
                    @endif
                @else
                    <a class="hnt-loadout-button is-full" href="{{ route('login') }}">{{ __('ui.login_to_submit') }}</a>
                @endauth
            </section>

            @auth
                <section class="box p-5 sm:p-6 hnt-loadout-submit-box">
                    <h2>{{ __('ui.loadout_my_submissions') }}</h2>
                    <div class="hnt-loadout-submission-list is-compact">
                        @forelse($viewerSubmissions as $submission)
                            <article class="hnt-loadout-submission-card">
                                <div><strong>{{ $submission->statusLabel() }}</strong><span>{{ $submission->created_at->format('d.m.Y H:i') }}</span></div>
                                <p>{{ $submission->body }}</p>
                            </article>
                        @empty
                            <p>{{ __('ui.loadout_no_my_submissions') }}</p>
                        @endforelse
                    </div>
                </section>
            @endauth
        </aside>
    </div>
    </div>
</div>
@endsection
