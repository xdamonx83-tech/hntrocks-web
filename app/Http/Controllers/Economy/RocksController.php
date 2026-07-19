<?php

namespace App\Http\Controllers\Economy;

use App\Http\Controllers\Controller;
use App\Models\CrownDailyStreakClaim;
use App\Models\CrownEquippedItem;
use App\Models\CrownInventoryItem;
use App\Models\CrownRewardClaim;
use App\Models\CrownShopItem;
use App\Models\CrownTransaction;
use App\Services\Economy\CrownDailyStreakService;
use App\Services\Economy\RocksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RocksController extends Controller
{
    public function index(
        Request $request,
        RocksService $rocks,
        CrownDailyStreakService $dailyStreakService
    ): View {
        $user = $request->user()->loadMissing(['profile', 'crownWallet']);
        $rocks->rewardCompletedProfileIfEligible($user);
        $user = $user->fresh(['profile', 'crownWallet']) ?? $user;

        $summary = $rocks->summary($user);
        $pendingCollection = $rocks->pendingCollection($user, 8, true);

        $recentTransactions = $rocks->enabled()
            ? CrownTransaction::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(60)
                ->get()
            : collect();

        $rewardDefinitions = collect(config('crowns.rewards', []))
            ->filter(function (mixed $definition): bool {
                return is_array($definition)
                    && (bool) data_get($definition, 'enabled', true)
                    && ! (bool) data_get($definition, 'legacy', false)
                    && (int) data_get($definition, 'amount', 0) > 0;
            });

        $todayClaims = Schema::hasTable('crown_reward_claims')
            ? CrownRewardClaim::query()
                ->where('user_id', $user->id)
                ->whereDate('reward_date', now()->toDateString())
                ->get()
                ->keyBy('action')
            : collect();

        $rewardRows = $rewardDefinitions->map(function (array $definition, string $action) use ($todayClaims): array {
            $amount = max(0, (int) data_get($definition, 'amount', 0));
            $dailyLimit = data_get($definition, 'daily_limit');
            $claimed = max(0, (int) ($todayClaims->get($action)?->claims_count ?? 0));
            $limit = $dailyLimit === null ? null : max(0, (int) $dailyLimit);
            $remaining = $limit === null ? null : max(0, $limit - $claimed);

            return [
                'action' => $action,
                'amount' => $amount,
                'description' => (string) data_get($definition, 'description', $action),
                'daily_limit' => $limit,
                'claimed_today' => $claimed,
                'remaining_today' => $remaining,
                'earned_today' => $amount * min($claimed, $limit ?? $claimed),
            ];
        })->values();

        $dailyRewardRows = $rewardRows
            ->filter(fn (array $reward): bool => $reward['daily_limit'] !== null)
            ->values();
        $quickRewards = $dailyRewardRows
            ->filter(fn (array $reward): bool => (int) ($reward['remaining_today'] ?? 0) > 0)
            ->sortByDesc(fn (array $reward): int => (int) $reward['amount'])
            ->take(3)
            ->values();

        $dailyPossible = (int) $dailyRewardRows->sum(
            fn (array $reward): int => (int) $reward['amount'] * (int) $reward['daily_limit']
        );
        $dailyEarned = (int) $dailyRewardRows->sum('earned_today');

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $weeklyTransactions = $rocks->enabled()
            ? CrownTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', CrownTransaction::TYPE_CREDIT)
                ->where('amount', '>', 0)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->get()
            : collect();

        $weeklyRocksDays = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $weeklyTransactions): array {
            $date = $weekStart->copy()->addDays($offset);

            return [
                'date' => $date,
                'amount' => (int) $weeklyTransactions
                    ->filter(fn (CrownTransaction $transaction): bool => $transaction->created_at?->isSameDay($date) ?? false)
                    ->sum('amount'),
                'is_today' => $date->isToday(),
                'is_future' => $date->isFuture(),
            ];
        });

        $dailyStreak = $dailyStreakService->status($user);
        $streakClaims = collect();
        if ((bool) ($dailyStreak['enabled'] ?? false) && Schema::hasTable('crown_daily_streak_claims')) {
            $streakClaims = CrownDailyStreakClaim::query()
                ->where('user_id', $user->id)
                ->whereBetween('claim_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->get()
                ->keyBy(fn (CrownDailyStreakClaim $claim): string => $claim->claim_date->toDateString());
        }

        $inventoryItems = collect();
        $equippedBySlot = collect();
        $recommendedShopItems = collect();

        $shopReady = Schema::hasTable('crown_shop_items')
            && Schema::hasTable('crown_inventory_items')
            && Schema::hasTable('crown_equipped_items');

        if ($shopReady) {
            $inventoryItems = CrownInventoryItem::query()
                ->with(['shopItem', 'equippedItem'])
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            $equippedBySlot = CrownEquippedItem::query()
                ->with('inventoryItem.shopItem')
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('slot');

            $ownedItemIds = $inventoryItems
                ->pluck('shop_item_id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $recommendedShopItems = CrownShopItem::query()
                ->available()
                ->when($ownedItemIds !== [], fn ($query) => $query->whereNotIn('id', $ownedItemIds))
                ->orderBy('sort_order')
                ->orderBy('price')
                ->limit(3)
                ->get();
        }

        $creditTransactionsCount = $rocks->enabled()
            ? CrownTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', CrownTransaction::TYPE_CREDIT)
                ->where('amount', '>', 0)
                ->count()
            : 0;

        return view('themes.hnt_preview.rocks.index', [
            'user' => $user,
            'summary' => $summary,
            'pendingCollection' => $pendingCollection,
            'recentTransactions' => $recentTransactions,
            'rewardRows' => $rewardRows,
            'dailyRewardRows' => $dailyRewardRows,
            'quickRewards' => $quickRewards,
            'dailyPossible' => $dailyPossible,
            'dailyEarned' => $dailyEarned,
            'weeklyRocksDays' => $weeklyRocksDays,
            'weeklyRocksTotal' => (int) $weeklyTransactions->sum('amount'),
            'dailyStreak' => $dailyStreak,
            'streakClaims' => $streakClaims,
            'inventoryItems' => $inventoryItems,
            'equippedBySlot' => $equippedBySlot,
            'recommendedShopItems' => $recommendedShopItems,
            'creditTransactionsCount' => $creditTransactionsCount,
            'nonCashNotice' => (string) config('crowns.non_cash_notice'),
        ]);
    }

    public function history(): RedirectResponse
    {
        return redirect()->route('rocks.index', ['tab' => 'history']);
    }

    public function claimDailyLogin(): RedirectResponse
    {
        return back()->with('error', __('ui.crowns_daily_login_app_only'));
    }

    public function collectPending(Request $request, RocksService $rocks): RedirectResponse
    {
        $count = $rocks->collectPending($request->user());

        return back()->with(
            $count > 0 ? 'status' : 'error',
            $count > 0 ? __('ui.crowns_collect_success') : __('ui.crowns_collect_empty')
        );
    }

    public function dismissPending(Request $request, RocksService $rocks): RedirectResponse|JsonResponse
    {
        $count = $rocks->dismissPendingCollection($request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'dismissed' => $count,
            ]);
        }

        return back();
    }
}
