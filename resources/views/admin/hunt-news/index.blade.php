@extends('admin.layouts.app')

@section('title', 'Hunt-News · Admin')
@section('admin_heading', 'Hunt-News')

@section('content')
    <section class="hh-page-header">
        <div>
            <p class="hh-kicker">Official News</p>
            <h1>Offizielle Hunt-News</h1>
            <p>News von der offiziellen Hunt: Showdown Website erkennen, vergangene Meldungen manuell freigeben und neue Meldungen automatisch als Feed-Post vom HuntNews-Account veröffentlichen.</p>
        </div>
        <div class="hh-page-header-meta">
            <strong>{{ $items->total() }}</strong>
            <span>News</span>
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
        <article class="hh-card hh-card-compact"><strong>{{ $stats['discovered'] }}</strong><span>Entdeckt</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['posted'] }}</strong><span>Gepostet</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['auto'] }}</strong><span>Auto-berechtigt</span></article>
        <article class="hh-card hh-card-compact"><strong>{{ $stats['errors'] }}</strong><span>Fehler</span></article>
    </section>

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <h2>Abgleich</h2>
                <p class="hh-muted">Quelle: <strong>{{ $sourceUrl }}</strong> · HuntNews-User-ID: <strong>{{ $huntNewsUserId }}</strong> · Auto-Posting ab: <strong>{{ $autoPublishFrom }}</strong></p>
            </div>
            <form method="post" action="{{ route('admin.hunt-news.sync') }}" class="hh-admin-actions">
                @csrf
                <input type="hidden" name="limit" value="16">
                <button class="hh-primary-button" type="submit">Jetzt prüfen</button>
            </form>
        </div>
        <p class="hh-muted">Neue News ab dem Auto-Datum werden beim Abgleich automatisch gepostet. Ältere News werden nur entdeckt und können hier manuell gepostet werden. Im Feed wird nur ein kurzer Auszug mit internem HNT-Link zur offiziellen Quelle veröffentlicht.</p>
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
