@php
    use Illuminate\Support\Str;

    $isEnglish = app()->getLocale() === 'en';
    $t = static fn (string $de, string $en): string => $isEnglish ? $en : $de;
    $members = $team->members->where('status', 'active')->values();
    $memberCount = $members->count();
    $requiredMembers = max(1, $team->requiredMembersCount());
    $teamPercent = min(100, (int) round(($memberCount / $requiredMembers) * 100));
    $complete = $memberCount >= $requiredMembers;
    $submissions = $team->submissions->sortByDesc(fn ($submission) => optional($submission->submitted_at ?: $submission->created_at)->timestamp)->values();
    $approvedStatuses = ['processed', 'approved', 'approved_manual'];
    $reviewStatuses = ['pending', 'review_required'];
    $approvedCount = $submissions->whereIn('status', $approvedStatuses)->count();
    $reviewCount = $submissions->whereIn('status', $reviewStatuses)->count();
    $submissionLimit = $cup->maxSubmissionsPerParticipant();
    $submissionUsed = $submissions->count();
    $submissionPercent = $submissionLimit ? min(100, (int) round(($submissionUsed / max(1, $submissionLimit)) * 100)) : min(100, $submissionUsed * 10);
    $inviteUrl = route('cups.teams.join', [$cup, $team->join_token]);
    $coverUrl = $cup->coverUrl();
    $canRename = ($isCaptain || $canManageTeam) && $team->canChangeRoster();
    $canRecruit = ($isCaptain || $canManageTeam) && $teamFinderEnabled && $team->canChangeRoster() && $team->slotsOpen() > 0 && $cup->isRegistrationOpen();
    $canSubmit = $team->status === 'active' && $cup->isSubmissionOpen() && $team->canSubmitForCup($viewer);
    $profileUrl = static function ($user): string {
        if (! $user?->username) return route('members.index');
        return (int) $user->id === (int) auth()->id() ? route('profile.show') : route('profile.public', $user);
    };
    $platformLabel = collect($cup->allowedPlatforms())->implode(' / ') ?: $t('Alle Plattformen', 'All platforms');
    $lockDate = $team->roster_locked_at ?: $cup->registration_closes_at;
    $activityItems = collect();
    foreach ($submissions->take(3) as $submission) {
        $activityItems->push([
            'title' => $t('Einreichung von', 'Submission by').' '.($submission->submitter?->username ?: $submission->submitter?->name ?: 'Hunter'),
            'text' => $submission->statusLabel(),
            'time' => ($submission->submitted_at ?: $submission->created_at)?->diffForHumans(),
        ]);
    }
    foreach ($chatMessages->take(-3)->reverse() as $message) {
        $activityItems->push([
            'title' => ($message->user?->username ?: $message->user?->name ?: 'Hunter').' '.$t('hat geschrieben', 'posted in chat'),
            'text' => Str::limit($message->body, 58),
            'time' => $message->created_at?->diffForHumans(),
        ]);
    }
    $activityItems = $activityItems->take(5);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta content="noindex,nofollow,noarchive" name="robots"/>
<title>{{ $team->displayName() }} · {{ $t('Team verwalten', 'Manage team') }} · HNT.ROCKS</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/team-manage-live.css')) ?: time() }}" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/shared/hnt-modal.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/shared/hnt-modal.css')) ?: time() }}" rel="stylesheet"/>
</head>
<body data-page="team-manage">
@include('themes.hnt_preview.partials.icons')
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
<main class="app-shell team-manage-page-shell"
      data-cups-active-url="{{ route('cups.index', ['status' => 'active']) }}"
      data-cups-mine-url="{{ route('cups.index', ['mine' => 1]) }}"
      data-cups-submissions-url="{{ route('cups.show.section', [$cup, 'submissions']) }}"
      data-cups-hall-url="{{ route('hall-of-fame.index') }}">
@include('themes.hnt_preview.partials.header')

<section class="team-manage-stage">
    <aside class="team-manage-side team-manage-left" aria-label="{{ $t('Teamübersicht', 'Team overview') }}">
        <article class="tm-card tm-identity-card">
            <a class="tm-cover" href="{{ route('cups.show', $cup) }}">
                <img src="{{ $coverUrl }}" alt="{{ $cup->title }}"/>
                <span><svg><use href="#i-arrow"></use></svg></span>
            </a>
            <div class="tm-identity-copy">
                <span class="tm-kicker">{{ $t('DEIN TEAM', 'YOUR TEAM') }}</span>
                <h2>{{ $team->displayName() }}</h2>
                <p>{{ $cup->title }} · {{ $cup->modeLabel() }} · {{ $platformLabel }}</p>
                <div class="tm-avatar-stack">
                    @foreach($members->take(3) as $member)
                        <img src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""/>
                    @endforeach
                    <strong>{{ $memberCount }}/{{ $requiredMembers }}</strong>
                </div>
                <div class="tm-progress-block">
                    <div><span>{{ $t('Teamstatus', 'Team status') }}</span><strong>{{ $teamPercent }}%</strong></div>
                    <i><b style="width:{{ $teamPercent }}%"></b></i>
                    <small>{{ $complete ? $t('Team vollständig', 'Team complete') : $t('Noch Plätze frei', 'Open slots remain') }}</small>
                </div>
                <div class="tm-pills">
                    <span>{{ $team->statusLabel() }}</span>
                    <span>{{ $isCaptain ? 'Captain' : $t('Mitglied', 'Member') }}</span>
                    <span>{{ $team->isRosterLocked() ? $t('Roster gesperrt', 'Roster locked') : $t('Roster offen', 'Roster open') }}</span>
                </div>
            </div>
        </article>

        <article class="tm-card tm-quick-card">
            <header><span class="tm-kicker">{{ $t('SCHNELLZUGRIFF', 'QUICK ACCESS') }}</span><h3>{{ $t('Teamaktionen', 'Team actions') }}</h3></header>
            <button type="button" data-team-tab-shortcut="invites"><svg><use href="#i-share"></use></svg><span><strong>{{ $t('Einladung teilen', 'Share invite') }}</strong><small>{{ $memberCount }}/{{ $requiredMembers }} {{ $t('Mitglieder', 'members') }}</small></span><i>→</i></button>
            <button type="button" data-team-tab-shortcut="submissions"><svg><use href="#i-image"></use></svg><span><strong>{{ $t('Einreichungen', 'Submissions') }}</strong><small>{{ $submissionUsed }}{{ $submissionLimit ? ' / '.$submissionLimit : '' }} {{ $t('genutzt', 'used') }}</small></span><i>→</i></button>
            <button type="button" data-team-tab-shortcut="members"><svg><use href="#i-users"></use></svg><span><strong>{{ $t('Mitglieder', 'Members') }}</strong><small>{{ $memberCount }} {{ $t('aktive Hunter', 'active Hunters') }}</small></span><i>→</i></button>
        </article>
    </aside>

    <section class="team-manage-scroll" id="teamManageScroll" tabindex="0">
        @if(session('status') || $errors->any())
            <article class="tm-alert {{ $errors->any() ? 'is-error' : 'is-success' }}">
                <strong>{{ $errors->any() ? $t('Bitte prüfen', 'Please check') : $t('Erledigt', 'Done') }}</strong>
                <span>{{ $errors->any() ? $errors->first() : session('status') }}</span>
            </article>
        @endif

        <section class="team-manage-overview">
            <div>
                <span class="tm-kicker">{{ Str::upper($cup->title) }} · {{ $t('DEIN TEAM', 'YOUR TEAM') }}</span>
                <h1>{{ $t('Team verwalten', 'Manage team') }}</h1>
                <div class="team-manage-meta">
                    <span class="is-live"><i></i>{{ $team->statusLabel() }}</span>
                    <span>{{ $team->displayName() }}</span>
                    <span>{{ $isCaptain ? 'Captain' : $t('Mitglied', 'Member') }}</span>
                    <span>{{ $platformLabel }}</span>
                    <span>{{ $memberCount }} / {{ $requiredMembers }} Hunter</span>
                </div>
                <p>{{ $t('Verwalte Teamname, Mitglieder, Einladungen, Teamchat und Einreichungen für diesen Community Cup.', 'Manage the team name, members, invitations, team chat and submissions for this Community Cup.') }}</p>
                <div class="team-manage-bars">
                    <div><span>{{ $t('Team vollständig', 'Team complete') }}</span><i><b style="width:{{ $teamPercent }}%"></b></i><strong>{{ $memberCount }}/{{ $requiredMembers }}</strong></div>
                    <div><span>{{ $t('Einreichungen', 'Submissions') }}</span><i><b style="width:{{ $submissionPercent }}%"></b></i><strong>{{ $submissionUsed }}{{ $submissionLimit ? '/'.$submissionLimit : '' }}</strong></div>
                    <div><span>{{ $t('Teamchat', 'Team chat') }}</span><i><b style="width:{{ min(100, $chatMessagesCount * 10) }}%"></b></i><strong>{{ $chatMessagesCount }}</strong></div>
                </div>
            </div>
            <div class="team-manage-overview-stats">
                <article><strong>{{ $memberCount }}</strong><span>{{ $t('Mitglieder', 'Members') }}</span></article>
                <article><strong>{{ $approvedCount }}</strong><span>Scores</span></article>
                <article><strong>{{ $chatMessagesCount }}</strong><span>{{ $t('Nachrichten', 'Messages') }}</span></article>
            </div>
        </section>

        <article class="team-center-card" id="teamManageCenter">
            <header class="team-center-head">
                <div><span class="tm-kicker">{{ Str::upper($team->displayName()) }}</span><h2 id="teamPanelTitle">{{ $t('Übersicht', 'Overview') }}</h2></div>
                <nav class="team-tabs" role="tablist" aria-label="{{ $t('Team Bereiche', 'Team sections') }}">
                    <button class="active" data-team-tab="overview" data-title="{{ $t('Übersicht', 'Overview') }}" type="button">{{ $t('Übersicht', 'Overview') }}</button>
                    <button data-team-tab="members" data-title="{{ $t('Mitglieder', 'Members') }}" type="button">{{ $t('Mitglieder', 'Members') }}</button>
                    <button data-team-tab="invites" data-title="{{ $t('Einladungen', 'Invites') }}" type="button">{{ $t('Einladungen', 'Invites') }}</button>
                    <button data-team-tab="submissions" data-title="{{ $t('Einreichungen', 'Submissions') }}" type="button">{{ $t('Einreichungen', 'Submissions') }}</button>
                    <button data-team-tab="recruiting" data-title="Recruiting" type="button">Recruiting</button>
                </nav>
            </header>

            <div class="team-panels">
                <section class="team-panel active" data-team-panel="overview">
                    <div class="tm-grid-2">
                        <article class="tm-panel-card">
                            <header><div><span class="tm-kicker">TEAMNAME</span><h3>{{ $team->displayName() }}</h3></div></header>
                            <p>{{ $canRename ? $t('Du kannst den Teamnamen ändern, solange das Roster nicht gesperrt ist.', 'You can rename the team while the roster remains unlocked.') : $t('Nur der Captain kann den Teamnamen ändern.', 'Only the captain can rename the team.') }}</p>
                            @if($canRename)
                                <form class="tm-name-form" method="post" action="{{ route('cups.teams.update', [$cup, $team]) }}">
                                    @csrf @method('patch')
                                    <input name="name" maxlength="100" required value="{{ old('name', $team->displayName()) }}"/>
                                    <button type="submit">{{ $t('Speichern', 'Save') }}</button>
                                </form>
                            @endif
                            <div class="tm-pills"><span>{{ $team->statusLabel() }}</span><span>{{ $team->isRosterLocked() ? $t('Gesperrt', 'Locked') : $t('Offen', 'Open') }}</span><span>Captain: {{ $team->owner?->username ?: $team->owner?->name }}</span></div>
                        </article>

                        <article class="tm-panel-card tm-readiness-card">
                            <header><div><span class="tm-kicker">{{ $t('TEILNAHMESTATUS', 'PARTICIPATION') }}</span><h3>{{ $complete ? $t('Bereit für den Cup', 'Ready for the Cup') : $t('Team noch unvollständig', 'Team not complete') }}</h3></div><strong>{{ $teamPercent }}%</strong></header>
                            <div class="tm-readiness-steps">
                                <span class="{{ $memberCount > 0 ? 'done' : '' }}"><i>1</i><b>Team</b><small>{{ $memberCount }}/{{ $requiredMembers }}</small></span>
                                <span class="{{ $complete ? 'done' : '' }}"><i>2</i><b>{{ $t('Vollständig', 'Complete') }}</b><small>{{ $complete ? $t('bereit', 'ready') : $t('offen', 'open') }}</small></span>
                                <span class="{{ $approvedCount > 0 ? 'done' : '' }}"><i>3</i><b>Scores</b><small>{{ $approvedCount }} {{ $t('gewertet', 'scored') }}</small></span>
                            </div>
                        </article>
                    </div>

                    <article class="tm-panel-card">
                        <header><div><span class="tm-kicker">{{ $t('DEIN TEAM', 'YOUR TEAM') }}</span><h3>{{ $t('Mitglieder', 'Members') }}</h3></div><button type="button" data-team-tab-shortcut="members">{{ $t('Alle ansehen', 'View all') }}</button></header>
                        <div class="tm-member-table">
                            @foreach($members as $member)
                                @php($user = $member->user)
                                <a href="{{ $user ? $profileUrl($user) : route('members.index') }}">
                                    <img src="{{ $user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""/>
                                    <span><strong>{{ $user?->username ?: $user?->name ?: 'Hunter' }}</strong><small>{{ $member->role === 'captain' ? 'Captain' : $t('Mitglied', 'Member') }}</small></span>
                                    <em>{{ $user?->profile?->platform ?: $platformLabel }}</em>
                                    <b><i></i>{{ $t('Aktiv', 'Active') }}</b>
                                    <svg><use href="#i-arrow"></use></svg>
                                </a>
                            @endforeach
                        </div>
                    </article>

                    <div class="tm-grid-2">
                        <article class="tm-panel-card tm-submission-ready">
                            <header><div><span class="tm-kicker">{{ $t('EINREICHUNGEN', 'SUBMISSIONS') }}</span><h3>{{ $canSubmit ? $t('Upload ist möglich', 'Upload available') : $t('Upload noch nicht möglich', 'Upload unavailable') }}</h3></div><strong>{{ $submissionUsed }}{{ $submissionLimit ? '/'.$submissionLimit : '' }}</strong></header>
                            <p>{{ $cup->isSubmissionOpen() ? $t('Der Einreichungszeitraum ist geöffnet.', 'The submission period is open.') : $cup->submissionClosedReason() }}</p>
                            <i class="tm-upload-progress"><b style="width:{{ $submissionPercent }}%"></b></i>
                            @if($canSubmit)<a href="{{ route('cups.show.section', [$cup, 'submit']) }}">{{ $t('Screenshot einreichen', 'Submit screenshot') }}</a>@endif
                        </article>
                        <article class="tm-panel-card tm-activity-card">
                            <header><div><span class="tm-kicker">{{ $t('LETZTE AKTIVITÄT', 'RECENT ACTIVITY') }}</span><h3>{{ $t('Im Team', 'In the team') }}</h3></div></header>
                            <ul>
                                @forelse($activityItems as $item)
                                    <li><i></i><span><strong>{{ $item['title'] }}</strong><small>{{ $item['text'] }}</small></span><time>{{ $item['time'] }}</time></li>
                                @empty
                                    <li><span><strong>{{ $t('Noch keine Aktivität', 'No activity yet') }}</strong></span></li>
                                @endforelse
                            </ul>
                        </article>
                    </div>

                    @if(!$team->isRosterLocked())
                        <article class="tm-panel-card tm-danger-zone">
                            <div><span class="tm-kicker">{{ $isCaptain ? $t('TEAM ZURÜCKZIEHEN', 'WITHDRAW TEAM') : $t('TEAM VERLASSEN', 'LEAVE TEAM') }}</span><h3>{{ $t('Teilnahme beenden', 'End participation') }}</h3><p>{{ $isCaptain ? $t('Als Captain ziehst du das gesamte Team aus dem Cup zurück.', 'As captain, you will withdraw the entire team from the Cup.') : $t('Du verlässt dieses Cup-Team.', 'You will leave this Cup team.') }}</p></div>
                            <button type="button" data-open-team-leave>{{ $isCaptain ? $t('Team zurückziehen', 'Withdraw team') : $t('Team verlassen', 'Leave team') }}</button>
                        </article>
                    @endif
                </section>

                <section class="team-panel" data-team-panel="members" hidden>
                    <div class="tm-section-intro"><div><span class="tm-kicker">{{ $memberCount }}/{{ $requiredMembers }} HUNTER</span><h3>{{ $t('Teammitglieder', 'Team members') }}</h3><p>{{ $t('Profile, Plattformen und Rollen deines Rosters.', 'Profiles, platforms and roles in your roster.') }}</p></div><span>{{ $team->isRosterLocked() ? $t('Roster gesperrt', 'Roster locked') : $t('Roster offen', 'Roster open') }}</span></div>
                    <div class="tm-member-cards">
                        @foreach($members as $member)
                            @php($user = $member->user)
                            <article>
                                <div class="tm-member-card-head"><img src="{{ $user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""/><div><span>{{ Str::upper($member->role) }}</span><h3>{{ $user?->username ?: $user?->name ?: 'Hunter' }}</h3><p>{{ $user?->last_seen_at?->diffForHumans() ?: $t('Aktiv', 'Active') }}</p></div><a href="{{ $user ? $profileUrl($user) : route('members.index') }}"><svg><use href="#i-arrow"></use></svg></a></div>
                                <div class="tm-member-stats"><span><strong>{{ $user?->level ?: 1 }}</strong><small>Level</small></span><span><strong>{{ $user?->profile?->platform ?: '—' }}</strong><small>{{ $t('Plattform', 'Platform') }}</small></span><span><strong>{{ $user?->profile?->region ?: '—' }}</strong><small>{{ $t('Region', 'Region') }}</small></span></div>
                                <div class="tm-member-details"><span>{{ $user?->profile?->playstyle ?: $t('Spielstil offen', 'Playstyle open') }}</span><span>{{ $user?->profile?->language ?: $t('Sprache offen', 'Language open') }}</span></div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="team-panel" data-team-panel="invites" hidden>
                    <div class="tm-section-intro"><div><span class="tm-kicker">{{ $t('EINLADUNGSLINK', 'INVITE LINK') }}</span><h3>{{ $t('Hunter einladen', 'Invite Hunters') }}</h3><p>{{ $t('Der Link gilt bis zur Rostersperre oder bis das Team vollständig ist.', 'The link remains valid until the roster is locked or the team is complete.') }}</p></div><span>{{ $memberCount }}/{{ $requiredMembers }}</span></div>
                    <article class="tm-panel-card tm-invite-card">
                        <div><span class="tm-kicker">{{ $t('DEIN PERSÖNLICHER LINK', 'YOUR PERSONAL LINK') }}</span><h3>{{ $cup->title }} · {{ $team->displayName() }}</h3><p>{{ $t('Dieser Link gehört zu deinem Team und kann direkt geteilt werden.', 'This link belongs to your team and can be shared directly.') }}</p><div class="tm-invite-link"><input id="teamInviteLink" readonly value="{{ $inviteUrl }}"/><button type="button" data-copy-invite>{{ $t('Link kopieren', 'Copy link') }}</button></div></div>
                        <div class="tm-invite-symbol"><svg><use href="#i-share"></use></svg></div>
                    </article>
                    <article class="tm-panel-card tm-lock-card"><span class="tm-kicker">TEAM-LOCK</span><strong>{{ $lockDate ? $lockDate->translatedFormat('d.m.Y · H:i') : $t('Noch nicht festgelegt', 'Not set yet') }}</strong><p>{{ $team->isRosterLocked() ? $t('Das Roster ist gesperrt und kann nicht mehr verändert werden.', 'The roster is locked and can no longer be changed.') : $t('Nach der Sperre verlieren offene Einladungen ihre Wirkung.', 'Open invitations stop working after the lock.') }}</p></article>
                </section>

                <section class="team-panel" data-team-panel="submissions" hidden>
                    <div class="tm-section-intro"><div><span class="tm-kicker">{{ $submissionUsed }}{{ $submissionLimit ? '/'.$submissionLimit : '' }} {{ $t('GENUTZT', 'USED') }}</span><h3>{{ $t('Einreichungen', 'Submissions') }}</h3><p>{{ $t('Status und Punkte aller Team-Einreichungen.', 'Status and points for all team submissions.') }}</p></div>@if($canSubmit)<a class="tm-primary-button" href="{{ route('cups.show.section', [$cup, 'submit']) }}">{{ $t('Neue Einreichung', 'New submission') }}</a>@endif</div>
                    <div class="tm-submission-summary"><article><span>{{ $t('Gewertet', 'Scored') }}</span><strong>{{ $approvedCount }}</strong><small>{{ $submissions->whereIn('status', $approvedStatuses)->sum('points') }} {{ $t('Punkte', 'points') }}</small></article><article><span>{{ $t('In Prüfung', 'In review') }}</span><strong>{{ $reviewCount }}</strong><small>{{ $t('offene Prüfung', 'pending review') }}</small></article><article><span>{{ $t('Frei', 'Available') }}</span><strong>{{ $submissionLimit ? max(0, $submissionLimit - $submissionUsed) : '∞' }}</strong><small>{{ $t('Uploads verbleibend', 'uploads remaining') }}</small></article></div>
                    <div class="tm-submission-list">
                        @forelse($submissions as $submission)
                            @php($tone = in_array($submission->status, $approvedStatuses, true) ? 'approved' : (in_array($submission->status, $reviewStatuses, true) ? 'review' : 'rejected'))
                            <article><span class="{{ $tone }}"><svg><use href="#{{ $tone === 'approved' ? 'i-check' : 'i-eye' }}"></use></svg></span><div><strong>{{ $submission->submitter?->username ?: $submission->submitter?->name ?: 'Hunter' }}</strong><small>{{ (int) $submission->kills }} Kills · {{ (int) $submission->bounty_tokens }} Bounty · {{ ($submission->submitted_at ?: $submission->created_at)?->diffForHumans() }}</small></div><b>{{ (int) $submission->points }} {{ $t('Punkte', 'points') }}</b><em>{{ $submission->statusLabel() }}</em><a href="{{ route('cups.submissions.screenshot', [$cup, $submission]) }}" target="_blank"><svg><use href="#i-eye"></use></svg></a></article>
                        @empty
                            <p class="tm-empty">{{ $t('Noch keine Einreichungen vorhanden.', 'No submissions yet.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="team-panel" data-team-panel="recruiting" hidden>
                    <div class="tm-section-intro"><div><span class="tm-kicker">TEAMFINDER</span><h3>Recruiting</h3><p>{{ $t('Schalte dein Team für passende Hunter sichtbar, solange Plätze frei sind.', 'Make your team visible to matching Hunters while slots are open.') }}</p></div><span>{{ $team->isRecruiting() ? $t('aktiv', 'active') : $t('pausiert', 'paused') }}</span></div>
                    <article class="tm-panel-card tm-recruiting-toggle"><div><span class="tm-kicker">RECRUITING-STATUS</span><h3>{{ $team->isRecruiting() ? $t('Recruiting aktiv', 'Recruiting active') : $t('Recruiting pausiert', 'Recruiting paused') }}</h3><p>{{ $canRecruit ? $t('Du kannst den Status jederzeit bis zum Team-Lock ändern.', 'You can change the status until team lock.') : $t('Recruiting ist bei vollem oder gesperrtem Team nicht verfügbar.', 'Recruiting is unavailable for full or locked teams.') }}</p></div>@if($teamFinderEnabled && ($isCaptain || $canManageTeam))<form method="post" action="{{ route('cups.teams.recruiting', [$cup, $team]) }}">@csrf @method('patch')<input type="hidden" name="is_recruiting" value="{{ $team->isRecruiting() ? 0 : 1 }}"/><button type="submit" @disabled(!$canRecruit && !$team->isRecruiting())>{{ $team->isRecruiting() ? $t('Pausieren', 'Pause') : $t('Aktivieren', 'Enable') }}</button></form>@endif</article>
                    <div class="tm-grid-2">
                        <article class="tm-panel-card tm-finder-players"><header><div><span class="tm-kicker">{{ $t('SUCHEN EIN TEAM', 'LOOKING FOR A TEAM') }}</span><h3>{{ $t('Verfügbare Hunter', 'Available Hunters') }}</h3></div><small>{{ $finderPosts->count() }}</small></header>@forelse($finderPosts as $finder)<a href="{{ $finder->user ? $profileUrl($finder->user) : route('members.index') }}"><img src="{{ $finder->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""/><span><strong>{{ $finder->user?->username ?: $finder->user?->name ?: 'Hunter' }}</strong><small>{{ $finder->platform ?: $finder->user?->profile?->platform ?: $t('Flexibel', 'Flexible') }} · {{ Str::limit($finder->message ?: $t('Sucht ein Cup-Team', 'Looking for a Cup team'), 55) }}</small></span><svg><use href="#i-arrow"></use></svg></a>@empty<p class="tm-empty">{{ $t('Aktuell sucht niemand öffentlich nach einem Team.', 'Nobody is publicly looking for a team right now.') }}</p>@endforelse</article>
                        <article class="tm-panel-card tm-recruiting-info"><span class="tm-kicker">{{ $t('SO FUNKTIONIERT ES', 'HOW IT WORKS') }}</span><h3>{{ $t('Teamfinder für Captains', 'Team finder for captains') }}</h3><p>{{ $t('Bei einem freien Platz kannst du dein Team als „sucht Hunter“ markieren. Interessierte Spieler sehen Teamname, Captain und offene Plätze.', 'When a slot is open, mark the team as looking for Hunters. Interested players can see the team, captain and open slots.') }}</p><ul><li>{{ $t('nur während offener Anmeldung', 'only during open registration') }}</li><li>{{ $t('nicht nach Team-Lock', 'not after team lock') }}</li><li>{{ $t('automatisch aus bei vollem Team', 'disabled when the team is full') }}</li></ul></article>
                    </div>
                </section>
            </div>
        </article>
    </section>

    <aside class="team-manage-side team-manage-right" aria-label="Teamchat">
        <article class="tm-card tm-chat-card" data-team-chat-panel data-chat-index-url="{{ route('cups.teams.chat.index', [$cup, $team]) }}">
            <header><div><span class="tm-kicker">TEAMCHAT</span><h2>{{ $team->displayName() }}</h2></div><strong data-team-chat-count>{{ $chatMessagesCount }}</strong></header>
            <div class="tm-chat-list" data-team-chat-list>
                @forelse($chatMessages as $chatMessage)
                    @include('themes.hnt_preview.cups.partials.chat-message', ['chatMessage' => $chatMessage])
                @empty
                    <p class="tm-empty" data-team-chat-empty>{{ $t('Noch keine Nachrichten.', 'No messages yet.') }}</p>
                @endforelse
            </div>
            <form class="tm-chat-compose" method="post" action="{{ route('cups.teams.chat.store', [$cup, $team]) }}" data-team-chat-form>
                @csrf
                <img src="{{ $viewer->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt=""/>
                <input name="body" maxlength="1200" required autocomplete="off" placeholder="{{ $t('Nachricht schreiben …', 'Write a message …') }}"/>
                <button type="submit" aria-label="{{ $t('Nachricht senden', 'Send message') }}">→</button>
            </form>
        </article>
    </aside>
</section>

<div class="hnt-modal-backdrop" data-team-leave-modal aria-hidden="true">
    <section class="hnt-modal" role="dialog" aria-modal="true" aria-labelledby="teamLeaveTitle">
        <form method="post" action="{{ route('cups.teams.leave', [$cup, $team]) }}">
            @csrf
            <header class="hnt-modal__head"><div class="hnt-modal__head-copy"><span class="hnt-modal__kicker">{{ $isCaptain ? $t('TEAM ZURÜCKZIEHEN', 'WITHDRAW TEAM') : $t('TEAM VERLASSEN', 'LEAVE TEAM') }}</span><h2 id="teamLeaveTitle">{{ $team->displayName() }}</h2><p>{{ $isCaptain ? $t('Das gesamte Team wird aus dem Cup zurückgezogen.', 'The whole team will be withdrawn from the Cup.') : $t('Du verlässt dieses Cup-Team.', 'You will leave this Cup team.') }}</p></div><button class="hnt-modal__close" type="button" data-close-team-leave aria-label="{{ $t('Schließen', 'Close') }}"><svg><use href="#i-x"></use></svg></button></header>
            <div class="hnt-modal__body"><div class="hnt-modal__note"><span class="hnt-modal__note-icon"><svg><use href="#i-users"></use></svg></span><div><strong>{{ $t('Diese Aktion kann nicht direkt rückgängig gemacht werden.', 'This action cannot be undone immediately.') }}</strong><span>{{ $team->isRosterLocked() ? $t('Das Roster ist bereits gesperrt.', 'The roster is already locked.') : $t('Offene Einladungen verlieren ihre Wirkung.', 'Open invites will stop working.') }}</span></div></div></div>
            <footer class="hnt-modal__footer"><button class="hnt-modal__button hnt-modal__button--secondary" type="button" data-close-team-leave>{{ $t('Abbrechen', 'Cancel') }}</button><button class="hnt-modal__button hnt-modal__button--primary" type="submit">{{ $isCaptain ? $t('Team zurückziehen', 'Withdraw team') : $t('Team verlassen', 'Leave team') }}</button></footer>
        </form>
    </section>
</div>
<div class="toast" id="toast"></div>
</main>
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = {{ \Illuminate\Support\Js::from(route('feed.index')) }};</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-cups/team-manage-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-cups/team-manage-live.js')) ?: time() }}"></script>
</body>
</html>
