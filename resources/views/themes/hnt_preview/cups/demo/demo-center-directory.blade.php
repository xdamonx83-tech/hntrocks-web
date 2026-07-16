<section class="cups-section cups-all-section">
<header>
<div>
<span>{{ __('hnt_cups_overview.all_events') }}</span>
<h3>{{ __('hnt_cups_overview.directory') }}</h3>
</div>
<small id="visibleCupCount">{{ $formatCount((int) $cups->total()) }} {{ $cups->total() === 1 ? __('hnt_cups_overview.cup_singular') : __('hnt_cups_overview.cup_plural') }}</small>
</header>
@if($cups->isEmpty())
<div class="cups-empty-state" id="cupsEmptyState">
<span>{{ __('hnt_cups_overview.empty_title') }}</span>
<p>{{ __('hnt_cups_overview.empty_text') }}</p>
</div>
@else
<div class="cups-card-grid" id="cupsCardGrid">
@foreach($cups as $cup)
@php
    $participantLimit = $cup->participantLimit();
    $platforms = implode(' / ', $cup->allowedPlatforms()) ?: ($cup->platform ?: __('hnt_cups_overview.all_platforms'));
    $statusClass = in_array($cup->status, ['active', 'planned', 'finished'], true) ? $cup->status : 'finished';
    $modeLabel = $cup->isSoloLeaderboard()
        ? __('hnt_cups_overview.solo')
        : match ((int) $cup->team_size) {
            2 => 'Duo',
            3 => 'Trio',
            default => __('hnt_cups_overview.team_size', ['counter' => max(1, (int) $cup->team_size)]),
        };
    $dateLabel = in_array($cup->status, ['finished', 'archived'], true)
        ? __('hnt_cups_overview.ended_on', ['date' => $cup->ends_at?->translatedFormat('d.m.Y') ?: $cup->starts_at?->translatedFormat('d.m.Y') ?: '—'])
        : __('hnt_cups_overview.starts_on', ['date' => $cup->starts_at?->translatedFormat('d.m.Y') ?: 'TBA']);
    $teamLabel = $cup->isSoloLeaderboard() ? __('hnt_cups_overview.participants') : __('hnt_cups_overview.teams');
    $availability = $participantLimit
        ? __('hnt_cups_overview.open_slots', ['count' => max(0, $participantLimit - (int) $cup->active_teams_count)])
        : __('hnt_cups_overview.unlimited_slots');
@endphp
<article class="cup-card"
         data-platform="{{ \Illuminate\Support\Str::lower($platforms) }}"
         data-search="{{ \Illuminate\Support\Str::lower($cup->title.' '.$cup->displaySummary().' '.$platforms) }}"
         data-status="{{ $cup->status }}">
<div class="cup-card-art" style="background-image:linear-gradient(180deg,rgba(243,243,240,.46),rgba(243,243,240,.88)),url('{{ $cup->coverUrl() }}');background-size:cover;background-position:center;">
<span>{{ \Illuminate\Support\Str::upper($cup->statusLabel()) }}</span>
<b>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::limit($cup->title, 24, '')) }}</b>
<small>{{ $dateLabel }}</small>
</div>
<div class="cup-card-body">
<div><span class="{{ $statusClass }}"><i></i>{{ $cup->statusLabel() }}</span><em>{{ $modeLabel }}</em></div>
<h4>{{ $cup->title }}</h4>
<p>{{ \Illuminate\Support\Str::limit($cup->displaySummary(), 122) }}</p>
<ul>
<li>{{ $formatCount((int) $cup->active_teams_count) }} {{ $teamLabel }}</li>
<li>{{ $platforms }}</li>
<li>{{ $availability }}</li>
</ul>
<a href="{{ route('cups.show', $cup) }}">{{ in_array($cup->status, ['finished', 'archived'], true) ? __('hnt_cups_overview.results') : __('hnt_cups_overview.details') }} <svg><use href="#i-arrow"></use></svg></a>
</div>
</article>
@endforeach
</div>
@endif
@if($cups->hasPages())
<nav class="cups-pagination" aria-label="{{ __('hnt_cups_overview.directory') }}">
@if($cups->previousPageUrl())
<a href="{{ $cups->previousPageUrl() }}">{{ __('hnt_cups_overview.previous') }}</a>
@else
<span aria-disabled="true">{{ __('hnt_cups_overview.previous') }}</span>
@endif
<strong>{{ $cups->currentPage() }} / {{ $cups->lastPage() }}</strong>
@if($cups->nextPageUrl())
<a href="{{ $cups->nextPageUrl() }}">{{ __('hnt_cups_overview.next_page') }}</a>
@else
<span aria-disabled="true">{{ __('hnt_cups_overview.next_page') }}</span>
@endif
</nav>
@endif
</section>
<section class="cups-section cups-hub-mobile">
<header>
<div><span>{{ __('hnt_cups_overview.cup_hub') }}</span><h3>{{ __('hnt_cups_overview.direct_access') }}</h3></div>
</header>
<div class="cups-hub-mobile-grid">
<button data-url="{{ route('hall-of-fame.index') }}" type="button">{{ __('hnt_cups_overview.hall_of_fame') }}</button>
<button data-url="{{ $featuredCup ? route('cups.show.section', ['cup' => $featuredCup, 'section' => 'rules']) : $allCupsUrl }}" type="button">{{ __('hnt_cups_overview.scoring_fair_play') }}</button>
<button data-url="{{ route('cup-ideas.index') }}" type="button">{{ __('hnt_cups_overview.cup_ideas') }}</button>
<button data-url="{{ $mineUrl }}" type="button">{{ __('hnt_cups_overview.my_cups') }}</button>
</div>
</section>
