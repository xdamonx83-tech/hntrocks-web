@extends('admin.layouts.app')

@section('title', 'Guides · Admin')
@section('admin_heading', 'Guides')

@section('content')
    <section class="hh-page-header">
        <div>
            <p class="hh-kicker">Community Moderation</p>
            <h1>Guide-Moderation</h1>
            <p>Community-Guides prüfen, freigeben, Änderungen anfordern, ablehnen und veröffentlichte Guides verwalten.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>{{ $stats['pending_review'] ?? 0 }}</strong>
            <span>zur Prüfung</span>
        </div>
    </section>

    @if(session('status'))
        <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="hh-alert hh-alert-danger">
            <strong>Bitte Eingaben prüfen.</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="hh-admin-stat-grid">
        <article class="hh-card hh-card-compact"><strong>{{ $stats['pending_review'] ?? 0 }}</strong><span>Zur Prüfung</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['published'] ?? 0 }}</strong><span>Veröffentlicht</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['changes_requested'] ?? 0 }}</strong><span>Änderungen</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['rejected'] ?? 0 }}</strong><span>Abgelehnt</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['draft'] ?? 0 }}</strong><span>Entwürfe</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['archived'] ?? 0 }}</strong><span>Archiviert</span></article>
    </section>

    <section class="hh-card hh-card-compact hh-filter-card">
        <form class="hh-admin-filter" method="get" action="{{ route('admin.guides.index') }}">
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>
                            {{ $label }} ({{ $stats[$value] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="q">Suche</label>
                <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Titel, Autor oder Slug">
            </div>
            <button class="hh-primary-button" type="submit">Filtern</button>
            @if($status !== 'pending_review' || $search !== '')
                <a class="hh-secondary-button" href="{{ route('admin.guides.index') }}">Zurücksetzen</a>
            @endif
        </form>
    </section>

    <section class="hh-admin-report-list">
        @forelse($guides as $guide)
            @php
                $revision = $guide->workingRevision ?: $guide->publishedRevision;
                $authorName = $guide->author?->name ?: ($guide->author?->username ?: 'Unbekannt');
                $category = $revision?->category?->label(app()->getLocale() === 'en' ? 'en' : 'de');
            @endphp
            <article class="hh-card hh-card-compact">
                <div class="hh-admin-report-head">
                    <div>
                        <p class="hh-kicker">
                            {{ $statuses[$guide->status] ?? $guide->status }}
                            @if($guide->is_featured)
                                · Featured
                            @endif
                            @if($revision)
                                · Revision {{ $revision->version }}
                            @endif
                        </p>
                        <h2>{{ $revision?->title ?: 'Guide ohne Titel' }}</h2>
                        <p>{{ $revision?->summary ?: 'Noch keine Zusammenfassung vorhanden.' }}</p>
                        <div class="hh-admin-report-context">
                            <span>Autor: {{ $authorName }}</span>
                            @if($guide->author?->username)
                                <span>@{{ $guide->author->username }}</span>
                            @endif
                            @if($category)
                                <span>{{ $category }}</span>
                            @endif
                            @if($revision?->language)
                                <span>{{ strtoupper($revision->language) }}</span>
                            @endif
                            @if($revision?->difficulty)
                                <span>{{ ucfirst($revision->difficulty) }}</span>
                            @endif
                            @if($revision?->platform)
                                <span>{{ strtoupper($revision->platform) }}</span>
                            @endif
                            @if($revision?->submitted_at)
                                <span>Eingereicht {{ $revision->submitted_at->format('d.m.Y H:i') }}</span>
                            @else
                                <span>Aktualisiert {{ $guide->updated_at?->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="hh-admin-actions">
                        <a class="hh-primary-button" href="{{ route('admin.guides.show', $guide) }}">
                            {{ $guide->status === 'pending_review' ? 'Jetzt prüfen' : 'Öffnen' }}
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <section class="hh-card hh-card-compact">
                <p class="hh-muted">Für diesen Filter wurden keine Guides gefunden.</p>
            </section>
        @endforelse
    </section>

    <div class="hh-pagination">
        {{ $guides->links() }}
    </div>
@endsection
