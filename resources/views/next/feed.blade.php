<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Feed – HNT.ROCKS Next</title>
    @vite(['resources/css/next/feed.css', 'resources/js/next/feed.js'], 'build/next')
</head>
<body class="hnt-next">
    <aside class="next-rail" aria-label="Hauptnavigation">
        <a class="next-logo" href="{{ route('next.feed') }}" aria-label="HNT.ROCKS">HR:<b>\</b></a>
        <div class="next-rail-links">
            <a class="is-active" href="{{ route('next.feed') }}" aria-current="page" title="Feed">⌂</a>
            <a href="{{ url('/maps') }}" title="Maps">▦</a>
            <a href="{{ url('/guides') }}" title="Guides">▤</a>
            <a href="{{ url('/cups') }}" title="Cups">♜</a>
        </div>
    </aside>

    <div class="next-shell">
        <header class="next-topbar">
            <nav aria-label="Schnellnavigation" class="next-top-links">
                <a href="{{ route('next.feed') }}" class="is-active">⌂</a>
                <a href="{{ url('/maps') }}">▦</a>
                <a href="{{ url('/guides') }}">▤</a>
            </nav>
            <div class="next-status"><span class="next-dot"></span> HNT.ROCKS <span class="next-divider"></span> NEXT PREVIEW</div>
            <div class="next-top-right">
                <span class="next-preview-pill">ENTWICKLUNGSVORSCHAU</span>
                <span class="next-avatar" aria-label="Angemeldet als {{ $viewer->name }}">{{ mb_strtoupper(mb_substr($viewer->name, 0, 1)) }}</span>
            </div>
        </header>

        <main class="next-content" id="nextMain">
            <header class="next-page-title">
                <div class="next-breadcrumb"><span class="next-dot" aria-hidden="true"></span> HNT.ROCKS / FEED / NEXT</div>
                <h1>Dein Bayou<span>.</span></h1>
            </header>

            <div class="next-columns">
                <aside class="next-sidebar-left">
                    <section class="next-widget">
                        <div class="next-overline">DEIN PROFIL</div>
                        <div class="next-person"><span class="next-avatar next-avatar-lg">{{ mb_strtoupper(mb_substr($viewer->name, 0, 1)) }}</span><span><strong>{{ $viewer->name }}</strong><small>Hunter</small></span></div>
                        <p>Dies ist die erste Laravel-/Vite-Anbindung unseres neuen Designs.</p>
                        <a href="{{ route('feed.index') }}" class="next-link">Zum bisherigen Feed →</a>
                    </section>
                    <section class="next-widget">
                        <div class="next-overline">MIGRATION</div>
                        <h2>Live-Daten, neue Ansicht.</h2>
                        <p>Beiträge kommen aus dem vorhandenen Laravel-Feed. Medien, Aktionen und die vollständigen HNTv3-Komponenten folgen als Nächstes.</p>
                    </section>
                </aside>

                <section class="next-feed" aria-label="Beiträge" id="nextFeed">
                    <div class="next-composer">
                        <span class="next-avatar">{{ mb_strtoupper(mb_substr($viewer->name, 0, 1)) }}</span>
                        <div>
                            <strong>Was gibt es Neues, {{ $viewer->name }}?</strong>
                            <p>Posten ist in dieser ersten Vorschau noch nicht freigeschaltet.</p>
                        </div>
                    </div>

                    <nav class="next-filters" aria-label="Feed-Filter">
                        @foreach(['all'=>'Alle Beiträge','mentions'=>'Erwähnungen','friends'=>'Freunde','teams'=>'Teams','media'=>'Medien'] as $id => $label)
                            <a href="{{ route('next.feed', $id === 'all' ? [] : ['filter'=>$id]) }}" @class(['is-active'=>$feedFilter === $id]) @if($feedFilter === $id) aria-current="page" @endif>{{ $label }}</a>
                        @endforeach
                    </nav>

                    <div class="next-feed-list" id="nextFeedList">
                        @forelse($posts as $post)
                            <article class="next-post" data-next-feed-post="{{ $post->id }}">
                                <div class="next-post-heading">
                                    <span class="next-avatar">{{ mb_strtoupper(mb_substr($post->user?->name ?? 'H', 0, 1)) }}</span>
                                    <div class="next-author">
                                        <strong>{{ $post->user?->name ?? 'Hunter' }}</strong>
                                        <span>{{ $post->created_at?->diffForHumans() }} @if($post->team) · {{ $post->team->name }} @endif</span>
                                    </div>
                                    @if($post->is_pinned)<span class="next-pin">ANGEHEFTET</span>@endif
                                </div>

                                @if($post->body)
                                    <p class="next-post-body">{{ $post->body }}</p>
                                @endif
                                @if($post->shared_post_id)
                                    <div class="next-post-meta">↗ Geteilter Beitrag</div>
                                @endif
                                @if($post->media?->count())
                                    <div class="next-post-media-notice">{{ $post->media->count() }} Medienanhang/Medienanhänge · Viewer folgt in der nächsten Integrationsstufe</div>
                                @endif

                                <div class="next-post-stats">
                                    <span>{{ number_format($post->reactions_count ?? 0, 0, ',', '.') }} Reaktionen</span>
                                    <span>{{ number_format($post->comments_count ?? 0, 0, ',', '.') }} Kommentare</span>
                                </div>
                                <div class="next-post-actions">
                                    <span>♡ Gefällt mir</span>
                                    <span>◌ Kommentieren</span>
                                    <a href="{{ route('feed.show', $post) }}">Beitrag öffnen ↗</a>
                                </div>
                            </article>
                        @empty
                            <div class="next-empty">Keine Beiträge für diesen Filter vorhanden.</div>
                        @endforelse
                    </div>

                    @if($hasMore && $nextPage <= 50)
                        <a class="next-load-more" id="nextLoadMore" href="{{ route('next.feed', array_filter(['filter'=>$feedFilter === 'all' ? null : $feedFilter,'page'=>$nextPage])) }}">Weitere Beiträge laden ↓</a>
                    @endif
                </section>

                <aside class="next-sidebar-right">
                    <section class="next-widget">
                        <div class="next-overline">NEXT / STATUS</div>
                        <h2>Feed-Integration</h2>
                        <div class="next-status-row"><span>Laravel-Daten</span><b class="is-ok">VERBUNDEN</b></div>
                        <div class="next-status-row"><span>Blade-Rendering</span><b class="is-ok">AKTIV</b></div>
                        <div class="next-status-row"><span>Vite</span><b class="is-ok">AKTIV</b></div>
                        <div class="next-status-row"><span>Schreibaktionen</span><b>NOCH AUS</b></div>
                        <div class="next-status-row"><span>Neue Medienansicht</span><b>FOLGT</b></div>
                    </section>
                </aside>
            </div>
        </main>
    </div>
</body>
</html>
