<?php

namespace App\Http\Controllers\Gamification;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Quest;
use App\Services\GamificationService;
use App\Support\HntTheme;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GamificationController extends Controller
{
    public function index(Request $request, GamificationService $gamification): View
    {
        $user = $request->user()->loadMissing(['badges', 'questProgress.quest']);
        $gamification->syncBadges($user);
        $user = $user->fresh(['badges', 'questProgress.quest']) ?? $user;

        $recentEvents = $user->xpEvents()
            ->latest()
            ->limit(20)
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

        return view(HntTheme::resolve('gamification.index'), [
            'user' => $user,
            'recentEvents' => $recentEvents,
            'availableBadges' => $availableBadges,
            'quests' => $quests,
            'progressByQuestId' => $progressByQuestId,
            'levelProgressPercent' => $gamification->progressPercent($user),
            'xpToNextLevel' => $user->xpToNextLevel(),
        ]);
    }
}
