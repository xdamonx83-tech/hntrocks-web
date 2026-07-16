@php
    $isEnglish = app()->getLocale() === 'en';
    $now = now();
    $soloCup = $cup->isSoloLeaderboard();
    $activeTeams = $cup->teams->where('status', 'active')->values();
    $teamCount = $activeTeams->count();
    $teamLimit = $cup->participantLimit();
    $participantCount = $soloCup
        ? $teamCount
        : $activeTeams->sum(fn ($team) => $team->members->where('status', 'active')->count());
    $submissionCount = $cup->submissions->count();
    $approvedSubmissionCount = $cup->submissions
        ->whereIn('status', \App\Models\CupSubmission::scoredStatuses())
        ->count();
    $pendingSubmissionCount = $cup->submissions
        ->whereIn('status', ['pending', 'review_required'])
        ->count();
    $registrationOpen = $cup->isRegistrationOpen();
    $submissionOpen = $cup->isSubmissionOpen();
    $summary = $cup->displaySummary();
    $description = $cup->displayDescription();
    $scoringRules = $cup->displayScoringRules();
    $platforms = $cup->allowedPlatforms();
    $platformLabel = $platforms !== []
        ? implode(' / ', $platforms)
        : (trim((string) $cup->platform) ?: ($isEnglish ? 'All platforms' : 'Alle Plattformen'));
    $regionLabel = trim((string) $cup->region) ?: ($isEnglish ? 'All regions' : 'Alle Regionen');
    $languageLabel = trim((string) $cup->language) ?: ($isEnglish ? 'Open' : 'Offen');
    $teamSize = $soloCup ? 1 : max(1, (int) $cup->team_size);
    $teamCapacityPercent = $teamLimit
        ? min(100, (int) round(($teamCount / max(1, $teamLimit)) * 100))
        : ($teamCount > 0 ? 100 : 0);
    $maxUploadsPerParticipant = $cup->maxSubmissionsPerParticipant();
    $maxScoredPerParticipant = $cup->maxScoredSubmissionsPerParticipant();
    $submissionCapacity = $maxUploadsPerParticipant && $participantCount > 0
        ? $maxUploadsPerParticipant * $participantCount
        : null;
    $submissionCapacityLabel = $submissionCapacity
        ? $submissionCount.' / '.$submissionCapacity
        : (string) $submissionCount;
    $cooldownMinutes = max(0, (int) config('hunthub.cups.submission_cooldown_minutes', 30));
    $verificationMode = \App\Support\CupOrganizerAccess::verificationMode($cup);
    $verificationLabel = $verificationMode === \App\Support\CupOrganizerAccess::VERIFICATION_AI
        ? ($isEnglish ? 'Automatic + manual' : 'Automatisch + manuell')
        : ($isEnglish ? 'Manual' : 'Manuell');
    $pointsPerKill = max(0, (int) data_get(
        is_array($cup->settings) ? $cup->settings : [],
        'scoring.points_per_kill',
        config('hunthub.cups.points_per_kill', 1)
    ));
    $extractBonus = max(0, (int) data_get(
        is_array($cup->settings) ? $cup->settings : [],
        'scoring.first_trophy_extraction_bonus',
        0
    ));
    $requiresExtraction = $cup->usesSummerFirstTrophyScoring()
        || (bool) config('hunthub.cups.require_extract_for_score', true);
    $requiresBounty = $cup->usesSummerFirstTrophyScoring()
        || (bool) config('hunthub.cups.require_bounty_for_score', true);

    $formatRemaining = static function ($target) use ($now, $isEnglish): string {
        if (! $target || $target->lessThanOrEqualTo($now)) {
            return $isEnglish ? 'Now' : 'Jetzt';
        }

        $seconds = max(0, (int) $now->diffInSeconds($target));
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return $days.($isEnglish ? 'd ' : 'T ').$hours.'h';
        }

        if ($hours > 0) {
            return $hours.'h '.max(0, $minutes).'m';
        }

        return max(1, $minutes).'m';
    };

    $dateRangeLabel = match (true) {
        $cup->starts_at && $cup->ends_at => $cup->starts_at->format('d.m.Y').' – '.$cup->ends_at->format('d.m.Y'),
        (bool) $cup->starts_at => $cup->starts_at->format('d.m.Y'),
        (bool) $cup->ends_at => $cup->ends_at->format('d.m.Y'),
        default => $isEnglish ? 'Open schedule' : 'Offener Zeitraum',
    };

    $registrationRemainingLabel = match (true) {
        $registrationOpen && $cup->registration_closes_at?->isFuture() => $formatRemaining($cup->registration_closes_at),
        $registrationOpen => $isEnglish ? 'Open' : 'Offen',
        default => $isEnglish ? 'Closed' : 'Geschlossen',
    };

    if ($cup->starts_at && $cup->ends_at) {
        if ($now->lt($cup->starts_at)) {
            $windowStart = $cup->registration_opens_at ?: $cup->created_at;
            $windowSeconds = max(1, (int) $windowStart->diffInSeconds($cup->starts_at));
            $elapsedSeconds = max(0, (int) $windowStart->diffInSeconds($now));
            $cupProgressPercent = min(25, (int) round(($elapsedSeconds / $windowSeconds) * 25));
        } elseif ($now->lte($cup->ends_at)) {
            $windowSeconds = max(1, (int) $cup->starts_at->diffInSeconds($cup->ends_at));
            $elapsedSeconds = max(0, (int) $cup->starts_at->diffInSeconds($now));
            $cupProgressPercent = min(100, 25 + (int) round(($elapsedSeconds / $windowSeconds) * 75));
        } else {
            $cupProgressPercent = 100;
        }
    } else {
        $cupProgressPercent = match ($cup->status) {
            'active' => 50,
            'finished', 'archived' => 100,
            default => 0,
        };
    }

    if ($cup->starts_at?->isFuture()) {
        $progressRemaining = $formatRemaining($cup->starts_at);
        $progressRemainingLabel = $isEnglish ? 'until cup start' : 'bis zum Cup-Start';
    } elseif ($cup->ends_at?->isFuture()) {
        $progressRemaining = $formatRemaining($cup->ends_at);
        $progressRemainingLabel = $isEnglish ? 'until cup end' : 'bis zum Cup-Ende';
    } else {
        $progressRemaining = $cup->statusLabel();
        $progressRemainingLabel = $isEnglish ? 'current status' : 'aktueller Status';
    }

    $registrationDate = $cup->registration_opens_at ?: $cup->created_at;
    $teamLockDate = $cup->registration_closes_at
        ?: ($cup->starts_at ? $cup->starts_at->copy()->subDay() : null);
    $startDate = $cup->starts_at;
    $midpointDate = ($cup->starts_at && $cup->ends_at)
        ? $cup->starts_at->copy()->addSeconds((int) ($cup->starts_at->diffInSeconds($cup->ends_at) / 2))
        : null;
    $endDate = $cup->ends_at;
    $milestoneDates = [$registrationDate, $teamLockDate, $startDate, $midpointDate, $endDate];
    $currentMilestoneIndex = 0;
    foreach ($milestoneDates as $index => $milestoneDate) {
        if ($milestoneDate && $now->gte($milestoneDate)) {
            $currentMilestoneIndex = $index;
        }
    }

    $viewerTeamMembers = $viewerTeam
        ? $viewerTeam->members->where('status', 'active')->values()
        : collect();
    $viewerTeamMemberCount = $viewerTeamMembers->count();
    $viewerTeamSubmissions = $viewerTeam
        ? $cup->submissions->where('cup_team_id', $viewerTeam->id)->values()
        : collect();
    $viewerTeamSubmissionCount = $viewerTeamSubmissions->count();
    $viewerTeamApprovedCount = $viewerTeamSubmissions
        ->whereIn('status', \App\Models\CupSubmission::scoredStatuses())
        ->count();
    $viewerTeamPendingCount = $viewerTeamSubmissions
        ->whereIn('status', ['pending', 'review_required'])
        ->count();
    $viewerTeamRequiredMembers = $teamSize;
    $viewerTeamPercent = $viewerTeam
        ? min(100, (int) round(($viewerTeamMemberCount / max(1, $viewerTeamRequiredMembers)) * 100))
        : 0;
    $viewerTeamConfirmedPercent = $viewerTeam && $viewerTeam->status === 'active' ? 100 : 0;
    $viewerScoreTarget = $maxScoredPerParticipant && $viewerTeamMemberCount > 0
        ? $maxScoredPerParticipant * $viewerTeamMemberCount
        : null;
    $viewerScoresPercent = $viewerScoreTarget
        ? min(100, (int) round(($viewerTeamApprovedCount / max(1, $viewerScoreTarget)) * 100))
        : ($viewerTeamApprovedCount > 0 ? 100 : 0);
    $viewerParticipationPercent = $viewerTeam
        ? (int) round(($viewerTeamPercent + $viewerTeamConfirmedPercent + $viewerScoresPercent) / 3)
        : 0;
    $viewerSubmissionLimitLabel = $maxUploadsPerParticipant
        ? $viewerTeamSubmissionCount.' · '.($isEnglish ? 'max. '.$maxUploadsPerParticipant.' per player' : 'max. '.$maxUploadsPerParticipant.' pro Spieler')
        : (string) $viewerTeamSubmissionCount;
    $participationRequirements = $cup->rulesSummary();

    $cupDetailCssVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-detail.css')) ?: time();
    $cupDetailThemeVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-detail-theme-live-red.css')) ?: time();
    $cupDetailJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-detail.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta content="{{ ($activeSection ?? 'overview') === 'overview' ? 'index,follow' : 'noindex,follow' }}" name="robots"/>
<title>{{ $cup->title }} · HNT.ROCKS</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail.css') }}?v={{ $cupDetailCssVersion }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail-theme-live-red.css') }}?v={{ $cupDetailThemeVersion }}" rel="stylesheet"/>
</head>
<body data-page="cup-detail">
@include('themes.hnt_preview.cups.demo.demo-svg')
<main class="app-shell cup-detail-page-shell"
      data-cups-active-url="{{ route('cups.index', ['status' => 'active']) }}"
      data-cups-mine-url="{{ route('cups.index', ['mine' => 1]) }}"
      data-cups-submissions-url="{{ route('cups.show.section', [$cup, 'submissions']) }}"
      data-cups-hall-url="{{ route('hall-of-fame.index') }}">
@include('themes.hnt_preview.partials.header')
<section class="cup-detail-stage">
@include('themes.hnt_preview.cups.demo.demo-detail-scroll')
@include('themes.hnt_preview.cups.demo.demo-detail-left')
@include('themes.hnt_preview.cups.demo.demo-detail-right')
</section>
<div class="toast" id="toast"></div>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v=20260710-1"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail.js') }}?v={{ $cupDetailJsVersion }}"></script>
</body>
</html>
