<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CrownEquippedItem;
use App\Models\CrownInventoryItem;
use App\Models\CrownShopItem;
use App\Models\CrownTransaction;
use App\Services\Economy\CrownsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiCrownsController extends Controller
{
    public function index(Request $request, CrownsService $crowns): JsonResponse
    {
        $user = $request->user();
        $crowns->rewardCompletedProfileIfEligible($user);

        return response()->json([
            'data' => $this->payload($request, $crowns),
        ]);
    }

    public function collect(Request $request, CrownsService $crowns): JsonResponse
    {
        $count = $crowns->collectPending($request->user());

        return response()->json([
            'message' => $count > 0 ? 'Crowns wurden eingesammelt.' : 'Keine offenen Crowns vorhanden.',
            'collected_count' => $count,
            'data' => $this->payload($request, $crowns),
        ]);
    }

    public function dismiss(Request $request, CrownsService $crowns): JsonResponse
    {
        $count = $crowns->dismissPendingCollection($request->user());

        return response()->json([
            'message' => 'Crowns-Hinweis wurde ausgeblendet.',
            'dismissed_count' => $count,
            'data' => $this->payload($request, $crowns),
        ]);
    }

    public function purchase(Request $request, CrownShopItem $item, CrownsService $crowns): JsonResponse
    {
        $user = $request->user();

        if (! $this->shopTablesReady()) {
            return response()->json(['message' => 'Crowns-Shop ist noch nicht bereit.'], 503);
        }

        if (! $this->isPurchasable($item)) {
            return response()->json(['message' => 'Dieses Item ist aktuell nicht verfügbar.'], 422);
        }

        $alreadyOwned = CrownInventoryItem::query()
            ->where('user_id', $user->id)
            ->where('shop_item_id', $item->id)
            ->exists();

        if ($alreadyOwned) {
            return response()->json(['message' => 'Du besitzt dieses Item bereits.'], 422);
        }

        $inventoryItem = DB::transaction(function () use ($user, $item, $crowns): ?CrownInventoryItem {
            $alreadyOwned = CrownInventoryItem::query()
                ->where('user_id', $user->id)
                ->where('shop_item_id', $item->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyOwned) {
                return null;
            }

            $transaction = $crowns->spend(
                $user,
                (int) $item->price,
                'shop_purchase',
                $item,
                'Shop-Kauf: ' . $item->displayName(),
                ['shop_item_key' => $item->key, 'slot' => $item->slot, 'rarity' => $item->rarity]
            );

            if (! $transaction) {
                return null;
            }

            return CrownInventoryItem::create([
                'user_id' => $user->id,
                'shop_item_id' => $item->id,
                'purchased_at' => now(),
                'metadata' => ['transaction_id' => $transaction->id],
            ]);
        });

        if (! $inventoryItem) {
            return response()->json(['message' => 'Nicht genug Crowns oder Item bereits gekauft.'], 422);
        }

        return response()->json([
            'message' => $item->displayName() . ' wurde gekauft.',
            'data' => $this->payload($request, $crowns),
        ]);
    }

    public function equip(Request $request, CrownInventoryItem $inventoryItem, CrownsService $crowns): JsonResponse
    {
        $user = $request->user();

        if ((int) $inventoryItem->user_id !== (int) $user->id) {
            abort(403);
        }

        $inventoryItem->load('shopItem');
        $item = $inventoryItem->shopItem;

        if (! $item || ! $item->isActivatable()) {
            return response()->json(['message' => 'Dieses Item kann nicht aktiviert werden.'], 422);
        }

        DB::transaction(function () use ($user, $inventoryItem, $item): void {
            CrownEquippedItem::query()
                ->where('user_id', $user->id)
                ->where('inventory_item_id', $inventoryItem->id)
                ->where('slot', '!=', $item->slot)
                ->delete();

            CrownEquippedItem::updateOrCreate(
                ['user_id' => $user->id, 'slot' => $item->slot],
                ['inventory_item_id' => $inventoryItem->id]
            );
        });

        return response()->json([
            'message' => $item->displayName() . ' wurde aktiviert.',
            'data' => $this->payload($request, $crowns),
        ]);
    }

    public function unequip(Request $request, string $slot, CrownsService $crowns): JsonResponse
    {
        CrownEquippedItem::query()
            ->where('user_id', $request->user()->id)
            ->where('slot', $slot)
            ->delete();

        return response()->json([
            'message' => 'Item wurde deaktiviert.',
            'data' => $this->payload($request, $crowns),
        ]);
    }

    private function payload(Request $request, CrownsService $crowns): array
    {
        $user = $request->user();

        if (! $this->shopTablesReady()) {
            return [
                'summary' => $crowns->summary($user),
                'pending_collection' => $this->pendingCollectionPayload($crowns->pendingCollection($user, 8, true)),
                'pending_popup' => $this->pendingCollectionPayload($crowns->pendingCollection($user, 3, false)),
                'history' => $this->historyPayload($user->id),
                'shop_items' => [],
                'inventory_items' => [],
                'equipped' => [],
            ];
        }

        $ownedItemIds = CrownInventoryItem::query()
            ->where('user_id', $user->id)
            ->pluck('shop_item_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $equippedInventoryIds = CrownEquippedItem::query()
            ->where('user_id', $user->id)
            ->pluck('inventory_item_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $shopItems = CrownShopItem::query()
            ->available()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->map(fn (CrownShopItem $item): array => $this->shopItemPayload(
                $item,
                in_array((int) $item->id, $ownedItemIds, true)
            ))
            ->values();

        $inventoryItems = CrownInventoryItem::query()
            ->with(['shopItem', 'equippedItem'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (CrownInventoryItem $inventoryItem): array => $this->inventoryItemPayload(
                $inventoryItem,
                in_array((int) $inventoryItem->id, $equippedInventoryIds, true)
            ))
            ->values();

        $equipped = CrownEquippedItem::query()
            ->with('inventoryItem.shopItem')
            ->where('user_id', $user->id)
            ->get()
            ->mapWithKeys(function (CrownEquippedItem $equippedItem): array {
                if (! $equippedItem->inventoryItem || ! $equippedItem->inventoryItem->shopItem) {
                    return [];
                }

                return [$equippedItem->slot => $this->inventoryItemPayload($equippedItem->inventoryItem, true)];
            });

        return [
            'summary' => $crowns->summary($user),
            'pending_collection' => $this->pendingCollectionPayload($crowns->pendingCollection($user, 8, true)),
            'pending_popup' => $this->pendingCollectionPayload($crowns->pendingCollection($user, 3, false)),
            'history' => $this->historyPayload($user->id),
            'shop_items' => $shopItems,
            'inventory_items' => $inventoryItems,
            'equipped' => $equipped,
            'non_cash_notice' => (string) config('crowns.non_cash_notice'),
        ];
    }


    private function historyPayload(int $userId): array
    {
        if (! Schema::hasTable('crown_transactions')) {
            return [];
        }

        $query = CrownTransaction::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit(60);

        $hasCollectedAt = Schema::hasColumn('crown_transactions', 'collected_at');
        $hasDismissedAt = Schema::hasColumn('crown_transactions', 'claim_dismissed_at');

        return $query
            ->get()
            ->map(function (CrownTransaction $transaction) use ($hasCollectedAt, $hasDismissedAt): array {
                $isCredit = (string) $transaction->type === 'credit' && (int) $transaction->amount > 0;
                $collectedAt = $hasCollectedAt ? $transaction->collected_at : $transaction->created_at;
                $dismissedAt = $hasDismissedAt ? $transaction->claim_dismissed_at : null;
                $isPending = $isCredit && $hasCollectedAt && $collectedAt === null;

                return [
                    'id' => (int) $transaction->id,
                    'type' => (string) $transaction->type,
                    'action' => (string) ($transaction->action ?? ''),
                    'amount' => (int) $transaction->amount,
                    'balance_after' => (int) $transaction->balance_after,
                    'description' => (string) ($transaction->description ?: $transaction->action ?: 'Crowns'),
                    'is_credit' => $isCredit,
                    'is_spend' => (string) $transaction->type === 'debit' || (int) $transaction->amount < 0,
                    'is_pending' => $isPending,
                    'is_collected' => ! $isPending,
                    'created_at' => optional($transaction->created_at)->toIso8601String(),
                    'collected_at' => optional($collectedAt)->toIso8601String(),
                    'claim_dismissed_at' => optional($dismissedAt)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array{enabled: bool, total: int, count: int, transactions: \Illuminate\Support\Collection<int, CrownTransaction>} $pending
     */
    private function pendingCollectionPayload(array $pending): array
    {
        $transactions = $pending['transactions'] ?? collect();

        return [
            'enabled' => (bool) ($pending['enabled'] ?? false),
            'total' => (int) ($pending['total'] ?? 0),
            'count' => (int) ($pending['count'] ?? 0),
            'transactions' => $transactions
                ->map(fn (CrownTransaction $transaction): array => [
                    'id' => (int) $transaction->id,
                    'action' => (string) $transaction->action,
                    'amount' => (int) $transaction->amount,
                    'description' => (string) ($transaction->description ?: $transaction->action),
                    'created_at' => optional($transaction->created_at)->toIso8601String(),
                ])
                ->values(),
        ];
    }

    private function shopItemPayload(CrownShopItem $item, bool $owned = false): array
    {
        return [
            'id' => (int) $item->id,
            'key' => (string) $item->key,
            'type' => (string) $item->type,
            'slot' => (string) ($item->slot ?? ''),
            'name' => $item->displayName(),
            'name_de' => (string) $item->name_de,
            'name_en' => (string) ($item->name_en ?: $item->name_de),
            'description' => $item->displayDescription(),
            'description_de' => (string) ($item->description_de ?? ''),
            'description_en' => (string) ($item->description_en ?: $item->description_de),
            'price' => (int) $item->price,
            'rarity' => (string) $item->rarity,
            'icon' => (string) ($item->icon ?? ''),
            'preview_class' => (string) ($item->preview_class ?? ''),
            'is_limited' => (bool) $item->is_limited,
            'is_activatable' => $item->isActivatable(),
            'owned' => $owned,
        ];
    }

    private function inventoryItemPayload(CrownInventoryItem $inventoryItem, bool $equipped = false): array
    {
        $shopItem = $inventoryItem->shopItem;

        return [
            'id' => (int) $inventoryItem->id,
            'shop_item_id' => (int) $inventoryItem->shop_item_id,
            'purchased_at' => optional($inventoryItem->purchased_at)->toIso8601String(),
            'is_equipped' => $equipped,
            'slot' => (string) ($shopItem?->slot ?? ''),
            'item' => $shopItem ? $this->shopItemPayload($shopItem, true) : null,
        ];
    }

    private function shopTablesReady(): bool
    {
        return Schema::hasTable('crown_wallets')
            && Schema::hasTable('crown_transactions')
            && Schema::hasTable('crown_reward_claims')
            && Schema::hasTable('crown_shop_items')
            && Schema::hasTable('crown_inventory_items')
            && Schema::hasTable('crown_equipped_items');
    }

    private function isPurchasable(CrownShopItem $item): bool
    {
        if (! $item->is_active) {
            return false;
        }

        if ($item->available_from && $item->available_from->isFuture()) {
            return false;
        }

        if ($item->available_until && $item->available_until->isPast()) {
            return false;
        }

        return true;
    }
}
