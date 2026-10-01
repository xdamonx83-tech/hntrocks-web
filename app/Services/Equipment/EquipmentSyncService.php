<?php
namespace App\Services\Equipment;

use App\Models\{EquipmentFamily,EquipmentFieldProvenance,EquipmentItem,EquipmentSource,EquipmentSourceSnapshot,EquipmentStatDefinition,EquipmentSyncChange,EquipmentSyncRun,EquipmentTrait};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class EquipmentSyncService
{
    public function __construct(
        private EquipmentStatCatalog $catalog,
        private EquipmentDescriptionGenerator $descriptions,
        private EquipmentSourceSnapshotService $snapshots,
        private EquipmentMergePolicy $policy,
    ) {}

    public function sync(EquipmentSourceInterface $adapter, bool $dryRun = false, ?string $item = null): array
    {
        $counts = ['new'=>0,'changed'=>0,'unchanged'=>0,'missing'=>0,'errors'=>0];
        $source = EquipmentSource::where('key', $adapter->key())->first();
        $run = null;
        if (! $dryRun) {
            $source = EquipmentSource::updateOrCreate(['key'=>$adapter->key()], ['name'=>$adapter->name(),'base_url'=>$adapter->baseUrl()]);
            $this->catalog->ensure();
            $run = EquipmentSyncRun::create(['source_id'=>$source->id,'status'=>'running','dry_run'=>false,'counts'=>$counts,'started_at'=>now()]);
        }
        $seen = [];
        $feedComplete = false;
        try {
            foreach ($adapter->items() as $raw) {
                $id = (string) ($raw['id'] ?? '');
                if ($id === '') { $counts['errors']++; continue; }
                if ($item !== null && $item !== $id && $item !== Str::slug((string)($raw['name'] ?? ''))) continue;
                if (isset($seen[$id])) { $counts['errors']++; continue; }
                $seen[$id] = true;
                $kind = null;
                try {
                    $facts = $this->facts($raw);
                    $hash = hash('sha256', json_encode($facts, JSON_THROW_ON_ERROR));
                    $existing = $source ? EquipmentItem::where('source_id',$source->id)->where('external_id',$id)->first() : null;
                    if (! $dryRun && $existing &&
                        ! EquipmentFieldProvenance::query()->where('equipment_item_id',$existing->id)
                            ->where('field_key','__baseline')->where('source_key',$adapter->key())->exists()) {
                        throw new RuntimeException('Legacy item has no reviewed source baseline; canonical sync is blocked.');
                    }
                    $kind = ! $existing ? 'new' : ($existing->source_hash !== $hash || $existing->source_status !== 'active' ? 'changed' : 'unchanged');
                    $counts[$kind]++;
                    if ($dryRun) continue;
                    DB::transaction(function () use ($raw,$facts,$hash,$existing,$source,$run,$id,$kind,$adapter) {
                        $familyId = null;
                        if (! empty($facts['family'])) {
                            $family = EquipmentFamily::firstOrCreate(['key'=>$facts['family']], ['name'=>Str::headline($facts['family'])]);
                            $familyId = $family->id;
                        }
                        $slug = $existing?->slug ?? $this->uniqueSlug((string)$raw['name'], $id);
                        $attributes = [
                            'slug'=>$slug,'name'=>$facts['name'],'item_type'=>$facts['item_type'],
                            'category'=>$facts['category'],'equipment_class'=>$facts['equipment_class'],
                            'comparison_group'=>$facts['comparison_group'],'family_id'=>$familyId,
                            'ammo_type'=>$facts['ammo_type'],'slot_size'=>$facts['slot_size'],'price'=>$facts['price'],
                            'unlock_rank'=>$facts['unlock_rank'],
                            'original_asset_url'=>$facts['icon_source_url'],
                            'source_status'=>'active','source_url'=>$facts['source_url'],'source_hash'=>$hash,
                            'last_synced_at'=>now(),'facts'=>array_replace($existing?->facts ?? [], $facts),
                        ];
                        $protected = [];
                        if ($existing) {
                            $protected = EquipmentFieldProvenance::query()->where('equipment_item_id',$existing->id)
                                ->get()->filter(fn ($row) => $this->policy->isProtected($row,$adapter->key()))
                                ->pluck('field_key')->all();
                            foreach ($protected as $field) {
                                $column = $field === 'family' ? 'family_id' : $field;
                                if (array_key_exists($column,$attributes)) $attributes[$column] = $existing->getAttribute($column);
                            }
                            if ($existing->local_asset_path) {
                                $attributes['original_asset_url'] = $existing->original_asset_url;
                            }
                        }
                        $item = EquipmentItem::updateOrCreate(['source_id'=>$source->id,'external_id'=>$id], $attributes);
                        foreach (['en','de'] as $locale) {
                            $translation = $item->translations()->firstOrNew(['locale'=>$locale]);
                            if (! $translation->description_is_manual && ! in_array('description.'.$locale,$protected,true)) {
                                $translation->description = $this->descriptions->generate($raw,$locale);
                            }
                            if (! in_array('name',$protected,true) && ! in_array('name.'.$locale,$protected,true)) {
                                $translation->name = $facts['name'];
                            }
                            $translation->save();
                        }
                        $this->syncRelations($item,$raw,$adapter->key());
                        $snapshot = $this->snapshots->recordHuntify($item,$source,$facts);
                        $this->recordProvenance($item,$snapshot,$facts,$protected);
                        if ($kind !== 'unchanged') {
                            $before = $existing?->facts ?? [];
                            foreach ($facts as $field => $value) {
                                if (($before[$field] ?? null) !== $value) $this->change($run,$item,$id,$kind,$field,$before[$field] ?? null,$value);
                            }
                        }
                    });
                } catch (Throwable $e) {
                    $counts['errors']++;
                    if ($kind !== null) $counts[$kind] = max(0,$counts[$kind] - 1);
                    Log::warning('Equipment sync item failed', ['source'=>$adapter->key(),'external_id'=>$id,'error'=>$e->getMessage()]);
                    if ($run) $this->change($run,null,$id,'error','import',null,$e->getMessage());
                }
            }
            $feedComplete = true;
        } catch (Throwable $e) {
            $counts['errors']++;
            Log::error('Equipment source feed failed', ['source'=>$adapter->key(),'error'=>$e->getMessage()]);
        }
        if ($feedComplete && $counts['errors'] === 0 && $item === null && $source) {
            EquipmentItem::where('source_id',$source->id)->where('source_status','active')->get()->each(function ($record) use (&$counts,$seen,$dryRun,$run) {
                if (isset($seen[$record->external_id])) return;
                $counts['missing']++;
                if ($dryRun) return;
                DB::transaction(function () use ($record,$run) {
                    $record->update(['source_status'=>'missing']);
                    $this->change($run,$record,$record->external_id,'missing','source_status','active','missing');
                });
            });
        }
        if ($run) $run->update(['status'=>! $feedComplete || $counts['errors'] ? 'partial' : 'completed','counts'=>$counts,'finished_at'=>now()]);
        return $counts;
    }

    private function facts(array $raw): array
    {
        $type = (string)($raw['_item_type'] ?? 'weapon');
        $class = (string)($raw['weaponType'] ?? $raw['category'] ?? ucfirst($type));
        $group = $this->comparisonGroup($raw, $type, $class);
        return [
            'name'=>(string)($raw['name'] ?? ''),'item_type'=>$type,'category'=>$raw['category'] ?? null,
            'equipment_class'=>$class,'comparison_group'=>$group,'family'=>$raw['family'] ?? null,
            'ammo_type'=>$raw['caliber'] ?? null,'slot_size'=>$raw['slots'] ?? null,'price'=>$raw['cost'] ?? null,
            'unlock_rank'=>is_numeric($raw['minRank'] ?? null) ? (int)$raw['minRank'] : null,
            'stats'=>$this->catalog->values($raw),'ammo'=>$this->ammoFacts($raw['ammo'] ?? []),
            'traits'=>array_values(array_filter(array_map(fn($t)=>$t['id'] ?? null,$raw['recommendedTraits'] ?? []))),
            'skins'=>array_map(fn($skin)=>['id'=>$skin['id'],'name'=>$skin['name'] ?? null,
                'rarity'=>$skin['rarity'] ?? null,'asset_url'=>$this->assetUrl($skin['icon'] ?? null)],$raw['_skins'] ?? []),
            'skin_ids'=>array_values($raw['skinIds'] ?? []),'icon_source_url'=>$this->assetUrl($raw['hiresIcon'] ?? $raw['icon'] ?? null),
            'chamber'=>$raw['chamber'] ?? null,'cylinder'=>$raw['cylinder'] ?? null,
            'min_rank'=>$raw['minRank'] ?? null,'rarity'=>$raw['rarity'] ?? null,'release_pack'=>$raw['releasePack'] ?? null,
            'source_url'=>(string)($raw['_source_url'] ?? ''),
        ];
    }

    public function comparisonGroup(array $raw, string $type, string $class): string
    {
        $category = (string) ($raw['category'] ?? '');
        if ($type === 'weapon') {
            $weaponClass = $class === 'Undetermined' ? $category : $class;
            return 'weapon:'.(Str::slug($weaponClass) ?: 'unknown');
        }
        if ($type === 'tool') {
            $toolClass = in_array($category, ['Melee', 'Melee / Throwable'], true) ? 'Melee' : $category;
            return 'tool:'.(Str::slug($toolClass) ?: 'unknown');
        }
        if ($type === 'consumable') {
            $labels = $raw['labels'] ?? [];
            $gameId = strtolower((string) ($raw['gameId'] ?? ''));
            if ((is_array($labels) && in_array('Boon', $labels, true)) || str_contains($gameId, 'consumableboost')) {
                return 'consumable:shot';
            }
            return 'consumable:'.(Str::slug($category) ?: 'unknown');
        }
        return Str::slug($type).':'.(Str::slug($class) ?: 'unknown');
    }

    private function ammoFacts(array $ammo): array
    {
        return array_map(fn($a)=>[
            'name'=>$a['variant'] ?? 'Basic','ammo_type'=>$a['caliber'] ?? null,'damage'=>$a['damage'] ?? null,
            'velocity'=>$a['speed'] ?? null,'loaded'=>$a['loaded'] ?? null,'reserve'=>$a['reserve'] ?? null,
            'envelope'=>$a['envelope'] ?? [],'max_distance'=>$a['maxDistance'] ?? null,
            'stats'=>$a['stats'] ?? [],'damage_types'=>$a['damageTypes'] ?? [],'hits'=>$a['hits'] ?? [],
        ],$ammo);
    }

    private function assetUrl(?string $path): ?string
    {
        return $path && str_starts_with($path,'Hunt/assets/') ? 'https://wiki.huntify.win/'.$path : null;
    }

    private function uniqueSlug(string $name, string $externalId): string
    {
        $base = Str::slug($name) ?: 'item';
        if (! EquipmentItem::where('slug',$base)->exists()) return $base;
        return $base.'-'.substr(hash('sha256',$externalId),0,8);
    }

    private function syncRelations(EquipmentItem $item, array $raw, string $sourceKey): void
    {
        $values = $this->catalog->values($raw);
        $ids = EquipmentStatDefinition::whereIn('key',array_keys($values))->pluck('id','key');
        $protectedFields = EquipmentFieldProvenance::query()->where('equipment_item_id',$item->id)
            ->get()->filter(fn ($row) => $this->policy->isProtected($row,$sourceKey))->pluck('field_key')->all();
        $protected = array_map(fn ($key)=>substr($key,5),array_values(array_filter(
            $protectedFields,fn ($key)=>str_starts_with($key,'stat.')
        )));
        $protectedColumns = array_values(array_intersect($protectedFields,['price','slot_size']));
        if (in_array('price',$protectedColumns,true)) $protected[] = 'price';
        if (in_array('slot_size',$protectedColumns,true)) $protected[] = 'slotSize';
        foreach ($values as $key=>$value) if (isset($ids[$key]) && ! in_array($key,$protected,true)) {
            $item->stats()->updateOrCreate(['stat_definition_id'=>$ids[$key]],['value'=>$value]);
        }
        // Source omissions need a reviewed retirement policy; they never delete canonical stats here.
        foreach ($this->ammoFacts($raw['ammo'] ?? []) as $index=>$data) {
            $key = Str::slug(($data['name'] ?? 'basic').'-'.($data['ammo_type'] ?? 'unknown').'-'.$index);
            $record = $item->ammo()->firstOrNew(['key'=>$key]);
            $ammoAttributes = [
                'name'=>$data['name'],'ammo_type'=>$data['ammo_type'],'damage'=>$data['damage'],
                'velocity'=>$data['velocity'],'loaded'=>$data['loaded'],'reserve'=>$data['reserve'],
                'facts'=>array_replace($record->facts ?? [],[
                    'max_distance'=>$data['max_distance'],'stats'=>$data['stats'],
                    'damage_types'=>$data['damage_types'],'hits'=>$data['hits']]),
            ];
            foreach ($ammoAttributes as $field=>$value) {
                if ($record->exists && in_array('ammo.'.$key.'.'.$field,$protectedFields,true)) continue;
                $record->setAttribute($field,$value);
            }
            $record->save();
            foreach ($data['envelope'] as $point) if (count($point) >= 2 && is_numeric($point[0]) && is_numeric($point[1])) {
                if (in_array('ammo.'.$key.'.damage',$protectedFields,true) ||
                    in_array('ammo.'.$key.'.falloff.'.(string)$point[0],$protectedFields,true)) continue;
                $record->falloffPoints()->updateOrCreate(
                    ['distance'=>$point[0]],
                    ['damage'=>($data['damage'] ?? 0)*$point[1]]
                );
            }
        }
        $traitIds = [];
        foreach ($raw['recommendedTraits'] ?? [] as $trait) if (! empty($trait['id'])) {
            $traitIds[] = EquipmentTrait::firstOrCreate(['external_id'=>$trait['id']],['name'=>$trait['name'] ?? $trait['id']])->id;
        }
        $item->traits()->syncWithoutDetaching($traitIds);
        $skins = collect($raw['_skins'] ?? [])->keyBy('id');
        foreach ($raw['skinIds'] ?? [] as $skinId) {
            $skin = $skins->get($skinId,[]);
            $record = $item->skins()->firstOrNew(['external_id'=>$skinId]);
            if (! $record->local_asset_path && ! data_get($record->facts, 'wiki_gg')) {
                $record->name = $skin['name'] ?? $record->name;
                $record->rarity = $skin['rarity'] ?? $record->rarity;
                $record->original_asset_url = $this->assetUrl($skin['icon'] ?? null);
            }
            $record->source_url = $record->source_url ?: 'https://wiki.huntify.win/Hunt/modules/Skins/data.js';
            $record->save();
        }
    }

    private function recordProvenance(EquipmentItem $item, EquipmentSourceSnapshot $snapshot, array $facts, array $protected): void
    {
        EquipmentFieldProvenance::query()->firstOrCreate(
            ['equipment_item_id'=>$item->id,'field_key'=>'__baseline'],
            ['source_key'=>$snapshot->source_key,'source_snapshot_id'=>$snapshot->id,
                'is_manual_override'=>false,'verified_at'=>now()]
        );
        if (in_array('price',$protected,true)) $protected[] = 'stat.price';
        if (in_array('slot_size',$protected,true)) $protected[] = 'stat.slotSize';
        $fields = [];
        foreach (['name','item_type','category','equipment_class','comparison_group','family',
            'ammo_type','slot_size','price','unlock_rank'] as $key) {
            if (($facts[$key] ?? null) !== null) $fields[] = $key;
        }
        foreach ($facts['stats'] ?? [] as $key=>$value) {
            if (is_numeric($value)) $fields[] = 'stat.'.$key;
        }
        foreach ($fields as $field) {
            if (in_array($field,$protected,true)) continue;
            $existing = EquipmentFieldProvenance::query()->where('equipment_item_id',$item->id)
                ->where('field_key',$field)->first();
            if ($existing && $this->policy->isProtected($existing,$snapshot->source_key)) continue;
            EquipmentFieldProvenance::query()->updateOrCreate(
                ['equipment_item_id'=>$item->id,'field_key'=>$field],
                [
                    'source_key'=>$snapshot->source_key,
                    'source_snapshot_id'=>$snapshot->id,
                    'source_revision_id'=>null,
                    'is_manual_override'=>false,
                ]
            );
        }
    }

    private function change(EquipmentSyncRun $run, ?EquipmentItem $item, string $id, string $type, string $field, mixed $old, mixed $new): void
    {
        EquipmentSyncChange::create(['sync_run_id'=>$run->id,'equipment_item_id'=>$item?->id,'external_id'=>$id,
            'change_type'=>$type,'field'=>$field,'old_value'=>$old,'new_value'=>$new]);
    }
}
