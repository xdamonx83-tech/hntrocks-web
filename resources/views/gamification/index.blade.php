@extends('layouts.app')

@section('title', __('ui.gamification_page_title'))

@section('content')
@php
    $profileCompletion = (int) \App\Support\ProfileCompletion::score($user);
    $unlockedBadgesCount = (int) $user->badges->count();
    $availableBadgesCount = (int) $availableBadges->count();
    $completedQuestsCount = (int) $user->questProgress->filter(fn ($progress) => filled($progress->completed_at))->count();
    $questsCount = (int) $quests->count();
    $levelProgress = (int) $levelProgressPercent;
    $questCovers = [
        asset('assets/vikinger/img/quest/cover/01.png'),
        asset('assets/vikinger/img/quest/cover/02.png'),
        asset('assets/vikinger/img/quest/cover/03.png'),
        asset('assets/vikinger/img/quest/cover/04.png'),
    ];
@endphp

<div class="section-banner hh-gm-section-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/badges-icon.png') }}" alt="{{ __('ui.gamification') }}">
    <p class="section-banner-title">{{ __('ui.gamification_page_title') }}</p>
    <p class="section-banner-text">{{ __('ui.gamification_page_text') }}</p>
</div>

@if (session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<div class="grid grid-3-3-3-3 hh-gm-overview-grid">
    <div class="widget-box hh-gm-overview-card">
        <div class="achievement-status">
            <p class="achievement-status-progress">{{ $user->level }}</p>
            <div class="achievement-status-info">
                <p class="achievement-status-title">{{ __('ui.gamification_level') }}</p>
                <p class="achievement-status-text">{{ number_format((int) $user->xp_total, 0, ',', '.') }} XP</p>
            </div>
            <img class="achievement-status-image" src="{{ asset('assets/vikinger/img/badge/level-badge.png') }}" alt="{{ __('ui.gamification_level') }}">
        </div>
        <div class="hh-gm-progress-wrap">
            <div class="progress-stat">
                <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $levelProgress }}%"></span></div>
                <div class="bar-progress-wrap small">
                    <p class="bar-progress-info negative center"><span>{{ $levelProgress }}%</span> {{ __('ui.gamification_next_level') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="widget-box hh-gm-overview-card">
        <div class="achievement-status">
            <p class="achievement-status-progress">{{ $unlockedBadgesCount }}</p>
            <div class="achievement-status-info">
                <p class="achievement-status-title">{{ __('ui.gamification_badges') }}</p>
                <p class="achievement-status-text">{{ __('ui.gamification_unlocked_of_total', ['unlocked' => $unlockedBadgesCount, 'total' => $availableBadgesCount]) }}</p>
            </div>
            <img class="achievement-status-image" src="{{ asset('assets/vikinger/img/badge/unlocked-badge.png') }}" alt="{{ __('ui.gamification_badges') }}">
        </div>
    </div>

    <div class="widget-box hh-gm-overview-card">
        <div class="achievement-status">
            <p class="achievement-status-progress">{{ $completedQuestsCount }}</p>
            <div class="achievement-status-info">
                <p class="achievement-status-title">{{ __('ui.gamification_quests') }}</p>
                <p class="achievement-status-text">{{ __('ui.gamification_completed_of_total', ['completed' => $completedQuestsCount, 'total' => $questsCount]) }}</p>
            </div>
            <img class="achievement-status-image" src="{{ asset('assets/vikinger/img/quest/completedq-s.png') }}" alt="{{ __('ui.gamification_quests') }}">
        </div>
    </div>

    <div class="widget-box hh-gm-overview-card">
        <div class="achievement-status">
            <p class="achievement-status-progress">{{ $profileCompletion }}%</p>
            <div class="achievement-status-info">
                <p class="achievement-status-title">{{ __('ui.gamification_profile') }}</p>
                <p class="achievement-status-text">{{ __('ui.gamification_profile_text') }}</p>
            </div>
            <img class="achievement-status-image" src="{{ asset('assets/vikinger/img/badge/verifieds-s.png') }}" alt="{{ __('ui.gamification_profile') }}">
        </div>
    </div>
</div>

<div class="section-header hh-gm-section-header">
    <div class="section-header-info">
        <p class="section-pretitle">{{ __('ui.gamification_collect_progress') }}</p>
        <h2 class="section-title">{{ __('ui.gamification_badge_collection') }}</h2>
    </div>
    <div class="section-header-actions">
        <p class="hh-gm-section-count">{{ __('ui.gamification_unlocked_of_total', ['unlocked' => $unlockedBadgesCount, 'total' => $availableBadgesCount]) }}</p>
    </div>
</div>

<div class="grid grid-3-3-3-3 top-space centered hh-gm-badge-grid">
    @forelse ($availableBadges as $badge)
        @php
            $isUnlocked = $user->badges->contains('id', $badge->id);
            $badgeIcon = $badge->iconUrl();
            $badgeFallback = $isUnlocked ? asset('assets/vikinger/img/badge/uexp-b.png') : asset('assets/vikinger/img/badge/badge-empty.png');
            $badgeImage = $badgeIcon ?: $badgeFallback;
            $badgePreview = $badgeIcon ?: ($isUnlocked ? asset('assets/vikinger/img/badge/uexp-s.png') : asset('assets/vikinger/img/badge/blank-s.png'));
            $badgeProgress = $isUnlocked ? 100 : 0;
            $badgeXp = (int) ($badge->xp_reward ?? 0);
        @endphp
        <div class="badge-item-stat hh-gm-badge-card {{ $isUnlocked ? 'is-unlocked' : 'is-locked' }}">
            <p class="text-sticker">
                <i class="text-sticker-icon hh-ph-action-icon ph ph-plus" aria-hidden="true"></i>
                {{ $badgeXp > 0 ? $badgeXp.' XP' : ($isUnlocked ? __('ui.gamification_unlocked') : __('ui.gamification_locked')) }}
            </p>

            <img class="badge-item-stat-image-preview" src="{{ $badgePreview }}" alt="{{ $badge->name }}">
            <img class="badge-item-stat-image" src="{{ $badgeImage }}" alt="{{ $badge->name }}">

            <p class="badge-item-stat-title">{{ $badge->name }}</p>
            <p class="badge-item-stat-text">{{ $badge->description ?: __('ui.gamification_badge_default_text') }}</p>

            <div class="progress-stat">
                <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $badgeProgress }}%"></span></div>
                <div class="bar-progress-wrap">
                    <p class="bar-progress-info negative center"><span>{{ $isUnlocked ? __('ui.gamification_unlocked') : __('ui.gamification_locked') }}</span></p>
                </div>
            </div>
        </div>
    @empty
        <div class="widget-box hh-gm-empty-card">
            <p class="widget-box-title">{{ __('ui.gamification_no_badges_title') }}</p>
            <p class="widget-box-text">{{ __('ui.gamification_no_badges_text') }}</p>
        </div>
    @endforelse
</div>

<div class="section-header hh-gm-section-header">
    <div class="section-header-info">
        <p class="section-pretitle">{{ __('ui.gamification_get_lead') }}</p>
        <h2 class="section-title">{{ __('ui.gamification_active_quests') }}</h2>
    </div>
    <div class="section-header-actions">
        <p class="hh-gm-section-count">{{ __('ui.gamification_completed_of_total', ['completed' => $completedQuestsCount, 'total' => $questsCount]) }}</p>
    </div>
</div>

<div class="grid grid-3-3-3-3 centered hh-gm-quest-grid">
    @forelse ($quests as $quest)
        @php
            $progress = $progressByQuestId->get($quest->id);
            $target = max(1, (int) $quest->target_count);
            $count = min((int) ($progress?->progress_count ?? 0), $target);
            $percent = (int) min(100, round(($count / $target) * 100));
            $done = filled($progress?->completed_at);
            $questIcon = $quest->iconUrl();
            $questBadgeImage = $questIcon ?: ($done ? asset('assets/vikinger/img/quest/completedq-b.png') : asset('assets/vikinger/img/quest/openq-b.png'));
            $coverImage = $questCovers[($loop->iteration - 1) % count($questCovers)];
        @endphp
        <div class="quest-item hh-gm-quest-item {{ $done ? 'is-completed' : '' }}">
            <figure class="quest-item-cover liquid hh-gm-quest-cover">
                <img src="{{ $coverImage }}" alt="{{ $quest->name }}">
            </figure>

            <p class="text-sticker small-text">
                <i class="text-sticker-icon hh-ph-action-icon ph ph-plus" aria-hidden="true"></i>
                {{ (int) $quest->xp_reward }} EXP
            </p>

            <div class="quest-item-info">
                <div class="quest-item-badge">
                    <img src="{{ $questBadgeImage }}" alt="{{ $quest->name }}">
                </div>

                <p class="quest-item-title">{{ $quest->name }}</p>
                <p class="quest-item-text">{{ $quest->description ?: __('ui.gamification_quest_default_text') }}</p>

                <div class="progress-stat">
                    <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $percent }}%"></span></div>
                    <div class="bar-progress-wrap small">
                        <p class="bar-progress-info negative start"><span>{{ $percent }}%</span> {{ __('ui.gamification_completed') }}</p>
                    </div>
                </div>

                <div class="quest-item-meta hh-gm-quest-meta">
                    <div class="hh-gm-quest-meta-block">
                        <p class="quest-item-meta-title">{{ $count }} / {{ $target }}</p>
                        <p class="quest-item-meta-text">{{ $done ? __('ui.gamification_done') : __('ui.gamification_progress') }}</p>
                    </div>
                    @if ($quest->badge_slug)
                        <div class="hh-gm-quest-meta-block">
                            <p class="quest-item-meta-title">Badge</p>
                            <p class="quest-item-meta-text">{{ $quest->badge_slug }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="widget-box hh-gm-empty-card">
            <p class="widget-box-title">{{ __('ui.gamification_no_quests_title') }}</p>
            <p class="widget-box-text">{{ __('ui.gamification_no_quests_text') }}</p>
        </div>
    @endforelse
</div>

<div class="section-header hh-gm-section-header">
    <div class="section-header-info">
        <p class="section-pretitle">{{ __('ui.gamification_latest_steps') }}</p>
        <h2 class="section-title">{{ __('ui.gamification_xp_history') }}</h2>
    </div>
</div>

<div class="widget-box hh-gm-xp-widget">
    <div class="hh-gm-xp-list">
        @forelse ($recentEvents as $event)
            <div class="hh-gm-xp-item">
                <div class="hh-gm-xp-points">+{{ $event->points }}</div>
                <div>
                    <p class="hh-gm-xp-title">{{ $event->description ?? $event->action }}</p>
                    <p class="hh-gm-xp-text">{{ $event->created_at?->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <p class="widget-box-text">{{ __('ui.gamification_no_xp_text') }}</p>
        @endforelse
    </div>
</div>
@endsection
