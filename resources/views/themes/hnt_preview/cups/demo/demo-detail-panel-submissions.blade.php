@php
    $isEnglish = app()->getLocale() === 'en';
    $liveSubmissions = $cup->submissions->sortByDesc(fn ($submission) => $submission->submitted_at ?: $submission->created_at)->values();
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
            @endphp
            <article data-submission-id="{{ $submission->id }}">
                <span class="{{ $statusClass }}"><svg><use href="{{ $iconId }}"></use></svg></span>
                <div>
                    <strong>{{ $submissionOwner }} · #{{ $submission->id }}</strong>
                    <small>{{ implode(' · ', $detailParts) }}</small>
                </div>
                <b>{{ (int) $submission->points }} {{ $isEnglish ? 'points' : 'Punkte' }}</b>
                <em>{{ $submission->statusLabel() }}</em>
            </article>
        @empty
            <div class="cup-submission-empty">
                {{ $isEnglish ? 'No submissions have been uploaded yet.' : 'Für diesen Cup wurden noch keine Einreichungen hochgeladen.' }}
            </div>
        @endforelse
    </div>
</section>
