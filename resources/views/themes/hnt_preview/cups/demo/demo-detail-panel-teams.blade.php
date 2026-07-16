@php
    $isEnglish = app()->getLocale() === 'en';
    $rankedTeams = collect($leaderboard ?? [])->values();
@endphp

<section class="cup-tab-panel" data-cup-panel="teams" hidden tabindex="0">
    <div class="cup-leaderboard">
        <div class="cup-leaderboard-head">
            <span>{{ $isEnglish ? 'Rank' : 'Rang' }}</span>
            <span>Team</span>
            <span>Kills</span>
            <span>{{ $isEnglish ? 'Trophies' : 'Trophäen' }}</span>
            <span>{{ $isEnglish ? 'Points' : 'Punkte' }}</span>
        </div>

        @forelse($rankedTeams as $rankIndex => $rankedTeam)
            @php
                $rankedMembers = $rankedTeam->members->where('status', 'active')->values();
                $fallbackOwner = $rankedTeam->owner;
                $isViewerTeam = $viewerTeam && (int) $viewerTeam->id === (int) $rankedTeam->id;
            @endphp
            <article @class(['is-viewer-team' => $isViewerTeam])>
                <b>{{ $rankIndex + 1 }}</b>
                <div>
                    <span class="team-avatars">
                        @forelse($rankedMembers->take(3) as $rankedMember)
                            @php($rankedUser = $rankedMember->user)
                            <img alt="{{ $rankedUser?->username ?: $rankedUser?->name ?: 'Hunter' }}" src="{{ $rankedUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
                        @empty
                            <img alt="{{ $fallbackOwner?->username ?: $fallbackOwner?->name ?: 'Hunter' }}" src="{{ $fallbackOwner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
                        @endforelse
                    </span>
                    <strong>{{ $rankedTeam->displayName() }}</strong>
                </div>
                <span>{{ (int) $rankedTeam->kills_total }}</span>
                <span>{{ (int) $rankedTeam->bounty_tokens_total }}</span>
                <strong>{{ (int) $rankedTeam->points_total }}</strong>
            </article>
        @empty
            <div class="cup-leaderboard-empty">
                {{ $isEnglish ? 'No confirmed teams are ranked yet.' : 'Noch ist kein bestätigtes Team im Leaderboard.' }}
            </div>
        @endforelse
    </div>
</section>
