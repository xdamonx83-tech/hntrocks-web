<aside aria-label="{{ $isEnglish ? 'Your cup participation' : 'Deine Cup-Teilnahme' }}" class="cup-fixed-column cup-fixed-right" id="cupFixedRight"><aside class="cup-participation-card">
<section class="cup-participation-top">
<header>
<div><span>{{ $isEnglish ? 'PARTICIPATION' : 'TEILNAHME' }}</span><h2>{{ $isEnglish ? 'Your cup' : 'Dein Cup' }}</h2></div>
<strong>{{ $viewerParticipationPercent }}%</strong>
</header>
<div class="cup-participation-segments">
<span><b>{{ $viewerTeamPercent }}%</b><i class="yellow"></i><small>{{ $soloCup ? ($isEnglish ? 'Player' : 'Spieler') : 'Team' }}</small></span>
<span><b>{{ $viewerTeamConfirmedPercent }}%</b><i class="dark"></i><small>{{ $isEnglish ? 'Confirmed' : 'Bestätigt' }}</small></span>
<span><b>{{ $viewerScoresPercent }}%</b><i class="grey"></i><small>Scores</small></span>
</div>
</section>
<section class="cup-my-team">
@if ($viewerTeam)
<header>
<div><span>{{ $soloCup ? ($isEnglish ? 'YOUR ENTRY' : 'DEINE TEILNAHME') : ($isEnglish ? 'YOUR TEAM' : 'DEIN TEAM') }}</span><h2>{{ $viewerTeam->displayName() }}</h2></div>
<strong>{{ $viewerTeamMemberCount }}/{{ $viewerTeamRequiredMembers }}</strong>
</header>
<div class="cup-team-members">
@foreach ($viewerTeamMembers->take(max(1, $viewerTeamRequiredMembers)) as $member)
@php($memberUser = $member->user)
<article>
<img alt="{{ $memberUser?->name ?: $memberUser?->username ?: 'Hunter' }}" src="{{ $memberUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<div><strong>{{ $memberUser?->name ?: $memberUser?->username ?: 'Hunter' }}</strong><span>{{ $member->roleLabel() }} · {{ $isEnglish ? 'ready' : 'bereit' }}</span></div>
<i class="done"><svg><use href="#i-check"></use></svg></i>
</article>
@endforeach
<article class="team-task">
<span class="team-task-icon"><svg><use href="#i-image"></use></svg></span>
<div><strong>{{ $isEnglish ? 'Submissions' : 'Einreichungen' }}</strong><span>{{ $viewerSubmissionLimitLabel }}{{ $viewerTeamPendingCount > 0 ? ' · '.$viewerTeamPendingCount.' '.($isEnglish ? 'pending' : 'in Prüfung') : '' }}</span></div>
<i @class(['done' => $viewerTeamApprovedCount > 0])>@if ($viewerTeamApprovedCount > 0)<svg><use href="#i-check"></use></svg>@endif</i>
</article>
@if (! $soloCup)
<article class="team-task">
<span class="team-task-icon"><svg><use href="#i-comment"></use></svg></span>
<div><strong>Teamchat</strong><span>{{ $viewerTeamChatMessagesCount }} {{ $isEnglish ? 'messages' : 'Nachrichten' }}</span></div>
<i @class(['notice' => $viewerTeamChatMessagesCount > 0])></i>
</article>
@endif
</div>
<a aria-label="{{ $isEnglish ? 'Manage team '.$viewerTeam->displayName() : 'Team '.$viewerTeam->displayName().' verwalten' }}" class="cup-team-button" href="{{ route('cups.teams.index', $cup) }}">{{ $soloCup ? ($isEnglish ? 'Open participation' : 'Teilnahme öffnen') : ($isEnglish ? 'Manage team' : 'Team verwalten') }} <svg><use href="#i-arrow"></use></svg></a>
@else
<header>
<div><span>{{ $isEnglish ? 'PARTICIPATION' : 'TEILNAHME' }}</span><h2>{{ $soloCup ? ($isEnglish ? 'Join now' : 'Jetzt teilnehmen') : ($isEnglish ? 'No team yet' : 'Noch kein Team') }}</h2></div>
<strong>0/{{ $viewerTeamRequiredMembers }}</strong>
</header>
<div class="cup-team-members">
<article class="team-task">
<span class="team-task-icon"><svg><use href="#i-users"></use></svg></span>
<div><strong>{{ $soloCup ? ($isEnglish ? 'Register for the cup' : 'Für den Cup anmelden') : ($isEnglish ? 'Create or find a team' : 'Team erstellen oder finden') }}</strong><span>{{ $registrationOpen ? ($isEnglish ? 'Registration is open' : 'Anmeldung ist geöffnet') : ($isEnglish ? 'Registration is currently closed' : 'Anmeldung ist aktuell geschlossen') }}</span></div>
<i @class(['notice' => $registrationOpen])></i>
</article>
<article class="team-task">
<span class="team-task-icon"><svg><use href="#i-check"></use></svg></span>
<div><strong>{{ $isEnglish ? 'Available places' : 'Freie Plätze' }}</strong><span>{{ $teamLimit ? max(0, $teamLimit - $teamCount).' / '.$teamLimit : ($isEnglish ? 'No fixed limit' : 'Kein festes Limit') }}</span></div>
<i></i>
</article>
</div>
@if($soloCup)
<a aria-label="{{ $isEnglish ? 'Open cup participation' : 'Cup-Teilnahme öffnen' }}" class="cup-team-button" href="{{ route('cups.teams.index', $cup) }}">{{ $isEnglish ? 'Join cup' : 'Cup beitreten' }} <svg><use href="#i-arrow"></use></svg></a>
@else
<button aria-label="{{ $isEnglish ? 'Create or find a team' : 'Team erstellen oder finden' }}" class="cup-team-button" type="button" data-open-cup-team-modal>{{ $isEnglish ? 'Create or find team' : 'Team erstellen oder finden' }} <svg><use href="#i-arrow"></use></svg></button>
@endif
@endif
</section>
</aside></aside>