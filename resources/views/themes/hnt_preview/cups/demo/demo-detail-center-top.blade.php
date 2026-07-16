@php
    $milestoneLabels = $isEnglish
        ? ['Registration', 'Team lock', 'Start', 'Midpoint', 'End']
        : ['Anmeldung', 'Team-Lock', 'Start', 'Zwischenstand', 'Ende'];
    $milestoneHeights = [38, 58, 86, 66, 46];
@endphp
<div class="cup-center-top-cards"><article class="cup-progress-card cup-white-card">
<header>
<div><span>{{ $isEnglish ? 'SCHEDULE' : 'ZEITPLAN' }}</span><h2>{{ $isEnglish ? 'Cup progress' : 'Cup Fortschritt' }}</h2></div>
<button aria-label="{{ $isEnglish ? 'Open schedule' : 'Zeitplan öffnen' }}" data-toast="{{ $isEnglish ? 'Full schedule opened' : 'Vollständigen Zeitplan geöffnet' }}" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</header>
<div class="cup-progress-summary">
<strong>{{ $progressRemaining }}</strong>
<span>{{ $progressRemainingLabel }}</span>
<em>{{ $teamCount }} {{ $soloCup ? ($isEnglish ? 'participants confirmed' : 'Teilnehmer bestätigt') : ($isEnglish ? 'teams confirmed' : 'Teams bestätigt') }}</em>
</div>
<div aria-label="{{ $isEnglish ? 'Cup milestones' : 'Cup Meilensteine' }}" class="cup-milestone-bars">
@foreach ($milestoneLabels as $index => $milestoneLabel)
<span @class(['active' => $currentMilestoneIndex === $index])><i style="height:{{ $milestoneHeights[$index] }}%"></i><b>{{ $milestoneDates[$index]?->format('d.') ?: '–' }}</b><small>{{ $milestoneLabel }}</small></span>
@endforeach
</div><div class="cup-progress-legend">
<span><i class="done"></i> {{ $isEnglish ? 'completed' : 'abgeschlossen' }}</span>
<span><i class="current"></i> {{ $isEnglish ? 'current step' : 'aktueller Schritt' }}</span>
<span><i></i> {{ $isEnglish ? 'upcoming' : 'ausstehend' }}</span>
</div>
</article><article class="cup-scoring-card cup-white-card">
<header>
<div><span>{{ $isEnglish ? 'SCORING' : 'WERTUNG' }}</span><h2>Scoring</h2></div>
<button aria-label="{{ $isEnglish ? 'Open rules' : 'Regeln öffnen' }}" data-cup-tab-shortcut="rules" type="button">
<svg><use href="#i-arrow"></use></svg>
</button>
</header>
<div class="cup-score-ring">
<div><strong>{{ $pointsPerKill > 0 ? $pointsPerKill.'+' : '–' }}</strong><span>{{ $isEnglish ? 'points / kill' : 'Punkte / Kill' }}</span></div>
</div>
<div class="cup-scoring-actions">
<button data-toast="{{ e($scoringRules) }}" type="button">
<svg><use href="#i-eye"></use></svg>
</button>
<button data-cup-tab-shortcut="rules" type="button">
<svg><use href="#i-check"></use></svg>
</button>
<span>{{ $requiresExtraction ? ($isEnglish ? 'Extraction required' : 'Extraktion erforderlich') : ($isEnglish ? 'No extraction requirement' : 'Keine Extraktionspflicht') }}{{ $extractBonus > 0 ? ' · +'.$extractBonus.' Bonus' : '' }}</span>
</div>
</article></div>
