@php
    $trophyStats = $trophyCabinet['stats'] ?? [];
    $trophyPlacements = collect($trophyCabinet['cup_placements'] ?? []);
    $trophySpotlights = collect($trophyCabinet['moment_spotlights'] ?? []);
    $trophyBestMoment = $trophyCabinet['best_moment'] ?? null;
    $hasTrophyPreview = $trophyPlacements->isNotEmpty() || $trophySpotlights->isNotEmpty() || $latestBadges->isNotEmpty() || $trophyBestMoment;
@endphp

<div id="profile-trophies" class="hnt-profile-trophy-card bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
    <div class="flex items-baseline justify-between text-black dark:text-white">
        <h3 class="font-bold text-base">{{ __('ui.profile_trophy_cabinet') }}</h3>
        <a href="{{ $profileSectionUrl('trophies') }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
    </div>

    <div class="hnt-profile-trophy-mini-stats mt-4">
        <div><strong>{{ (int) ($trophyStats['cup_points'] ?? 0) }}</strong><span>{{ __('ui.profile_trophy_points') }}</span></div>
        <div><strong>{{ (int) ($trophyStats['badges'] ?? 0) }}</strong><span>{{ __('ui.gamification_badges') }}</span></div>
        <div><strong>{{ (int) ($trophyStats['moments'] ?? 0) }}</strong><span>{{ __('ui.moments') }}</span></div>
    </div>

    @if($hasTrophyPreview)
        <div class="mt-4 space-y-3">
            @if($trophyPlacements->isNotEmpty())
                @php($placement = $trophyPlacements->first())
                @php($placementRank = (int) ($placement['rank'] ?? 0))
                @php($placementRankTone = $placementRank >= 1 && $placementRank <= 3 ? (string) $placementRank : 'default')
                <a href="{{ $placement['url'] }}" class="hnt-profile-trophy-preview-row">
                    <span class="hnt-profile-trophy-medal hnt-profile-trophy-medal-{{ $placementRankTone }}">#{{ $placementRank }}</span>
                    <span class="min-w-0">
                        <strong>{{ $placement['cup']->title }}</strong>
                        <small>{{ $placement['points'] }} {{ __('ui.profile_trophy_points') }} · {{ $placement['kills'] }} {{ __('ui.profile_trophy_kills') }}</small>
                    </span>
                </a>
            @endif

            @if($trophySpotlights->isNotEmpty())
                @php($spotlight = $trophySpotlights->first())
                <a href="{{ route('moments.show', $spotlight->moment) }}" class="hnt-profile-trophy-preview-row">
                    <span class="hnt-profile-trophy-icon"><ion-icon name="play-circle-outline"></ion-icon></span>
                    <span class="min-w-0">
                        <strong>{{ $spotlight->title ?: __('ui.profile_trophy_moment_of_week') }}</strong>
                        <small>{{ $spotlight->dateLabel() }}</small>
                    </span>
                </a>
            @elseif($trophyBestMoment)
                <a href="{{ route('moments.show', $trophyBestMoment) }}" class="hnt-profile-trophy-preview-row">
                    <span class="hnt-profile-trophy-icon"><ion-icon name="videocam-outline"></ion-icon></span>
                    <span class="min-w-0">
                        <strong>{{ __('ui.profile_trophy_best_moment') }}</strong>
                        <small>{{ (int) $trophyBestMoment->likes_count }} {{ __('ui.likes') }} · {{ (int) $trophyBestMoment->views_count }} {{ __('ui.views') }}</small>
                    </span>
                </a>
            @endif
        </div>
    @else
        <p class="mt-4 text-sm text-gray-500 dark:text-white/70">{{ __('ui.profile_trophy_empty_short') }}</p>
    @endif
</div>
