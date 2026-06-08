<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Quest;
use App\Models\QuestProgress;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiContractsController extends Controller
{
    public function index(Request $request, GamificationService $gamification): JsonResponse
    {
        $user = $request->user();
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
            ->with(['progress' => function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            }])
            ->orderBy('sort_order')
            ->orderBy('contract_ends_at')
            ->orderBy('name')
            ->get();

        $completedCount = $contracts->filter(function (Quest $contract): bool {
            return filled($contract->progress->first()?->completed_at);
        })->count();

        $availableXp = $contracts->sum(fn (Quest $contract): int => (int) $contract->xp_reward);
        $claimedXp = $contracts->sum(function (Quest $contract): int {
            return filled($contract->progress->first()?->completed_at) ? (int) $contract->xp_reward : 0;
        });
        $completionPercent = $contracts->isNotEmpty()
            ? (int) round(($completedCount / max(1, $contracts->count())) * 100)
            : 0;

        return response()->json([
            'data' => [
                'contracts' => $contracts->map(fn (Quest $contract): array => $this->contractPayload($contract))->values(),
                'stats' => [
                    'total' => $contracts->count(),
                    'completed' => $completedCount,
                    'open' => max(0, $contracts->count() - $completedCount),
                    'available_xp' => $availableXp,
                    'claimed_xp' => $claimedXp,
                    'completion_percent' => $completionPercent,
                ],
                'profile' => [
                    'level' => (int) ($user->level ?: 1),
                    'xp_total' => (int) ($user->xp_total ?: 0),
                    'level_progress_percent' => $gamification->progressPercent($user),
                ],
            ],
        ]);
    }

    private function contractPayload(Quest $contract): array
    {
        /** @var QuestProgress|null $progress */
        $progress = $contract->progress->first();
        $target = max(1, (int) $contract->target_count);
        $current = min($target, max(0, (int) ($progress?->progress_count ?? 0)));
        $percent = min(100, (int) round(($current / $target) * 100));
        $completedAt = $progress?->completed_at;

        return [
            'id' => (int) $contract->id,
            'slug' => (string) $contract->slug,
            'name' => (string) $contract->name,
            'category' => (string) $contract->category,
            'action' => (string) $contract->action,
            'action_label' => $contract->actionLabel(),
            'description' => (string) ($contract->description ?: ''),
            'period' => (string) $contract->period,
            'period_label' => $contract->periodLabel(),
            'status' => $contract->contractStatus(),
            'status_label' => $contract->contractStatusLabel(),
            'target_count' => $target,
            'progress_count' => $current,
            'progress_percent' => $percent,
            'is_completed' => filled($completedAt),
            'completed_at' => $completedAt?->toISOString(),
            'xp_reward' => (int) $contract->xp_reward,
            'badge_slug' => (string) ($contract->badge_slug ?: ''),
            'icon' => (string) ($contract->icon ?: ''),
            'icon_url' => $contract->iconUrl(),
            'starts_at' => $contract->contract_starts_at?->toISOString(),
            'ends_at' => $contract->contract_ends_at?->toISOString(),
        ];
    }
}
