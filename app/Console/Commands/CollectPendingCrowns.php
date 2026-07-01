<?php

namespace App\Console\Commands;

use App\Models\CrownTransaction;
use App\Models\CrownWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CollectPendingCrowns extends Command
{
    protected $signature = 'hnt:crowns-collect-pending {--user= : Optional user ID to process} {--limit= : Optional maximum number of users to process}';

    protected $description = 'Book old pending Bounty Mark credit transactions into user wallets';

    public function handle(): int
    {
        if (! $this->tablesReady()) {
            $this->warn('Crowns tables are not ready.');

            return self::SUCCESS;
        }

        if (! Schema::hasColumn('crown_transactions', 'collected_at')) {
            $this->warn('crown_transactions.collected_at is missing.');

            return self::SUCCESS;
        }

        $userIds = $this->pendingUserIds();
        $processedUsers = 0;
        $collectedTransactions = 0;
        $collectedAmount = 0;

        foreach ($userIds as $userId) {
            $stats = DB::transaction(fn (): array => $this->collectForUser((int) $userId));

            if ($stats['transactions'] <= 0) {
                continue;
            }

            $processedUsers++;
            $collectedTransactions += $stats['transactions'];
            $collectedAmount += $stats['amount'];
        }

        $this->line('processed_users: '.$processedUsers);
        $this->line('collected_transactions: '.$collectedTransactions);
        $this->line('collected_amount: '.$collectedAmount);

        return self::SUCCESS;
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('crown_wallets')
            && Schema::hasTable('crown_transactions');
    }

    /**
     * @return array<int, int>
     */
    private function pendingUserIds(): array
    {
        $query = CrownTransaction::query()
            ->where('type', CrownTransaction::TYPE_CREDIT)
            ->where('amount', '>', 0)
            ->whereNull('collected_at')
            ->select('user_id')
            ->distinct()
            ->orderBy('user_id');

        $userId = (int) $this->option('user');
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return array{transactions: int, amount: int}
     */
    private function collectForUser(int $userId): array
    {
        CrownWallet::firstOrCreate(['user_id' => $userId], [
            'balance' => 0,
            'lifetime_earned' => 0,
            'lifetime_spent' => 0,
        ]);

        $wallet = CrownWallet::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->firstOrFail();

        $transactions = CrownTransaction::query()
            ->where('user_id', $userId)
            ->where('type', CrownTransaction::TYPE_CREDIT)
            ->where('amount', '>', 0)
            ->whereNull('collected_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($transactions->isEmpty()) {
            return ['transactions' => 0, 'amount' => 0];
        }

        $now = now();
        $totalAmount = 0;

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
            $totalAmount += $amount;
        }

        $wallet->save();

        return [
            'transactions' => $transactions->count(),
            'amount' => $totalAmount,
        ];
    }
}
