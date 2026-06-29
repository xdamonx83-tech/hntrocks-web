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
<a aria-label="Cup-Menü" class="cup-more" href="#"><i aria-hidden="true" class="ph ph-dots-three ph-icon"></i></a>
</div>
<div class="cup-detail-main">
<div class="cup-visual-column">
<div class="cup-emblem">
<img alt="Summer Cup" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/>
</div>
<div class="cup-skill-list">
<span><i aria-hidden="true" class="ph ph-trophy ph-icon"></i> Trophäen</span>
<span><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i> Hunter-Kills</span>
<span><i aria-hidden="true" class="ph ph-image-square ph-icon"></i> Screenshots</span>
</div>
<div class="cup-side-actions">
<a class="btn" href="#">Team anmelden</a>
<a class="btn ghost" href="#">Einreichung</a>
</div>
</div>
<div class="cup-info-column">
<div class="cup-title-row">
<div>
<h2>HNT Summer Cup</h2>
<span>Trio Console · PS5/Xbox · EU</span>
</div>
<strong class="cup-rarity">Anmeldung offen</strong>
</div>
<p class="cup-summary">Erste Trophäen-Extraktion zählt, Hunter-Kills bringen Bonuspunkte. Captains laden Screenshots hoch und halten ihr Trio sauber im Rennen.</p>
<div class="cup-progress-wrap">
<div class="cup-progress-label"><span>Teilnehmer</span><strong>18 / 32 Teams</strong></div>
<div class="cup-progress"><span style="width:56%"></span><em>56%</em></div>
</div>
<div class="cup-stat-row">
<span><small>Zeitraum</small><b>26.06.–05.07.</b></span>
<span><small>Modus</small><b>Trio</b></span>
<span><small>Status</small><b>Geplant</b></span>
</div>
<div class="cup-bar-grid">
<div><label>Einreichungen</label><span><i style="width:68%"></i></span></div>
<div><label>Leaderboard</label><span><i style="width:42%"></i></span></div>
<div><label>Chat-Aktivität</label><span><i style="width:74%"></i></span></div>
<div><label>Preis-Pool</label><span><i style="width:100%"></i></span></div>
</div>
</div>
</div>
</section>
<section class="card cup-detail-tabs-card">
    <div aria-label="Cup-Bereiche" class="cup-detail-tabs" role="tablist">
        <a class="active" data-cup-detail-tab="overview" href="#cup-overview">Übersicht</a>
<a data-cup-detail-tab="rules" href="#cup-rules">Regeln</a>
<a data-cup-detail-tab="leaderboard" href="#cup-leaderboard">Leaderboard</a>
<a data-cup-detail-tab="prizes" href="#cup-prizes">Preise</a>
<a data-cup-detail-tab="submit" href="#cup-submit">Einreichen</a>
        <a data-cup-detail-tab="my-submissions" href="#cup-my-submissions">Meine Einreichungen</a>
        <?php $cupAdminCanManage = auth()->check() && $cup->canManage(auth()->user()); ?>
        <?php if ($cupAdminCanManage) { ?>
            <a data-cup-detail-tab="admin-submissions" href="#cup-admin-submissions">Admin Einreichungen</a>
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
            <span>Übersicht</span>
            <h2>Beschreibung</h2>
            <p>Alles Wichtige zum Cup als Einstieg. Die einzelnen Tabs werden danach separat sauber gemacht.</p>
        </div>

        <div class="cup-description-copy">
            @forelse($overviewParagraphs as $paragraph)
                <p>{!! nl2br(e($paragraph)) !!}</p>
            @empty
                <p>Noch keine Beschreibung hinterlegt.</p>
            @endforelse
        </div>
    </article>

    <article class="cup-panel-card card cup-overview-chat-card" id="cup-chat">
        <div class="cup-panel-head inline">
            <div>
                <span>Cup-Chat</span>
                <h2>Community-Chat</h2>
                <p>Kurze Absprachen, Fragen und Updates zum Cup.</p>
            </div>
            <strong class="cup-chat-count"><i aria-hidden="true" class="ph ph-chat-circle-dots ph-icon"></i>{{ number_format((int) $cupChatMessagesCount) }} Nachrichten</strong>
        </div>

        <div class="cup-chat-shell">
            <div class="cup-chat-list" data-cup-chat-list aria-label="Cup-Chat Nachrichten">
                @forelse($cupChatMessages as $chatMessage)
                    @include('themes.rework.cups.partials.chat-message', ['chatMessage' => $chatMessage])
                @empty
                    <div class="cup-chat-empty">
                        <i aria-hidden="true" class="ph ph-chat-circle-dots ph-icon"></i>
                        <strong>Noch keine Nachrichten</strong>
                        <p>Starte den Cup-Chat mit einer kurzen Frage oder Info für die Community.</p>
                    </div>
                @endforelse
            </div>

            @auth
                <form class="cup-chat-form" method="post" action="{{ route('cups.chat.store', $cup) }}">
                    @csrf
                    <label for="cup-chat-body">Nachricht schreiben</label>
                    <div class="cup-chat-compose">
                        <textarea id="cup-chat-body" name="body" rows="3" maxlength="1200" required placeholder="Schreibe eine Nachricht zum Cup ...">{{ old('body') }}</textarea>
                        <button class="btn" type="submit">
                            <i aria-hidden="true" class="ph ph-paper-plane-tilt ph-icon"></i>
                            Senden
                        </button>
                    </div>
                    <span>Max. 1200 Zeichen · sichtbar für alle Cup-Besucher.</span>
                </form>
            @else
                <div class="cup-chat-login">
                    <i aria-hidden="true" class="ph ph-lock-key ph-icon"></i>
                    <div>
                        <strong>Einloggen zum Schreiben</strong>
                        <p>Lesen ist möglich. Zum Antworten brauchst du ein HNT.rocks Konto.</p>
                    </div>
                    <a class="btn ghost" href="{{ route('login') }}">Einloggen</a>
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
        $allowedPlatformLabel = $allowedPlatforms !== [] ? implode(' / ', $allowedPlatforms) : (trim((string) $cup->platform) ?: 'Alle Plattformen');
        $maxUploads = data_get($cup->settings, 'submission_limit.max_uploads_per_participant');
        $maxScored = data_get($cup->settings, 'submission_limit.max_scored_runs_per_participant');
        $profileRequired = (bool) data_get($cup->settings, 'participation_requirements.profile_complete', false);
        $minCommunityActions = (int) data_get($cup->settings, 'participation_requirements.min_community_actions', 0);
    @endphp

    <article class="cup-panel-card card cup-rules-real-card">
        <div class="cup-panel-head">
            <span>Regelwerk</span>
            <h2>Regeln</h2>
            <p>Die offiziellen Regeln für diesen Cup. Wertung, Plattform und Teilnahmebedingungen werden hier gebündelt.</p>
        </div>

        <div class="cup-rules-real-layout">
            <div class="cup-rules-copy">
                <h3>Regeltext</h3>
                @forelse($rulesParagraphs as $paragraph)
                    <p>{!! nl2br(e($paragraph)) !!}</p>
                @empty
                    <p>Noch kein Regeltext hinterlegt.</p>
                @endforelse

                @if($scoringParagraphs->isNotEmpty())
                    <h3>Wertung</h3>
                    @foreach($scoringParagraphs as $paragraph)
                        <p>{!! nl2br(e($paragraph)) !!}</p>
                    @endforeach
                @endif
            </div>

            <aside class="cup-rules-facts">
                <div><i aria-hidden="true" class="ph ph-game-controller ph-icon"></i><span>Plattform</span><strong>{{ $allowedPlatformLabel }}</strong></div>
                <div><i aria-hidden="true" class="ph ph-users-three ph-icon"></i><span>Teamgröße</span><strong>{{ $cup->isSoloLeaderboard() ? 'Solo' : $cup->team_size.' Spieler' }}</strong></div>
                <div><i aria-hidden="true" class="ph ph-cloud-arrow-up ph-icon"></i><span>Uploads</span><strong>{{ $maxUploads ? 'max. '.$maxUploads : 'nicht limitiert' }}</strong></div>
                <div><i aria-hidden="true" class="ph ph-chart-line-up ph-icon"></i><span>Gewertete Runs</span><strong>{{ $maxScored ? 'max. '.$maxScored : 'alle gültigen' }}</strong></div>
                <div><i aria-hidden="true" class="ph ph-user-check ph-icon"></i><span>Profil</span><strong>{{ $profileRequired ? 'vollständig nötig' : 'nicht zwingend' }}</strong></div>
                <div><i aria-hidden="true" class="ph ph-handshake ph-icon"></i><span>Community-Aktionen</span><strong>{{ $minCommunityActions > 0 ? $minCommunityActions.' nötig' : 'keine Pflicht' }}</strong></div>
            </aside>
        </div>
    </article>
</section>
<section class="cup-tab-panel" data-cup-detail-panel="leaderboard" id="cup-leaderboard">
    @php
        $leaderboardRows = $leaderboard instanceof \Illuminate\Support\Collection ? $leaderboard->values() : collect($leaderboard ?? [])->values();
        $leaderboardLabel = $cup->isSoloLeaderboard() ? 'Spieler' : 'Teams';
    @endphp

    <article class="cup-panel-card card">
        <div class="cup-panel-head inline">
            <div><span>Leaderboard</span><h2>Aktuelle Spitze</h2></div>
            <strong>{{ $leaderboardRows->count() }} {{ $leaderboardLabel }}</strong>
        </div>

        <div class="cup-leaderboard">
            <div class="cup-board-row head"><span>#</span><span>Team</span><span>Trophäen</span><span>Kills</span><span>Punkte</span></div>

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
                    <strong>Noch keine Teams</strong>
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
        <article class="cup-prize-card card"><i aria-hidden="true" class="ph ph-medal ph-icon"></i><span>1. Platz</span><strong>3×10 €</strong><p>Gutschein nach Wahl für das Gewinner-Trio.</p></article>
        <article class="cup-prize-card card"><i aria-hidden="true" class="ph ph-gift ph-icon"></i><span>Random Team</span><strong>3×5 €</strong><p>Auslosung unter gültigen Teilnehmerteams.</p></article>
        <article class="cup-prize-card card"><i aria-hidden="true" class="ph ph-star ph-icon"></i><span>Bonus</span><strong>Hall of Fame</strong><p>Gewinner werden dauerhaft im Cup-Bereich gezeigt.</p></article>
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
            $submitBlocker = $cup->isSoloLeaderboard() ? 'Erst teilnehmen, dann einreichen.' : 'Erst Team anmelden, dann einreichen.';
        } elseif ($viewerTeam->status === 'disqualified') {
            $submitBlocker = 'Team disqualifiziert.';
        } elseif (! $viewerTeamActive) {
            $submitBlocker = 'Team nicht aktiv.';
        } elseif (! $submitOpen) {
            $submitBlocker = $cup->submissionClosedReason();
        } elseif (! $viewerCanSubmitTeam) {
            $submitBlocker = $cup->isSoloLeaderboard() ? 'Nicht einreichungsberechtigt.' : 'Nur Captain eines vollständigen Teams.';
        }
    @endphp

    <article class="cup-panel-card card">
        <div class="cup-panel-head">
            <span>Einreichen</span>
            <h2>Match-Nachweis hochladen</h2>
            <p>Demo-Uploadbereich für Captain-Screenshots und Ergebnisprüfung.</p>
        </div>

        @if($canSubmitNow && $viewerTeam)
            <form method="post" action="{{ route('cups.submissions.store', [$cup, $viewerTeam]) }}" enctype="multipart/form-data" class="cup-submit-demo" data-cup-submission-form>
                @csrf

                <label class="cup-upload-box cup-upload-box-input" for="cup-submit-screenshot">
                    <i aria-hidden="true" class="ph ph-cloud-arrow-up ph-icon"></i>
                    <strong>Screenshots ablegen</strong>
                    <span data-cup-submit-file-name>Bis zu 3 Dateien · PNG/JPG/WebP · max. {{ $cupUploadLimitMb }} MB</span>
                    <input id="cup-submit-screenshot" name="screenshot" type="file" accept="image/*" required data-cup-submit-file-input>
                </label>

                <div class="cup-submit-fields">
                    <label><span>Trophäen</span><input readonly value="1"></label>
                    <label><span>Hunter-Kills</span><input readonly value="8"></label>
                    <label><span>Kommentar</span><textarea name="note" maxlength="1200" placeholder="Match lief sauber, Screenshots zeigen Ergebnis und Team.">{{ old('note') }}</textarea></label>
                </div>

                <button class="btn" type="submit" data-cup-submit-button>Einreichung speichern</button>
            </form>
        @else
            <div class="cup-submit-demo">
                <div class="cup-upload-box is-disabled">
                    <i aria-hidden="true" class="ph ph-cloud-arrow-up ph-icon"></i>
                    <strong>Screenshots ablegen</strong>
                    <span>{{ $submitBlocker }}</span>
                </div>

                <div class="cup-submit-fields">
                    <label><span>Trophäen</span><input readonly value="1"></label>
                    <label><span>Hunter-Kills</span><input readonly value="8"></label>
                    <label><span>Kommentar</span><textarea readonly>Match lief sauber, Screenshots zeigen Ergebnis und Team.</textarea></label>
                </div>

                <a class="btn" href="{{ $teamsUrl }}">{{ $viewerTeam ? 'Team ansehen' : 'Team anmelden' }}</a>
            </div>
        @endif
    </article>
    <div class="cup-submit-progress-modal" data-cup-submit-progress-modal hidden>
        <div class="cup-submit-progress-backdrop" data-cup-submit-progress-close></div>
        <div class="cup-submit-progress-dialog" role="dialog" aria-modal="true" aria-labelledby="cup-submit-progress-title">
            <button class="cup-submit-progress-close" type="button" data-cup-submit-progress-close aria-label="Schließen">
                <i aria-hidden="true" class="ph ph-x ph-icon"></i>
            </button>

            <span class="cup-submit-progress-kicker" data-cup-submit-progress-kicker>Einreichung</span>
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
                <button class="btn" type="button" data-cup-submit-progress-close>Schließen</button>
                <a class="btn ghost" href="#" data-cup-submit-result-link hidden>Einreichungen ansehen</a>
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
            <div><span>Meine Einreichungen</span><h2>Upload-History</h2></div>
            <strong>{{ $viewerSubmissionRows->count() }} Einreichungen</strong>
        </div>

        <div class="cup-submission-list">
            @forelse($viewerSubmissionRows as $submission)
                @php
                    $screenshotUrl = $submission->screenshot?->url();
                    $submittedAt = $submission->submitted_at ?? $submission->created_at;
                    $extractLabel = $submission->extracted ? 'JA' : 'NEIN';
                    $resultText = $submission->resultSummary();
                    $invalidReason = $submission->invalidReasonLabel();
                @endphp

                <article class="cup-submission-card {{ $submissionStatusClass($submission) }}">
                    <div class="cup-submission-main">
                        <div class="cup-submission-rank">#{{ $submission->id }}</div>

                        <div class="cup-submission-content">
                            <div class="cup-submission-title-row">
                                <strong>{{ $submission->team?->name ?? 'Einreichung' }}</strong>
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
                            <a class="cup-submission-shot" href="{{ $screenshotUrl }}" data-cup-submission-shot data-shot-title="#{{ $submission->id }} · {{ $submission->team?->name ?? 'Einreichung' }}">
                                <i aria-hidden="true" class="ph ph-image-square ph-icon"></i> Screenshot
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="cup-submission-empty">
                    <i aria-hidden="true" class="ph ph-cloud-arrow-up ph-icon"></i>
                    <strong>Noch keine Einreichungen</strong>
                    <p>Wenn du einen Screenshot einreichst, erscheint hier deine Upload-History mit Auswertung.</p>
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
            <div><span>Admin</span><h2>Alle Einreichungen</h2></div>
            <strong>{{ $adminSubmissionRows->count() }} Einreichungen</strong>
        </div>

        <div class="cup-admin-submission-list">
            @forelse($adminSubmissionRows as $submission)
                @php
                    $screenshotUrl = route('cups.submissions.screenshot', [$cup, $submission]);
                    $submittedAt = $submission->submitted_at ?? $submission->created_at;
                    $extractLabel = $submission->extracted ? 'JA' : 'NEIN';
                    $resultText = $submission->resultSummary();
                    $invalidReason = $submission->invalidReasonLabel();
                    $confidenceLabel = $submission->ai_confidence !== null ? round(((float) $submission->ai_confidence) * 100).'%' : 'Unbekannt';
                    $completeLabel = $submission->ai_complete_screenshot === null ? 'Unbekannt' : ($submission->ai_complete_screenshot ? 'Ja' : 'Nein');
                @endphp

                <article class="cup-admin-submission-card {{ $adminStatusClass($submission) }}">
                    <div class="cup-admin-submission-top">
                        <div class="cup-admin-submission-rank">#{{ $submission->id }}</div>

                        <div class="cup-admin-submission-main">
                            <div class="cup-admin-title-row">
                                <strong>{{ $submission->team?->name ?? 'Einreichung' }}</strong>
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

                        <a class="cup-admin-shot" href="{{ $screenshotUrl }}" data-cup-submission-shot data-shot-title="#{{ $submission->id }} · {{ $submission->team?->name ?? 'Einreichung' }}">
                            <i aria-hidden="true" class="ph ph-image-square ph-icon"></i> Screenshot
                        </a>
                    </div>

                    <div class="cup-admin-ai-row">
                        <span>Screen: {{ $submission->screen_type ?: 'Unbekannt' }}</span>
                        <span>KI-Sicherheit: {{ $confidenceLabel }}</span>
                        <span>Vollständig: {{ $completeLabel }}</span>
                        <span>Gamertag: {{ $submission->ai_gamertag ?: 'Unbekannt' }}</span>
                    </div>

                    <details class="cup-admin-correction">
                        <summary>Punkte korrigieren</summary>

                        <form method="post" action="{{ route('cups.submissions.manual-score', [$cup, $submission]) }}" class="cup-admin-score-form">
                            @csrf
                            <label><span>Trophäen</span><input name="bounty_tokens" type="number" min="0" max="4" value="{{ (int) $submission->bounty_tokens }}"></label>
                            <label><span>Kills</span><input name="kills" type="number" min="0" max="99" value="{{ (int) $submission->kills }}"></label>
                            <label><span>Punkte</span><input name="points" type="number" min="0" max="999" value="{{ (int) $submission->points }}"></label>
                            <label class="wide"><span>Notiz</span><input name="review_note" type="text" maxlength="1200" value="{{ $submission->review_note }}" placeholder="Grund oder kurze interne Notiz"></label>
                            <button class="btn" type="submit">Speichern</button>
                        </form>
                    </details>

                    <div class="cup-admin-actions">
                        <form method="post" action="{{ route('cups.submissions.rescore', [$cup, $submission]) }}">
                            @csrf
                            <button class="btn ghost" type="submit">Neu auswerten</button>
                        </form>

                        <form method="post" action="{{ route('cups.submissions.approve', [$cup, $submission]) }}">
                            @csrf
                            <input name="review_note" type="hidden" value="Manuell bestätigt">
                            <button class="btn ghost" type="submit">Gültig setzen</button>
                        </form>

                        <form method="post" action="{{ route('cups.submissions.reject', [$cup, $submission]) }}" class="cup-admin-reject-form">
                            @csrf
                            <input name="review_note" type="text" maxlength="1200" placeholder="Grund für Disqualifikation">
                            <button class="btn danger" type="submit">Disqualifizieren</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="cup-submission-empty">
                    <i aria-hidden="true" class="ph ph-clipboard-text ph-icon"></i>
                    <strong>Noch keine Einreichungen</strong>
                    <p>Sobald Teilnehmer Screenshots einreichen, kannst du sie hier prüfen, korrigieren oder neu auswerten.</p>
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
        <button class="cup-shot-modal-close" type="button" data-cup-shot-close aria-label="Schließen">
            <i aria-hidden="true" class="ph ph-x ph-icon"></i>
        </button>

        <div class="cup-shot-modal-head">
            <span>Screenshot</span>
            <h3 id="cup-shot-modal-title" data-cup-shot-title>Einreichung</h3>
        </div>

        <div class="cup-shot-modal-body">
            <img data-cup-shot-image alt="Cup Einreichung Screenshot">
        </div>

        <div class="cup-shot-modal-actions">
            <a class="btn ghost" href="#" target="_blank" rel="noopener" data-cup-shot-open-new>Original öffnen</a>
            <button class="btn" type="button" data-cup-shot-close>Schließen</button>
        </div>
    </div>
</div>
<!-- /078 cup screenshot modal -->

@endsection
