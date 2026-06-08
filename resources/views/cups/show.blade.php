@extends('layouts.app')

@section('title', $cup->title.' · '.__('ui.cup_detail_title_suffix'))

@section('content')
@php
    $soloCup = $cup->isSoloLeaderboard();
    $bayouBloodCup = $cup->isBayouBloodCup();
    $activeTeams = $cup->teams->where('status', 'active')->values();
    $teamCount = $activeTeams->count();
    $teamLimit = $cup->participantLimit();
    $participantCount = $soloCup ? $teamCount : $cup->teams->sum(fn ($team) => $team->activeMembers->count());
    $entryLabel = $soloCup ? __('ui.cup_participants') : __('ui.teams');
    $entryTableLabel = $soloCup ? __('ui.cup_table_player') : __('ui.cup_table_team');
    $submissionCount = $cup->submissions->count();
    $visibleSubmissions = $canManage
        ? $cup->submissions
        : ($viewerTeam ? $cup->submissions->where('cup_team_id', $viewerTeam->id) : collect());
    $visibleSubmissionCount = $visibleSubmissions->count();
    $pendingSubmissionCount = $cup->submissions->whereIn('status', ['pending', 'review_required'])->count();
    $approvedSubmissionCount = $cup->submissions->whereIn('status', ['processed', 'approved', 'approved_manual'])->count();
    $invalidSubmissionCount = $cup->submissions->whereIn('status', ['invalid', 'rejected', 'rejected_manual'])->count();
    $cupStart = $cup->starts_at ? $cup->starts_at->format('d.m.Y H:i') : __('ui.cup_open');
    $cupEnd = $cup->ends_at ? $cup->ends_at->format('d.m.Y H:i') : __('ui.cup_open');
    $registrationOpen = $cup->isRegistrationOpen();
    $viewerTeamActive = $viewerTeam && $viewerTeam->status === 'active';
    $summary = $cup->displaySummary();
    $description = $cup->displayDescription();
    $rules = $cup->displayRules();
    $scoringRules = $cup->displayScoringRules();
    $prizeRows = $cup->prizeRows();
    $prizeNotes = $cup->prizeNotes();
    $cupUploadLimitMb = max(1, (int) ceil(((int) config('hunthub.upload_limits.cup_submission_screenshot_kb', 10240)) / 1024));
    $cupCooldownMinutes = max(0, (int) config('hunthub.cups.submission_cooldown_minutes', 30));
    $rosterTeams = $activeTeams;
    $cupTabs = [
        ['label' => __('ui.cup_tab_overview'), 'count' => null],
        ['label' => __('ui.cup_tab_rules'), 'count' => null],
        ['label' => __('ui.cup_tab_prizes'), 'count' => count($prizeRows)],
        ['label' => __('ui.cup_leaderboard'), 'count' => $leaderboard->count()],
        ['label' => $entryLabel, 'count' => $activeTeams->count()],
        ['label' => __('ui.cup_tab_submit'), 'count' => null],
        ['label' => __('ui.cup_tab_submissions'), 'count' => $visibleSubmissionCount],
    ];
@endphp

@if (session('status'))
    <div class="hh-toast-stack" aria-live="polite" aria-atomic="true">
        <div class="hh-toast hh-toast-success">
            <i class="hh-toast-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    </div>
@endif

@if (session('cup_submission_result'))
    @php
        $cupSubmissionResult = session('cup_submission_result');
        $cupSubmissionResultType = $cupSubmissionResult['type'] ?? 'info';
        $cupSubmissionResultIcon = match ($cupSubmissionResultType) {
            'success' => 'ph-check-circle',
            'danger' => 'ph-x-circle',
            'warning' => 'ph-warning-circle',
            default => 'ph-info',
        };
    @endphp
    <div class="hh-cup-submission-result hh-cup-submission-result-{{ $cupSubmissionResultType }}" role="status" aria-live="polite">
        <div class="hh-cup-submission-result-icon">
            <i class="hh-ph-action-icon ph {{ $cupSubmissionResultIcon }}" aria-hidden="true"></i>
        </div>
        <div class="hh-cup-submission-result-content">
            <p class="hh-cup-submission-result-kicker">{{ __('ui.cup_submission_kicker') }}</p>
            <h3 class="hh-cup-submission-result-title">{{ $cupSubmissionResult['title'] ?? __('ui.cup_submission_processed') }}</h3>
            <p class="hh-cup-submission-result-text">{{ $cupSubmissionResult['message'] ?? __('ui.cup_submission_result_text') }}</p>
            <div class="hh-cup-submission-result-lines">
                <span>{{ $cupSubmissionResult['status'] ?? __('ui.cup_submission_status_open') }}</span>
                <span>{{ $cupSubmissionResult['score'] ?? __('ui.cup_submission_score_empty') }}</span>
                <span>{{ $cupSubmissionResult['summary'] ?? __('ui.cup_submission_check_table') }}</span>
            </div>
        </div>
    </div>
@endif


<div class="hh-cup-submission-overlay" id="hh-cup-submission-overlay" hidden role="alertdialog" aria-modal="true" aria-live="assertive" aria-labelledby="hh-cup-submission-overlay-title">
    <div class="hh-cup-submission-overlay-card">
        <div class="hh-cup-submission-spinner" aria-hidden="true"></div>
        <p class="hh-cup-submission-overlay-kicker">{{ __('ui.cup_submission_kicker') }}</p>
        <h3 class="hh-cup-submission-overlay-title" id="hh-cup-submission-overlay-title">{{ __('ui.cup_submission_overlay_title') }}</h3>
        <p class="hh-cup-submission-overlay-text">{{ __('ui.cup_submission_overlay_text') }}</p>
        <div class="hh-cup-submission-overlay-steps" aria-hidden="true">
            <span>{{ __('ui.cup_submission_overlay_upload') }}</span>
            <span>{{ __('ui.cup_submission_overlay_ai') }}</span>
            <span>{{ __('ui.cup_submission_overlay_scoring') }}</span>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="hh-alert hh-alert-danger">
        <strong>{{ __('ui.please_check') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- SECTION BANNER -->
<div class="section-banner">
    <!-- SECTION BANNER ICON -->
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/events-icon.png') }}" alt="events-icon">
    <!-- /SECTION BANNER ICON -->

    <!-- SECTION BANNER TITLE -->
    <p class="section-banner-title">hnt.rocks Cup</p>
    <!-- /SECTION BANNER TITLE -->

    <!-- SECTION BANNER TEXT -->
    <p class="section-banner-text">{{ __('ui.cup_detail_banner_text') }}</p>
    <!-- /SECTION BANNER TEXT -->
</div>
<!-- /SECTION BANNER -->

<!-- SECTION HEADER -->
<div class="section-header">
    <!-- SECTION HEADER INFO -->
    <div class="section-header-info">
        <!-- SECTION PRETITLE -->
        <p class="section-pretitle">{{ $cup->statusLabel() }} · {{ $cup->visibilityLabel() }}</p>
        <!-- /SECTION PRETITLE -->

        <!-- SECTION TITLE -->
        <h2 class="section-title">{{ $cup->title }}</h2>
        <!-- /SECTION TITLE -->
    </div>
    <!-- /SECTION HEADER INFO -->

    <!-- SECTION HEADER ACTIONS -->
    <div class="section-header-actions">
        <!-- SECTION HEADER SUBSECTION -->
        <a class="section-header-subsection" href="{{ route('cups.index') }}">{{ __('ui.cups') }}</a>
        <!-- /SECTION HEADER SUBSECTION -->

        <!-- SECTION HEADER SUBSECTION -->
        <a class="section-header-subsection" href="{{ route('hall-of-fame.index') }}">{{ __('ui.hall_of_fame') }}</a>
        <!-- /SECTION HEADER SUBSECTION -->

        <!-- SECTION HEADER SUBSECTION -->
        <p class="section-header-subsection">{{ $cup->platform ?: __('ui.cup_all_platforms') }}</p>
        <!-- /SECTION HEADER SUBSECTION -->

        <!-- SECTION HEADER SUBSECTION -->
        <p class="section-header-subsection">{{ $cup->region ?: __('ui.cup_all_regions') }}</p>
        <!-- /SECTION HEADER SUBSECTION -->
    </div>
    <!-- /SECTION HEADER ACTIONS -->
</div>
<!-- /SECTION HEADER -->

<!-- GRID -->
<div class="grid grid-9-3">
    <!-- MARKETPLACE CONTENT -->
    <div class="marketplace-content grid-column">
        <!-- SLIDER PANEL -->
        <div class="slider-panel hh-cup-market-slider">
            <!-- SLIDER PANEL SLIDES -->
            <div id="cup-detail-slider-items" class="slider-panel-slides">
                <!-- SLIDER PANEL SLIDE -->
                <div class="slider-panel-slide">
                    <!-- SLIDER PANEL SLIDE IMAGE -->
                    <figure class="slider-panel-slide-image liquid">
                        <img src="{{ $cup->coverUrl() }}" alt="{{ $cup->title }}">
                    </figure>
                    <!-- /SLIDER PANEL SLIDE IMAGE -->
                </div>
                <!-- /SLIDER PANEL SLIDE -->
            </div>
            <!-- /SLIDER PANEL SLIDES -->

            <!-- SLIDER PANEL ROSTER -->
            <div class="slider-panel-roster hh-cup-team-strip" aria-label="{{ $entryLabel }}">
                @forelse ($rosterTeams as $team)
                    @php
                        $stripAvatar = $team->owner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                        $stripLevel = max(1, (int) ($team->owner?->level ?: 1));
                    @endphp
                    <div class="hh-cup-strip-card">
                        <span class="hh-cup-strip-avatar" aria-hidden="true">
                            <img src="{{ $stripAvatar }}" alt="">
                            <span class="hh-cup-strip-level">{{ $stripLevel }}</span>
                        </span>
                        <span class="hh-cup-strip-copy">
                            <span class="hh-cup-strip-name">{{ $soloCup ? $team->displayName() : $team->name }}</span>
                            <span class="hh-cup-strip-meta">{{ $soloCup ? __('ui.cup_participant_points_line', ['points' => $team->points_total]) : __('ui.cup_team_points_line', ['points' => $team->points_total, 'members' => $team->activeMembers->count(), 'size' => $cup->team_size]) }}</span>
                        </span>
                    </div>
                @empty
                    <p class="hh-cup-strip-empty">{{ $soloCup ? __('ui.cup_no_participants_registered') : __('ui.cup_no_teams_registered') }}</p>
                @endforelse
            </div>
            <!-- /SLIDER PANEL ROSTER -->
        </div>
        <!-- /SLIDER PANEL -->

        <!-- TAB BOX -->
        <div class="tab-box hh-cup-content-tabs" data-cup-content-tabs>
            <!-- MOBILE TAB SELECT -->
            <div class="hh-cup-mobile-tab-control">
                <label class="hh-cup-mobile-tab-label" for="cup-mobile-tab-select">{{ __('ui.cup_mobile_tab_select_label') }}</label>
                <div class="form-select hh-cup-mobile-tab-select">
                    <select id="cup-mobile-tab-select" data-cup-tab-select>
                        @foreach ($cupTabs as $tabIndex => $cupTab)
                            <option value="{{ $tabIndex }}">{{ $cupTab['label'] }}</option>
                        @endforeach
                    </select>
                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                </div>
            </div>
            <!-- /MOBILE TAB SELECT -->

            <!-- TAB BOX OPTIONS -->
            <div class="tab-box-options hh-cup-desktop-tab-options">
                @foreach ($cupTabs as $tabIndex => $cupTab)
                    <div class="tab-box-option" data-cup-tab-option="{{ $tabIndex }}">
                        <p class="tab-box-option-title">
                            {{ $cupTab['label'] }}
                            @if ($cupTab['count'] !== null)
                                <span class="highlighted">{{ $cupTab['count'] }}</span>
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
            <!-- /TAB BOX OPTIONS -->

            <!-- TAB BOX ITEMS -->
            <div class="tab-box-items">
                <!-- TAB BOX ITEM -->
                <div class="tab-box-item">
                    <div class="tab-box-item-content">
                        <p class="tab-box-item-title hh-cup-overview-summary">{{ $summary }}</p>

                        @if ($description !== '')
                            <p class="tab-box-item-paragraph">{!! nl2br(e($description)) !!}</p>
                        @else
                            <p class="tab-box-item-paragraph">{{ __('ui.cup_description_empty') }}</p>
                        @endif

                        <div class="information-line-list hh-cup-information-lines">
                            <div class="information-line">
                                <p class="information-line-title">{{ __('ui.cup_info_period') }}</p>
                                <p class="information-line-text">{{ __('ui.cup_info_period_line', ['start' => $cupStart, 'end' => $cupEnd]) }}</p>
                            </div>

                            <div class="information-line">
                                <p class="information-line-title">{{ __('ui.cup_info_setup') }}</p>
                                <p class="information-line-text">{{ $soloCup ? __('ui.cup_info_setup_solo_line', ['platform' => $cup->platform ?: __('ui.cup_platform_open'), 'language' => $cup->language ?: __('ui.cup_language_open')]) : __('ui.cup_info_setup_line', ['size' => $cup->team_size, 'platform' => $cup->platform ?: __('ui.cup_platform_open'), 'language' => $cup->language ?: __('ui.cup_language_open')]) }}</p>
                            </div>

                            <div class="information-line">
                                <p class="information-line-title">{{ __('ui.cup_mode') }}</p>
                                <p class="information-line-text">{{ $cup->modeLabel() }}</p>
                            </div>
                        </div>

                        @if ($bayouBloodCup)
                            <div class="hh-cup-ai-submit-note">
                                <div class="hh-cup-ai-submit-note-icon">
                                    <i class="hh-ph-action-icon ph ph-shield-check" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="hh-cup-ai-submit-note-title">{{ __('ui.cup_launch_review_notice_title') }}</p>
                                    <p class="hh-cup-ai-submit-note-text">{{ __('ui.cup_launch_review_notice_text') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <!-- /TAB BOX ITEM -->

                <!-- TAB BOX ITEM -->
                <div class="tab-box-item">
                    <div class="tab-box-item-content">
                        <p class="tab-box-item-title">{{ __('ui.cup_tab_rules') }}</p>

                        @if ($rules !== '')
                            <p class="tab-box-item-paragraph">{!! nl2br(e($rules)) !!}</p>
                        @else
                            <p class="tab-box-item-paragraph">{{ __('ui.cup_rules_empty') }}</p>
                        @endif

                        <div class="information-line-list hh-cup-information-lines">
                            <div class="information-line">
                                <p class="information-line-title">{{ __('ui.cup_info_scoring') }}</p>
                                <p class="information-line-text">{!! nl2br(e($scoringRules)) !!}</p>
                            </div>
                        </div>

                        @if ($bayouBloodCup)
                            <div class="hh-cup-ai-submit-note">
                                <div class="hh-cup-ai-submit-note-icon">
                                    <i class="hh-ph-action-icon ph ph-list-checks" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="hh-cup-ai-submit-note-title">{{ __('ui.cup_fairplay_notice_title') }}</p>
                                    <p class="hh-cup-ai-submit-note-text">{{ __('ui.cup_fairplay_notice_text') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <!-- /TAB BOX ITEM -->

                <!-- TAB BOX ITEM -->
                <div class="tab-box-item">
                    <div class="tab-box-item-content">
                        <p class="tab-box-item-title">{{ __('ui.cup_tab_prizes') }}</p>

                        @if (count($prizeRows) > 0)
                            <div class="information-line-list hh-cup-information-lines hh-cup-prize-lines">
                                @foreach ($prizeRows as $prizeRow)
                                    <div class="information-line">
                                        <p class="information-line-title">{{ $prizeRow['label'] }}</p>
                                        <p class="information-line-text">{!! nl2br(e($prizeRow['text'])) !!}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="tab-box-item-paragraph">{{ __('ui.cup_prizes_empty_text') }}</p>
                        @endif

                        @if (count($prizeNotes) > 0)
                            <div class="information-line-list hh-cup-information-lines hh-cup-prize-lines">
                                @foreach ($prizeNotes as $prizeNote)
                                    <div class="information-line">
                                        <p class="information-line-title">{{ $prizeNote['label'] }}</p>
                                        <p class="information-line-text">{!! nl2br(e($prizeNote['text'])) !!}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($bayouBloodCup)
                            <div class="hh-cup-ai-submit-note">
                                <div class="hh-cup-ai-submit-note-icon">
                                    <i class="hh-ph-action-icon ph ph-gift" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="hh-cup-ai-submit-note-title">{{ __('ui.cup_prize_verification_notice_title') }}</p>
                                    <p class="hh-cup-ai-submit-note-text">{{ __('ui.cup_prize_verification_notice_text') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <!-- /TAB BOX ITEM -->

                <!-- TAB BOX ITEM -->
                <div class="tab-box-item">
                    <!-- TAB BOX ITEM CONTENT -->
                    <div class="tab-box-item-content">
                        <!-- TAB BOX ITEM TITLE -->
                        <p class="tab-box-item-title">{{ __('ui.cup_leaderboard') }}</p>
                        <!-- /TAB BOX ITEM TITLE -->

                        <div class="hh-cup-ai-submit-note">
                            <div class="hh-cup-ai-submit-note-icon">
                                <i class="hh-ph-action-icon ph ph-eye" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="hh-cup-ai-submit-note-title">{{ __('ui.cup_leaderboard_preliminary_title') }}</p>
                                <p class="hh-cup-ai-submit-note-text">{{ __('ui.cup_leaderboard_preliminary_text') }}</p>
                            </div>
                        </div>

                        <!-- TABLE -->
                        <div class="table table-top-friends join-rows hh-cup-market-table hh-cup-leaderboard-table">
                            <!-- TABLE HEADER -->
                            <div class="table-header">
                                <div class="table-header-column"><p class="table-header-title">{{ $entryTableLabel }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_points') }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_token') }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_kills') }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_matches') }}</p></div>
                            </div>
                            <!-- /TABLE HEADER -->

                            <!-- TABLE BODY -->
                            <div class="table-body">
                                @forelse ($leaderboard as $index => $team)
                                    <!-- TABLE ROW -->
                                    <div class="table-row tiny">
                                        <div class="table-column">
                                            <div class="user-status">
                                                <p class="user-status-title"><span class="bold">#{{ $index + 1 }} · {{ $soloCup ? $team->displayName() : $team->name }}</span></p>
                                                <p class="user-status-text small">{{ $soloCup ? __('ui.cup_player') : __('ui.cup_captain') }}: {{ $team->owner?->name ?? __('ui.cup_unknown') }}</p>
                                            </div>
                                        </div>
                                        <div class="table-column centered padded"><p class="table-title">{{ $team->points_total }}</p></div>
                                        <div class="table-column centered padded"><p class="table-title">{{ $team->bounty_tokens_total }}</p></div>
                                        <div class="table-column centered padded"><p class="table-title">{{ $team->kills_total }}</p></div>
                                        <div class="table-column centered padded"><p class="table-title">{{ $team->submissions_approved_count }}</p></div>
                                    </div>
                                    <!-- /TABLE ROW -->
                                @empty
                                    <!-- TABLE ROW -->
                                    <div class="table-row tiny">
                                        <div class="table-column">
                                            <p class="table-text">{{ $soloCup ? __('ui.cup_no_participants_registered') : __('ui.cup_no_teams_registered') }}</p>
                                        </div>
                                    </div>
                                    <!-- /TABLE ROW -->
                                @endforelse
                            </div>
                            <!-- /TABLE BODY -->
                        </div>
                        <!-- /TABLE -->

                        <div class="hh-cup-mobile-leaderboard" aria-label="{{ __('ui.cup_leaderboard') }}">
                            @forelse ($leaderboard as $index => $team)
                                <div class="hh-cup-mobile-leaderboard-card">
                                    <div class="hh-cup-mobile-leaderboard-head">
                                        <span class="hh-cup-mobile-leaderboard-rank">#{{ $index + 1 }}</span>
                                        <span class="hh-cup-mobile-leaderboard-name">{{ $soloCup ? $team->displayName() : $team->name }}</span>
                                        <small>{{ $soloCup ? __('ui.cup_player') : __('ui.cup_captain') }}: {{ $team->owner?->name ?? __('ui.cup_unknown') }}</small>
                                    </div>
                                    <div class="hh-cup-mobile-leaderboard-stats">
                                        <div><span>{{ __('ui.cup_table_points') }}</span><strong>{{ $team->points_total }}</strong></div>
                                        <div><span>{{ __('ui.cup_table_token') }}</span><strong>{{ $team->bounty_tokens_total }}</strong></div>
                                        <div><span>{{ __('ui.cup_table_kills') }}</span><strong>{{ $team->kills_total }}</strong></div>
                                        <div><span>{{ __('ui.cup_table_matches') }}</span><strong>{{ $team->submissions_approved_count }}</strong></div>
                                    </div>
                                </div>
                            @empty
                                <div class="hh-cup-mobile-leaderboard-empty">{{ $soloCup ? __('ui.cup_no_participants_registered') : __('ui.cup_no_teams_registered') }}</div>
                            @endforelse
                        </div>
                    </div>
                    <!-- /TAB BOX ITEM CONTENT -->
                </div>
                <!-- /TAB BOX ITEM -->

                <!-- TAB BOX ITEM -->
                <div class="tab-box-item">
                    <!-- TAB BOX ITEM CONTENT -->
                    <div class="tab-box-item-content">
                        <!-- TAB BOX ITEM TITLE -->
                        <p class="tab-box-item-title">{{ $entryLabel }}</p>
                        <!-- /TAB BOX ITEM TITLE -->

                        <!-- TABLE -->
                        <div class="table table-top-friends join-rows hh-cup-market-table">
                            <!-- TABLE HEADER -->
                            <div class="table-header">
                                <div class="table-header-column"><p class="table-header-title">{{ $entryTableLabel }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ $soloCup ? __('ui.cup_table_status') : __('ui.cup_table_players') }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_points') }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_token') }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_kills') }}</p></div>
                            </div>
                            <!-- /TABLE HEADER -->

                            <!-- TABLE BODY -->
                            <div class="table-body">
                                @forelse (($canManage ? $cup->teams : $rosterTeams) as $team)
                                    <!-- TABLE ROW -->
                                    <div class="table-row tiny">
                                        <div class="table-column">
                                            <div class="user-status hh-cup-table-team">
                                                <span class="user-status-avatar" aria-hidden="true">
                                                    <span class="user-avatar small no-outline">
                                                        <span class="user-avatar-content">
                                                            <span class="hexagon-image-30-32" data-src="{{ $team->owner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"></span>
                                                        </span>
                                                        <span class="user-avatar-progress"><span class="hexagon-progress-40-44"></span></span>
                                                        <span class="user-avatar-progress-border"><span class="hexagon-border-40-44"></span></span>
                                                        <span class="user-avatar-badge">
                                                            <span class="user-avatar-badge-border"><span class="hexagon-22-24"></span></span>
                                                            <span class="user-avatar-badge-content"><span class="hexagon-dark-16-18"></span></span>
                                                            <span class="user-avatar-badge-text">{{ max(1, (int) ($team->owner?->level ?: 1)) }}</span>
                                                        </span>
                                                    </span>
                                                </span>
                                                <p class="user-status-title">
                                                    <span class="bold">{{ $soloCup ? $team->displayName() : $team->name }}</span>
                                                    @if ((int) $team->owner_id === (int) $cup->owner_id)
                                                        <span class="hh-cup-author-pill">{{ __('ui.cup_author') }}</span>
                                                    @endif
                                                </p>
                                                <p class="user-status-text small">{{ $soloCup ? __('ui.cup_registered_player') : $team->statusLabel().' · '.__('ui.cup_captain').': '.($team->owner?->name ?? __('ui.cup_unknown')) }}</p>
                                            </div>
                                        </div>
                                        <div class="table-column centered padded"><p class="table-title">{{ $soloCup ? $team->statusLabel() : $team->activeMembers->count().'/'.$cup->team_size }}</p></div>
                                        <div class="table-column centered padded"><p class="table-title">{{ $team->points_total }}</p></div>
                                        <div class="table-column centered padded"><p class="table-title">{{ $team->bounty_tokens_total }}</p></div>
                                        <div class="table-column centered padded"><p class="table-title">{{ $team->kills_total }}</p></div>
                                    </div>
                                    <!-- /TABLE ROW -->
                                    @if ($canManage)
                                        <div class="table-row tiny hh-cup-review-row">
                                            <div class="table-column">
                                                <p class="table-title">{{ __('ui.cup_administration') }}</p>
                                                @if ($team->status === 'disqualified')
                                                    <p class="table-text hh-cup-submission-warning">{{ __('ui.cup_disqualified_note', ['reason' => $team->disqualification_reason ?: __('ui.cup_no_reason_given')]) }}</p>
                                                @else
                                                    <p class="table-text">{{ $team->statusLabel() }}</p>
                                                @endif
                                            </div>
                                            <div class="table-column" colspan="4">
                                                @if ($team->status === 'disqualified')
                                                    <form method="post" action="{{ route('cups.teams.reinstate', [$cup, $team]) }}" class="hh-cup-review-form" onsubmit="return confirm('{{ __('ui.cup_reinstate_confirm') }}')">
                                                        @csrf
                                                        <button class="button small primary" type="submit">{{ __('ui.cup_reinstate') }}</button>
                                                    </form>
                                                @else
                                                    <form method="post" action="{{ route('cups.teams.disqualify', [$cup, $team]) }}" class="hh-cup-review-form" onsubmit="return confirm('{{ __('ui.cup_disqualify_confirm') }}')">
                                                        @csrf
                                                        <input class="hh-cup-field" name="reason" type="text" maxlength="1200" placeholder="{{ __('ui.cup_disqualify_reason_placeholder') }}">
                                                        <button class="button small secondary" type="submit">{{ __('ui.cup_disqualify') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <!-- TABLE ROW -->
                                    <div class="table-row tiny">
                                        <div class="table-column">
                                            <p class="table-text">{{ $soloCup ? __('ui.cup_no_participants') : __('ui.cup_no_teams') }}</p>
                                        </div>
                                    </div>
                                    <!-- /TABLE ROW -->
                                @endforelse
                            </div>
                            <!-- /TABLE BODY -->
                        </div>
                        <!-- /TABLE -->
                    </div>
                    <!-- /TAB BOX ITEM CONTENT -->
                </div>
                <!-- /TAB BOX ITEM -->

                <!-- TAB BOX ITEM -->
                <div class="tab-box-item">
                    <!-- TAB BOX ITEM CONTENT -->
                    <div class="tab-box-item-content">
                        <!-- TAB BOX ITEM TITLE -->
                        <p class="tab-box-item-title">{{ __('ui.cup_submit_result_title') }}</p>
                        <!-- /TAB BOX ITEM TITLE -->

                        @if ($viewerTeamActive && $cup->status === 'active')
                            <!-- TAB BOX ITEM PARAGRAPH -->
                            <p class="tab-box-item-paragraph">{{ __('ui.cup_submit_intro') }}</p>
                            <!-- /TAB BOX ITEM PARAGRAPH -->

                            <!-- FORM -->
                            <form method="post" action="{{ route('cups.submissions.store', [$cup, $viewerTeam]) }}" enctype="multipart/form-data" class="form hh-cup-submit-form" data-cup-submission-form>
                                @csrf

                                <div class="hh-cup-ai-submit-note">
                                    <div class="hh-cup-ai-submit-note-icon">
                                        <i class="hh-ph-action-icon ph ph-sparkle" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <p class="hh-cup-ai-submit-note-title">{{ __('ui.cup_submit_ai_title') }}</p>
                                        <p class="hh-cup-ai-submit-note-text">{{ __('ui.cup_submit_ai_text') }}</p>
                                    </div>
                                </div>

                                <div class="hh-cup-ai-submit-note">
                                    <div class="hh-cup-ai-submit-note-icon">
                                        <i class="hh-ph-action-icon ph ph-upload-simple" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <p class="hh-cup-ai-submit-note-title">{{ __('ui.cup_submit_checklist_title') }}</p>
                                        <p class="hh-cup-ai-submit-note-text">{{ __('ui.cup_submit_checklist_text', ['limit' => $cupUploadLimitMb, 'cooldown' => $cupCooldownMinutes]) }}</p>
                                    </div>
                                </div>

                                <div class="hh-cup-summary-example">
                                    <div class="hh-cup-summary-example-copy">
                                        <p class="hh-cup-ai-submit-note-title">{{ __('ui.cup_submit_example_title') }}</p>
                                        <p class="hh-cup-ai-submit-note-text">{{ __('ui.cup_submit_example_text') }}</p>
                                    </div>

                                    <button class="hh-cup-summary-example-thumb" type="button" data-hh-cup-summary-example-open aria-label="{{ __('ui.cup_submit_example_open') }}">
                                        <img src="{{ asset('assets/vikinger/img/cups/summary-screen-example.jpg') }}" alt="{{ __('ui.cup_submit_example_alt') }}" loading="lazy">
                                        <span>{{ __('ui.cup_submit_example_open') }}</span>
                                    </button>
                                </div>

                                <div class="hh-cup-summary-example-modal" data-hh-cup-summary-example-modal hidden>
                                    <button class="hh-cup-summary-example-backdrop" type="button" data-hh-cup-summary-example-close aria-label="{{ __('ui.close') }}"></button>
                                    <div class="hh-cup-summary-example-dialog" role="dialog" aria-modal="true" aria-label="{{ __('ui.cup_submit_example_title') }}">
                                        <button class="hh-cup-summary-example-close" type="button" data-hh-cup-summary-example-close aria-label="{{ __('ui.close') }}">
                                            <i class="hh-ph-action-icon ph ph-x" aria-hidden="true"></i>
                                        </button>
                                        <img src="{{ asset('assets/vikinger/img/cups/summary-screen-example.jpg') }}" alt="{{ __('ui.cup_submit_example_alt') }}">
                                    </div>
                                </div>

                                <!-- FORM ROW -->
                                <div class="form-row">
                                    <div class="form-item">
                                        <label class="hh-cup-field-label" for="screenshot">{{ __('ui.cup_submit_screenshot') }}</label>
                                        <input class="hh-cup-field hh-cup-file-field" id="screenshot" name="screenshot" type="file" accept="image/*" required>
                                    </div>
                                </div>
                                <!-- /FORM ROW -->

                                <!-- FORM ROW -->
                                <div class="form-row">
                                    <div class="form-item">
                                        <label class="hh-cup-field-label" for="note">{{ __('ui.cup_submit_note') }}</label>
                                        <textarea class="hh-cup-field" id="note" name="note" rows="4" maxlength="1200" placeholder="{{ __('ui.cup_submit_note_placeholder') }}"></textarea>
                                    </div>
                                </div>
                                <!-- /FORM ROW -->

                                <button class="button primary full" type="submit" data-cup-submit-button>{{ __('ui.cup_submit_button') }}</button>
                            </form>
                            <!-- /FORM -->
                        @elseif ($viewerTeam && $viewerTeam->status === 'disqualified')
                            <div class="hh-cup-ai-submit-note">
                                <div class="hh-cup-ai-submit-note-icon">
                                    <i class="hh-ph-action-icon ph ph-warning" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="hh-cup-ai-submit-note-title">{{ __('ui.cup_team_status_disqualified') }}</p>
                                    <p class="hh-cup-ai-submit-note-text">{{ __('ui.cup_submission_error_disqualified') }}</p>
                                </div>
                            </div>
                        @elseif ($viewerTeam)
                            <p class="tab-box-item-paragraph">{{ __('ui.cup_submit_only_active') }}</p>
                        @else
                            <p class="tab-box-item-paragraph">{{ $soloCup ? __('ui.cup_submit_need_participant') : __('ui.cup_submit_need_team') }}</p>
                        @endif
                    </div>
                    <!-- /TAB BOX ITEM CONTENT -->
                </div>
                <!-- /TAB BOX ITEM -->

                <!-- TAB BOX ITEM -->
                <div class="tab-box-item">
                    <!-- TAB BOX ITEM CONTENT -->
                    <div class="tab-box-item-content">
                        <!-- TAB BOX ITEM TITLE -->
                        <p class="tab-box-item-title" id="cup-submissions">{{ __('ui.cup_tab_submissions') }}</p>
                        <!-- /TAB BOX ITEM TITLE -->

                        <!-- TABLE -->
                        <div class="table table-top-friends join-rows hh-cup-market-table hh-cup-submissions-table">
                            <!-- TABLE HEADER -->
                            <div class="table-header">
                                <div class="table-header-column"><p class="table-header-title">{{ $entryTableLabel }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_status') }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_score') }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_submitted') }}</p></div>
                                <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_action') }}</p></div>
                            </div>
                            <!-- /TABLE HEADER -->

                            <!-- TABLE BODY -->
                            <div class="table-body">
                                @forelse ($visibleSubmissions as $submission)
                                    @php
                                        $canSeeSubmission = $canManage || ($viewerTeam && (int) $viewerTeam->id === (int) $submission->cup_team_id);
                                    @endphp

                                    @if ($canSeeSubmission)
                                        <!-- TABLE ROW -->
                                        <div class="table-row tiny">
                                            <div class="table-column">
                                                <div class="user-status hh-cup-table-team">
                                                    <span class="user-status-avatar" aria-hidden="true">
                                                        <span class="user-avatar small no-outline">
                                                            <span class="user-avatar-content">
                                                                <span class="hexagon-image-30-32" data-src="{{ $submission->submitter?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"></span>
                                                            </span>
                                                            <span class="user-avatar-progress"><span class="hexagon-progress-40-44"></span></span>
                                                            <span class="user-avatar-progress-border"><span class="hexagon-border-40-44"></span></span>
                                                            <span class="user-avatar-badge">
                                                                <span class="user-avatar-badge-border"><span class="hexagon-22-24"></span></span>
                                                                <span class="user-avatar-badge-content"><span class="hexagon-dark-16-18"></span></span>
                                                                <span class="user-avatar-badge-text">{{ max(1, (int) ($submission->submitter?->level ?: 1)) }}</span>
                                                            </span>
                                                        </span>
                                                    </span>
                                                    <p class="user-status-title">
                                                        <span class="bold">{{ $submission->team ? ($soloCup ? $submission->team->displayName() : $submission->team->name) : $entryTableLabel }}</span>
                                                        @if ($submission->team && (int) $submission->team->owner_id === (int) $cup->owner_id)
                                                            <span class="hh-cup-author-pill">{{ __('ui.cup_author') }}</span>
                                                        @endif
                                                    </p>
                                                    <p class="user-status-text small">{{ __('ui.cup_submitted_by', ['name' => $submission->submitter?->name ?? __('ui.cup_unknown')]) }}</p>
                                                </div>
                                            </div>
                                            <div class="table-column centered padded">
                                                <p class="table-title">{{ $submission->statusLabel() }}</p>
                                                @if ($submission->invalidReasonLabel())
                                                    <p class="table-text hh-cup-submission-reason">{{ $submission->invalidReasonLabel() }}</p>
                                                @endif
                                                @if ($submission->ai_gamertag)
                                                    <p class="table-text hh-cup-submission-gamertag">{{ __('ui.cup_submission_gamertag_detected', ['name' => $submission->ai_gamertag]) }}</p>
                                                @endif
                                                @if ($canManage && $submission->ai_gamertag_mismatch && $submission->team?->detected_gamertag)
                                                    <p class="table-text hh-cup-submission-warning">{{ __('ui.cup_submission_gamertag_expected', ['name' => $submission->team->detected_gamertag]) }}</p>
                                                @endif
                                            </div>
                                            <div class="table-column centered padded"><p class="table-title">{{ __('ui.cup_submission_score_line', ['points' => $submission->points, 'kills' => $submission->kills, 'tokens' => $submission->bounty_tokens]) }}</p><p class="table-text hh-cup-submission-reason">{{ $submission->resultSummary() }}</p></div>
                                            <div class="table-column centered padded"><p class="table-title">{{ $submission->submitted_at?->diffForHumans() }}</p><p class="table-text">{{ $submission->submitted_at?->format('d.m.Y H:i') }}</p></div>
                                            <div class="table-column centered padded">
                                                @if ($submission->screenshot)
                                                    <a class="hh-cup-table-link" href="{{ route('cups.submissions.screenshot', [$cup, $submission]) }}" target="_blank" rel="noopener">{{ __('ui.cup_screenshot') }}</a>
                                                @else
                                                    <p class="table-text">-</p>
                                                @endif
                                            </div>
                                        </div>
                                        <!-- /TABLE ROW -->

                                        @if ($canManage && in_array($submission->status, ['pending', 'review_required'], true))
                                            <!-- TABLE ROW -->
                                            <div class="table-row tiny hh-cup-review-row">
                                                <div class="table-column">
                                                    <form method="post" action="{{ route('cups.submissions.approve', [$cup, $submission]) }}">
                                                        @csrf
                                                        <button class="button small primary" type="submit">{{ __('ui.cup_approve') }}</button>
                                                    </form>
                                                </div>
                                                <div class="table-column" colspan="4">
                                                    <form method="post" action="{{ route('cups.submissions.reject', [$cup, $submission]) }}" class="hh-cup-review-form">
                                                        @csrf
                                                        <input class="hh-cup-field" name="review_note" type="text" maxlength="1200" placeholder="{{ __('ui.cup_review_reason_placeholder') }}">
                                                        <button class="button small secondary" type="submit">{{ __('ui.cup_reject') }}</button>
                                                    </form>
                                                </div>
                                            </div>
                                            <!-- /TABLE ROW -->
                                        @endif
                                        @if ($canManage)
                                            <!-- TABLE ROW -->
                                            <div class="table-row tiny hh-cup-review-row">
                                                <div class="table-column">
                                                    <p class="table-title">{{ __('ui.cup_manual_score_title') }}</p>
                                                    <p class="table-text">{{ __('ui.cup_manual_score_help') }}</p>
                                                </div>
                                                <div class="table-column" colspan="4">
                                                    <form method="post" action="{{ route('cups.submissions.manual-score', [$cup, $submission]) }}" class="hh-cup-review-form">
                                                        @csrf
                                                        <input class="hh-cup-field" name="kills" type="number" min="0" max="99" value="{{ old('kills', $submission->kills) }}" placeholder="{{ __('ui.cup_manual_kills_placeholder') }}">
                                                        <input class="hh-cup-field" name="bounty_tokens" type="number" min="0" max="4" value="{{ old('bounty_tokens', $submission->bounty_tokens) }}" placeholder="{{ __('ui.cup_manual_bounty_placeholder') }}">
                                                        <input class="hh-cup-field" name="points" type="number" min="0" max="999" value="{{ old('points', $submission->points) }}" placeholder="{{ __('ui.cup_manual_points_placeholder') }}">
                                                        <input class="hh-cup-field" name="review_note" type="text" maxlength="1200" placeholder="{{ __('ui.cup_manual_note_placeholder') }}">
                                                        <button class="button small primary" type="submit">{{ __('ui.cup_manual_score_save') }}</button>
                                                    </form>
                                                    <form method="post" action="{{ route('cups.submissions.rescore', [$cup, $submission]) }}" class="hh-cup-review-form" style="margin-top: 8px;">
                                                        @csrf
                                                        <button class="button small secondary" type="submit">{{ __('ui.cup_rescore') }}</button>
                                                    </form>
                                                    @if ($submission->team)
                                                        @if ($submission->team->status === 'disqualified')
                                                            <div class="hh-cup-review-form" style="margin-top: 8px;">
                                                                <p class="table-text hh-cup-submission-warning">{{ __('ui.cup_disqualified_note', ['reason' => $submission->team->disqualification_reason ?: __('ui.cup_no_reason_given')]) }}</p>
                                                            </div>
                                                            <form method="post" action="{{ route('cups.teams.reinstate', [$cup, $submission->team]) }}" class="hh-cup-review-form" style="margin-top: 8px;" onsubmit="return confirm('{{ __('ui.cup_reinstate_confirm') }}')">
                                                                @csrf
                                                                <button class="button small primary" type="submit">{{ __('ui.cup_reinstate') }}</button>
                                                            </form>
                                                        @else
                                                            <form method="post" action="{{ route('cups.teams.disqualify', [$cup, $submission->team]) }}" class="hh-cup-review-form" style="margin-top: 8px;" onsubmit="return confirm('{{ __('ui.cup_disqualify_confirm') }}')">
                                                                @csrf
                                                                <input class="hh-cup-field" name="reason" type="text" maxlength="1200" placeholder="{{ __('ui.cup_disqualify_reason_placeholder') }}">
                                                                <button class="button small secondary" type="submit">{{ __('ui.cup_disqualify') }}</button>
                                                            </form>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                            <!-- /TABLE ROW -->
                                        @endif
                                    @endif
                                @empty
                                    <!-- TABLE ROW -->
                                    <div class="table-row tiny">
                                        <div class="table-column">
                                            <p class="table-text">{{ __('ui.cup_no_submissions') }}</p>
                                        </div>
                                    </div>
                                    <!-- /TABLE ROW -->
                                @endforelse
                            </div>
                            <!-- /TABLE BODY -->
                        </div>
                        <!-- /TABLE -->
                    </div>
                    <!-- /TAB BOX ITEM CONTENT -->
                </div>
                <!-- /TAB BOX ITEM -->
            </div>
            <!-- /TAB BOX ITEMS -->
        </div>
        <!-- /TAB BOX -->
    </div>
    <!-- /MARKETPLACE CONTENT -->

    <!-- MARKETPLACE SIDEBAR -->
    <div class="marketplace-sidebar">
        <!-- SIDEBAR BOX -->
        <div class="sidebar-box">
            <!-- SIDEBAR BOX ITEMS -->
            <div class="sidebar-box-items">
                <!-- PRICE TITLE -->
                <p class="price-title big"><span class="currency">#</span> {{ $cup->statusLabel() }}</p>
                <!-- /PRICE TITLE -->

                <!-- FORM -->
                <div class="form hh-cup-market-license">
                    <!-- CHECKBOX WRAP -->
                    <div class="checkbox-wrap">
                        <input type="radio" id="cup-status-radio" name="cup_status_radio" checked disabled>
                        <div class="checkbox-box">
                            <i class="hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
                        </div>
                        <label for="cup-status-radio">{{ $cup->visibilityLabel() }}</label>

                        <div class="checkbox-info accordion-content-linked accordion-open">
                            <p class="checkbox-info-text">{{ $summary }}</p>
                        </div>
                    </div>
                    <!-- /CHECKBOX WRAP -->
                </div>
                <!-- /FORM -->

                @guest
                    @if ($registrationOpen)
                        <a class="button primary full" href="{{ route('register') }}">{{ __('ui.cup_join_login_required_action') }}</a>
                    @else
                        <p class="button secondary full">{{ __('ui.cup_registration_closed') }}</p>
                    @endif
                @else
                    @if (! $viewerTeam && $registrationOpen)
                        <button class="button primary full" type="submit" form="cup-sidebar-register-form">{{ $soloCup ? __('ui.cup_register_solo') : __('ui.cup_register_team') }}</button>
                    @elseif ($viewerTeam)
                        <p class="button primary full">{{ $viewerTeam->name }}</p>
                    @else
                        <p class="button secondary full">{{ __('ui.cup_registration_closed') }}</p>
                    @endif
                @endguest

                @auth
                    @if ((int) $cup->owner_id !== (int) auth()->id())
                        <button class="button white full hh-report-wide-action text-tooltip-tft" type="button" title="{{ __('ui.cup_report') }}" data-title="{{ __('ui.cup_report') }}" aria-label="{{ __('ui.cup_report') }}" data-hh-report-open data-hh-report-type="cup" data-hh-report-id="{{ $cup->id }}" data-hh-report-label="{{ __('ui.cup_report_label', ['title' => $cup->title]) }}">
                            <i class="button-icon hh-ph-action-icon ph ph-warning-octagon" aria-hidden="true"></i>
                            <span>{{ __('ui.report_short') }}</span>
                        </button>
                    @endif
                @endauth

                <!-- USER STATS -->
                <div class="user-stats">
                    <div class="user-stat big"><p class="user-stat-title">{{ $teamCount }}{{ $teamLimit ? '/'.$teamLimit : '' }}</p><p class="user-stat-text">{{ $entryLabel }}</p></div>
                    <div class="user-stat big"><p class="user-stat-title">{{ $submissionCount }}</p><p class="user-stat-text">{{ __('ui.cup_tab_submissions') }}</p></div>
                </div>
                <!-- /USER STATS -->
            </div>
            <!-- /SIDEBAR BOX ITEMS -->

            @guest
                @if ($registrationOpen)
                    <p class="sidebar-box-title medium-space">{{ __('ui.cup_join_login_required_title') }}</p>
                    <div class="sidebar-box-items">
                        <p class="sidebar-box-text">{{ __('ui.cup_join_login_required_text') }}</p>
                        <a class="button secondary full" href="{{ route('login') }}">{{ __('ui.login') }}</a>
                    </div>
                @endif
            @else
                @if (! $viewerTeam && $registrationOpen)
                    <p class="sidebar-box-title medium-space">{{ $soloCup ? __('ui.cup_register_solo_title') : __('ui.cup_register_team_title') }}</p>
                    <div class="sidebar-box-items">
                        <form id="cup-sidebar-register-form" method="post" action="{{ route('cups.teams.store', $cup) }}" class="form">
                            @csrf
                            @if ($soloCup)
                                @php($cupProfileUsername = auth()->user()?->username ?: auth()->user()?->name ?: __('ui.cup_player_fallback', ['id' => auth()->id()]))
                                <div class="information-line-list">
                                    <div class="information-line">
                                        <p class="information-line-title">{{ __('ui.cup_participant_name') }}</p>
                                        <p class="information-line-text">{{ '@'.$cupProfileUsername }}</p>
                                    </div>
                                </div>
                                <p class="sidebar-box-text">{{ __('ui.cup_solo_username_locked_text') }}</p>
                            @else
                                <div class="form-row">
                                    <div class="form-item">
                                        <label class="hh-cup-field-label" for="cup_team_name">{{ __('ui.cup_team_name') }}</label>
                                        <input class="hh-cup-field" id="cup_team_name" name="name" type="text" maxlength="100" required placeholder="{{ __('ui.cup_team_name_placeholder') }}">
                                    </div>
                                </div>
                            @endif
                        </form>
                    </div>
                @endif
            @endguest

            @if ($viewerTeam)
                <p class="sidebar-box-title medium-space">{{ $soloCup ? __('ui.cup_your_participation') : __('ui.cup_your_team') }}</p>
                <div class="sidebar-box-items">
                    <div class="information-line-list">
                        <div class="information-line"><p class="information-line-title">{{ __('ui.cup_table_points') }}</p><p class="information-line-text">{{ $viewerTeam->points_total }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.cup_table_token') }}</p><p class="information-line-text">{{ $viewerTeam->bounty_tokens_total }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.cup_table_kills') }}</p><p class="information-line-text">{{ $viewerTeam->kills_total }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.status') }}</p><p class="information-line-text">{{ $viewerTeam->statusLabel() }}</p></div>
                    </div>

                    @unless ($soloCup)
                        <div class="hh-cup-copy-field">
                            <label class="hh-cup-field-label" for="cup_join_link">{{ __('ui.cup_invite_link') }}</label>
                            <input class="hh-cup-field" id="cup_join_link" type="text" readonly value="{{ route('cups.teams.join', [$cup, $viewerTeam->join_token]) }}">
                        </div>
                    @endunless

                    @if ($viewerTeam->status === 'disqualified')
                        <p class="tab-box-item-paragraph">{{ __('ui.cup_submission_error_disqualified') }}</p>
                    @else
                        <form method="post" action="{{ route('cups.teams.leave', [$cup, $viewerTeam]) }}" onsubmit="return confirm('{{ $soloCup ? __('ui.cup_leave_participation_confirm') : __('ui.cup_leave_confirm') }}')">
                            @csrf
                            <button class="button secondary full" type="submit">{{ $soloCup ? __('ui.cup_leave_participation') : __('ui.cup_leave_team') }}</button>
                        </form>
                    @endif
                </div>
            @endif

            <p class="sidebar-box-title medium-space">{{ __('ui.cup_data') }}</p>
            <div class="sidebar-box-items">
                <div class="information-line-list">
                    <div class="information-line"><p class="information-line-title">{{ __('ui.cup_start') }}</p><p class="information-line-text">{{ $cupStart }}</p></div>
                    <div class="information-line"><p class="information-line-title">{{ __('ui.cup_end') }}</p><p class="information-line-text">{{ $cupEnd }}</p></div>
                    <div class="information-line"><p class="information-line-title">{{ __('ui.cup_mode') }}</p><p class="information-line-text">{{ $cup->modeLabel() }}</p></div>
                    <div class="information-line"><p class="information-line-title">{{ __('ui.platform') }}</p><p class="information-line-text">{{ $cup->platform ?: __('ui.cup_open') }}</p></div>
                    <div class="information-line"><p class="information-line-title">{{ __('ui.region') }}</p><p class="information-line-text">{{ $cup->region ?: __('ui.cup_open') }}</p></div>
                    <div class="information-line"><p class="information-line-title">{{ __('ui.language') }}</p><p class="information-line-text">{{ $cup->language ?: __('ui.cup_open') }}</p></div>
                    <div class="information-line"><p class="information-line-title">{{ __('ui.cup_tab_submissions') }}</p><p class="information-line-text">{{ __('ui.cup_submission_stats', ['approved' => $approvedSubmissionCount, 'pending' => $pendingSubmissionCount, 'invalid' => $invalidSubmissionCount]) }}</p></div>
                </div>
            </div>

            <p class="sidebar-box-title medium-space">{{ __('ui.cup_owner') }}</p>
            <div class="sidebar-box-items">
                <div class="user-status">
                    <a class="user-status-avatar" href="{{ $cup->owner ? route('profile.public', $cup->owner) : route('cups.index') }}">
                        <div class="user-avatar small no-outline">
                            <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $cup->owner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"></div></div>
                            <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                            <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                            <div class="user-avatar-badge">
                                <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                                <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                                <p class="user-avatar-badge-text">{{ max(1, (int) ($cup->owner?->level ?: 1)) }}</p>
                            </div>
                        </div>
                    </a>
                    <p class="user-status-title"><a class="bold" href="{{ $cup->owner ? route('profile.public', $cup->owner) : route('cups.index') }}">{{ $cup->owner?->name ?? 'hnt.rocks' }}</a></p>
                    <p class="user-status-text small">{{ $cup->owner?->username ? '@'.$cup->owner->username : __('ui.cup_organisation') }}</p>
                </div>
            </div>

            @if ($canManage)
                <p class="sidebar-box-title medium-space">{{ __('ui.cup_administration') }}</p>
                <div class="sidebar-box-items">
                    <a class="button primary full" href="{{ route('cups.edit', $cup) }}">{{ __('ui.cup_edit_action') }}</a>
                    <form method="post" action="{{ route('cups.destroy', $cup) }}" onsubmit="return confirm('{{ __('ui.cup_archive_confirm') }}')">
                        @csrf
                        @method('delete')
                        <button class="button secondary full" type="submit">{{ __('ui.cup_archive') }}</button>
                    </form>
                </div>
            @endif
        </div>
        <!-- /SIDEBAR BOX -->
    </div>
    <!-- /MARKETPLACE SIDEBAR -->
</div>
<!-- /GRID -->
<script>
(function(){
  var cupTabs = document.querySelector('[data-cup-content-tabs]');
  if (!cupTabs) return;

  var select = cupTabs.querySelector('[data-cup-tab-select]');
  var options = Array.prototype.slice.call(cupTabs.querySelectorAll('[data-cup-tab-option]'));
  var items = Array.prototype.slice.call(cupTabs.querySelectorAll('.tab-box-items > .tab-box-item'));

  if (!select || !options.length || !items.length) return;

  function normalizeIndex(index) {
    index = parseInt(index, 10);

    if (isNaN(index) || index < 0 || index >= items.length) {
      return 0;
    }

    return index;
  }

  function activateMobileTab(index) {
    index = normalizeIndex(index);

    options.forEach(function(option, optionIndex){
      option.classList.toggle('active', optionIndex === index);
    });

    items.forEach(function(item, itemIndex){
      var active = itemIndex === index;
      item.classList.toggle('active', active);
      item.style.display = active ? 'block' : 'none';
    });

    select.value = String(index);
  }

  select.addEventListener('change', function(){
    activateMobileTab(select.value);
  });

  options.forEach(function(option, index){
    option.addEventListener('click', function(){
      select.value = String(index);
    });
  });

  if (window.matchMedia && window.matchMedia('(max-width: 760px)').matches) {
    activateMobileTab(select.value);
  }
})();

(function(){
  var form = document.querySelector('[data-cup-submission-form]');
  var overlay = document.getElementById('hh-cup-submission-overlay');
  if (!form || !overlay) return;

  if (overlay.parentNode !== document.body) {
    document.body.appendChild(overlay);
  }

  form.addEventListener('submit', function(){
    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
      return;
    }

    var button = form.querySelector('[data-cup-submit-button]');
    overlay.hidden = false;
    document.documentElement.classList.add('hh-cup-submission-busy');

    if (button) {
      button.disabled = true;
      button.textContent = {!! json_encode(__('ui.cup_submission_checking'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    }
  });
})();
</script>

@endsection
