@php
    $teamUrl = route('teams.show', $team);
    $memberCount = (int) ($team->members_count ?? $team->activeMembers()->count());
    $postsCount = (int) ($team->posts_count ?? 0);
    $isPrivate = $team->visibility === 'private';
    $isRecruiting = $team->recruitment_status === 'open';
    $activeMembers = $team->relationLoaded('activeMembers') ? $team->activeMembers->take(6) : collect();
    $visibleMembers = $activeMembers->take(5);
    $remainingMembers = max(0, $memberCount - $visibleMembers->count());
    $canReportTeam = ! $team->canManage(auth()->user());
@endphp

<div class="user-preview hh-team-user-preview">
    <figure class="user-preview-cover liquid">
        <img src="{{ $team->coverUrl() }}" alt="Cover von {{ $team->name }}">
    </figure>

    <div class="user-preview-info">
        <div class="tag-sticker {{ $isPrivate ? 'hh-team-visibility-private' : 'hh-team-visibility-public' }}" title="{{ $team->visibilityLabel() }}">
            <svg class="tag-sticker-icon {{ $isPrivate ? 'icon-private' : 'icon-public' }}">
                <use xlink:href="{{ $isPrivate ? '#svg-private' : '#svg-public' }}"></use>
            </svg>
        </div>

        <div class="user-short-description">
            <a class="user-short-description-avatar user-avatar medium no-stats" href="{{ $teamUrl }}">
                <div class="user-avatar-border"><div class="hexagon-120-130"></div></div>
                <div class="user-avatar-content"><div class="hexagon-image-100-110" data-src="{{ $team->avatarUrl() }}"></div></div>
            </a>

            <p class="user-short-description-title"><a href="{{ $teamUrl }}">{{ $team->name }}</a></p>
            <p class="user-short-description-text">{{ $team->tagline ?: __('ui.team_default_tagline') }}</p>
        </div>

        <div class="user-stats hh-team-card-stats">
            <div class="user-stat"><p class="user-stat-title">{{ $memberCount }}</p><p class="user-stat-text">{{ __('ui.team_members') }}</p></div>
            <div class="user-stat"><p class="user-stat-title">{{ $postsCount }}</p><p class="user-stat-text">{{ __('ui.posts') }}</p></div>
            <div class="user-stat"><p class="user-stat-title">{{ $team->recruitmentLabel() }}</p><p class="user-stat-text">{{ __('ui.recruiting') }}</p></div>
        </div>

        <div class="hh-team-card-meta">
            @foreach (array_filter([$team->platform, $team->playstyle, $team->region, $team->language]) as $tag)
                <span>{{ $tag }}</span>
            @endforeach
        </div>

        <div class="user-avatar-list medium reverse centered hh-team-avatar-list">
            @forelse ($visibleMembers as $member)
                @php($memberUser = $member->user)
                <a class="user-avatar smaller no-stats" href="{{ $memberUser ? route('profile.public', $memberUser) : $teamUrl }}" title="{{ $memberUser?->name }}">
                    <div class="user-avatar-border"><div class="hexagon-34-36"></div></div>
                    <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $memberUser?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg') }}"></div></div>
                </a>
            @empty
                <div class="user-avatar smaller no-stats hh-team-empty-avatar">
                    <div class="user-avatar-border"><div class="hexagon-34-36"></div></div>
                    <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ asset('assets/vikinger/img/default-avatar.svg') }}"></div></div>
                </div>
            @endforelse

            @if ($remainingMembers > 0)
                <a class="user-avatar smaller no-stats" href="{{ route('teams.members', $team) }}" title="{{ __('ui.all_members') }}">
                    <div class="user-avatar-border"><div class="hexagon-34-36"></div></div>
                    <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $team->avatarUrl() }}"></div></div>
                    <div class="user-avatar-overlay"><div class="hexagon-overlay-30-32"></div></div>
                    <div class="user-avatar-overlay-content"><p class="user-avatar-overlay-content-text">+{{ $remainingMembers }}</p></div>
                </a>
            @endif
        </div>

        <div class="user-preview-actions hh-team-preview-actions hh-report-card-actions">
            <a class="button secondary full" href="{{ $teamUrl }}">
                <i class="button-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                {{ __('ui.view_team') }}
            </a>
            @if ($canReportTeam)
                <button class="button white hh-report-card-action text-tooltip-tft" type="button" title="{{ __('ui.report_team') }}" data-title="{{ __('ui.report_team') }}" aria-label="{{ __('ui.report_team') }}" data-hh-report-open data-hh-report-type="team" data-hh-report-id="{{ $team->id }}" data-hh-report-label="Team: {{ $team->name }}">
                    <i class="button-icon hh-ph-action-icon ph ph-warning-octagon" aria-hidden="true"></i>
                    <span class="hh-report-button-label">{{ __('ui.report') }}</span>
                </button>
            @endif
        </div>
    </div>
</div>
