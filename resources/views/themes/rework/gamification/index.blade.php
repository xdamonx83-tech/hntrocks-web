@extends('themes.rework.layouts.app')

@section('title', 'HNT.rocks Gamification')
@section('body_class', 'gamification-page')
@section('content_grid_class', 'gamification-page-grid')
@section('left_col_class', 'gamification-left')

@section('content')
@php
    $profileCompletion = (int) \App\Support\ProfileCompletion::score($user);
    $unlockedBadges = $user->badges->keyBy('id');
    $unlockedBadgesCount = (int) $unlockedBadges->count();
    $availableBadgesCount = (int) $availableBadges->count();
    $completedQuestsCount = (int) $user->questProgress->filter(fn ($progress) => filled($progress->completed_at))->count();
    $questsCount = (int) $quests->count();
    $activeQuestsCount = max(0, $questsCount - $completedQuestsCount);
    $levelProgress = max(0, min(100, (int) $levelProgressPercent));
    $number = static fn (int|float $value): string => number_format((float) $value, 0, app()->getLocale() === 'de' ? ',' : '.', app()->getLocale() === 'de' ? '.' : ',');
    $translated = static function (string $prefix, ?string $slug, string $field, ?string $fallback = null): string {
        $slug = (string) $slug;
        $key = 'ui.' . $prefix . '_' . str_replace('-', '_', $slug) . '_' . $field;

        return $slug !== '' && \Illuminate\Support\Facades\Lang::has($key) ? __($key) : (string) $fallback;
    };
    $iconFor = static function (?string $slug, string $fallback = 'ph-sparkle'): string {
        $slug = strtolower((string) $slug);

        return match (true) {
            str_contains($slug, 'comment'), str_contains($slug, 'chat') => 'ph-chat-circle-dots',
            str_contains($slug, 'post'), str_contains($slug, 'feed') => 'ph-note-pencil',
            str_contains($slug, 'moment'), str_contains($slug, 'video') => 'ph-video-camera',
            str_contains($slug, 'cup'), str_contains($slug, 'trophy') => 'ph-trophy',
            str_contains($slug, 'profile'), str_contains($slug, 'profil') => 'ph-user-circle-check',
            str_contains($slug, 'level'), str_contains($slug, 'veteran') => 'ph-shield-star',
            str_contains($slug, 'alpha') => 'ph-wolf',
            str_contains($slug, 'quest'), str_contains($slug, 'challenge') => 'ph-target',
            str_contains($slug, 'badge') => 'ph-seal-check',
            default => $fallback,
        };
    };
    $eventIconFor = static function (?string $action): string {
        $action = strtolower((string) $action);

        return match (true) {
            str_contains($action, 'comment') => 'ph-chat-circle-text',
            str_contains($action, 'post') => 'ph-note-pencil',
            str_contains($action, 'moment') => 'ph-video-camera',
            str_contains($action, 'cup') => 'ph-trophy',
            str_contains($action, 'profile') => 'ph-user-circle-check',
            str_contains($action, 'badge') => 'ph-seal-check',
            str_contains($action, 'quest') => 'ph-target',
            default => 'ph-sparkle',
        };
    };
    $openQuestXp = (int) $quests->sum(function ($quest) use ($progressByQuestId) {
        $progress = $progressByQuestId->get($quest->id);
        return filled($progress?->completed_at) ? 0 : (int) $quest->xp_reward;
    });
    $weekXp = (int) $recentEvents
        ->filter(fn ($event) => optional($event->created_at)->greaterThanOrEqualTo(now()->startOfWeek()))
        ->sum(fn ($event) => (int) $event->points);
    $featuredBadges = $availableBadges->take(6);
    $featuredQuests = $quests->take(6);
@endphp

<section class="members-head gamification-head">
    <div>
        <span class="members-eyebrow">HNT Progress</span>
        <h1>Gamification</h1>
        <p>Sammle XP, schalte Badges frei und arbeite dich durch deine hnt.rocks-Quests.</p>
    </div>
    <div class="wallet-head-actions">
        <a class="members-filter-btn" href="#gamification-badges"><i aria-hidden="true" class="ph ph-seal-check ph-icon"></i>Badges</a>
        <a class="members-filter-btn" href="#gamification-quests"><i aria-hidden="true" class="ph ph-target ph-icon"></i>Quests</a>
    </div>
</section>

<section class="gamification-hero card">
    <div class="gamification-hero-main">
        <div class="gamification-level-orb">
            <i aria-hidden="true" class="ph ph-ranking ph-icon"></i>
            <strong>{{ (int) $user->level }}</strong>
        </div>
        <div class="gamification-hero-copy">
            <span class="members-eyebrow">Dein Fortschritt</span>
            <h2>Level {{ (int) $user->level }} · {{ $number((int) $user->xp_total) }} XP</h2>
            <p>Noch {{ $number((int) $xpToNextLevel) }} XP bis zum nächsten Level. Beiträge, Kommentare, Moments und Cup-Aktionen bringen dich weiter.</p>
        </div>
        <div class="gamification-claim-card">
            <span>Quest-XP offen</span>
            <strong>+{{ $number($openQuestXp) }} XP</strong>
            <a href="#gamification-quests">Quests prüfen</a>
        </div>
    </div>
    <div class="gamification-progress-block">
        <div class="wallet-progress-head"><span>Nächstes Level</span><strong>{{ $levelProgress }}%</strong></div>
        <div class="wallet-progress"><span style="width:{{ $levelProgress }}%"></span></div>
    </div>
    <div class="gamification-stats-grid">
        <article><span>Level</span><strong>{{ (int) $user->level }}</strong><em>aktueller Rang</em></article>
        <article><span>Badges</span><strong>{{ $unlockedBadgesCount }} / {{ $availableBadgesCount }}</strong><em>freigeschaltet</em></article>
        <article><span>Quests</span><strong>{{ $completedQuestsCount }} / {{ $questsCount }}</strong><em>abgeschlossen</em></article>
        <article><span>Profil</span><strong>{{ $profileCompletion }}%</strong><em>vollständig</em></article>
    </div>
</section>

<section aria-label="Gamification Bereiche" class="shop-tabs gamification-tabs">
    <a class="active" href="#">Übersicht</a>
    <a href="#gamification-badges">Meine Badges</a>
    <a href="#gamification-quests">Quests</a>
    <a href="#gamification-history">XP-Verlauf</a>
</section>

<section aria-label="Gamification Übersicht" class="gamification-overview-grid">
    <article class="gamification-overview-card card">
        <i aria-hidden="true" class="ph ph-medal ph-icon"></i>
        <span>Badges</span>
        <strong>{{ $unlockedBadgesCount }}</strong>
        <p>{{ $unlockedBadgesCount }} von {{ $availableBadgesCount }} Badges sind aktuell freigeschaltet.</p>
    </article>
    <article class="gamification-overview-card card">
        <i aria-hidden="true" class="ph ph-target ph-icon"></i>
        <span>Aktive Quests</span>
        <strong>{{ $activeQuestsCount }}</strong>
        <p>{{ $completedQuestsCount }} von {{ $questsCount }} Quests sind abgeschlossen.</p>
    </article>
    <article class="gamification-overview-card card">
        <i aria-hidden="true" class="ph ph-chart-line-up ph-icon"></i>
        <span>XP diese Woche</span>
        <strong>+{{ $number($weekXp) }}</strong>
        <p>Aus den letzten erfassten XP-Aktionen dieser Woche.</p>
    </article>
</section>

<section class="wallet-section card" id="gamification-badges">
    <div class="wallet-section-head">
        <div><span class="members-eyebrow">Sammlung</span><h2>Badge-Sammlung</h2></div>
        <a class="wallet-small-link" href="#gamification-badges">{{ $unlockedBadgesCount }} / {{ $availableBadgesCount }} freigeschaltet</a>
    </div>
    <div class="gamification-card-grid">
        @forelse($featuredBadges as $badge)
            @php
                $isUnlocked = $unlockedBadges->has($badge->id);
                $badgeName = $translated('gamification_badge', $badge->slug, 'name', $badge->name);
                $badgeDescription = $translated('gamification_badge', $badge->slug, 'description', $badge->description ?: 'Badge-Fortschritt auf hnt.rocks.');
                $badgeXp = (int) ($badge->xp_reward ?? 0);
            @endphp
            <article class="gamification-item-card card {{ $isUnlocked ? 'is-unlocked' : '' }}">
                <div class="shop-item-top"><span class="shop-rarity {{ $isUnlocked ? 'legendary' : 'rare' }}">{{ $isUnlocked ? 'Aktiv' : 'Offen' }}</span><div class="shop-item-icon"><i aria-hidden="true" class="ph {{ $iconFor($badge->slug, 'ph-seal-check') }} ph-icon"></i></div></div>
                <h2>{{ $badgeName }}</h2><p class="shop-slot">Badge</p><p class="shop-desc">{{ $badgeDescription }}</p>
                <div class="shop-stats"><span>Reward</span><strong>{{ $badgeXp }} XP</strong><span>Status</span><strong>{{ $isUnlocked ? 'Aktiv' : 'Offen' }}</strong></div>
                <div class="shop-progress"><span style="width:{{ $isUnlocked ? 100 : 0 }}%"></span><em>{{ $isUnlocked ? '100%' : '0%' }}</em></div>
            </article>
        @empty
            <article class="gamification-item-card card">
                <div class="shop-item-top"><span class="shop-rarity rare">Leer</span><div class="shop-item-icon"><i aria-hidden="true" class="ph ph-seal-check ph-icon"></i></div></div>
                <h2>Noch keine Badges</h2><p class="shop-slot">Badge</p><p class="shop-desc">Sobald Badges aktiv sind, erscheinen sie hier.</p>
                <div class="shop-stats"><span>Status</span><strong>Leer</strong><span>Reward</span><strong>0 XP</strong></div>
                <div class="shop-progress"><span style="width:0%"></span><em>0%</em></div>
            </article>
        @endforelse
    </div>
</section>

<section class="wallet-section card" id="gamification-quests">
    <div class="wallet-section-head">
        <div><span class="members-eyebrow">Aufträge</span><h2>Aktive Quests</h2></div>
        <a class="wallet-small-link" href="#gamification-quests">{{ $completedQuestsCount }} / {{ $questsCount }} abgeschlossen</a>
    </div>
    <div class="gamification-card-grid">
        @forelse($featuredQuests as $quest)
            @php
                $progress = $progressByQuestId->get($quest->id);
                $target = max(1, (int) $quest->target_count);
                $count = min((int) ($progress?->progress_count ?? 0), $target);
                $percent = (int) min(100, round(($count / $target) * 100));
                $done = filled($progress?->completed_at);
                $questName = $translated('gamification_quest', $quest->slug, 'name', $quest->name);
                $questDescription = $translated('gamification_quest', $quest->slug, 'description', $quest->description ?: 'Schließe diese Quest ab, um XP zu sammeln.');
            @endphp
            <article class="gamification-quest-card card {{ $done ? 'is-unlocked' : '' }}">
                <div class="gamification-quest-icon"><i aria-hidden="true" class="ph {{ $iconFor($quest->slug, 'ph-target') }} ph-icon"></i></div>
                <h2>{{ $questName }}</h2><p>{{ $questDescription }}</p>
                <div class="shop-stats"><span>Ziel</span><strong>{{ $count }}/{{ $target }}</strong><span>Reward</span><strong>+{{ $number((int) $quest->xp_reward) }}</strong></div>
                <div class="shop-progress"><span style="width:{{ $percent }}%"></span><em>{{ $done ? 'fertig' : $percent . '%' }}</em></div>
            </article>
        @empty
            <article class="gamification-quest-card card">
                <div class="gamification-quest-icon"><i aria-hidden="true" class="ph ph-target ph-icon"></i></div>
                <h2>Keine Quests aktiv</h2><p>Sobald neue Quests aktiv sind, erscheinen sie hier.</p>
                <div class="shop-stats"><span>Ziel</span><strong>0/0</strong><span>Reward</span><strong>+0</strong></div>
                <div class="shop-progress"><span style="width:0%"></span><em>0%</em></div>
            </article>
        @endforelse
    </div>
</section>

<section class="wallet-section card" id="gamification-history">
    <div class="wallet-section-head"><div><span class="members-eyebrow">XP-Verlauf</span><h2>Letzte Schritte</h2></div></div>
    <div class="wallet-history-list gamification-history-list">
        @forelse($recentEvents->take(8) as $event)
            @php
                $eventPoints = (int) $event->points;
                $eventAction = (string) ($event->action ?? 'xp');
                $eventDescription = filled($event->description)
                    ? (string) $event->description
                    : \Illuminate\Support\Str::headline(str_replace(['_', '.'], ' ', $eventAction));
            @endphp
            <div><i class="ph {{ $eventIconFor($eventAction) }} ph-icon"></i><span><strong>{{ $eventDescription }}</strong><small>{{ optional($event->created_at)->diffForHumans() }}</small></span><em>+{{ $number($eventPoints) }} XP</em></div>
        @empty
            <div><i class="ph ph-clock-counter-clockwise ph-icon"></i><span><strong>Noch kein XP-Verlauf</strong><small>Neue XP-Aktionen erscheinen hier.</small></span><em>+0 XP</em></div>
        @endforelse
    </div>
</section>
@endsection
