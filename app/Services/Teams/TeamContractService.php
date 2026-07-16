<?php

namespace App\Services\Teams;

use App\Models\Team;
use App\Models\TeamContract;
use App\Models\TeamContractContribution;
use App\Models\TeamContractTemplate;
use App\Models\TeamRewardGrant;
use App\Models\User;
use App\Services\Economy\CrownsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamContractService
{
    public function __construct(
        private readonly TeamProgressionService $progression,
        private readonly TeamParticipationService $participation,
        private readonly CrownsService $crowns,
    ) {}

    public function activate(Team $team, TeamContractTemplate $template, User $actor): TeamContract
    {
        return DB::transaction(function () use ($team, $template, $actor): TeamContract {
            Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();
            $template = TeamContractTemplate::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();

            if (! $template->is_active) {
                throw ValidationException::withMessages(['template' => 'Dieser Team-Auftrag ist nicht aktiv.']);
            }

            $activeCount = TeamContract::query()->where('team_id', $team->id)->where('status', TeamContract::STATUS_ACTIVE)->count();
            if ($activeCount >= max(1, (int) config('team_progression.contracts.max_active', 1))) {
                throw ValidationException::withMessages(['template' => 'Das Team hat bereits die maximal erlaubte Zahl aktiver Aufträge.']);
            }

            $previous = TeamContract::query()->where('team_id', $team->id)->where('template_id', $template->id)->latest('id')->first();
            if ($previous && ! $template->is_repeatable) {
                throw ValidationException::withMessages(['template' => 'Dieser Team-Auftrag kann nicht wiederholt werden.']);
            }

            if ($previous?->completed_at && $template->cooldown_days && $previous->completed_at->addDays($template->cooldown_days)->isFuture()) {
                throw ValidationException::withMessages(['template' => 'Der Cooldown dieses Team-Auftrags ist noch aktiv.']);
            }

            $startsAt = now();

            return TeamContract::query()->create([
                'team_id' => $team->id,
                'template_id' => $template->id,
                'activated_by' => $actor->id,
                'status' => TeamContract::STATUS_ACTIVE,
                'name_key' => $template->name_key,
                'description_key' => $template->description_key,
                'category' => $template->category,
                'metric' => $template->metric,
                'progress_value' => 0,
                'target_value' => $template->target_value,
                'minimum_contributors' => $template->minimum_contributors,
                'contributors_count' => 0,
                'team_xp_reward' => $template->team_xp_reward,
                'rocks_reward' => $template->rocks_reward,
                'configuration' => $template->configuration,
                'starts_at' => $startsAt,
                'ends_at' => $template->duration_days ? $startsAt->copy()->addDays($template->duration_days) : null,
            ]);
        });
    }

    public function cancel(Team $team, TeamContract $contract): TeamContract
    {
        return DB::transaction(function () use ($team, $contract): TeamContract {
            Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();
            $contract = TeamContract::query()->whereKey($contract->id)->where('team_id', $team->id)->lockForUpdate()->firstOrFail();

            if ($contract->status !== TeamContract::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['contract' => 'Nur ein aktiver Team-Auftrag kann abgebrochen werden.']);
            }

            $contract->forceFill(['status' => TeamContract::STATUS_CANCELLED, 'cancelled_at' => now()])->save();

            return $contract;
        });
    }

    public function recordActivity(Team $team, string $eventType, User $contributor, string $sourceKey, int $amount = 1, array $metadata = []): ?TeamContract
    {
        if ($amount <= 0 || ! $team->activeMembers()->where('user_id', $contributor->id)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($team, $eventType, $contributor, $sourceKey, $amount, $metadata): ?TeamContract {
            Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();
            $contract = TeamContract::query()
                ->where('team_id', $team->id)
                ->where('status', TeamContract::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $contract || $contract->metric !== $eventType) {
                return null;
            }

            if ($contract->ends_at?->isPast()) {
                $contract->forceFill(['status' => TeamContract::STATUS_FAILED])->save();
                return $contract;
            }

            $durableKey = "team:{$team->id}:{$sourceKey}";
            if (TeamContractContribution::query()->where('team_contract_id', $contract->id)->where('source_key', $durableKey)->exists()) {
                return $contract;
            }

            TeamContractContribution::query()->create([
                'team_contract_id' => $contract->id,
                'user_id' => $contributor->id,
                'event_type' => $eventType,
                'source_key' => $durableKey,
                'amount' => $amount,
                'metadata' => $metadata ?: null,
            ]);

            $progress = (int) TeamContractContribution::query()->where('team_contract_id', $contract->id)->sum('amount');
            $contributors = TeamContractContribution::query()->where('team_contract_id', $contract->id)->distinct()->count('user_id');
            $contract->forceFill([
                'progress_value' => min($progress, (int) $contract->target_value),
                'contributors_count' => $contributors,
            ])->save();

            if ($progress >= (int) $contract->target_value && $contributors >= (int) $contract->minimum_contributors) {
                $this->completeLocked($team, $contract);
            }

            return $contract->fresh();
        });
    }

    private function completeLocked(Team $team, TeamContract $contract): void
    {
        if ($contract->status !== TeamContract::STATUS_ACTIVE) {
            return;
        }

        $contract->forceFill(['status' => TeamContract::STATUS_COMPLETED, 'completed_at' => now()])->save();
        $actor = User::query()->find($contract->activated_by);

        $this->progression->award(
            $team,
            (int) $contract->team_xp_reward,
            'team_contract_completed',
            "team-contract:{$contract->id}",
            $actor,
            ['team_contract_id' => $contract->id]
        );

        $minimum = max(1, (int) config('team_progression.contracts.minimum_qualifying_contribution', 1));
        $qualifiedRows = TeamContractContribution::query()
            ->where('team_contract_id', $contract->id)
            ->selectRaw('user_id, SUM(amount) as contribution_total')
            ->groupBy('user_id')
            ->havingRaw('SUM(amount) >= ?', [$minimum])
            ->get();

        $contributionTotals = $qualifiedRows->pluck('contribution_total', 'user_id');
        $qualifiedIds = $qualifiedRows->pluck('user_id');
        TeamContractContribution::query()->where('team_contract_id', $contract->id)->get()->each(function (TeamContractContribution $contribution) use ($qualifiedIds): void {
            foreach ((array) data_get($contribution->metadata, 'qualified_user_ids', []) as $userId) {
                $qualifiedIds->push((int) $userId);
            }
        });

        foreach ($qualifiedIds->unique()->values() as $userId) {
            $user = User::query()->find($userId);
            if (! $user || ! $team->activeMembers()->where('user_id', $user->id)->exists()) {
                continue;
            }

            $this->participation->award(
                $team,
                $user,
                'team_contract_contributed',
                "team-contract-participation:{$contract->id}:{$user->id}",
                metadata: ['team_contract_id' => $contract->id, 'contribution_total' => (int) ($contributionTotals[$userId] ?? 0)]
            );

            if ((int) $contract->rocks_reward <= 0 || TeamRewardGrant::query()->where('team_contract_id', $contract->id)->where('user_id', $user->id)->where('kind', 'contract_rocks')->exists()) {
                continue;
            }

            $transaction = $this->crowns->reward(
                $user,
                'team_contract_reward',
                $contract,
                (int) $contract->rocks_reward,
                'Team-Auftrag abgeschlossen',
                ['team_id' => $team->id, 'team_contract_id' => $contract->id],
                true
            );

            if ($transaction) {
                TeamRewardGrant::query()->create([
                    'team_id' => $team->id,
                    'team_contract_id' => $contract->id,
                    'user_id' => $user->id,
                    'crown_transaction_id' => $transaction->id,
                    'kind' => 'contract_rocks',
                    'amount' => $contract->rocks_reward,
                    'granted_at' => now(),
                ]);
            }
        }

        $contract->forceFill(['rewarded_at' => now()])->save();
    }
}
