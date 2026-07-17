<section class="team-manage-overview">
<div class="team-manage-heading">
<span>{{ $cup->title }} · {{ $t('DEIN TEAM', 'YOUR TEAM') }}</span>
<h1>{{ $t('Team verwalten', 'Manage team') }}</h1>
<div class="team-manage-meta">
<span class="live"><i></i>{{ $team->statusLabel() }}</span>
<span>{{ $team->displayName() }}</span>
<span>{{ $teamRoleLabel }}</span>
<span>{{ $teamPlatformLabel }}</span>
<span>{{ $teamMemberCount }} / {{ $teamRequiredMembers }} Hunter</span>
</div>
<p>
{{ $t(
    'Verwalte Teamname, Mitglieder, Einladungen, Teamchat und Einreichungen für den '.$cup->title.' Community Cup.',
    'Manage the team name, members, invitations, team chat and submissions for the '.$cup->title.' Community Cup.'
) }}
</p>
<div class="team-manage-bars">
<div class="team-manage-bar wide">
<span>{{ $t('Team vollständig', 'Team complete') }}</span>
<div class="dark"><b>{{ $teamMemberCount }} / {{ $teamRequiredMembers }}</b><i style="width:{{ $teamPercent }}%"></i></div>
</div>
<div class="team-manage-bar">
<span>{{ $t('Bestätigt', 'Confirmed') }}</span>
<div class="yellow"><b>{{ $teamConfirmedPercent }}%</b><i style="width:{{ $teamConfirmedPercent }}%"></i></div>
</div>
<div class="team-manage-bar">
<span>{{ $t('Einreichungen', 'Submissions') }}</span>
<div class="striped"><b>{{ $teamSubmissionCount }}{{ $teamSubmissionLimit ? ' / '.$teamSubmissionLimit : '' }}</b><i style="width:{{ $teamSubmissionPercent }}%"></i></div>
</div>
<div class="team-manage-bar compact">
<span>{{ $t('Teamchat', 'Team chat') }}</span>
<div class="outline"><b>{{ $chatMessagesCount }}</b></div>
</div>
</div>
</div>
<div class="team-manage-overview-stats">
<article><strong>{{ $teamMemberCount }}</strong><span>{{ $t('Mitglieder', 'Members') }}</span></article>
<article><strong>{{ $teamApprovedCount }}</strong><span>Scores</span></article>
<article><strong>{{ $chatMessagesCount }}</strong><span>{{ $t('Nachrichten', 'Messages') }}</span></article>
</div>
</section>
