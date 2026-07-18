@extends('themes.hnt_preview.guides.layout')

@section('robots', request()->query() ? 'noindex,follow' : 'index,follow')
@section('canonical', route('guides.index'))
@section('body_class', 'guides-demo-index')
@section('skip_guides_base_styles', '1')

@push('head')
<link href="{{ asset('assets/themes/hnt_preview/guides/guides-index-demo.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides-index-demo.css')) ?: time() }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('assets/themes/hnt_preview/guides/guides-index-demo.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides-index-demo.js')) ?: time() }}" defer></script>
@endpush

@php
    $categoryIcons = [
        'beginner' => 'ph-star',
        'loadouts' => 'ph-sliders-horizontal',
        'weapons-loadouts' => 'ph-sliders-horizontal',
        'traits' => 'ph-user',
        'hunter-traits' => 'ph-user',
        'maps' => 'ph-map-trifold',
        'bosses' => 'ph-skull',
        'pvp' => 'ph-crosshair',
        'teams' => 'ph-users',
        'events' => 'ph-calendar-dots',
    ];
    $difficultyLabels = [
        'beginner' => 'Anfänger',
        'advanced' => 'Fortgeschritten',
        'expert' => 'Experte',
    ];
    $platformLabels = [
        'all' => 'Alle Plattformen',
        'pc' => 'PC',
        'playstation' => 'PlayStation',
        'xbox' => 'Xbox',
    ];
    $featuredRevision = $featuredGuide?->publishedRevision;
    $featuredAuthor = $featuredGuide?->author;
    $featuredAuthorName = $featuredAuthor?->name ?: ($featuredAuthor?->username ?: 'HNT Hunter');
    $featuredCoverUrl = $featuredRevision?->cover_media_id
        ? route('guides.media.show', $featuredRevision->cover_media_id)
        : null;
@endphp

@section('content')
<section class="guides-hero">
    <div>
        <span>HNT.ROCKS COMMUNITY WISSEN</span>
        <h1>Guides</h1>
        <p>Von den ersten Schritten bis zu fortgeschrittenen Rotationen. Geprüftes Wissen aus der Community – sauber strukturiert, mobil lesbar und ohne ungeprüfte Veröffentlichung.</p>
        <div class="guides-hero-actions">
            @auth
                <a href="{{ route('guides.mine') }}"><i class="ph ph-books" aria-hidden="true"></i> Meine Guides</a>
                <form action="{{ route('guides.store') }}" method="post">
                    @csrf
                    <button type="submit"><i class="ph ph-plus" aria-hidden="true"></i> Guide erstellen</button>
                </form>
            @else
                <a href="{{ route('login') }}"><i class="ph ph-sign-in" aria-hidden="true"></i> Anmelden</a>
                <a class="primary" href="{{ route('login') }}"><i class="ph ph-plus" aria-hidden="true"></i> Guide erstellen</a>
            @endauth
        </div>
    </div>

    <div class="guides-hero-stats" aria-label="Guide-Statistik">
        <article><strong>{{ number_format($publishedCount, 0, ',', '.') }}</strong><span>veröffentlichte Guides</span></article>
        <article><strong>{{ number_format($authorCount, 0, ',', '.') }}</strong><span>aktive Autoren</span></article>
        <article><strong>{{ number_format($helpfulCount, 0, ',', '.') }}</strong><span>Hilfreich-Markierungen</span></article>
    </div>
</section>

<section class="guides-overview-layout">
    <aside class="guides-category-column">
        <section class="guides-side-card">
            <header>
                <div><span>KATEGORIEN</span><h2>Wissen entdecken</h2></div>
                <b>{{ $categories->count() }}</b>
            </header>

            <div class="guide-category-list">
                <a class="{{ $filters['category'] === '' ? 'active' : '' }}" href="{{ route('guides.index', request()->except('page', 'category')) }}">
                    <span><i class="ph ph-books" aria-hidden="true"></i></span>
                    <strong>Alle Guides</strong>
                    <b>{{ number_format($publishedCount, 0, ',', '.') }}</b>
                </a>

                @foreach($categories as $category)
                    <a class="{{ $filters['category'] === $category->slug ? 'active' : '' }}" href="{{ route('guides.index', array_merge(request()->except('page', 'category'), ['category' => $category->slug])) }}">
                        <span><i class="ph {{ $categoryIcons[$category->slug] ?? 'ph-book-open-text' }}" aria-hidden="true"></i></span>
                        <strong>{{ $category->label() }}</strong>
                        <b>{{ number_format((int) $category->published_guides_count, 0, ',', '.') }}</b>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="guides-side-card guide-author-card">
            <span>GUIDE-REPUTATION</span>
            <h2>Wissen wird sichtbar</h2>
            <div class="guide-reputation-ring">
                <strong>{{ $viewerReputation === null ? '—' : number_format($viewerReputation, 0, ',', '.') }}</strong>
                <span>Reputation</span>
            </div>
            <p>Hilfreiche, freigegebene Guides stärken die Reputation eines Autors.</p>
            @auth
                <a href="{{ route('guides.mine') }}">Meine Reputation <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
            @else
                <a href="{{ route('login') }}">Anmelden <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
            @endauth
        </section>
    </aside>

    <main class="guides-main-column">
        <form class="guides-toolbar" data-guide-filter-form action="{{ route('guides.index') }}" method="get" aria-label="Guidefilter">
            @if($filters['category'] !== '')
                <input type="hidden" name="category" value="{{ $filters['category'] }}">
            @endif

            <label class="guide-search" for="guideSearch">
                <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                <input id="guideSearch" name="q" value="{{ $filters['q'] }}" type="search" placeholder="Guides, Themen oder Autoren suchen">
            </label>

            <select id="guideLanguage" name="language" aria-label="Sprache">
                <option value="">Alle Sprachen</option>
                <option value="de" @selected($filters['language'] === 'de')>Deutsch</option>
                <option value="en" @selected($filters['language'] === 'en')>Englisch</option>
            </select>

            <select id="guidePlatform" name="platform" aria-label="Plattform">
                <option value="">Alle Plattformen</option>
                <option value="pc" @selected($filters['platform'] === 'pc')>PC</option>
                <option value="playstation" @selected($filters['platform'] === 'playstation')>PlayStation</option>
                <option value="xbox" @selected($filters['platform'] === 'xbox')>Xbox</option>
            </select>

            <select id="guideDifficulty" name="difficulty" aria-label="Schwierigkeit">
                <option value="">Alle Stufen</option>
                <option value="beginner" @selected($filters['difficulty'] === 'beginner')>Anfänger</option>
                <option value="advanced" @selected($filters['difficulty'] === 'advanced')>Fortgeschritten</option>
                <option value="expert" @selected($filters['difficulty'] === 'expert')>Experte</option>
            </select>

            <select id="guideSort" name="sort" aria-label="Sortierung">
                <option value="new" @selected($filters['sort'] === 'new')>Neu</option>
                <option value="helpful" @selected($filters['sort'] === 'helpful')>Hilfreich</option>
                <option value="popular" @selected($filters['sort'] === 'popular')>Beliebt</option>
            </select>
        </form>

        @if($featuredGuide && $featuredRevision)
            <section class="guide-featured">
                @if($featuredCoverUrl)
                    <img src="{{ $featuredCoverUrl }}" alt="{{ $featuredRevision->title }}">
                @else
                    <div class="guide-real-featured-placeholder" aria-hidden="true">
                        <i class="ph ph-book-open-text"></i>
                    </div>
                @endif

                <div>
                    <span>{{ $featuredGuide->is_featured ? 'HERVORGEHOBENER GUIDE' : 'BELIEBTER GUIDE' }}</span>
                    <h2>{{ $featuredRevision->title }}</h2>
                    <p>{{ $featuredRevision->summary }}</p>

                    <div>
                        @if($featuredRevision->category)
                            <span class="guide-chip accent">{{ $featuredRevision->category->label() }}</span>
                        @endif
                        <span class="guide-chip">{{ $difficultyLabels[$featuredRevision->difficulty] ?? ucfirst((string) $featuredRevision->difficulty) }}</span>
                        <span class="guide-chip">{{ $platformLabels[$featuredRevision->platform] ?? ucfirst((string) $featuredRevision->platform) }}</span>
                    </div>

                    <footer>
                        <div>
                            <img src="{{ $featuredAuthor?->avatarUrl() }}" alt="" loading="lazy">
                            <span>
                                <strong>{{ $featuredAuthorName }}</strong>
                                <small>Guide-Autor · {{ number_format((int) $featuredGuide->helpful_count, 0, ',', '.') }} hilfreich</small>
                            </span>
                        </div>
                        <a href="{{ route('guides.show', $featuredGuide) }}">Guide öffnen <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
                    </footer>
                </div>
            </section>
        @else
            <section class="guide-featured guide-featured-empty">
                <div class="guide-real-featured-placeholder" aria-hidden="true"><i class="ph ph-book-open-text"></i></div>
                <div>
                    <span>COMMUNITY GUIDES</span>
                    <h2>Noch kein Guide veröffentlicht</h2>
                    <p>Sobald der erste moderierte Guide veröffentlicht ist, erscheint er hier.</p>
                </div>
            </section>
        @endif

        <section class="guides-section-head">
            <div><span>COMMUNITY GUIDES</span><h2>Alle Guides</h2></div>
            <strong><b>{{ number_format($guides->total(), 0, ',', '.') }}</b> sichtbar</strong>
        </section>

        <section class="guide-grid" id="guideGrid">
            @forelse($guides as $guide)
                @include('themes.hnt_preview.guides.partials.overview-card', [
                    'guide' => $guide,
                    'bookmarkedGuideIds' => $bookmarkedGuideIds,
                ])
            @empty
                <div class="guide-empty guide-empty-visible">
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <h3>Keine passenden Guides</h3>
                    <p>Ändere Suche oder Filter.</p>
                    @if(request()->query())
                        <a href="{{ route('guides.index') }}">Alle Filter zurücksetzen</a>
                    @endif
                </div>
            @endforelse
        </section>

        @if($guides->hasPages())
            <nav class="guide-pagination" aria-label="Guide-Seiten">
                @if($guides->onFirstPage())
                    <span aria-disabled="true"><i class="ph ph-arrow-left" aria-hidden="true"></i> Zurück</span>
                @else
                    <a href="{{ $guides->previousPageUrl() }}" rel="prev"><i class="ph ph-arrow-left" aria-hidden="true"></i> Zurück</a>
                @endif

                <strong>Seite {{ $guides->currentPage() }} von {{ $guides->lastPage() }}</strong>

                @if($guides->hasMorePages())
                    <a href="{{ $guides->nextPageUrl() }}" rel="next">Weiter <i class="ph ph-arrow-right" aria-hidden="true"></i></a>
                @else
                    <span aria-disabled="true">Weiter <i class="ph ph-arrow-right" aria-hidden="true"></i></span>
                @endif
            </nav>
        @endif
    </main>

    <aside class="guides-right-column">
        <section class="guides-side-card guide-create-widget">
            <span>DEIN WISSEN</span>
            <h2>Eigenen Guide erstellen</h2>
            <p>Block-Editor, automatische Entwürfe und Moderation vor der Veröffentlichung.</p>
            <div>
                <article><i class="ph ph-floppy-disk" aria-hidden="true"></i><span><strong>Automatisch gespeichert</strong><small>Entwurf bleibt erhalten</small></span></article>
                <article><i class="ph ph-shield-check" aria-hidden="true"></i><span><strong>Moderiert</strong><small>Nichts erscheint ungeprüft</small></span></article>
                <article><i class="ph ph-arrows-clockwise" aria-hidden="true"></i><span><strong>Versioniert</strong><small>Öffentliche Version bleibt live</small></span></article>
            </div>
            @auth
                <form class="guide-create-form" action="{{ route('guides.store') }}" method="post">
                    @csrf
                    <button type="submit">Guide erstellen <i class="ph ph-arrow-up-right" aria-hidden="true"></i></button>
                </form>
            @else
                <a href="{{ route('login') }}">Anmelden <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
            @endauth
        </section>

        <section class="guides-side-card guide-top-authors">
            <header><div><span>COMMUNITY-EXPERTEN</span><h2>Top Autoren</h2></div><a href="#guideGrid">Alle</a></header>
            <div>
                @forelse($topAuthors as $author)
                    <article>
                        <b>{{ $loop->iteration }}</b>
                        <img src="{{ $author->avatarUrl() }}" alt="" loading="lazy">
                        <span>
                            <a href="{{ route('profile.public', $author) }}"><strong>{{ $author->name ?: $author->username }}</strong></a>
                            <small>{{ number_format((int) $author->published_guides_count, 0, ',', '.') }} Guides · {{ number_format((int) $author->guides_helpful_total, 0, ',', '.') }} hilfreich</small>
                        </span>
                    </article>
                @empty
                    <p class="guide-widget-empty">Noch keine veröffentlichten Autoren.</p>
                @endforelse
            </div>
        </section>

        <section class="guides-side-card guide-status-widget">
            <span>DEINE GUIDES</span>
            <h2>Aktueller Status</h2>
            <div>
                <article><strong>{{ $viewerGuideStats === null ? '—' : number_format($viewerGuideStats['drafts'], 0, ',', '.') }}</strong><span>Entwürfe</span></article>
                <article><strong>{{ $viewerGuideStats === null ? '—' : number_format($viewerGuideStats['review'], 0, ',', '.') }}</strong><span>Wird geprüft</span></article>
                <article><strong>{{ $viewerGuideStats === null ? '—' : number_format($viewerGuideStats['published'], 0, ',', '.') }}</strong><span>Veröffentlicht</span></article>
            </div>
            @auth
                <a href="{{ route('guides.mine') }}">Verwalten <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
            @else
                <a href="{{ route('login') }}">Anmelden <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
            @endauth
        </section>
    </aside>
</section>
@endsection
