@extends('admin.layouts.app')

@section('title', 'News-Zentrale · Admin')
@section('admin_heading', 'News-Zentrale')

@section('content')
    <section class="hh-page-header">
        <div>
            <p class="hh-kicker">HuntNews & HNT.ROCKS</p>
            <h1>News für den Feed</h1>
            <p>Veröffentliche eigene Plattform-Updates als HNT.ROCKS News oder offizielle Spielmeldungen als HuntNews. Beide Varianten erscheinen als normaler Feed-Post mit einer festen News-Schablone.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>{{ $stats['manual'] }}</strong>
            <span>News-Karten</span>
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
        <article class="hh-card hh-card-compact"><strong>{{ $stats['manual'] }}</strong><span>News-Karten</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['discovered'] }}</strong><span>Entdeckt</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['posted'] }}</strong><span>HuntNews gepostet</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['errors'] }}</strong><span>Fehler</span></article>
    </section>

    <section class="hh-card">
        <div class="hh-card-title-row">
            <div>
                <p class="hh-kicker">Manuell veröffentlichen</p>
                <h2>News-Post erstellen</h2>
                <p class="hh-muted">Der Beitragstext erscheint oberhalb der festen News-Karte. Die Stichpunkte bilden die rechte Seite der Schablone.</p>
            </div>
        </div>

        <form method="post" action="{{ route('admin.hunt-news.sync') }}" class="hh-admin-form-wide hh-section-space">
            @csrf
            <input type="hidden" name="action" value="manual_publish">

            <div class="hh-admin-grid">
                <div class="hh-admin-form-wide">
                    <div>
                        <label for="publisher">Absender</label>
                        <select id="publisher" name="publisher" required>
                            @foreach($publishers as $value => $label)
                                <option value="{{ $value }}" @selected(old('publisher', 'hnt_rocks') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="badge">Kennzeichnung im Feed</label>
                        <select id="badge" name="badge" required>
                            @foreach(['Update', 'News', 'Official', 'Event', 'Patch Notes'] as $badge)
                                <option value="{{ $badge }}" @selected(old('badge', 'Update') === $badge)>{{ $badge }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="kicker">Kleine Überschrift</label>
                        <input id="kicker" name="kicker" maxlength="80" value="{{ old('kicker') }}" placeholder="Wird automatisch aus dem Absender gesetzt">
                    </div>

                    <div>
                        <label for="headline">Titel der News-Karte</label>
                        <input id="headline" name="headline" maxlength="190" value="{{ old('headline') }}" placeholder="Community Update 2.4" required>
                    </div>
                </div>

                <div class="hh-admin-form-wide">
                    <div>
                        <label for="body">Beitragstext oberhalb der Karte</label>
                        <textarea id="body" name="body" maxlength="5000" placeholder="Das Community-Update ist live. Die wichtigsten Änderungen betreffen ..." required>{{ old('body') }}</textarea>
                    </div>

                    @for($index = 0; $index < 5; $index++)
                        <div>
                            <label for="highlight_{{ $index }}">Stichpunkt {{ $index + 1 }}{{ $index > 2 ? ' · optional' : '' }}</label>
                            <input id="highlight_{{ $index }}" name="highlights[]" maxlength="180" value="{{ old('highlights.'.$index) }}" placeholder="{{ $index === 0 ? 'Schnellere LFG-Suche' : ($index === 1 ? 'Neue Moment-Reaktionen' : ($index === 2 ? 'Verbesserte Benachrichtigungen' : 'Weiterer Punkt')) }}" {{ $index === 0 ? 'required' : '' }}>
                        </div>
                    @endfor

                    <label class="hh-checkline">
                        <input type="checkbox" name="pin" value="1" @checked(old('pin'))>
                        <span>Post oben im Feed anpinnen</span>
                    </label>
                </div>
            </div>

            <div class="hh-admin-actions hh-admin-form-submit">
                <button class="hh-primary-button" type="submit">Im Feed veröffentlichen</button>
            </div>
        </form>
    </section>

    @if($recentNewsCards->isNotEmpty())
        <section class="hh-card hh-section-space">
            <div class="hh-card-title-row">
                <div>
                    <h2>Zuletzt veröffentlichte News</h2>
                    <p class="hh-muted">Die letzten strukturierten News-Posts aus beiden Absendern.</p>
                </div>
            </div>

            <div class="hh-admin-list">
                @foreach($recentNewsCards as $newsCard)
                    <div>
                        <strong>{{ $newsCard->publisherName() }} · {{ $newsCard->headline }}</strong>
                        <span>{{ $newsCard->badge }} · {{ $newsCard->created_at->diffForHumans() }}</span>
                        @if($newsCard->feedPost)
                            <a class="hh-link-button" href="{{ route('feed.show', $newsCard->feedPost) }}" target="_blank" rel="noopener">Feed-Post öffnen</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <p class="hh-kicker">Offizielle Hunt-Quelle</p>
                <h2>Automatischer Abgleich</h2>
                <p class="hh-muted">Quelle: <strong>{{ $sourceUrl }}</strong> · HuntNews-User-ID: <strong>{{ $huntNewsUserId }}</strong> · Auto-Posting ab: <strong>{{ $autoPublishFrom }}</strong></p>
            </div>
            <form method="post" action="{{ route('admin.hunt-news.sync') }}" class="hh-admin-actions">
                @csrf
                <input type="hidden" name="limit" value="16">
                <button class="hh-primary-button" type="submit">Jetzt prüfen</button>
            </form>
        </div>
        <p class="hh-muted">Neue offizielle Meldungen werden weiterhin automatisch erkannt. Neu veröffentlichte HuntNews-Posts erhalten ebenfalls die strukturierte News-Schablone.</p>
    </section>

    <section class="hh-card hh-card-compact hh-filter-card">
        <form class="hh-admin-filter" method="get" action="{{ route('admin.hunt-news.index') }}">
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Alle</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="hh-primary-button" type="submit">Filter</button>
        </form>
    </section>

    <section class="hh-admin-report-list">
        @forelse($items as $item)
            <article class="hh-card hh-card-compact">
                <div class="hh-admin-report-head">
                    <div>
                        <p class="hh-kicker">
                            {{ $statuses[$item->status] ?? $item->status }}
                            @if($item->auto_publish_eligible)
                                · Auto-berechtigt
                            @endif
                        </p>
                        <h2>{{ $item->displayTitle() }}</h2>
                        @if($item->excerpt)
                            <p>{{ $item->excerpt }}</p>
                        @else
                            <p class="hh-muted">Kein Auszug erkannt. Beim Posten wird nur Titel und Quellenlink verwendet.</p>
                        @endif
                        <div class="hh-admin-report-context">
                            <span>{{ $item->source_published_at?->format('d.m.Y H:i') ?? 'Datum unbekannt' }}</span>
                            <span>{{ $item->source_domain }}</span>
                            @if($item->feed_post_id)
                                <span>Feed-Post #{{ $item->feed_post_id }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="hh-admin-actions">
                        <a class="hh-secondary-button" href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer nofollow">Quelle öffnen</a>
                        @if($item->outboundLink)
                            <a class="hh-link-button" href="{{ $item->outboundLink->publicUrl() }}" target="_blank" rel="noopener">HNT-Link</a>
                        @endif
                    </div>
                </div>

                @if($item->error_message)
                    <div class="hh-alert hh-alert-danger">{{ $item->error_message }}</div>
                @endif

                <div class="hh-admin-outbound-copy">
                    <span>Quelle</span>
                    <code>{{ $item->source_url }}</code>
                </div>

                <div class="hh-admin-actions hh-section-space">
                    @if(! $item->isPosted())
                        <form method="post" action="{{ route('admin.hunt-news.publish', $item) }}">
                            @csrf
                            <button class="hh-primary-button" type="submit">Als HuntNews posten</button>
                        </form>
                        <form method="post" action="{{ route('admin.hunt-news.skip', $item) }}">
                            @csrf
                            <button class="hh-secondary-button" type="submit">Überspringen</button>
                        </form>
                    @elseif($item->feedPost)
                        <a class="hh-primary-button" href="{{ route('feed.show', $item->feedPost) }}" target="_blank" rel="noopener">Feed-Post öffnen</a>
                    @else
                        <span class="hh-muted">Feed-Post wurde nicht gefunden.</span>
                    @endif
                </div>
            </article>
        @empty
            <section class="hh-card hh-card-compact">
                <p class="hh-muted">Noch keine offiziellen Hunt-News entdeckt. Starte oben den ersten Abgleich.</p>
            </section>
        @endforelse
    </section>

    <div class="hh-pagination">
        {{ $items->links() }}
    </div>
@endsection
