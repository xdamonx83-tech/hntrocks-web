<?php

namespace App\Support;

use App\Models\CrownEquippedItem;
use App\Models\CrownShopItem;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrownCosmetics
{
    /**
     * Small per-request cache for feed/comment rendering.
     *
     * @var array<int, array<string, mixed>>
     */
    private static array $userCache = [];

    /**
     * Return active Crowns cosmetics for profile rendering.
     *
     * @return array<string, mixed>
     */
    public static function forUser(User $user): array
    {
        $cacheKey = (int) $user->id;

        if ($cacheKey > 0 && isset(self::$userCache[$cacheKey])) {
            return self::$userCache[$cacheKey];
        }

        $empty = self::emptyState();

        if (! Schema::hasTable('crown_equipped_items') || ! Schema::hasTable('crown_inventory_items') || ! Schema::hasTable('crown_shop_items')) {
            return $cacheKey > 0 ? self::$userCache[$cacheKey] = $empty : $empty;
        }

        $equipped = CrownEquippedItem::query()
            ->with('inventoryItem.shopItem')
            ->where('user_id', $user->id)
            ->get()
            ->mapWithKeys(function (CrownEquippedItem $equippedItem): array {
                $shopItem = $equippedItem->inventoryItem?->shopItem;

                return $shopItem ? [$equippedItem->slot => $shopItem] : [];
            });

        /** @var CrownShopItem|null $avatarFrame */
        $avatarFrame = $equipped->get('avatar_frame');
        /** @var CrownShopItem|null $usernameEffect */
        $usernameEffect = $equipped->get('username_effect');
        /** @var CrownShopItem|null $profileBanner */
        $profileBanner = $equipped->get('profile_banner');
        /** @var CrownShopItem|null $profileTitle */
        $profileTitle = $equipped->get('profile_title');

        $state = [
            'avatar_frame' => $avatarFrame,
            'username_effect' => $usernameEffect,
            'profile_banner' => $profileBanner,
            'profile_title' => $profileTitle,
            'avatar_frame_class' => self::classFor($avatarFrame, 'avatar_frame'),
            'username_effect_class' => self::classFor($usernameEffect, 'username_effect'),
            'profile_banner_class' => self::classFor($profileBanner, 'profile_banner'),
            'profile_title_label' => self::profileTitleLabel($profileTitle),
        ];

        return $cacheKey > 0 ? self::$userCache[$cacheKey] = $state : $state;
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyState(): array
    {
        return [
            'avatar_frame' => null,
            'username_effect' => null,
            'profile_banner' => null,
            'profile_title' => null,
            'avatar_frame_class' => '',
            'username_effect_class' => '',
            'profile_banner_class' => '',
            'profile_title_label' => '',
        ];
    }

    private static function classFor(?CrownShopItem $item, string $slot): string
    {
        if (! $item) {
            return '';
        }

        $known = [
            'avatar_frame' => [
                'avatar_frame_bayou_iron' => 'hh-crowns-avatar-frame-bayou-iron',
                'avatar_frame_blood_crown' => 'hh-crowns-avatar-frame-blood-crown',
            ],
            'username_effect' => [
                'username_glow_ember' => 'hh-crowns-username-ember',
                'username_effect_bloodmarked' => 'hh-crowns-username-bloodmarked',
            ],
            'profile_banner' => [
                'profile_banner_dark_bayou' => 'hh-crowns-profile-banner-dark-bayou',
            ],
        ];

        if (isset($known[$slot][$item->key])) {
            return $known[$slot][$item->key];
        }

        $source = $item->preview_class ?: $item->key;
        $suffix = Str::slug((string) $source);

        return $suffix !== '' ? 'hh-crowns-' . Str::slug($slot) . '-' . $suffix : '';
    }

    private static function profileTitleLabel(?CrownShopItem $item): string
    {
        if (! $item) {
            return '';
        }

        $label = trim($item->displayName());
        $label = preg_replace('/^(Titel|Title)\s*:\s*/iu', '', $label) ?: $label;

        return trim($label);
    }
}
