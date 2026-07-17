<section class="team-panel active" data-team-panel="overview">
<div class="team-overview-grid">
<article class="team-panel-card team-name-card">
<header>
<div>
<span>TEAMNAME</span>
<h3>{{ $team->displayName() }}</h3>
</div>
@if($teamCanRename)
<button aria-label="{{ $t('Teamname bearbeiten', 'Edit team name') }}" data-open-team-name="" type="button">
<svg><use href="#i-edit"></use></svg>
</button>
@endif
</header>
<p>{{ $teamCanRename
    ? $t('Du kannst den Teamnamen ändern, solange das Roster nicht gesperrt ist.', 'You can rename the team while the roster remains unlocked.')
    : $t('Nur der Captain kann den Teamnamen ändern.', 'Only the captain can rename the team.') }}</p>
<form class="team-name-form" method="post" action="{{ route('cups.teams.update', [$cup, $team]) }}">
@csrf
@method('patch')
<label for="teamNameInput">{{ $t('Teamname bearbeiten', 'Edit team name') }}</label>
<div>
<input id="teamNameInput" name="name" maxlength="100" value="{{ old('name', $team->displayName()) }}" {{ $teamCanRename ? '' : 'disabled' }}/>
<button type="submit" {{ $teamCanRename ? '' : 'disabled' }}>{{ $t('Speichern', 'Save') }}</button>
</div>
</form>
<div class="team-status-pills">
<span class="ready">{{ $team->statusLabel() }}</span>
<span>{{ $teamRosterLabel }}</span>
<span>Captain: {{ $teamCaptainName }}</span>
</div>
</article>
<article class="team-panel-card team-readiness-card">
<header>
<div>
<span>{{ $t('TEILNAHMESTATUS', 'PARTICIPATION') }}</span>
<h3>{{ $teamComplete ? $t('Bereit für den Cup', 'Ready for the Cup') : $t('Team noch unvollständig', 'Team not complete') }}</h3>
</div>
<strong>{{ $teamPercent }}%</strong>
</header>
<div class="team-readiness-steps">
<span class="{{ $teamMemberCount > 0 ? 'done' : 'current' }}"><i>@if($teamMemberCount > 0)<svg><use href="#i-check"></use></svg>@else 1 @endif</i><b>Team</b><small>{{ $teamMemberCount }} / {{ $teamRequiredMembers }} {{ $t('vollständig', 'complete') }}</small></span>
<span class="{{ $teamConfirmedPercent === 100 ? 'done' : 'current' }}"><i>@if($teamConfirmedPercent === 100)<svg><use href="#i-check"></use></svg>@else 2 @endif</i><b>{{ $t('Bestätigung', 'Confirmation') }}</b><small>{{ $teamConfirmedPercent === 100 ? $t('aktiv bestätigt', 'active and confirmed') : $t('noch offen', 'still pending') }}</small></span>
<span class="{{ $teamApprovedCount > 0 ? 'done' : 'current' }}"><i>@if($teamApprovedCount > 0)<svg><use href="#i-check"></use></svg>@else 3 @endif</i><b>Scores</b><small>{{ $teamApprovedCount }} {{ $t('gewertet', 'scored') }}</small></span>
</div>
</article>
</div>
<article class="team-panel-card team-members-overview">
<header>
<div>
<span>{{ $t('DEIN TEAM', 'YOUR TEAM') }}</span>
<h3>{{ $t('Mitglieder', 'Members') }}</h3>
</div>
<button data-team-tab-shortcut="members" type="button">{{ $t('Alle ansehen', 'View all') }}</button>
</header>
<div class="team-member-table">
@forelse($teamMembers as $member)
@php
    $memberUser = $member->user;
    $memberName = $memberUser?->username ?: $memberUser?->name ?: 'Hunter';
    $memberRole = $member->role === 'captain' ? 'Captain' : $t('Mitglied', 'Member');
    $memberPlatform = $memberUser?->profile?->platform ?: $teamPlatformLabel;
@endphp
<article>
<img alt="{{ $memberName }}" src="{{ $memberUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<div><strong>{{ $memberName }}</strong><span>{{ $memberUser?->username ? '@'.$memberUser->username.' · ' : '' }}{{ $memberRole }}</span></div>
<span class="platform">{{ $memberPlatform }}</span>
<span class="member-state ready"><i></i>{{ $t('Aktiv', 'Active') }}</span>
<button aria-label="{{ $t('Profil öffnen', 'Open profile') }}" onclick="window.location.href='{{ $memberUser ? $teamProfileUrl($memberUser) : route('members.index') }}'" type="button"><svg><use href="#i-arrow"></use></svg></button>
</article>
@empty
<p>{{ $t('Noch keine aktiven Mitglieder.', 'No active members yet.') }}</p>
@endforelse
</div>
</article>
<div class="team-overview-grid lower">
<article class="team-panel-card team-submission-ready">
<header>
<div>
<span>{{ $t('EINREICHUNGEN', 'SUBMISSIONS') }}</span>
<h3>{{ $teamCanSubmit ? $t('Upload ist möglich', 'Upload available') : $t('Upload noch nicht möglich', 'Upload unavailable') }}</h3>
</div>
<strong>{{ $teamSubmissionCount }}{{ $teamSubmissionLimit ? ' / '.$teamSubmissionLimit : '' }}</strong>
</header>
<p>{{ $teamCanSubmit
    ? $t('Das Team ist vollständig und der Einreichungszeitraum ist geöffnet.', 'The team is complete and the submission period is open.')
    : ($cup->isSubmissionOpen() ? $t('Die Teilnahmebedingungen für einen Upload sind noch nicht erfüllt.', 'The submission requirements are not met yet.') : $cup->submissionClosedReason()) }}</p>
<div class="team-upload-progress"><i style="width:{{ $teamSubmissionPercent }}%"></i></div>
@if($teamCanSubmit)
<button data-team-tab-shortcut="submissions" type="button">{{ $t('Einreichungen öffnen', 'Open submissions') }}</button>
@endif
</article>
<article class="team-panel-card team-activity-card">
<header>
<div>
<span>{{ $t('LETZTE AKTIVITÄT', 'RECENT ACTIVITY') }}</span>
<h3>{{ $t('Im Team', 'In the team') }}</h3>
</div>
</header>
<ul>
@forelse($teamActivityItems as $item)
<li><i></i><span><strong>{{ $item['title'] }}</strong> {{ $item['text'] }}</span><time>{{ $item['time'] }}</time></li>
@empty
<li><i></i><span><strong>{{ $t('Noch keine Aktivität', 'No activity yet') }}</strong></span></li>
@endforelse
</ul>
</article>
</div>
<article class="team-panel-card team-danger-zone">
<div>
<span>{{ $isCaptain ? $t('TEAM ZURÜCKZIEHEN', 'WITHDRAW TEAM') : $t('TEAM VERLASSEN', 'LEAVE TEAM') }}</span>
<h3>{{ $t('Teilnahme beenden', 'End participation') }}</h3>
<p>{{ $isCaptain
    ? $t('Als Captain ziehst du das gesamte Team aus dem Cup zurück.', 'As captain, you will withdraw the entire team from the Cup.')
    : $t('Du verlässt dieses Cup-Team.', 'You will leave this Cup team.') }}</p>
</div>
<button data-confirm-team-leave="" type="button">{{ $isCaptain ? $t('Team zurückziehen', 'Withdraw team') : $t('Team verlassen', 'Leave team') }}</button>
</article>
</section>