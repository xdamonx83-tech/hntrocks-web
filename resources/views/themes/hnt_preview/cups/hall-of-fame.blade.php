@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.hall_of_fame').' · HNT Preview')
@section('main_class', 'feed-main hall-main')

@php
    $rankLabel = static function (int $rank): string {
        return match ($rank) {
            1 => __('ui.hof_rank_champion'),
            2 => __('ui.hof_rank_second_place'),
            3 => __('ui.hof_rank_third_place'),
            default => __('ui.hof_rank_finalist'),
        };
    };

    $scoreLine = static function ($team): string {
        return __('ui.hof_score_line', [
            'points' => (int) ($team->points_total ?? 0),
            'tokens' => (int) ($team->bounty_tokens_total ?? 0),
            'kills' => (int) ($team->kills_total ?? 0),
        ]);
    };

    $shortScoreLine = static function ($team): string {
        return (int) ($team->points_total ?? 0).' '.__('ui.preview_cup_points_short')
            .' · '.(int) ($team->bounty_tokens_total ?? 0).' '.__('ui.preview_cup_bounty_short')
            .' · '.(int) ($team->kills_total ?? 0).' '.__('ui.preview_cup_kills_short');
    };

    $profileUrlFor = static function ($player, $fallbackCup): string {
        return $player ? route('profile.public', $player) : route('cups.show', $fallbackCup);
    };

    $nameFor = static function ($cup, $team): string {
        return $cup->isSoloLeaderboard() ? $team->displayName() : $team->name;
    };

    $featuredEntry = $hallCups->first(fn ($entry) => ($entry['topThree'] ?? collect())->isNotEmpty());
    $podiumOrder = [2 => 0, 1 => 1, 3 => 2];
    $featuredPodium = $featuredEntry
        ? ($featuredEntry['topThree'] ?? collect())
            ->values()
            ->map(fn ($team, $index) => ['team' => $team, 'rank' => $index + 1, 'cup' => $featuredEntry['cup']])
            ->sortBy(fn ($entry) => $podiumOrder[(int) $entry['rank']] ?? 99)
            ->values()
        : collect();
@endphp

@section('content')
    <div class="hall-shell">
        <section class="hall-hero" aria-label="{{ __('ui.preview_hof_stat_aria') }}">
            <div class="hall-trophy" aria-hidden="true">
                <i class="ph ph-trophy" aria-hidden="true"></i>
            </div>
            <span class="hall-kicker">{{ __('ui.preview_hof_kicker') }}</span>
            <h1>{{ __('ui.hof_socialite_title') }}</h1>
            <p>{{ __('ui.hof_banner_text') }}</p>
            <div class="hall-stats" aria-label="{{ __('ui.preview_hof_stat_aria') }}">
                <div><span>{{ __('ui.hof_stat_cups') }}</span><strong>{{ $hallCups->count() }}</strong></div>
                <div><span>{{ __('ui.hof_stat_winners') }}</span><strong>{{ $winnerCount }}</strong></div>
                <div><span>{{ __('ui.hof_stat_finalists') }}</span><strong>{{ $finalistCount }}</strong></div>
            </div>
        </section>

        @if($hallCups->isEmpty())
            <section class="hall-section" aria-label="{{ __('ui.hof_empty_title') }}">
                <div class="cup-panel">
                    <h2>{{ __('ui.hof_empty_title') }}</h2>
                    <p>{{ __('ui.hof_empty_text') }}</p>
                    <a class="hall-cup-link" href="{{ route('cups.index') }}">{{ __('ui.cups') }}</a>
                </div>
            </section>
        @else
            <section class="hall-section hall-podium-section" aria-label="{{ __('ui.preview_hof_podium_aria') }}">
                <div class="hall-section-title">
                    <span>{{ __('ui.preview_hof_confirmed_kicker') }}</span>
                    <h2>{{ __('ui.hof_top_three_short') }}</h2>
                </div>
                <div class="hall-podium">
                    @forelse($featuredPodium as $entry)
                        @php
                            $team = $entry['team'];
                            $cup = $entry['cup'];
                            $rank = (int) $entry['rank'];
                            $player = $team->owner;
                            $displayName = $nameFor($cup, $team);
                            $profileUrl = $profileUrlFor($player, $cup);
                            $winnerClass = $rank === 1 ? 'hall-winner-main' : 'hall-winner-side';
                            $medalClass = match ($rank) {
                                1 => 'champion',
                                2 => 'second',
                                default => 'third',
                            };
                        @endphp
                        <article class="hall-winner {{ $winnerClass }}">
                            <div class="hall-place">#{{ $rank }} · {{ $rankLabel($rank) }}</div>
                            <h3><a href="{{ $profileUrl }}">{{ $displayName }}</a></h3>
                            <div class="hall-medal {{ $medalClass }}"><span>{{ $rank }}</span></div>
                            <p><strong>{{ $cup->title }}</strong><br>{{ $scoreLine($team) }}</p>
                        </article>
                    @empty
                        <article class="hall-winner hall-winner-main">
                            <div class="hall-place">{{ __('ui.hof_no_winners_title') }}</div>
                            <h3>{{ __('ui.hof_no_winners_text') }}</h3>
                        </article>
                    @endforelse
                </div>
            </section>

            <section class="hall-section hall-archive-note" aria-label="{{ __('ui.preview_hof_archive_aria') }}">
                <div class="hall-section-title">
                    <span>{{ __('ui.preview_hof_archive_kicker') }}</span>
                    <h2>{{ __('ui.hof_socialite_archive_title') }}</h2>
                </div>
                <div class="hall-note-grid">
                    <div class="hall-note-item">
                        <i class="ph ph-shield-check" aria-hidden="true"></i>
                        <div>
                            <h3>{{ __('ui.hof_verification_notice_title') }}</h3>
                            <p>{{ __('ui.hof_verification_notice_text') }}</p>
                        </div>
                    </div>
                    <div class="hall-note-item">
                        <i class="ph ph-chart-bar" aria-hidden="true"></i>
                        <div>
                            <h3>{{ __('ui.hof_socialite_top_title') }}</h3>
                            <p>{{ __('ui.hof_final_leaderboard_text') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            @foreach($hallCups as $hallEntry)
                @php
                    $cup = $hallEntry['cup'];
                    $topThree = $hallEntry['topThree'];
                    $topFive = $hallEntry['topFive'];
                    $cupPeriod = $cup->ends_at
                        ? $cup->ends_at->translatedFormat('d.m.Y')
                        : ($cup->starts_at ? $cup->starts_at->translatedFormat('d.m.Y') : __('ui.cup_open'));
                    $archivePodium = ($topThree ?? collect())
                        ->values()
                        ->map(fn ($team, $index) => ['team' => $team, 'rank' => $index + 1])
                        ->sortBy(fn ($entry) => $podiumOrder[(int) $entry['rank']] ?? 99)
                        ->values();
                @endphp

                <section class="hall-section hall-cup-archive" aria-label="{{ $cup->title }}">
                    <div class="hall-section-title">
                        <span>{{ __('ui.preview_hof_finished_cup') }}</span>
                        <h2>{{ $cup->title }}</h2>
                    </div>
                    <div class="hall-cup-banner" style="background: linear-gradient(90deg, rgba(0, 0, 0, 0.78), rgba(0, 0, 0, 0.40) 52%, rgba(0, 0, 0, 0.72)), url('{{ $cup->coverUrl() }}') center / cover no-repeat;">
                        <div>
                            <span>{{ __('ui.hof_cup_subline', ['date' => $cupPeriod, 'mode' => $cup->modeLabel()]) }}</span>
                            <h3>{{ $cup->title }}</h3>
                        </div>
                        <a href="{{ route('cups.show', $cup) }}" class="hall-cup-link">{{ __('ui.hof_view_cup') }}</a>
                    </div>

                    <div class="hall-cup-winners">
                        @forelse($archivePodium as $entry)
                            @php
                                $team = $entry['team'];
                                $rank = (int) $entry['rank'];
                                $displayName = $nameFor($cup, $team);
                            @endphp
                            <div>
                                <span>#{{ $rank }} · {{ $rankLabel($rank) }}</span>
                                <strong>{{ $displayName }}</strong>
                                <small>{{ $shortScoreLine($team) }}</small>
                            </div>
                        @empty
                            <div>
                                <span>{{ __('ui.hof_no_winners_title') }}</span>
                                <strong>{{ __('ui.hof_no_winners_text') }}</strong>
                            </div>
                        @endforelse
                    </div>

                    <div class="hall-section-title hall-leader-title">
                        <span>{{ __('ui.preview_hof_leaderboard_kicker') }}</span>
                        <h2>{{ __('ui.hof_final_leaderboard_title') }}</h2>
                    </div>
                    <div class="hall-leaderboard" aria-label="{{ __('ui.preview_hof_leaderboard_aria') }}">
                        @forelse($topFive as $index => $team)
                            @php
                                $rank = $index + 1;
                                $displayName = $nameFor($cup, $team);
                            @endphp
                            <div class="hall-rank-row">
                                <span>#{{ $rank }}</span>
                                <strong>{{ $displayName }}</strong>
                                <small>{{ (int) $team->points_total }} {{ __('ui.preview_cup_points_short') }}</small>
                                <small>{{ (int) $team->bounty_tokens_total }} {{ __('ui.preview_cup_bounty_short') }}</small>
                                <small>{{ (int) $team->kills_total }} {{ __('ui.preview_cup_kills_short') }}</small>
                            </div>
                        @empty
                            <div class="hall-rank-row">
                                <span>—</span>
                                <strong>{{ __('ui.hof_no_finalists') }}</strong>
                                <small></small>
                                <small></small>
                                <small></small>
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        @endif
    </div>
@endsection
