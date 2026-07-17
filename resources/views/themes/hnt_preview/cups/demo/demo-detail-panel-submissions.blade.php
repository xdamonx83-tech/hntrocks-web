@php
    $isEnglish = app()->getLocale() === 'en';
    $liveSubmissions = $cup->submissions->sortByDesc(fn ($submission) => $submission->submitted_at ?: $submission->created_at)->values();
    $canModerateSubmissions = auth()->check() && $cup->canManage(auth()->user());
@endphp

<section class="cup-tab-panel" data-cup-panel="submissions" hidden tabindex="0">
    <div class="cup-submission-list">
        @forelse($liveSubmissions as $submission)
            @php
                $isApproved = in_array($submission->status, \App\Models\CupSubmission::scoredStatuses(), true);
                $isRejected = in_array($submission->status, ['invalid', 'rejected', 'rejected_manual'], true);
                $statusClass = $isApproved ? 'approved' : ($isRejected ? 'rejected' : 'review');
                $iconId = $isApproved ? '#i-check' : ($isRejected ? '#i-x' : '#i-eye');
                $submissionOwner = $submission->team?->displayName()
                    ?: ($submission->submitter?->username ?: $submission->submitter?->name ?: ($isEnglish ? 'Hunter' : 'Jäger'));
                $submittedAt = $submission->submitted_at ?: $submission->created_at;
                $detailParts = [];
                $detailParts[] = (int) $submission->kills.' Kills';
                $detailParts[] = (int) $submission->bounty_tokens.' '.($isEnglish ? 'trophies' : 'Trophäen');
                $detailParts[] = $submission->extracted ? ($isEnglish ? 'extracted' : 'extrahiert') : ($isEnglish ? 'no extraction' : 'keine Extraktion');
                if ($submittedAt) {
                    $detailParts[] = $submittedAt->diffForHumans();
                }
                $screenshotUrl = $submission->screenshot
                    ? route('cups.submissions.screenshot', [$cup, $submission])
                    : null;
                $confidenceLabel = $submission->ai_confidence !== null
                    ? number_format((float) $submission->ai_confidence * 100, 0).'%'
                    : '—';
            @endphp
            <article class="{{ $canModerateSubmissions ? 'cup-submission-row--moderatable' : '' }}" data-submission-id="{{ $submission->id }}">
                <span class="{{ $statusClass }}"><svg><use href="{{ $iconId }}"></use></svg></span>
                <div>
                    <strong>{{ $submissionOwner }} · #{{ $submission->id }}</strong>
                    <small>{{ implode(' · ', $detailParts) }}</small>
                </div>
                <b>{{ (int) $submission->points }} {{ $isEnglish ? 'points' : 'Punkte' }}</b>
                <em>{{ $submission->statusLabel() }}</em>
                @if($canModerateSubmissions)
                    <div class="cup-submission-actions">
                        @if($screenshotUrl)
                            <a href="{{ $screenshotUrl }}" target="_blank" rel="noopener" aria-label="{{ $isEnglish ? 'Open screenshot' : 'Screenshot öffnen' }}">
                                <svg><use href="#i-eye"></use></svg>
                                {{ $isEnglish ? 'Screenshot' : 'Screen' }}
                            </a>
                        @endif
                        <button type="button" data-open-cup-submission-moderation="{{ $submission->id }}">
                            {{ $isEnglish ? 'Moderate' : 'Moderieren' }}
                        </button>
                    </div>
                @endif
            </article>

            @if($canModerateSubmissions)
                <div class="cup-submission-moderation" id="cupSubmissionModeration{{ $submission->id }}" hidden>
                    <button class="cup-submission-moderation__backdrop" type="button" aria-label="{{ $isEnglish ? 'Close moderation' : 'Moderation schließen' }}" data-close-cup-submission-moderation></button>
                    <section class="cup-submission-moderation__panel" role="dialog" aria-modal="true" aria-labelledby="cupSubmissionModerationTitle{{ $submission->id }}">
                        <header class="cup-submission-moderation__head">
                            <div>
                                <span>{{ $isEnglish ? 'SUBMISSION REVIEW' : 'EINREICHUNG PRÜFEN' }}</span>
                                <h2 id="cupSubmissionModerationTitle{{ $submission->id }}">{{ $submissionOwner }} · #{{ $submission->id }}</h2>
                            </div>
                            <button class="cup-submission-moderation__close" type="button" aria-label="{{ $isEnglish ? 'Close' : 'Schließen' }}" data-close-cup-submission-moderation>×</button>
                        </header>

                        <div class="cup-submission-moderation__layout">
                            <article class="cup-submission-moderation__media">
                                <div class="cup-submission-moderation__image">
                                    @if($screenshotUrl)
                                        <img src="{{ $screenshotUrl }}" alt="{{ $isEnglish ? 'Submitted screenshot' : 'Eingereichter Screenshot' }}" loading="lazy"/>
                                    @else
                                        <div class="cup-submission-moderation__image-empty">{{ $isEnglish ? 'No screenshot file is available.' : 'Keine Screenshot-Datei verfügbar.' }}</div>
                                    @endif
                                </div>
                                <div class="cup-submission-moderation__facts">
                                    <span><small>Kills</small><strong>{{ (int) $submission->kills }}</strong></span>
                                    <span><small>{{ $isEnglish ? 'Trophies' : 'Trophäen' }}</small><strong>{{ (int) $submission->bounty_tokens }}</strong></span>
                                    <span><small>{{ $isEnglish ? 'Points' : 'Punkte' }}</small><strong>{{ (int) $submission->points }}</strong></span>
                                    <span><small>{{ $isEnglish ? 'AI confidence' : 'KI-Sicherheit' }}</small><strong>{{ $confidenceLabel }}</strong></span>
                                </div>
                            </article>

                            <article class="cup-submission-moderation__tools">
                                <header>
                                    <span>{{ $isEnglish ? 'MANUAL SCORE' : 'MANUELLE WERTUNG' }}</span>
                                    <h3>{{ $isEnglish ? 'Correct values and save score' : 'Werte korrigieren und speichern' }}</h3>
                                </header>

                                <form class="cup-submission-score-form" method="post" action="{{ route('cups.submissions.manual-score', [$cup, $submission]) }}">
                                    @csrf
                                    <div class="cup-submission-score-grid">
                                        <label>
                                            Kills
                                            <input type="number" name="kills" min="0" max="99" required value="{{ (int) $submission->kills }}"/>
                                        </label>
                                        <label>
                                            {{ $isEnglish ? 'Trophies' : 'Trophäen' }}
                                            <input type="number" name="bounty_tokens" min="0" max="4" required value="{{ (int) $submission->bounty_tokens }}"/>
                                        </label>
                                        <label>
                                            {{ $isEnglish ? 'Points' : 'Punkte' }}
                                            <input type="number" name="points" min="0" max="999" value="{{ (int) $submission->points }}"/>
                                        </label>
                                    </div>
                                    <label>
                                        {{ $isEnglish ? 'Moderator note' : 'Moderator-Notiz' }}
                                        <textarea name="review_note" maxlength="1200" data-cup-submission-review-note="{{ $submission->id }}" placeholder="{{ $isEnglish ? 'Optional note for the participant' : 'Optionale Notiz für den Teilnehmer' }}">{{ $submission->review_note }}</textarea>
                                    </label>
                                    <button type="submit">{{ $isEnglish ? 'Save manual score' : 'Manuelle Wertung speichern' }}</button>
                                </form>

                                <div class="cup-submission-moderation__quick">
                                    <span>{{ $isEnglish ? 'QUICK ACTIONS' : 'SCHNELLAKTIONEN' }}</span>
                                    <div class="cup-submission-moderation__quick-grid">
                                        <form method="post" action="{{ route('cups.submissions.approve', [$cup, $submission]) }}" data-sync-review-note="{{ $submission->id }}">
                                            @csrf
                                            <input type="hidden" name="review_note" value=""/>
                                            <button class="approve" type="submit">{{ $isEnglish ? 'Approve values' : 'Werte freigeben' }}</button>
                                        </form>
                                        <form method="post" action="{{ route('cups.submissions.rescore', [$cup, $submission]) }}">
                                            @csrf
                                            <button type="submit">{{ $isEnglish ? 'Run AI again' : 'KI erneut prüfen' }}</button>
                                        </form>
                                        <form method="post" action="{{ route('cups.submissions.reject', [$cup, $submission]) }}" data-sync-review-note="{{ $submission->id }}" data-confirm-reject="{{ $isEnglish ? 'Reject this submission?' : 'Diese Einreichung wirklich ablehnen?' }}">
                                            @csrf
                                            <input type="hidden" name="review_note" value=""/>
                                            <button class="reject" type="submit">{{ $isEnglish ? 'Reject' : 'Ablehnen' }}</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="cup-submission-moderation__source">
                                    <span>{{ $submission->statusLabel() }} · {{ $submittedAt?->translatedFormat('d.m.Y · H:i') ?: '—' }}</span>
                                    @if($screenshotUrl)
                                        <a href="{{ $screenshotUrl }}" target="_blank" rel="noopener">{{ $isEnglish ? 'Open original' : 'Original öffnen' }}</a>
                                    @endif
                                </div>
                            </article>
                        </div>
                    </section>
                </div>
            @endif
        @empty
            <div class="cup-submission-empty">
                {{ $isEnglish ? 'No submissions have been uploaded yet.' : 'Für diesen Cup wurden noch keine Einreichungen hochgeladen.' }}
            </div>
        @endforelse
    </div>
</section>
