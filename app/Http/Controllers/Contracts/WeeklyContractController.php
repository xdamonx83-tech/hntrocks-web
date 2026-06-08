<?php

namespace App\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use App\Models\Quest;
use App\Services\GamificationService;
use App\Support\HntTheme;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeeklyContractController extends Controller
{
    public function index(Request $request, GamificationService $gamification): View
    {
        $user = $request->user()->loadMissing(['questProgress.quest']);
        $now = now();

        $contracts = Quest::query()
            ->where('is_weekly_contract', true)
            ->where('is_active', true)
            ->where(function ($query) use ($now): void {
                $query->whereNull('contract_starts_at')
                    ->orWhere('contract_starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('contract_ends_at')
                    ->orWhere('contract_ends_at', '>=', $now);
            })
            ->orderBy('sort_order')
            ->orderBy('contract_ends_at')
            ->orderBy('name')
            ->get();

        $progressByQuestId = $user->questProgress->keyBy('quest_id');
        $completedCount = $contracts->filter(function (Quest $contract) use ($progressByQuestId): bool {
            return filled($progressByQuestId->get($contract->id)?->completed_at);
        })->count();

        $availableXp = $contracts->sum(fn (Quest $contract): int => (int) $contract->xp_reward);
        $claimedXp = $contracts->sum(function (Quest $contract) use ($progressByQuestId): int {
            return filled($progressByQuestId->get($contract->id)?->completed_at) ? (int) $contract->xp_reward : 0;
        });

        return HntTheme::view('contracts.index', [
            'user' => $user,
            'contracts' => $contracts,
            'progressByQuestId' => $progressByQuestId,
            'completedCount' => $completedCount,
            'availableXp' => $availableXp,
            'claimedXp' => $claimedXp,
            'levelProgressPercent' => $gamification->progressPercent($user),
        ]);
    }
}
