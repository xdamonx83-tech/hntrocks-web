<?php

namespace App\Http\Controllers\Gamification;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\CrownDailyStreakClaim;
use App\Models\Quest;
use App\Models\XpEvent;
use App\Services\Economy\CrownDailyStreakService;
use App\Services\GamificationService;
use App\Support\HntTheme;
use App\Support\ReworkFeedSidebar;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GamificationController extends Controller
{
    public function index(
        Request $request,
        GamificationService $gamification,
        CrownDailyStreakService $dailyStreakService
    ): View {
        $user = $request->user()->loadMissing([
            'badges',
            'questProgress.quest',
            'profile',
            'crownWallet',
        ]);

        $gamification->syncBadges($user);
        $user = $user->fresh([
            'badges',
            'questProgress.quest',
            'profile',
            'crownWallet',
        ]) ?? $user;

        $recentEvents = $user->xpEvents()
            ->latest()
            ->limit(30)
            ->get();

        $availableBadges = Badge::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $quests = Quest::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $progressByQuestId = $user->questProgress->keyBy('quest_id');
        $activeQuests = $quests
            ->filter(fn (Quest $quest): bool => blank($progressByQuestId->get($quest->id)?->completed_at))
            ->values();
        $completedQuests = $quests
            ->filter(fn (Quest $quest): bool => filled($progressByQuestId->get($quest->id)?->completed_at))
            ->values();

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $weeklyEvents = $user->xpEvents()
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->oldest()
            ->get();

        $weeklyXpDays = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $weeklyEvents): array {
            $date = $weekStart->copy()->addDays($offset);

            return [
                'date' => $date,
                'points' => (int) $weeklyEvents
                    ->filter(fn (XpEvent $event): bool => $event->created_at?->isSameDay($date) ?? false)
                    ->sum('points'),
                'is_today' => $date->isToday(),
                'is_future' => $date->isFuture(),
            ];
        });

        $todayEvents = $weeklyEvents
            ->filter(fn (XpEvent $event): bool => $event->created_at?->isToday() ?? false)
            ->values();
        $todayXpByArea = $todayEvents
            ->groupBy(fn (XpEvent $event): string => $this->areaForAction((string) $event->action))
            ->map(fn (Collection $events, string $area): array => [
                'area' => $area,
                'points' => (int) $events->sum('points'),
            ])
            ->sortByDesc('points')
            ->values();

        $dailyStreak = $dailyStreakService->status($user);
        $streakClaims = collect();
        if ((bool) ($dailyStreak['enabled'] ?? false)) {
            $streakClaims = CrownDailyStreakClaim::query()
                ->where('user_id', $user->id)
                ->whereBetween('claim_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->get()
                ->keyBy(fn (CrownDailyStreakClaim $claim): string => $claim->claim_date->toDateString());
        }

        $level = max(1, (int) ($user->level ?: 1));
        $levelStartXp = $gamification->xpForCurrentLevel($level);
        $nextLevelXp = $gamification->xpForNextLevel($level);
        $levelProgressPercent = $gamification->progressPercent($user);
        $xpToNextLevel = max(0, $nextLevelXp - (int) $user->xp_total);
        $weeklyXpTotal = (int) $weeklyEvents->sum('points');
        $weeklyXpGoal = max(100, (int) config('hunthub.gamification.weekly_xp_goal', 500));

        $sidebarData = ReworkFeedSidebar::forViewer($user);

        return view(HntTheme::resolve('gamification.index'), [
            'user' => $user,
            'recentEvents' => $recentEvents,
            'availableBadges' => $availableBadges,
            'quests' => $quests,
            'activeQuests' => $activeQuests,
            'completedQuests' => $completedQuests,
            'progressByQuestId' => $progressByQuestId,
            'levelProgressPercent' => $levelProgressPercent,
            'levelStartXp' => $levelStartXp,
            'nextLevelXp' => $nextLevelXp,
            'xpToNextLevel' => $xpToNextLevel,
            'weeklyXpDays' => $weeklyXpDays,
            'weeklyXpTotal' => $weeklyXpTotal,
            'weeklyXpGoal' => $weeklyXpGoal,
            'todayXpByArea' => $todayXpByArea,
            'dailyStreak' => $dailyStreak,
            'streakClaims' => $streakClaims,
            'socialiteMembers' => $sidebarData['members'],
            'socialiteProfileStats' => $sidebarData['profileStats'],
            'socialiteCrownsSummary' => $sidebarData['crownsSummary'],
            'socialiteHighlightTopPost' => $sidebarData['highlightTopPost'],
            'socialiteHighlightLfg' => $sidebarData['highlightLfg'],
            'socialiteHighlightCup' => $sidebarData['highlightCup'],
        ]);
    }

    private function areaForAction(string $action): string
    {
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
    }
}
