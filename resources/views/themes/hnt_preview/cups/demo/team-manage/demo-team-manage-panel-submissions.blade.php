@php
    $submissionScoredStatuses = \App\Models\CupSubmission::scoredStatuses();
    $submissionReviewStatuses = ['pending', 'review_required'];
    $submissionRejectedStatuses = ['invalid', 'rejected', 'rejected_manual'];
    $submissionScoredCount = $teamSubmissions->whereIn('status', $submissionScoredStatuses)->count();
    $submissionReviewCount = $teamSubmissions->whereIn('status', $submissionReviewStatuses)->count();
    $submissionRejectedCount = $teamSubmissions->whereIn('status', $submissionRejectedStatuses)->count();
    $submissionPointsTotal = (int) $teamSubmissions->whereIn('status', $submissionScoredStatuses)->sum('points');
    $submissionSlotsFree = $teamSubmissionLimit === null
        ? null
        : max(0, $teamSubmissionLimit - $teamSubmissionCount);
    $submissionCooldownMinutes = max(0, (int) config('hunthub.cups.submission_cooldown_minutes', 30));
@endphp
<section class="team-panel" data-team-panel="submissions" hidden="">
<div class="team-section-intro">
<div>
<span>{{ $teamSubmissionCount }}{{ $teamSubmissionLimit ? ' / '.$teamSubmissionLimit : '' }} {{ $t('GENUTZT', 'USED') }}</span>
<h3>{{ $t('Einreichungen', 'Submissions') }}</h3>
<p>{{ $isCaptain
    ? $t('Du kannst als Captain die Screenshots deines Teams einreichen und den Prüfstatus verfolgen.', 'As captain, you can submit your team screenshots and follow their review status.')
    : $t('Hier siehst du alle Einreichungen deines Teams. Neue Screenshots kann nur der Captain senden.', 'Here you can see all team submissions. Only the captain can upload new screenshots.') }}</p>
</div>
@if($teamCanSubmit)
<button class="team-primary-button" onclick="window.location.href='{{ route('cups.show.section', [$cup, 'submit']) }}'" type="button">{{ $t('Neue Einreichung', 'New submission') }}</button>
@endif
</div>
<div class="team-submission-summary">
<article><span>{{ $t('Gewertet', 'Scored') }}</span><strong>{{ $submissionScoredCount }}</strong><small>{{ $submissionPointsTotal }} {{ $t('Punkte', 'points') }}</small></article>
<article><span>{{ $t('In Prüfung', 'In review') }}</span><strong>{{ $submissionReviewCount }}</strong><small>{{ $submissionRejectedCount }} {{ $t('abgelehnt', 'rejected') }}</small></article>
<article><span>{{ $t('Frei', 'Available') }}</span><strong>{{ $submissionSlotsFree ?? '∞' }}</strong><small>{{ $t('Uploads verbleibend', 'uploads remaining') }}</small></article>
</div>
<div class="team-submission-list">
@forelse($teamSubmissions as $submission)
@php
    $submissionIsScored = in_array($submission->status, $submissionScoredStatuses, true);
    $submissionIsReview = in_array($submission->status, $submissionReviewStatuses, true);
    $submissionTone = $submissionIsScored ? 'approved' : 'review';
    $submissionIcon = $submissionIsScored ? 'i-check' : 'i-eye';
    $submissionUser = $submission->submitter;
    $submissionUserName = $submissionUser?->username ?: $submissionUser?->name ?: 'Hunter';
    $submissionAt = $submission->submitted_at ?: $submission->created_at;
@endphp
<article>
<span class="{{ $submissionTone }}"><svg><use href="#{{ $submissionIcon }}"></use></svg></span>
<div>
<strong>{{ $submissionUserName }} · #{{ $submission->id }}</strong>
<small>{{ (int) $submission->kills }} Kills · {{ (int) $submission->bounty_tokens }} {{ $t('Trophäen', 'trophies') }} · {{ $submission->extracted ? $t('extrahiert', 'extracted') : $t('nicht extrahiert', 'not extracted') }} · {{ $submissionAt?->diffForHumans() ?: '—' }}</small>
</div>
<b>{{ (int) $submission->points }} {{ $t('Punkte', 'points') }}</b>
<em>{{ $submission->statusLabel() }}</em>
@if($submission->screenshot)
<button aria-label="{{ $t('Screenshot öffnen', 'Open screenshot') }}" onclick="window.open('{{ route('cups.submissions.screenshot', [$cup, $submission]) }}', '_blank', 'noopener')" type="button"><svg><use href="#i-eye"></use></svg></button>
@else
<button aria-label="{{ $t('Kein Screenshot verfügbar', 'No screenshot available') }}" disabled type="button"><svg><use href="#i-eye"></use></svg></button>
@endif
</article>
@empty
<article>
<span class="review"><svg><use href="#i-image"></use></svg></span>
<div><strong>{{ $t('Noch keine Einreichungen', 'No submissions yet') }}</strong><small>{{ $t('Sobald ein Screenshot eingereicht wurde, erscheint er hier mit Prüfstatus und Punkten.', 'Once a screenshot is submitted, it will appear here with its review status and points.') }}</small></div>
<b>0 {{ $t('Punkte', 'points') }}</b><em>{{ $t('Offen', 'Open') }}</em>
<button disabled type="button"><svg><use href="#i-eye"></use></svg></button>
</article>
@endforelse
</div>
<article class="team-panel-card team-upload-rules">
<header><div><span>{{ $t('UPLOAD-HINWEISE', 'UPLOAD NOTES') }}</span><h3>{{ $t('Vor dem Einreichen', 'Before submitting') }}</h3></div></header>
<div><span>01</span><p>{{ $t('Der vollständige Match-Screenshot muss gut lesbar und unverändert sein.', 'The complete match screenshot must be clearly readable and unmodified.') }}</p></div>
<div><span>02</span><p>{{ $isCaptain ? $t('Du reichst als Captain für das gesamte Team ein.', 'As captain, you submit on behalf of the whole team.') : $t('Nur der Captain des Teams kann neue Screenshots einreichen.', 'Only the team captain can submit new screenshots.') }}</p></div>
<div><span>03</span><p>{{ $teamSubmissionLimit
    ? $t('Pro Teilnehmer sind maximal '.$teamSubmissionLimit.' Uploads erlaubt. Cooldown: '.$submissionCooldownMinutes.' Minuten.', 'A maximum of '.$teamSubmissionLimit.' uploads per participant is allowed. Cooldown: '.$submissionCooldownMinutes.' minutes.')
    : $t('Zwischen zwei Uploads gilt ein Cooldown von '.$submissionCooldownMinutes.' Minuten.', 'A cooldown of '.$submissionCooldownMinutes.' minutes applies between uploads.') }}</p></div>
</article>
</section>