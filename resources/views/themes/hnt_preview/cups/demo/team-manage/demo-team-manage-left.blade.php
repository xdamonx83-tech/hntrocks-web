<aside aria-label="{{ $t('Teamübersicht', 'Team overview') }}" class="team-fixed-column team-fixed-left" id="teamFixedLeft">
<article class="team-identity-card">
<div class="team-cover-mini">
<img alt="{{ $cup->title }}" src="{{ $teamCoverUrl }}"/>
<a aria-label="{{ $t('Zur Cup-Detailseite', 'Open cup details') }}" href="{{ route('cups.show', $cup) }}"><svg><use href="#i-arrow"></use></svg></a>
</div>
<div class="team-identity-copy">
<span>{{ $t('DEIN TEAM', 'YOUR TEAM') }}</span>
<h2>{{ $team->displayName() }}</h2>
<p>{{ $cup->title }} · {{ $cup->modeLabel() }} · {{ $teamPlatformLabel }}</p>
<div class="team-avatar-stack">
@foreach($teamMembers->take(3) as $member)
<img alt="{{ $member->user?->username ?: $member->user?->name ?: 'Hunter' }}" src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
@endforeach
<strong>{{ $teamMemberCount }}/{{ $teamRequiredMembers }}</strong>
</div>
<div class="team-completion">
<div><span>{{ $t('Teamstatus', 'Team status') }}</span><strong>{{ $teamPercent }}%</strong></div>
<i><b style="width:{{ $teamPercent }}%"></b></i>
<small>{{ $teamCompletionLabel }}</small>
</div>
<div class="team-identity-pills">
<span>{{ $team->statusLabel() }}</span><span>{{ $teamRoleLabel }}</span><span>{{ $teamRosterLabel }}</span>
</div>
</div>
</article>
<article class="team-quick-card">
<header><span>{{ $t('SCHNELLZUGRIFF', 'QUICK ACCESS') }}</span><h3>{{ $t('Teamaktionen', 'Team actions') }}</h3></header>
<button data-team-tab-shortcut="invites" type="button"><svg><use href="#i-share"></use></svg><span><strong>{{ $t('Einladung teilen', 'Share invite') }}</strong><small>{{ $teamMemberCount }}/{{ $teamRequiredMembers }} {{ $t('Mitglieder', 'members') }}</small></span><i>→</i></button>
<button data-team-tab-shortcut="submissions" type="button"><svg><use href="#i-image"></use></svg><span><strong>{{ $t('Einreichungen', 'Submissions') }}</strong><small>{{ $teamSubmissionCount }}{{ $teamSubmissionLimit ? ' / '.$teamSubmissionLimit : '' }} {{ $t('genutzt', 'used') }}</small></span><i>→</i></button>
<button data-team-tab-shortcut="members" type="button"><svg><use href="#i-user"></use></svg><span><strong>{{ $t('Mitglieder', 'Members') }}</strong><small>{{ $teamMemberCount }} {{ $t('aktive Hunter', 'active Hunters') }}</small></span><i>→</i></button>
</article>
</aside>
