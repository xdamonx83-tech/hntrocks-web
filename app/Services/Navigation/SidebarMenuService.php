<?php

namespace App\Services\Navigation;

use App\Models\SidebarMenuItem;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SidebarMenuService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function itemsForUser(User $user): array
    {
        if (! Schema::hasTable('sidebar_menu_items')) {
            return $this->fallbackItemsForUser($user);
        }

        $this->ensureDefaultItems();

        return SidebarMenuItem::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (SidebarMenuItem $item): bool => ! $item->admin_only || $user->isAdmin())
            ->filter(fn (SidebarMenuItem $item): bool => ! $this->isTrophyRoomItem($item))
            ->map(fn (SidebarMenuItem $item): ?array => $this->normaliseItem($item))
            ->filter()
            ->values()
            ->all();
    }

    public function ensureDefaultItems(): void
    {
        if (! Schema::hasTable('sidebar_menu_items')) {
            return;
        }

        foreach ($this->defaultDefinitions() as $definition) {
            $item = SidebarMenuItem::firstOrNew(['menu_key' => $definition['menu_key']]);

            $item->fill([
                'label_key' => $definition['label_key'] ?? null,
                'route_name' => $definition['route_name'] ?? null,
                'match_pattern' => $definition['match_pattern'] ?? null,
                'is_custom' => false,
            ]);

            if (! $item->exists) {
                $item->fill([
                    'label' => null,
                    'url' => null,
                    'phosphor_icon' => $definition['phosphor_icon'] ?? 'circle',
                    'section' => $definition['section'] ?? 'main',
                    'sort_order' => $definition['sort_order'] ?? 100,
                    'is_enabled' => $definition['is_enabled'] ?? true,
                    'admin_only' => $definition['admin_only'] ?? false,
                ]);
            }

            $item->save();
        }
    }

    /**
     * @return array<int, SidebarMenuItem>
     */
    public function adminItems(): array
    {
        $this->ensureDefaultItems();

        return SidebarMenuItem::query()
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
            ['menu_key' => 'feed', 'label_key' => 'ui.feed', 'route_name' => 'feed.index', 'match_pattern' => 'feed.*', 'phosphor_icon' => 'newspaper', 'section' => 'main', 'sort_order' => 10],
            ['menu_key' => 'overview', 'label_key' => 'ui.overview', 'route_name' => 'overview.index', 'match_pattern' => 'overview.*', 'phosphor_icon' => 'squares-four', 'section' => 'main', 'sort_order' => 20, 'admin_only' => true],
            ['menu_key' => 'gamification', 'label_key' => 'ui.gamification', 'route_name' => 'gamification.index', 'match_pattern' => 'gamification.*', 'phosphor_icon' => 'medal', 'section' => 'main', 'sort_order' => 30],
            ['menu_key' => 'crowns', 'label_key' => 'ui.crowns_nav', 'route_name' => 'crowns.index', 'match_pattern' => 'crowns.*', 'phosphor_icon' => 'crown-simple', 'section' => 'main', 'sort_order' => 35],
            ['menu_key' => 'teams', 'label_key' => 'ui.teams', 'route_name' => 'teams.index', 'match_pattern' => 'teams.*', 'phosphor_icon' => 'users-three', 'section' => 'main', 'sort_order' => 40],
            ['menu_key' => 'members', 'label_key' => 'ui.members', 'route_name' => 'members.index', 'match_pattern' => 'members.*', 'phosphor_icon' => 'users', 'section' => 'main', 'sort_order' => 50],
            ['menu_key' => 'lfg', 'label_key' => 'ui.lfg', 'route_name' => 'lfg.index', 'match_pattern' => 'lfg.*', 'phosphor_icon' => 'crosshair', 'section' => 'main', 'sort_order' => 60],
            ['menu_key' => 'team_lfg', 'label_key' => 'ui.team_lfg', 'route_name' => 'team-lfg.index', 'match_pattern' => 'team-lfg.*', 'phosphor_icon' => 'users-four', 'section' => 'main', 'sort_order' => 70],
            ['menu_key' => 'moments', 'label_key' => 'ui.moments', 'route_name' => 'moments.index', 'match_pattern' => 'moments.*', 'phosphor_icon' => 'play-circle', 'section' => 'main', 'sort_order' => 80, 'is_enabled' => false],
            ['menu_key' => 'cups', 'label_key' => 'ui.cups', 'route_name' => 'cups.index', 'match_pattern' => 'cups.*', 'phosphor_icon' => 'trophy', 'section' => 'main', 'sort_order' => 90],
            ['menu_key' => 'media', 'label_key' => 'ui.media_library', 'route_name' => 'media.index', 'match_pattern' => 'media.*', 'phosphor_icon' => 'images', 'section' => 'account', 'sort_order' => 100],
            ['menu_key' => 'referrals', 'label_key' => 'ui.referrals', 'route_name' => 'referrals.index', 'match_pattern' => 'referrals.*', 'phosphor_icon' => 'gift', 'section' => 'account', 'sort_order' => 110],
            ['menu_key' => 'admin', 'label_key' => 'ui.admin', 'route_name' => 'admin.index', 'match_pattern' => 'admin.*', 'phosphor_icon' => 'shield-star', 'section' => 'account', 'sort_order' => 120, 'admin_only' => true],
        ];
    }

    public function makeCustomKey(string $label): string
    {
        return 'custom_' . Str::slug($label) . '_' . Str::lower(Str::random(6));
    }

    private function normaliseItem(SidebarMenuItem $item): ?array
    {
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
            'section' => $item->section ?: 'main',
        ];
    }

    private function isTrophyRoomItem(SidebarMenuItem $item): bool
    {
        $haystack = Str::lower(implode(' ', array_filter([
            $item->menu_key,
            $item->label,
            $item->label_key,
            $item->route_name,
            $item->match_pattern,
            $item->url,
        ])));

        foreach (['trophäenraum', 'trophaenraum', 'trophyroom', 'trophy-room', 'trophy_room', '/trophy'] as $needle) {
            if (Str::contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
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
                if (! Route::has($item['route_name'])) {
                    return null;
                }

                return [
                    'key' => $item['menu_key'],
                    'label' => __($item['label_key']),
                    'url' => route($item['route_name']),
                    'match' => $item['match_pattern'],
                    'phosphor' => $item['phosphor_icon'] ?? 'circle',
                    'external' => false,
                    'section' => $item['section'] ?? 'main',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
