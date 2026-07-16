<header class="cups-center-head">
<div class="cups-center-title">
<span>{{ __('hnt_cups_overview.community_cups') }}</span>
<h2 id="cupsPanelTitle">{{ $panelTitle }}</h2>
</div>
<nav aria-label="{{ __('hnt_cups_overview.title') }}" class="cups-tabs" role="tablist">
<button class="{{ ! $mineFilter && $statusFilter === '' ? 'active' : '' }}" data-cups-tab="all" data-url="{{ $allCupsUrl }}" type="button">{{ __('hnt_cups_overview.tab_all') }}</button>
<button class="{{ ! $mineFilter && $statusFilter === 'active' ? 'active' : '' }}" data-cups-tab="active" data-url="{{ $statusUrl('active') }}" type="button">{{ __('hnt_cups_overview.tab_active') }}</button>
<button class="{{ ! $mineFilter && $statusFilter === 'planned' ? 'active' : '' }}" data-cups-tab="planned" data-url="{{ $statusUrl('planned') }}" type="button">{{ __('hnt_cups_overview.tab_planned') }}</button>
<button class="{{ ! $mineFilter && $statusFilter === 'finished' ? 'active' : '' }}" data-cups-tab="finished" data-url="{{ $statusUrl('finished') }}" type="button">{{ __('hnt_cups_overview.tab_finished') }}</button>
<button class="{{ $mineFilter ? 'active' : '' }}" data-cups-tab="mine" data-url="{{ $mineUrl }}" type="button">{{ __('hnt_cups_overview.tab_mine') }}</button>
</nav>
</header>
<form class="cups-filter-row" method="get" action="{{ route('cups.index') }}" data-cups-server-filter>
@if($statusFilter !== '')<input type="hidden" name="status" value="{{ $statusFilter }}"/>@endif
@if($mineFilter)<input type="hidden" name="mine" value="1"/>@endif
<label class="cups-search">
<svg><use href="#i-search"></use></svg>
<input id="cupSearch" name="q" value="{{ $searchFilter }}" placeholder="{{ __('hnt_cups_overview.search_placeholder') }}" type="search"/>
</label>
<select aria-label="{{ __('hnt_cups_overview.platforms') }}" id="cupPlatform" name="platform">
<option value="">{{ __('hnt_cups_overview.all_platforms') }}</option>
<option value="console" @selected($platformFilter === 'console')>{{ __('hnt_cups_overview.console') }}</option>
<option value="ps5" @selected($platformFilter === 'ps5')>PlayStation 5</option>
<option value="xbox" @selected($platformFilter === 'xbox')>Xbox</option>
<option value="pc" @selected($platformFilter === 'pc')>PC</option>
</select>
<button id="resetCupFilters" data-reset-url="{{ $resetUrl }}" type="button">
<svg><use href="#i-sliders"></use></svg>
{{ __('hnt_cups_overview.reset_filters') }}
</button>
</form>
@if($featuredCup)
<section class="cups-featured"
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
<span>{{ $cupModeLabel($featuredCup) }}</span>
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
<button data-url="{{ route('cups.show', $featuredCup) }}" type="button" aria-label="{{ __('hnt_cups_overview.view_cup') }}"><svg><use href="#i-bookmark"></use></svg></button>
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
<small>{{ __('hnt_cups_overview.planned_count', ['count' => $upcomingCups->count()]) }}</small>
</header>
@if($upcomingCups->isNotEmpty())
<div class="cups-highlight-grid">
@foreach($upcomingCups as $upcomingCup)
@php
    $upcomingPlatforms = $cupPlatformLabel($upcomingCup);
    $upcomingLimit = $upcomingCup->participantLimit();
    $upcomingCountLabel = $formatCount((int) $upcomingCup->active_teams_count)
        .($upcomingLimit ? ' / '.$formatCount($upcomingLimit) : '')
        .' '.($upcomingCup->isSoloLeaderboard() ? __('hnt_cups_overview.participants') : __('hnt_cups_overview.teams'));
@endphp
<article class="cup-highlight-card {{ $loop->first ? 'blood' : 'frost' }}"
         data-mine="{{ $viewerCupIds->contains((int) $upcomingCup->id) ? 'true' : 'false' }}"
         data-platform="{{ $cupPlatformKey($upcomingCup) }}"
         data-search="{{ \Illuminate\Support\Str::lower($upcomingCup->title.' '.$upcomingPlatforms.' '.$upcomingCup->status) }}"
         data-status="{{ $upcomingCup->status }}">
<div class="cup-highlight-art" style="background-image:url('{{ $upcomingCup->coverUrl() }}')">
<span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::limit($upcomingCup->title, 22, '')) }}</span>
<strong>{{ $cupModeLabel($upcomingCup) }}</strong>
<small>{{ $cupDateLabel($upcomingCup) }}</small>
</div>
<div class="cup-highlight-content">
<span class="planned">{{ $upcomingCup->statusLabel() }}</span>
<h4>{{ $upcomingCup->title }}</h4>
<p>{{ \Illuminate\Support\Str::limit($upcomingCup->displaySummary(), 118) }}</p>
<div><span>{{ $cupModeLabel($upcomingCup) }}</span><span>{{ $upcomingPlatforms }}</span><span>{{ $upcomingCountLabel }}</span></div>
<button data-url="{{ route('cups.show', $upcomingCup) }}" type="button">{{ __('hnt_cups_overview.remember') }}</button>
</div>
</article>
@endforeach
</div>
@else
<div class="cups-empty-state">
<span>{{ __('hnt_cups_overview.empty_title') }}</span>
<p>{{ __('hnt_cups_overview.empty_text') }}</p>
</div>
@endif
</section>
