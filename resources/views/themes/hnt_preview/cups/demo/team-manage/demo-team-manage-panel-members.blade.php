<section class="team-panel" data-team-panel="members" hidden="">
<div class="team-section-intro">
<div>
<span>{{ $teamMemberCount }} / {{ $teamRequiredMembers }} HUNTER</span>
<h3>{{ $t('Teammitglieder', 'Team members') }}</h3>
<p>{{ $teamComplete
    ? $t('Das Roster ist vollständig. Rollen und Profile sind hier zusammengefasst.', 'The roster is complete. Roles and profiles are summarized here.')
    : $t('Das Roster ist noch nicht vollständig. Rollen und Profile sind hier zusammengefasst.', 'The roster is not complete yet. Roles and profiles are summarized here.') }}</p>
</div>
<span class="team-roster-state"><i></i>{{ $teamRosterLabel }}</span>
</div>
<div class="team-member-cards">
@forelse($teamMembers as $member)
@php
    $memberUser = $member->user;
    $memberName = $memberUser?->username ?: $memberUser?->name ?: 'Hunter';
    $memberRole = $member->role === 'captain' ? 'Captain' : $t('Mitglied', 'Member');
    $memberHandle = $memberUser?->username ? '@'.$memberUser->username : $memberName;
    $memberProfile = $memberUser?->profile;
    $memberPlatform = trim((string) ($memberProfile?->platform ?: '')) ?: $teamPlatformLabel;
    $memberRegion = trim((string) ($memberProfile?->region ?: '')) ?: $t('Region offen', 'Region open');
    $memberPlaystyle = trim((string) ($memberProfile?->playstyle ?: '')) ?: $t('Spielstil offen', 'Playstyle open');
    $memberLanguage = trim((string) ($memberProfile?->language ?: '')) ?: $t('Sprache offen', 'Language open');
    $memberLevel = max(1, (int) ($memberUser?->level ?: 1));
    $memberPosts = max(0, (int) ($memberUser?->feed_posts_count ?? 0));
    $memberRocks = max(0, (int) ($memberUser?->crownWallet?->balance ?? 0));
    $memberRocksLabel = $isEnglish
        ? number_format($memberRocks, 0, '.', ',')
        : number_format($memberRocks, 0, ',', '.');
    $presenceVisible = $memberUser?->allowsOnlineStatusVisibility($viewer) ?? false;
    $memberPresence = ! $presenceVisible
        ? $t('Status verborgen', 'Status hidden')
        : ($memberUser?->isOnline()
            ? $t('online', 'online')
            : ($memberUser?->last_seen_at?->diffForHumans() ?: $t('zuletzt nicht bekannt', 'last seen unknown')));
@endphp
<article>
<div class="team-member-card-head">
<img alt="{{ $memberName }}" src="{{ $memberUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<div><span>{{ strtoupper($memberRole) }}</span><h3>{{ $memberName }}</h3><p>{{ $memberHandle }} · {{ $memberPresence }}</p></div>
<button aria-label="{{ $t('Profil öffnen', 'Open profile') }}" onclick="window.location.href='{{ $memberUser ? $teamProfileUrl($memberUser) : route('members.index') }}'" type="button"><svg><use href="#i-arrow"></use></svg></button>
</div>
<div class="team-member-stats">
<span><strong>{{ $memberLevel }}</strong><small>Level</small></span>
<span><strong>{{ $memberPosts }}</strong><small>Posts</small></span>
<span><strong>{{ $memberRocksLabel }}</strong><small>Rocks</small></span>
</div>
<div class="team-member-details"><span>{{ $memberPlatform }}</span><span>{{ $memberRegion }}</span><span>{{ $memberPlaystyle }}</span><span>{{ $memberLanguage }}</span></div>
</article>
@empty
<article>
<div class="team-member-card-head">
<div><span>{{ $t('TEAM', 'TEAM') }}</span><h3>{{ $t('Noch keine Mitglieder', 'No members yet') }}</h3><p>{{ $t('Das Roster ist aktuell leer.', 'The roster is currently empty.') }}</p></div>
</div>
</article>
@endforelse
</div>
<article class="team-panel-card team-roster-note">
<span><svg><use href="#i-check"></use></svg></span>
<div>
<strong>{{ $teamCompletionLabel }}</strong>
<p>{{ $teamComplete
    ? $t('Es gibt keine freien Plätze. Recruiting und neue Einladungen sind aktuell deaktiviert.', 'There are no open slots. Recruiting and new invitations are currently disabled.')
    : $t('Es sind noch '.max(0, $teamRequiredMembers - $teamMemberCount).' Plätze frei.', max(0, $teamRequiredMembers - $teamMemberCount).' slots are still open.') }}</p>
</div>
</article>
</section>
