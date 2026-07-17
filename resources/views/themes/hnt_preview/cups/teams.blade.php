@php
    $isEnglish = app()->getLocale() === 'en';
    $t = static fn (string $de, string $en): string => $isEnglish ? $en : $de;

    $teamMembers = $team->members->where('status', 'active')->values();
    $teamMemberCount = $teamMembers->count();
    $teamRequiredMembers = max(1, $team->requiredMembersCount());
    $teamPercent = min(100, (int) round(($teamMemberCount / $teamRequiredMembers) * 100));
    $teamComplete = $teamMemberCount >= $teamRequiredMembers;
    $teamConfirmedPercent = $team->status === 'active' ? 100 : 0;

    $teamSubmissions = $team->submissions
        ->sortByDesc(fn ($submission) => optional($submission->submitted_at ?: $submission->created_at)->timestamp)
        ->values();
    $teamSubmissionCount = $teamSubmissions->count();
    $teamApprovedCount = $teamSubmissions
        ->whereIn('status', \App\Models\CupSubmission::scoredStatuses())
        ->count();
    $teamSubmissionLimit = $cup->maxSubmissionsPerParticipant();
    $teamSubmissionPercent = $teamSubmissionLimit
        ? min(100, (int) round(($teamSubmissionCount / max(1, $teamSubmissionLimit)) * 100))
        : min(100, $teamSubmissionCount * 10);

    $teamPlatformLabel = collect($cup->allowedPlatforms())->implode(' / ')
        ?: $t('Alle Plattformen', 'All platforms');
    $teamCoverUrl = $cup->coverUrl();
    $teamRoleLabel = $isCaptain ? 'Captain' : $t('Mitglied', 'Member');
    $teamRosterLabel = $team->isRosterLocked()
        ? $t('Roster gesperrt', 'Roster locked')
        : $t('Roster offen', 'Roster open');
    $teamCompletionLabel = $teamComplete
        ? $t('Team vollständig', 'Team complete')
        : $t('Noch Plätze frei', 'Open slots remain');

    $teamManageJsVersion = @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/team-manage.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta name="robots" content="noindex,nofollow"/>
<title>{{ $team->displayName() }} · {{ $t('Team verwalten', 'Manage team') }} · HNT.ROCKS</title>
<base href="{{ asset('assets/themes/hnt_preview/dashboard-feed/') }}/"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part1.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part2.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part3.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part4.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part5.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-original/part6.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail-theme-live-red.css') }}" rel="stylesheet"/>
</head>
<body data-page="team-manage">
@include('themes.hnt_preview.cups.demo.demo-svg')
<svg aria-hidden="true" class="svg-defs">
<symbol id="i-edit" viewBox="0 0 24 24"><path d="m4 20 4.2-1 10.4-10.4a2 2 0 0 0 0-2.8l-.4-.4a2 2 0 0 0-2.8 0L5 15.8 4 20Z"></path><path d="m13.8 7 3.2 3.2"></path></symbol>
</svg>
<main class="app-shell team-manage-page-shell">
@include('themes.hnt_preview.partials.header')
@include('themes.hnt_preview.cups.demo.team-manage.demo-team-manage-stage')
@include('themes.hnt_preview.cups.demo.team-manage.demo-team-manage-confirm')
<div class="toast" id="toast"></div>
</main>
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = '/feed';</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v=20260710-1"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v=20260714-1"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v=20260714-1"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage.js') }}?v={{ $teamManageJsVersion }}"></script>
</body>
</html>
