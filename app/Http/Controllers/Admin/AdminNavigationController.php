<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileNavItem;
use App\Models\SidebarMenuItem;
use App\Services\Navigation\MobileNavService;
use App\Services\Navigation\SidebarMenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminNavigationController extends Controller
{
    public function index(Request $request, SidebarMenuService $sidebarMenu, MobileNavService $mobileNav): View
    {
        $this->guardAdmin($request);

        return view('admin.navigation.index', [
            'items' => $sidebarMenu->adminItems(),
            'mobileItems' => $mobileNav->adminItems(),
            'sidebarNavigationAvailable' => Schema::hasTable('sidebar_menu_items'),
            'mobileNavigationAvailable' => Schema::hasTable('mobile_nav_items'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);

        if (! Schema::hasTable('sidebar_menu_items')) {
            return $this->missingNavigationTable('sidebar_menu_items');
        }

        $validated = $request->validate([
            'items' => ['array'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'items.*.phosphor_icon' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/i'],
            'items.*.section' => ['nullable', 'in:main,account'],
            'items.*.url' => ['nullable', 'string', 'max:2048'],
            'items.*.label' => ['nullable', 'string', 'max:80'],
            'items.*.delete' => ['nullable', 'boolean'],
        ]);

        $enabledIds = collect($request->input('enabled', []))->map(fn ($id) => (int) $id)->all();
        $adminOnlyIds = collect($request->input('admin_only', []))->map(fn ($id) => (int) $id)->all();

        foreach (($validated['items'] ?? []) as $id => $payload) {
            $item = SidebarMenuItem::find((int) $id);
            if (! $item) {
                continue;
            }

            if ($item->is_custom && ! empty($payload['delete'])) {
                $item->delete();
                continue;
            }

            $item->is_enabled = in_array($item->id, $enabledIds, true);
            $item->admin_only = in_array($item->id, $adminOnlyIds, true);
            if (! $item->is_custom && in_array($item->menu_key, ['overview', 'admin'], true)) {
                $item->admin_only = true;
            }
            $item->sort_order = (int) ($payload['sort_order'] ?? $item->sort_order);
            $item->phosphor_icon = $this->cleanIcon($payload['phosphor_icon'] ?? $item->phosphor_icon);
            $item->section = $payload['section'] ?? $item->section;

            if ($item->is_custom) {
                $item->label = trim((string) ($payload['label'] ?? $item->label));
                $item->url = $this->normaliseCustomUrl((string) ($payload['url'] ?? $item->url));
            }

            $item->save();
        }

        return redirect()->route('admin.navigation.index')->with('status', 'Sidebar-Menü gespeichert.');
    }

    public function store(Request $request, SidebarMenuService $sidebarMenu): RedirectResponse
    {
        $this->guardAdmin($request);

        if (! Schema::hasTable('sidebar_menu_items')) {
            return $this->missingNavigationTable('sidebar_menu_items');
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'url' => ['required', 'string', 'max:2048'],
            'phosphor_icon' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/i'],
            'section' => ['nullable', 'in:main,account'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_enabled' => ['nullable', 'boolean'],
            'admin_only' => ['nullable', 'boolean'],
        ]);

        SidebarMenuItem::create([
            'menu_key' => $sidebarMenu->makeCustomKey($validated['label']),
            'label' => trim($validated['label']),
            'label_key' => null,
            'route_name' => null,
            'url' => $this->normaliseCustomUrl($validated['url']),
            'match_pattern' => null,
            'phosphor_icon' => $this->cleanIcon($validated['phosphor_icon'] ?? 'link'),
            'section' => $validated['section'] ?? 'main',
            'sort_order' => (int) ($validated['sort_order'] ?? 900),
            'is_enabled' => $request->boolean('is_enabled', true),
            'admin_only' => $request->boolean('admin_only', false),
            'is_custom' => true,
        ]);

        return redirect()->route('admin.navigation.index')->with('status', 'Menüpunkt hinzugefügt.');
    }


    public function updateMobile(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);

        if (! Schema::hasTable('mobile_nav_items')) {
            return $this->missingNavigationTable('mobile_nav_items');
        }

        $validated = $request->validate([
            'mobile_items' => ['array'],
            'mobile_items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'mobile_items.*.phosphor_icon' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/i'],
            'mobile_items.*.url' => ['nullable', 'string', 'max:2048'],
            'mobile_items.*.label' => ['nullable', 'string', 'max:80'],
            'mobile_items.*.delete' => ['nullable', 'boolean'],
        ]);

        $enabledIds = collect($request->input('mobile_enabled', []))->map(fn ($id) => (int) $id)->all();
        $adminOnlyIds = collect($request->input('mobile_admin_only', []))->map(fn ($id) => (int) $id)->all();

        foreach (($validated['mobile_items'] ?? []) as $id => $payload) {
            $item = MobileNavItem::find((int) $id);
            if (! $item) {
                continue;
            }

            if ($item->is_custom && ! empty($payload['delete'])) {
                $item->delete();
                continue;
            }

            $item->is_enabled = in_array($item->id, $enabledIds, true);
            $item->admin_only = in_array($item->id, $adminOnlyIds, true);
            $item->sort_order = (int) ($payload['sort_order'] ?? $item->sort_order);
            $item->phosphor_icon = $this->cleanIcon($payload['phosphor_icon'] ?? $item->phosphor_icon);

            if ($item->is_custom) {
                $item->label = trim((string) ($payload['label'] ?? $item->label));
                $item->url = $this->normaliseCustomUrl((string) ($payload['url'] ?? $item->url));
            }

            $item->save();
        }

        return redirect()->route('admin.navigation.index')->with('status', 'Mobile-Bottom-Navigation gespeichert.');
    }

    public function storeMobile(Request $request, MobileNavService $mobileNav): RedirectResponse
    {
        $this->guardAdmin($request);

        if (! Schema::hasTable('mobile_nav_items')) {
            return $this->missingNavigationTable('mobile_nav_items');
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'url' => ['required', 'string', 'max:2048'],
            'phosphor_icon' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/i'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_enabled' => ['nullable', 'boolean'],
            'admin_only' => ['nullable', 'boolean'],
        ]);

        MobileNavItem::create([
            'menu_key' => $mobileNav->makeCustomKey($validated['label']),
            'label' => trim($validated['label']),
            'label_key' => null,
            'route_name' => null,
            'url' => $this->normaliseCustomUrl($validated['url']),
            'match_pattern' => null,
            'phosphor_icon' => $this->cleanIcon($validated['phosphor_icon'] ?? 'link'),
            'action' => 'link',
            'sort_order' => (int) ($validated['sort_order'] ?? 900),
            'is_enabled' => $request->boolean('is_enabled', true),
            'admin_only' => $request->boolean('admin_only', false),
            'is_custom' => true,
        ]);

        return redirect()->route('admin.navigation.index')->with('status', 'Mobile-Menüpunkt hinzugefügt.');
    }

    private function cleanIcon(?string $icon): string
    {
        $icon = trim((string) $icon);

        return $icon !== '' ? Str::lower($icon) : 'circle';
    }

    private function normaliseCustomUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '/';
        }

        if (Str::startsWith($url, ['http://', 'https://', '/'])) {
            return $url;
        }

        return '/' . ltrim($url, '/');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function missingNavigationTable(string $table): RedirectResponse
    {
        return redirect()
            ->route('admin.navigation.index')
            ->withErrors(['navigation' => "Die Navigationstabelle {$table} fehlt. Bitte die Migrationen ausführen."]);
    }
}
