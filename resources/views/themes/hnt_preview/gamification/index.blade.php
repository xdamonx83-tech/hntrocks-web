@extends('themes.hnt_preview.layouts.app')

@section('main_class', 'gamification-main')
@section('title', __('ui.gamification_page_title'))

@php
    $profileCompletion = (int) \App\Support\ProfileCompletion::score($user);
    $unlockedBadges = $user->badges->keyBy('id');
    $unlockedBadgesCount = (int) $unlockedBadges->count();
    $availableBadgesCount = (int) $availableBadges->count();
    $completedQuestsCount = (int) $user->questProgress->filter(fn ($progress) => filled($progress->completed_at))->count();
    $questsCount = (int) $quests->count();
    $levelProgress = max(0, min(100, (int) $levelProgressPercent));
    $locale = str_replace('_', '-', app()->getLocale());
    $number = static fn (int|float $value): string => number_format((float) $value, 0, app()->getLocale() === 'de' ? ',' : '.', app()->getLocale() === 'de' ? '.' : ',');
    $translated = static function (string $prefix, string $slug, string $field, ?string $fallback = null): string {
        $key = 'ui.' . $prefix . '_' . str_replace('-', '_', $slug) . '_' . $field;

        return \Illuminate\Support\Facades\Lang::has($key) ? __($key) : (string) $fallback;
    };
    $badgeSymbol = static function ($badge): string {
        $icon = trim((string) ($badge->icon ?? ''));

        return $icon !== '' ? $icon : '✦';
    };
    $topBadges = $availableBadges->filter(fn ($badge) => $unlockedBadges->has($badge->id))->take(4);
@endphp

@section('content')
<div class="gamification-shell">
    @if (session('status'))
        <div class="hnt-crowns-alert success">{{ session('status') }}</div>
    @endif

    <nav class="gamification-tabs" aria-label="{{ __('ui.gamification_page_title') }}">
        <a class="active" href="#all-badges">{{ __('ui.gamification_tab_all_badges') }} <span>{{ $availableBadgesCount }}</span></a>
        <a href="#my-badges">{{ __('ui.gamification_tab_unlocked') }} <span>{{ $unlockedBadgesCount }}</span></a>
        <a href="#active-quests">{{ __('ui.gamification_tab_quests') }} <span>{{ $questsCount }}</span></a>
    </nav>

    <section class="gamification-progress-panel">
        <p class="gamification-kicker">{{ __('ui.gamification_collect_progress') }}</p>
        <div class="gamification-progress-head">
            <div>
                <h1>{{ __('ui.gamification_page_title') }}</h1>
                <p>{{ __('ui.gamification_page_text') }}</p>
            </div>
            <a class="gamification-profile-link" href="{{ route('profile.show') }}">{{ __('ui.preview_nav_open_profile') }}</a>
        </div>

        <div class="gamification-stat-row">
            <div class="game-stat">
                <strong>{{ (int) $user->level }}</strong>
                <span>{{ __('ui.gamification_level') }}</span>
            </div>
            <div class="game-stat">
                <strong>{{ $number((int) $user->xp_total) }}</strong>
                <span>XP</span>
            </div>
            <div class="game-stat">
                <strong>{{ $unlockedBadgesCount }}</strong>
                <span>{{ __('ui.gamification_badges') }}</span>
            </div>
            <div class="game-stat">
                <strong>{{ $completedQuestsCount }}</strong>
                <span>{{ __('ui.gamification_quests') }}</span>
            </div>
            <div class="game-stat">
                <strong>{{ $profileCompletion }}%</strong>
                <span>{{ __('ui.gamification_profile') }}</span>
            </div>
        </div>

        <div class="gamification-progress-line">
            <div class="game-progress-meta">
                <span>{{ $number((int) $user->xp_total) }} XP</span>
                <span>{{ $levelProgress }}% {{ __('ui.gamification_next_level') }} · {{ $number((int) $xpToNextLevel) }} XP</span>
            </div>
            <div class="game-progress-track" aria-hidden="true"><span style="width: {{ $levelProgress }}%"></span></div>
        </div>
    </section>

    <section class="gamification-section" id="my-badges">
        <div class="gamification-section-head">
            <div>
                <span>{{ __('ui.gamification_sidebar_top_badges') }}</span>
                <h2>{{ __('ui.gamification_tab_unlocked') }}</h2>
            </div>
            <p>{{ __('ui.gamification_unlocked_of_total', ['unlocked' => $unlockedBadgesCount, 'total' => $availableBadgesCount]) }}</p>
        </div>

        @if ($topBadges->isNotEmpty())
            <div class="badge-grid badge-grid-featured">
                @foreach ($topBadges as $badge)
                    @php
                        $badgeName = $translated('gamification_badge', $badge->slug, 'name', $badge->name);
                        $badgeDescription = $translated('gamification_badge', $badge->slug, 'description', $badge->description ?: __('ui.gamification_badge_default_text'));
                        $badgeIcon = $badge->iconUrl();
                    @endphp
                    <article class="badge-card unlocked">
                        <div class="badge-medallion">
                            <span>
                                @if ($badgeIcon)
                                    <img src="{{ $badgeIcon }}" alt="{{ $badgeName }}">
                                @else
                                    {{ $badgeSymbol($badge) }}
                                @endif
                            </span>
                        </div>
                        <div class="badge-card-body">
                            <h2>{{ $badgeName }}</h2>
                            <p>{{ $badgeDescription }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <article class="gamification-empty-card">
                <h2>{{ __('ui.gamification_no_unlocked_badges_title') }}</h2>
                <p>{{ __('ui.gamification_no_unlocked_badges_text') }}</p>
            </article>
        @endif
    </section>

    <section class="gamification-section" id="all-badges">
        <div class="gamification-section-head">
            <div>
                <span>{{ __('ui.gamification_badges_games_hint') }}</span>
                <h2>{{ __('ui.gamification_badge_collection') }}</h2>
            </div>
            <p>{{ __('ui.gamification_unlocked_of_total', ['unlocked' => $unlockedBadgesCount, 'total' => $availableBadgesCount]) }}</p>
        </div>

        <div class="badge-grid">
            @forelse ($availableBadges as $badge)
                @php
                    $isUnlocked = $unlockedBadges->has($badge->id);
                    $badgeName = $translated('gamification_badge', $badge->slug, 'name', $badge->name);
                    $badgeDescription = $translated('gamification_badge', $badge->slug, 'description', $badge->description ?: __('ui.gamification_badge_default_text'));
                    $badgeIcon = $badge->iconUrl();
                    $badgeXp = (int) ($badge->xp_reward ?? 0);
                    $badgeStatus = $isUnlocked ? __('ui.gamification_unlocked') : __('ui.gamification_locked');
                @endphp
                <article class="badge-card {{ $isUnlocked ? 'unlocked' : 'locked' }}" data-status="{{ $badgeStatus }}">
                    <div class="gamification-reward-pill">{{ $badgeXp > 0 ? '+' . $number($badgeXp) . ' XP' : __('ui.gamification_reward') }}</div>
                    <div class="badge-medallion">
                        <span>
                            @if ($badgeIcon)
                                <img src="{{ $badgeIcon }}" alt="{{ $badgeName }}">
                            @else
                                {{ $badgeSymbol($badge) }}
                            @endif
                        </span>
                    </div>
                    <div class="badge-card-body">
                        <h2>{{ $badgeName }}</h2>
                        <p>{{ $isUnlocked ? $badgeDescription : $badgeStatus }}</p>
                    </div>
                </article>
            @empty
                <article class="gamification-empty-card gamification-empty-card-wide">
                    <h2>{{ __('ui.gamification_no_badges_title') }}</h2>
                    <p>{{ __('ui.gamification_no_badges_text') }}</p>
                </article>
            @endforelse
        </div>
    </section>

    <section class="gamification-section" id="active-quests">
        <div class="gamification-section-head">
            <div>
                <span>{{ __('ui.gamification_get_lead') }}</span>
                <h2>{{ __('ui.gamification_active_quests') }}</h2>
            </div>
            <p>{{ __('ui.gamification_completed_of_total', ['completed' => $completedQuestsCount, 'total' => $questsCount]) }}</p>
        </div>

        <div class="quest-grid">
            @forelse ($quests as $quest)
                @php
                    $progress = $progressByQuestId->get($quest->id);
                    $target = max(1, (int) $quest->target_count);
                    $count = min((int) ($progress?->progress_count ?? 0), $target);
                    $percent = (int) min(100, round(($count / $target) * 100));
                    $done = filled($progress?->completed_at);
                    $questName = $translated('gamification_quest', $quest->slug, 'name', $quest->name);
                    $questDescription = $translated('gamification_quest', $quest->slug, 'description', $quest->description ?: __('ui.gamification_quest_default_text'));
                    $questIcon = $quest->iconUrl();
                @endphp
                <article class="quest-card {{ $done ? 'completed' : '' }}">
                    <div class="quest-card-top">
                        <div class="quest-icon">
                            @if ($questIcon)
                                <img src="{{ $questIcon }}" alt="{{ $questName }}">
                            @else
                                <span>{{ $done ? '✓' : '✦' }}</span>
                            @endif
                        </div>
                        <div>
                            <h3>{{ $questName }}</h3>
                            <p>{{ $questDescription }}</p>
                        </div>
                    </div>
                    <div class="quest-meta-row">
                        <span>{{ $count }} / {{ $target }}</span>
                        <strong>{{ $done ? __('ui.gamification_done') : $percent . '%' }}</strong>
                    </div>
                    <div class="game-progress-track" aria-hidden="true"><span style="width: {{ $percent }}%"></span></div>
                    <div class="quest-reward-row">
                        <span>{{ __('ui.gamification_reward') }}</span>
                        <strong>+{{ $number((int) $quest->xp_reward) }} XP</strong>
                    </div>
                </article>
            @empty
                <article class="gamification-empty-card gamification-empty-card-wide">
                    <h2>{{ __('ui.gamification_no_quests_title') }}</h2>
                    <p>{{ __('ui.gamification_no_quests_text') }}</p>
                </article>
            @endforelse
        </div>
    </section>

    <section class="gamification-section">
        <div class="gamification-section-head">
            <div>
                <span>{{ __('ui.gamification_latest_steps') }}</span>
                <h2>{{ __('ui.gamification_xp_history') }}</h2>
            </div>
        </div>

        <div class="xp-history-list">
            @forelse ($recentEvents as $event)
                <article class="xp-history-item">
                    <strong>+{{ $number((int) $event->points) }}</strong>
                    <div>
                        <h3>{{ $event->description ?? $event->action }}</h3>
                        <p>{{ $event->created_at?->diffForHumans() }}</p>
                    </div>
                </article>
            @empty
                <article class="gamification-empty-card gamification-empty-card-wide">
                    <p>{{ __('ui.gamification_no_xp_text') }}</p>
                </article>
            @endforelse
        </div>
    </section>
</div>
@endsection
