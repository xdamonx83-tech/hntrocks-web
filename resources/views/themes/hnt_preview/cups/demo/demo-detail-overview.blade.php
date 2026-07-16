<section class="cup-detail-overview">
<div class="cup-detail-heading">
<span>HNT.ROCKS COMMUNITY CUP</span>
<h1>{{ $cup->title }}</h1>
<div class="cup-hero-meta">
<span @class(['live' => $registrationOpen])><i></i>{{ $registrationOpen ? ($isEnglish ? 'Registration open' : 'Anmeldung geöffnet') : $cup->statusLabel() }}</span>
<span>{{ $cup->modeLabel() }}</span>
<span>{{ $platformLabel }}</span>
<span>{{ $regionLabel }}</span>
<span>{{ $dateRangeLabel }}</span>
</div>
<p class="cup-hero-description">{{ $summary }}</p>
<div class="cup-overview-bars">
<div class="cup-overview-bar wide">
<span>{{ $soloCup ? ($isEnglish ? 'Participants' : 'Teilnehmer') : ($isEnglish ? 'Teams' : 'Teams') }}</span>
<div class="dark"><b>{{ $teamCount }}{{ $teamLimit ? ' / '.$teamLimit : '' }}</b><i style="width:{{ $teamCapacityPercent }}%"></i></div>
</div>
<div class="cup-overview-bar">
<span>{{ $isEnglish ? 'Registration' : 'Anmeldung' }}</span>
<div class="yellow"><b>{{ $registrationRemainingLabel }}</b><i style="width:{{ $registrationOpen ? max(10, 100 - $cupProgressPercent) : 100 }}%"></i></div>
</div>
<div class="cup-overview-bar">
<span>{{ $isEnglish ? 'Cup progress' : 'Cup-Fortschritt' }}</span>
<div class="striped"><b>{{ $cupProgressPercent }}%</b><i style="width:{{ $cupProgressPercent }}%"></i></div>
</div>
<div class="cup-overview-bar compact">
<span>{{ $isEnglish ? 'Submissions' : 'Einreichungen' }}</span>
<div class="outline"><b>{{ $submissionCapacityLabel }}</b></div>
</div>
</div>
</div>
<div class="cup-overview-stats">
<article><strong>{{ $teamCount }}</strong><span>{{ $soloCup ? ($isEnglish ? 'Participants' : 'Teilnehmer') : 'Teams' }}</span></article>
<article><strong>{{ $participantCount }}</strong><span>{{ $isEnglish ? 'Hunters' : 'Hunter' }}</span></article>
<article><strong>{{ $submissionCount }}</strong><span>Scores</span></article>
</div>
</section>
