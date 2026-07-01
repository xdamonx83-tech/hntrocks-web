<?php

namespace App\Services\Economy;

use App\Models\CrownRewardClaim;
use App\Models\CrownTransaction;
use App\Models\CrownWallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrownsService
{
    public function enabled(): bool
    {
        return (bool) config('crowns.enabled', true) && $this->tablesReady();
    }

    public function tablesReady(): bool
    {
        return Schema::hasTable('crown_wallets')
            && Schema::hasTable('crown_transactions')
            && Schema::hasTable('crown_reward_claims');
    }

    public function wallet(User $user): CrownWallet
    {
        CrownWallet::firstOrCreate(['user_id' => $user->id], [
            'balance' => 0,
            'lifetime_earned' => 0,
            'lifetime_spent' => 0,
        ]);

        return CrownWallet::query()->where('user_id', $user->id)->firstOrFail();
    }

    public function balance(User $user): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        return (int) $this->wallet($user)->balance;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        if (! $this->enabled()) {
            return [
                'enabled' => false,
                'balance' => 0,
                'lifetime_earned' => 0,
                'lifetime_spent' => 0,
                'daily_login_claimed' => true,
                'daily_login_amount' => 0,
            ];
        }

        $wallet = $this->wallet($user);
        $pending = $this->pendingCollection($user, 1, true);

        return [
            'enabled' => true,
            'balance' => (int) $wallet->balance,
            'lifetime_earned' => (int) $wallet->lifetime_earned,
            'lifetime_spent' => (int) $wallet->lifetime_spent,
            'daily_login_claimed' => ! $this->canClaimActionToday($user, 'daily_login'),
            'daily_login_amount' => (int) data_get($this->definition('daily_login'), 'amount', 0),
            'pending_total' => (int) ($pending['total'] ?? 0),
            'pending_count' => (int) ($pending['count'] ?? 0),
        ];
    }

    public function rewardCompletedProfileIfEligible(User $user): ?CrownTransaction
    {
        if (! $this->enabled()) {
            return null;
        }

        $freshUser = $user->fresh(['profile']) ?? $user;

        if (! method_exists($freshUser, 'profileCompletionScore') || \App\Support\ProfileCompletion::score($freshUser) < 100) {
            return null;
        }

        return $this->reward(
            $freshUser,
            'profile_completed',
            source: $freshUser,
            description: (string) data_get($this->definition('profile_completed'), 'description', 'Profil vervollständigt'),
            oncePerSource: true
        );
    }

    public function rewardDailyLogin(User $user): ?CrownTransaction
    {
        return $this->reward(
            $user,
            'daily_login',
            description: (string) data_get($this->definition('daily_login'), 'description', 'Täglicher Login-Bonus'),
            oncePerSource: false
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function reward(
        User $user,
        string $action,
        ?Model $source = null,
        ?int $amount = null,
        ?string $description = null,
        array $metadata = [],
        bool $oncePerSource = true
    ): ?CrownTransaction {
        if (! $this->enabled()) {
            return null;
        }

        $definition = $this->definition($action);
        if ($definition === null && $amount === null) {
            return null;
        }

        $rewardAmount = $amount ?? (int) data_get($definition, 'amount', 0);
        if ($rewardAmount <= 0) {
            return null;
        }

        $dailyLimit = data_get($definition, 'daily_limit');
        $description ??= (string) data_get($definition, 'description', $action);

        return DB::transaction(function () use ($user, $action, $source, $rewardAmount, $dailyLimit, $description, $metadata, $oncePerSource): ?CrownTransaction {
            $wallet = $this->lockedWallet($user);

            if ($oncePerSource && $source && $this->alreadyRewardedForSource($user, $action, $source)) {
                return null;
            }

            $claim = null;
            if ($dailyLimit !== null) {
                $claim = $this->lockedClaim($user, $action);

                if ((int) $claim->claims_count >= (int) $dailyLimit) {
                    return null;
                }
            }

            $wallet->balance = max(0, (int) $wallet->balance + $rewardAmount);
            $wallet->lifetime_earned = max(0, (int) $wallet->lifetime_earned + $rewardAmount);

            if ($action === 'daily_login') {
                $wallet->last_daily_login_reward_at = now();
            }

            $wallet->save();

            if ($claim) {
                $claim->claims_count = (int) $claim->claims_count + 1;
                $claim->save();
            }

            $transactionData = [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => CrownTransaction::TYPE_CREDIT,
                'action' => $action,
                'amount' => $rewardAmount,
                'balance_after' => (int) $wallet->balance,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'description' => $description,
                'metadata' => $metadata ?: null,
            ];

            if (Schema::hasColumn('crown_transactions', 'collected_at')) {
                $transactionData['collected_at'] = now();
            }

            return CrownTransaction::create($transactionData);
        });
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function spend(User $user, int $amount, string $action, ?Model $source = null, ?string $description = null, array $metadata = []): ?CrownTransaction
    {
        if (! $this->enabled() || $amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($user, $amount, $action, $source, $description, $metadata): ?CrownTransaction {
            $wallet = $this->lockedWallet($user);

            if ((int) $wallet->balance < $amount) {
                return null;
            }

            $wallet->balance = max(0, (int) $wallet->balance - $amount);
            $wallet->lifetime_spent = (int) $wallet->lifetime_spent + $amount;
            $wallet->save();

            $transactionData = [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => CrownTransaction::TYPE_DEBIT,
                'action' => $action,
                'amount' => -$amount,
                'balance_after' => (int) $wallet->balance,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'description' => $description,
                'metadata' => $metadata ?: null,
            ];

            if (Schema::hasColumn('crown_transactions', 'collected_at')) {
                $transactionData['collected_at'] = now();
            }

            return CrownTransaction::create($transactionData);
        });
    }

    /**
     * @return array{enabled: bool, total: int, count: int, transactions: Collection<int, CrownTransaction>}
     */
    public function pendingCollection(User $user, int $limit = 8, bool $includeDismissed = false): array
    {
        if (! $this->enabled() || ! Schema::hasColumn('crown_transactions', 'collected_at')) {
            return [
                'enabled' => false,
                'total' => 0,
                'count' => 0,
                'transactions' => new Collection(),
            ];
        }

        $query = CrownTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', CrownTransaction::TYPE_CREDIT)
            ->where('amount', '>', 0)
            ->whereNull('collected_at');

        if (! $includeDismissed && Schema::hasColumn('crown_transactions', 'claim_dismissed_at')) {
            $query->whereNull('claim_dismissed_at');
        }

        $total = (int) (clone $query)->sum('amount');
        $count = (int) (clone $query)->count();

        return [
            'enabled' => true,
            'total' => $total,
            'count' => $count,
            'transactions' => $query
                ->latest()
                ->limit(max(1, min(12, $limit)))
                ->get(),
        ];
    }

    public function collectPending(User $user): int
    {
        if (! $this->enabled() || ! Schema::hasColumn('crown_transactions', 'collected_at')) {
            return 0;
        }

        return DB::transaction(function () use ($user): int {
            $wallet = $this->lockedWallet($user);
            $now = now();

            $transactions = CrownTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', CrownTransaction::TYPE_CREDIT)
                ->where('amount', '>', 0)
                ->whereNull('collected_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($transactions->isEmpty()) {
                return 0;
            }

            foreach ($transactions as $transaction) {
                $amount = max(0, (int) $transaction->amount);

                if ($amount <= 0) {
                    continue;
                }

                $wallet->balance = max(0, (int) $wallet->balance + $amount);
                $wallet->lifetime_earned = max(0, (int) $wallet->lifetime_earned + $amount);

                $transaction->balance_after = (int) $wallet->balance;
                $transaction->collected_at = $now;

                if (Schema::hasColumn('crown_transactions', 'claim_dismissed_at')) {
                    $transaction->claim_dismissed_at = null;
                }

                $transaction->save();
            }

            $wallet->save();

            return $transactions->count();
        });
    }

    public function dismissPendingCollection(User $user): int
    {
        if (! $this->enabled() || ! Schema::hasColumn('crown_transactions', 'claim_dismissed_at')) {
            return 0;
        }

        return CrownTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', CrownTransaction::TYPE_CREDIT)
            ->where('amount', '>', 0)
            ->whereNull('collected_at')
            ->whereNull('claim_dismissed_at')
            ->update(['claim_dismissed_at' => now()]);
    }

    /**
     * @return Collection<int, CrownTransaction>
     */
    public function recentTransactions(User $user, int $limit = 25): Collection
    {
        if (! $this->enabled()) {
            return new Collection();
        }

        return CrownTransaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(max(1, min(100, $limit)))
            ->get();
    }

    public function canClaimActionToday(User $user, string $action): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $definition = $this->definition($action);
        $dailyLimit = data_get($definition, 'daily_limit');

        if ($dailyLimit === null) {
            return true;
        }

        $count = CrownRewardClaim::query()
            ->where('user_id', $user->id)
            ->where('action', $action)
            ->whereDate('reward_date', now()->toDateString())
            ->value('claims_count');

        return (int) $count < (int) $dailyLimit;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function definition(string $action): ?array
    {
        $definition = config('crowns.rewards.' . $action);

        return is_array($definition) ? $definition : null;
    }

    private function lockedWallet(User $user): CrownWallet
    {
        CrownWallet::firstOrCreate(['user_id' => $user->id], [
            'balance' => 0,
            'lifetime_earned' => 0,
            'lifetime_spent' => 0,
        ]);

        return CrownWallet::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockedClaim(User $user, string $action): CrownRewardClaim
    {
        $today = now()->toDateString();

        CrownRewardClaim::firstOrCreate([
            'user_id' => $user->id,
            'action' => $action,
            'reward_date' => $today,
        ], [
            'claims_count' => 0,
        ]);

        return CrownRewardClaim::query()
            ->where('user_id', $user->id)
            ->where('action', $action)
            ->whereDate('reward_date', $today)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function alreadyRewardedForSource(User $user, string $action, Model $source): bool
    {
        return CrownTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', CrownTransaction::TYPE_CREDIT)
            ->where('action', $action)
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->exists();
    }
}
