@php
    $teamDetail = app(\App\Support\TeamDetailDashboardData::class)->build($team, auth()->user());
    $activeMembers = $teamDetail['activeMembers'];
    $pendingMembers = $teamDetail['pendingMembers'];
    $teamPosts = $teamDetail['posts'];
    $latestPost = $teamDetail['latestPost'];
    $upcomingSession = $teamDetail['upcomingSession'];
    $activeContracts = $teamDetail['activeContracts'];
    $teamActivities = $teamDetail['activities'];
    $onlineMembers = $teamDetail['onlineMembers'];
    $profileCompletion = $teamDetail['profileCompletion'];
    $sessionGoing = $teamDetail['sessionGoing'];
    $viewerSessionResponse = $teamDetail['viewerSessionResponse'];
    $teamInitials = $teamDetail['teamInitials'];
    $isEnglish = $teamDetail['isEnglish'];
    $isMember = $teamDetail['isMember'];
    $viewerMembership = $teamDetail['viewerMembership'];
    $canManage = $teamDetail['canManage'];
    $teamName = $team->name;
    $teamTagline = $team->tagline ?: ($isEnglish ? 'Hunt together. Grow together.' : 'Gemeinsam jagen. Gemeinsam wachsen.');
    $teamDescription = $team->description ?: ($isEnglish ? 'This team has not added a description yet.' : 'Dieses Team hat noch keine Beschreibung hinterlegt.');
    $sessionsThisWeek = (int) $teamDetail['sessionsThisWeek'];
    $completedSessionsThisWeek = (int) $teamDetail['completedSessionsThisWeek'];
    $sessionWeekProgress = $sessionsThisWeek > 0
        ? min(100, (int) round(($completedSessionsThisWeek / $sessionsThisWeek) * 100))
        : 0;
    $averageContractProgress = (int) $teamDetail['averageContractProgress'];
    $teamInviteUrl = route('teams.show', $team);
    $sessionDuration = $upcomingSession && $upcomingSession->ends_at
        ? max(1, $upcomingSession->starts_at->diffInMinutes($upcomingSession->ends_at))
        : null;
    $goingCount = $sessionGoing->count();
    $sessionCapacity = $upcomingSession
        ? max(1, (int) ($upcomingSession->max_participants ?: $activeMembers->count()))
        : 0;
@endphp

<section class="team-detail-stage">
    <div class="team-detail-scroll" id="teamDetailScroll">
        <section class="team-detail-heading">
            <div>
                <span>HNT.ROCKS TEAM</span>
                <h1>{{ $teamName }}</h1>
                <div class="team-heading-tags">
                    <span class="good"><i></i>{{ $team->status === 'active' ? ($isEnglish ? 'Active' : 'Aktiv') : ucfirst($team->status) }}</span>
                    <span>{{ $team->visibilityLabel() }}</span>
                    <span>{{ $team->recruitmentLabel() }}</span>
                    @if($team->platform)
                        <span>{{ $team->platform }}</span>
                    @endif
                    @if($team->region || $team->language)
                        <span>{{ collect([$team->region, $team->language])->filter()->implode(' · ') }}</span>
                    @endif
                </div>
                <p>{{ $teamTagline }}</p>
            </div>

            <div class="team-heading-stats">
                <article><strong>{{ (int) $team->members_count }}</strong><span>{{ $isEnglish ? 'Members' : 'Mitglieder' }}</span></article>
                <article><strong>{{ (int) $team->posts_count }}</strong><span>{{ $isEnglish ? 'Posts' : 'Beiträge' }}</span></article>
                <article><strong>{{ (int) $team->sessions_count }}</strong><span>Sessions</span></article>
                <article><strong>{{ (int) $team->pending_count }}</strong><span>{{ $isEnglish ? 'Requests' : 'Anfragen' }}</span></article>
            </div>
        </section>

        <section class="team-detail-workspace">
            <aside class="team-left-sidebar">
                <article class="team-identity-card">
                    <div class="team-cover" style="background-image:linear-gradient(180deg,rgba(20,20,18,.05),rgba(20,20,18,.58)),url('{{ $team->coverUrl() }}')">
                        <div class="team-cover-grid"></div>
                        <button aria-label="{{ $isEnglish ? 'Share invitation' : 'Einladung teilen' }}" data-copy-team-url="{{ $teamInviteUrl }}" type="button">
                            <svg><use href="#i-share"></use></svg>
                        </button>
                        <div class="team-cover-identity">
                            <div class="team-avatar {{ $team->avatar_path ? 'has-image' : '' }}">
                                @if($team->avatar_path)
                                    <img src="{{ $team->avatarUrl() }}" alt="{{ $teamName }}">
                                @else
                                    {{ $teamInitials }}
                                @endif
                            </div>
                            <div>
                                <span>{{ $isMember ? ($isEnglish ? 'YOUR TEAM' : 'DEIN TEAM') : 'HNT.ROCKS TEAM' }}</span>
                                <strong>{{ $teamName }}</strong>
                                <small>{{ collect([$team->playstyle, $team->region])->filter()->implode(' · ') ?: ($isEnglish ? 'Community team' : 'Community-Team') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="team-identity-copy"><p>{{ $teamTagline }}</p></div>

                    <div class="team-identity-member-row">
                        <div class="team-avatar-stack">
                            @forelse($activeMembers->take(4) as $member)
                                <img alt="{{ $member->user?->name }}" src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}">
                            @empty
                                <img alt="" src="{{ asset('assets/vikinger/img/default-avatar.svg') }}">
                            @endforelse
                        </div>
                        <strong>{{ $activeMembers->count() }} {{ $isEnglish ? 'active' : 'aktiv' }}</strong>
                    </div>

                    <div class="team-completion">
                        <div><span>{{ $isEnglish ? 'Team profile' : 'Teamprofil' }}</span><strong>{{ $profileCompletion }}%</strong></div>
                        <i><b style="width:{{ $profileCompletion }}%"></b></i>
                        <small>{{ $profileCompletion >= 100 ? ($isEnglish ? 'All team details are complete' : 'Alle Teamangaben sind vollständig') : ($isEnglish ? 'Complete the missing team details' : 'Fehlende Teamangaben ergänzen') }}</small>
                    </div>

                    <div class="team-identity-actions">
                        @if($canManage)
                            <a href="{{ route('teams.edit', $team) }}">{{ $isEnglish ? 'Edit team' : 'Team bearbeiten' }} <svg><use href="#i-arrow"></use></svg></a>
                        @elseif(!$viewerMembership && $team->recruitment_status === 'open')
                            <form method="post" action="{{ route('teams.join', $team) }}">
                                @csrf
                                <button type="submit">{{ $isEnglish ? 'Request to join' : 'Beitritt anfragen' }}</button>
                            </form>
                        @elseif($viewerMembership?->status === 'pending')
                            <span class="team-membership-state">{{ $isEnglish ? 'Request pending' : 'Anfrage ausstehend' }}</span>
                        @elseif($viewerMembership && $viewerMembership->role !== 'owner')
                            <form method="post" action="{{ route('teams.leave', $team) }}" onsubmit="return confirm('{{ $isEnglish ? 'Leave this team?' : 'Dieses Team wirklich verlassen?' }}')">
                                @csrf
                                <button type="submit">{{ $isEnglish ? 'Leave team' : 'Team verlassen' }}</button>
                            </form>
                        @endif
                        <button data-copy-team-url="{{ $teamInviteUrl }}" type="button"><svg><use href="#i-share"></use></svg>{{ $isEnglish ? 'Share' : 'Einladung' }}</button>
                    </div>
                </article>

                <article class="team-info-card">
                    <header><div><span>{{ $isEnglish ? 'TEAM INFO' : 'TEAMINFO' }}</span><h2>{{ $isEnglish ? 'At a glance' : 'Auf einen Blick' }}</h2></div></header>
                    <dl>
                        <div><dt>{{ $isEnglish ? 'Platform' : 'Plattform' }}</dt><dd>{{ $team->platform ?: '—' }}</dd></div>
                        <div><dt>Region</dt><dd>{{ $team->region ?: '—' }}</dd></div>
                        <div><dt>{{ $isEnglish ? 'Playstyle' : 'Spielstil' }}</dt><dd>{{ $team->playstyle ?: '—' }}</dd></div>
                        <div><dt>{{ $isEnglish ? 'Language' : 'Sprache' }}</dt><dd>{{ $team->language ?: '—' }}</dd></div>
                        <div><dt>{{ $isEnglish ? 'Founded' : 'Gegründet' }}</dt><dd>{{ $team->created_at?->translatedFormat('F Y') ?: '—' }}</dd></div>
                        <div><dt>{{ $isEnglish ? 'Visibility' : 'Sichtbarkeit' }}</dt><dd>{{ $team->visibilityLabel() }}</dd></div>
                    </dl>
                </article>

                <article class="team-quick-nav-card">
                    <header><span>{{ $isEnglish ? 'QUICK ACCESS' : 'SCHNELLZUGRIFF' }}</span><h2>{{ $isEnglish ? 'Team areas' : 'Teambereiche' }}</h2></header>
                    <nav>
                        <button class="active" data-open-team-tab="overview" type="button"><svg><use href="#i-sliders"></use></svg><span><strong>{{ $isEnglish ? 'Overview' : 'Übersicht' }}</strong><small>{{ $isEnglish ? 'Activity and status' : 'Aktivität und Status' }}</small></span><i>01</i></button>
                        <button data-open-team-tab="posts" type="button"><svg><use href="#i-comment"></use></svg><span><strong>{{ $isEnglish ? 'Posts' : 'Beiträge' }}</strong><small>{{ $team->posts_count }} {{ $isEnglish ? 'team posts' : 'Team-Beiträge' }}</small></span><i>{{ $team->posts_count }}</i></button>
                        <button data-open-team-tab="info" type="button"><svg><use href="#i-eye"></use></svg><span><strong>Info</strong><small>{{ $isEnglish ? 'Description and data' : 'Beschreibung und Daten' }}</small></span><i>{{ $profileCompletion }}%</i></button>
                        <button data-open-team-tab="members" type="button"><svg><use href="#i-users"></use></svg><span><strong>{{ $isEnglish ? 'Members' : 'Mitglieder' }}</strong><small>{{ $isEnglish ? 'Roster and roles' : 'Roster und Rollen' }}</small></span><i>{{ $team->members_count }}</i></button>
                        @if($canManage)
                            <button data-open-team-tab="requests" type="button"><svg><use href="#i-user"></use></svg><span><strong>{{ $isEnglish ? 'Requests' : 'Anfragen' }}</strong><small>{{ $team->pending_count ? ($isEnglish ? 'Open join requests' : 'Offene Beitrittsanfragen') : ($isEnglish ? 'No open requests' : 'Keine offenen Anfragen') }}</small></span><i>{{ $team->pending_count }}</i></button>
                        @endif
                    </nav>
                </article>
            </aside>

            <section class="team-main-card">
                <header class="team-main-head">
                    <div><span>{{ \Illuminate\Support\Str::upper($teamName) }}</span><h2 id="teamPanelTitle">{{ $isEnglish ? 'Overview' : 'Übersicht' }}</h2></div>
                    <nav aria-label="{{ $isEnglish ? 'Team areas' : 'Teambereiche' }}" class="team-tabs">
                        <button class="active" data-team-tab="overview" type="button">{{ $isEnglish ? 'Overview' : 'Übersicht' }}</button>
                        <button data-team-tab="posts" type="button">{{ $isEnglish ? 'Posts' : 'Beiträge' }} <i>{{ $team->posts_count }}</i></button>
                        <button data-team-tab="info" type="button">Info</button>
                        <button data-team-tab="members" type="button">{{ $isEnglish ? 'Members' : 'Mitglieder' }} <i>{{ $team->members_count }}</i></button>
                        @if($canManage)
                            <button data-team-tab="requests" type="button">{{ $isEnglish ? 'Requests' : 'Anfragen' }} <i>{{ $team->pending_count }}</i></button>
                        @endif
                    </nav>
                </header>

                <div class="team-panels">
                    <section class="team-panel active" data-team-panel="overview">
                        <article class="team-overview-hero">
                            <div><span>{{ $isEnglish ? 'TEAM PROFILE' : 'TEAMPROFIL' }}</span><h3>{{ $profileCompletion >= 100 ? ($isEnglish ? 'Ready for the community' : 'Bereit für die Community') : ($isEnglish ? 'Complete your team profile' : 'Vervollständige dein Teamprofil') }}</h3><p>{{ $teamDescription }}</p></div>
                            <div class="team-overview-score"><strong>{{ $profileCompletion }}%</strong><span>{{ $isEnglish ? 'Profile complete' : 'Profil vollständig' }}</span></div>
                            <div class="team-overview-track"><i style="width:{{ $profileCompletion }}%"></i></div>
                            <div class="team-overview-meta">
                                <span><b></b>{{ $activeMembers->count() }} {{ $isEnglish ? 'active members' : 'aktive Mitglieder' }}</span>
                                <span><b></b>{{ $team->recruitmentLabel() }}</span>
                                <span><b></b>{{ $upcomingSession ? ($isEnglish ? 'Session planned' : 'Session geplant') : ($isEnglish ? 'No session planned' : 'Keine Session geplant') }}</span>
                            </div>
                        </article>

                        <section class="team-dashboard-grid">
                            <article class="team-next-session-card">
                                <header>
                                    <div><span>{{ $isEnglish ? 'NEXT SESSION' : 'NÄCHSTE SESSION' }}</span><h3>{{ $upcomingSession?->title ?: ($isEnglish ? 'No session planned' : 'Keine Session geplant') }}</h3></div>
                                    @if($upcomingSession)
                                        <strong>{{ $upcomingSession->starts_at->isToday() ? ($isEnglish ? 'Today' : 'Heute') : $upcomingSession->starts_at->translatedFormat('D, d.m.') }}</strong>
                                    @endif
                                </header>

                                @if($upcomingSession)
                                    <div class="session-time-row">
                                        <div><strong>{{ $upcomingSession->starts_at->format('H:i') }}</strong><span>Start</span></div>
                                        <div><strong>{{ $sessionDuration ? $sessionDuration.' Min.' : '—' }}</strong><span>{{ $isEnglish ? 'Duration' : 'Dauer' }}</span></div>
                                        <div><strong>{{ $goingCount }} / {{ $sessionCapacity }}</strong><span>{{ $isEnglish ? 'Going' : 'Zugesagt' }}</span></div>
                                    </div>
                                    <div class="session-attendees">
                                        <div class="team-avatar-stack">
                                            @foreach($sessionGoing->take(4) as $response)
                                                <img alt="{{ $response->user?->name }}" src="{{ $response->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}">
                                            @endforeach
                                        </div>
                                        <span>{{ $goingCount ? ($isEnglish ? $goingCount.' members confirmed.' : $goingCount.' Mitglieder haben zugesagt.') : ($isEnglish ? 'No confirmations yet.' : 'Noch keine Zusagen.') }}</span>
                                    </div>
                                    @if($isMember)
                                        <form class="team-session-response" method="post" action="{{ route('teams.sessions.respond', [$team, $upcomingSession]) }}">
                                            @csrf
                                            <button name="response" value="going" class="{{ $viewerSessionResponse?->response === 'going' ? 'active' : '' }}" type="submit">{{ $isEnglish ? 'Going' : 'Dabei' }}</button>
                                            <button name="response" value="maybe" class="{{ $viewerSessionResponse?->response === 'maybe' ? 'active' : '' }}" type="submit">{{ $isEnglish ? 'Maybe' : 'Vielleicht' }}</button>
                                            <button name="response" value="declined" class="{{ $viewerSessionResponse?->response === 'declined' ? 'active' : '' }}" type="submit">{{ $isEnglish ? 'Decline' : 'Absagen' }}</button>
                                        </form>
                                    @endif
                                @else
                                    <p class="team-real-empty">{{ $isEnglish ? 'The team has not scheduled a session yet.' : 'Das Team hat noch keine Session geplant.' }}</p>
                                @endif
                            </article>

                            <article class="team-cup-readiness-card team-week-progress-card">
                                <header><div><span>{{ $isEnglish ? 'REAL PROGRESS' : 'ECHTER FORTSCHRITT' }}</span><h3>{{ $isEnglish ? 'Current team status' : 'Aktueller Teamstatus' }}</h3></div><strong>{{ $teamDetail['teamLevel'] }}</strong></header>
                                <div class="cup-readiness-list">
                                    <div><span>{{ $isEnglish ? 'Sessions this week' : 'Sessions diese Woche' }}</span><i><b style="width:{{ $sessionWeekProgress }}%"></b></i><strong>{{ $completedSessionsThisWeek }} / {{ $sessionsThisWeek }}</strong></div>
                                    <div><span>{{ $isEnglish ? 'Active goals' : 'Aktive Ziele' }}</span><i><b style="width:{{ $averageContractProgress }}%"></b></i><strong>{{ $activeContracts->count() }}</strong></div>
                                    <div><span>{{ $isEnglish ? 'Team profile' : 'Teamprofil' }}</span><i><b style="width:{{ $profileCompletion }}%"></b></i><strong>{{ $profileCompletion }}%</strong></div>
                                </div>
                                <small class="team-level-meta">{{ number_format($teamDetail['teamXp'], 0, ',', '.') }} Team-XP</small>
                            </article>
                        </section>

                        <section class="team-latest-update">
                            <header><div><span>{{ $isEnglish ? 'LATEST TEAM UPDATE' : 'LETZTES TEAM-UPDATE' }}</span><h3>{{ $latestPost ? \Illuminate\Support\Str::limit(strip_tags((string) $latestPost->body), 58) : ($isEnglish ? 'No team posts yet' : 'Noch keine Team-Beiträge') }}</h3></div><button data-open-team-tab="posts" type="button">{{ $isEnglish ? 'All posts' : 'Alle Beiträge' }}</button></header>
                            @if($latestPost)
                                <div class="team-post-author"><img alt="{{ $latestPost->user?->name }}" src="{{ $latestPost->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"><div><strong>{{ $latestPost->user?->name ?: $latestPost->user?->username }}</strong><span>{{ '@'.($latestPost->user?->username ?: 'hunter') }} · {{ $latestPost->created_at?->diffForHumans() }}</span></div><em>{{ $isEnglish ? 'Team post' : 'Team-Beitrag' }}</em></div>
                                <div class="team-latest-post-body">{!! \App\Support\FeedTextRenderer::render($latestPost->body) !!}</div>
                                <footer><div><a href="{{ $latestPost->permalink() }}"><svg><use href="#i-heart"></use></svg>{{ (int) $latestPost->reactions_count }}</a><a href="{{ $latestPost->permalink() }}?comments=1"><svg><use href="#i-comment"></use></svg>{{ (int) $latestPost->comments_count }}</a></div><a href="{{ $latestPost->permalink() }}"><svg><use href="#i-arrow"></use></svg>{{ $isEnglish ? 'Open post' : 'Beitrag öffnen' }}</a></footer>
                            @else
                                <p class="team-real-empty">{{ $isEnglish ? 'The first team post will appear here.' : 'Der erste Team-Beitrag erscheint später an dieser Stelle.' }}</p>
                            @endif
                        </section>

                        <section class="team-overview-bottom">
                            <article class="team-activity-card">
                                <header><div><span>{{ $isEnglish ? 'RECENT ACTIVITY' : 'LETZTE AKTIVITÄT' }}</span><h3>{{ $isEnglish ? 'What happened in the team' : 'Im Team passiert' }}</h3></div><small>{{ $isEnglish ? 'Live data' : 'Echte Daten' }}</small></header>
                                <div class="team-activity-list">
                                    @forelse($teamActivities as $activity)
                                        <article>
                                            @if($activity['url'])
                                                <a href="{{ $activity['url'] }}"><img alt="" src="{{ $activity['avatar'] }}"></a>
                                            @else
                                                <img alt="" src="{{ $activity['avatar'] }}">
                                            @endif
                                            <div><strong>{{ $activity['title'] }}</strong><small>{{ $activity['meta'] }}</small></div><span>{{ optional($activity['at'])->diffForHumans() }}</span>
                                        </article>
                                    @empty
                                        <p class="team-real-empty">{{ $isEnglish ? 'No team activity yet.' : 'Noch keine Team-Aktivität vorhanden.' }}</p>
                                    @endforelse
                                </div>
                            </article>

                            <article class="team-goals-card">
                                <header><div><span>{{ $isEnglish ? 'TEAM GOALS' : 'TEAMZIELE' }}</span><h3>{{ $isEnglish ? 'Active contracts' : 'Aktive Aufträge' }}</h3></div><strong>{{ $activeContracts->count() }}</strong></header>
                                <div>
                                    @forelse($activeContracts as $contract)
                                        @php
                                            $contractPercent = min(100, max(0, (int) round(((int) $contract->progress_value / max(1, (int) $contract->target_value)) * 100)));
                                        @endphp
                                        <span>{{ __($contract->name_key) }}</span><strong>{{ $contract->progress_value }} / {{ $contract->target_value }}</strong><i><b style="width:{{ $contractPercent }}%"></b></i>
                                    @empty
                                        <p class="team-real-empty">{{ $isEnglish ? 'No active team contracts.' : 'Keine aktiven Team-Aufträge.' }}</p>
                                    @endforelse
                                </div>
                            </article>
                        </section>
                    </section>

                    <section class="team-panel" data-team-panel="posts" hidden>
                        @if($isMember)
                            <form class="team-composer" method="post" action="{{ route('teams.feed.store', $team) }}" enctype="multipart/form-data">
                                @csrf
                                <img alt="{{ auth()->user()->name }}" src="{{ auth()->user()->avatarUrl() }}">
                                <div><span>{{ $isEnglish ? 'TEAM POST' : 'TEAM-BEITRAG' }}</span><textarea name="body" maxlength="5000" placeholder="{{ $isEnglish ? 'What is new in '.$teamName.'?' : 'Was gibt es Neues bei '.$teamName.'?' }}" rows="3"></textarea></div>
                                <footer><div><label class="team-media-picker"><svg><use href="#i-image"></use></svg>{{ $isEnglish ? 'Media' : 'Medien' }}<input type="file" name="media[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" multiple></label></div><button type="submit">{{ $isEnglish ? 'Post' : 'Posten' }}</button></footer>
                            </form>
                        @endif

                        <div class="post-list team-feed-list" id="teamFeedList">
                            @forelse($teamPosts as $post)
                                @php
                                    $postAuthor = $post->user;
                                    $postMedia = $post->media->first();
                                    $postPoll = $post->poll;
                                    $pollTotal = $postPoll?->votes?->count() ?? 0;
                                @endphp
                                <article class="social-post team-real-post">
                                    <header class="post-head"><img alt="{{ $postAuthor?->name }}" src="{{ $postAuthor?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"><div class="post-author"><strong>{{ $postAuthor?->name ?: $postAuthor?->username }}</strong><span>{{ '@'.($postAuthor?->username ?: 'hunter') }} · {{ $post->created_at?->diffForHumans() }}</span></div><span class="post-badge discussion">Team</span><a class="post-more" href="{{ $post->permalink() }}" aria-label="{{ $isEnglish ? 'Open post' : 'Beitrag öffnen' }}"><svg><use href="#i-arrow"></use></svg></a></header>
                                    <div class="post-body">
                                        @if($post->body)
                                            <div class="team-real-post-copy">{!! \App\Support\FeedTextRenderer::render($post->body) !!}</div>
                                        @endif
                                        @if($postMedia)
                                            <div class="team-real-post-media">
                                                @if($postMedia->isImage())
                                                    <img src="{{ $postMedia->url() }}" alt="{{ $postMedia->original_name }}">
                                                @elseif($postMedia->isVideo())
                                                    <video controls preload="metadata"><source src="{{ $postMedia->url() }}" type="{{ $postMedia->mime_type }}"></video>
                                                @endif
                                            </div>
                                        @endif
                                        @if($postPoll)
                                            <div class="poll">
                                                @foreach($postPoll->options as $option)
                                                    @php
                                                        $optionVotes = $option->votes->count();
                                                    @endphp
                                                    <a href="{{ $post->permalink() }}"><span>{{ $option->body }}</span><b>{{ $pollTotal > 0 ? round(($optionVotes / $pollTotal) * 100) : 0 }}%</b></a>
                                                @endforeach
                                                <small>{{ $pollTotal }} {{ $isEnglish ? 'votes' : 'Stimmen' }}</small>
                                            </div>
                                        @endif
                                    </div>
                                    <footer class="post-actions"><a href="{{ $post->permalink() }}"><svg><use href="#i-heart"></use></svg><span>{{ (int) $post->reactions_count }}</span></a><a href="{{ $post->permalink() }}?comments=1"><svg><use href="#i-comment"></use></svg><span>{{ (int) $post->comments_count }}</span></a><a href="{{ $post->permalink() }}"><svg><use href="#i-share"></use></svg><span>{{ (int) $post->shares_count }}</span></a><a href="{{ $post->permalink() }}"><svg><use href="#i-bookmark"></use></svg></a></footer>
                                </article>
                            @empty
                                <article class="team-real-empty-card"><strong>{{ $isEnglish ? 'No team posts yet' : 'Noch keine Team-Beiträge' }}</strong><span>{{ $isEnglish ? 'The first post from a team member will appear here.' : 'Der erste Beitrag eines Teammitglieds erscheint hier.' }}</span></article>
                            @endforelse
                        </div>
                    </section>

                    <section class="team-panel" data-team-panel="info" hidden>
                        <div class="team-info-intro"><div><span>{{ $isEnglish ? 'ABOUT THE TEAM' : 'ÜBER DAS TEAM' }}</span><h3>{{ $teamTagline }}</h3></div><p>{{ $teamDescription }}</p></div>
                        <section class="team-info-layout">
                            <article class="team-description-card"><span>{{ $isEnglish ? 'DESCRIPTION' : 'BESCHREIBUNG' }}</span><h3>{{ $teamName }}</h3><p>{!! nl2br(e($teamDescription)) !!}</p><div class="team-tag-list">@foreach(collect([$team->playstyle, $team->platform, $team->region, $team->language])->filter() as $tag)<span>{{ $tag }}</span>@endforeach</div></article>
                            <article class="team-data-card"><span>{{ $isEnglish ? 'TEAM DATA' : 'TEAMDATEN' }}</span><dl><div><dt>{{ $isEnglish ? 'Founded' : 'Gründung' }}</dt><dd>{{ $team->created_at?->translatedFormat('d. F Y') ?: '—' }}</dd></div><div><dt>{{ $isEnglish ? 'Visibility' : 'Sichtbarkeit' }}</dt><dd>{{ $team->visibilityLabel() }}</dd></div><div><dt>Recruiting</dt><dd>{{ $team->recruitmentLabel() }}</dd></div><div><dt>{{ $isEnglish ? 'Platform' : 'Plattform' }}</dt><dd>{{ $team->platform ?: '—' }}</dd></div><div><dt>Region</dt><dd>{{ $team->region ?: '—' }}</dd></div><div><dt>{{ $isEnglish ? 'Language' : 'Sprache' }}</dt><dd>{{ $team->language ?: '—' }}</dd></div></dl></article>
                        </section>
                        <section class="team-organizer-card"><header><div><span>{{ $isEnglish ? 'ORGANIZATION' : 'ORGANISATION' }}</span><h3>{{ $isEnglish ? 'Team lead' : 'Teamleitung' }}</h3></div><button data-open-team-tab="members" type="button">{{ $isEnglish ? 'All members' : 'Alle Mitglieder' }}</button></header><div><img alt="{{ $team->owner?->name }}" src="{{ $team->owner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"><div><strong>{{ $team->owner?->name ?: $team->owner?->username }}</strong><span>{{ $isEnglish ? 'Owner · Team founder' : 'Owner · Teamgründer' }}</span><small>{{ '@'.($team->owner?->username ?: 'hunter') }}</small></div>@if($team->owner)<a href="{{ route('profile.public', $team->owner) }}"><svg><use href="#i-arrow"></use></svg></a>@endif</div></section>
                    </section>

                    <section class="team-panel" data-team-panel="members" hidden>
                        <div class="team-members-intro"><div><span>{{ $activeMembers->count() }} {{ $isEnglish ? 'ACTIVE MEMBERS' : 'AKTIVE MITGLIEDER' }}</span><h3>{{ $isEnglish ? 'Team members' : 'Teammitglieder' }}</h3></div><div><span class="ready"><i></i>{{ $team->recruitmentLabel() }}</span><button data-copy-team-url="{{ $teamInviteUrl }}" type="button">{{ $isEnglish ? 'Share invitation' : 'Einladung teilen' }}</button></div></div>
                        <div class="team-member-grid">
                            @forelse($activeMembers as $member)
                                @php
                                    $user = $member->user;
                                    $profile = $user?->profile;
                                    $online = $onlineMembers->contains('user_id', $member->user_id);
                                @endphp
                                <article>
                                    <header><img alt="{{ $user?->name }}" src="{{ $user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"><span>{{ \Illuminate\Support\Str::upper($member->roleLabel()) }}</span>@if($user)<a href="{{ route('profile.public', $user) }}"><svg><use href="#i-arrow"></use></svg></a>@endif</header>
                                    <h3>{{ $user?->name ?: $user?->username }}</h3>
                                    <p>{{ '@'.($user?->username ?: 'hunter') }} · {{ $online ? 'online' : ($user?->last_seen_at?->diffForHumans() ?: 'offline') }}</p>
                                    <div class="member-stats"><span><strong>{{ max(1, (int) ($user?->level ?? 1)) }}</strong>Level</span><span><strong>{{ (int) ($user?->published_posts_count ?? 0) }}</strong>Posts</span><span><strong>{{ number_format((int) ($user?->crownWallet?->balance ?? 0), 0, ',', '.') }}</strong>Rocks</span></div>
                                    <div class="member-tags">@foreach(collect([$profile?->platform, $profile?->region, $profile?->playstyle, $profile?->language])->filter()->take(4) as $tag)<span>{{ $tag }}</span>@endforeach</div>
                                    <footer><i class="{{ $online ? 'online' : '' }}"></i><span>{{ $online ? 'Online' : $member->roleLabel() }}</span></footer>
                                </article>
                            @empty
                                <article class="team-real-empty-card"><strong>{{ $isEnglish ? 'No active members' : 'Keine aktiven Mitglieder' }}</strong></article>
                            @endforelse
                        </div>
                        <article class="team-roster-complete"><span><svg><use href="#i-users"></use></svg></span><div><strong>{{ $team->recruitmentLabel() }}</strong><small>{{ $team->recruitment_status === 'open' ? ($isEnglish ? 'New hunters can request to join this team.' : 'Neue Hunter können einen Beitritt anfragen.') : ($isEnglish ? 'The team is currently not accepting new requests.' : 'Das Team nimmt derzeit keine neuen Anfragen an.') }}</small></div>@if($canManage)<a href="{{ route('teams.edit', $team) }}">{{ $isEnglish ? 'Manage roster' : 'Roster verwalten' }}</a>@endif</article>
                    </section>

                    @if($canManage)
                        <section class="team-panel" data-team-panel="requests" hidden>
                            <div class="team-request-intro"><div><span>{{ $isEnglish ? 'JOIN REQUESTS' : 'BEITRITTSANFRAGEN' }}</span><h3>{{ $pendingMembers->count() }} {{ $isEnglish ? ($pendingMembers->count() === 1 ? 'open request' : 'open requests') : ($pendingMembers->count() === 1 ? 'offene Anfrage' : 'offene Anfragen') }}</h3><p>{{ $isEnglish ? 'Review profile and message before accepting a request.' : 'Prüfe Profil und Nachricht, bevor du eine Anfrage annimmst.' }}</p></div><span class="request-counter">{{ $pendingMembers->count() }} {{ $isEnglish ? 'open' : 'offen' }}</span></div>
                            <div class="team-request-list" id="teamRequestList">
                                @forelse($pendingMembers as $requestMember)
                                    @php
                                        $requestUser = $requestMember->user;
                                    @endphp
                                    <article class="team-request-item"><img alt="{{ $requestUser?->name }}" src="{{ $requestUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"><div class="team-request-copy"><span>{{ $isEnglish ? 'JOIN REQUEST' : 'BEITRITTSANFRAGE' }}</span><h3>{{ $requestUser?->name ?: $requestUser?->username }}</h3><small>{{ '@'.($requestUser?->username ?: 'hunter') }} · {{ collect([$requestUser?->profile?->platform, $requestUser?->profile?->region, $requestUser?->profile?->playstyle])->filter()->implode(' · ') }}</small><p>{{ $requestMember->message ?: ($isEnglish ? 'No message was included.' : 'Es wurde keine Nachricht mitgesendet.') }}</p><div><span>Level {{ max(1, (int) ($requestUser?->level ?? 1)) }}</span><span>{{ $requestMember->created_at?->diffForHumans() }}</span></div></div><div class="team-request-actions">@if($requestUser)<a href="{{ route('profile.public', $requestUser) }}">{{ $isEnglish ? 'View profile' : 'Profil ansehen' }} <svg><use href="#i-arrow"></use></svg></a>@endif<form method="post" action="{{ route('teams.requests.accept', [$team, $requestMember]) }}">@csrf<button class="accept-request" type="submit">{{ $isEnglish ? 'Accept' : 'Annehmen' }}</button></form><form method="post" action="{{ route('teams.requests.reject', [$team, $requestMember]) }}">@csrf<button class="reject-request" type="submit">{{ $isEnglish ? 'Reject' : 'Ablehnen' }}</button></form></div></article>
                                @empty
                                    <article class="team-request-empty"><span><svg><use href="#i-check"></use></svg></span><div><strong>{{ $isEnglish ? 'No open requests' : 'Keine offenen Anfragen' }}</strong><small>{{ $isEnglish ? 'New requests will appear here automatically.' : 'Neue Anfragen erscheinen automatisch in diesem Bereich.' }}</small></div></article>
                                @endforelse
                            </div>
                        </section>
                    @endif
                </div>
            </section>

            <aside class="team-right-sidebar">
                <article class="team-status-card">
                    <header><div><span>{{ $isEnglish ? 'TEAM PROFILE' : 'TEAMPROFIL' }}</span><h2>{{ $isEnglish ? 'Completeness' : 'Vollständigkeit' }}</h2></div>@if($canManage)<a href="{{ route('teams.edit', $team) }}"><svg><use href="#i-arrow"></use></svg></a>@endif</header>
                    <div class="team-status-ring" style="--team-status:{{ $profileCompletion }}"><div><strong>{{ $profileCompletion }}%</strong><span>{{ $isEnglish ? 'Complete' : 'Vollständig' }}</span></div></div>
                    <div class="team-status-list">
                        <article><i class="{{ $activeMembers->isNotEmpty() ? 'good' : '' }}"></i><div><strong>Roster</strong><small>{{ $activeMembers->count() }} {{ $isEnglish ? 'active members' : 'aktive Mitglieder' }}</small></div><span>{{ $activeMembers->count() }}</span></article>
                        <article><i class="{{ $team->recruitment_status === 'open' ? 'good' : '' }}"></i><div><strong>Recruiting</strong><small>{{ $team->recruitmentLabel() }}</small></div><span>{{ $team->recruitment_status === 'open' ? ($isEnglish ? 'Open' : 'Offen') : ($isEnglish ? 'Closed' : 'Zu') }}</span></article>
                        <article><i class="{{ $upcomingSession ? 'good' : '' }}"></i><div><strong>{{ $isEnglish ? 'Next session' : 'Nächste Session' }}</strong><small>{{ $upcomingSession ? $upcomingSession->starts_at->translatedFormat('D · d.m. · H:i') : ($isEnglish ? 'Not planned' : 'Nicht geplant') }}</small></div><span>{{ $upcomingSession ? ($isEnglish ? 'Planned' : 'Geplant') : '—' }}</span></article>
                        <article><i class="{{ $activeContracts->isNotEmpty() ? 'good' : '' }}"></i><div><strong>{{ $isEnglish ? 'Team goals' : 'Team-Aufträge' }}</strong><small>{{ $activeContracts->count() }} {{ $isEnglish ? 'active' : 'aktiv' }}</small></div><span>{{ $averageContractProgress }}%</span></article>
                    </div>
                </article>

                <article class="team-online-card">
                    <header><div><span>{{ $isEnglish ? 'MEMBERS' : 'MITGLIEDER' }}</span><h2>{{ $isEnglish ? 'Online now' : 'Jetzt online' }}</h2></div><strong>{{ $onlineMembers->count() }}</strong></header>
                    <div>
                        @forelse($activeMembers->take(5) as $member)
                            @php
                                $memberOnline = $onlineMembers->contains('user_id', $member->user_id);
                            @endphp
                            <article class="{{ $memberOnline ? '' : 'offline' }}"><img alt="{{ $member->user?->name }}" src="{{ $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"><div><strong>{{ $member->user?->name ?: $member->user?->username }}</strong><small>{{ $member->roleLabel() }} · {{ $memberOnline ? 'online' : ($member->user?->last_seen_at?->diffForHumans() ?: 'offline') }}</small></div><i></i></article>
                        @empty
                            <p class="team-real-empty">{{ $isEnglish ? 'No members yet.' : 'Noch keine Mitglieder.' }}</p>
                        @endforelse
                    </div>
                    <button data-open-team-tab="members" type="button">{{ $isEnglish ? 'All members' : 'Alle Mitglieder' }}</button>
                </article>
            </aside>
        </section>
    </div>
</section>
