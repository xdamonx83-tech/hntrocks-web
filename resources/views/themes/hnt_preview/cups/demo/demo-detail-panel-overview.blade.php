@php
    $scoredSubmissions = $cup->submissions
        ->whereIn('status', \App\Models\CupSubmission::scoredStatuses())
        ->values();
    $totalKills = (int) $scoredSubmissions->sum('kills');
    $totalBountyTokens = (int) $scoredSubmissions->sum('bounty_tokens');
    $recentSubmissions = $cup->submissions
        ->sortByDesc(fn ($submission) => optional($submission->submitted_at ?: $submission->created_at)->timestamp ?? 0)
        ->take(4)
        ->values();
    $topTeams = collect($leaderboard ?? [])->take(3)->values();
    $overviewTitle = trim((string) $summary) !== '' ? $summary : $cup->title;
    $overviewCopy = trim((string) $description) !== ''
        ? $description
        : trim((string) $cup->displayRules());
    $overviewCopy = $overviewCopy !== ''
        ? $overviewCopy
        : ($isEnglish ? 'All important cup information is collected here.' : 'Hier findest du alle wichtigen Informationen zu diesem Cup.');
    $scheduleBaseDate = $cup->starts_at ?: $cup->registration_opens_at ?: $cup->created_at;
    $scheduleDates = collect([
        $registrationDate,
        $teamLockDate,
        $startDate,
        $midpointDate,
        $endDate,
    ]);
    $activityTeam = $viewerTeam ?: $topTeams->first();
    $activityMembers = $activityTeam
        ? $activityTeam->members->where('status', 'active')->take(3)->values()
        : collect();
    $cupNotices = collect([
        [
            'date' => $cup->registration_closes_at,
            'title' => $isEnglish ? 'Registration closes' : 'Anmeldung schließt',
            'text' => $isEnglish ? 'Teams and participants must be complete before this deadline.' : 'Teams und Teilnehmer müssen bis zu diesem Zeitpunkt vollständig sein.',
        ],
        [
            'date' => $cup->starts_at,
            'title' => $isEnglish ? 'Cup starts' : 'Cup startet',
            'text' => $submissionOpen
                ? ($isEnglish ? 'Submissions are currently open.' : 'Einreichungen sind aktuell geöffnet.')
                : ($isEnglish ? 'Submissions become available when the cup starts.' : 'Einreichungen werden zum Cup-Start freigeschaltet.'),
        ],
        [
            'date' => $cup->ends_at,
            'title' => $isEnglish ? 'Submission deadline' : 'Einreichungsfrist',
            'text' => $isEnglish ? 'All valid submissions must be received before the cup ends.' : 'Alle gültigen Einreichungen müssen vor dem Cup-Ende eingegangen sein.',
        ],
        [
            'date' => null,
            'title' => $isEnglish ? 'Verification' : 'Prüfung',
            'text' => $verificationMode === \App\Support\CupOrganizerAccess::VERIFICATION_AI
                ? ($isEnglish ? 'Submissions are checked automatically and can be reviewed manually.' : 'Einreichungen werden automatisch geprüft und können manuell kontrolliert werden.')
                : ($isEnglish ? 'Submissions are reviewed manually by the cup organizers.' : 'Einreichungen werden manuell durch die Cup-Leitung geprüft.'),
        ],
    ])->filter(fn (array $notice) => $notice['date'] !== null || trim((string) $notice['text']) !== '')->values();
@endphp
<section class="cup-tab-panel active" data-cup-panel="overview" tabindex="0"><article class="cup-description-panel">
<div class="cup-description-copy">
<span>{{ $isEnglish ? 'ABOUT THE CUP' : 'ÜBER DEN CUP' }}</span>
<h3>{{ \Illuminate\Support\Str::limit($overviewTitle, 110) }}</h3>
<p>{!! nl2br(e($overviewCopy)) !!}</p>
</div>
<div class="cup-description-facts">
<span><small>{{ $isEnglish ? 'Mode' : 'Modus' }}</small><strong>{{ $cup->modeLabel() }}</strong></span>
<span><small>{{ $isEnglish ? 'Scoring' : 'Wertung' }}</small><strong>{{ $requiresExtraction ? ($isEnglish ? 'Extraction' : 'Extraktion') : ($isEnglish ? 'Points' : 'Punkte') }}{{ $pointsPerKill > 0 ? ' + Kills' : '' }}</strong></span>
<span><small>{{ $isEnglish ? 'Proof' : 'Nachweis' }}</small><strong>Screenshot</strong></span>
<span><small>{{ $isEnglish ? 'Verification' : 'Prüfung' }}</small><strong>{{ $verificationLabel }}</strong></span>
</div>
</article><section class="cup-schedule-block"><div class="cup-calendar-head">
<button type="button">{{ $scheduleBaseDate?->translatedFormat('F') ?: ($isEnglish ? 'Schedule' : 'Zeitplan') }}</button>
<strong>{{ $cup->title }}{{ $scheduleBaseDate ? ' '.$scheduleBaseDate->format('Y') : '' }}</strong>
<button type="button">{{ $isEnglish ? 'Cup schedule' : 'Cup-Zeitplan' }}</button>
</div><div class="cup-timeline-grid">
<div class="cup-timeline-days">
@foreach ($scheduleDates as $index => $date)
<span @class(['active' => $index === $currentMilestoneIndex])><small>{{ $date?->translatedFormat('D') ?: '—' }}</small><b>{{ $date?->format('d') ?: '—' }}</b></span>
@endforeach
</div>
<div class="cup-timeline-hours">
<span>{{ $teamLockDate?->format('H:i') ?: '—' }}</span><span>{{ $startDate?->format('H:i') ?: '—' }}</span><span>{{ $endDate?->format('H:i') ?: '—' }}</span>
</div>
<div class="cup-timeline-events">
<article class="registration">
<strong>{{ $isEnglish ? 'Registration & team lock' : 'Anmeldung & Team-Lock' }}</strong>
<span>{{ $teamLockDate ? $teamLockDate->translatedFormat('d.m.Y · H:i') : ($isEnglish ? 'No fixed deadline' : 'Keine feste Frist') }}</span>
</article>
<article class="start">
<strong>{{ $isEnglish ? 'Cup starts' : 'Cup startet' }}</strong>
<span>{{ $startDate ? $startDate->translatedFormat('d.m.Y · H:i') : ($isEnglish ? 'Start not scheduled' : 'Start noch nicht geplant') }}</span>
@if ($activityMembers->isNotEmpty())
<div>@foreach ($activityMembers as $member)<img alt="{{ $member->user?->name ?: $member->user?->username ?: 'Hunter' }}" src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>@endforeach</div>
@endif
</article>
<article class="deadline">
<strong>{{ $isEnglish ? 'Cup ends' : 'Cup endet' }}</strong>
<span>{{ $endDate ? $endDate->translatedFormat('d.m.Y · H:i') : ($isEnglish ? 'Open end' : 'Offenes Ende') }}</span>
</article>
</div>
</div></section><section class="cup-overview-more">
<div class="cup-overview-section-head">
<div>
<span>{{ $isEnglish ? 'LIVE CUP' : 'LIVE CUP' }}</span>
<h3>{{ $isEnglish ? 'Current status' : 'Aktueller Stand' }}</h3>
</div>
<button type="button" onclick="window.location.reload()">{{ $isEnglish ? 'Refresh' : 'Aktualisieren' }}</button>
</div>
<div class="cup-live-summary">
<article>
<span>{{ $soloCup ? ($isEnglish ? 'Participants' : 'Teilnehmer') : ($isEnglish ? 'Confirmed teams' : 'Teams bestätigt') }}</span>
<strong>{{ $teamCount }}{{ $teamLimit ? ' / '.$teamLimit : '' }}</strong>
<small>{{ $teamLimit ? max(0, $teamLimit - $teamCount).' '.($isEnglish ? 'places available' : 'Plätze frei') : ($isEnglish ? 'no fixed limit' : 'kein festes Limit') }}</small>
</article>
<article>
<span>{{ $isEnglish ? 'Submissions' : 'Einreichungen' }}</span>
<strong>{{ $submissionCount }}</strong>
<small>{{ $pendingSubmissionCount }} {{ $isEnglish ? 'pending review' : 'in Prüfung' }}</small>
</article>
<article>
<span>{{ $isEnglish ? 'Hunter kills' : 'Hunter-Kills' }}</span>
<strong>{{ $totalKills }}</strong>
<small>{{ $isEnglish ? 'scored in total' : 'gesamt gewertet' }}</small>
</article>
<article>
<span>{{ $cup->usesSummerFirstTrophyScoring() ? ($isEnglish ? 'Trophies' : 'Trophäen') : 'Bounty' }}</span>
<strong>{{ $totalBountyTokens }}</strong>
<small>{{ $isEnglish ? 'confirmed' : 'bestätigt' }}</small>
</article>
</div>
<div class="cup-overview-split">
<section class="cup-overview-feed">
<header>
<div>
<span>{{ $isEnglish ? 'LIVE ACTIVITY' : 'LIVE-AKTIVITÄT' }}</span>
<h3>{{ $isEnglish ? 'Latest submissions' : 'Letzte Einreichungen' }}</h3>
</div>
<small>{{ $recentSubmissions->count() }}</small>
</header>
@forelse ($recentSubmissions as $submission)
<article>
<img alt="{{ $submission->submitter?->name ?: $submission->submitter?->username ?: 'Hunter' }}" src="{{ $submission->submitter?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<div>
<strong>{{ $submission->team?->displayName() ?: ($submission->submitter?->username ?: $submission->submitter?->name ?: ($isEnglish ? 'Submission' : 'Einreichung')) }}</strong>
<span>{{ $submission->statusLabel() }} · {{ (int) $submission->points }} {{ $isEnglish ? 'points' : 'Punkte' }}</span>
</div>
<time>{{ optional($submission->submitted_at ?: $submission->created_at)->diffForHumans() }}</time>
</article>
@empty
<article>
<img alt="HNT.ROCKS" src="{{ asset('assets/vikinger/img/default-avatar.svg') }}"/>
<div><strong>{{ $isEnglish ? 'No submissions yet' : 'Noch keine Einreichungen' }}</strong><span>{{ $isEnglish ? 'Activity appears here after the first upload.' : 'Nach dem ersten Upload erscheint hier die Aktivität.' }}</span></div>
<time>—</time>
</article>
@endforelse
</section>
<section class="cup-overview-ranking">
<header>
<div>
<span>TOP 3</span>
<h3>Leaderboard</h3>
</div>
<button data-cup-tab-shortcut="teams" type="button">{{ $isEnglish ? 'All teams' : 'Alle Teams' }}</button>
</header>
@forelse ($topTeams as $index => $team)
<article>
<b>{{ $index + 1 }}</b>
<div class="cup-overview-team">
<span class="team-avatars">
@php($rankingMembers = $team->members->where('status', 'active')->take(3))
@if ($rankingMembers->isNotEmpty())
@foreach ($rankingMembers as $member)<img alt="{{ $member->user?->name ?: $member->user?->username ?: 'Hunter' }}" src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>@endforeach
@else
<img alt="{{ $team->owner?->name ?: $team->displayName() }}" src="{{ $team->owner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
@endif
</span>
<div><strong>{{ $team->displayName() }}</strong><small>{{ (int) $team->kills_total }} Kills · {{ (int) $team->bounty_tokens_total }} {{ $isEnglish ? 'Bounty' : 'Trophäen' }}</small></div>
</div>
<strong>{{ (int) $team->points_total }}</strong>
</article>
@empty
<article><b>—</b><div class="cup-overview-team"><div><strong>{{ $isEnglish ? 'No rankings yet' : 'Noch keine Platzierungen' }}</strong><small>{{ $isEnglish ? 'Approved scores appear here.' : 'Bestätigte Scores erscheinen hier.' }}</small></div></div><strong>0</strong></article>
@endforelse
</section>
</div>
<section class="cup-overview-updates">
<header>
<div>
<span>{{ $isEnglish ? 'CUP INFO' : 'CUP-INFOS' }}</span>
<h3>{{ $isEnglish ? 'Dates & notes' : 'Termine & Hinweise' }}</h3>
</div>
</header>
@foreach ($cupNotices as $notice)
<article>
<time>{{ $notice['date'] ? $notice['date']->translatedFormat('d.m.Y · H:i') : ($isEnglish ? 'Current' : 'Aktuell') }}</time>
<div><strong>{{ $notice['title'] }}</strong><p>{{ $notice['text'] }}</p></div>
</article>
@endforeach
</section>
<section class="cup-overview-faq">
<div class="cup-overview-section-head">
<div>
<span>{{ $isEnglish ? 'FREQUENT QUESTIONS' : 'HÄUFIGE FRAGEN' }}</span>
<h3>{{ $isEnglish ? 'Participation & scores' : 'Teilnahme & Scores' }}</h3>
</div>
</div>
<details open="">
<summary>{{ $isEnglish ? 'Who can upload submissions?' : 'Wer darf Einreichungen hochladen?' }}<svg><use href="#i-chevron"></use></svg></summary>
<p>{{ $soloCup ? ($isEnglish ? 'Registered participants can upload their own screenshots.' : 'Angemeldete Teilnehmer können ihre eigenen Screenshots hochladen.') : ($isEnglish ? 'The captain of a complete team can upload screenshots.' : 'Der Captain eines vollständigen Teams kann Screenshots hochladen.') }}</p>
</details>
<details>
<summary>{{ $isEnglish ? 'How many screenshots are allowed?' : 'Wie viele Screenshots sind erlaubt?' }}<svg><use href="#i-chevron"></use></svg></summary>
<p>{{ $maxUploadsPerParticipant ? ($isEnglish ? 'Up to '.$maxUploadsPerParticipant.' uploads per participant are allowed.' : 'Pro Teilnehmer sind maximal '.$maxUploadsPerParticipant.' Uploads erlaubt.') : ($isEnglish ? 'No fixed upload limit is configured.' : 'Es ist kein festes Upload-Limit eingestellt.') }} {{ $cooldownMinutes > 0 ? ($isEnglish ? 'The cooldown is '.$cooldownMinutes.' minutes.' : 'Der Cooldown beträgt '.$cooldownMinutes.' Minuten.') : '' }}</p>
</details>
<details>
<summary>{{ $isEnglish ? 'When does a score appear on the leaderboard?' : 'Wann erscheint ein Score im Leaderboard?' }}<svg><use href="#i-chevron"></use></svg></summary>
<p>{{ $verificationMode === \App\Support\CupOrganizerAccess::VERIFICATION_AI ? ($isEnglish ? 'After automatic validation or a manual review.' : 'Nach automatischer Bestätigung oder einer manuellen Prüfung.') : ($isEnglish ? 'After manual review and approval by the cup organizers.' : 'Nach manueller Prüfung und Bestätigung durch die Cup-Leitung.') }}</p>
</details>
</section>
</section></section>