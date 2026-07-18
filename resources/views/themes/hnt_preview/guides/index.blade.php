@extends('themes.hnt_preview.guides.layout')

@section('robots', request()->query() ? 'noindex,follow' : 'index,follow')
@section('canonical', route('guides.index'))
@section('body_class', 'guides-demo-index')

@push('head')
<link href="{{ asset('assets/themes/hnt_preview/guides/guides-index-demo.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides-index-demo.css')) ?: time() }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('assets/themes/hnt_preview/guides/guides-index-demo.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides-index-demo.js')) ?: time() }}" defer></script>
@endpush

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
    <div class="guides-hero-stats" aria-label="Demo-Statistik">
        <article><strong>184</strong><span>veröffentlichte Guides</span></article>
        <article><strong>26</strong><span>aktive Autoren</span></article>
        <article><strong>1.248</strong><span>Hilfreich-Markierungen</span></article>
    </div>
</section>

<section class="guides-overview-layout">
    <aside class="guides-category-column">
        <section class="guides-side-card">
            <header>
                <div><span>KATEGORIEN</span><h2>Wissen entdecken</h2></div>
                <b>8</b>
            </header>
            <div class="guide-category-list">
                <button class="active" data-guide-category="all" type="button"><span><i class="ph ph-books"></i></span><strong>Alle Guides</strong><b>184</b></button>
                <button data-guide-category="beginner" type="button"><span><i class="ph ph-star"></i></span><strong>Anfänger</strong><b>42</b></button>
                <button data-guide-category="loadouts" type="button"><span><i class="ph ph-sliders-horizontal"></i></span><strong>Waffen &amp; Loadouts</strong><b>31</b></button>
                <button data-guide-category="traits" type="button"><span><i class="ph ph-user"></i></span><strong>Hunter &amp; Traits</strong><b>25</b></button>
                <button data-guide-category="maps" type="button"><span><i class="ph ph-map-trifold"></i></span><strong>Karten</strong><b>22</b></button>
                <button data-guide-category="bosses" type="button"><span><i class="ph ph-skull"></i></span><strong>Bosse</strong><b>18</b></button>
                <button data-guide-category="pvp" type="button"><span><i class="ph ph-crosshair"></i></span><strong>PvP</strong><b>27</b></button>
                <button data-guide-category="teams" type="button"><span><i class="ph ph-users"></i></span><strong>Teams</strong><b>14</b></button>
                <button data-guide-category="events" type="button"><span><i class="ph ph-calendar-dots"></i></span><strong>Events</strong><b>5</b></button>
            </div>
        </section>

        <section class="guides-side-card guide-author-card">
            <span>GUIDE-REPUTATION</span>
            <h2>Wissen wird sichtbar</h2>
            <div class="guide-reputation-ring"><strong>340</strong><span>Reputation</span></div>
            <p>Hilfreiche, freigegebene Guides stärken die Reputation eines Autors.</p>
            @auth
                <a href="{{ route('guides.mine') }}">Meine Reputation <i class="ph ph-arrow-up-right"></i></a>
            @else
                <a href="{{ route('login') }}">Anmelden <i class="ph ph-arrow-up-right"></i></a>
            @endauth
        </section>
    </aside>

    <main class="guides-main-column">
        <section class="guides-toolbar" aria-label="Demo-Guidefilter">
            <label class="guide-search" for="guideSearch"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input id="guideSearch" type="search" placeholder="Guides, Themen oder Autoren suchen"></label>
            <select id="guideLanguage" aria-label="Sprache"><option value="all">Alle Sprachen</option><option value="de">Deutsch</option><option value="en">Englisch</option></select>
            <select id="guidePlatform" aria-label="Plattform"><option value="all">Alle Plattformen</option><option value="pc">PC</option><option value="console">PlayStation / Xbox</option></select>
            <select id="guideDifficulty" aria-label="Schwierigkeit"><option value="all">Alle Stufen</option><option value="beginner">Anfänger</option><option value="advanced">Fortgeschritten</option><option value="expert">Experte</option></select>
            <select id="guideSort" aria-label="Sortierung"><option value="new">Neu</option><option value="helpful">Hilfreich</option><option value="popular">Beliebt</option></select>
        </section>

        <section class="guide-featured">
            <div class="guide-demo-cover"><i class="ph ph-crosshair" aria-hidden="true"></i></div>
            <div>
                <span>HERVORGEHOBENER GUIDE</span>
                <h2>Budget-Loadouts, die wirklich funktionieren</h2>
                <p>Drei günstige Builds für aggressive Runden – inklusive Alternativen für PS5, Xbox und PC.</p>
                <div><span class="guide-chip accent">Waffen &amp; Loadouts</span><span class="guide-chip">Anfänger</span><span class="guide-chip">Alle Plattformen</span></div>
                <footer>
                    <div><div class="guide-demo-avatar" aria-hidden="true">EW</div><span><strong>Erica Wyatt</strong><small>Guide-Autorin · 684 hilfreich</small></span></div>
                    <a data-demo-guide-link href="#">Guide öffnen <i class="ph ph-arrow-up-right"></i></a>
                </footer>
            </div>
        </section>

        <section class="guides-section-head">
            <div><span>COMMUNITY GUIDES</span><h2>Alle Guides</h2></div>
            <strong><b id="guideVisibleCount">5</b> sichtbar</strong>
        </section>

        <section class="guide-grid" id="guideGrid">
            <article class="guide-card" data-guide-card data-category="maps" data-language="de" data-platform="all" data-difficulty="advanced" data-helpful="512" data-date="5" data-popular="91" data-search="Stillwater Bayou sichere Rotationen für Trios Jonathan Kelly Karten">
                <a class="guide-card-cover" data-demo-guide-link href="#"><div class="guide-demo-cover tone-map"><i class="ph ph-map-trifold"></i></div><span>Karten</span></a>
                <div class="guide-card-body">
                    <div class="guide-card-author"><div class="guide-demo-avatar">JK</div><div><strong>Jonathan Kelly</strong><small>vor 5 Tagen · geprüft</small></div><button data-guide-save aria-label="Guide speichern" type="button"><i class="ph ph-bookmark-simple"></i></button></div>
                    <a data-demo-guide-link href="#"><h3>Stillwater Bayou: sichere Rotationen für Trios</h3></a>
                    <p>Routen, Rückzugswege und sichere Übergänge für Teams, die nicht in jedem Compound festlaufen wollen.</p>
                    <div class="guide-card-tags"><span class="guide-chip">Fortgeschritten</span><span class="guide-chip">Alle Plattformen</span><span class="guide-chip">Deutsch</span></div>
                    <footer><span><i class="ph ph-heart"></i> <b>512</b> hilfreich</span><span><i class="ph ph-eye"></i> 9,8 Tsd.</span><a data-demo-guide-link href="#">Lesen <i class="ph ph-arrow-up-right"></i></a></footer>
                </div>
            </article>

            <article class="guide-card" data-guide-card data-category="traits" data-language="de" data-platform="pc" data-difficulty="expert" data-helpful="438" data-date="10" data-popular="86" data-search="Trait Synergien für aggressive Hunter Sarah Page Hunter Traits">
                <a class="guide-card-cover" data-demo-guide-link href="#"><div class="guide-demo-cover tone-traits"><i class="ph ph-person-simple-run"></i></div><span>Hunter &amp; Traits</span></a>
                <div class="guide-card-body">
                    <div class="guide-card-author"><div class="guide-demo-avatar">SP</div><div><strong>Sarah Page</strong><small>vor 10 Tagen · geprüft</small></div><button data-guide-save aria-label="Guide speichern" type="button"><i class="ph ph-bookmark-simple"></i></button></div>
                    <a data-demo-guide-link href="#"><h3>Trait-Synergien für aggressive Hunter</h3></a>
                    <p>Kombinationen für Pushes, schnelle Revives und kontrollierten Druck im Lair.</p>
                    <div class="guide-card-tags"><span class="guide-chip">Experte</span><span class="guide-chip">PC</span><span class="guide-chip">Deutsch</span></div>
                    <footer><span><i class="ph ph-heart"></i> <b>438</b> hilfreich</span><span><i class="ph ph-eye"></i> 7,2 Tsd.</span><a data-demo-guide-link href="#">Lesen <i class="ph ph-arrow-up-right"></i></a></footer>
                </div>
            </article>

            <article class="guide-card" data-guide-card data-category="bosses" data-language="de" data-platform="all" data-difficulty="advanced" data-helpful="377" data-date="2" data-popular="94" data-search="Boss Lair verteidigen ohne festzusitzen Mara Voss Bosse">
                <a class="guide-card-cover" data-demo-guide-link href="#"><div class="guide-demo-cover tone-boss"><i class="ph ph-skull"></i></div><span>Bosse</span></a>
                <div class="guide-card-body">
                    <div class="guide-card-author"><div class="guide-demo-avatar">MV</div><div><strong>Mara Voss</strong><small>vor 2 Tagen · geprüft</small></div><button data-guide-save aria-label="Guide speichern" type="button"><i class="ph ph-bookmark-simple"></i></button></div>
                    <a data-demo-guide-link href="#"><h3>Boss-Lair verteidigen, ohne festzusitzen</h3></a>
                    <p>Positionen, Rotationen und Ausbruchsmöglichkeiten für Teams mit Bounty.</p>
                    <div class="guide-card-tags"><span class="guide-chip">Fortgeschritten</span><span class="guide-chip">Alle Plattformen</span><span class="guide-chip">Deutsch</span></div>
                    <footer><span><i class="ph ph-heart"></i> <b>377</b> hilfreich</span><span><i class="ph ph-eye"></i> 6,6 Tsd.</span><a data-demo-guide-link href="#">Lesen <i class="ph ph-arrow-up-right"></i></a></footer>
                </div>
            </article>

            <article class="guide-card" data-guide-card data-category="pvp" data-language="de" data-platform="console" data-difficulty="beginner" data-helpful="301" data-date="14" data-popular="78" data-search="Aim Grundlagen auf Konsole Katy Fuller PvP">
                <a class="guide-card-cover" data-demo-guide-link href="#"><div class="guide-demo-cover tone-console"><i class="ph ph-game-controller"></i></div><span>PvP</span></a>
                <div class="guide-card-body">
                    <div class="guide-card-author"><div class="guide-demo-avatar">KF</div><div><strong>Katy Fuller</strong><small>vor 14 Tagen · geprüft</small></div><button data-guide-save aria-label="Guide speichern" type="button"><i class="ph ph-bookmark-simple"></i></button></div>
                    <a data-demo-guide-link href="#"><h3>Aim-Grundlagen auf Konsole</h3></a>
                    <p>Empfindlichkeit, Aim-Assist, Crosshair Placement und tägliche Übungen für PlayStation und Xbox.</p>
                    <div class="guide-card-tags"><span class="guide-chip">Anfänger</span><span class="guide-chip">PlayStation / Xbox</span><span class="guide-chip">Deutsch</span></div>
                    <footer><span><i class="ph ph-heart"></i> <b>301</b> hilfreich</span><span><i class="ph ph-eye"></i> 5,9 Tsd.</span><a data-demo-guide-link href="#">Lesen <i class="ph ph-arrow-up-right"></i></a></footer>
                </div>
            </article>

            <article class="guide-card" data-guide-card data-category="beginner" data-language="de" data-platform="all" data-difficulty="beginner" data-helpful="692" data-date="1" data-popular="98" data-search="Die ersten 10 Stunden im Bayou Valentina Anfänger">
                <a class="guide-card-cover" data-demo-guide-link href="#"><div class="guide-demo-cover tone-beginner"><i class="ph ph-compass"></i></div><span>Anfänger</span></a>
                <div class="guide-card-body">
                    <div class="guide-card-author"><div class="guide-demo-avatar">VA</div><div><strong>Valentina</strong><small>vor 1 Tag · geprüft</small></div><button data-guide-save aria-label="Guide speichern" type="button"><i class="ph ph-bookmark-simple"></i></button></div>
                    <a data-demo-guide-link href="#"><h3>Die ersten 10 Stunden im Bayou</h3></a>
                    <p>Die wichtigsten Systeme verständlich erklärt: Hinweise, Bosse, Extraktion und Economy.</p>
                    <div class="guide-card-tags"><span class="guide-chip">Anfänger</span><span class="guide-chip">Alle Plattformen</span><span class="guide-chip">Deutsch</span></div>
                    <footer><span><i class="ph ph-heart"></i> <b>692</b> hilfreich</span><span><i class="ph ph-eye"></i> 14,1 Tsd.</span><a data-demo-guide-link href="#">Lesen <i class="ph ph-arrow-up-right"></i></a></footer>
                </div>
            </article>

            <div class="guide-empty" id="guideEmpty"><i class="ph ph-magnifying-glass"></i><h3>Keine passenden Guides</h3><p>Ändere Suche oder Filter.</p></div>
        </section>
    </main>

    <aside class="guides-right-column">
        <section class="guides-side-card guide-create-widget">
            <span>DEIN WISSEN</span>
            <h2>Eigenen Guide erstellen</h2>
            <p>Block-Editor, automatische Entwürfe und Moderation vor der Veröffentlichung.</p>
            <div>
                <article><i class="ph ph-floppy-disk"></i><span><strong>Automatisch gespeichert</strong><small>Entwurf bleibt erhalten</small></span></article>
                <article><i class="ph ph-shield-check"></i><span><strong>Moderiert</strong><small>Nichts erscheint ungeprüft</small></span></article>
                <article><i class="ph ph-arrows-clockwise"></i><span><strong>Versioniert</strong><small>Öffentliche Version bleibt live</small></span></article>
            </div>
            @auth
                <form class="guide-create-form" action="{{ route('guides.store') }}" method="post">
                    @csrf
                    <button type="submit">Guide erstellen <i class="ph ph-arrow-up-right"></i></button>
                </form>
            @else
                <a href="{{ route('login') }}">Anmelden <i class="ph ph-arrow-up-right"></i></a>
            @endauth
        </section>

        <section class="guides-side-card guide-top-authors">
            <header><div><span>COMMUNITY-EXPERTEN</span><h2>Top Autoren</h2></div><a href="#guideGrid">Alle</a></header>
            <div>
                <article><b>1</b><div class="guide-demo-avatar">VA</div><span><strong>Valentina</strong><small>8 Guides · 1.406 hilfreich</small></span></article>
                <article><b>2</b><div class="guide-demo-avatar">EW</div><span><strong>Erica Wyatt</strong><small>6 Guides · 982 hilfreich</small></span></article>
                <article><b>3</b><div class="guide-demo-avatar">JK</div><span><strong>Jonathan Kelly</strong><small>5 Guides · 714 hilfreich</small></span></article>
            </div>
        </section>

        <section class="guides-side-card guide-status-widget">
            <span>DEINE GUIDES</span>
            <h2>Aktueller Status</h2>
            <div><article><strong>2</strong><span>Entwürfe</span></article><article><strong>1</strong><span>Wird geprüft</span></article><article><strong>1</strong><span>Veröffentlicht</span></article></div>
            @auth
                <a href="{{ route('guides.mine') }}">Verwalten <i class="ph ph-arrow-up-right"></i></a>
            @else
                <a href="{{ route('login') }}">Anmelden <i class="ph ph-arrow-up-right"></i></a>
            @endauth
        </section>
    </aside>
</section>
@endsection
