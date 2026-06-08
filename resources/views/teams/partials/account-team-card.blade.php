@php
    $membersCount = $team->members_count ?? $team->activeMembers()->count();
    $pendingCount = $team->pending_count ?? $team->pendingMembers()->count();
@endphp

<div class="user-preview small fixed-height-medium hh-account-team-preview">
    <figure class="user-preview-cover liquid">
        <img src="{{ $team->coverUrl() }}" alt="{{ $team->name }}">
    </figure>

    <div class="user-preview-info">
        <div class="user-short-description small">
            <a class="user-short-description-avatar user-avatar no-stats" href="{{ route('teams.show', $team) }}">
                <div class="user-avatar-border"><div class="hexagon-100-108"></div></div>
                <div class="user-avatar-content">
                    <div class="hexagon-image-84-92" data-src="{{ $team->avatarUrl() }}" style="background-image:url('{{ $team->avatarUrl() }}');"></div>
                </div>
            </a>

            <p class="user-short-description-title small"><a href="{{ route('teams.show', $team) }}">{{ $team->name }}</a></p>
            <p class="user-short-description-text regular">{{ $team->tagline ?: __('ui.team_default_tagline') }}</p>
        </div>

        <div class="user-stats">
            <div class="user-stat">
                <p class="user-stat-title">{{ $membersCount }}</p>
                <p class="user-stat-text">{{ __('ui.team_members') }}</p>
            </div>
            <div class="user-stat">
                <p class="user-stat-title">{{ $pendingCount }}</p>
                <p class="user-stat-text">{{ __('ui.team_requests_short') }}</p>
            </div>
            <div class="user-stat">
                <p class="user-stat-title">{{ strtoupper((string) ($team->platform ?: '—')) }}</p>
                <p class="user-stat-text">{{ __('ui.platform') }}</p>
            </div>
        </div>

        <a class="button white full" href="{{ route('teams.edit', $team) }}">{{ __('ui.manage_team') }}</a>
    </div>
</div>
