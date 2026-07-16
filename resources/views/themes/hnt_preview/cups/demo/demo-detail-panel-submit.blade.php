@php
    $isEnglish = app()->getLocale() === 'en';
    $viewer = auth()->user();
    $viewerIsCaptain = $viewerTeam ? $viewerTeam->isCaptain($viewer) : false;
    $viewerTeamComplete = $viewerTeam ? $viewerTeam->isComplete() : false;
    $uploadLimitReached = $viewerTeam && $maxUploadsPerParticipant
        ? $viewerTeamSubmissionCount >= $maxUploadsPerParticipant
        : false;
    $viewerCanUpload = $viewerTeam
        && $viewerTeam->status === 'active'
        && $submissionOpen
        && ($soloCup || $viewerIsCaptain)
        && ($soloCup || $viewerTeamComplete)
        && ! $uploadLimitReached;
    $uploadLimitLabel = $maxUploadsPerParticipant
        ? $viewerTeamSubmissionCount.' / '.$maxUploadsPerParticipant
        : (string) $viewerTeamSubmissionCount;
@endphp

<section class="cup-tab-panel" data-cup-panel="submit" hidden tabindex="0">
    @if($viewerCanUpload)
        <form class="cup-submit-layout" method="post" action="{{ route('cups.submissions.store', [$cup, $viewerTeam]) }}" enctype="multipart/form-data">
            @csrf
            <label class="cup-upload-zone">
                <input accept="image/jpeg,image/png,image/webp" name="screenshot" type="file" required/>
                <span><svg><use href="#i-image"></use></svg></span>
                <strong>{{ $isEnglish ? 'Select screenshot' : 'Screenshot auswählen' }}</strong>
                <small>{{ $isEnglish ? 'JPG, PNG or WebP · maximum 10 MB' : 'JPG, PNG oder WebP · maximal 10 MB' }}</small>
            </label>

            <div class="cup-submit-copy">
                <span>{{ $isEnglish ? 'YOUR NEXT SUBMISSION' : 'DEINE NÄCHSTE EINREICHUNG' }}</span>
                <h3>{{ $soloCup ? ($isEnglish ? 'Score for '.$viewerTeam->displayName() : 'Score für '.$viewerTeam->displayName()) : ($isEnglish ? 'Score for '.$viewerTeam->displayName() : 'Score für '.$viewerTeam->displayName()) }}</h3>
                <p>{{ $isEnglish ? 'The screenshot is checked according to the current cup rules before it appears in the scores.' : 'Der Screenshot wird nach den aktuellen Cup-Regeln geprüft, bevor er in den Scores erscheint.' }}</p>
                <div><strong>{{ $uploadLimitLabel }}</strong><span>{{ $isEnglish ? 'uploads used' : 'Uploads genutzt' }}</span></div>
                <button type="submit">{{ $isEnglish ? 'Submit screenshot' : 'Screenshot einreichen' }}</button>
            </div>
        </form>
    @else
        <div class="cup-submit-layout">
            <div class="cup-upload-zone is-disabled">
                <span><svg><use href="#i-image"></use></svg></span>
                <strong>{{ $isEnglish ? 'Submission unavailable' : 'Einreichung nicht möglich' }}</strong>
                <small>
                    @if(! $viewerTeam)
                        {{ $isEnglish ? 'Create or join a cup team first.' : 'Erstelle zuerst ein Cup-Team oder tritt einem Team bei.' }}
                    @elseif(! $submissionOpen)
                        {{ $cup->submissionClosedReason() }}
                    @elseif($uploadLimitReached)
                        {{ $isEnglish ? 'The upload limit has been reached.' : 'Das Upload-Limit wurde erreicht.' }}
                    @elseif(! $soloCup && ! $viewerIsCaptain)
                        {{ $isEnglish ? 'Only the captain can submit screenshots.' : 'Nur der Captain kann Screenshots einreichen.' }}
                    @elseif(! $soloCup && ! $viewerTeamComplete)
                        {{ $isEnglish ? 'The team must be complete first.' : 'Das Team muss zuerst vollständig sein.' }}
                    @else
                        {{ $isEnglish ? 'The current team cannot submit.' : 'Das aktuelle Team kann nicht einreichen.' }}
                    @endif
                </small>
            </div>

            <div class="cup-submit-copy">
                <span>{{ $isEnglish ? 'SUBMISSION STATUS' : 'EINREICHUNGSSTATUS' }}</span>
                <h3>{{ $viewerTeam?->displayName() ?: ($isEnglish ? 'No cup team' : 'Kein Cup-Team') }}</h3>
                <p>{{ $isEnglish ? 'Open team management to complete the missing step.' : 'Öffne die Teamverwaltung, um den fehlenden Schritt abzuschließen.' }}</p>
                <div><strong>{{ $uploadLimitLabel }}</strong><span>{{ $isEnglish ? 'uploads used' : 'Uploads genutzt' }}</span></div>
                <a class="cup-submit-manage-link" href="{{ route('cups.teams.index', $cup) }}">{{ $isEnglish ? 'Open team management' : 'Teamverwaltung öffnen' }}</a>
            </div>
        </div>
    @endif
</section>
