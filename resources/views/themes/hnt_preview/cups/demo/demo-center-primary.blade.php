<header class="cups-center-head">
<div class="cups-center-title">
<span>COMMUNITY CUPS</span>
<h2 id="cupsPanelTitle">Alle Cups</h2>
</div>
<nav aria-label="Cup Filter" class="cups-tabs" role="tablist">
<button class="active" data-cups-tab="all" data-title="Alle Cups" type="button">Alle</button>
<button data-cups-tab="active" data-title="Aktive Cups" type="button">Aktiv</button>
<button data-cups-tab="planned" data-title="Geplante Cups" type="button">Geplant</button>
<button data-cups-tab="finished" data-title="Vergangene Cups" type="button">Beendet</button>
<button data-cups-tab="mine" data-title="Meine Cups" type="button">Meine</button>
</nav>
</header>
<section class="cups-filter-row">
<label class="cups-search">
<svg><use href="#i-search"></use></svg>
<input id="cupSearch" placeholder="Cups durchsuchen" type="search"/>
</label>
<select aria-label="Plattform filtern" id="cupPlatform">
<option value="all">Alle Plattformen</option>
<option value="console">PS5 / Xbox</option>
<option value="ps5">PlayStation 5</option>
<option value="xbox">Xbox</option>
<option value="pc">PC</option>
</select>
<button id="resetCupFilters" type="button">
<svg><use href="#i-sliders"></use></svg>
              Filter zurücksetzen
            </button>
</section>
<section class="cups-featured" data-cup-item="" data-mine="true" data-platform="console" data-search="summer hunt trio console ps5 xbox" data-status="active">
<div class="cups-featured-cover">
<img alt="Summer Hunt" src="{{ asset('assets/themes/hnt_preview/dashboard-cups/assets/summer-hunt-cup-cover.svg') }}"/>
<span>FEATURED CUP</span>
</div>
<div class="cups-featured-copy">
<div class="cups-card-status">
<span class="active"><i></i>Anmeldung geöffnet</span>
<span>Trio</span>
<span>PS5 / Xbox</span>
</div>
<h3>Summer Hunt</h3>
<p>
                Feste Dreierteams treten auf Konsole gegeneinander an. Gewertet werden
                die erste erfolgreiche Trophäen-Extraktion und bestätigte Hunter-Kills.
              </p>
<div class="cups-featured-stats">
<span><strong>16 / 20</strong><small>Teams</small></span>
<span><strong>2T 08h</strong><small>bis zum Start</small></span>
<span><strong>7 / 48</strong><small>Einreichungen</small></span>
</div>
<div class="cups-featured-actions">
<a href="{{ $demoCupUrl }}">Cup ansehen <svg><use href="#i-arrow"></use></svg></a>
<button data-toast="Cup wurde gespeichert" type="button"><svg><use href="#i-bookmark"></use></svg></button>
</div>
</div>
</section>
<section class="cups-section">
<header>
<div>
<span>ALS NÄCHSTES</span>
<h3>Kommende Highlights</h3>
</div>
<small>2 geplant</small>
</header>
<div class="cups-highlight-grid">
<article class="cup-highlight-card blood" data-cup-item="" data-mine="false" data-platform="console" data-search="bayou blood cup solo console" data-status="planned">
<div class="cup-highlight-art">
<span>BAYOU BLOOD</span>
<strong>SOLO CUP</strong>
<small>August 2026</small>
</div>
<div class="cup-highlight-content">
<span class="planned">Geplant</span>
<h4>Bayou Blood Cup</h4>
<p>Solo-Leaderboard mit festem Loadout und manueller Score-Prüfung.</p>
<div><span>Solo</span><span>Konsole</span><span>64 Plätze</span></div>
<button data-toast="Benachrichtigung aktiviert" type="button">Erinnern</button>
</div>
</article>
<article class="cup-highlight-card frost" data-cup-item="" data-mine="false" data-platform="pc" data-search="winter bayou trio pc cup" data-status="planned">
<div class="cup-highlight-art">
<span>WINTER BAYOU</span>
<strong>TRIO EVENT</strong>
<small>Dezember 2026</small>
</div>
<div class="cup-highlight-content">
<span class="planned">Geplant</span>
<h4>Winter Bayou</h4>
<p>Drei Abende, drei Maps und ein gemeinsames Team-Leaderboard.</p>
<div><span>Trio</span><span>PC</span><span>20 Teams</span></div>
<button data-toast="Benachrichtigung aktiviert" type="button">Erinnern</button>
</div>
</article>
</div>
</section>
