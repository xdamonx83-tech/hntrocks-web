<?php

namespace App\Support;

use App\Models\Quest;
use App\Models\User;
use App\Services\GamificationService;

class DashboardProgressPayload
{
    public function __construct(private readonly GamificationService $gamification)
    {
    }

    public function forUser(User $user): array
    {
        $user->loadMissing(['questProgress.quest']);
        $now = now();
        $locale = app()->getLocale() === 'en' ? 'en' : 'de';

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

        $cards = $contracts->map(function (Quest $contract) use ($progressByQuestId, $locale): array {
            $progress = $progressByQuestId->get($contract->id);
            $target = max(1, (int) $contract->target_count);
            $current = min($target, max(0, (int) ($progress?->progress_count ?? 0)));
            $completed = filled($progress?->completed_at);
            $percent = min(100, (int) round(($current / $target) * 100));
            $status = $completed ? __('ui.contracts_done') : __('ui.contracts_open');

            if ($contract->contract_ends_at) {
                $status .= ' · '.__('ui.contracts_until').' '.$contract->contract_ends_at->format($locale === 'de' ? 'd.m.Y' : 'M j, Y');
            }

            return [
                'id' => (int) $contract->id,
                'name' => $contract->displayName($locale),
                'action' => $contract->actionLabel(),
                'reward' => '+'.(int) $contract->xp_reward.' XP',
                'status' => $status,
                'completed' => $completed,
                'current' => $current,
                'target' => $target,
                'percent' => $percent,
            ];
        })->values();

        $completed = $cards->where('completed', true)->count();
        $total = $cards->count();
        $availableXp = (int) $contracts->sum(fn (Quest $contract): int => (int) $contract->xp_reward);
        $claimedXp = (int) $contracts->sum(function (Quest $contract) use ($progressByQuestId): int {
            return filled($progressByQuestId->get($contract->id)?->completed_at)
                ? (int) $contract->xp_reward
                : 0;
        });

        return [
            'cards' => $cards->all(),
            'completed' => $completed,
            'total' => $total,
            'open' => max(0, $total - $completed),
            'completion_percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'claimed_xp' => $claimedXp,
            'available_xp' => $availableXp,
            'level_progress' => max(0, min(100, (int) $this->gamification->progressPercent($user))),
            'next_open' => $cards->firstWhere('completed', false),
        ];
    }
}
