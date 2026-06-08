@php
    $cupUrl = route('cups.show', $cup);
    $soloCup = $cup->isSoloLeaderboard();
    $teamCount = (int) ($cup->active_teams_count ?? $cup->activeTeams()->count());
    $pendingCount = (int) ($cup->pending_submissions_count ?? $cup->pendingSubmissions()->count());
    $maxTeams = $cup->participantLimit();
    $slotsLabel = $maxTeams ? $teamCount.'/'.$maxTeams : __('ui.cup_slots_open');
    $isPrivate = $cup->visibility === 'private';
    $owner = $cup->owner;
    $ownerUrl = $owner ? route('profile.public', $owner) : $cupUrl;
    $ownerAvatar = $owner?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg');
    $startLabel = $cup->starts_at ? $cup->starts_at->format('d.m.Y H:i') : __('ui.cup_start').' '.__('ui.cup_open');
    $metaTags = array_filter([$cup->platform, $cup->region, $cup->language, $startLabel]);
@endphp

<div class="user-preview hh-cup-user-preview">
    <figure class="user-preview-cover liquid">
        <img src="{{ $cup->coverUrl() }}" alt="{{ __('ui.cup_cover_alt', ['title' => $cup->title]) }}">
    </figure>

    <div class="user-preview-info">
        <div class="tag-sticker {{ $isPrivate ? 'hh-cup-visibility-private' : 'hh-cup-visibility-public' }}" title="{{ $cup->visibilityLabel() }}">
            <svg class="tag-sticker-icon {{ $isPrivate ? 'icon-private' : 'icon-public' }}">
                <use xlink:href="{{ $isPrivate ? '#svg-private' : '#svg-public' }}"></use>
            </svg>
        </div>

        <div class="user-short-description">
            <a class="user-short-description-avatar user-avatar medium no-stats" href="{{ $cupUrl }}">
                <div class="user-avatar-border"><div class="hexagon-120-130"></div></div>
                <div class="user-avatar-content"><div class="hexagon-image-100-110" data-src="{{ $cup->coverUrl() }}"></div></div>
            </a>

            <p class="user-short-description-title"><a href="{{ $cupUrl }}">{{ $cup->title }}</a></p>
            <p class="user-short-description-text">{{ $cup->displaySummary() }}</p>
        </div>

        <div class="user-stats hh-cup-card-stats">
            <div class="user-stat"><p class="user-stat-title">{{ $teamCount }}</p><p class="user-stat-text">{{ $soloCup ? __('ui.cup_card_participants') : __('ui.cup_card_teams') }}</p></div>
            <div class="user-stat"><p class="user-stat-title">{{ $pendingCount }}</p><p class="user-stat-text">{{ __('ui.cup_card_reviews') }}</p></div>
            <div class="user-stat"><p class="user-stat-title">{{ $soloCup ? __('ui.cup_card_solo_mode') : $cup->team_size.'v'.$cup->team_size }}</p><p class="user-stat-text">{{ __('ui.cup_card_mode') }}</p></div>
        </div>

        <div class="hh-cup-card-meta">
            @foreach ($metaTags as $tag)
                <span>{{ $tag }}</span>
            @endforeach
            <span>{{ $cup->statusLabel() }}</span>
            <span>{{ $slotsLabel }} {{ $soloCup ? __('ui.cup_slot_participants') : __('ui.cup_slots') }}</span>
        </div>

        <div class="user-avatar-list medium reverse centered hh-cup-avatar-list">
            <a class="user-avatar smaller no-stats" href="{{ $ownerUrl }}" title="{{ $owner?->name ?? __('ui.cup_host') }}">
                <div class="user-avatar-border"><div class="hexagon-34-36"></div></div>
                <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $ownerAvatar }}"></div></div>
            </a>
        </div>

        <p class="hh-cup-card-host">{{ __('ui.cup_host') }}: <a href="{{ $ownerUrl }}">{{ $owner?->name ?? __('ui.cup_unknown') }}</a></p>

        <div class="user-preview-actions hh-cup-preview-actions">
            <a class="button secondary full" href="{{ $cupUrl }}">
                <i class="button-icon hh-ph-action-icon ph ph-trophy" aria-hidden="true"></i>
                {{ __('ui.cup_view') }}
            </a>
        </div>
    </div>
</div>
