<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EquipmentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminArsenalController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['weapon', 'tool', 'consumable'])],
            'status' => ['nullable', Rule::in(['active', 'missing'])],
            'class' => ['nullable', 'string', 'max:100'],
            'group' => ['nullable', 'string', 'max:100'],
        ]);

        $items = EquipmentItem::query()
            ->with(['translations', 'family', 'source'])
            ->withCount(['stats', 'ammo', 'traits', 'skins'])
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                        ->orWhere('slug', 'like', '%'.addcslashes($search, '%_\\').'%')
                        ->orWhere('external_id', 'like', '%'.addcslashes($search, '%_\\').'%');
                });
            })
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('item_type', $type))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('source_status', $status))
            ->when($filters['class'] ?? null, fn ($query, string $class) => $query->where('equipment_class', $class))
            ->when($filters['group'] ?? null, fn ($query, string $group) => $query->where('comparison_group', $group))
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();

        $stats = [
            'total' => EquipmentItem::query()->count(),
            'weapons' => EquipmentItem::query()->where('item_type', 'weapon')->count(),
            'tools' => EquipmentItem::query()->where('item_type', 'tool')->count(),
            'consumables' => EquipmentItem::query()->where('item_type', 'consumable')->count(),
            'missing' => EquipmentItem::query()->where('source_status', 'missing')->count(),
            'without_local_asset' => EquipmentItem::query()->whereNull('local_asset_path')->count(),
        ];

        $classes = EquipmentItem::query()
            ->whereNotNull('equipment_class')
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('item_type', $type))
            ->distinct()
            ->orderBy('equipment_class')
            ->pluck('equipment_class');

        $groups = EquipmentItem::query()
            ->whereNotNull('comparison_group')
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('item_type', $type))
            ->distinct()
            ->orderBy('comparison_group')
            ->pluck('comparison_group');

        $syncRuns = DB::table('equipment_sync_runs')
            ->leftJoin('equipment_sources', 'equipment_sources.id', '=', 'equipment_sync_runs.source_id')
            ->select([
                'equipment_sync_runs.id',
                'equipment_sync_runs.status',
                'equipment_sync_runs.dry_run',
                'equipment_sync_runs.counts',
                'equipment_sync_runs.started_at',
                'equipment_sync_runs.finished_at',
                'equipment_sources.name as source_name',
            ])
            ->latest('equipment_sync_runs.id')
            ->limit(8)
            ->get()
            ->map(function ($run) {
                $run->counts = is_string($run->counts) ? (json_decode($run->counts, true) ?: []) : (array) $run->counts;
                return $run;
            });

        $recentChanges = DB::table('equipment_sync_changes')
            ->leftJoin('equipment_items', 'equipment_items.id', '=', 'equipment_sync_changes.equipment_item_id')
            ->select([
                'equipment_sync_changes.id',
                'equipment_sync_changes.external_id',
                'equipment_sync_changes.change_type',
                'equipment_sync_changes.field',
                'equipment_sync_changes.created_at',
                'equipment_items.slug',
                'equipment_items.name',
            ])
            ->latest('equipment_sync_changes.id')
            ->limit(20)
            ->get();

        return view('admin.arsenal.index', compact(
            'items',
            'stats',
            'classes',
            'groups',
            'syncRuns',
            'recentChanges',
            'filters'
        ));
    }

    public function show(Request $request, string $slug): View
    {
        $this->guardAdmin($request);

        $item = EquipmentItem::query()
            ->where('slug', $slug)
            ->with([
                'source',
                'family',
                'translations',
                'stats.definition',
                'ammo.falloffPoints',
                'traits',
                'skins',
                'patchHistory',
            ])
            ->firstOrFail();

        $variants = $item->family_id
            ? EquipmentItem::query()
                ->where('family_id', $item->family_id)
                ->whereKeyNot($item->id)
                ->orderBy('name')
                ->get(['id', 'slug', 'name', 'equipment_class', 'source_status'])
            : collect();

        $changes = DB::table('equipment_sync_changes')
            ->where('equipment_item_id', $item->id)
            ->latest('id')
            ->limit(30)
            ->get();

        return view('admin.arsenal.show', compact('item', 'variants', 'changes'));
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
