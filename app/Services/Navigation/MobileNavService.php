<?php

namespace App\Services\Navigation;

use App\Models\MobileNavItem;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MobileNavService
{
    public const PROFILE_SHEET_ACTION = 'profile_sheet';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function itemsForUser(User $user): array
    {
        if (! Schema::hasTable('mobile_nav_items')) {
            return $this->fallbackItemsForUser($user);
        }

        $this->ensureDefaultItems();

        return MobileNavItem::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (MobileNavItem $item): bool => ! $item->admin_only || $user->isAdmin())
            ->map(fn (MobileNavItem $item): ?array => $this->normaliseItem($item))
            ->filter()
            ->values()
            ->all();
    }

    public function ensureDefaultItems(): void
    {
        if (! Schema::hasTable('mobile_nav_items')) {
            return;
        }

        foreach ($this->defaultDefinitions() as $definition) {
            $item = MobileNavItem::firstOrNew(['menu_key' => $definition['menu_key']]);

            $item->fill([
                'label_key' => $definition['label_key'] ?? null,
                'route_name' => $definition['route_name'] ?? null,
                'match_pattern' => $definition['match_pattern'] ?? null,
                'action' => $definition['action'] ?? 'link',
                'is_custom' => false,
            ]);

            if (! $item->exists) {
                $item->fill([
                    'label' => null,
                    'url' => null,
                    'phosphor_icon' => $definition['phosphor_icon'] ?? 'circle',
                    'sort_order' => $definition['sort_order'] ?? 100,
                    'is_enabled' => $definition['is_enabled'] ?? true,
                    'admin_only' => $definition['admin_only'] ?? false,
                ]);
            }

            $item->save();
        }
    }

    /**
     * @return array<int, MobileNavItem>
     */
    public function adminItems(): array
    {
        $this->ensureDefaultItems();

        if (! Schema::hasTable('mobile_nav_items')) {
            return [];
        }

        return MobileNavItem::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function defaultDefinitions(): array
    {
        return [
            ['menu_key' => 'feed', 'label_key' => 'ui.feed', 'route_name' => 'feed.index', 'match_pattern' => 'feed.*', 'phosphor_icon' => 'newspaper', 'sort_order' => 10],
            ['menu_key' => 'lfg', 'label_key' => 'ui.lfg', 'route_name' => 'lfg.index', 'match_pattern' => 'lfg.*', 'phosphor_icon' => 'crosshair', 'sort_order' => 20],
            ['menu_key' => 'teams', 'label_key' => 'ui.teams', 'route_name' => 'teams.index', 'match_pattern' => 'teams.*', 'phosphor_icon' => 'users-three', 'sort_order' => 30],
            ['menu_key' => 'profile_sheet', 'label_key' => 'ui.mobile_profile_nav', 'action' => self::PROFILE_SHEET_ACTION, 'match_pattern' => 'profile.*,account.*,settings.*,messages.*,notifications.*', 'phosphor_icon' => 'user-circle', 'sort_order' => 40],
            ['menu_key' => 'cups', 'label_key' => 'ui.cups', 'route_name' => 'cups.index', 'match_pattern' => 'cups.*', 'phosphor_icon' => 'trophy', 'sort_order' => 50, 'is_enabled' => true],
            ['menu_key' => 'maps', 'label_key' => 'ui.maps', 'route_name' => 'maps.index', 'match_pattern' => 'maps.*', 'phosphor_icon' => 'map-trifold', 'sort_order' => 55, 'is_enabled' => false, 'admin_only' => false],
            ['menu_key' => 'moments', 'label_key' => 'ui.moments', 'route_name' => 'moments.index', 'match_pattern' => 'moments.*', 'phosphor_icon' => 'play-circle', 'sort_order' => 60, 'is_enabled' => false],
        ];
    }

    public function makeCustomKey(string $label): string
    {
        return 'custom_' . Str::slug($label) . '_' . Str::lower(Str::random(6));
    }

    private function normaliseItem(MobileNavItem $item): ?array
    {
        if ($item->action === self::PROFILE_SHEET_ACTION) {
            return [
                'key' => $item->menu_key,
                'label' => $item->displayLabel(),
                'url' => null,
                'match' => $item->match_pattern,
                'phosphor' => $item->phosphor_icon ?: 'user-circle',
                'external' => false,
                'action' => self::PROFILE_SHEET_ACTION,
                'active' => $this->isMatchActive($item->match_pattern),
            ];
        }

        $url = $item->url;
        $isRouteItem = false;

        if ($item->route_name) {
            if (! Route::has($item->route_name)) {
                return null;
            }

            $url = route($item->route_name);
            $isRouteItem = true;
        }

        if (! $url) {
            return null;
        }

        return [
            'key' => $item->menu_key,
            'label' => $item->displayLabel(),
            'url' => $url,
            'match' => $item->match_pattern,
            'phosphor' => $item->phosphor_icon ?: 'circle',
            'external' => $isRouteItem ? false : $this->isExternalUrl($url),
            'action' => 'link',
            'active' => $this->isMatchActive($item->match_pattern),
        ];
    }

    private function isMatchActive(?string $matchPattern): bool
    {
        if (! $matchPattern) {
            return false;
        }

        $patterns = collect(explode(',', $matchPattern))
            ->map(fn (string $pattern): string => trim($pattern))
            ->filter()
            ->all();

        return $patterns !== [] && request()->routeIs(...$patterns);
    }

    private function isExternalUrl(string $url): bool
    {
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return false;
        }

        $linkHost = parse_url($url, PHP_URL_HOST);

        if (! $linkHost) {
            return false;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $currentHost = request()->getHost();

        return $linkHost !== $appHost && $linkHost !== $currentHost;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fallbackItemsForUser(User $user): array
    {
        return collect($this->defaultDefinitions())
            ->filter(fn (array $item): bool => $item['is_enabled'] ?? true)
            ->filter(fn (array $item): bool => empty($item['admin_only']) || $user->isAdmin())
            ->map(function (array $item): ?array {
                if (($item['action'] ?? 'link') === self::PROFILE_SHEET_ACTION) {
                    return [
                        'key' => $item['menu_key'],
                        'label' => __($item['label_key']),
                        'url' => null,
                        'match' => $item['match_pattern'] ?? null,
                        'phosphor' => $item['phosphor_icon'] ?? 'user-circle',
                        'external' => false,
                        'action' => self::PROFILE_SHEET_ACTION,
                        'active' => $this->isMatchActive($item['match_pattern'] ?? null),
                    ];
                }

                if (! Route::has($item['route_name'])) {
                    return null;
                }

                return [
                    'key' => $item['menu_key'],
                    'label' => __($item['label_key']),
                    'url' => route($item['route_name']),
                    'match' => $item['match_pattern'] ?? null,
                    'phosphor' => $item['phosphor_icon'] ?? 'circle',
                    'external' => false,
                    'action' => 'link',
                    'active' => $this->isMatchActive($item['match_pattern'] ?? null),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
