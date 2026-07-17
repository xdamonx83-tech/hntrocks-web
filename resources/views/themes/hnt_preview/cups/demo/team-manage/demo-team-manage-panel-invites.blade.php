<section class="team-panel" data-team-panel="invites" hidden="">
<div class="team-section-intro">
<div>
<span>{{ $t('EINLADUNGSLINK', 'INVITE LINK') }}</span>
<h3>{{ $t('Hunter einladen', 'Invite Hunters') }}</h3>
<p>
{{ $team->isRosterLocked()
    ? $t('Das Roster ist gesperrt. Der Einladungslink kann nicht mehr verwendet werden.', 'The roster is locked. The invite link can no longer be used.')
    : ($teamComplete
        ? $t('Dein Team ist vollständig. Der persönliche Link bleibt für dein Team gespeichert.', 'Your team is complete. The personal link remains saved for your team.')
        : $t('Teile den persönlichen Link, um die noch freien Plätze zu besetzen.', 'Share the personal link to fill the remaining roster slots.')) }}
</p>
</div>
<span class="team-roster-state {{ $teamComplete ? 'full' : '' }}"><i></i>{{ $teamMemberCount }} / {{ $teamRequiredMembers }} {{ $teamComplete ? $t('voll', 'full') : $t('belegt', 'filled') }}</span>
</div>
<article class="team-panel-card team-invite-card">
<div class="team-invite-main">
<span>{{ $t('DEIN PERSÖNLICHER LINK', 'YOUR PERSONAL LINK') }}</span>
<h3>{{ $cup->title }} · {{ $team->displayName() }}</h3>
<p>{{ $t('Dieser Einladungslink gehört zu deinem Team und kann direkt geteilt oder als QR-Code gescannt werden.', 'This invite link belongs to your team and can be shared directly or scanned as a QR code.') }}</p>
<div class="team-invite-link">
<input id="teamInviteLink" readonly="" value="{{ $teamInviteUrl }}"/>
<button data-copy-invite="" data-copy-success="{{ $t('Einladungslink kopiert', 'Invite link copied') }}" type="button">{{ $t('Link kopieren', 'Copy link') }}</button>
</div>
</div>
<div aria-label="{{ $t('QR-Code für den Team-Einladungslink', 'QR code for the team invite link') }}" class="team-invite-code team-invite-code-real" data-qr-value="{{ $teamInviteUrl }}" id="teamInviteQr"></div>
</article>
<div class="team-overview-grid lower">
<article class="team-panel-card team-invite-history">
<header><div><span>{{ $t('TEAMVERLAUF', 'TEAM HISTORY') }}</span><h3>{{ $t('Beitritte', 'Joins') }}</h3></div><small>{{ $teamInviteHistory->count() }} {{ $t('Einträge', 'entries') }}</small></header>
<ul>
@forelse($teamInviteHistory as $inviteMember)
@php
    $inviteUser = $inviteMember->user;
    $inviteMemberName = $inviteUser?->username ?: $inviteUser?->name ?: 'Hunter';
    $inviteJoinedAt = $inviteMember->joined_at ?: $inviteMember->created_at;
    $inviteIsCaptain = $inviteMember->role === 'captain';
@endphp
<li>
<span class="accepted"><svg><use href="#i-check"></use></svg></span>
<div>
<strong>{{ $inviteMemberName }} {{ $inviteIsCaptain ? $t('hat das Team erstellt', 'created the team') : $t('ist beigetreten', 'joined the team') }}</strong>
<small>{{ $inviteIsCaptain ? 'Captain' : $t('Einladung angenommen', 'Invite accepted') }}</small>
</div>
<time>{{ $inviteJoinedAt?->diffForHumans() ?: '—' }}</time>
</li>
@empty
<li>
<span><svg><use href="#i-share"></use></svg></span>
<div><strong>{{ $t('Noch keine Beitritte', 'No joins yet') }}</strong><small>{{ $t('Teile den Einladungslink mit einem Hunter.', 'Share the invite link with a Hunter.') }}</small></div>
</li>
@endforelse
</ul>
</article>
<article class="team-panel-card team-lock-card">
<span>TEAM-LOCK</span>
<strong>{{ $teamLockDateLabel }}</strong>
<p>{{ $team->isRosterLocked()
    ? $t('Das Roster ist gesperrt. Mitglieder und Einladungen können nicht mehr verändert werden.', 'The roster is locked. Members and invites can no longer be changed.')
    : $t('Nach dem Team-Lock verlieren offene Einladungen ihre Gültigkeit.', 'Open invitations stop working after the team lock.') }}</p>
<div><i style="width:{{ $teamLockPercent }}%"></i></div>
<small>{{ $teamLockRemainingLabel }}</small>
</article>
</div>
</section>