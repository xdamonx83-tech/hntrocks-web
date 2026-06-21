@extends('themes.hnt_preview.layouts.app')

@section('title', $cup->title.' · '.__('ui.preview_cup_page_title_suffix'))
@section('robots', ($activeSection ?? 'overview') === 'overview' ? 'index,follow' : 'noindex,follow')
@section('main_class', 'cup-detail-main')

@php
    use Illuminate\Support\Str;

    $viewer = auth()->user();
    $soloCup = $cup->isSoloLeaderboard();
    $activeTeams = $cup->teams->where('status', 'active')->values();
    $teamCount = $activeTeams->count();
    $participantCount = $soloCup ? $teamCount : $cup->teams->sum(fn ($team) => $team->members->where('status', 'active')->count());
    $participantLimit = $cup->participantLimit();
    $entryLabel = $soloCup ? __('ui.cup_participants') : __('ui.teams');
    $visibleSubmissions = $canManage
        ? $cup->submissions
        : ($viewerTeam ? $cup->submissions->where('cup_team_id', $viewerTeam->id) : collect());
    $pendingSubmissionCount = $cup->submissions->whereIn('status', ['pending', 'review_required'])->count();
    $approvedSubmissionCount = $cup->submissions->whereIn('status', ['processed', 'approved', 'approved_manual'])->count();
    $invalidSubmissionCount = $cup->submissions->whereIn('status', ['invalid', 'rejected', 'rejected_manual'])->count();
    $summary = $cup->displaySummary();
    $description = $cup->displayDescription();
    $rules = $cup->displayRules();
    $scoringRules = $cup->displayScoringRules();
    $prizeRows = $cup->prizeRows();
    $prizeNotes = $cup->prizeNotes();
    $rulesSummaryRows = $cup->rulesSummary();
    $registrationOpen = $cup->isRegistrationOpen();
    $submissionOpen = $cup->isSubmissionOpen();
    $submissionClosedReason = $submissionOpen ? null : $cup->submissionClosedReason();
    $viewerEligibility = $viewer ? $cup->participationEligibility($viewer) : ['eligible' => false, 'messages' => [__('ui.cup_requirement_login')]];
    $coverUrl = $cup->coverUrl();
    $dateSource = $cup->starts_at ?: $cup->registration_closes_at ?: $cup->created_at;
    $dateDay = $dateSource ? $dateSource->format('d') : '—';
    $dateMonth = $dateSource ? Str::upper($dateSource->translatedFormat('M')) : 'TBA';
    $dateLine = $cup->starts_at
        ? $cup->starts_at->translatedFormat('D d.m.Y · H:i')
        : ($cup->registration_closes_at ? __('ui.cup_registration_closes_at_short', ['date' => $cup->registration_closes_at->translatedFormat('d.m.Y')]) : $cup->statusLabel());
    $countdownTarget = null;
    $countdownLabel = null;
    if ($cup->starts_at && $cup->starts_at->isFuture()) {
        $countdownTarget = $cup->starts_at;
        $countdownLabel = __('ui.cup_start');
    } elseif ($cup->ends_at && $cup->ends_at->isFuture()) {
        $countdownTarget = $cup->ends_at;
        $countdownLabel = __('ui.cup_end');
    }
    $diff = $countdownTarget ? now()->diff($countdownTarget) : null;
    $countdownBoxes = $diff ? [
        ['value' => str_pad((string) $diff->days, 2, '0', STR_PAD_LEFT), 'label' => __('ui.preview_cup_days')],
        ['value' => str_pad((string) $diff->h, 2, '0', STR_PAD_LEFT), 'label' => __('ui.preview_cup_hours_short')],
        ['value' => str_pad((string) $diff->i, 2, '0', STR_PAD_LEFT), 'label' => __('ui.preview_cup_minutes_short')],
        ['value' => str_pad((string) $diff->s, 2, '0', STR_PAD_LEFT), 'label' => __('ui.preview_cup_seconds_short')],
    ] : [];
    $activeSection = in_array($activeSection ?? 'overview', ['overview', 'rules', 'prizes', 'leaderboard', 'participants', 'submit', 'submissions'], true) ? $activeSection : 'overview';
    // Preview decision: the separate leaderboard tab duplicated the participant/player view.
    // Keep old /leaderboard URLs safe, but show the nicer player/ranking section instead.
    if ($activeSection === 'leaderboard') {
        $activeSection = 'participants';
    }
    $cupSectionUrl = fn (string $section): string => $section === 'overview'
        ? route('cups.show', $cup)
        : route('cups.show.section', [$cup, $section]);
    $navItems = [
        ['href' => $cupSectionUrl('overview'), 'label' => __('ui.cup_tab_overview'), 'active' => $activeSection === 'overview'],
        ['href' => $cupSectionUrl('rules'), 'label' => __('ui.cup_tab_rules'), 'active' => $activeSection === 'rules'],
        ['href' => $cupSectionUrl('participants'), 'label' => $entryLabel, 'active' => $activeSection === 'participants'],
        ['href' => $cupSectionUrl('prizes'), 'label' => __('ui.cup_tab_prizes'), 'active' => $activeSection === 'prizes'],
        ['href' => $cupSectionUrl('submit'), 'label' => __('ui.cup_tab_submit'), 'active' => $activeSection === 'submit'],
        ['href' => $cupSectionUrl('submissions'), 'label' => __('ui.cup_tab_submissions'), 'active' => $activeSection === 'submissions'],
    ];
    $activeNavItem = collect($navItems)->firstWhere('active', true) ?: $navItems[0];
    $rankedParticipants = collect($leaderboard ?? [])->isNotEmpty() ? collect($leaderboard) : $activeTeams;
    $maxUploads = $cup->maxSubmissionsPerParticipant();
    $bestRuns = $cup->maxScoredSubmissionsPerParticipant();
    $uploadLimitMb = max(1, (int) ceil(((int) config('hunthub.upload_limits.cup_submission_screenshot_kb', 10240)) / 1024));
    $profileUrl = static function ($user): string {
        if (! $user?->username) {
            return route('members.index');
        }

        return (int) $user->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $user);
    };
    $viewerTeamMembers = $viewerTeam ? $viewerTeam->members->where('status', 'active')->values() : collect();
    $viewerTeamSize = max(1, (int) ($cup->team_size ?: 1));
    $viewerTeamMemberCount = $viewerTeamMembers->count();
    $viewerIsTeamCaptain = $viewerTeam ? $viewerTeam->isCaptain($viewer) : false;
    $viewerTeamComplete = $soloCup || ($viewerTeam && $viewerTeamMemberCount >= $viewerTeamSize);
    $viewerTeamCanSubmit = $viewerTeam && $viewerTeam->status === 'active' && $submissionOpen && ($soloCup || ($viewerIsTeamCaptain && $viewerTeamComplete));
    $viewerTeamInviteUrl = ($viewerTeam && ! $soloCup) ? route('cups.teams.join', [$cup, $viewerTeam->join_token]) : null;
    $viewerTeamChatMessages = $viewerTeamChatMessages ?? collect();
    $viewerTeamChatMessagesCount = $viewerTeamChatMessagesCount ?? 0;
    $cupRandomizerDraws = $cupRandomizerDraws ?? collect();
    $cupRandomizerEligibleTeams = $cupRandomizerEligibleTeams ?? collect();
    $cupAwardsExampleUrl = asset('assets/themes/hnt_preview/images/cups/hunt-awards-submit-example.jpg');
@endphp

@section('content')
    <div class="cup-detail-shell">
        @if(session('status') || $errors->any() || session('cup_submission_result'))
            <article class="cup-panel" style="margin-bottom: 18px;">
                @if($errors->any())
                    <h2>{{ __('ui.please_check') }}</h2>
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                @elseif(session('cup_submission_result'))
                    @php
                        $result = session('cup_submission_result');
                    @endphp
                    <h2>{{ $result['title'] ?? __('ui.cup_submission_processed') }}</h2>
                    <p>{{ $result['message'] ?? __('ui.cup_submission_result_text') }}</p>
                    <p>{{ $result['status'] ?? '' }} {{ $result['score'] ?? '' }}</p>
                @else
                    <h2>{{ __('ui.note') }}</h2>
                    <p>{{ session('status') }}</p>
                @endif
            </article>
        @endif

        <section class="cup-hero-card">
            <div class="cup-cover-art" style="background-image: linear-gradient(180deg, rgba(26,26,24,0.08), rgba(26,26,24,0.72)), url('{{ $coverUrl }}');">
                <div class="cup-cover-shade"></div>
                <div class="cup-date-tile" aria-label="{{ __('ui.preview_cup_date_aria') }}">
                    <span>{{ $dateMonth }}</span>
                    <strong>{{ $dateDay }}</strong>
                </div>
                <div class="cup-cover-actions">
                    <a class="btn-create" href="{{ route('cups.index') }}">
                        <i class="ph ph-trophy" aria-hidden="true"></i>
                        Cups
                    </a>
                    @if($canManage)
                        <a class="btn-create" href="{{ route('cups.edit', $cup) }}">
                            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                            {{ __('ui.preview_cup_edit') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="cup-hero-body">
                <div class="cup-title-block">
                    <p class="cup-meta">{{ Str::upper($dateLine) }}</p>
                    <h1>{{ $cup->title }}</h1>
                    <div class="cup-info-line">
                        <span class="cup-status">{{ $cup->statusLabel() }}</span>
                        <span>{{ $cup->modeLabel() }}</span>
                        <span>{{ $cup->platform ?: __('ui.cup_all_platforms') }}</span>
                        <span>{{ $cup->region ?: __('ui.cup_all_regions') }}</span>
                    </div>
                </div>

                <div class="cup-countdown" aria-label="{{ __('ui.preview_cup_countdown_aria') }}">
                    <span class="countdown-label">{{ $countdownLabel ?: __('ui.preview_cup_status') }}</span>
                    <div class="countdown-boxes">
                        @forelse($countdownBoxes as $box)
                            <div><strong>{{ $box['value'] }}</strong><span>{{ $box['label'] }}</span></div>
                        @empty
                            <div><strong>{{ $teamCount }}</strong><span>{{ $entryLabel }}</span></div>
                            <div><strong>{{ $approvedSubmissionCount }}</strong><span>{{ __('ui.preview_cup_scores') }}</span></div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="cup-tabs-row">
                <details class="cup-section-menu">
                    <summary>
                        <span>{{ $activeNavItem['label'] }}</span>
                        <i class="ph ph-caret-down" aria-hidden="true"></i>
                    </summary>
                    <div class="cup-section-options" role="menu" aria-label="{{ __('ui.preview_cup_sections_aria') }}">
                        @foreach($navItems as $item)
                            <a href="{{ $item['href'] }}" @class(['active' => $item['active']]) role="menuitem">{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                </details>
                <div class="cup-join-actions">
                    @auth
                        @if($viewerTeam && $viewerTeam->status === 'active')
                            @if($soloCup)
                                <form method="post" action="{{ route('cups.teams.leave', [$cup, $viewerTeam]) }}">
                                    @csrf
                                    <button class="btn-create" type="submit">{{ __('ui.preview_cup_leave') }}</button>
                                </form>
                            @else
                                <a class="btn-create" href="{{ route('cups.teams.index', $cup) }}">{{ __('ui.cup_team_nav_button') }}</a>
                            @endif
                        @elseif($registrationOpen && ($viewerEligibility['eligible'] ?? false))
                            @if($soloCup)
                                <form method="post" action="{{ route('cups.teams.store', $cup) }}">
                                    @csrf
                                    <button class="btn-create" type="submit">{{ __('ui.preview_cup_join_single') }}</button>
                                </form>
                            @else
                                <a class="btn-create" href="{{ route('cups.teams.index', $cup) }}">{{ __('ui.preview_cup_join_team') }}</a>
                            @endif
                        @else
                            <button class="btn-following" type="button" disabled>{{ __('ui.preview_cup_join_closed') }}</button>
                        @endif
                    @else
                        <a class="btn-create" href="{{ route('login') }}">{{ __('ui.preview_cup_login') }}</a>
                    @endauth
                    <a class="cup-icon-btn" href="{{ route('hall-of-fame.index') }}" aria-label="{{ __('ui.preview_cup_hof_aria') }}"><i class="ph ph-circle" aria-hidden="true"></i></a>
                </div>
            </div>
        </section>

        @if($activeSection === 'overview')
            <section class="cup-content-grid">
                <article class="cup-panel cup-overview-panel">
                    <h2>{{ __('ui.cup_tab_overview') }}</h2>
                    <p class="cup-lead">{{ $summary }}</p>
                    @if(trim($description) !== '')
                        @foreach(preg_split('/\R{2,}/', trim($description)) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    @endif
                </article>

                <aside class="cup-side-stack">
                    <article class="cup-panel cup-data-panel">
                        <h2>{{ __('ui.preview_cup_data') }}</h2>
                        <ul class="cup-data-list">
                            <li><i class="ph ph-circle" aria-hidden="true"></i><span><strong>{{ $cup->statusLabel() }}</strong> · {{ $cup->visibilityLabel() }}</span></li>
                            <li><i class="ph ph-users" aria-hidden="true"></i><span><strong>{{ $participantCount }}</strong> {{ $entryLabel }} @if($participantLimit) · {{ __('ui.preview_cup_max_short') }} {{ $participantLimit }} @endif</span></li>
                            <li><i class="ph ph-image-square" aria-hidden="true"></i><span><strong>{{ $approvedSubmissionCount }}</strong> {{ __('ui.preview_cup_counted') }} · <strong>{{ $pendingSubmissionCount }}</strong> {{ __('ui.preview_cup_open') }} · <strong>{{ $invalidSubmissionCount }}</strong> {{ __('ui.preview_cup_invalid') }}</span></li>
                            <li><i class="ph ph-circle" aria-hidden="true"></i><span>{{ $cup->platform ?: __('ui.cup_all_platforms') }} · {{ $cup->region ?: __('ui.cup_all_regions') }}</span></li>
                        </ul>
                    </article>

                    <article class="cup-panel cup-join-panel">
                        <h2>{{ $viewerTeam ? __('ui.preview_cup_joined_title') : __('ui.preview_cup_join_title') }}</h2>
                        @if($viewerTeam)
                            <p>{{ $soloCup ? __('ui.preview_cup_registered_solo') : __('ui.preview_cup_registered_team') }}</p>
                            @if($soloCup)
                                <a class="btn-create cup-panel-btn" href="{{ $cupSectionUrl('submit') }}">{{ __('ui.preview_cup_submit_screenshot') }}</a>
                            @else
                                <a class="btn-create cup-panel-btn" href="{{ route('cups.teams.index', $cup) }}">{{ __('ui.cup_team_nav_button') }}</a>
                                <a class="btn-following cup-panel-btn cup-panel-btn-secondary" href="{{ route('cups.teams.index', $cup) }}#cup-team-finder">{{ __('ui.cup_team_finder_nav_button') }}</a>
                            @endif
                        @elseif(! $soloCup && $registrationOpen && ($viewerEligibility['eligible'] ?? false))
                            <p>{{ __('ui.cup_team_page_empty_text') }}</p>
                            <a class="btn-create cup-panel-btn" href="{{ route('cups.teams.index', $cup) }}">{{ __('ui.preview_cup_join_team') }}</a>
                            <a class="btn-following cup-panel-btn cup-panel-btn-secondary" href="{{ route('cups.teams.index', $cup) }}#cup-team-finder">{{ __('ui.cup_team_finder_nav_button') }}</a>
                        @else
                            <p>{{ __('ui.preview_cup_login_when_registration') }}</p>
                        @endif
                    </article>
                </aside>
            </section>

            <section class="cup-content-grid hnt-cup-chat-grid">
                <article class="cup-panel hnt-cup-chat-panel" id="cup-chat" data-cup-chat-panel data-cup-chat-url="{{ route('cups.chat.index', $cup) }}" data-cup-chat-empty-text="{{ __('ui.cup_chat_empty') }}">
                    <div class="hnt-cup-chat-head">
                        <div>
                            <span>{{ __('ui.cup_tab_overview') }}</span>
                            <h2>{{ __('ui.cup_chat_title') }}</h2>
                        </div>
                        <strong data-cup-chat-count>{{ $cupChatMessagesCount }}</strong>
                    </div>

                    <div class="hnt-cup-chat-list" data-cup-chat-list>
                        @forelse($cupChatMessages as $chatMessage)
                            @include('themes.hnt_preview.cups.partials.chat-message', ['chatMessage' => $chatMessage])
                        @empty
                            <p class="hnt-cup-chat-empty" data-cup-chat-empty>{{ __('ui.cup_chat_empty') }}</p>
                        @endforelse
                    </div>

                    <div class="hnt-cup-chat-compose">
                        @auth
                            <form method="post" action="{{ route('cups.chat.store', $cup) }}" data-cup-chat-form data-error-text="{{ __('ui.cup_chat_send_failed') }}">
                                @csrf
                                <span class="hnt-cup-chat-avatar hnt-avatar-shell" aria-hidden="true">
                                    <img src="{{ auth()->user()?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt="">
                                </span>
                                <input name="body" type="text" maxlength="1200" required placeholder="{{ __('ui.cup_chat_placeholder') }}" autocomplete="off" data-cup-chat-input>
                                <button class="btn-create" type="submit" data-loading-text="{{ __('ui.preview_loading_short') }}">{{ __('ui.cup_chat_send') }}</button>
                            </form>
                            <p class="hnt-cup-chat-status" data-cup-chat-status hidden></p>
                        @else
                            <div class="hnt-cup-chat-login">
                                <span>{{ __('ui.cup_chat_login_text') }}</span>
                                <a class="btn-create" href="{{ route('login') }}">{{ __('ui.cup_chat_login_action') }}</a>
                            </div>
                        @endauth
                    </div>
                </article>
            </section>

            <section class="cup-content-grid compact">
                <article class="cup-panel">
                    <h2>{{ __('ui.preview_cup_scoring') }}</h2>
                    <div class="cup-score-list">
                        <div><strong>{{ config('hunthub.cups.points_per_bounty_token', 2) }} {{ __('ui.preview_cup_points') }}</strong><span>{{ __('ui.preview_cup_per_bounty') }}</span></div>
                        <div><strong>{{ config('hunthub.cups.points_per_kill', 1) }} {{ __('ui.preview_cup_point') }}</strong><span>{{ __('ui.preview_cup_per_kill') }}</span></div>
                        @if($maxUploads)<div><strong>{{ __('ui.preview_cup_max_uploads', ['count' => $maxUploads]) }}</strong><span>{{ __('ui.preview_cup_max_per_participant') }}</span></div>@endif
                        @if($bestRuns)<div><strong>{{ __('ui.preview_cup_best_runs', ['count' => $bestRuns]) }}</strong><span>{{ __('ui.preview_cup_best_runs_leaderboard') }}</span></div>@endif
                    </div>
                </article>
                <article class="cup-panel">
                    <h2>{{ __('ui.cup_tab_prizes') }}</h2>
                    @forelse($prizeRows as $row)
                        <p><strong>{{ $row['label'] }}:</strong> {{ $row['text'] }}</p>
                    @empty
                        <p>{{ __('ui.preview_cup_no_prizes') }}</p>
                    @endforelse
                </article>
            </section>
        @elseif($activeSection === 'rules')
            <section class="cup-content-grid">
                <article class="cup-panel">
                    <h2>{{ __('ui.cup_tab_rules') }}</h2>
                    @foreach(preg_split('/\R{2,}/', trim($rules ?: __('ui.preview_cup_rules_default'))) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </article>
                <aside class="cup-side-stack">
                    <article class="cup-panel">
                        <h2>{{ __('ui.preview_cup_summary') }}</h2>
                        <ul class="cup-data-list">
                            @forelse($rulesSummaryRows as $row)
                                <li><i class="ph ph-check" aria-hidden="true"></i><span>{{ $row }}</span></li>
                            @empty
                                <li><i class="ph ph-circle" aria-hidden="true"></i><span>{{ __('ui.preview_cup_rules_default') }}</span></li>
                            @endforelse
                        </ul>
                    </article>
                </aside>
            </section>
            <section class="cup-content-grid compact">
                <article class="cup-panel cup-scoring-panel">
                    <h2>{{ __('ui.preview_cup_scoring_title') }}</h2>
                    @if(trim($scoringRules) !== '')
                        @foreach(preg_split('/\R{2,}/', trim($scoringRules)) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    @else
                        <p>{{ __('ui.preview_cup_scoring_extracts') }}</p>
                    @endif
                </article>
                <article class="cup-panel">
                    <h2>{{ __('ui.preview_cup_scoring') }}</h2>
                    <div class="cup-score-list">
                        <div><strong>{{ config('hunthub.cups.points_per_bounty_token', 2) }} {{ __('ui.preview_cup_points') }}</strong><span>{{ __('ui.preview_cup_per_bounty') }}</span></div>
                        <div><strong>{{ config('hunthub.cups.points_per_kill', 1) }} {{ __('ui.preview_cup_point') }}</strong><span>{{ __('ui.preview_cup_per_kill') }}</span></div>
                        @if($maxUploads)<div><strong>{{ __('ui.preview_cup_max_uploads', ['count' => $maxUploads]) }}</strong><span>{{ __('ui.preview_cup_max_per_participant') }}</span></div>@endif
                        @if($bestRuns)<div><strong>{{ __('ui.preview_cup_best_runs', ['count' => $bestRuns]) }}</strong><span>{{ __('ui.preview_cup_best_runs_ranking') }}</span></div>@endif
                    </div>
                </article>
            </section>
        @elseif($activeSection === 'prizes')
            <section class="cup-content-grid compact">
                @forelse($prizeRows as $row)
                    <article class="cup-panel">
                        <h2>{{ $row['label'] }}</h2>
                        <p>{{ $row['text'] }}</p>
                    </article>
                @empty
                    <article class="cup-panel">
                        <h2>{{ __('ui.cup_tab_prizes') }}</h2>
                        <p>{{ __('ui.preview_cup_no_prizes') }}</p>
                    </article>
                @endforelse
            </section>
            @if($prizeNotes !== [])
                <section class="cup-content-grid compact">
                    @foreach($prizeNotes as $note)
                        <article class="cup-panel">
                            <h2>{{ $note['label'] }}</h2>
                            <p>{{ $note['text'] }}</p>
                        </article>
                    @endforeach
                </section>
            @endif
        @elseif($activeSection === 'participants')
            <section class="cup-content-grid compact cup-player-grid">
                @forelse($rankedParticipants as $team)
                    @php
                        $owner = $team->owner;
                    @endphp
                    <article class="lfg-card cup-player-card">
                        <span class="cup-player-rank">#{{ $loop->iteration }}</span>
                        <div class="lfg-user">
                            <span class="avatar lfg-avatar hnt-avatar-shell">
                                @if($owner?->avatarUrl())<img src="{{ $owner->avatarUrl() }}" alt="{{ $team->displayName() }}">@endif
                            </span>
                            <div>
                                <h2>{{ $soloCup ? $team->displayName() : $team->name }}</h2>
                                <p>{{ $team->points_total }} {{ __('ui.preview_cup_points') }} · {{ $team->kills_total }} {{ __('ui.preview_profile_kills') }} · {{ $team->bounty_tokens_total }} {{ __('ui.preview_profile_bounty') }}</p>
                            </div>
                        </div>
                        @if($owner)
                            <a class="btn-create" href="{{ $profileUrl($owner) }}">{{ __('ui.preview_cup_profile') }}</a>
                        @endif
                        <div class="lfg-tags">
                            <span>{{ $team->statusLabel() }}</span>
                            <span>{{ $team->submissions_approved_count }} {{ __('ui.preview_cup_scores') }}</span>
                            @if($team->points_total > 0)<span>{{ __('ui.preview_cup_ranking') }}</span>@endif
                        </div>
                    </article>
                @empty
                    <article class="cup-panel" style="grid-column: 1 / -1;">
                        <h2>{{ $entryLabel }}</h2>
                        <p>{{ __('ui.preview_cup_no_participants') }}</p>
                    </article>
                @endforelse
            </section>
        @elseif($activeSection === 'submit')
            <section class="cup-content-grid">
                <article class="cup-panel">
                    <h2>{{ __('ui.cup_tab_submit') }}</h2>
                    @auth
                        @if($viewerTeam && ! $submissionOpen)
                            <h3>{{ __('ui.cup_submit_not_open_title') }}</h3>
                            <p>{{ $submissionClosedReason }}</p>
                            @if($viewerTeam && ! $soloCup)
                                <a class="btn-create cup-panel-btn" href="{{ route('cups.teams.index', $cup) }}">{{ __('ui.cup_team_nav_button') }}</a>
                            @endif
                        @elseif($viewerTeamCanSubmit)
                            <p>{{ __('ui.preview_cup_submit_hint', ['mb' => $uploadLimitMb]) }}</p>
                            <form method="post" action="{{ route('cups.submissions.store', [$cup, $viewerTeam]) }}" enctype="multipart/form-data" class="profile-edit-form hnt-cup-submit-form" data-hnt-cup-submission-form data-success-redirect="{{ route('cups.show.section', [$cup, 'submissions']) }}">
                                @csrf
                                <div class="feedback-field hnt-cup-file-field">
                                    <span>{{ __('ui.preview_cup_screenshot') }}</span>
                                    <label class="hnt-cup-file-picker">
                                        <input class="hnt-cup-file-native" type="file" name="screenshot" accept="image/*" required data-hnt-cup-screenshot-input>
                                        <span class="hnt-cup-file-picker-button">
                                            <i class="ph ph-plus" aria-hidden="true"></i>
                                            {{ __('ui.preview_cup_choose_file') }}
                                        </span>
                                        <span class="hnt-cup-file-name" data-hnt-cup-file-name>{{ __('ui.preview_cup_no_screenshot') }}</span>
                                    </label>
                                </div>
                                <div class="feedback-field">
                                    <span>{{ __('ui.preview_cup_note') }}</span>
                                    <textarea name="note" placeholder="{{ __('ui.preview_cup_note_placeholder') }}"></textarea>
                                </div>
                                <button class="btn-create" type="submit" data-hnt-cup-submit-button>{{ __('ui.preview_cup_submit') }}</button>
                            </form>
                        @elseif($viewerTeam && $viewerTeam->status === 'active' && ! $soloCup && ! $viewerTeamComplete)
                            <h3>{{ __('ui.cup_submit_team_incomplete_title') }}</h3>
                            <p>{{ __('ui.cup_submit_team_incomplete_text', ['count' => $viewerTeamMemberCount, 'size' => $viewerTeamSize]) }}</p>
                            <a class="btn-create cup-panel-btn" href="{{ route('cups.teams.index', $cup) }}">{{ __('ui.cup_team_nav_button') }}</a>
                        @elseif($viewerTeam && $viewerTeam->status === 'active' && ! $soloCup && ! $viewerIsTeamCaptain)
                            <h3>{{ __('ui.cup_submit_captain_only_title') }}</h3>
                            <p>{{ __('ui.cup_submit_captain_only_text') }}</p>
                            <a class="btn-create cup-panel-btn" href="{{ route('cups.teams.index', $cup) }}">{{ __('ui.cup_team_nav_button') }}</a>
                        @elseif($viewerTeam)
                            <p>{{ __('ui.preview_cup_participation_inactive') }}</p>
                        @else
                            <p>{{ __('ui.preview_cup_join_before_submit') }}</p>
                        @endif
                    @else
                        <p>{{ __('ui.preview_cup_login_to_submit') }}</p>
                        <a class="btn-create" href="{{ route('login') }}">{{ __('ui.preview_cup_login') }}</a>
                    @endauth
                </article>
                <aside class="cup-side-stack">
                    <article class="cup-panel hnt-cup-example-panel">
                        <h2>{{ __('ui.preview_cup_example_title') }}</h2>
                        <p>{{ __('ui.preview_cup_example_text') }}</p>
                        <a class="hnt-cup-example-thumb" href="#cup-submit-example-modal" aria-label="{{ __('ui.preview_cup_example_zoom') }}">
                            <img src="{{ $cupAwardsExampleUrl }}" alt="{{ __('ui.preview_cup_example_alt') }}">
                            <span class="hnt-cup-example-thumb-badge">{{ __('ui.preview_cup_example_zoom') }}</span>
                        </a>
                    </article>
                    <article class="cup-panel">
                        <h2>{{ __('ui.preview_cup_review_title') }}</h2>
                        <p>{{ __('ui.preview_cup_review_text') }}</p>
                    </article>
                </aside>
            </section>

            <div id="cup-submit-example-modal" class="hnt-cup-lightbox" aria-hidden="true">
                <a class="hnt-cup-lightbox-backdrop" href="#" aria-label="{{ __('ui.preview_cup_example_close') }}"></a>
                <div class="hnt-cup-lightbox-dialog" role="dialog" aria-modal="true" aria-label="{{ __('ui.preview_cup_example_title') }}">
                    <div class="hnt-cup-lightbox-head">
                        <div>
                            <strong>{{ __('ui.preview_cup_example_title') }}</strong>
                            <span>{{ __('ui.preview_cup_example_caption') }}</span>
                        </div>
                        <a class="hnt-cup-lightbox-close" href="#" aria-label="{{ __('ui.preview_cup_example_close') }}"><i class="ph ph-x" aria-hidden="true"></i></a>
                    </div>
                    <img src="{{ $cupAwardsExampleUrl }}" alt="{{ __('ui.preview_cup_example_alt') }}">
                </div>
            </div>
        @elseif($activeSection === 'submissions')
            <section class="cup-content-grid">
                <article class="cup-panel hnt-cup-submissions-panel" style="grid-column: 1 / -1;">
                    <div class="hnt-cup-section-head">
                        <div>
                            <span>{{ __('ui.cup_submission_kicker') }}</span>
                            <h2>{{ __('ui.cup_tab_submissions') }}</h2>
                            <p>{{ __('ui.cup_submission_stats', ['approved' => $approvedSubmissionCount, 'pending' => $pendingSubmissionCount, 'invalid' => $invalidSubmissionCount]) }}</p>
                        </div>
                        @if($canManage)
                            <strong>{{ __('ui.cup_admin_review_badge') }}</strong>
                        @endif
                    </div>

                    @if($canManage)
                        <div class="hnt-cup-randomizer-panel">
                            <div class="hnt-cup-randomizer-main">
                                <div>
                                    <span>{{ __('ui.cup_randomizer_kicker') }}</span>
                                    <h3>{{ __('ui.cup_randomizer_title') }}</h3>
                                    <p>{{ __('ui.cup_randomizer_text') }}</p>
                                </div>
                                <div class="hnt-cup-randomizer-stats">
                                    <span><strong>{{ $cupRandomizerEligibleTeams->count() }}</strong>{{ __('ui.cup_randomizer_eligible_teams') }}</span>
                                    <span><strong>{{ $cupRandomizerDraws->count() }}</strong>{{ __('ui.cup_randomizer_draws') }}</span>
                                </div>
                            </div>

                            <form method="post" action="{{ route('cups.randomizer.draws.store', $cup) }}" class="hnt-cup-randomizer-form" onsubmit="return confirm('{{ __('ui.cup_randomizer_confirm') }}')">
                                @csrf
                                <label>
                                    <span>{{ __('ui.cup_randomizer_prize_label') }}</span>
                                    <input name="prize_label" type="text" maxlength="180" value="{{ old('prize_label', __('ui.cup_randomizer_default_prize')) }}" placeholder="{{ __('ui.cup_randomizer_prize_placeholder') }}">
                                </label>
                                <label>
                                    <span>{{ __('ui.cup_randomizer_notes_label') }}</span>
                                    <input name="notes" type="text" maxlength="1200" value="{{ old('notes') }}" placeholder="{{ __('ui.cup_randomizer_notes_placeholder') }}">
                                </label>
                                <button class="btn-create" type="submit" @disabled($cupRandomizerEligibleTeams->isEmpty())>{{ __('ui.cup_randomizer_draw_button') }}</button>
                            </form>

                            @if($cupRandomizerEligibleTeams->isEmpty())
                                <p class="hnt-cup-randomizer-hint">{{ __('ui.cup_randomizer_no_eligible_teams') }}</p>
                            @else
                                <div class="hnt-cup-randomizer-eligible">
                                    <span>{{ __('ui.cup_randomizer_eligible_preview') }}</span>
                                    @foreach($cupRandomizerEligibleTeams->take(6) as $eligibleTeam)
                                        <strong>{{ $eligibleTeam->displayName() }}</strong>
                                    @endforeach
                                    @if($cupRandomizerEligibleTeams->count() > 6)
                                        <em>+{{ $cupRandomizerEligibleTeams->count() - 6 }}</em>
                                    @endif
                                </div>
                            @endif

                            @if($cupRandomizerDraws->isNotEmpty())
                                <div class="hnt-cup-randomizer-history">
                                    <h4>{{ __('ui.cup_randomizer_history_title') }}</h4>
                                    @foreach($cupRandomizerDraws as $draw)
                                        <article>
                                            <div>
                                                <strong>{{ $draw->winnerName() }}</strong>
                                                <span>{{ $draw->prize_label ?: __('ui.cup_randomizer_default_prize') }}</span>
                                            </div>
                                            <p>{{ __('ui.cup_randomizer_history_meta', ['count' => $draw->eligible_team_count, 'date' => optional($draw->created_at)->translatedFormat('d.m.Y H:i')]) }}</p>
                                            @if($draw->notes)
                                                <p>{{ $draw->notes }}</p>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="hnt-cup-submission-list">
                        @forelse($visibleSubmissions as $submission)
                            @php
                                $screenshotReady = $submission->screenshot && $submission->screenshot->status === 'ready';
                                $screenshotUrl = $screenshotReady ? route('cups.submissions.screenshot', [$cup, $submission]) : null;
                                $submissionTeamName = $submission->team ? ($soloCup ? $submission->team->displayName() : $submission->team->name) : __('ui.cup_unknown');
                                $submitterName = $submission->submitter?->username ?: $submission->submitter?->name ?: __('ui.preview_hnt_hunter');
                                $statusClass = match ($submission->status) {
                                    'processed', 'approved', 'approved_manual' => 'is-approved',
                                    'pending', 'review_required' => 'is-review',
                                    'invalid', 'rejected', 'rejected_manual' => 'is-rejected',
                                    default => 'is-neutral',
                                };
                                $aiConfidence = is_numeric($submission->ai_confidence) ? round(((float) $submission->ai_confidence) * 100) : null;
                            @endphp

                            <article class="hnt-cup-submission-card {{ $statusClass }}">
                                <div class="hnt-cup-submission-main">
                                    <div class="hnt-cup-submission-id">#{{ $submission->id }}</div>
                                    <div class="hnt-cup-submission-info">
                                        <div class="hnt-cup-submission-title-row">
                                            <h3>{{ $submissionTeamName }}</h3>
                                            <span>{{ $submission->statusLabel() }}</span>
                                        </div>
                                        <p>{{ __('ui.cup_submitted_by', ['name' => $submitterName]) }} · {{ optional($submission->submitted_at ?: $submission->created_at)->diffForHumans() }}</p>
                                        <p>{{ $submission->resultSummary() }}</p>

                                        <div class="hnt-cup-submission-metrics">
                                            <span><strong>{{ $submission->points }}</strong>{{ __('ui.preview_cup_points_short') }}</span>
                                            <span><strong>{{ $submission->kills }}</strong>{{ __('ui.preview_cup_kills_short') }}</span>
                                            <span><strong>{{ $submission->bounty_tokens }}</strong>{{ __('ui.preview_cup_bounty_short') }}</span>
                                            <span><strong>{{ $submission->extracted ? __('ui.cup_admin_yes') : __('ui.cup_admin_no') }}</strong>{{ __('ui.cup_table_extract') }}</span>
                                        </div>

                                        @if($submission->invalidReasonLabel() || $submission->review_note || $submission->ai_gamertag || $submission->ai_gamertag_mismatch)
                                            <div class="hnt-cup-submission-flags">
                                                @if($submission->invalidReasonLabel())
                                                    <span>{{ $submission->invalidReasonLabel() }}</span>
                                                @endif
                                                @if($submission->review_note)
                                                    <span>{{ __('ui.cup_review_note_label') }}: {{ $submission->review_note }}</span>
                                                @endif
                                                @if($submission->ai_gamertag)
                                                    <span>{{ __('ui.cup_submission_gamertag_detected', ['name' => $submission->ai_gamertag]) }}</span>
                                                @endif
                                                @if($canManage && $submission->ai_gamertag_mismatch && $submission->team?->detected_gamertag)
                                                    <span>{{ __('ui.cup_submission_gamertag_expected', ['name' => $submission->team->detected_gamertag]) }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="hnt-cup-submission-actions">
                                        @if($screenshotUrl)
                                            <a class="cup-submission-shot-link" href="{{ $screenshotUrl }}" target="_blank" rel="noopener">
                                                <i class="ph ph-image-square" aria-hidden="true"></i>
                                                <span>{{ __('ui.preview_cup_screenshot') }}</span>
                                            </a>
                                        @else
                                            <span class="cup-submission-shot-missing">{{ __('ui.preview_cup_no_screenshot_short') }}</span>
                                        @endif
                                    </div>
                                </div>

                                @if($canManage)
                                    <div class="hnt-cup-review-box">
                                        <div class="hnt-cup-review-meta">
                                            <span>{{ __('ui.cup_admin_ai_meta') }}: {{ $submission->screen_type ?: __('ui.cup_unknown') }}</span>
                                            <span>{{ __('ui.cup_admin_ai_confidence') }}: {{ $aiConfidence !== null ? $aiConfidence.'%' : __('ui.cup_unknown') }}</span>
                                            <span>{{ __('ui.cup_admin_complete_screenshot') }}: {{ $submission->ai_complete_screenshot === null ? __('ui.cup_unknown') : ($submission->ai_complete_screenshot ? __('ui.cup_admin_yes') : __('ui.cup_admin_no')) }}</span>
                                            @if($submission->ai_suspected_tampering)
                                                <span>{{ __('ui.cup_submission_reason_tampering_suspected') }}</span>
                                            @endif
                                        </div>

                                        @if(in_array($submission->status, ['pending', 'review_required'], true))
                                            <div class="hnt-cup-review-quick-actions">
                                                <form method="post" action="{{ route('cups.submissions.approve', [$cup, $submission]) }}">
                                                    @csrf
                                                    <button class="btn-create" type="submit">{{ __('ui.cup_approve') }}</button>
                                                </form>
                                                <form method="post" action="{{ route('cups.submissions.reject', [$cup, $submission]) }}" class="hnt-cup-inline-form">
                                                    @csrf
                                                    <input name="review_note" type="text" maxlength="1200" placeholder="{{ __('ui.cup_review_reason_placeholder') }}">
                                                    <button class="hnt-cup-danger-btn" type="submit">{{ __('ui.cup_reject') }}</button>
                                                </form>
                                            </div>
                                        @endif

                                        <details class="hnt-cup-review-details">
                                            <summary>{{ __('ui.cup_manual_score_title') }}</summary>
                                            <form method="post" action="{{ route('cups.submissions.manual-score', [$cup, $submission]) }}" class="hnt-cup-score-form">
                                                @csrf
                                                <label>
                                                    <span>{{ __('ui.cup_table_kills') }}</span>
                                                    <input name="kills" type="number" min="0" max="99" value="{{ old('kills', $submission->kills) }}" placeholder="{{ __('ui.cup_manual_kills_placeholder') }}">
                                                </label>
                                                <label>
                                                    <span>{{ __('ui.cup_table_token') }}</span>
                                                    <input name="bounty_tokens" type="number" min="0" max="4" value="{{ old('bounty_tokens', $submission->bounty_tokens) }}" placeholder="{{ __('ui.cup_manual_bounty_placeholder') }}">
                                                </label>
                                                <label>
                                                    <span>{{ __('ui.cup_table_points') }}</span>
                                                    <input name="points" type="number" min="0" max="999" value="{{ old('points', $submission->points) }}" placeholder="{{ __('ui.cup_manual_points_placeholder') }}">
                                                </label>
                                                <label class="hnt-cup-score-note">
                                                    <span>{{ __('ui.cup_manual_note_placeholder') }}</span>
                                                    <input name="review_note" type="text" maxlength="1200" placeholder="{{ __('ui.cup_manual_note_placeholder') }}">
                                                </label>
                                                <button class="btn-create" type="submit">{{ __('ui.cup_manual_score_save') }}</button>
                                            </form>
                                        </details>

                                        <div class="hnt-cup-admin-row">
                                            <form method="post" action="{{ route('cups.submissions.rescore', [$cup, $submission]) }}">
                                                @csrf
                                                <button class="hnt-cup-secondary-btn" type="submit">{{ __('ui.cup_rescore') }}</button>
                                            </form>

                                            @if($submission->team)
                                                @if($submission->team->status === 'disqualified')
                                                    <form method="post" action="{{ route('cups.teams.reinstate', [$cup, $submission->team]) }}" onsubmit="return confirm('{{ __('ui.cup_reinstate_confirm') }}')">
                                                        @csrf
                                                        <button class="btn-create" type="submit">{{ __('ui.cup_reinstate') }}</button>
                                                    </form>
                                                @else
                                                    <form method="post" action="{{ route('cups.teams.disqualify', [$cup, $submission->team]) }}" class="hnt-cup-inline-form" onsubmit="return confirm('{{ __('ui.cup_disqualify_confirm') }}')">
                                                        @csrf
                                                        <input name="reason" type="text" maxlength="1200" placeholder="{{ __('ui.cup_disqualify_reason_placeholder') }}">
                                                        <button class="hnt-cup-danger-btn" type="submit">{{ __('ui.cup_disqualify') }}</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </article>
                        @empty
                            <p>{{ __('ui.preview_cup_no_submissions') }}</p>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif

        <div class="hnt-cup-upload-modal" data-hnt-cup-upload-modal hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="hnt-cup-upload-title">
            <div class="hnt-cup-upload-card">
                <span class="hnt-cup-upload-kicker">{{ __('ui.preview_cup_upload_kicker') }}</span>
                <h2 id="hnt-cup-upload-title" data-hnt-cup-upload-title>{{ __('ui.preview_cup_upload_title') }}</h2>
                <p data-hnt-cup-upload-text>{{ __('ui.preview_cup_upload_text') }}</p>
                <div class="hnt-cup-upload-progress" aria-hidden="true">
                    <span data-hnt-cup-upload-bar style="width: 0%"></span>
                </div>
                <div class="hnt-cup-upload-meter">
                    <strong data-hnt-cup-upload-percent>0%</strong>
                    <span data-hnt-cup-upload-time>{{ __('ui.preview_cup_estimated_duration') }}</span>
                </div>
                <div class="hnt-cup-upload-steps">
                    <span data-hnt-cup-upload-step="upload" class="active">{{ __('ui.preview_cup_upload_step') }}</span>
                    <span data-hnt-cup-upload-step="check">{{ __('ui.preview_cup_check_step') }}</span>
                    <span data-hnt-cup-upload-step="done">{{ __('ui.preview_cup_result_step') }}</span>
                </div>
                <div class="hnt-cup-upload-result" data-hnt-cup-upload-result hidden></div>
                <div class="hnt-cup-upload-actions">
                    <button type="button" class="btn-create hnt-cup-upload-redirect" data-hnt-cup-upload-redirect hidden>{{ __('ui.preview_cup_to_submissions') }}</button>
                    <button type="button" class="btn-create hnt-cup-upload-close" data-hnt-cup-upload-close hidden>{{ __('ui.preview_action_close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const pollHandles = new WeakMap();

    function scrollChatToBottom(list) {
        if (!list) {
            return;
        }

        list.scrollTop = list.scrollHeight;
    }

    function isNearBottom(list) {
        if (!list) {
            return true;
        }

        return (list.scrollHeight - list.scrollTop - list.clientHeight) < 96;
    }

    function setChatStatus(status, message, type) {
        if (!status) {
            return;
        }

        status.textContent = message || '';
        status.hidden = !message;
        status.classList.remove('is-success', 'is-error');

        if (type) {
            status.classList.add(type);
        }
    }

    function renderEmptyState(panel, list) {
        if (!list) {
            return;
        }

        const text = panel?.getAttribute('data-cup-chat-empty-text') || '';
        list.innerHTML = text ? '<p class="hnt-cup-chat-empty" data-cup-chat-empty>' + text + '</p>' : '';
    }

    async function refreshCupChat(panel, options) {
        if (!panel) {
            return;
        }

        const url = panel.getAttribute('data-cup-chat-url');
        const list = panel.querySelector('[data-cup-chat-list]');
        const counter = panel.querySelector('[data-cup-chat-count]');

        if (!url || !list) {
            return;
        }

        const shouldStickToBottom = options?.forceScroll || isNearBottom(list);
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(data.message || '');
        }

        if (typeof data.html === 'string') {
            if (data.html.trim()) {
                list.innerHTML = data.html;
            } else {
                renderEmptyState(panel, list);
            }
        }

        if (counter && typeof data.count !== 'undefined') {
            counter.textContent = data.count;
        }

        if (shouldStickToBottom) {
            scrollChatToBottom(list);
        }
    }

    function startCupChatPolling(panel) {
        if (!panel || pollHandles.has(panel)) {
            return;
        }

        const handle = window.setInterval(function () {
            refreshCupChat(panel).catch(function () {});
        }, 10000);

        pollHandles.set(panel, handle);
    }

    document.querySelectorAll('[data-cup-chat-panel]').forEach(function (panel) {
        const list = panel.querySelector('[data-cup-chat-list]');
        scrollChatToBottom(list);
        startCupChatPolling(panel);
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            return;
        }

        document.querySelectorAll('[data-cup-chat-panel]').forEach(function (panel) {
            refreshCupChat(panel).catch(function () {});
        });
    });

    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('[data-cup-chat-form]');

        if (!form) {
            return;
        }

        event.preventDefault();

        const panel = form.closest('[data-cup-chat-panel]') || document;
        const input = form.querySelector('[data-cup-chat-input]');
        const button = form.querySelector('[type="submit"]');
        const status = panel.querySelector('[data-cup-chat-status]');
        const originalButtonText = button ? button.textContent : '';
        const loadingText = button ? (button.getAttribute('data-loading-text') || originalButtonText) : '';

        if (!input || !input.value.trim()) {
            return;
        }

        if (button) {
            button.disabled = true;
            button.textContent = loadingText;
        }

        setChatStatus(status, '', null);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                throw new Error(data.message || form.dataset.errorText || '');
            }

            form.reset();
            input.focus();

            await refreshCupChat(panel, { forceScroll: true });

            if (data.message) {
                setChatStatus(status, data.message, 'is-success');
                window.setTimeout(function () {
                    setChatStatus(status, '', null);
                }, 2200);
            }
        } catch (error) {
            setChatStatus(status, error.message || form.dataset.errorText || '', 'is-error');
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = originalButtonText;
            }
        }
    });

    const teamChatPollHandles = new WeakMap();

    function renderTeamChatEmptyState(panel, list) {
        if (!list) {
            return;
        }

        const text = panel?.getAttribute('data-cup-team-chat-empty-text') || '';
        list.innerHTML = text ? '<p class="hnt-cup-chat-empty" data-cup-team-chat-empty>' + text + '</p>' : '';
    }

    async function refreshCupTeamChat(panel, options) {
        if (!panel) {
            return;
        }

        const url = panel.getAttribute('data-cup-team-chat-url');
        const list = panel.querySelector('[data-cup-team-chat-list]');
        const counter = panel.querySelector('[data-cup-team-chat-count]');

        if (!url || !list) {
            return;
        }

        const shouldStickToBottom = options?.forceScroll || isNearBottom(list);
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(data.message || '');
        }

        if (typeof data.html === 'string') {
            if (data.html.trim()) {
                list.innerHTML = data.html;
            } else {
                renderTeamChatEmptyState(panel, list);
            }
        }

        if (counter && typeof data.count !== 'undefined') {
            counter.textContent = data.count;
        }

        if (shouldStickToBottom) {
            scrollChatToBottom(list);
        }
    }

    function startCupTeamChatPolling(panel) {
        if (!panel || teamChatPollHandles.has(panel)) {
            return;
        }

        const handle = window.setInterval(function () {
            refreshCupTeamChat(panel).catch(function () {});
        }, 10000);

        teamChatPollHandles.set(panel, handle);
    }

    document.querySelectorAll('[data-cup-team-chat-panel]').forEach(function (panel) {
        const list = panel.querySelector('[data-cup-team-chat-list]');
        scrollChatToBottom(list);
        startCupTeamChatPolling(panel);
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            return;
        }

        document.querySelectorAll('[data-cup-team-chat-panel]').forEach(function (panel) {
            refreshCupTeamChat(panel).catch(function () {});
        });
    });

    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('[data-cup-team-chat-form]');

        if (!form) {
            return;
        }

        event.preventDefault();

        const panel = form.closest('[data-cup-team-chat-panel]') || document;
        const input = form.querySelector('[data-cup-team-chat-input]');
        const button = form.querySelector('[type="submit"]');
        const status = panel.querySelector('[data-cup-team-chat-status]');
        const originalButtonText = button ? button.textContent : '';
        const loadingText = button ? (button.getAttribute('data-loading-text') || originalButtonText) : '';

        if (!input || !input.value.trim()) {
            return;
        }

        if (button) {
            button.disabled = true;
            button.textContent = loadingText;
        }

        setChatStatus(status, '', null);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                throw new Error(data.message || form.dataset.errorText || '');
            }

            form.reset();
            input.focus();

            await refreshCupTeamChat(panel, { forceScroll: true });

            if (data.message) {
                setChatStatus(status, data.message, 'is-success');
                window.setTimeout(function () {
                    setChatStatus(status, '', null);
                }, 2200);
            }
        } catch (error) {
            setChatStatus(status, error.message || form.dataset.errorText || '', 'is-error');
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = originalButtonText;
            }
        }
    });
})();
</script>
@endpush
