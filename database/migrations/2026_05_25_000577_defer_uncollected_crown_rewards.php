<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crown_wallets') || ! Schema::hasTable('crown_transactions')) {
            return;
        }

        if (! Schema::hasColumn('crown_transactions', 'collected_at')) {
            return;
        }

        $pendingByWallet = DB::table('crown_transactions')
            ->select('wallet_id', DB::raw('SUM(amount) as pending_amount'))
            ->whereNotNull('wallet_id')
            ->where('type', 'credit')
            ->where('amount', '>', 0)
            ->whereNull('collected_at')
            ->groupBy('wallet_id')
            ->get();

        foreach ($pendingByWallet as $row) {
            $walletId = (int) $row->wallet_id;
            $pendingAmount = max(0, (int) $row->pending_amount);

            if ($walletId <= 0 || $pendingAmount <= 0) {
                continue;
            }

            $wallet = DB::table('crown_wallets')->where('id', $walletId)->first();
            if (! $wallet) {
                continue;
            }

            DB::table('crown_wallets')
                ->where('id', $walletId)
                ->update([
                    'balance' => max(0, (int) $wallet->balance - $pendingAmount),
                    'lifetime_earned' => max(0, (int) $wallet->lifetime_earned - $pendingAmount),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally not reversible: this migration corrects already-created pending rewards
        // so uncollected Crowns are no longer counted as available wallet balance.
    }
};
