@php
    use Illuminate\Support\Str;

    $viewer = auth()->user();
    $isEnglish = app()->getLocale() === 'en';
    $t = static fn (string $de, string $en): string => $isEnglish ? $en : $de;
    $soloCup = $cup->isSoloLeaderboard();
    $activeTeams = $cup->teams->where('status', 'active')->values();
    $teamCount = $activeTeams->count();
    $participantCount = $soloCup
        ? $teamCount
        : $activeTeams->sum(fn ($team) => $team->members->where('status', 'active')->count());
    $participantLimit = $cup->participantLimit();
    $approvedSubmissionCount = $cup->submissions->whereIn('status', ['processed', 'approved', 'approved_manual'])->count();
    $pendingSubmissionCount = $cup->submissions->whereIn('status', ['pending', 'review_required'])->count();
    $invalidSubmissionCount = $cup->submissions->whereIn('status', ['invalid', 'rejected', 'rejected_manual'])->count();
    $visibleSubmissions = $canManage
        ? $cup->submissions
        : ($viewerTeam ? $cup->submissions->where('cup_team_id', $viewerTeam->id) : collect());
    $summary = $cup->displaySummary();
    $description = $cup->displayDescription();
    $rules = $cup->displayRules();
    $scoringRules = $cup->displayScoringRules();
    $rulesSummaryRows = $cup->rulesSummary();
    $prizeRows = $cup->prizeRows();
    $prizeNotes = $cup->prizeNotes();
    $registrationOpen = $cup->isRegistrationOpen();
    $submissionOpen = $cup->isSubmissionOpen();
    $submissionClosedReason = $submissionOpen ? null : $cup->submissionClosedReason();
    $viewerEligibility = $viewer ? $cup->participationEligibility($viewer) : ['eligible' => false, 'messages' => []];
    $coverUrl = $cup->coverUrl();
    $viewerTeamMembers = $viewerTeam ? $viewerTeam->members->where('status', 'active')->values() : collect();
    $viewerTeamSize = max(1, (int) ($cup->team_size ?: 1));
    $viewerTeamMemberCount = $viewerTeamMembers->count();
    $viewerIsTeamCaptain = $viewerTeam ? $viewerTeam->isCaptain($viewer) : false;
    $viewerTeamComplete = $soloCup || ($viewerTeam && $viewerTeamMemberCount >= $viewerTeamSize);
    $viewerTeamCanSubmit = $viewerTeam
        && $viewerTeam->status === 'active'
        && $submissionOpen
        && ($soloCup || ($viewerIsTeamCaptain && $viewerTeamComplete));
    $maxUploads = $cup->maxSubmissionsPerParticipant();
    $submissionLimit = max(1, (int) ($maxUploads ?: 3));
    $usedUploads = $viewerTeam ? $cup->submissions->where('cup_team_id', $viewerTeam->id)->count() : 0;
    $teamProgress = $viewerTeam ? min(100, (int) round(($viewerTeamMemberCount / $viewerTeamSize) * 100)) : 0;
    $scoreProgress = $viewerTeam ? min(100, (int) round(($usedUploads / $submissionLimit) * 100)) : 0;
    $uploadLimitMb = max(1, (int) ceil(((int) config('hunthub.upload_limits.cup_submission_screenshot_kb', 10240)) / 1024));
    $entryLabel = $soloCup ? $t('Hunter', 'Hunters') : $t('Teams', 'Teams');
    $rankedTeams = collect($leaderboard ?? $activeTeams)->values();
    $topTeams = $rankedTeams->take(3)->values();
    $recentSubmissions = $cup->submissions->sortByDesc(fn ($submission) => optional($submission->submitted_at ?: $submission->created_at)->timestamp)->take(4)->values();
    $activeSection = in_array($activeSection ?? 'overview', ['overview', 'rules', 'participants', 'prizes', 'submit', 'submissions'], true)
        ? $activeSection
        : 'overview';
    $dateRange = $cup->starts_at
        ? $cup->starts_at->translatedFormat('d.m.Y') . ($cup->ends_at ? ' – '.$cup->ends_at->translatedFormat('d.m.Y') : '')
        : $t('Termin offen', 'Date TBA');
    $registrationProgress = 0;
    if ($cup->registration_opens_at && $cup->registration_closes_at) {
        $from = $cup->registration_opens_at->timestamp;
        $to = max($from + 1, $cup->registration_closes_at->timestamp);
        $registrationProgress = min(100, max(0, (int) round(((now()->timestamp - $from) / ($to - $from)) * 100)));
    } elseif($registrationOpen) {
        $registrationProgress = 50;
    }
    $cupProgress = 0;
    if ($cup->starts_at && $cup->ends_at) {
        $from = $cup->starts_at->timestamp;
        $to = max($from + 1, $cup->ends_at->timestamp);
        $cupProgress = min(100, max(0, (int) round(((now()->timestamp - $from) / ($to - $from)) * 100)));
    } elseif($cup->status === 'finished') {
        $cupProgress = 100;
    }
    $fillPercent = $participantLimit ? min(100, (int) round(($teamCount / max(1, $participantLimit)) * 100)) : min(100, $teamCount * 10);
    $statusUrl = fn (string $status): string => route('cups.index', ['status' => $status]);
    $mineUrl = route('cups.index', ['mine' => 1]);
    $submissionUrl = $viewerTeam ? route('cups.show.section', [$cup, 'submissions']) : $mineUrl;
    $sectionUrl = fn (string $section): string => $section === 'overview'
        ? route('cups.show', $cup)
        : route('cups.show.section', [$cup, $section]);
    $statusTone = match ($cup->status) {
        'active' => 'active',
        'planned' => 'planned',
        'finished' => 'finished',
        default => 'neutral',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta content="{{ $activeSection === 'overview' ? 'index,follow' : 'noindex,follow' }}" name="robots"/>
<title>{{ $cup->title }} · HNT.ROCKS</title>
<meta name="description" content="{{ Str::limit(strip_tags($summary), 155) }}"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-detail-live.css')) ?: time() }}" rel="stylesheet"/>
</head>
<body data-page="cup-detail">
<svg aria-hidden="true" class="svg-defs">
<symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.8"></circle><path d="m16.2 16.2 4 4"></path></symbol>
<symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></symbol>
<symbol id="i-sliders" viewBox="0 0 24 24"><path d="M4 7h9M17 7h3M4 17h3M11 17h9M13 4v6M8 14v6"></path></symbol>
<symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.1"></circle><path d="M19 13.6v-3.2l-2-.7-.7-1.7.9-1.9-2.3-2.3-1.9.9-1.7-.7-.7-2H8.4l-.7 2-1.7.7-1.9-.9-2.3 2.3.9 1.9-.7 1.7-2 .7v3.2l2 .7.7 1.7-.9 1.9 2.3 2.3 1.9-.9 1.7.7.7 2h3.2l.7-2 1.7-.7 1.9.9 2.3-2.3-.9-1.9.7-1.7z"></path></symbol>
<symbol id="i-bell" viewBox="0 0 24 24"><path d="M6 9a6 6 0 0 1 12 0c0 7 3 6 3 8H3c0-2 3-1 3-8"></path><path d="M9.5 20h5"></path></symbol>
<symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.4"></circle><path d="M5.5 20a6.5 6.5 0 0 1 13 0"></path></symbol>
<symbol id="i-arrow" viewBox="0 0 24 24"><path d="M7 17 17 7M9 7h8v8"></path></symbol>
<symbol id="i-briefcase" viewBox="0 0 24 24"><rect height="12" rx="3" width="18" x="3" y="7"></rect><path d="M9 7V5h6v2M3 12h18M10 12v2h4v-2"></path></symbol>
<symbol id="i-chevron" viewBox="0 0 24 24"><path d="m8 10 4 4 4-4"></path></symbol>
<symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8.5" r="3"></circle><circle cx="17" cy="9.5" r="2.3"></circle><path d="M3 19a6 6 0 0 1 12 0M14 18a4.5 4.5 0 0 1 7 0"></path></symbol>
<symbol id="i-folder" viewBox="0 0 24 24"><path d="M3 7h7l2 2h9v10H3z"></path><path d="M3 7V5h7l2 2"></path></symbol>
<symbol id="i-check" viewBox="0 0 24 24"><path d="m6 12 4 4 8-8"></path></symbol>
<symbol id="i-comment" viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-8 8H5l-3 2 1-5a8 8 0 1 1 18-5Z"></path></symbol>
<symbol id="i-bookmark" viewBox="0 0 24 24"><path d="M6 3h12v18l-6-4-6 4z"></path></symbol>
<symbol id="i-image" viewBox="0 0 24 24"><rect height="16" rx="3" width="18" x="3" y="4"></rect><circle cx="9" cy="10" r="2"></circle><path d="m5 18 5-5 3 3 2-2 4 4"></path></symbol>
<symbol id="i-eye" viewBox="0 0 24 24"><path d="M2.8 12s3.3-6 9.2-6 9.2 6 9.2 6-3.3 6-9.2 6-9.2-6"></path><circle cx="12" cy="12" r="2.6"></circle></symbol>
<symbol id="i-share" viewBox="0 0 24 24"><circle cx="18" cy="5" r="2.5"></circle><circle cx="6" cy="12" r="2.5"></circle><circle cx="18" cy="19" r="2.5"></circle><path d="m8.2 10.8 7.6-4.5M8.2 13.2l7.6 4.5"></path></symbol>
<symbol id="i-x" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"></path></symbol>
</svg>
<main class="app-shell cup-detail-page-shell"
      data-cup-active-section="{{ $activeSection }}"
      data-cup-share-url="{{ route('cups.show', $cup) }}"
      data-cups-active-url="{{ $statusUrl('active') }}"
      data-cups-mine-url="{{ $mineUrl }}"
      data-cups-submissions-url="{{ $submissionUrl }}"
      data-cups-hall-url="{{ route('hall-of-fame.index') }}">
@include('themes.hnt_preview.partials.header')
<section class="cup-detail-stage">
    <aside class="cup-detail-side cup-detail-left" aria-label="{{ $t('Cup-Daten', 'Cup details') }}">
        <article class="cup-cover-card">
            <img src="{{ $coverUrl }}" alt="{{ $cup->title }}"/>
            <div class="cup-cover-copy">
                <div><span>HNT.ROCKS CUP</span><strong>{{ $cup->title }}</strong><small>{{ $cup->modeLabel() }} · {{ $cup->platform ?: $t('Alle Plattformen', 'All platforms') }}</small></div>
                <button type="button" data-cup-share aria-label="{{ $t('Cup teilen', 'Share cup') }}"><svg><use href="#i-share"></use></svg></button>
            </div>
        </article>
        <section class="cup-data-card">
            <details open>
                <summary>{{ $t('Cup-Daten', 'Cup details') }} <svg><use href="#i-chevron"></use></svg></summary>
                <dl>
                    <div><dt>Status</dt><dd>{{ $cup->statusLabel() }}</dd></div>
                    <div><dt>{{ $t('Modus', 'Mode') }}</dt><dd>{{ $cup->modeLabel() }}</dd></div>
                    <div><dt>{{ $t('Teamgröße', 'Team size') }}</dt><dd>{{ $soloCup ? 1 : $viewerTeamSize }}</dd></div>
                    <div><dt>{{ $t('Maximum', 'Maximum') }}</dt><dd>{{ $participantLimit ?: '—' }} {{ $entryLabel }}</dd></div>
                    <div><dt>{{ $t('Sprache', 'Language') }}</dt><dd>{{ $cup->language ?: 'DE / EN' }}</dd></div>
                </dl>
            </details>
            <details>
                <summary>{{ $t('Einreichungen', 'Submissions') }} <svg><use href="#i-chevron"></use></svg></summary>
                <dl>
                    <div><dt>Uploads</dt><dd>{{ $maxUploads ?: '—' }}</dd></div>
                    <div><dt>Cooldown</dt><dd>{{ config('hunthub.cups.submission_cooldown_minutes', 30) }} Min.</dd></div>
                    <div><dt>{{ $t('Nachweis', 'Proof') }}</dt><dd>Screenshot</dd></div>
                    <div><dt>{{ $t('Prüfung', 'Review') }}</dt><dd>{{ $t('Automatisch + manuell', 'Automatic + manual') }}</dd></div>
                </dl>
            </details>
            <details>
                <summary>{{ $t('Veranstalter', 'Organizer') }} <svg><use href="#i-chevron"></use></svg></summary>
                <div class="cup-organizer">
                    <img src="{{ $cup->owner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""/>
                    <div><strong>{{ $cup->owner?->name ?: 'HNT.ROCKS' }}</strong><span>{{ $cup->owner?->username ? '@'.$cup->owner->username : '@hntrocks' }}</span></div>
                    @if($canManage)<a href="{{ route('cups.edit', $cup) }}" aria-label="{{ $t('Cup bearbeiten', 'Edit cup') }}"><svg><use href="#i-settings"></use></svg></a>@endif
                </div>
            </details>
        </section>
    </aside>

    <div class="cup-detail-scroll" id="cupDetailScroll" tabindex="0">
        @if(session('status') || $errors->any() || session('cup_submission_result'))
            <article class="cup-detail-alert {{ $errors->any() ? 'danger' : '' }}">
                <strong>{{ $errors->any() ? $t('Bitte prüfen', 'Please check') : $t('Hinweis', 'Notice') }}</strong>
                @if($errors->any())
                    @foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach
                @elseif(session('cup_submission_result'))
                    <span>{{ data_get(session('cup_submission_result'), 'message') }}</span>
                @else
                    <span>{{ session('status') }}</span>
                @endif
            </article>
        @endif

        <section class="cup-detail-overview">
            <div class="cup-detail-heading">
                <span>HNT.ROCKS COMMUNITY CUP</span>
                <h1>{{ $cup->title }}</h1>
                <div class="cup-hero-meta">
                    <span class="{{ $statusTone }}"><i></i>{{ $cup->statusLabel() }}</span>
                    <span>{{ $cup->modeLabel() }}</span>
                    <span>{{ $cup->platform ?: $t('Alle Plattformen', 'All platforms') }}</span>
                    <span>{{ $cup->region ?: $t('Alle Regionen', 'All regions') }}</span>
                    <span>{{ $dateRange }}</span>
                </div>
                <p>{{ $summary }}</p>
                <div class="cup-overview-bars">
                    <div class="cup-overview-bar wide"><span>{{ $entryLabel }}</span><div class="dark"><b>{{ $teamCount }} / {{ $participantLimit ?: '∞' }}</b><i style="width:{{ $fillPercent }}%"></i></div></div>
                    <div class="cup-overview-bar"><span>{{ $t('Anmeldung', 'Registration') }}</span><div class="red"><b>{{ $registrationOpen ? $t('Offen', 'Open') : $t('Geschlossen', 'Closed') }}</b><i style="width:{{ $registrationProgress }}%"></i></div></div>
                    <div class="cup-overview-bar"><span>{{ $t('Cup-Fortschritt', 'Cup progress') }}</span><div class="striped"><b>{{ $cupProgress }}%</b><i style="width:{{ $cupProgress }}%"></i></div></div>
                    <div class="cup-overview-bar compact"><span>{{ $t('Einreichungen', 'Submissions') }}</span><div class="outline"><b>{{ $approvedSubmissionCount }} / {{ $cup->submissions->count() }}</b></div></div>
                </div>
            </div>
            <div class="cup-overview-stats">
                <article><strong>{{ $teamCount }}</strong><span>{{ $entryLabel }}</span></article>
                <article><strong>{{ $participantCount }}</strong><span>{{ $t('Hunter', 'Hunters') }}</span></article>
                <article><strong>{{ $approvedSubmissionCount }}</strong><span>Scores</span></article>
            </div>
        </section>

        <section class="cup-center-top-cards">
            <article class="cup-progress-card cup-white-card">
                <header><div><span>{{ $t('ZEITPLAN', 'TIMELINE') }}</span><h2>{{ $t('Cup-Fortschritt', 'Cup progress') }}</h2></div><a href="#cupSections"><svg><use href="#i-arrow"></use></svg></a></header>
                <div class="cup-progress-summary"><strong>{{ $cup->starts_at && $cup->starts_at->isFuture() ? $cup->starts_at->diffForHumans(null, true) : $cup->statusLabel() }}</strong><span>{{ $cup->starts_at && $cup->starts_at->isFuture() ? $t('bis zum Cup-Start', 'until cup start') : $dateRange }}</span><em>{{ $teamCount }} {{ $entryLabel }} {{ $t('bestätigt', 'confirmed') }}</em></div>
                <div class="cup-milestone-bars">
                    <span class="{{ $cup->registration_opens_at?->isPast() ? 'done' : '' }}"><i style="height:38%"></i><b>{{ $cup->registration_opens_at?->format('d.') ?: '—' }}</b><small>{{ $t('Anmeldung', 'Registration') }}</small></span>
                    <span class="{{ $cup->registration_closes_at?->isPast() ? 'done' : '' }}"><i style="height:58%"></i><b>{{ $cup->registration_closes_at?->format('d.') ?: '—' }}</b><small>Team-Lock</small></span>
                    <span class="{{ $cup->status === 'active' ? 'active' : ($cup->starts_at?->isPast() ? 'done' : '') }}"><i style="height:86%"></i><b>{{ $cup->starts_at?->format('d.') ?: '—' }}</b><small>{{ $t('Start', 'Start') }}</small></span>
                    <span class="{{ $cup->status === 'finished' ? 'done' : '' }}"><i style="height:46%"></i><b>{{ $cup->ends_at?->format('d.') ?: '—' }}</b><small>{{ $t('Ende', 'End') }}</small></span>
                </div>
            </article>
            <article class="cup-scoring-card cup-white-card">
                <header><div><span>{{ $t('WERTUNG', 'SCORING') }}</span><h2>Scoring</h2></div><button type="button" data-cup-tab-shortcut="rules"><svg><use href="#i-arrow"></use></svg></button></header>
                <div class="cup-score-ring"><div><strong>{{ config('hunthub.cups.points_per_kill', 1) }}+</strong><span>{{ $t('Kill-Punkt', 'point per kill') }}</span></div></div>
                <div class="cup-scoring-actions"><span>{{ $t('Extraktion erforderlich', 'Extraction required') }}</span><button type="button" data-cup-tab-shortcut="rules"><svg><use href="#i-check"></use></svg></button></div>
            </article>
        </section>

        <article class="cup-section-card cup-white-card" id="cupSections">
            <header class="cup-section-head">
                <div class="cup-section-title"><span>{{ Str::upper($cup->title) }}</span><h2 id="cupSectionTitle">{{ $t('Übersicht', 'Overview') }}</h2></div>
                <nav class="cup-section-tabs" role="tablist" aria-label="{{ $t('Cup-Bereiche', 'Cup sections') }}">
                    @foreach([
                        'overview' => [$t('Übersicht', 'Overview'), $sectionUrl('overview')],
                        'rules' => [$t('Regeln', 'Rules'), $sectionUrl('rules')],
                        'participants' => [$entryLabel, $sectionUrl('participants')],
                        'prizes' => [$t('Preise', 'Prizes'), $sectionUrl('prizes')],
                        'submit' => [$t('Einreichen', 'Submit'), $sectionUrl('submit')],
                        'submissions' => ['Scores', $sectionUrl('submissions')],
                    ] as $key => [$label, $url])
                        <button type="button" data-cup-tab="{{ $key }}" data-title="{{ $label }}" data-url="{{ $url }}" class="{{ $activeSection === $key ? 'active' : '' }}">{{ $label }}</button>
                    @endforeach
                </nav>
            </header>

            <div class="cup-section-panels">
                <section class="cup-tab-panel {{ $activeSection === 'overview' ? 'active' : '' }}" data-cup-panel="overview" @hidden($activeSection !== 'overview')>
                    <article class="cup-description-panel">
                        <div><span>{{ $t('ÜBER DEN CUP', 'ABOUT THE CUP') }}</span><h3>{{ $summary }}</h3>@if(trim($description) !== '')<p>{{ $description }}</p>@endif</div>
                        <div class="cup-description-facts">
                            <span><small>{{ $t('Modus', 'Mode') }}</small><strong>{{ $cup->modeLabel() }}</strong></span>
                            <span><small>{{ $t('Wertung', 'Scoring') }}</small><strong>{{ config('hunthub.cups.points_per_kill', 1) }} {{ $t('Punkt pro Kill', 'point per kill') }}</strong></span>
                            <span><small>{{ $t('Nachweis', 'Proof') }}</small><strong>Screenshot</strong></span>
                            <span><small>{{ $t('Prüfung', 'Review') }}</small><strong>{{ $t('Automatisch + manuell', 'Automatic + manual') }}</strong></span>
                        </div>
                    </article>
                    <div class="cup-live-summary">
                        <article><span>{{ $entryLabel }} {{ $t('bestätigt', 'confirmed') }}</span><strong>{{ $teamCount }}{{ $participantLimit ? ' / '.$participantLimit : '' }}</strong><small>{{ $participantLimit ? max(0, $participantLimit - $teamCount).' '.$t('Plätze frei', 'spots open') : $t('Keine Begrenzung', 'No limit') }}</small></article>
                        <article><span>{{ $t('Einreichungen', 'Submissions') }}</span><strong>{{ $cup->submissions->count() }}</strong><small>{{ $pendingSubmissionCount }} {{ $t('in Prüfung', 'under review') }}</small></article>
                        <article><span>Hunter-Kills</span><strong>{{ $activeTeams->sum('kills_total') }}</strong><small>{{ $t('gesamt gewertet', 'counted total') }}</small></article>
                        <article><span>{{ $t('Bounty', 'Bounty') }}</span><strong>{{ $activeTeams->sum('bounty_tokens_total') }}</strong><small>{{ $t('bestätigt', 'confirmed') }}</small></article>
                    </div>
                    <div class="cup-overview-split">
                        <section class="cup-overview-feed">
                            <header><div><span>{{ $t('LIVE-AKTIVITÄT', 'LIVE ACTIVITY') }}</span><h3>{{ $t('Letzte Einreichungen', 'Recent submissions') }}</h3></div><small>{{ $recentSubmissions->count() }}</small></header>
                            @forelse($recentSubmissions as $submission)
                                <article><img src="{{ $submission->submitter?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""/><div><strong>{{ $submission->team?->displayName() ?: $t('Unbekanntes Team', 'Unknown team') }}</strong><span>{{ $submission->resultSummary() }}</span></div><time>{{ optional($submission->submitted_at ?: $submission->created_at)->diffForHumans() }}</time></article>
                            @empty
                                <p class="cup-empty-copy">{{ $t('Noch keine Einreichungen.', 'No submissions yet.') }}</p>
                            @endforelse
                        </section>
                        <section class="cup-overview-ranking">
                            <header><div><span>TOP 3</span><h3>Leaderboard</h3></div><button type="button" data-cup-tab-shortcut="participants">{{ $t('Alle', 'All') }}</button></header>
                            @forelse($topTeams as $team)
                                <article><b>{{ $loop->iteration }}</b><div class="cup-overview-team"><span class="team-avatars">@foreach($team->members->where('status', 'active')->take(3) as $member)<img src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""/>@endforeach</span><div><strong>{{ $team->displayName() }}</strong><small>{{ $team->kills_total }} Kills · {{ $team->bounty_tokens_total }} Bounty</small></div></div><strong>{{ $team->points_total }}</strong></article>
                            @empty
                                <p class="cup-empty-copy">{{ $t('Noch keine Platzierungen.', 'No rankings yet.') }}</p>
                            @endforelse
                        </section>
                    </div>
                </section>

                <section class="cup-tab-panel {{ $activeSection === 'rules' ? 'active' : '' }}" data-cup-panel="rules" @hidden($activeSection !== 'rules')>
                    <div class="cup-rule-grid">
                        @forelse($rulesSummaryRows as $row)
                            <article><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><strong>{{ $row }}</strong></div></article>
                        @empty
                            @foreach(preg_split('/\R+/', trim($rules ?: $t('Fair Play und nachvollziehbare Nachweise sind Pflicht.', 'Fair play and verifiable proof are required.'))) as $row)
                                @if(trim($row) !== '')<article><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><strong>{{ trim($row) }}</strong></div></article>@endif
                            @endforeach
                        @endforelse
                    </div>
                    <article class="cup-rules-copy"><span>SCORING</span><h3>{{ $t('Wertung im Detail', 'Scoring details') }}</h3><p>{{ $scoringRules }}</p></article>
                </section>

                <section class="cup-tab-panel {{ $activeSection === 'participants' ? 'active' : '' }}" data-cup-panel="participants" @hidden($activeSection !== 'participants')>
                    <div class="cup-leaderboard">
                        <div class="cup-leaderboard-head"><span>#</span><span>{{ $soloCup ? $t('Hunter', 'Hunter') : $t('Team', 'Team') }}</span><span>Kills</span><span>Bounty</span><span>{{ $t('Punkte', 'Points') }}</span></div>
                        @forelse($rankedTeams as $team)
                            <article><b>{{ $loop->iteration }}</b><div><span class="team-avatars">@foreach($team->members->where('status', 'active')->take(3) as $member)<img src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""/>@endforeach</span><strong>{{ $team->displayName() }}</strong></div><span>{{ $team->kills_total }}</span><span>{{ $team->bounty_tokens_total }}</span><strong>{{ $team->points_total }}</strong></article>
                        @empty
                            <p class="cup-empty-copy">{{ $t('Noch keine Teilnehmer.', 'No participants yet.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="cup-tab-panel {{ $activeSection === 'prizes' ? 'active' : '' }}" data-cup-panel="prizes" @hidden($activeSection !== 'prizes')>
                    <div class="cup-prizes-layout">
                        @forelse($prizeRows as $row)
                            <article class="cup-prize-card place-{{ $row['place'] }}"><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><small>{{ Str::upper($row['label']) }}</small><strong>{{ $row['text'] }}</strong></div></article>
                        @empty
                            <article class="cup-prize-card"><span>—</span><div><small>{{ $t('PREISE', 'PRIZES') }}</small><strong>{{ $t('Für diesen Cup wurden noch keine Preise hinterlegt.', 'No prizes have been added for this cup yet.') }}</strong></div></article>
                        @endforelse
                    </div>
                    @if($prizeNotes !== [])<div class="cup-prize-notes">@foreach($prizeNotes as $note)<article><strong>{{ $note['label'] }}</strong><span>{{ $note['text'] }}</span></article>@endforeach</div>@endif
                </section>

                <section class="cup-tab-panel {{ $activeSection === 'submit' ? 'active' : '' }}" data-cup-panel="submit" @hidden($activeSection !== 'submit')>
                    <div class="cup-submit-layout">
                        @if($viewerTeamCanSubmit)
                            <form method="post" action="{{ route('cups.submissions.store', [$cup, $viewerTeam]) }}" enctype="multipart/form-data" class="cup-submit-form" data-hnt-cup-submission-form data-success-redirect="{{ $sectionUrl('submissions') }}">
                                @csrf
                                <label class="cup-upload-zone"><input accept="image/*" type="file" name="screenshot" required data-cup-file/><span><svg><use href="#i-image"></use></svg></span><strong>{{ $t('Screenshot auswählen', 'Choose screenshot') }}</strong><small>JPG / PNG · max. {{ $uploadLimitMb }} MB</small></label>
                                <label class="cup-submit-note"><span>{{ $t('Notiz', 'Note') }}</span><textarea name="note" maxlength="1200" placeholder="{{ $t('Optionale Hinweise zur Runde', 'Optional notes about the round') }}"></textarea></label>
                                <button type="submit">{{ $t('Einreichung senden', 'Submit entry') }}</button>
                            </form>
                            <div class="cup-submit-copy"><span>{{ $t('DEINE NÄCHSTE EINREICHUNG', 'YOUR NEXT SUBMISSION') }}</span><h3>{{ $viewerTeam->displayName() }}</h3><p>{{ $t('Der Screenshot wird analysiert und bei Bedarf manuell geprüft.', 'The screenshot is analyzed and manually reviewed when needed.') }}</p><div><strong>{{ $usedUploads }} / {{ $submissionLimit }}</strong><span>{{ $t('Uploads genutzt', 'uploads used') }}</span></div></div>
                        @else
                            <article class="cup-submit-locked"><span>{{ $t('EINREICHUNG', 'SUBMISSION') }}</span><h3>{{ $submissionClosedReason ?: $t('Teilnahme oder Captain-Rechte erforderlich', 'Participation or captain permission required') }}</h3><p>{{ $viewerTeam ? $t('Prüfe Teamstatus, Vollständigkeit und Einreichungszeitraum.', 'Check team status, completeness and the submission window.') : $t('Tritt dem Cup zuerst bei.', 'Join the cup first.') }}</p>@if(!$viewerTeam && $registrationOpen)<a href="{{ $soloCup ? $sectionUrl('overview') : route('cups.teams.index', $cup) }}">{{ $t('Teilnahme verwalten', 'Manage participation') }}</a>@endif</article>
                        @endif
                    </div>
                </section>

                <section class="cup-tab-panel {{ $activeSection === 'submissions' ? 'active' : '' }}" data-cup-panel="submissions" @hidden($activeSection !== 'submissions')>
                    <div class="cup-submission-list">
                        @forelse($visibleSubmissions as $submission)
                            @php
                                $approved = in_array($submission->status, ['processed', 'approved', 'approved_manual'], true);
                                $review = in_array($submission->status, ['pending', 'review_required'], true);
                                $rejected = in_array($submission->status, ['invalid', 'rejected', 'rejected_manual'], true);
                                $shotReady = $submission->screenshot && $submission->screenshot->status === 'ready';
                            @endphp
                            <article class="{{ $approved ? 'approved' : ($review ? 'review' : ($rejected ? 'rejected' : 'neutral')) }}">
                                <span class="cup-submission-state"><svg><use href="#{{ $approved ? 'i-check' : ($rejected ? 'i-x' : 'i-eye') }}"></use></svg></span>
                                <div><strong>{{ $submission->team?->displayName() ?: $t('Unbekanntes Team', 'Unknown team') }}</strong><small>{{ $submission->resultSummary() }} · {{ optional($submission->submitted_at ?: $submission->created_at)->diffForHumans() }}</small>@if($submission->review_note)<em>{{ $submission->review_note }}</em>@endif</div>
                                <b>{{ $submission->points }} {{ $t('Punkte', 'points') }}</b>
                                <em>{{ $submission->statusLabel() }}</em>
                                <div class="cup-submission-actions">
                                    @if($shotReady)<a href="{{ route('cups.submissions.screenshot', [$cup, $submission]) }}" target="_blank" rel="noopener"><svg><use href="#i-image"></use></svg></a>@endif
                                    @if($canManage && $review)
                                        <form method="post" action="{{ route('cups.submissions.approve', [$cup, $submission]) }}">@csrf<input name="review_note" type="hidden" value=""><button type="submit" class="approve">{{ $t('Freigeben', 'Approve') }}</button></form>
                                        <form method="post" action="{{ route('cups.submissions.reject', [$cup, $submission]) }}">@csrf<input name="review_note" type="hidden" value="{{ $t('Manuell abgelehnt', 'Rejected manually') }}"><button type="submit" class="reject">{{ $t('Ablehnen', 'Reject') }}</button></form>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="cup-empty-copy">{{ $t('Noch keine sichtbaren Einreichungen.', 'No visible submissions yet.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </article>
    </div>

    <aside class="cup-detail-side cup-detail-right" aria-label="{{ $t('Deine Teilnahme', 'Your participation') }}">
        <article class="cup-participation-card">
            <section class="cup-participation-top">
                <header><div><span>{{ $t('TEILNAHME', 'PARTICIPATION') }}</span><h2>{{ $t('Dein Cup', 'Your cup') }}</h2></div><strong>{{ $viewerTeam ? max($teamProgress, $scoreProgress) : 0 }}%</strong></header>
                <div class="cup-participation-segments">
                    <span><b>{{ $teamProgress }}%</b><i style="--progress:{{ $teamProgress }}%"></i><small>{{ $t('Team', 'Team') }}</small></span>
                    <span><b>{{ $viewerTeam && $viewerTeam->status === 'active' ? '100%' : '0%' }}</b><i style="--progress:{{ $viewerTeam && $viewerTeam->status === 'active' ? 100 : 0 }}%"></i><small>{{ $t('Bestätigt', 'Confirmed') }}</small></span>
                    <span><b>{{ $scoreProgress }}%</b><i style="--progress:{{ $scoreProgress }}%"></i><small>Scores</small></span>
                </div>
            </section>
            <section class="cup-my-team">
                @if($viewerTeam)
                    <header><div><span>{{ $soloCup ? $t('DEINE TEILNAHME', 'YOUR ENTRY') : $t('DEIN TEAM', 'YOUR TEAM') }}</span><h2>{{ $viewerTeam->displayName() }}</h2></div><strong>{{ $viewerTeamMemberCount }}/{{ $viewerTeamSize }}</strong></header>
                    <div class="cup-team-members">
                        @foreach($viewerTeamMembers as $member)
                            <article><img src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""/><div><strong>{{ $member->user?->name ?: $member->user?->username ?: $t('Hunter', 'Hunter') }}</strong><span>{{ $viewerTeam->isCaptain($member->user) ? 'Captain' : $t('Mitglied', 'Member') }}</span></div><i class="done"><svg><use href="#i-check"></use></svg></i></article>
                        @endforeach
                        <article class="team-task"><span><svg><use href="#i-image"></use></svg></span><div><strong>{{ $t('Einreichungen', 'Submissions') }}</strong><small>{{ $usedUploads }} / {{ $submissionLimit }}</small></div></article>
                        @if(!$soloCup)<article class="team-task"><span><svg><use href="#i-comment"></use></svg></span><div><strong>Teamchat</strong><small>{{ $viewerTeamChatMessagesCount }} {{ $t('Nachrichten', 'messages') }}</small></div></article>@endif
                    </div>
                    <a class="cup-team-button" href="{{ $soloCup ? $sectionUrl('submit') : route('cups.teams.index', $cup) }}">{{ $soloCup ? $t('Einreichen', 'Submit') : $t('Team verwalten', 'Manage team') }} <svg><use href="#i-arrow"></use></svg></a>
                @else
                    <header><div><span>{{ $t('TEILNAHME', 'PARTICIPATION') }}</span><h2>{{ $registrationOpen ? $t('Jetzt teilnehmen', 'Join now') : $t('Anmeldung geschlossen', 'Registration closed') }}</h2></div></header>
                    <p class="cup-participation-copy">{{ implode(' ', $viewerEligibility['messages'] ?? []) ?: $t('Erstelle ein Team oder tritt einem bestehenden Team bei.', 'Create a team or join an existing one.') }}</p>
                    @if($registrationOpen && ($viewerEligibility['eligible'] ?? false))
                        @if($soloCup)
                            <form method="post" action="{{ route('cups.teams.store', $cup) }}">@csrf<button class="cup-team-button" type="submit">{{ $t('Teilnehmen', 'Join') }}</button></form>
                        @else
                            <a class="cup-team-button" href="{{ route('cups.teams.index', $cup) }}">{{ $t('Team finden oder erstellen', 'Find or create a team') }} <svg><use href="#i-arrow"></use></svg></a>
                        @endif
                    @endif
                @endif
            </section>
        </article>
    </aside>
</section>
<div class="toast" id="toast"></div>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v=20260710-1"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-detail-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-detail-live.js')) ?: time() }}"></script>
</body>
</html>
