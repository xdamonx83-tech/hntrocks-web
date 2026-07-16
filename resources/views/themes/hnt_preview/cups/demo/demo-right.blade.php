@php
    $podium = collect([
        ['place' => 2, 'class' => 'second', 'team' => $topTeams->get(1)],
        ['place' => 1, 'class' => 'first', 'team' => $topTeams->get(0)],
        ['place' => 3, 'class' => 'third', 'team' => $topTeams->get(2)],
    ]);
    $scoringUrl = $featuredCup
        ? route('cups.show.section', ['cup' => $featuredCup, 'section' => 'rules'])
        : $allCupsUrl;
@endphp
<aside aria-label="{{ __('hnt_cups_overview.cup_hub') }}" class="cups-fixed-column cups-fixed-right" id="cupsFixedRight">
<article class="cups-hall-card">
<header>
<div><span>{{ __('hnt_cups_overview.hall_of_fame') }}</span><h2>{{ __('hnt_cups_overview.top_teams') }}</h2></div>
<button data-url="{{ route('hall-of-fame.index') }}" type="button" aria-label="{{ __('hnt_cups_overview.hall_of_fame') }}"><svg><use href="#i-arrow"></use></svg></button>
</header>
<div class="cups-podium">
@foreach($podium as $entry)
@php $team = $entry['team']; @endphp
<article class="{{ $entry['class'] }}">
<b>{{ $entry['place'] }}</b>
<img alt="{{ $team?->displayName() ?: '' }}" src="{{ $team?->owner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<strong>{{ $team?->displayName() ?: '—' }}</strong>
<small>{{ __('hnt_cups_overview.points', ['count' => $formatCount((int) ($team?->points_total ?? 0))]) }}</small>
</article>
@endforeach
</div>
@if($topTeams->isEmpty())
<p class="cups-hall-empty">{{ __('hnt_cups_overview.no_hall_entries') }}</p>
@endif
<div class="cups-hall-stats">
<span><strong>{{ $formatCount((int) $hallStats['cups']) }}</strong><small>{{ __('hnt_cups_overview.cups_stat') }}</small></span>
<span><strong>{{ $formatCount((int) $hallStats['finalists']) }}</strong><small>{{ __('hnt_cups_overview.finalists') }}</small></span>
<span><strong>{{ $formatCount((int) $hallStats['winners']) }}</strong><small>{{ __('hnt_cups_overview.winners') }}</small></span>
</div>
</article>
<article class="cups-hub-card">
<header><span>{{ __('hnt_cups_overview.cup_hub') }}</span><h3>{{ __('hnt_cups_overview.direct_access') }}</h3></header>
<button data-url="{{ $statusUrl('active') }}" type="button"><svg><use href="#i-check"></use></svg><span><strong>{{ __('hnt_cups_overview.active_cups') }}</strong><small>{{ __('hnt_cups_overview.active_events', ['count' => $stats['active']]) }}</small></span><i>→</i></button>
<button data-url="{{ $statusUrl('planned') }}" type="button"><svg><use href="#i-folder"></use></svg><span><strong>{{ __('hnt_cups_overview.planned') }}</strong><small>{{ __('hnt_cups_overview.planned_events', ['count' => $stats['planned']]) }}</small></span><i>→</i></button>
<button data-url="{{ $scoringUrl }}" type="button"><svg><use href="#i-sliders"></use></svg><span><strong>{{ __('hnt_cups_overview.scoring_fair_play') }}</strong><small>{{ __('hnt_cups_overview.rules_and_scores') }}</small></span><i>→</i></button>
<button data-url="{{ route('cup-ideas.index') }}" type="button"><svg><use href="#i-comment"></use></svg><span><strong>{{ __('hnt_cups_overview.cup_ideas') }}</strong><small>{{ __('hnt_cups_overview.cup_ideas_text') }}</small></span><i>→</i></button>
</article>
</aside>
