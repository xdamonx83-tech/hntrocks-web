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
    $viewerEligibility = $viewer
        ? $cup->participationEligibility($viewer)
        : ['eligible' => false, 'messages' => [$isEnglish ? 'Please sign in first.' : 'Bitte melde dich zuerst an.']];
    $viewerTeamMembers = $viewerTeam
        ? $viewerTeam->members->where('status', 'active')->values()
        : collect();
    $teamSize = max(1, (int) ($cup->team_size ?: 1));
    $viewerIsCaptain = $viewerTeam ? $viewerTeam->isCaptain($viewer) : false;
    $viewerCanManage = $viewerTeam && ($viewerIsCaptain || $canManage);
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

                <div class="cup-team-flow__actions">
                    <a class="cup-team-flow__primary" href="{{ route('cups.show.section', [$cup, 'submit']) }}">{{ $isEnglish ? 'Open submissions' : 'Einreichungen öffnen' }}</a>
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
                                <p>{{ $memberCount }}/{{ $recruitingTeam->requiredMembersCount() }} · {{ $isEnglish ? 'Captain' : 'Captain' }}: {{ $owner?->username ?: $owner?->name ?: 'Hunter' }}</p>
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
@endsection
