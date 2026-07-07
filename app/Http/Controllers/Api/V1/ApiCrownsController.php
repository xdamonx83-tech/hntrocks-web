<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CrownEquippedItem;
use App\Models\CrownInventoryItem;
use App\Models\CrownShopItem;
use App\Models\CrownTransaction;
use App\Services\Economy\CrownDailyStreakService;
use App\Services\Economy\CrownsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiCrownsController extends Controller
{
    public function index(Request $request, CrownsService $crowns): JsonResponse
    {
        return $this->withApiLocale($request, function (string $locale) use ($request, $crowns): JsonResponse {
            $user = $request->user();
            $crowns->rewardCompletedProfileIfEligible($user);

            return response()->json([
                'data' => $this->payload($request, $crowns, $locale),
            ]);
        });
    }

    public function collect(Request $request, CrownsService $crowns): JsonResponse
    {
        return $this->withApiLocale($request, function (string $locale) use ($request, $crowns): JsonResponse {
            $count = $crowns->collectPending($request->user());

            return response()->json([
                'message' => $this->apiMessage($count > 0 ? 'collect_success' : 'collect_empty', $locale),
                'collected_count' => $count,
                'data' => $this->payload($request, $crowns, $locale),
            ]);
        });
    }

    public function dismiss(Request $request, CrownsService $crowns): JsonResponse
    {
        return $this->withApiLocale($request, function (string $locale) use ($request, $crowns): JsonResponse {
            $count = $crowns->dismissPendingCollection($request->user());

            return response()->json([
                'message' => $this->apiMessage('dismiss_success', $locale),
                'dismissed_count' => $count,
                'data' => $this->payload($request, $crowns, $locale),
            ]);
        });
    }

    public function purchase(Request $request, CrownShopItem $item, CrownsService $crowns): JsonResponse
    {
        return $this->withApiLocale($request, function (string $locale) use ($request, $item, $crowns): JsonResponse {
            $user = $request->user();

            if (! $this->shopTablesReady()) {
                return response()->json(['message' => $this->apiMessage('shop_unavailable', $locale)], 503);
            }

            if (! $this->isPurchasable($item)) {
                return response()->json(['message' => $this->apiMessage('item_unavailable', $locale)], 422);
            }

            $alreadyOwned = CrownInventoryItem::query()
                ->where('user_id', $user->id)
                ->where('shop_item_id', $item->id)
                ->exists();

            if ($alreadyOwned) {
                return response()->json(['message' => $this->apiMessage('already_owned', $locale)], 422);
            }

            $inventoryItem = DB::transaction(function () use ($user, $item, $crowns, $locale): ?CrownInventoryItem {
                $alreadyOwned = CrownInventoryItem::query()
                    ->where('user_id', $user->id)
                    ->where('shop_item_id', $item->id)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyOwned) {
                    return null;
                }

                $offerActive = $item->offerActive();
                $effectivePrice = $item->effectivePrice();

                $transaction = $crowns->spend(
                    $user,
                    $effectivePrice,
                    'shop_purchase',
                    $item,
                    $this->localizedActionLabel('shop_purchase', $locale) . ': ' . $item->displayName(),
                    [
                        'shop_item_key' => $item->key,
                        'slot' => $item->slot,
                        'rarity' => $item->rarity,
                        'original_price' => (int) $item->price,
                        'effective_price' => $effectivePrice,
                        'sale_price' => $offerActive ? (int) $item->sale_price : null,
                        'offer_active' => $offerActive,
                    ]
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
                return response()->json(['message' => $this->apiMessage('not_enough_or_owned', $locale)], 422);
            }

            return response()->json([
                'message' => $this->apiMessage('purchase_success', $locale, ['item' => $item->displayName()]),
                'data' => $this->payload($request, $crowns, $locale),
            ]);
        });
    }

    public function equip(Request $request, CrownInventoryItem $inventoryItem, CrownsService $crowns): JsonResponse
    {
        return $this->withApiLocale($request, function (string $locale) use ($request, $inventoryItem, $crowns): JsonResponse {
            $user = $request->user();

            if ((int) $inventoryItem->user_id !== (int) $user->id) {
                abort(403);
            }

            $inventoryItem->load('shopItem');
            $item = $inventoryItem->shopItem;

            if (! $item || ! $item->isActivatable()) {
                return response()->json(['message' => $this->apiMessage('not_activatable', $locale)], 422);
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
                'message' => $this->apiMessage('equip_success', $locale, ['item' => $item->displayName()]),
                'data' => $this->payload($request, $crowns, $locale),
            ]);
        });
    }

    public function unequip(Request $request, string $slot, CrownsService $crowns): JsonResponse
    {
        return $this->withApiLocale($request, function (string $locale) use ($request, $slot, $crowns): JsonResponse {
            CrownEquippedItem::query()
                ->where('user_id', $request->user()->id)
                ->where('slot', $slot)
                ->delete();

            return response()->json([
                'message' => $this->apiMessage('unequip_success', $locale),
                'data' => $this->payload($request, $crowns, $locale),
            ]);
        });
    }

    private function payload(Request $request, CrownsService $crowns, string $locale): array
    {
        $user = $request->user();
        $dailyStreak = app(CrownDailyStreakService::class)->status($user);

        if (! $this->shopTablesReady()) {
            return [
                'summary' => $crowns->summary($user),
                'daily_streak' => $dailyStreak,
                'pending_collection' => $this->pendingCollectionPayload($crowns->pendingCollection($user, 8, true), $locale),
                'pending_popup' => $this->pendingCollectionPayload($crowns->pendingCollection($user, 3, false), $locale),
                'history' => $this->historyPayload($user->id, $locale),
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
            'daily_streak' => $dailyStreak,
            'pending_collection' => $this->pendingCollectionPayload($crowns->pendingCollection($user, 8, true), $locale),
            'pending_popup' => $this->pendingCollectionPayload($crowns->pendingCollection($user, 3, false), $locale),
            'history' => $this->historyPayload($user->id, $locale),
            'shop_items' => $shopItems,
            'inventory_items' => $inventoryItems,
            'equipped' => $equipped,
            'non_cash_notice' => (string) config('crowns.non_cash_notice'),
        ];
    }

    private function historyPayload(int $userId, string $locale): array
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
            ->with('source')
            ->get()
            ->map(function (CrownTransaction $transaction) use ($hasCollectedAt, $hasDismissedAt, $locale): array {
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
                    'description' => $this->localizedTransactionDescription($transaction, $locale),
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
    private function pendingCollectionPayload(array $pending, string $locale): array
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
                    'description' => $this->localizedTransactionDescription($transaction, $locale),
                    'created_at' => optional($transaction->created_at)->toIso8601String(),
                ])
                ->values(),
        ];
    }

    private function shopItemPayload(CrownShopItem $item, bool $owned = false): array
    {
        $offerActive = $item->offerActive();
        $effectivePrice = $item->effectivePrice();

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
            'price' => $effectivePrice,
            'original_price' => (int) $item->price,
            'effective_price' => $effectivePrice,
            'sale_price' => $offerActive ? (int) $item->sale_price : null,
            'offer_active' => $offerActive,
            'offer_ends_at' => $offerActive ? optional($item->offer_ends_at)->toIso8601String() : null,
            'badge' => (string) ($item->badge ?? ''),
            'sort_order' => (int) $item->sort_order,
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

    private function withApiLocale(Request $request, callable $callback): JsonResponse
    {
        $previousLocale = app()->getLocale();
        $locale = $this->resolveApiLocale($request);

        App::setLocale($locale);

        try {
            return $callback($locale);
        } finally {
            App::setLocale($previousLocale);
        }
    }

    private function resolveApiLocale(Request $request): string
    {
        $queryLocale = $this->validApiLocale($request->query('locale'));
        if ($queryLocale !== null) {
            return $queryLocale;
        }

        $headerLocale = $this->validApiLocale($request->header('X-HNT-Locale'));
        if ($headerLocale !== null) {
            return $headerLocale;
        }

        $acceptLanguageLocale = $this->localeFromAcceptLanguage((string) $request->headers->get('Accept-Language', ''));
        if ($acceptLanguageLocale !== null) {
            return $acceptLanguageLocale;
        }

        return $this->validApiLocale(app()->getLocale())
            ?? $this->validApiLocale((string) config('app.locale'))
            ?? $this->validApiLocale((string) config('app.fallback_locale'))
            ?? 'de';
    }

    private function validApiLocale(mixed $locale, bool $allowLanguageTag = false): ?string
    {
        if (! is_string($locale)) {
            return null;
        }

        $locale = strtolower(trim($locale));
        $locale = str_replace('_', '-', $locale);

        if (! $allowLanguageTag && str_contains($locale, '-')) {
            return null;
        }

        $locale = explode('-', $locale, 2)[0] ?? $locale;

        return in_array($locale, ['de', 'en'], true) ? $locale : null;
    }

    private function localeFromAcceptLanguage(string $acceptLanguage): ?string
    {
        foreach (explode(',', strtolower($acceptLanguage)) as $part) {
            $language = trim(explode(';', $part, 2)[0] ?? '');
            $locale = $this->validApiLocale($language, true);

            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }

    private function localizedTransactionDescription(CrownTransaction $transaction, string $locale): string
    {
        $action = (string) ($transaction->action ?? '');
        $label = $this->localizedActionLabel($action, $locale);

        if ($label === null) {
            return (string) ($transaction->description ?: $action ?: 'Bounty Marks');
        }

        if ($action === 'shop_purchase') {
            $itemName = $this->transactionShopItemName($transaction);

            return $itemName !== '' ? $label . ': ' . $itemName : $label;
        }

        return $label;
    }

    private function localizedActionLabel(string $action, string $locale): ?string
    {
        $labels = [
            'shop_purchase' => ['de' => 'Shop-Kauf', 'en' => 'Shop purchase'],
            'daily_login' => ['de' => 'Täglicher Login', 'en' => 'Daily login'],
            'daily_login_streak' => ['de' => 'Täglicher Login', 'en' => 'Daily login'],
            'profile_completed' => ['de' => 'Profil vervollständigt', 'en' => 'Profile completed'],
            'feed_post_created' => ['de' => 'Feed-Beitrag erstellt', 'en' => 'Feed post created'],
            'feed_comment_created' => ['de' => 'Kommentar geschrieben', 'en' => 'Comment posted'],
            'moment_created' => ['de' => 'Moment veröffentlicht', 'en' => 'Moment published'],
            'moment_comment_created' => ['de' => 'Moment kommentiert', 'en' => 'Moment commented'],
            'cup_submission_created' => ['de' => 'Cup-Ergebnis eingereicht', 'en' => 'Cup result submitted'],
            'cup_submission_approved' => ['de' => 'Cup-Einreichung bestätigt', 'en' => 'Cup submission approved'],
            'cup_idea_submitted' => ['de' => 'Cup-Idee eingereicht', 'en' => 'Cup idea submitted'],
            'cup_idea_voted' => ['de' => 'Cup-Idee bewertet', 'en' => 'Cup idea voted'],
            'loadout_challenge_submission_created' => ['de' => 'Loadout-Challenge eingereicht', 'en' => 'Loadout challenge submitted'],
            'loadout_challenge_submission_accepted' => ['de' => 'Loadout-Challenge bestätigt', 'en' => 'Loadout challenge approved'],
            'moment_of_week_selected' => ['de' => 'Moment der Woche ausgewählt', 'en' => 'Moment of the week selected'],
            'quest_completed' => ['de' => 'HNT-Auftrag abgeschlossen', 'en' => 'HNT contract completed'],
        ];

        return $labels[$action][$locale] ?? null;
    }

    private function transactionShopItemName(CrownTransaction $transaction): string
    {
        $source = $transaction->relationLoaded('source') ? $transaction->source : null;

        if ($source instanceof CrownShopItem) {
            return trim($source->displayName());
        }

        $key = $transaction->metadata['shop_item_key'] ?? null;
        if (! is_string($key) || trim($key) === '') {
            return '';
        }

        $item = CrownShopItem::query()->where('key', trim($key))->first();

        return $item ? trim($item->displayName()) : '';
    }

    /**
     * @param array<string, string> $replace
     */
    private function apiMessage(string $key, string $locale, array $replace = []): string
    {
        $messages = [
            'collect_success' => ['de' => 'Bounty Marks wurden eingesammelt.', 'en' => 'Bounty Marks collected.'],
            'collect_empty' => ['de' => 'Keine offenen Bounty Marks vorhanden.', 'en' => 'No open Bounty Marks available.'],
            'dismiss_success' => ['de' => 'Bounty-Marks-Hinweis wurde ausgeblendet.', 'en' => 'Bounty Marks notice dismissed.'],
            'shop_unavailable' => ['de' => 'Marks-Shop ist noch nicht bereit.', 'en' => 'Marks shop is not ready yet.'],
            'item_unavailable' => ['de' => 'Dieses Item ist aktuell nicht verfügbar.', 'en' => 'This item is not available right now.'],
            'already_owned' => ['de' => 'Du besitzt dieses Item bereits.', 'en' => 'You already own this item.'],
            'not_enough_or_owned' => ['de' => 'Nicht genug Bounty Marks oder Item bereits gekauft.', 'en' => 'Not enough Bounty Marks or item already purchased.'],
            'purchase_success' => ['de' => ':item wurde gekauft.', 'en' => ':item was purchased.'],
            'not_activatable' => ['de' => 'Dieses Item kann nicht aktiviert werden.', 'en' => 'This item cannot be activated.'],
            'equip_success' => ['de' => ':item wurde aktiviert.', 'en' => ':item was activated.'],
            'unequip_success' => ['de' => 'Item wurde deaktiviert.', 'en' => 'Item was deactivated.'],
        ];

        $message = $messages[$key][$locale] ?? $messages[$key]['de'] ?? $key;

        foreach ($replace as $placeholder => $value) {
            $message = str_replace(':' . $placeholder, $value, $message);
        }

        return $message;
    }
}
