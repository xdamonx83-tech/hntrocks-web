@extends('layouts.app')

@section('title', __('ui.hall_of_fame'))

@section('content')
<div class="section-banner hh-cups-section-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/events-icon.png') }}" alt="{{ __('ui.hall_of_fame') }}">
    <p class="section-banner-title">{{ __('ui.hof_banner_title') }}</p>
    <p class="section-banner-text">{{ __('ui.hof_banner_text') }}</p>
</div>

<div class="section-header">
    <div class="section-header-info">
        <p class="section-pretitle">{{ __('ui.cups') }}</p>
        <h2 class="section-title">{{ __('ui.hall_of_fame') }}</h2>
    </div>

    <div class="section-header-actions">
        <a class="section-header-subsection" href="{{ route('cups.index') }}">{{ __('ui.cups') }}</a>
        <p class="section-header-subsection">{{ __('ui.hof_top_three_short') }}</p>
        <p class="section-header-subsection">{{ __('ui.hof_top_five_short') }}</p>
    </div>
</div>

<div class="grid grid-3-3-3 centered hh-cups-vikinger-grid">
    <div class="widget-box">
        <p class="widget-box-title">{{ __('ui.hof_stat_cups') }}</p>
        <p class="widget-box-text">{{ trans_choice('ui.hof_stat_cups_value', $hallCups->count(), ['count' => $hallCups->count()]) }}</p>
    </div>

    <div class="widget-box">
        <p class="widget-box-title">{{ __('ui.hof_stat_winners') }}</p>
        <p class="widget-box-text">{{ trans_choice('ui.hof_stat_winners_value', $winnerCount, ['count' => $winnerCount]) }}</p>
    </div>

    <div class="widget-box">
        <p class="widget-box-title">{{ __('ui.hof_stat_finalists') }}</p>
        <p class="widget-box-text">{{ trans_choice('ui.hof_stat_finalists_value', $finalistCount, ['count' => $finalistCount]) }}</p>
    </div>
</div>

<div class="hh-cup-ai-submit-note">
    <div class="hh-cup-ai-submit-note-icon">
        <i class="hh-ph-action-icon ph ph-medal" aria-hidden="true"></i>
    </div>
    <div>
        <p class="hh-cup-ai-submit-note-title">{{ __('ui.hof_verification_notice_title') }}</p>
        <p class="hh-cup-ai-submit-note-text">{{ __('ui.hof_verification_notice_text') }}</p>
    </div>
</div>

@if ($hallCups->isEmpty())
    <div class="widget-box hh-empty-state hh-cups-empty-state">
        <p class="widget-box-title">{{ __('ui.hof_empty_title') }}</p>
        <p class="widget-box-text">{{ __('ui.hof_empty_text') }}</p>
        <a class="button secondary" href="{{ route('cups.index') }}">{{ __('ui.cup_index_title') }}</a>
    </div>
@else
    @foreach ($hallCups as $hallEntry)
        @php
            /** @var \App\Models\Cup $cup */
            $cup = $hallEntry['cup'];
            $topThree = $hallEntry['topThree'];
            $topFive = $hallEntry['topFive'];
            $soloCup = $cup->isSoloLeaderboard();
            $entryLabel = $soloCup ? __('ui.cup_table_player') : __('ui.cup_table_team');
            $cupPeriod = $cup->ends_at
                ? $cup->ends_at->format('d.m.Y')
                : ($cup->starts_at ? $cup->starts_at->format('d.m.Y') : __('ui.cup_open'));
        @endphp

        <div class="widget-box hh-cup-hof-cup-card">
            <div class="widget-box-title-wrap">
                <div class="widget-box-title-content">
                    <p class="widget-box-title">{{ $cup->title }}</p>
                    <p class="widget-box-text">{{ __('ui.hof_cup_subline', ['date' => $cupPeriod, 'mode' => $cup->modeLabel()]) }}</p>
                </div>

                <a class="button small secondary" href="{{ route('cups.show', $cup) }}">{{ __('ui.hof_view_cup') }}</a>
            </div>

            <div class="grid grid-3-3-3 centered hh-cups-vikinger-grid">
                @forelse ($topThree as $index => $team)
                    @php
                        $rank = $index + 1;
                        $player = $team->owner;
                        $profileUrl = $player ? route('profile.public', $player) : route('cups.show', $cup);
                        $displayName = $soloCup ? $team->displayName() : $team->name;
                        $rankLabel = match ($rank) {
                            1 => __('ui.hof_rank_champion'),
                            2 => __('ui.hof_rank_finalist'),
                            default => __('ui.hof_rank_top_three'),
                        };
                    @endphp

                    <div class="product-preview fixed-height hh-cup-hof-winner-card">
                        <a href="{{ $profileUrl }}">
                            <figure class="product-preview-image liquid">
                                <img src="{{ $cup->coverUrl() }}" alt="{{ $cup->title }}">
                            </figure>
                        </a>

                        <div class="product-preview-info">
                            <p class="text-sticker"><span class="highlighted">#{{ $rank }}</span> {{ $rankLabel }}</p>

                            <p class="product-preview-title">
                                <a href="{{ $profileUrl }}">{{ $displayName }}</a>
                            </p>

                            <p class="product-preview-category digital">{{ $entryLabel }}</p>

                            <div class="user-status no-padding-top">
                                <a class="user-status-avatar" href="{{ $profileUrl }}">
                                    <div class="user-avatar small no-outline">
                                        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $player?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"></div></div>
                                        <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                                        <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                                        <div class="user-avatar-badge">
                                            <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                                            <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                                            <p class="user-avatar-badge-text">{{ max(1, (int) ($player?->level ?: 1)) }}</p>
                                        </div>
                                    </div>
                                </a>
                                <p class="user-status-title"><a class="bold" href="{{ $profileUrl }}">{{ $player?->name ?? $displayName }}</a></p>
                                <p class="user-status-text small">{{ __('ui.hof_score_line', ['points' => $team->points_total, 'kills' => $team->kills_total, 'tokens' => $team->bounty_tokens_total]) }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="widget-box hh-empty-state">
                        <p class="widget-box-title">{{ __('ui.hof_no_winners_title') }}</p>
                        <p class="widget-box-text">{{ __('ui.hof_no_winners_text') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="tab-box hh-cup-hof-table-box">
                <div class="tab-box-options">
                    <div class="tab-box-option active">
                        <p class="tab-box-option-title">{{ __('ui.hof_final_leaderboard_title') }}</p>
                    </div>
                </div>

                <div class="tab-box-items">
                    <div class="tab-box-item">
                        <div class="tab-box-item-content">
                            <p class="tab-box-item-paragraph">{{ __('ui.hof_final_leaderboard_text') }}</p>

                            <div class="table table-top-friends join-rows hh-cup-market-table">
                                <div class="table-header">
                                    <div class="table-header-column"><p class="table-header-title">{{ $entryLabel }}</p></div>
                                    <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_points') }}</p></div>
                                    <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_token') }}</p></div>
                                    <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_kills') }}</p></div>
                                    <div class="table-header-column centered padded"><p class="table-header-title">{{ __('ui.cup_table_matches') }}</p></div>
                                </div>

                                <div class="table-body">
                                    @forelse ($topFive as $index => $team)
                                        <div class="table-row tiny">
                                            <div class="table-column">
                                                <div class="user-status">
                                                    <p class="user-status-title"><span class="bold">#{{ $index + 1 }} · {{ $soloCup ? $team->displayName() : $team->name }}</span></p>
                                                    <p class="user-status-text small">{{ $soloCup ? __('ui.cup_player') : __('ui.cup_captain') }}: {{ $team->owner?->name ?? __('ui.cup_unknown') }}</p>
                                                </div>
                                            </div>
                                            <div class="table-column centered padded"><p class="table-title">{{ $team->points_total }}</p></div>
                                            <div class="table-column centered padded"><p class="table-title">{{ $team->bounty_tokens_total }}</p></div>
                                            <div class="table-column centered padded"><p class="table-title">{{ $team->kills_total }}</p></div>
                                            <div class="table-column centered padded"><p class="table-title">{{ $team->submissions_approved_count }}</p></div>
                                        </div>
                                    @empty
                                        <div class="table-row tiny">
                                            <div class="table-column">
                                                <p class="table-text">{{ __('ui.hof_no_finalists') }}</p>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
