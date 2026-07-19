@php
    $isEnglish = app()->getLocale() === 'en';
    $locale = $isEnglish ? 'en' : 'de';
    $t = static fn (string $de, string $en): string => $isEnglish ? $en : $de;
    $number = static fn (int|float $value): string => number_format((float) $value, 0, $isEnglish ? '.' : ',', $isEnglish ? ',' : '.');
    $profileCompletion = (int) \App\Support\ProfileCompletion::score($user);
    $unlockedBadges = $user->badges->keyBy('id');
    $unlockedBadgesCount = (int) $unlockedBadges->count();
    $availableBadgesCount = (int) $availableBadges->count();
    $completedQuestsCount = (int) $completedQuests->count();
    $questsCount = (int) $quests->count();
    $activeQuestsCount = (int) $activeQuests->count();
    $level = max(1, (int) ($user->level ?: 1));
    $levelProgress = max(0, min(100, (int) $levelProgressPercent));
    $weeklyGoalPercent = max(0, min(100, (int) round(($weeklyXpTotal / max(1, $weeklyXpGoal)) * 100)));
    $maxDailyXp = max(1, (int) $weeklyXpDays->max('points'));
    $avatarUrl = $user->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $displayName = $user->name ?: ($user->username ?: 'HNT Hunter');
    $handle = $user->username ? '@'.$user->username : '@hunter';
    $onlineLabel = $user->isOnline() ? $t('Online', 'Online') : $t('Offline', 'Offline');
    $topBadges = $user->badges->sortByDesc(fn ($badge) => $badge->pivot?->awarded_at)->take(4);
    $sidebarQuests = $activeQuests->take(4);
    if ($sidebarQuests->isEmpty()) {
        $sidebarQuests = $completedQuests->take(4);
    }
    $selectedQuest = $activeQuests->first() ?: $quests->first();
    $nextLockedBadge = $availableBadges->first(fn ($badge) => ! $unlockedBadges->has($badge->id));
    $nextBadgeQuest = $nextLockedBadge
        ? $quests->first(fn ($quest) => (string) $quest->badge_slug === (string) $nextLockedBadge->slug)
        : null;
    $nextBadgeProgress = $nextBadgeQuest ? $progressByQuestId->get($nextBadgeQuest->id) : null;
    $nextBadgeTarget = max(1, (int) ($nextBadgeQuest?->target_count ?? 1));
    $nextBadgeCount = min((int) ($nextBadgeProgress?->progress_count ?? 0), $nextBadgeTarget);
    $nextBadgePercent = $nextLockedBadge
        ? ($nextBadgeQuest ? (int) min(100, round(($nextBadgeCount / $nextBadgeTarget) * 100)) : 0)
        : 100;
    $questMasterPercent = $questsCount > 0 ? (int) min(100, round(($completedQuestsCount / $questsCount) * 100)) : 0;
    $streakCurrent = max(0, (int) ($dailyStreak['current_streak'] ?? 0));
    $streakMax = max(1, (int) ($dailyStreak['max_streak_days'] ?? 7));
    $dayLabels = $isEnglish ? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] : ['Mo','Di','Mi','Do','Fr','Sa','So'];
    $badgeSymbol = static function ($badge): string {
        $icon = trim((string) ($badge->icon ?? ''));
        return $icon !== '' ? $icon : '✦';
    };
    $questData = static function ($quest) use ($progressByQuestId): array {
        $progress = $progressByQuestId->get($quest->id);
        $target = max(1, (int) $quest->target_count);
        $count = min((int) ($progress?->progress_count ?? 0), $target);
        $done = filled($progress?->completed_at);
        return [
            'target' => $target,
            'count' => $count,
            'percent' => $done ? 100 : (int) min(100, round(($count / $target) * 100)),
            'done' => $done,
        ];
    };
    $eventArea = static function (string $action): string {
        return match (true) {
            str_starts_with($action, 'feed_') => 'Feed',
            str_starts_with($action, 'moment_') => 'Moments',
            str_starts_with($action, 'lfg_') => 'LFG',
            str_starts_with($action, 'team_') => 'Teams',
            str_starts_with($action, 'cup_') => 'Cups',
            str_starts_with($action, 'profile_'), $action === 'account_created' => 'Profil',
            str_starts_with($action, 'media_') => 'Medien',
            str_starts_with($action, 'quest_') => 'Quests',
            str_starts_with($action, 'loadout_') => 'Challenges',
            default => 'Community',
        };
    };
    $eventTitle = static fn ($event): string => filled($event->description)
        ? (string) $event->description
        : ucfirst(str_replace('_', ' ', (string) $event->action));
    $selectedState = $selectedQuest ? $questData($selectedQuest) : ['target' => 1, 'count' => 0, 'percent' => 0, 'done' => false];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>{{ $t('Gamification', 'Gamification') }} · HNT.ROCKS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-gamification/gamification-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-gamification/gamification-live.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="gamification">
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
@include('themes.hnt_preview.partials.icons')
<main class="app-shell gamification-page-shell" data-gamification-dashboard
      data-overview-label="{{ $t('Übersicht', 'Overview') }}"
      data-badges-label="{{ $t('Badges', 'Badges') }}"
      data-quests-label="{{ $t('Quests', 'Quests') }}"
      data-history-label="{{ $t('XP-Verlauf', 'XP history') }}">
@include('themes.hnt_preview.partials.header')
<section class="gamification-stage">
<div class="gamification-scroll" id="gamificationScroll">
<section class="gamification-heading">
<div>
<span>HNT.ROCKS {{ $t('FORTSCHRITT', 'PROGRESS') }}</span>
<h1>Gamification</h1>
<p>{{ $t('Level aufsteigen, Quests abschließen, XP sammeln und neue Badges freischalten.', 'Level up, complete quests, earn XP and unlock new badges.') }}</p>
</div>
<div class="gamification-heading-stats">
<article><strong>{{ $level }}</strong><span>Level</span></article>
<article><strong>{{ $number((int) $user->xp_total) }}</strong><span>{{ $t('XP gesamt', 'Total XP') }}</span></article>
<article><strong>{{ $unlockedBadgesCount }}</strong><span>Badges</span></article>
<article><strong>{{ $completedQuestsCount }}</strong><span>{{ $t('Quests erledigt', 'Quests completed') }}</span></article>
</div>
</section>

<section class="gamification-workspace">
<aside class="gamification-quest-sidebar">
<article class="quest-sidebar-card">
<header><div><span>{{ $t('AKTIVE QUESTS', 'ACTIVE QUESTS') }}</span><h2>{{ $t('Noch offen', 'Still open') }}</h2></div><strong>{{ $activeQuestsCount }}</strong></header>
<div class="quest-sidebar-list">
@forelse($sidebarQuests as $index => $quest)
@php
    $state = $questData($quest);
    $questName = $quest->displayName($locale);
    $questDescription = $quest->displayDescription($locale);
    $questIcon = $quest->iconUrl();
@endphp
<button class="{{ $index === 0 ? 'active' : '' }}"
        data-quest-title="{{ $questName }}"
        data-quest-description="{{ $questDescription }}"
        data-quest-progress="{{ $state['percent'] }}"
        data-quest-count="{{ $state['count'] }} / {{ $state['target'] }}"
        data-quest-reward="+{{ $number((int) $quest->xp_reward) }} XP"
        type="button">
<span class="quest-sidebar-icon">@if($questIcon)<img src="{{ $questIcon }}" alt="">@else{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($questName,0,1)) }}@endif</span>
<span><strong>{{ $questName }}</strong><small>{{ $state['done'] ? $t('Abgeschlossen', 'Completed') : $state['count'].' / '.$state['target'] }}</small><i><b style="width:{{ $state['percent'] }}%"></b></i></span>
<em>{{ $state['percent'] }}%</em>
</button>
@empty
<div class="gamification-empty-small">{{ $t('Zurzeit sind keine Quests aktiv.', 'There are no active quests right now.') }}</div>
@endforelse
</div>
<footer><div><span>{{ $t('Quest-Fortschritt', 'Quest progress') }}</span><strong>{{ $completedQuestsCount }} / {{ $questsCount }} {{ $t('abgeschlossen', 'completed') }}</strong></div><i><b style="width:{{ $questMasterPercent }}%"></b></i></footer>
</article>

<article class="gamification-streak-card">
<header><div><span>{{ $t('AKTUELLE SERIE', 'CURRENT STREAK') }}</span><h2>{{ $streakCurrent }} {{ $streakCurrent === 1 ? $t('Tag aktiv', 'active day') : $t('Tage aktiv', 'active days') }}</h2></div><strong>{{ $streakCurrent }}</strong></header>
<div class="streak-days">
@foreach($weeklyXpDays as $index => $day)
@php
    $dateKey = $day['date']->toDateString();
    $claimed = $streakClaims->has($dateKey);
@endphp
<span class="{{ $claimed ? 'done' : ($day['is_today'] ? 'today' : '') }}">{{ $dayLabels[$index] }}</span>
@endforeach
</div>
<p>{{ ($dailyStreak['claimed_today'] ?? false) ? $t('Die heutige Login-Belohnung wurde bereits eingesammelt.', 'Today’s login reward has already been collected.') : $t('Die tägliche Belohnung kannst du im Feed einsammeln.', 'You can collect the daily reward in the feed.') }}</p>
<button data-gamification-toast="{{ $t('Die Login-Serie wird täglich über den Feed fortgesetzt.', 'The login streak continues daily through the feed.') }}" type="button">{{ $t('Login-Serie ansehen', 'View login streak') }}</button>
</article>
</aside>

<section class="gamification-main-card">
<header class="gamification-main-head">
<div><span>{{ $t('DEIN FORTSCHRITT', 'YOUR PROGRESS') }}</span><h2 id="gamificationPanelTitle">{{ $t('Übersicht', 'Overview') }}</h2></div>
<nav aria-label="Gamification" class="gamification-tabs">
<button class="active" data-gamification-tab="overview" aria-selected="true" type="button">{{ $t('Übersicht', 'Overview') }}</button>
<button data-gamification-tab="badges" aria-selected="false" type="button">Badges <i>{{ $availableBadgesCount }}</i></button>
<button data-gamification-tab="quests" aria-selected="false" type="button">Quests <i>{{ $questsCount }}</i></button>
<button data-gamification-tab="history" aria-selected="false" type="button">{{ $t('XP-Verlauf', 'XP history') }}</button>
</nav>
</header>
<div class="gamification-panels">
<section class="gamification-panel active" data-gamification-panel="overview">
<article class="level-overview-card">
<div class="level-overview-copy"><span>{{ $t('LEVEL-FORTSCHRITT', 'LEVEL PROGRESS') }}</span><h3>Level {{ $level }}</h3><p>{{ $t('Jede echte Community-Aktion bringt dich näher an das nächste Level.', 'Every real community action moves you closer to the next level.') }}</p></div>
<div class="level-overview-value"><strong>{{ $levelProgress }}%</strong><span>{{ $number((int) $xpToNextLevel) }} XP {{ $t('fehlen', 'remaining') }}</span></div>
<div class="level-progress-track"><i style="width:{{ $levelProgress }}%"></i></div>
<div class="level-overview-meta"><span>{{ $number((int) $levelStartXp) }} XP</span><span>{{ $number((int) $nextLevelXp) }} XP</span></div>
</article>

<section class="weekly-activity-card">
<header><div><span>{{ $t('DIESE WOCHE', 'THIS WEEK') }}</span><h3>{{ $t('XP-Aktivität', 'XP activity') }}</h3></div><strong>+{{ $number($weeklyXpTotal) }} XP</strong></header>
<div class="weekly-bars">
@foreach($weeklyXpDays as $index => $day)
@php
    $height = $day['points'] > 0 ? max(8, (int) round(($day['points'] / $maxDailyXp) * 100)) : 5;
@endphp
<article class="{{ $day['is_today'] ? 'today' : '' }} {{ $day['is_future'] ? 'future' : '' }}"><div><i style="height:{{ $height }}%"></i></div><span>{{ $dayLabels[$index] }}</span><small>{{ $day['points'] > 0 ? $number($day['points']).' XP' : ($day['is_future'] ? $t('offen', 'open') : '0 XP') }}</small></article>
@endforeach
</div>
<div class="weekly-goal"><div><span>{{ $t('Wochenziel', 'Weekly goal') }}</span><strong>{{ $number($weeklyXpTotal) }} / {{ $number($weeklyXpGoal) }} XP</strong></div><i><b style="width:{{ $weeklyGoalPercent }}%"></b></i></div>
</section>

@if($selectedQuest)
<section class="selected-quest-card">
<header><div><span>{{ $t('AUSGEWÄHLTE QUEST', 'SELECTED QUEST') }}</span><h3 id="selectedQuestTitle">{{ $selectedQuest->displayName($locale) }}</h3></div><strong id="selectedQuestReward">+{{ $number((int) $selectedQuest->xp_reward) }} XP</strong></header>
<p id="selectedQuestDescription">{{ $selectedQuest->displayDescription($locale) }}</p>
<div class="selected-quest-progress"><div><span id="selectedQuestCount">{{ $selectedState['count'] }} / {{ $selectedState['target'] }}</span><strong id="selectedQuestProgress">{{ $selectedState['percent'] }}%</strong></div><i><b id="selectedQuestBar" style="width:{{ $selectedState['percent'] }}%"></b></i></div>
<footer><span>{{ $t('Die Belohnung wird nach Abschluss automatisch gutgeschrieben.', 'The reward is credited automatically after completion.') }}</span><button data-open-panel="quests" type="button">{{ $t('Details ansehen', 'View details') }}</button></footer>
</section>
@endif

<section class="milestones-section">
<header><div><span>{{ $t('NÄCHSTE ZIELE', 'NEXT GOALS') }}</span><h3>{{ $t('Meilensteine', 'Milestones') }}</h3></div><small>{{ $t('Fortschritt wird automatisch aktualisiert.', 'Progress updates automatically.') }}</small></header>
<div class="milestone-grid">
<article><span class="milestone-icon">{{ $level + 1 }}</span><div><strong>Level {{ $level + 1 }}</strong><small>{{ $number((int) $xpToNextLevel) }} XP {{ $t('fehlen', 'remaining') }}</small></div><i><b style="width:{{ $levelProgress }}%"></b></i></article>
<article><span class="milestone-icon">B</span><div><strong>{{ $nextLockedBadge ? $nextLockedBadge->displayName($locale) : $t('Alle Badges', 'All badges') }}</strong><small>{{ $nextLockedBadge ? $nextBadgePercent.'%' : $t('Freigeschaltet', 'Unlocked') }}</small></div><i><b style="width:{{ $nextBadgePercent }}%"></b></i></article>
<article><span class="milestone-icon">Q</span><div><strong>{{ $t('Quest-Meister', 'Quest master') }}</strong><small>{{ $completedQuestsCount }} / {{ $questsCount }} {{ $t('erledigt', 'completed') }}</small></div><i><b style="width:{{ $questMasterPercent }}%"></b></i></article>
</div>
</section>
</section>

<section class="gamification-panel" data-gamification-panel="badges" hidden>
<div class="panel-intro"><div><span>{{ $t('BADGE-SAMMLUNG', 'BADGE COLLECTION') }}</span><h3>{{ $unlockedBadgesCount }} / {{ $availableBadgesCount }} {{ $t('freigeschaltet', 'unlocked') }}</h3></div><p>{{ $t('Badges dokumentieren deine wichtigsten Community-Erfolge.', 'Badges document your most important community achievements.') }}</p></div>
<div class="badge-collection-grid">
@forelse($availableBadges as $badge)
@php
    $isUnlocked = $unlockedBadges->has($badge->id);
    $badgeIcon = $badge->iconUrl();
    $badgeQuest = $quests->first(fn ($quest) => (string) $quest->badge_slug === (string) $badge->slug);
    $badgeProgress = $badgeQuest ? $progressByQuestId->get($badgeQuest->id) : null;
    $badgeTarget = max(1, (int) ($badgeQuest?->target_count ?? 1));
    $badgeCount = min((int) ($badgeProgress?->progress_count ?? 0), $badgeTarget);
    $badgePercent = $isUnlocked ? 100 : ($badgeQuest ? (int) min(100, round(($badgeCount / $badgeTarget) * 100)) : 0);
@endphp
<article class="{{ $isUnlocked ? 'unlocked' : ($badgePercent > 0 ? 'progress' : 'locked') }}">
<span>@if($badgeIcon)<img src="{{ $badgeIcon }}" alt="">@else{{ $badgeSymbol($badge) }}@endif</span>
<div><strong>{{ $badge->displayName($locale) }}</strong><small>{{ $badge->displayDescription($locale) }}</small></div>
<em>{{ $isUnlocked ? $t('Freigeschaltet', 'Unlocked') : ($badgePercent > 0 ? $badgePercent.'%' : $t('Gesperrt', 'Locked')) }}</em>
@if(!$isUnlocked && $badgePercent > 0)<i><b style="width:{{ $badgePercent }}%"></b></i>@endif
</article>
@empty
<div class="gamification-empty-card">{{ $t('Noch keine Badges eingerichtet.', 'No badges have been configured yet.') }}</div>
@endforelse
</div>
</section>

<section class="gamification-panel" data-gamification-panel="quests" hidden>
<div class="panel-intro"><div><span>QUESTS</span><h3>{{ $completedQuestsCount }} / {{ $questsCount }} {{ $t('abgeschlossen', 'completed') }}</h3></div><p>{{ $t('Aufgaben führen durch die wichtigsten Community-Bereiche.', 'Tasks guide you through the most important community areas.') }}</p></div>
<div class="quest-detail-grid">
@forelse($quests as $quest)
@php
    $state = $questData($quest);
    $questIcon = $quest->iconUrl();
    $questName = $quest->displayName($locale);
@endphp
<article class="{{ $state['done'] ? 'completed' : '' }}">
<span>@if($questIcon)<img src="{{ $questIcon }}" alt="">@else{{ $state['done'] ? '✓' : \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($questName,0,1)) }}@endif</span>
<div><strong>{{ $questName }}</strong><small>{{ $quest->displayDescription($locale) }}</small></div>
<em>{{ $state['count'] }} / {{ $state['target'] }} · +{{ $number((int) $quest->xp_reward) }} XP</em>
<i><b style="width:{{ $state['percent'] }}%"></b></i>
</article>
@empty
<div class="gamification-empty-card">{{ $t('Zurzeit sind keine Quests eingerichtet.', 'No quests are configured right now.') }}</div>
@endforelse
</div>
</section>

<section class="gamification-panel" data-gamification-panel="history" hidden>
<div class="panel-intro"><div><span>{{ $t('LETZTE SCHRITTE', 'LATEST STEPS') }}</span><h3>{{ $t('XP-Verlauf', 'XP history') }}</h3></div><p>{{ $t('Deine letzten echten XP-Ereignisse aus allen Community-Bereichen.', 'Your latest real XP events from all community areas.') }}</p></div>
<div class="xp-history-table">
<header><span>{{ $t('Aktion', 'Action') }}</span><span>{{ $t('Bereich', 'Area') }}</span><span>{{ $t('Zeit', 'Time') }}</span><span>XP</span></header>
@forelse($recentEvents as $event)
@php $area = $eventArea((string) $event->action); @endphp
<article><div><i>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($area,0,1)) }}</i><span><strong>{{ $eventTitle($event) }}</strong><small>{{ str_replace('_', ' ', (string) $event->action) }}</small></span></div><span>{{ $area }}</span><span>{{ $event->created_at?->diffForHumans() }}</span><strong>+{{ $number((int) $event->points) }}</strong></article>
@empty
<div class="gamification-empty-card">{{ $t('Noch keine XP-Ereignisse vorhanden.', 'There are no XP events yet.') }}</div>
@endforelse
</div>
</section>
</div>
</section>

<aside class="gamification-profile-sidebar">
<article class="gamification-profile-card">
<div class="gamification-profile-cover"><span>{{ $t('DEIN PROFIL', 'YOUR PROFILE') }}</span><a aria-label="{{ $t('Profil öffnen', 'Open profile') }}" href="{{ route('profile.show') }}"><svg><use href="#i-arrow"></use></svg></a></div>
<img alt="{{ $displayName }}" src="{{ $avatarUrl }}">
<div class="gamification-profile-copy"><h2>{{ $displayName }}</h2><span>{{ $handle }} · {{ $onlineLabel }}</span></div>
<div class="gamification-level-ring" style="--level-progress:{{ $levelProgress }}"><div><strong>{{ $level }}</strong><span>Level</span></div></div>
<div class="gamification-profile-stats"><article><strong>{{ $number((int) $user->xp_total) }}</strong><span>XP</span></article><article><strong>{{ $unlockedBadgesCount }}</strong><span>Badges</span></article><article><strong>{{ $completedQuestsCount }}</strong><span>Quests</span></article></div>
<div class="gamification-profile-progress"><div><span>{{ $t('Bis Level', 'Until level') }} {{ $level + 1 }}</span><strong>{{ $levelProgress }}%</strong></div><i><b style="width:{{ $levelProgress }}%"></b></i><small>{{ $number((int) $xpToNextLevel) }} XP {{ $t('bis zum nächsten Level.', 'until the next level.') }}</small></div>
</article>

<article class="top-badges-card">
<header><div><span>TOP-BADGES</span><h2>{{ $t('Deine Auswahl', 'Your selection') }}</h2></div><button data-open-panel="badges" type="button">{{ $t('Alle', 'All') }}</button></header>
<div class="top-badge-list">
@forelse($topBadges as $badge)
@php $badgeIcon = $badge->iconUrl(); @endphp
<article><span>@if($badgeIcon)<img src="{{ $badgeIcon }}" alt="">@else{{ $badgeSymbol($badge) }}@endif</span><div><strong>{{ $badge->displayName($locale) }}</strong><small>{{ $badge->displayDescription($locale) }}</small></div></article>
@empty
<div class="gamification-empty-small">{{ $t('Noch keine Badges freigeschaltet.', 'No badges unlocked yet.') }}</div>
@endforelse
</div>
</article>

<article class="daily-xp-card">
<header><span>{{ $t('HEUTE', 'TODAY') }}</span><strong>+{{ $number((int) $todayXpByArea->sum('points')) }} XP</strong></header>
<div>
@forelse($todayXpByArea->take(4) as $area)
@php $areaPercent = max(5, min(100, (int) round(($area['points'] / max(1, (int) $todayXpByArea->max('points'))) * 100))); @endphp
<span>{{ $area['area'] }}</span><i><b style="width:{{ $areaPercent }}%"></b></i><strong>+{{ $number((int) $area['points']) }}</strong>
@empty
<span>{{ $t('Noch keine XP', 'No XP yet') }}</span><i><b style="width:0%"></b></i><strong>0</strong>
@endforelse
</div>
<button data-open-panel="history" type="button">{{ $t('XP-Verlauf öffnen', 'Open XP history') }}</button>
</article>
</aside>
</section>
</div>
</section>
<div class="toast" id="toast"></div>
</main>
<script>
window.HNT_DASHBOARD_HEADER_ENDPOINT = @json(route('feed.index'));
window.HNT_PREVIEW_LOCALE = @json(str_replace('_', '-', app()->getLocale()));
window.HNT_PREVIEW_USER_ID = @json(auth()->id());
window.HNT_PREVIEW_LIVE_BADGES = {
  endpoint: @json(route('socialite.header.live-badges')),
  notificationsEndpoint: @json(route('socialite.header.notifications')),
  messagesEndpoint: @json(route('socialite.header.messages')),
  friendRequestsEndpoint: @json(route('socialite.header.friend-requests')),
  interval: 8000,
  shellInterval: 5000,
  chatTabInterval: 4500
};
</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-gamification/gamification-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-gamification/gamification-live.js')) ?: time() }}" defer></script>
</body>
</html>
