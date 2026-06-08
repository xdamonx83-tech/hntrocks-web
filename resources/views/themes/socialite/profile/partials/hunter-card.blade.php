@php
    $card = $hunterCard ?? [];
    $cardStats = $card['stats'] ?? [];
    $cardBadges = $latestBadges->take(3);
    $cardRole = $card['role_label'] ?: __('ui.hunter_card_default_role');
    $bestPlacement = $card['best_placement'] ?? null;
    $bestMoment = $card['best_moment'] ?? null;
@endphp

<section id="profile-hunter-card" class="hnt-hunter-card" aria-label="{{ __('ui.hunter_card_title') }}">
    <div class="hnt-hunter-card__frame">
        <div class="hnt-hunter-card__ornament hnt-hunter-card__ornament--left"></div>
        <div class="hnt-hunter-card__ornament hnt-hunter-card__ornament--right"></div>

        <div class="hnt-hunter-card__topline">
            <span>{{ __('ui.hunter_card_brand') }}</span>
            <strong>{{ $card['tier_roman'] ?? 'I' }}</strong>
            <span>{{ __('ui.hunter_card_tier') }}</span>
        </div>

        <div class="hnt-hunter-card__avatar-wrap">
            <div class="hnt-hunter-card__halo"></div>
            <div class="hnt-hunter-card__avatar">
                <img src="{{ $profileUser->avatarUrl() }}" alt="{{ $profileUser->name }}">
            </div>
        </div>

        <div class="hnt-hunter-card__identity">
            <p>{{ __('ui.hunter_card_title') }}</p>
            <h3>{{ $profileUser->name }}</h3>
            <span>{{ '@' . $profileUser->username }} · {{ $cardRole }}</span>
        </div>

        <div class="hnt-hunter-card__level">
            <div>
                <strong>{{ __('ui.level') }} {{ (int) ($card['level'] ?? 1) }}</strong>
                <span>{{ number_format((int) ($card['xp_total'] ?? 0), 0, ',', '.') }} XP</span>
            </div>
            <div class="hnt-hunter-card__bar" aria-hidden="true">
                <i style="width: {{ (int) ($card['level_progress'] ?? 0) }}%"></i>
            </div>
        </div>

        <div class="hnt-hunter-card__stats">
            <div>
                <strong>{{ (int) ($cardStats['cup_points'] ?? 0) }}</strong>
                <span>{{ __('ui.hunter_card_stat_cup_points') }}</span>
            </div>
            <div>
                <strong>{{ (int) ($cardStats['bounties'] ?? 0) }}</strong>
                <span>{{ __('ui.hunter_card_stat_bounties') }}</span>
            </div>
            <div>
                <strong>{{ (int) ($cardStats['kills'] ?? 0) }}</strong>
                <span>{{ __('ui.hunter_card_stat_kills') }}</span>
            </div>
            <div>
                <strong>{{ (int) ($cardStats['accepted_loadout_runs'] ?? 0) }}</strong>
                <span>{{ __('ui.hunter_card_stat_challenges') }}</span>
            </div>
        </div>

        <div class="hnt-hunter-card__badges">
            @forelse($cardBadges as $badge)
                <a href="{{ $profileSectionUrl('badges') }}" title="{{ $badge->name }}">
                    @if($badge->iconUrl())
                        <img src="{{ $badge->iconUrl() }}" alt="{{ $badge->name }}">
                    @else
                        <span>{{ $badge->icon ?: '◆' }}</span>
                    @endif
                </a>
            @empty
                <span class="hnt-hunter-card__empty-badge">◆</span>
                <span class="hnt-hunter-card__empty-badge">◆</span>
                <span class="hnt-hunter-card__empty-badge">◆</span>
            @endforelse
        </div>

        <div class="hnt-hunter-card__footer">
            @if($bestPlacement)
                <a href="{{ $bestPlacement['url'] }}">
                    {{ __('ui.hunter_card_best_placement', ['rank' => (int) ($bestPlacement['rank'] ?? 0)]) }}
                </a>
            @elseif($bestMoment)
                <a href="{{ route('moments.show', $bestMoment) }}">
                    {{ __('ui.hunter_card_best_moment') }}
                </a>
            @else
                <span>{{ __('ui.hunter_card_empty_footer') }}</span>
            @endif
        </div>
    </div>
</section>
