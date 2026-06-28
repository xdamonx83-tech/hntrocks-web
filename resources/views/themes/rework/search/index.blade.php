@extends('themes.rework.layouts.app')

@section('title', 'Suche · HNT.rocks')
@section('body_class', 'search-page')

@section('content')
@php
    $typeLabels = collect($searchTypes)->except('all');
@endphp

<section class="card search-hero-card">
    <div>
        <span class="search-eyebrow"><i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i> HNT Suche</span>
        <h1>Finde Hunter, Posts, Moments und mehr.</h1>
        <p>Durchsuche die wichtigsten Bereiche von HNT.rocks. Die Live-Suche im Header kommt danach als schneller Einstieg.</p>
    </div>
    <form action="{{ route('search.index') }}" class="search-page-form" method="get" role="search" autocomplete="off">
        <label class="sr-only" for="search-page-input">Suchbegriff</label>
        <i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i>
        <input id="search-page-input" name="q" placeholder="Spieler, Post, Moment, Map, Cup..." type="search" value="{{ $searchQuery }}" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" autofocus>
        <input name="type" type="hidden" value="{{ $activeType }}">
        <button type="submit">Suchen</button>
    </form>
</section>

<nav aria-label="Suchfilter" class="search-type-tabs">
@foreach($searchTypes as $key => $meta)
    <a @class(['active' => $activeType === $key]) href="{{ route('search.index', ['q' => $searchQuery, 'type' => $key]) }}">
        <i aria-hidden="true" class="ph {{ $meta['icon'] }} ph-icon"></i>{{ $meta['label'] }}
    </a>
@endforeach
</nav>

@if(mb_strlen($searchQuery) < 2)
    <section class="card search-empty-card">
        <i aria-hidden="true" class="ph ph-binoculars ph-icon"></i>
        <strong>Suchbegriff eingeben</strong>
        <p>Gib mindestens zwei Zeichen ein. Danach zeigen wir Treffer aus Spielern, Feed, Kommentaren, Moments, LFG, Maps und Cups.</p>
    </section>
@elseif($searchResultTotal < 1)
    <section class="card search-empty-card">
        <i aria-hidden="true" class="ph ph-skull ph-icon"></i>
        <strong>Keine Treffer</strong>
        <p>Für „{{ $searchQuery }}“ wurde nichts gefunden. Probiere einen kürzeren Begriff oder wechsle den Filter.</p>
    </section>
@else
    <div class="search-results-stack">
    @foreach($typeLabels as $key => $meta)
        @php $items = $searchResults[$key] ?? collect(); @endphp
        @if(($activeType === 'all' || $activeType === $key) && $items->isNotEmpty())
            <section class="card search-result-group">
                <header class="search-result-head">
                    <div>
                        <span><i aria-hidden="true" class="ph {{ $meta['icon'] }} ph-icon"></i>{{ $meta['label'] }}</span>
                        <h2>{{ $meta['label'] }} zu „{{ $searchQuery }}“</h2>
                    </div>
                    @if($activeType === 'all')
                        <a href="{{ route('search.index', ['q' => $searchQuery, 'type' => $key]) }}">Nur {{ $meta['label'] }}</a>
                    @endif
                </header>

                <div class="search-result-list">
                    @foreach($items as $item)
                        <a class="search-result-item" href="{{ $item['url'] }}">
                            <span class="search-result-media">
                                @if(! empty($item['image']))
                                    <img alt="" src="{{ $item['image'] }}">
                                @else
                                    <i aria-hidden="true" class="ph {{ $item['icon'] ?? $meta['icon'] }} ph-icon"></i>
                                @endif
                            </span>
                            <span class="search-result-copy">
                                <strong>{{ $item['title'] }}</strong>
                                <small>{{ $item['subtitle'] }}</small>
                                <em>{{ $item['text'] }}</em>
                                @if(! empty($item['meta']))
                                    <span class="search-result-meta">
                                        @foreach($item['meta'] as $badge)
                                            <b><i aria-hidden="true" class="ph {{ $badge['icon'] }} ph-icon"></i>{{ $badge['label'] }}</b>
                                        @endforeach
                                    </span>
                                @endif
                            </span>
                            <i aria-hidden="true" class="ph ph-arrow-right ph-icon search-result-arrow"></i>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
    </div>
@endif
@endsection
