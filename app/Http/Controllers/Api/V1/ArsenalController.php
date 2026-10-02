<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{EquipmentItem,EquipmentStatDefinition};
use App\Services\Equipment\WeaponBallisticsPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ArsenalController extends Controller
{
    private const MODE_SENSITIVE_STATS = [
        'damage', 'dropRange', 'effectiveRange', 'rateOfFire', 'cycleTime', 'reload',
        'muzzleVelocity', 'spread', 'sway', 'recoil', 'magazine', 'reserve',
        'baseDamage', 'headMultiplier', 'upperTorsoMultiplier', 'torsoMultiplier',
        'armMultiplier', 'legMultiplier',
    ];

    public function index(Request $request): JsonResponse
    {
        $v = $request->validate([
            'search'=>'nullable|string|max:100','category'=>'nullable|string|max:80','class'=>'nullable|string|max:80',
            'type'=>'nullable|in:weapon,tool,consumable,ammo',
            'ammo'=>'nullable|string|max:80','family'=>'nullable|string|max:100','comparison_group'=>'nullable|string|max:80',
            'sort'=>'nullable|in:name,price,damage,velocity','page'=>'nullable|integer|min:1','per_page'=>'nullable|integer|min:1|max:100',
        ]);
        $q = EquipmentItem::query()->where('source_status','active');
        if (isset($v['type'])) $q->where('item_type',$v['type']);
        if (isset($v['search'])) $q->where('name','like','%'.addcslashes($v['search'],'%_\\').'%');
        foreach (['category'=>'category','class'=>'equipment_class','ammo'=>'ammo_type','comparison_group'=>'comparison_group'] as $input=>$column)
            if (isset($v[$input])) $q->where($column,$v[$input]);
        if (isset($v['family'])) $q->whereHas('family',fn($f)=>$f->where('key',$v['family']));
        $sort = $v['sort'] ?? 'name';
        if ($sort === 'name') $q->orderBy('name');
        elseif ($sort === 'price') $q->orderBy('price')->orderBy('name');
        else $q->orderBy(EquipmentItem::query()->select('value')->from('equipment_stats')
            ->join('equipment_stat_definitions','equipment_stat_definitions.id','=','equipment_stats.stat_definition_id')
            ->whereColumn('equipment_stats.equipment_item_id','equipment_items.id')
            ->where('equipment_stat_definitions.key',$sort === 'velocity' ? 'muzzleVelocity' : 'damage')->limit(1),'desc')->orderBy('name');
        $page = $q->with('translations')->paginate($v['per_page'] ?? 24);
        return response()->json($page->through(fn($item)=>$this->compact($item,$request)));
    }

    public function categories(Request $request): JsonResponse
    {
        $v = $request->validate(['type'=>'nullable|in:weapon,tool,consumable,ammo']);
        $q = EquipmentItem::where('source_status','active')->whereNotNull('category');
        if (isset($v['type'])) $q->where('item_type',$v['type']);
        return response()->json($q->distinct()->orderBy('category')->pluck('category'));
    }

    public function classes(Request $request): JsonResponse
    {
        $v = $request->validate(['type'=>'nullable|in:weapon,tool,consumable,ammo']);
        $q = EquipmentItem::where('source_status','active')->whereNotNull('equipment_class');
        if (isset($v['type'])) $q->where('item_type',$v['type']);
        return response()->json($q->distinct()->orderBy('equipment_class')->pluck('equipment_class'));
    }

    public function show(string $slug, Request $request, WeaponBallisticsPresenter $ballistics): JsonResponse
    {
        $item = EquipmentItem::where('slug',$slug)->where('source_status','active')->with(['source','translations','family','stats.definition','ammo.falloffPoints','traits','skins','patchHistory'])->firstOrFail();
        return response()->json($this->detail($item,$request,$ballistics));
    }

    public function ballistics(string $slug, Request $request, WeaponBallisticsPresenter $ballistics): JsonResponse
    {
        $validated = $request->validate([
            'ammo_key' => 'required|string|max:160',
            'distance' => 'required|numeric|min:0|max:1000',
            'hp' => 'required|integer|in:150,125,100,75,50',
        ]);
        $item = EquipmentItem::where('slug',$slug)->where('source_status','active')
            ->where('item_type','weapon')->with(['stats.definition','ammo.falloffPoints'])->firstOrFail();
        $result = $ballistics->atDistance($item, $validated['ammo_key'],
            (float) $validated['distance'], (int) $validated['hp']);
        abort_if($result === null, 404);

        return response()->json($result);
    }

    public function related(string $slug, Request $request): JsonResponse
    {
        $item = EquipmentItem::where('slug',$slug)->where('source_status','active')->firstOrFail();
        $items = EquipmentItem::where('source_status','active')->where('comparison_group',$item->comparison_group)->whereKeyNot($item->id)
            ->orderByRaw('CASE WHEN family_id = ? THEN 0 ELSE 1 END',[$item->family_id ?? 0])
            ->orderBy('name')->limit(12)->with('translations')->get();
        return response()->json(['items'=>$items->map(fn($i)=>$this->compact($i,$request))]);
    }

    public function compare(Request $request): JsonResponse
    {
        $request->validate(['items'=>'required|string|max:300']);
        $slugs = explode(',',(string)$request->query('items'));
        if (count($slugs) < 2 || count($slugs) > 3 || count(array_unique($slugs)) !== count($slugs))
            throw ValidationException::withMessages(['items'=>'Choose two or three distinct items.']);
        $found = EquipmentItem::whereIn('slug',$slugs)->where('source_status','active')->with(['translations','stats.definition','ammo.falloffPoints'])->get()->keyBy('slug');
        if ($found->count() !== count($slugs)) throw ValidationException::withMessages(['items'=>'One or more items were not found.']);
        $items = collect($slugs)->map(fn($slug)=>$found[$slug]);
        if ($items->pluck('comparison_group')->unique()->count() !== 1)
            throw ValidationException::withMessages(['items'=>'Items must share a comparison group.']);
        $definitions = EquipmentStatDefinition::orderBy('sort_order')->get();
        $stats = $definitions->map(function ($def) use ($items) {
            $values = $items->mapWithKeys(fn($i)=>[$i->slug => $i->stats->firstWhere('stat_definition_id',$def->id)?->value]);
            return ['key'=>$def->key,'label'=>$def->label,'unit'=>$def->unit,'group'=>$def->group,
                'comparison_direction'=>$def->comparison_direction,'values'=>$values];
        })->filter(fn($s)=>$s['values']->filter(fn($v)=>$v !== null)->isNotEmpty())->values();
        return response()->json(['items'=>$items->map(fn($i)=>$this->compact($i,$request)),'compatible'=>true,
            'comparison_group'=>$items->first()->comparison_group,'stats'=>$stats,
            'has_multiple_ammo_modes'=>$items->contains(fn($i)=>$this->hasMultipleAmmoModes($i)),
            'mode_ambiguous_stat_keys'=>$items->flatMap(fn($i)=>$this->modeAmbiguousStatKeys($i))->unique()->values(),
            'stat_definitions'=>$definitions->map(fn($d)=>['key'=>$d->key,'label'=>$d->label,'unit'=>$d->unit,
                'comparison_direction'=>$d->comparison_direction,'group'=>$d->group,'sort_order'=>$d->sort_order])->values(),
            'ammo'=>$items->mapWithKeys(fn($i)=>[$i->slug=>$this->ammoPayload($i)]),
            'falloff_curves'=>$items->mapWithKeys(fn($i)=>[$i->slug=>$i->ammo->mapWithKeys(fn($a)=>[$a->key=>$a->falloffPoints->map(fn($p)=>$this->pointPayload($p))->values()])]),
            'differences'=>$stats->filter(fn($s)=>$s['values']->filter(fn($v)=>$v !== null)->unique()->count()>1)->pluck('key')->values()]);
    }

    public function statRanges(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comparison_group' => 'required|string|max:80',
            'category' => 'nullable|string|max:80',
            'class' => 'nullable|string|max:80',
        ]);
        $ranges = [];
        $query = EquipmentItem::query()->where('source_status', 'active')
            ->where('item_type', 'weapon')->where('comparison_group', $validated['comparison_group']);
        if (isset($validated['category'])) $query->where('category', $validated['category']);
        if (isset($validated['class'])) $query->where('equipment_class', $validated['class']);
        $items = $query->with('stats.definition')->get();

        foreach ($items as $item) {
            foreach ($item->stats as $stat) {
                $key = $stat->definition?->key;
                if ($key === null || $stat->value === null) continue;
                $value = (float) $stat->value;
                if (! isset($ranges[$key])) {
                    $ranges[$key] = ['min' => $value, 'max' => $value, 'count' => 0];
                }
                $ranges[$key]['min'] = min($ranges[$key]['min'], $value);
                $ranges[$key]['max'] = max($ranges[$key]['max'], $value);
                $ranges[$key]['count']++;
            }
        }

        return response()->json(['comparison_group' => $validated['comparison_group'], 'ranges' => $ranges]);
    }

    private function compact(EquipmentItem $item, Request $request): array
    {
        $locale = in_array($request->query('locale'),['de','en','es','ru'],true) ? $request->query('locale') : 'en';
        $translation = $item->translations->firstWhere('locale',$locale) ?? $item->translations->firstWhere('locale','en');
        return ['id'=>$item->id,'slug'=>$item->slug,'name'=>$translation?->name ?? $item->name,'description'=>$translation?->description,
            'item_type'=>$item->item_type,'category'=>$item->category,'class'=>$item->equipment_class,
            'comparison_group'=>$item->comparison_group,'ammo_type'=>$item->ammo_type,'slot_size'=>$item->slot_size,
            'price'=>$item->price,'unlock_rank'=>$item->unlock_rank,'image_url'=>$item->imageUrl()];
    }

    private function detail(EquipmentItem $item, Request $request, WeaponBallisticsPresenter $ballistics): array
    {
        $variants = $item->family_id ? EquipmentItem::where('family_id',$item->family_id)->where('source_status','active')
            ->whereKeyNot($item->id)->orderBy('name')->with('translations')->get()->map(fn($variant)=>$this->compact($variant,$request)) : [];
        return $this->compact($item,$request) + [
            'family'=>$item->family ? ['key'=>$item->family->key,'name'=>$item->family->name] : null,
            'stats'=>$item->stats->sortBy(fn($s)=>$s->definition?->sort_order ?? 9999)->map(fn($s)=>[
                'key'=>$s->definition?->key,
                'label'=>$s->definition?->label,
                'unit'=>$s->definition?->unit,
                'group'=>$s->definition?->group,
                'sort_order'=>$s->definition?->sort_order,
                'comparison_direction'=>$s->definition?->comparison_direction,
                'value'=>$s->value,
            ])->values(),
            'has_multiple_ammo_modes'=>$this->hasMultipleAmmoModes($item),
            'mode_ambiguous_stat_keys'=>$this->modeAmbiguousStatKeys($item),
            'ammo'=>$this->ammoPayload($item),
            'variants'=>$variants,
            'traits'=>$item->traits->map(fn($trait)=>['id'=>$trait->external_id,'name'=>$trait->name])->values(),
            'skins'=>$item->skins->map(fn($skin)=>[
                'id'=>$skin->external_id,
                'name'=>$skin->name,
                'rarity'=>$skin->rarity,
                'image_url'=>$skin->imageUrl(),
                'price'=>data_get($skin->facts, 'wiki_gg.price'),
                'acquisition_source'=>data_get($skin->facts, 'wiki_gg.source'),
                'update'=>data_get($skin->facts, 'wiki_gg.update'),
            ])->values(),
            'details'=>[
                'chamber'=>$item->facts['chamber'] ?? null,
                'cylinder'=>$item->facts['cylinder'] ?? null,
                'release_pack'=>$item->facts['release_pack'] ?? null,
                'rarity'=>$item->facts['rarity'] ?? null,
                'min_rank'=>$item->facts['min_rank'] ?? null,
            ],
            'patch_history'=>$item->patchHistory->map(fn($entry)=>['patch'=>$entry->patch,'field'=>$entry->field,
                'old_value'=>$entry->old_value,'new_value'=>$entry->new_value,'note'=>$entry->note])->values(),
            'ballistics'=>$item->item_type === 'weapon' ? $ballistics->summary($item) : null];
    }

    private function ammoPayload(EquipmentItem $item): array
    {
        $definitions = EquipmentStatDefinition::query()->whereIn('key', $item->ammo->flatMap(
            fn($ammo)=>array_keys($this->ammoRawStats($ammo->facts))
        )->unique())->get()->keyBy('key');
        return $item->ammo->map(fn($ammo)=>['key'=>$ammo->key,'name'=>$ammo->name,'ammo_type'=>$ammo->ammo_type,
            'damage'=>$ammo->damage,'velocity'=>$ammo->velocity,'loaded'=>$ammo->loaded,'reserve'=>$ammo->reserve,
            'facts'=>$ammo->facts,
            'mode_stats'=>collect($this->ammoRawStats($ammo->facts))->filter(
                fn($value,$key)=>is_numeric($value) && is_finite((float)$value) && $definitions->has($key)
            )->map(fn($value,$key)=>[
                'key'=>$key,'label'=>$definitions[$key]->label,'unit'=>$definitions[$key]->unit,
                'value'=>(float)$value,'sort_order'=>$definitions[$key]->sort_order,
            ])->sortBy('sort_order')->values(),
            'falloff_points'=>$ammo->falloffPoints->map(fn($p)=>$this->pointPayload($p))->values()])->values()->all();
    }

    private function ammoRawStats(?array $facts): array
    {
        $stats = $facts['stats'] ?? [];
        return is_array($stats) ? $stats : [];
    }

    private function hasMultipleAmmoModes(EquipmentItem $item): bool
    {
        return $item->ammo->pluck('ammo_type')->filter()->unique()->count() > 1;
    }

    private function modeAmbiguousStatKeys(EquipmentItem $item): array
    {
        if (! $this->hasMultipleAmmoModes($item)) return [];
        return $item->stats->pluck('definition.key')->filter(
            fn($key)=>in_array($key, self::MODE_SENSITIVE_STATS, true)
        )->values()->all();
    }

    private function pointPayload($point): array
    {
        return ['distance'=>$point->distance,'damage'=>$point->damage];
    }
}
