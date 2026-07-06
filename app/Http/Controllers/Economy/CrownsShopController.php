<?php

namespace App\Http\Controllers\Economy;

use App\Http\Controllers\Controller;
use App\Models\CrownEquippedItem;
use App\Models\CrownInventoryItem;
use App\Models\CrownShopItem;
use App\Services\Economy\CrownsService;
use App\Support\HntTheme;
use App\Support\ReworkFeedSidebar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CrownsShopController extends Controller
{
    public function shop(Request $request, CrownsService $crowns): View
    {
        $user = $request->user();
        $crowns->rewardCompletedProfileIfEligible($user);

        $items = CrownShopItem::query()
            ->available()
            ->withCount('inventoryItems')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        $inventoryByItemId = CrownInventoryItem::query()
            ->with('equippedItem')
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('shop_item_id');

        $ownedItemIds = $inventoryByItemId
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $sidebarData = ReworkFeedSidebar::forViewer($user);

        return view($this->themeView('crowns.shop'), [
            'summary' => $crowns->summary($user),
            'items' => $items,
            'inventoryByItemId' => $inventoryByItemId,
            'ownedItemIds' => $ownedItemIds,
            'nonCashNotice' => (string) config('crowns.non_cash_notice'),
            'socialiteMembers' => $sidebarData['members'],
            'socialiteProfileStats' => $sidebarData['profileStats'],
            'socialiteCrownsSummary' => $sidebarData['crownsSummary'],
            'socialiteHighlightTopPost' => $sidebarData['highlightTopPost'],
            'socialiteHighlightLfg' => $sidebarData['highlightLfg'],
            'socialiteHighlightCup' => $sidebarData['highlightCup'],
        ]);
    }

    public function inventory(Request $request, CrownsService $crowns): View
    {
        $user = $request->user();
        $crowns->rewardCompletedProfileIfEligible($user);

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

        $sidebarData = ReworkFeedSidebar::forViewer($user);

        return view($this->themeView('crowns.inventory'), [
            'summary' => $crowns->summary($user),
            'inventoryItems' => $inventoryItems,
            'equippedBySlot' => $equippedBySlot,
            'socialiteMembers' => $sidebarData['members'],
            'socialiteProfileStats' => $sidebarData['profileStats'],
            'socialiteCrownsSummary' => $sidebarData['crownsSummary'],
            'socialiteHighlightTopPost' => $sidebarData['highlightTopPost'],
            'socialiteHighlightLfg' => $sidebarData['highlightLfg'],
            'socialiteHighlightCup' => $sidebarData['highlightCup'],
        ]);
    }

    public function purchase(Request $request, CrownShopItem $item, CrownsService $crowns): RedirectResponse
    {
        $user = $request->user();

        if (! $this->isPurchasable($item)) {
            return back()->with('error', __('ui.crowns_shop_item_unavailable'));
        }

        $alreadyOwned = CrownInventoryItem::query()
            ->where('user_id', $user->id)
            ->where('shop_item_id', $item->id)
            ->exists();

        if ($alreadyOwned) {
            return back()->with('error', __('ui.crowns_shop_already_owned'));
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

            $offerActive = $item->offerActive();
            $effectivePrice = $item->effectivePrice();

            $transaction = $crowns->spend(
                $user,
                $effectivePrice,
                'shop_purchase',
                $item,
                __('ui.crowns_shop_transaction_purchase', ['item' => $item->displayName()]),
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
            return back()->with('error', __('ui.crowns_shop_not_enough_or_owned'));
        }

        return redirect()->route('crowns.inventory')->with('status', __('ui.crowns_shop_purchase_success', ['item' => $item->displayName()]));
    }

    public function equip(Request $request, CrownInventoryItem $inventoryItem): RedirectResponse
    {
        $user = $request->user();

        if ((int) $inventoryItem->user_id !== (int) $user->id) {
            abort(403);
        }

        $inventoryItem->load('shopItem');
        $item = $inventoryItem->shopItem;

        if (! $item || ! $item->isActivatable()) {
            return back()->with('error', __('ui.crowns_inventory_not_activatable'));
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

        return back()->with('status', __('ui.crowns_inventory_equipped', ['item' => $item->displayName()]));
    }

    public function unequip(Request $request, string $slot): RedirectResponse
    {
        CrownEquippedItem::query()
            ->where('user_id', $request->user()->id)
            ->where('slot', $slot)
            ->delete();

        return back()->with('status', __('ui.crowns_inventory_unequipped'));
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
    private function themeView(string $view): string
    {
        return HntTheme::enabled()
            ? HntTheme::resolve($view)
            : 'themes.socialite.' . $view;
    }
}
