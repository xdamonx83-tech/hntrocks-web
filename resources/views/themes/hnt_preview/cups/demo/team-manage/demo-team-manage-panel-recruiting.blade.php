@php
    $teamRecruitingActive = $team->isRecruiting();
    $teamSlotsOpen = max(0, $teamRequiredMembers - $teamMemberCount);
    $teamCanControlRecruiting = $teamFinderEnabled && ($isCaptain || $canManageTeam);
    $teamCanEnableRecruiting = $teamCanControlRecruiting
        && $team->canChangeRoster()
        && $teamSlotsOpen > 0
        && $cup->isRegistrationOpen();
    $teamRecruitingAvailable = $teamCanEnableRecruiting || $teamRecruitingActive;

    $teamRecruitingStateLabel = $teamRecruitingActive
        ? $t('aktiv', 'active')
        : ($teamRecruitingAvailable ? $t('pausiert', 'paused') : $t('nicht verfügbar', 'unavailable'));

    $teamRecruitingTitle = $teamRecruitingActive
        ? $t('Recruiting aktiv', 'Recruiting active')
        : $t('Recruiting pausiert', 'Recruiting paused');

    $teamRecruitingDescription = match (true) {
        ! $teamFinderEnabled => $t('Der Teamfinder ist für diesen Cup derzeit nicht verfügbar.', 'The team finder is currently unavailable for this Cup.'),
        $team->isRosterLocked() => $t('Das Roster ist gesperrt. Recruiting kann nicht mehr geändert werden.', 'The roster is locked. Recruiting can no longer be changed.'),
        $teamSlotsOpen <= 0 => $t('Dein Team ist vollständig. Sobald ein Platz frei wird, kann Recruiting aktiviert werden.', 'Your team is complete. Recruiting can be enabled once a slot becomes available.'),
        ! $cup->isRegistrationOpen() => $t('Die Anmeldung ist geschlossen. Recruiting kann nicht mehr aktiviert werden.', 'Registration is closed. Recruiting can no longer be enabled.'),
        $teamRecruitingActive => $t('Dein Team ist im Teamfinder sichtbar und sucht aktuell nach weiteren Huntern.', 'Your team is visible in the team finder and is currently looking for more Hunters.'),
        default => $t('Du kannst dein Team bis zum Team-Lock für passende Hunter sichtbar schalten.', 'You can make your team visible to matching Hunters until team lock.'),
    };
@endphp
<section class="team-panel" data-team-panel="recruiting" hidden="">
<div class="team-section-intro">
<div>
<span>TEAMFINDER</span>
<h3>Recruiting</h3>
<p>{{ $t('Teams mit freien Plätzen können sich für passende Hunter sichtbar schalten.', 'Teams with open slots can make themselves visible to matching Hunters.') }}</p>
</div>
<span class="team-roster-state {{ $teamRecruitingActive ? '' : 'full' }}"><i></i>{{ $teamRecruitingStateLabel }}</span>
</div>
<article class="team-panel-card team-recruiting-toggle">
<div>
<span>{{ $t('RECRUITING-STATUS', 'RECRUITING STATUS') }}</span>
<h3>{{ $teamRecruitingTitle }}</h3>
<p>{{ $teamRecruitingDescription }}</p>
</div>
@if($teamCanControlRecruiting)
<form method="post" action="{{ route('cups.teams.recruiting', [$cup, $team]) }}">
@csrf
@method('patch')
<input type="hidden" name="is_recruiting" value="{{ $teamRecruitingActive ? 0 : 1 }}"/>
<button type="submit" {{ (! $teamCanEnableRecruiting && ! $teamRecruitingActive) ? 'disabled' : '' }}>
{{ $teamRecruitingActive ? $t('Pausieren', 'Pause') : $t('Aktivieren', 'Enable') }}
</button>
</form>
@else
<button disabled type="button">{{ $t('Nicht verfügbar', 'Unavailable') }}</button>
@endif
</article>
<div class="team-overview-grid lower">
<article class="team-panel-card team-finder-players">
<header>
<div><span>{{ $t('SUCHEN EIN TEAM', 'LOOKING FOR A TEAM') }}</span><h3>{{ $t('Verfügbare Hunter', 'Available Hunters') }}</h3></div>
<small>{{ $finderPosts->count() }} {{ $t('aktiv', 'active') }}</small>
</header>
@forelse($finderPosts as $finder)
@php
    $finderUser = $finder->user;
    $finderName = $finderUser?->username ?: $finderUser?->name ?: 'Hunter';
    $finderPlatform = $finder->platform ?: $finderUser?->profile?->platform ?: $t('Flexibel', 'Flexible');
    $finderMessage = \Illuminate\Support\Str::limit(
        $finder->message ?: $t('Sucht ein Cup-Team', 'Looking for a Cup team'),
        58
    );
@endphp
<article>
<img alt="{{ $finderName }}" src="{{ $finderUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<div><strong>{{ $finderName }}</strong><span>{{ $finderPlatform }} · {{ $finderMessage }}</span></div>
<button aria-label="{{ $t('Profil öffnen', 'Open profile') }}" onclick="window.location.href='{{ $finderUser ? $teamProfileUrl($finderUser) : route('members.index') }}'" type="button"><svg><use href="#i-arrow"></use></svg></button>
</article>
@empty
<article>
<img alt="" src="{{ asset('assets/vikinger/img/default-avatar.svg') }}"/>
<div><strong>{{ $t('Aktuell keine Hunter verfügbar', 'No Hunters available right now') }}</strong><span>{{ $t('Sobald jemand öffentlich ein Cup-Team sucht, erscheint das Profil hier.', 'Profiles will appear here once someone publicly looks for a Cup team.') }}</span></div>
<button disabled type="button"><svg><use href="#i-arrow"></use></svg></button>
</article>
@endforelse
</article>
<article class="team-panel-card team-recruiting-info">
<span>{{ $t('SO FUNKTIONIERT ES', 'HOW IT WORKS') }}</span>
<h3>{{ $t('Teamfinder für Captains', 'Team finder for captains') }}</h3>
<p>{{ $t('Bei einem freien Platz kannst du dein Team als „sucht Hunter“ markieren. Interessierte Spieler sehen Teamname, Captain und offene Plätze.', 'When a slot is open, mark the team as looking for Hunters. Interested players can see the team, captain and open slots.') }}</p>
<ul>
<li>{{ $t('nur während offener Anmeldung', 'only during open registration') }}</li>
<li>{{ $t('nicht nach Team-Lock', 'not after team lock') }}</li>
<li>{{ $t('automatisch aus bei vollem Team', 'disabled automatically when the team is full') }}</li>
</ul>
</article>
</div>
</section>