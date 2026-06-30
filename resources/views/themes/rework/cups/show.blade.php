@extends('themes.rework.layouts.app')

@section('title', 'Summer Cup · Cup · HNT.rocks')
@section('body_class', 'cup-detail-page')
@section('left_col_class', 'cup-detail-left')

@section('content')

<section class="cup-detail-hero card">
<div class="cup-detail-hero-head">
<div>
<span class="cup-eyebrow">HNT Cup</span>
<h1>Summer Cup</h1>
</div>
<a aria-label="{{ __('ui.rework_cup_menu_aria') }}" class="cup-more" href="#"><i aria-hidden="true" class="ph ph-dots-three ph-icon"></i></a>
</div>
<div class="cup-detail-main">
<div class="cup-visual-column">
<div class="cup-emblem">
<img alt="Summer Cup" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/>
</div>
<div class="cup-skill-list">
<span><i aria-hidden="true" class="ph ph-trophy ph-icon"></i> {{ __('ui.rework_cup_trophies') }}</span>
<span><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i> Hunter-Kills</span>
<span><i aria-hidden="true" class="ph ph-image-square ph-icon"></i> Screenshots</span>
</div>
<div class="cup-side-actions">
<a class="btn" href="#">{{ __('ui.rework_cup_register_team') }}</a>
<a class="btn ghost" href="#">{{ __('ui.rework_cup_submission') }}</a>
</div>
</div>
<div class="cup-info-column">
<div class="cup-title-row">
<div>
<h2>HNT Summer Cup</h2>
<span>Trio Console · PS5/Xbox · EU</span>
</div>
<strong class="cup-rarity">{{ __('ui.rework_cup_registration_open') }}</strong>
</div>
<p class="cup-summary">Erste Trophäen-Extraktion zählt, Hunter-Kills bringen Bonuspunkte. Captains laden Screenshots hoch und halten ihr Trio sauber im Rennen.</p>
<div class="cup-progress-wrap">
<div class="cup-progress-label"><span>{{ __('ui.rework_cup_participants') }}</span><strong>18 / 32 Teams</strong></div>
<div class="cup-progress"><span style="width:56%"></span><em>56%</em></div>
</div>
<div class="cup-stat-row">
<span><small>{{ __('ui.rework_cup_period') }}</small><b>26.06.–05.07.</b></span>
<span><small>{{ __('ui.rework_cup_mode') }}</small><b>Trio</b></span>
<span><small>{{ __('ui.rework_cup_status') }}</small><b>{{ __('ui.rework_cup_planned') }}</b></span>
</div>
<div class="cup-bar-grid">
<div><label>{{ __('ui.rework_cup_tab_my_submissions') }}</label><span><i style="width:68%"></i></span></div>
<div><label>Leaderboard</label><span><i style="width:42%"></i></span></div>
<div><label>{{ __('ui.rework_cup_chat_activity') }}</label><span><i style="width:74%"></i></span></div>
<div><label>{{ __('ui.rework_cup_prize_pool') }}</label><span><i style="width:100%"></i></span></div>
</div>
</div>
</div>
</section>
<section class="card cup-detail-tabs-card">
    <div aria-label="{{ __('ui.rework_cup_sections_aria') }}" class="cup-detail-tabs" role="tablist">
        <a class="active" data-cup-detail-tab="overview" href="#cup-overview">{{ __('ui.rework_cup_tab_overview') }}</a>
<a data-cup-detail-tab="rules" href="#cup-rules">{{ __('ui.rework_cup_tab_rules') }}</a>
<a data-cup-detail-tab="leaderboard" href="#cup-leaderboard">Leaderboard</a>
<a data-cup-detail-tab="prizes" href="#cup-prizes">{{ __('ui.rework_cup_tab_prizes') }}</a>
<a data-cup-detail-tab="submit" href="#cup-submit">{{ __('ui.rework_cup_tab_submit') }}</a>
        <a data-cup-detail-tab="my-submissions" href="#cup-my-submissions">{{ __('ui.rework_cup_tab_my_submissions') }}</a>
        <?php $cupAdminCanManage = auth()->check() && $cup->canManage(auth()->user()); ?>
        <?php if ($cupAdminCanManage) { ?>
            <a data-cup-detail-tab="admin-submissions" href="#cup-admin-submissions">{{ __('ui.rework_cup_tab_admin_submissions') }}</a>
        <?php } ?>
    </div>
</section>
<div class="cup-tab-panels">
<section class="cup-tab-panel is-active" data-cup-detail-panel="overview" id="cup-overview">
    @php
        $overviewCandidates = [
            $cup->displayDescription(),
            $cup->localizedContentSetting('description', '', 'de'),
            $cup->localizedContentSetting('cup_description', '', 'de'),
            data_get($cup->settings, 'content.locales.de.description'),
            data_get($cup->settings, 'content.locales.de.cup_description'),
            data_get($cup->settings, 'content.description'),
            data_get($cup->settings, 'content.cup_description'),
            data_get($cup->settings, 'description'),
            data_get($cup->settings, 'cup_description'),
        ];

        $overviewDescription = collect($overviewCandidates)
            ->map(fn ($value) => trim((string) $value))
            ->first(fn ($value) => $value !== '') ?? '';

        if ($overviewDescription === '') {
            $overviewDescription = trim((string) $cup->displaySummary());
        }

        $overviewParagraphs = collect(preg_split('/\\R{2,}/', $overviewDescription) ?: [])
            ->map(fn ($paragraph) => trim((string) $paragraph))
            ->filter()
            ->values();
    @endphp

    <article class="cup-panel-card card cup-overview-description">
        <div class="cup-panel-head">
            <span>{{ __('ui.rework_cup_tab_overview') }}</span>
            <h2>{{ __('ui.rework_cup_description_title') }}</h2>
            <p>{{ __('ui.rework_cup_overview_intro') }}</p>
        </div>

        <div class="cup-description-copy">
            @forelse($overviewParagraphs as $paragraph)
                <p>{!! nl2br(e($paragraph)) !!}</p>
            @empty
                <p>{{ __('ui.rework_cup_no_description') }}</p>
            @endforelse
        </div>
    </article>

    <article class="cup-panel-card card cup-overview-chat-card" id="cup-chat">
        <div class="cup-panel-head inline">
            <div>
                <span>Cup-Chat</span>
                <h2>Community-Chat</h2>
                <p>{{ __('ui.rework_cup_chat_intro') }}</p>
            </div>
            <strong class="cup-chat-count"><i aria-hidden="true" class="ph ph-chat-circle-dots ph-icon"></i>{{ __('ui.rework_cup_chat_message_count', ['count' => number_format((int) $cupChatMessagesCount)]) }}</strong>
        </div>

        <div class="cup-chat-shell">
            <div class="cup-chat-list" data-cup-chat-list aria-label="{{ __('ui.rework_cup_chat_messages_aria') }}">
                @forelse($cupChatMessages as $chatMessage)
                    @include('themes.rework.cups.partials.chat-message', ['chatMessage' => $chatMessage])
                @empty
                    <div class="cup-chat-empty">
                        <i aria-hidden="true" class="ph ph-chat-circle-dots ph-icon"></i>
                        <strong>{{ __('ui.rework_cup_chat_empty_title') }}</strong>
                        <p>{{ __('ui.rework_cup_chat_empty_text') }}</p>
                    </div>
                @endforelse
            </div>

            @auth
                <form class="cup-chat-form" method="post" action="{{ route('cups.chat.store', $cup) }}">
                    @csrf
                    <label for="cup-chat-body">{{ __('ui.rework_cup_chat_write_label') }}</label>
                    <div class="cup-chat-compose">
                        <textarea id="cup-chat-body" name="body" rows="3" maxlength="1200" required placeholder="{{ __('ui.rework_cup_chat_placeholder') }}">{{ old('body') }}</textarea>
                        <button class="btn" type="submit">
                            <i aria-hidden="true" class="ph ph-paper-plane-tilt ph-icon"></i>
                            {{ __('ui.send') }}
                        </button>
                    </div>
                    <span>{{ __('ui.rework_cup_chat_hint') }}</span>
                </form>
            @else
                <div class="cup-chat-login">
                    <i aria-hidden="true" class="ph ph-lock-key ph-icon"></i>
                    <div>
                        <strong>{{ __('ui.rework_cup_chat_login_title') }}</strong>
                        <p>{{ __('ui.rework_cup_chat_login_text') }}</p>
                    </div>
                    <a class="btn ghost" href="{{ route('login') }}">{{ __('ui.login') }}</a>
                </div>
            @endauth
        </div>
    </article>
</section>
<section class="cup-tab-panel" data-cup-detail-panel="rules" id="cup-rules">
    @php
        $rulesCandidates = [
            $cup->displayRules(),
            $cup->localizedContentSetting('rules', '', 'de'),
            data_get($cup->settings, 'content.locales.de.rules'),
            data_get($cup->settings, 'content.rules'),
            data_get($cup->settings, 'rules'),
            $cup->rules,
        ];

        $rulesText = collect($rulesCandidates)
            ->map(fn ($value) => trim((string) $value))
            ->first(fn ($value) => $value !== '') ?? '';

        $scoringCandidates = [
            $cup->displayScoringRules(),
            $cup->localizedContentSetting('scoring_rules', '', 'de'),
            data_get($cup->settings, 'content.locales.de.scoring_rules'),
            data_get($cup->settings, 'content.scoring_rules'),
            data_get($cup->settings, 'scoring_rules'),
        ];

        $scoringText = collect($scoringCandidates)
            ->map(fn ($value) => trim((string) $value))
            ->first(fn ($value) => $value !== '') ?? '';

        $rulesParagraphs = collect(preg_split('/\\R{2,}/', $rulesText) ?: [])
            ->map(fn ($paragraph) => trim((string) $paragraph))
            ->filter()
            ->values();

        $scoringParagraphs = collect(preg_split('/\\R{2,}/', $scoringText) ?: [])
            ->map(fn ($paragraph) => trim((string) $paragraph))
            ->filter()
            ->values();

        $allowedPlatforms = $cup->allowedPlatforms();
        $allowedPlatformLabel = $allowedPlatforms !== [] ? implode(' / ', $allowedPlatforms) : (trim((string) $cup->platform) ?: __('ui.cup_all_platforms'));
        $maxUploads = data_get($cup->settings, 'submission_limit.max_uploads_per_participant');
        $maxScored = data_get($cup->settings, 'submission_limit.max_scored_runs_per_participant');
        $profileRequired = (bool) data_get($cup->settings, 'participation_requirements.profile_complete', false);
        $minCommunityActions = (int) data_get($cup->settings, 'participation_requirements.min_community_actions', 0);
    @endphp

    <article class="cup-panel-card card cup-rules-real-card">
        <div class="cup-panel-head">
            <span>{{ __('ui.rework_cup_rules_kicker') }}</span>
            <h2>{{ __('ui.rework_cup_tab_rules') }}</h2>
            <p>{{ __('ui.rework_cup_rules_intro') }}</p>
        </div>

        <div class="cup-rules-real-layout">
            <div class="cup-rules-copy">
                <h3>{{ __('ui.rework_cup_rules_text_title') }}</h3>
                @forelse($rulesParagraphs as $paragraph)
                    <p>{!! nl2br(e($paragraph)) !!}</p>
                @empty
                    <p>{{ __('ui.rework_cup_no_rules') }}</p>
                @endforelse

                @if($scoringParagraphs->isNotEmpty())
                    <h3>{{ __('ui.rework_cup_scoring') }}</h3>
                    @foreach($scoringParagraphs as $paragraph)
                        <p>{!! nl2br(e($paragraph)) !!}</p>
                    @endforeach
                @endif
            </div>

            <aside class="cup-rules-facts">
                <div><i aria-hidden="true" class="ph ph-game-controller ph-icon"></i><span>{{ __('ui.platform') }}</span><strong>{{ $allowedPlatformLabel }}</strong></div>
                <div><i aria-hidden="true" class="ph ph-users-three ph-icon"></i><span>{{ __('ui.rework_cup_team_size') }}</span><strong>{{ $cup->isSoloLeaderboard() ? 'Solo' : __('ui.rework_cup_players_count', ['count' => $cup->team_size]) }}</strong></div>
                <div><i aria-hidden="true" class="ph ph-cloud-arrow-up ph-icon"></i><span>Uploads</span><strong>{{ $maxUploads ? __('ui.rework_cup_max_count', ['count' => $maxUploads]) : __('ui.rework_cup_not_limited') }}</strong></div>
                <div><i aria-hidden="true" class="ph ph-chart-line-up ph-icon"></i><span>{{ __('ui.rework_cup_scored_runs') }}</span><strong>{{ $maxScored ? __('ui.rework_cup_max_count', ['count' => $maxScored]) : __('ui.rework_cup_all_valid') }}</strong></div>
                <div><i aria-hidden="true" class="ph ph-user-check ph-icon"></i><span>{{ __('ui.profile') }}</span><strong>{{ $profileRequired ? __('ui.rework_cup_profile_required') : __('ui.rework_cup_profile_optional') }}</strong></div>
                <div><i aria-hidden="true" class="ph ph-handshake ph-icon"></i><span>{{ __('ui.rework_cup_community_actions') }}</span><strong>{{ $minCommunityActions > 0 ? __('ui.rework_cup_required_count', ['count' => $minCommunityActions]) : __('ui.rework_cup_no_requirement') }}</strong></div>
            </aside>
        </div>
    </article>
</section>
<section class="cup-tab-panel" data-cup-detail-panel="leaderboard" id="cup-leaderboard">
    @php
        $leaderboardRows = $leaderboard instanceof \Illuminate\Support\Collection ? $leaderboard->values() : collect($leaderboard ?? [])->values();
        $leaderboardLabel = $cup->isSoloLeaderboard() ? __('ui.players') : 'Teams';
    @endphp

    <article class="cup-panel-card card">
        <div class="cup-panel-head inline">
            <div><span>Leaderboard</span><h2>{{ __('ui.rework_cup_current_leaders') }}</h2></div>
            <strong>{{ $leaderboardRows->count() }} {{ $leaderboardLabel }}</strong>
        </div>

        <div class="cup-leaderboard">
            <div class="cup-board-row head"><span>#</span><span>Team</span><span>{{ __('ui.rework_cup_trophies') }}</span><span>Kills</span><span>{{ __('ui.profile_trophy_points') }}</span></div>

            @forelse($leaderboardRows as $team)
                <div class="cup-board-row">
                    <span>{{ $loop->iteration }}</span>
                    <strong>{{ $team->name }}</strong>
                    <span>{{ number_format((int) ($team->bounty_tokens_total ?? 0)) }}</span>
                    <span>{{ number_format((int) ($team->kills_total ?? 0)) }}</span>
                    <b>{{ number_format((int) ($team->points_total ?? 0)) }}</b>
                </div>
            @empty
                <div class="cup-board-row">
                    <span>–</span>
                    <strong>{{ __('ui.rework_cup_no_teams') }}</strong>
                    <span>0</span>
                    <span>0</span>
                    <b>0</b>
                </div>
            @endforelse
        </div>
    </article>
</section>

<section class="cup-tab-panel" data-cup-detail-panel="prizes" id="cup-prizes">
    <div class="cup-prize-grid">
        <article class="cup-prize-card card"><i aria-hidden="true" class="ph ph-medal ph-icon"></i><span>{{ __('ui.rework_cup_first_place') }}</span><strong>3×10 €</strong><p>{{ __('ui.rework_cup_first_place_text') }}</p></article>
        <article class="cup-prize-card card"><i aria-hidden="true" class="ph ph-gift ph-icon"></i><span>Random Team</span><strong>3×5 €</strong><p>{{ __('ui.rework_cup_random_team_text') }}</p></article>
        <article class="cup-prize-card card"><i aria-hidden="true" class="ph ph-star ph-icon"></i><span>Bonus</span><strong>Hall of Fame</strong><p>{{ __('ui.rework_cup_hof_text') }}</p></article>
    </div>
</section>
<section class="cup-tab-panel" data-cup-detail-panel="submit" id="cup-submit">
    @php
        $submitOpen = $cup->isSubmissionOpen();
        $viewerTeamActive = $viewerTeam && $viewerTeam->status === 'active';
        $viewerCanSubmitTeam = $viewerTeam ? $viewerTeam->canSubmitForCup(auth()->user()) : false;
        $canSubmitNow = $viewerTeamActive && $submitOpen && $viewerCanSubmitTeam;
        $cupUploadLimitMb = max(1, (int) ceil(((int) config('hunthub.upload_limits.cup_submission_screenshot_kb', 10240)) / 1024));
        $teamsUrl = \Illuminate\Support\Facades\Route::has('cups.teams.index') ? route('cups.teams.index', $cup) : '#';
        $submitBlocker = null;

        if (! $viewerTeam) {
            $submitBlocker = $cup->isSoloLeaderboard() ? __('ui.rework_cup_join_before_submit') : __('ui.rework_cup_register_team_before_submit');
        } elseif ($viewerTeam->status === 'disqualified') {
            $submitBlocker = __('ui.rework_cup_team_disqualified');
        } elseif (! $viewerTeamActive) {
            $submitBlocker = __('ui.rework_cup_team_not_active');
        } elseif (! $submitOpen) {
            $submitBlocker = $cup->submissionClosedReason();
        } elseif (! $viewerCanSubmitTeam) {
            $submitBlocker = $cup->isSoloLeaderboard() ? __('ui.rework_cup_not_eligible_submit') : __('ui.rework_cup_captain_only_submit');
        }
    @endphp

    <article class="cup-panel-card card">
        <div class="cup-panel-head">
            <span>{{ __('ui.rework_cup_tab_submit') }}</span>
            <h2>{{ __('ui.rework_cup_submit_title') }}</h2>
            <p>{{ __('ui.rework_cup_submit_intro') }}</p>
        </div>

        @if($canSubmitNow && $viewerTeam)
            <form method="post" action="{{ route('cups.submissions.store', [$cup, $viewerTeam]) }}" enctype="multipart/form-data" class="cup-submit-demo" data-cup-submission-form>
                @csrf

                <label class="cup-upload-box cup-upload-box-input" for="cup-submit-screenshot">
                    <i aria-hidden="true" class="ph ph-cloud-arrow-up ph-icon"></i>
                    <strong>{{ __('ui.rework_cup_drop_screenshots') }}</strong>
                    <span data-cup-submit-file-name>{{ __('ui.rework_cup_file_hint', ['limit' => $cupUploadLimitMb]) }}</span>
                    <input id="cup-submit-screenshot" name="screenshot" type="file" accept="image/*" required data-cup-submit-file-input>
                </label>

                <div class="cup-submit-fields">
                    <label><span>{{ __('ui.rework_cup_trophies') }}</span><input readonly value="1"></label>
                    <label><span>Hunter-Kills</span><input readonly value="8"></label>
                    <label><span>{{ __('ui.rework_cup_comment') }}</span><textarea name="note" maxlength="1200" placeholder="{{ __('ui.rework_cup_submit_note_placeholder') }}">{{ old('note') }}</textarea></label>
                </div>

                <button class="btn" type="submit" data-cup-submit-button>{{ __('ui.rework_cup_submission_save') }}</button>
            </form>
        @else
            <div class="cup-submit-demo">
                <div class="cup-upload-box is-disabled">
                    <i aria-hidden="true" class="ph ph-cloud-arrow-up ph-icon"></i>
                    <strong>{{ __('ui.rework_cup_drop_screenshots') }}</strong>
                    <span>{{ $submitBlocker }}</span>
                </div>

                <div class="cup-submit-fields">
                    <label><span>{{ __('ui.rework_cup_trophies') }}</span><input readonly value="1"></label>
                    <label><span>Hunter-Kills</span><input readonly value="8"></label>
                    <label><span>{{ __('ui.rework_cup_comment') }}</span><textarea readonly>{{ __('ui.rework_cup_submit_note_example') }}</textarea></label>
                </div>

                <a class="btn" href="{{ $teamsUrl }}">{{ $viewerTeam ? __('ui.rework_cup_view_team') : __('ui.rework_cup_register_team') }}</a>
            </div>
        @endif
    </article>
    <div class="cup-submit-progress-modal" data-cup-submit-progress-modal hidden>
        <div class="cup-submit-progress-backdrop" data-cup-submit-progress-close></div>
        <div class="cup-submit-progress-dialog" role="dialog" aria-modal="true" aria-labelledby="cup-submit-progress-title">
            <button class="cup-submit-progress-close" type="button" data-cup-submit-progress-close aria-label="{{ __('ui.preview_action_close') }}">
                <i aria-hidden="true" class="ph ph-x ph-icon"></i>
            </button>

            <span class="cup-submit-progress-kicker" data-cup-submit-progress-kicker>{{ __('ui.rework_cup_submission') }}</span>
            <h3 id="cup-submit-progress-title" data-cup-submit-progress-title>Upload startet ...</h3>
            <p data-cup-submit-progress-text>Dein Screenshot wird hochgeladen.</p>

            <div class="cup-submit-progress-bar" aria-hidden="true">
                <span data-cup-submit-progress-bar style="width:0%"></span>
                <em data-cup-submit-progress-percent>0%</em>
            </div>

            <div class="cup-submit-progress-steps">
                <span data-cup-submit-step="upload">Upload</span>
                <span data-cup-submit-step="process">Verarbeitung</span>
                <span data-cup-submit-step="result">Ergebnis</span>
            </div>

            <div class="cup-submit-progress-result" data-cup-submit-progress-result hidden></div>

            <div class="cup-submit-progress-actions" hidden data-cup-submit-progress-actions>
                <button class="btn" type="button" data-cup-submit-progress-close>{{ __('ui.preview_action_close') }}</button>
                <a class="btn ghost" href="#" data-cup-submit-result-link hidden>{{ __('ui.rework_cup_view_submissions') }}</a>
            </div>
        </div>
    </div>
    <!-- /072 cup submit progress modal -->
</section>

<section class="cup-tab-panel" data-cup-detail-panel="my-submissions" id="cup-my-submissions">
    @php
        $viewerSubmissionRows = collect();

        if (auth()->check()) {
            $viewerSubmissionRows = $cup->submissions
                ->filter(function ($submission) use ($viewerTeam): bool {
                    if ((int) $submission->submitted_by === (int) auth()->id()) {
                        return true;
                    }

                    return $viewerTeam && (int) $submission->cup_team_id === (int) $viewerTeam->id;
                })
                ->sortByDesc(fn ($submission) => $submission->submitted_at ?? $submission->created_at)
                ->values();
        }

        $submissionStatusClass = function ($submission): string {
            return match ($submission->status) {
                'processed', 'approved', 'approved_manual' => 'is-valid',
                'invalid', 'rejected', 'rejected_manual' => 'is-invalid',
                'review_required', 'pending' => 'is-review',
                default => 'is-review',
            };
        };

        $submissionStatusText = function ($submission): string {
            return $submission->statusLabel();
        };
    @endphp

    <article class="cup-panel-card card cup-my-submissions-card">
        <div class="cup-panel-head inline">
            <div><span>{{ __('ui.rework_cup_tab_my_submissions') }}</span><h2>{{ __('ui.rework_cup_upload_history') }}</h2></div>
            <strong>{{ __('ui.rework_cup_submission_count', ['count' => $viewerSubmissionRows->count()]) }}</strong>
        </div>

        <div class="cup-submission-list">
            @forelse($viewerSubmissionRows as $submission)
                @php
                    $screenshotUrl = $submission->screenshot?->url();
                    $submittedAt = $submission->submitted_at ?? $submission->created_at;
                    $extractLabel = $submission->extracted ? __('ui.cup_admin_yes') : __('ui.cup_admin_no');
                    $resultText = $submission->resultSummary();
                    $invalidReason = $submission->invalidReasonLabel();
                @endphp

                <article class="cup-submission-card {{ $submissionStatusClass($submission) }}">
                    <div class="cup-submission-main">
                        <div class="cup-submission-rank">#{{ $submission->id }}</div>

                        <div class="cup-submission-content">
                            <div class="cup-submission-title-row">
                                <strong>{{ $submission->team?->name ?? __('ui.rework_cup_submission') }}</strong>
                                <span>{{ $submissionStatusText($submission) }}</span>
                            </div>

                            @if($submittedAt)
                                <p>{{ $submittedAt->diffForHumans() }}</p>
                            @endif

                            @if($submission->note)
                                <p class="cup-submission-note">{{ $submission->note }}</p>
                            @endif

                            <div class="cup-submission-metrics">
                                <span><b>{{ number_format((int) $submission->points) }}</b> P</span>
                                <span><b>{{ number_format((int) $submission->kills) }}</b> K</span>
                                <span><b>{{ number_format((int) $submission->bounty_tokens) }}</b> B</span>
                                <span>Extract <b>{{ $extractLabel }}</b></span>
                            </div>

                            <div class="cup-submission-result">
                                <span>{{ $resultText }}</span>
                                @if($invalidReason && $invalidReason !== $resultText)
                                    <span>{{ $invalidReason }}</span>
                                @endif
                            </div>
                        </div>

                        @if($screenshotUrl)
                            <a class="cup-submission-shot" href="{{ $screenshotUrl }}" data-cup-submission-shot data-shot-title="#{{ $submission->id }} · {{ $submission->team?->name ?? __('ui.rework_cup_submission') }}">
                                <i aria-hidden="true" class="ph ph-image-square ph-icon"></i> Screenshot
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="cup-submission-empty">
                    <i aria-hidden="true" class="ph ph-cloud-arrow-up ph-icon"></i>
                    <strong>{{ __('ui.rework_cup_no_submissions') }}</strong>
                    <p>{{ __('ui.rework_cup_no_submissions_text') }}</p>
                </div>
            @endforelse
        </div>
    </article>
</section>
<!-- /076 my submissions panel -->

<?php $cupAdminCanManage = $cupAdminCanManage ?? (auth()->check() && $cup->canManage(auth()->user())); ?>
<?php if ($cupAdminCanManage) { ?>
<section class="cup-tab-panel" data-cup-detail-panel="admin-submissions" id="cup-admin-submissions">
    @php
        $adminSubmissionRows = $cup->submissions
            ->sortByDesc(fn ($submission) => $submission->submitted_at ?? $submission->created_at)
            ->values();

        $adminStatusClass = function ($submission): string {
            return match ($submission->status) {
                'processed', 'approved', 'approved_manual' => 'is-valid',
                'invalid', 'rejected', 'rejected_manual' => 'is-invalid',
                'review_required', 'pending' => 'is-review',
                default => 'is-review',
            };
        };

        $adminStatusText = function ($submission): string {
            return $submission->statusLabel();
        };
    @endphp

    <article class="cup-panel-card card cup-admin-submissions-card">
        <div class="cup-panel-head inline">
            <div><span>Admin</span><h2>{{ __('ui.rework_cup_all_submissions') }}</h2></div>
            <strong>{{ __('ui.rework_cup_submission_count', ['count' => $adminSubmissionRows->count()]) }}</strong>
        </div>

        <div class="cup-admin-submission-list">
            @forelse($adminSubmissionRows as $submission)
                @php
                    $screenshotUrl = route('cups.submissions.screenshot', [$cup, $submission]);
                    $submittedAt = $submission->submitted_at ?? $submission->created_at;
                    $extractLabel = $submission->extracted ? __('ui.cup_admin_yes') : __('ui.cup_admin_no');
                    $resultText = $submission->resultSummary();
                    $invalidReason = $submission->invalidReasonLabel();
                    $confidenceLabel = $submission->ai_confidence !== null ? round(((float) $submission->ai_confidence) * 100).'%' : __('ui.rework_unknown');
                    $completeLabel = $submission->ai_complete_screenshot === null ? __('ui.rework_unknown') : ($submission->ai_complete_screenshot ? __('ui.cup_admin_yes') : __('ui.cup_admin_no'));
                @endphp

                <article class="cup-admin-submission-card {{ $adminStatusClass($submission) }}">
                    <div class="cup-admin-submission-top">
                        <div class="cup-admin-submission-rank">#{{ $submission->id }}</div>

                        <div class="cup-admin-submission-main">
                            <div class="cup-admin-title-row">
                                <strong>{{ $submission->team?->name ?? __('ui.rework_cup_submission') }}</strong>
                                <span>{{ $adminStatusText($submission) }}</span>
                            </div>

                            <p>
                                Eingereicht von {{ $submission->submitter?->name ?? $submission->submitter?->username ?? 'HNT Hunter' }}
                                @if($submittedAt) · {{ $submittedAt->diffForHumans() }} @endif
                            </p>

                            @if($submission->note)
                                <p class="cup-admin-note">{{ $submission->note }}</p>
                            @endif

                            <div class="cup-admin-metrics">
                                <span><b>{{ number_format((int) $submission->points) }}</b> P</span>
                                <span><b>{{ number_format((int) $submission->kills) }}</b> K</span>
                                <span><b>{{ number_format((int) $submission->bounty_tokens) }}</b> B</span>
                                <span>Extract <b>{{ $extractLabel }}</b></span>
                            </div>

                            <div class="cup-admin-result">
                                <span>{{ $resultText }}</span>
                                @if($invalidReason && $invalidReason !== $resultText)
                                    <span>{{ $invalidReason }}</span>
                                @endif
                            </div>
                        </div>

                        <a class="cup-admin-shot" href="{{ $screenshotUrl }}" data-cup-submission-shot data-shot-title="#{{ $submission->id }} · {{ $submission->team?->name ?? __('ui.rework_cup_submission') }}">
                            <i aria-hidden="true" class="ph ph-image-square ph-icon"></i> Screenshot
                        </a>
                    </div>

                    <div class="cup-admin-ai-row">
                        <span>Screen: {{ $submission->screen_type ?: __('ui.rework_unknown') }}</span>
                    <span>{{ __('ui.rework_cup_ai_confidence') }}: {{ $confidenceLabel }}</span>
                    <span>{{ __('ui.rework_cup_complete') }}: {{ $completeLabel }}</span>
                    <span>Gamertag: {{ $submission->ai_gamertag ?: __('ui.rework_unknown') }}</span>
                    </div>

                    <details class="cup-admin-correction">
                        <summary>{{ __('ui.cup_manual_score_title') }}</summary>

                        <form method="post" action="{{ route('cups.submissions.manual-score', [$cup, $submission]) }}" class="cup-admin-score-form">
                            @csrf
                            <label><span>{{ __('ui.rework_cup_trophies') }}</span><input name="bounty_tokens" type="number" min="0" max="4" value="{{ (int) $submission->bounty_tokens }}"></label>
                            <label><span>Kills</span><input name="kills" type="number" min="0" max="99" value="{{ (int) $submission->kills }}"></label>
                            <label><span>{{ __('ui.profile_trophy_points') }}</span><input name="points" type="number" min="0" max="999" value="{{ (int) $submission->points }}"></label>
                            <label class="wide"><span>{{ __('ui.preview_cup_note') }}</span><input name="review_note" type="text" maxlength="1200" value="{{ $submission->review_note }}" placeholder="{{ __('ui.rework_cup_internal_note_placeholder') }}"></label>
                            <button class="btn" type="submit">{{ __('ui.preview_action_save') }}</button>
                        </form>
                    </details>

                    <div class="cup-admin-actions">
                        <form method="post" action="{{ route('cups.submissions.rescore', [$cup, $submission]) }}">
                            @csrf
                            <button class="btn ghost" type="submit">{{ __('ui.cup_rescore') }}</button>
                        </form>

                        <form method="post" action="{{ route('cups.submissions.approve', [$cup, $submission]) }}">
                            @csrf
                            <input name="review_note" type="hidden" value="{{ __('ui.rework_cup_manually_confirmed') }}">
                            <button class="btn ghost" type="submit">{{ __('ui.rework_cup_mark_valid') }}</button>
                        </form>

                        <form method="post" action="{{ route('cups.submissions.reject', [$cup, $submission]) }}" class="cup-admin-reject-form">
                            @csrf
                            <input name="review_note" type="text" maxlength="1200" placeholder="{{ __('ui.cup_disqualify_reason_placeholder') }}">
                            <button class="btn danger" type="submit">{{ __('ui.cup_disqualify') }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="cup-submission-empty">
                    <i aria-hidden="true" class="ph ph-clipboard-text ph-icon"></i>
                    <strong>{{ __('ui.rework_cup_no_submissions') }}</strong>
                    <p>{{ __('ui.rework_cup_admin_no_submissions_text') }}</p>
                </div>
            @endforelse
        </div>
    </article>
</section>
<!-- /081 admin submissions panel -->
<?php } ?>


</div>
<div class="cup-shot-modal" data-cup-shot-modal hidden>
    <div class="cup-shot-modal-backdrop" data-cup-shot-close></div>
    <div class="cup-shot-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cup-shot-modal-title">
        <button class="cup-shot-modal-close" type="button" data-cup-shot-close aria-label="{{ __('ui.preview_action_close') }}">
            <i aria-hidden="true" class="ph ph-x ph-icon"></i>
        </button>

        <div class="cup-shot-modal-head">
            <span>Screenshot</span>
            <h3 id="cup-shot-modal-title" data-cup-shot-title>{{ __('ui.rework_cup_submission') }}</h3>
        </div>

        <div class="cup-shot-modal-body">
            <img data-cup-shot-image alt="{{ __('ui.rework_cup_submission_screenshot_alt') }}">
        </div>

        <div class="cup-shot-modal-actions">
            <a class="btn ghost" href="#" target="_blank" rel="noopener" data-cup-shot-open-new>{{ __('ui.rework_open_original') }}</a>
            <button class="btn" type="button" data-cup-shot-close>{{ __('ui.preview_action_close') }}</button>
        </div>
    </div>
</div>
<!-- /078 cup screenshot modal -->

@endsection
