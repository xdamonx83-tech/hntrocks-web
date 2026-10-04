<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Quest;
use App\Models\QuestProgress;
use App\Services\GamificationService;
use App\Support\ProfileCompletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiGamificationController extends Controller
{
    /**
     * Hunter progression: read-only overview for the React interface.
     * Quest completion and badge awards continue to be handled by the existing
     * GamificationService hooks, never by the dashboard request itself.
     */
    public function index(Request $request, GamificationService $gamification): JsonResponse
    {
        $user = $request->user()->loadMissing(['profile', 'badges', 'questProgress']);
        $ownedBadges = $user->badges->keyBy('id');
        $progress = $user->questProgress->keyBy('quest_id');

        $requestedLocale = strtolower((string) ($request->query('locale') ?: $request->header('X-HNT-Locale', app()->getLocale())));
        $locale = str_starts_with($requestedLocale, 'de') ? 'de' : 'en';

        $badges = Badge::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Badge $badge) use ($ownedBadges, $locale): array {
                $owned = $ownedBadges->get($badge->id);

                return [
                    'id' => (int) $badge->id,
                    'slug' => (string) $badge->slug,
                    'name' => $badge->displayName($locale),
                    'description' => $badge->displayDescription($locale),
                    'category' => (string) ($badge->category ?: ''),
                    'rarity' => (string) ($badge->rarity ?: 'common'),
                    'icon_url' => $badge->iconUrl(),
                    'xp_reward' => (int) ($badge->xp_reward ?? 0),
                    'unlocked' => $owned !== null,
                    'unlocked_at' => $owned?->pivot?->awarded_at
                        ? (string) $owned->pivot->awarded_at
                        : null,
                ];
            })
            ->values();

        $quests = Quest::query()
            ->where('is_active', true)
            ->where('is_weekly_contract', false)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Quest $quest) use ($progress, $locale): array {
                /** @var QuestProgress|null $state */
                $state = $progress->get($quest->id);
                $target = max(1, (int) $quest->target_count);
                $current = min($target, max(0, (int) ($state?->progress_count ?? 0)));

                return [
                    'id' => (int) $quest->id,
                    'slug' => (string) $quest->slug,
                    'name' => $quest->displayName($locale),
                    'description' => $quest->displayDescription($locale),
                    'category' => (string) ($quest->category ?: ''),
                    'period' => (string) ($quest->period ?: 'once'),
                    'target_count' => $target,
                    'progress_count' => $current,
                    'progress_percent' => min(100, (int) round(($current / $target) * 100)),
                    'completed' => $state?->completed_at !== null,
                    'completed_at' => $state?->completed_at?->toISOString(),
                    'xp_reward' => (int) $quest->xp_reward,
                    'badge_slug' => $quest->badge_slug,
                    'icon_url' => $quest->iconUrl(),
                ];
            })
            ->values();

        $xpEvents = $user->xpEvents()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($event): array => [
                'id' => (int) $event->id,
                'action' => (string) $event->action,
                'description' => (string) ($event->description ?: ''),
                'points' => (int) $event->points,
                'created_at' => $event->created_at?->toISOString(),
            ])
            ->values();

        $level = max(1, (int) ($user->level ?: 1));
        $xp = max(0, (int) ($user->xp_total ?: 0));

        return response()->json([
            'data' => [
                'profile' => [
                    'level' => $level,
                    'xp_total' => $xp,
                    'xp_level_start' => $gamification->xpForCurrentLevel($level),
                    'xp_next_level' => $gamification->xpForNextLevel($level),
                    'xp_to_next_level' => max(0, $gamification->xpForNextLevel($level) - $xp),
                    'level_progress_percent' => $gamification->progressPercent($user),
                    'trust_score' => max(0, (int) ($user->trust_score ?: 0)),
                    'profile_completion_percent' => (int) ProfileCompletion::score($user),
                ],
                'badges' => $badges,
                'quests' => $quests,
                'xp_events' => $xpEvents,
                'stats' => [
                    'badges_total' => $badges->count(),
                    'badges_unlocked' => $badges->where('unlocked', true)->count(),
                    'quests_total' => $quests->count(),
                    'quests_completed' => $quests->where('completed', true)->count(),
                ],
            ],
        ]);
    }
}
