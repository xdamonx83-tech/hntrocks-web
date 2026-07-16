@extends('themes.hnt_preview.layouts.app')

@section('title', (app()->getLocale() === 'en' ? 'Cup teams' : 'Cup-Teams').' · '.$cup->title)
@section('main_class', 'cup-detail-main cup-team-flow-main')
@section('right_sidebar')
@endsection

@push('head')
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/cup-team-flow.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/cup-team-flow.css')) ?: time() }}" rel="stylesheet">
@endpush

@php
    $isEnglish = app()->getLocale() === 'en';
    $viewer = auth()->user();
    $registrationOpen = $cup->isRegistrationOpen();
    $submissionOpen = $cup->isSubmissionOpen();
    $viewerEligibility = $viewer
        ? $cup->participationEligibility($viewer)
        : ['eligible' => false, 'messages' => [$isEnglish ? 'Please sign in first.' : 'Bitte melde dich zuerst an.']];
    $viewerTeamMembers = $viewerTeam
        ? $viewerTeam->members->where('status', 'active')->values()
        : collect();
    $teamSize = max(1, (int) ($cup->team_size ?: 1));
    $viewerIsCaptain = $viewerTeam ? $viewerTeam->isCaptain($viewer) : false;
    $viewerCanManage = $viewerTeam && ($viewerIsCaptain || $canManage);
    $viewerTeamComplete = $viewerTeam ? $viewerTeam->isComplete() : false;
    $viewerUploadLimit = $cup->maxSubmissionsPerParticipant();
    $viewerUploadCount = $viewerTeam ? $viewerTeam->submissions()->count() : 0;
    $viewerUploadLimitReached = $viewerUploadLimit !== null && $viewerUploadCount >= $viewerUploadLimit;
    $viewerCanSubmit = $viewerTeam
        && $viewerTeam->status === 'active'
        && $submissionOpen
        && $viewerIsCaptain
        && $viewerTeamComplete
        && ! $viewerUploadLimitReached;
    $inviteUrl = $viewerTeam ? route('cups.teams.join', [$cup, $viewerTeam->join_token]) : null;
    $coverUrl = $cup->coverUrl();
@endphp

@section('content')
<div class="cup-team-flow" id="cup-team-finder">
    <section class="cup-team-flow__hero" style="background-image:url('{{ $coverUrl }}')">
        <div class="cup-team-flow__hero-copy">
            <span class="cup-team-flow__eyebrow">HNT.ROCKS CUP</span>
            <h1>{{ $viewerTeam ? ($isEnglish ? 'Manage your team' : 'Dein Team verwalten') : ($isEnglish ? 'Find a cup team' : 'Cup-Team finden') }}</h1>
            <p>{{ $cup->title }} · {{ $cup->modeLabel() }} · {{ $teamSize }} {{ $isEnglish ? 'players per team' : 'Spieler pro Team' }}</p>
        </div>
        <a class="cup-team-flow__secondary cup-team-flow__back" href="{{ route('cups.show', $cup) }}">
            {{ $isEnglish ? 'Back to cup' : 'Zurück zum Cup' }}
        </a>
    </section>

    @if(session('status'))
        <div class="cup-team-flow__notice">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="cup-team-flow__notice is-error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if($viewerTeam)
        <section class="cup-team-flow__grid">
            <article class="cup-team-flow__panel is-dark">
                <div class="cup-team-flow__panel-head">
                    <div>
                        <span class="cup-team-flow__eyebrow">{{ $isEnglish ? 'YOUR TEAM' : 'DEIN TEAM' }}</span>
                        <h2>{{ $viewerTeam->displayName() }}</h2>
                        <p class="cup-team-flow__muted">{{ $viewerTeamMembers->count() }}/{{ $teamSize }} · {{ $viewerTeam->statusLabel() }}</p>
                    </div>
                    <strong>{{ $viewerTeamMembers->count() }}/{{ $teamSize }}</strong>
                </div>

                <div class="cup-team-flow__members">
                    @foreach($viewerTeamMembers as $member)
                        @php($memberUser = $member->user)
                        <div class="cup-team-flow__member">
                            <img src="{{ $memberUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt="">
                            <span>
                                <strong>{{ $memberUser?->username ?: $memberUser?->name ?: ($isEnglish ? 'Hunter' : 'Jäger') }}</strong>
                                <small>{{ $member->roleLabel() }}</small>
                            </span>
                        </div>
                    @endforeach
                </div>

                @if($viewerCanManage && $inviteUrl && $viewerTeamMembers->count() < $teamSize && ! $viewerTeam->isRosterLocked())
                    <div class="cup-team-flow__invite">
                        <strong>{{ $isEnglish ? 'Invitation link' : 'Einladungslink' }}</strong>
                        <p class="cup-team-flow__muted">{{ $isEnglish ? 'Share this link with the missing team members.' : 'Teile diesen Link mit den fehlenden Teammitgliedern.' }}</p>
                        <input type="text" readonly value="{{ $inviteUrl }}" onfocus="this.select()">
                    </div>
                @endif

                <div class="cup-team-flow__submit-summary">
                    <span class="cup-team-flow__eyebrow">{{ $isEnglish ? 'SUBMISSIONS' : 'EINREICHUNGEN' }}</span>
                    <strong>{{ $viewerUploadCount }}{{ $viewerUploadLimit !== null ? ' / '.$viewerUploadLimit : '' }}</strong>
                    <p class="cup-team-flow__muted">
                        @if($viewerCanSubmit)
                            {{ $isEnglish ? 'Your team can submit another screenshot.' : 'Dein Team kann einen weiteren Screenshot einreichen.' }}
                        @elseif(! $submissionOpen)
                            {{ $cup->submissionClosedReason() }}
                        @elseif($viewerUploadLimitReached)
                            {{ $isEnglish ? 'The upload limit has been reached.' : 'Das Upload-Limit wurde erreicht.' }}
                        @elseif(! $viewerIsCaptain)
                            {{ $isEnglish ? 'Only the captain can submit screenshots.' : 'Nur der Captain kann Screenshots einreichen.' }}
                        @elseif(! $viewerTeamComplete)
                            {{ $isEnglish ? 'Complete the team before submitting.' : 'Vervollständige zuerst das Team.' }}
                        @endif
                    </p>
                </div>

                <div class="cup-team-flow__actions">
                    @if($viewerCanSubmit)
                        <button class="cup-team-flow__primary" type="button" data-open-cup-submission-modal>{{ $isEnglish ? 'Submit screenshot' : 'Screenshot einreichen' }}</button>
                    @else
                        <a class="cup-team-flow__primary" href="{{ route('cups.show.section', [$cup, 'submit']) }}">{{ $isEnglish ? 'Open submission status' : 'Einreichungsstatus öffnen' }}</a>
                    @endif
                    <a class="cup-team-flow__secondary" href="{{ route('cups.show.section', [$cup, 'participants']) }}">Leaderboard</a>
                </div>
            </article>

            <article class="cup-team-flow__panel">
                <span class="cup-team-flow__eyebrow">{{ $isEnglish ? 'SETTINGS' : 'EINSTELLUNGEN' }}</span>
                <h2>{{ $isEnglish ? 'Team settings' : 'Team-Einstellungen' }}</h2>

                @if($viewerCanManage)
                    <form class="cup-team-flow__inline-form" method="post" action="{{ route('cups.teams.update', [$cup, $viewerTeam]) }}">
                        @csrf
                        @method('patch')
                        <input type="text" name="name" maxlength="100" required value="{{ old('name', $viewerTeam->displayName()) }}" aria-label="{{ $isEnglish ? 'Team name' : 'Teamname' }}">
                        <button class="cup-team-flow__primary" type="submit">{{ $isEnglish ? 'Save' : 'Speichern' }}</button>
                    </form>

                    @if($teamFinderEnabled)
                        <form class="cup-team-flow__actions" method="post" action="{{ route('cups.teams.recruiting', [$cup, $viewerTeam]) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="is_recruiting" value="{{ $viewerTeam->isRecruiting() ? 0 : 1 }}">
                            <button class="cup-team-flow__secondary" type="submit" @disabled($viewerTeam->isRosterLocked() || $viewerTeam->slotsOpen() <= 0 || ! $registrationOpen)>
                                {{ $viewerTeam->isRecruiting() ? ($isEnglish ? 'Stop recruiting' : 'Spielersuche stoppen') : ($isEnglish ? 'Find players' : 'Spieler suchen') }}
                            </button>
                        </form>
                    @endif
                @endif

                @if(! $viewerTeam->isRosterLocked())
                    <form class="cup-team-flow__actions" method="post" action="{{ route('cups.teams.leave', [$cup, $viewerTeam]) }}">
                        @csrf
                        <button class="cup-team-flow__secondary" type="submit">{{ $isEnglish ? 'Leave team' : 'Team verlassen' }}</button>
                    </form>
                @endif
            </article>
        </section>
    @else
        <section class="cup-team-flow__grid">
            <article class="cup-team-flow__panel">
                <div class="cup-team-flow__panel-head">
                    <div>
                        <span class="cup-team-flow__eyebrow">{{ $isEnglish ? 'OPEN TEAMS' : 'OFFENE TEAMS' }}</span>
                        <h2>{{ $isEnglish ? 'Join an existing team' : 'Bestehendem Team beitreten' }}</h2>
                        <p class="cup-team-flow__muted">{{ $isEnglish ? 'Only teams currently looking for players are shown.' : 'Es werden nur Teams angezeigt, die aktuell Spieler suchen.' }}</p>
                    </div>
                    <a class="cup-team-flow__primary" href="{{ route('cups.show', $cup) }}?team=create">{{ $isEnglish ? 'Create team' : 'Team erstellen' }}</a>
                </div>

                <div class="cup-team-flow__list">
                    @forelse($recruitingTeams as $recruitingTeam)
                        @php
                            $memberCount = $recruitingTeam->members->where('status', 'active')->count();
                            $owner = $recruitingTeam->owner;
                        @endphp
                        <div class="cup-team-flow__card">
                            <div>
                                <strong>{{ $recruitingTeam->displayName() }}</strong>
                                <p>{{ $memberCount }}/{{ $recruitingTeam->requiredMembersCount() }} · Captain: {{ $owner?->username ?: $owner?->name ?: 'Hunter' }}</p>
                            </div>
                            @if($viewer && $registrationOpen && ($viewerEligibility['eligible'] ?? false))
                                <a class="cup-team-flow__primary" href="{{ route('cups.teams.join', [$cup, $recruitingTeam->join_token]) }}">{{ $isEnglish ? 'Join' : 'Beitreten' }}</a>
                            @endif
                        </div>
                    @empty
                        <div class="cup-team-flow__empty">{{ $isEnglish ? 'No team is recruiting right now.' : 'Aktuell sucht noch kein Team nach Spielern.' }}</div>
                    @endforelse
                </div>
            </article>

            <article class="cup-team-flow__panel">
                <span class="cup-team-flow__eyebrow">{{ $isEnglish ? 'PLAYER FINDER' : 'SPIELERSUCHE' }}</span>
                <h2>{{ $isEnglish ? 'Let teams find you' : 'Lass dich von Teams finden' }}</h2>
                <p class="cup-team-flow__muted">{{ $isEnglish ? 'Publish a short request for this cup.' : 'Veröffentliche eine kurze Suche für diesen Cup.' }}</p>

                @if($viewerFinderPost)
                    <div class="cup-team-flow__notice">
                        <strong>{{ $isEnglish ? 'Your request is active' : 'Deine Suche ist aktiv' }}</strong>
                        @if($viewerFinderPost->message)<p>{{ $viewerFinderPost->message }}</p>@endif
                    </div>
                    <form class="cup-team-flow__actions" method="post" action="{{ route('cups.team-finder.close', $cup) }}">
                        @csrf
                        @method('delete')
                        <button class="cup-team-flow__secondary" type="submit">{{ $isEnglish ? 'Close request' : 'Suche beenden' }}</button>
                    </form>
                @elseif($viewer && $teamFinderEnabled && $registrationOpen && ($viewerEligibility['eligible'] ?? false))
                    <form class="cup-team-flow__form" method="post" action="{{ route('cups.team-finder.store', $cup) }}">
                        @csrf
                        <div class="cup-team-flow__form-field">
                            <label for="team-finder-platform">{{ $isEnglish ? 'Platform' : 'Plattform' }}</label>
                            <select id="team-finder-platform" name="platform">
                                <option value="">{{ $isEnglish ? 'Not specified' : 'Keine Angabe' }}</option>
                                <option value="playstation">PlayStation</option>
                                <option value="xbox">Xbox</option>
                                <option value="flexible">{{ $isEnglish ? 'Flexible' : 'Flexibel' }}</option>
                            </select>
                        </div>
                        <div class="cup-team-flow__form-field">
                            <label for="team-finder-message">{{ $isEnglish ? 'Short message' : 'Kurze Nachricht' }}</label>
                            <textarea id="team-finder-message" name="message" maxlength="500" placeholder="{{ $isEnglish ? 'What should a team know about you?' : 'Was sollte ein Team über dich wissen?' }}">{{ old('message') }}</textarea>
                        </div>
                        <button class="cup-team-flow__primary" type="submit">{{ $isEnglish ? 'Publish request' : 'Suche veröffentlichen' }}</button>
                    </form>
                @else
                    <div class="cup-team-flow__empty">
                        @foreach(($viewerEligibility['messages'] ?? []) as $message)<div>{{ $message }}</div>@endforeach
                    </div>
                @endif

                @if($teamFinderPosts->isNotEmpty())
                    <div class="cup-team-flow__list" style="margin-top:18px">
                        @foreach($teamFinderPosts->take(8) as $finderPost)
                            @php($finderUser = $finderPost->user)
                            <div class="cup-team-flow__card">
                                <div>
                                    <strong>{{ $finderUser?->username ?: $finderUser?->name ?: 'Hunter' }}</strong>
                                    <p>{{ $finderPost->message ?: ($isEnglish ? 'Looking for a team.' : 'Sucht ein Team.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>
    @endif
</div>

@if($viewerCanSubmit)
<div class="cup-team-modal" id="cupTeamSubmissionModal" hidden>
    <button class="cup-team-modal__backdrop" type="button" aria-label="{{ $isEnglish ? 'Close modal' : 'Modal schließen' }}" data-close-cup-submission-modal></button>
    <section class="cup-team-modal__panel cup-team-submit-modal__panel" role="dialog" aria-modal="true" aria-labelledby="cupTeamSubmissionTitle">
        <header class="cup-team-modal__head">
            <div>
                <span class="cup-team-modal__eyebrow">{{ $isEnglish ? 'CUP SUBMISSION' : 'CUP-EINREICHUNG' }}</span>
                <h2 id="cupTeamSubmissionTitle">{{ $isEnglish ? 'Submit screenshot' : 'Screenshot einreichen' }}</h2>
            </div>
            <button class="cup-team-modal__close" type="button" aria-label="{{ $isEnglish ? 'Close' : 'Schließen' }}" data-close-cup-submission-modal>×</button>
        </header>
        <p class="cup-team-modal__intro">{{ $isEnglish ? 'Upload the complete, unedited match screenshot. It will be checked immediately.' : 'Lade den vollständigen, unbearbeiteten Match-Screenshot hoch. Er wird direkt geprüft.' }}</p>

        <form class="cup-team-modal__form" id="cupTeamSubmissionForm" method="post" action="{{ route('cups.submissions.store', [$cup, $viewerTeam]) }}" enctype="multipart/form-data">
            @csrf
            <label class="cup-team-submit-upload" for="cupTeamSubmissionFile">
                <input id="cupTeamSubmissionFile" type="file" name="screenshot" accept="image/jpeg,image/png,image/webp" required>
                <span class="cup-team-submit-upload__icon">▧</span>
                <strong data-cup-submission-file-label>{{ $isEnglish ? 'Select screenshot' : 'Screenshot auswählen' }}</strong>
                <small>JPG, PNG, WebP · max. 10 MB</small>
            </label>
            <label for="cupTeamSubmissionNote">{{ $isEnglish ? 'Note (optional)' : 'Notiz (optional)' }}</label>
            <textarea id="cupTeamSubmissionNote" name="note" maxlength="1200" rows="3" placeholder="{{ $isEnglish ? 'Optional note for the review' : 'Optionale Notiz für die Prüfung' }}"></textarea>
            <p class="cup-team-submit-modal__status" data-cup-submission-status hidden></p>
            <button class="cup-team-modal__submit" type="submit" data-cup-submission-submit>{{ $isEnglish ? 'Upload and check' : 'Hochladen und prüfen' }}</button>
        </form>
    </section>
</div>
@endif
@endsection

@push('scripts')
@if($viewerCanSubmit)
<script>
(() => {
    const modal = document.getElementById('cupTeamSubmissionModal');
    const form = document.getElementById('cupTeamSubmissionForm');
    if (!modal || !form) return;

    const fileInput = form.querySelector('input[name="screenshot"]');
    const fileLabel = form.querySelector('[data-cup-submission-file-label]');
    const status = form.querySelector('[data-cup-submission-status]');
    const submitButton = form.querySelector('[data-cup-submission-submit]');
    const openButtons = document.querySelectorAll('[data-open-cup-submission-modal]');
    const closeButtons = modal.querySelectorAll('[data-close-cup-submission-modal]');

    const openModal = () => {
        modal.hidden = false;
        document.body.classList.add('cup-team-modal-open');
        window.setTimeout(() => fileInput?.focus(), 30);
    };

    const closeModal = () => {
        if (submitButton?.disabled) return;
        modal.hidden = true;
        document.body.classList.remove('cup-team-modal-open');
    };

    openButtons.forEach((button) => button.addEventListener('click', openModal));
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });

    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (file && fileLabel) fileLabel.textContent = file.name;
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!fileInput?.files?.[0]) return;

        submitButton.disabled = true;
        submitButton.textContent = @json($isEnglish ? 'Checking screenshot…' : 'Screenshot wird geprüft…');
        status.hidden = false;
        status.classList.remove('is-error', 'is-success');
        status.textContent = @json($isEnglish ? 'Upload and analysis are running. Please keep this window open.' : 'Upload und Prüfung laufen. Bitte dieses Fenster geöffnet lassen.');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.ok === false) {
                throw new Error(payload.message || @json($isEnglish ? 'The submission failed.' : 'Die Einreichung ist fehlgeschlagen.'));
            }

            status.classList.add('is-success');
            status.textContent = payload.message || @json($isEnglish ? 'Submission saved.' : 'Einreichung gespeichert.');
            submitButton.textContent = @json($isEnglish ? 'Done' : 'Fertig');
            window.setTimeout(() => window.location.reload(), 1100);
        } catch (error) {
            status.classList.add('is-error');
            status.textContent = error.message || @json($isEnglish ? 'The submission failed.' : 'Die Einreichung ist fehlgeschlagen.');
            submitButton.disabled = false;
            submitButton.textContent = @json($isEnglish ? 'Upload and check' : 'Hochladen und prüfen');
        }
    });
})();
</script>
@endif
@endpush
