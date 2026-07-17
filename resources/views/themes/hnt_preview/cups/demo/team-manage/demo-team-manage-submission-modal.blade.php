@php
    $submissionModalMaxKb = max(1, (int) config('hunthub.upload_limits.cup_submission_screenshot_kb', 10240));
    $submissionModalMaxBytes = $submissionModalMaxKb * 1024;
    $submissionModalMaxMb = rtrim(rtrim(number_format($submissionModalMaxKb / 1024, 1, ',', '.'), '0'), ',');
    $submissionModalRemaining = $teamSubmissionLimit === null
        ? null
        : max(0, $teamSubmissionLimit - $teamSubmissionCount);
@endphp

@if($teamCanSubmit)
<div class="team-submission-modal" id="teamSubmissionModal" hidden>
    <button
        class="team-submission-modal__backdrop"
        type="button"
        aria-label="{{ $t('Modal schließen', 'Close modal') }}"
        data-close-team-submission-modal
    ></button>

    <section
        class="team-submission-modal__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="teamSubmissionModalTitle"
    >
        <header class="team-submission-modal__head">
            <div>
                <span>{{ mb_strtoupper($cup->title) }}</span>
                <h2 id="teamSubmissionModalTitle">{{ $t('Screenshot einreichen', 'Submit screenshot') }}</h2>
            </div>
            <button
                class="team-submission-modal__close"
                type="button"
                aria-label="{{ $t('Schließen', 'Close') }}"
                data-close-team-submission-modal
            >×</button>
        </header>

        <div class="team-submission-modal__cup">
            <img src="{{ $teamCoverUrl }}" alt="{{ $cup->title }}"/>
            <div>
                <span>{{ $t('EINREICHUNG FÜR', 'SUBMISSION FOR') }}</span>
                <strong>{{ $cup->title }}</strong>
                <small>{{ $team->displayName() }} · {{ $teamSubmissionCount }}{{ $teamSubmissionLimit ? ' / '.$teamSubmissionLimit : '' }} {{ $t('genutzt', 'used') }}</small>
            </div>
            <b>{{ $submissionModalRemaining ?? '∞' }}<small>{{ $t('frei', 'left') }}</small></b>
        </div>

        <form
            class="team-submission-modal__form"
            id="teamSubmissionModalForm"
            method="post"
            action="{{ route('cups.submissions.store', [$cup, $team]) }}"
            enctype="multipart/form-data"
            data-max-bytes="{{ $submissionModalMaxBytes }}"
            data-file-required="{{ $t('Wähle zuerst einen Screenshot aus.', 'Choose a screenshot first.') }}"
            data-file-too-large="{{ $t('Der Screenshot ist größer als '.$submissionModalMaxMb.' MB.', 'The screenshot is larger than '.$submissionModalMaxMb.' MB.') }}"
            data-file-invalid="{{ $t('Bitte wähle eine gültige Bilddatei aus.', 'Choose a valid image file.') }}"
            data-uploading="{{ $t('Screenshot wird hochgeladen …', 'Uploading screenshot …') }}"
            data-analyzing="{{ $t('Screenshot wird geprüft …', 'Analyzing screenshot …') }}"
            data-success="{{ $t('Einreichung wurde verarbeitet.', 'Submission was processed.') }}"
            data-error="{{ $t('Die Einreichung konnte nicht gesendet werden.', 'The submission could not be sent.') }}"
        >
            @csrf

            <label class="team-submission-modal__drop" for="teamSubmissionScreenshot" data-team-submission-drop>
                <input
                    id="teamSubmissionScreenshot"
                    name="screenshot"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    required
                    hidden
                    data-team-submission-file
                />

                <span class="team-submission-modal__empty" data-team-submission-empty>
                    <i><svg><use href="#i-image"></use></svg></i>
                    <strong>{{ $t('Screenshot auswählen', 'Choose screenshot') }}</strong>
                    <small>{{ $t('PNG, JPG oder WEBP · maximal '.$submissionModalMaxMb.' MB', 'PNG, JPG or WEBP · maximum '.$submissionModalMaxMb.' MB') }}</small>
                    <em>{{ $t('Datei hier ablegen oder klicken', 'Drop file here or click') }}</em>
                </span>

                <span class="team-submission-modal__preview" data-team-submission-preview hidden>
                    <img alt="{{ $t('Screenshot-Vorschau', 'Screenshot preview') }}" data-team-submission-preview-image/>
                    <span>
                        <strong data-team-submission-file-name></strong>
                        <small data-team-submission-file-size></small>
                        <em>{{ $t('Klicken, um eine andere Datei zu wählen', 'Click to choose another file') }}</em>
                    </span>
                </span>
            </label>

            <label class="team-submission-modal__note" for="teamSubmissionNote">
                <span>{{ $t('Notiz', 'Note') }} <small>{{ $t('optional', 'optional') }}</small></span>
                <textarea
                    id="teamSubmissionNote"
                    name="note"
                    maxlength="1200"
                    rows="3"
                    placeholder="{{ $t('Hinweis für die Prüfung …', 'Note for the review …') }}"
                ></textarea>
            </label>

            <div class="team-submission-modal__notice" role="alert" data-team-submission-error hidden></div>

            <div class="team-submission-modal__progress" data-team-submission-progress hidden>
                <div><i data-team-submission-progress-bar></i></div>
                <span data-team-submission-progress-text></span>
            </div>

            <footer class="team-submission-modal__actions">
                <button type="button" data-close-team-submission-modal>{{ $t('Abbrechen', 'Cancel') }}</button>
                <button class="is-primary" type="submit" data-team-submission-submit>
                    {{ $t('Screenshot einreichen', 'Submit screenshot') }}
                </button>
            </footer>
        </form>
    </section>
</div>
@endif
