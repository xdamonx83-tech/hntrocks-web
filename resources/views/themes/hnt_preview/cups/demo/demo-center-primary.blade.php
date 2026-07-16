<header class="cups-center-head">
<div class="cups-center-title">
<span>{{ __('hnt_cups_overview.community_cups') }}</span>
<h2 id="cupsPanelTitle">{{ __('hnt_cups_overview.all_cups') }}</h2>
</div>
<nav aria-label="{{ __('hnt_cups_overview.title') }}" class="cups-tabs" role="tablist">
<button class="active" data-cups-tab="all" data-title="{{ __('hnt_cups_overview.all_cups') }}" type="button">{{ __('hnt_cups_overview.tab_all') }}</button>
<button data-cups-tab="active" data-title="{{ __('hnt_cups_overview.active_cups') }}" type="button">{{ __('hnt_cups_overview.tab_active') }}</button>
<button data-cups-tab="planned" data-title="{{ __('hnt_cups_overview.planned') }}" type="button">{{ __('hnt_cups_overview.tab_planned') }}</button>
<button data-cups-tab="finished" data-title="{{ __('hnt_cups_overview.finished') }}" type="button">{{ __('hnt_cups_overview.tab_finished') }}</button>
<button data-cups-tab="mine" data-title="{{ __('hnt_cups_overview.my_cups') }}" type="button">{{ __('hnt_cups_overview.tab_mine') }}</button>
</nav>
</header>
<section class="cups-filter-row">
<label class="cups-search">
<svg><use href="#i-search"></use></svg>
<input id="cupSearch" placeholder="{{ __('hnt_cups_overview.search_placeholder') }}" type="search"/>
</label>
<select aria-label="{{ __('hnt_cups_overview.platforms') }}" id="cupPlatform">
<option value="all">{{ __('hnt_cups_overview.all_platforms') }}</option>
<option value="console">{{ __('hnt_cups_overview.console') }}</option>
<option value="ps5">PlayStation 5</option>
<option value="xbox">Xbox</option>
<option value="pc">PC</option>
</select>
<button id="resetCupFilters" type="button">
<svg><use href="#i-sliders"></use></svg>
{{ __('hnt_cups_overview.reset_filters') }}
</button>
</section>
@if($featuredCup)
<section class="cups-featured"
         data-cup-item=""
         data-mine="{{ $featuredMine ? 'true' : 'false' }}"
         data-platform="{{ $featuredPlatformKey }}"
         data-search="{{ \Illuminate\Support\Str::lower($featuredCup->title.' '.$featuredPlatforms.' '.$featuredCup->status) }}"
         data-status="{{ $featuredCup->status }}">
<div class="cups-featured-cover">
<img alt="{{ $featuredCup->title }}" src="{{ $featuredCup->coverUrl() }}"/>
<span>{{ __('hnt_cups_overview.featured') }}</span>
</div>
<div class="cups-featured-copy">
<div class="cups-card-status">
<span class="active"><i></i>{{ $featuredCup->isRegistrationOpen() ? __('hnt_cups_overview.registration_open') : $featuredCup->statusLabel() }}</span>
<span>{{ $featuredCup->isSoloLeaderboard() ? __('hnt_cups_overview.solo') : __('hnt_cups_overview.team_size', ['counter' => $featuredCup->team_size]) }}</span>
<span>{{ $featuredPlatforms }}</span>
</div>
<h3>{{ $featuredCup->title }}</h3>
<p>{{ \Illuminate\Support\Str::limit($featuredCup->displaySummary(), 260) }}</p>
<div class="cups-featured-stats">
<span>
<strong>{{ $formatCount((int) $featuredCup->active_teams_count) }}{{ $featuredLimit ? ' / '.$formatCount($featuredLimit) : '' }}</strong>
<small>{{ $featuredCup->isSoloLeaderboard() ? __('hnt_cups_overview.participants') : __('hnt_cups_overview.teams') }}</small>
</span>
<span><strong>{{ $featuredStart }}</strong><small>{{ __('hnt_cups_overview.until_start') }}</small></span>
<span><strong>{{ $formatCount((int) $featuredCup->submissions_count) }}</strong><small>{{ __('hnt_cups_overview.submissions') }}</small></span>
</div>
<div class="cups-featured-actions">
<a href="{{ route('cups.show', $featuredCup) }}">{{ __('hnt_cups_overview.view_cup') }} <svg><use href="#i-arrow"></use></svg></a>
<button data-toast="{{ __('hnt_cups_overview.view_cup') }}" type="button" aria-label="{{ __('hnt_cups_overview.view_cup') }}"><svg><use href="#i-bookmark"></use></svg></button>
</div>
</div>
</section>
@else
<section class="cups-featured">
<div class="cups-featured-cover">
<img alt="" src="{{ asset('assets/vikinger/img/default-cover.svg') }}"/>
<span>{{ __('hnt_cups_overview.featured') }}</span>
</div>
<div class="cups-featured-copy">
<h3>{{ __('hnt_cups_overview.no_current_cup') }}</h3>
<p>{{ __('hnt_cups_overview.no_current_cup_text') }}</p>
<div class="cups-featured-actions">
<a href="{{ route('cups.index', ['status' => 'active']) }}">{{ __('hnt_cups_overview.browse_cups') }} <svg><use href="#i-arrow"></use></svg></a>
</div>
</div>
</section>
@endif
<section class="cups-section">
<header>
<div>
<span>{{ __('hnt_cups_overview.next') }}</span>
<h3>{{ __('hnt_cups_overview.upcoming_highlights') }}</h3>
</div>
<small>2 {{ __('hnt_cups_overview.planned') }}</small>
</header>
<div class="cups-highlight-grid">
<article class="cup-highlight-card blood" data-cup-item="" data-mine="false" data-platform="console" data-search="bayou blood cup solo console" data-status="planned">
<div class="cup-highlight-art">
<span>BAYOU BLOOD</span>
<strong>SOLO CUP</strong>
<small>August 2026</small>
</div>
<div class="cup-highlight-content">
<span class="planned">{{ __('hnt_cups_overview.planned') }}</span>
<h4>Bayou Blood Cup</h4>
<p>Solo-Leaderboard mit festem Loadout und manueller Score-Prüfung.</p>
<div><span>{{ __('hnt_cups_overview.solo') }}</span><span>Konsole</span><span>64 Plätze</span></div>
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
<span class="planned">{{ __('hnt_cups_overview.planned') }}</span>
<h4>Winter Bayou</h4>
<p>Drei Abende, drei Maps und ein gemeinsames Team-Leaderboard.</p>
<div><span>Trio</span><span>PC</span><span>20 {{ __('hnt_cups_overview.teams') }}</span></div>
<button data-toast="Benachrichtigung aktiviert" type="button">Erinnern</button>
</div>
</article>
</div>
</section>
