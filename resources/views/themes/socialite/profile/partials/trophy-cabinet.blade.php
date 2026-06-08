@php
    $trophyStats = $trophyCabinet['stats'] ?? [];
    $trophyPlacements = collect($trophyCabinet['cup_placements'] ?? []);
    $trophySpotlights = collect($trophyCabinet['moment_spotlights'] ?? []);
    $bestSubmission = $trophyCabinet['best_submission'] ?? null;
    $bestMoment = $trophyCabinet['best_moment'] ?? null;
@endphp

<div class="space-y-5">
    <section class="hnt-profile-trophy-hero">
        <div class="hnt-profile-trophy-hero-copy">
            <p class="hnt-profile-trophy-pretitle">{{ __('ui.profile_trophies') }}</p>
            <h2>{{ __('ui.profile_trophy_cabinet') }}</h2>
            <p>{{ __('ui.profile_trophy_cabinet_subtitle') }}</p>
        </div>
        <div class="hnt-profile-trophy-stat-grid">
            <div><strong>{{ (int) ($trophyStats['cup_points'] ?? 0) }}</strong><span>{{ __('ui.profile_trophy_points') }}</span></div>
            <div><strong>{{ (int) ($trophyStats['cup_bounty_tokens'] ?? 0) }}</strong><span>{{ __('ui.profile_trophy_bounties') }}</span></div>
            <div><strong>{{ (int) ($trophyStats['cup_kills'] ?? 0) }}</strong><span>{{ __('ui.profile_trophy_kills') }}</span></div>
            <div><strong>{{ (int) ($trophyStats['badges'] ?? 0) }}</strong><span>{{ __('ui.gamification_badges') }}</span></div>
        </div>
    </section>

    <section class="hnt-profile-trophy-panel">
        <div class="hnt-profile-trophy-panel-head">
            <div>
                <p>{{ __('ui.profile_trophy_cups_pretitle') }}</p>
                <h3>{{ __('ui.profile_trophy_cup_placements') }}</h3>
            </div>
            @if($bestSubmission)
                <span>{{ __('ui.profile_trophy_best_round') }}: {{ (int) $bestSubmission->points }} {{ __('ui.profile_trophy_points') }}</span>
            @endif
        </div>

        @if($trophyPlacements->isNotEmpty())
            <div class="hnt-profile-trophy-placement-list">
                @foreach($trophyPlacements as $placement)
                    @php
                        $placementRank = (int) ($placement['rank'] ?? 0);
                        $placementRankTone = $placementRank >= 1 && $placementRank <= 3 ? (string) $placementRank : 'default';
                    @endphp
                    <a href="{{ $placement['url'] }}" class="hnt-profile-trophy-placement">
                        <span class="hnt-profile-trophy-rank hnt-profile-trophy-rank-{{ $placementRankTone }}">#{{ $placementRank }}</span>
                        <span class="min-w-0 flex-1">
                            <strong>{{ $placement['cup']->title }}</strong>
                            <small>{{ $placement['points'] }} {{ __('ui.profile_trophy_points') }} · {{ $placement['bounty_tokens'] }} {{ __('ui.profile_trophy_bounties') }} · {{ $placement['kills'] }} {{ __('ui.profile_trophy_kills') }}</small>
                        </span>
                        <span class="hnt-profile-trophy-open">{{ __('ui.open') }}</span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="hnt-profile-trophy-empty">{{ __('ui.profile_trophy_no_cup_results') }}</div>
        @endif
    </section>

    <section class="grid lg:grid-cols-2 gap-5">
        <div class="hnt-profile-trophy-panel">
            <div class="hnt-profile-trophy-panel-head">
                <div>
                    <p>{{ __('ui.moments') }}</p>
                    <h3>{{ __('ui.profile_trophy_moment_spotlights') }}</h3>
                </div>
            </div>
            <div class="hnt-profile-trophy-moment-grid">
                @forelse($trophySpotlights as $spotlight)
                    <a href="{{ route('moments.show', $spotlight->moment) }}" class="hnt-profile-trophy-moment">
                        <img src="{{ $spotlight->moment->coverUrl() }}" alt="{{ $spotlight->title ?: __('ui.profile_trophy_moment_of_week') }}">
                        <div>
                            <strong>{{ $spotlight->title ?: __('ui.profile_trophy_moment_of_week') }}</strong>
                            <small>{{ $spotlight->dateLabel() }}</small>
                        </div>
                    </a>
                @empty
                    @if($bestMoment)
                        <a href="{{ route('moments.show', $bestMoment) }}" class="hnt-profile-trophy-moment">
                            <img src="{{ $bestMoment->coverUrl() }}" alt="{{ $bestMoment->caption ?: __('ui.profile_trophy_best_moment') }}">
                            <div>
                                <strong>{{ __('ui.profile_trophy_best_moment') }}</strong>
                                <small>{{ (int) $bestMoment->likes_count }} {{ __('ui.likes') }} · {{ (int) $bestMoment->views_count }} {{ __('ui.views') }}</small>
                            </div>
                        </a>
                    @else
                        <div class="hnt-profile-trophy-empty">{{ __('ui.profile_trophy_no_moments') }}</div>
                    @endif
                @endforelse
            </div>
        </div>

        <div class="hnt-profile-trophy-panel">
            <div class="hnt-profile-trophy-panel-head">
                <div>
                    <p>{{ __('ui.profile_trophy_achievements') }}</p>
                    <h3>{{ __('ui.profile_trophy_featured_badges') }}</h3>
                </div>
                <a href="{{ $profileSectionUrl('badges') }}">{{ __('ui.see_all') }}</a>
            </div>
            <div class="hnt-profile-trophy-badge-grid">
                @forelse($latestBadges->take(8) as $badge)
                    <a href="{{ $profileSectionUrl('badges') }}" title="{{ $badge->name }}">
                        <span>
                            @if($badge->iconUrl())
                                <img src="{{ $badge->iconUrl() }}" alt="{{ $badge->name }}">
                            @else
                                {{ $badge->icon ?: '◆' }}
                            @endif
                        </span>
                        <strong>{{ $badge->name }}</strong>
                    </a>
                @empty
                    <div class="hnt-profile-trophy-empty">{{ __('ui.profile_trophy_no_badges') }}</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
