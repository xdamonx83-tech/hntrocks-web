<?php
namespace App\Services\Equipment;

use App\Models\{EquipmentFamily,EquipmentItem,EquipmentSource,EquipmentStatDefinition,EquipmentSyncChange,EquipmentSyncRun,EquipmentTrait};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EquipmentSyncService
{
    public function __construct(private EquipmentStatCatalog $catalog, private EquipmentDescriptionGenerator $descriptions) {}

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
                    $kind = ! $existing ? 'new' : ($existing->source_hash !== $hash || $existing->source_status !== 'active' ? 'changed' : 'unchanged');
                    $counts[$kind]++;
                    if ($dryRun) continue;
                    DB::transaction(function () use ($raw,$facts,$hash,$existing,$source,$run,$id,$kind) {
                        $familyId = null;
                        if (! empty($facts['family'])) {
                            $family = EquipmentFamily::firstOrCreate(['key'=>$facts['family']], ['name'=>Str::headline($facts['family'])]);
                            $familyId = $family->id;
                        }
                        $slug = $existing?->slug ?? $this->uniqueSlug((string)$raw['name'], $id);
                        $item = EquipmentItem::updateOrCreate(['source_id'=>$source->id,'external_id'=>$id], [
                            'slug'=>$slug,'name'=>$facts['name'],'item_type'=>$facts['item_type'],
                            'category'=>$facts['category'],'equipment_class'=>$facts['equipment_class'],
                            'comparison_group'=>$facts['comparison_group'],'family_id'=>$familyId,
                            'ammo_type'=>$facts['ammo_type'],'slot_size'=>$facts['slot_size'],'price'=>$facts['price'],
                            'unlock_rank'=>$facts['unlock_rank'],
                            'original_asset_url'=>$facts['icon_source_url'],
                            'source_status'=>'active','source_url'=>$facts['source_url'],'source_hash'=>$hash,
                            'last_synced_at'=>now(),'facts'=>$facts,
                        ]);
                        foreach (['en','de'] as $locale) {
                            $translation = $item->translations()->firstOrNew(['locale'=>$locale]);
                            if (! $translation->description_is_manual) $translation->description = $this->descriptions->generate($raw,$locale);
                            $translation->name = $facts['name']; $translation->save();
                        }
                        $this->syncRelations($item,$raw);
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

    private function syncRelations(EquipmentItem $item, array $raw): void
    {
        $values = $this->catalog->values($raw);
        $ids = EquipmentStatDefinition::whereIn('key',array_keys($values))->pluck('id','key');
        foreach ($values as $key=>$value) if (isset($ids[$key])) $item->stats()->updateOrCreate(['stat_definition_id'=>$ids[$key]],['value'=>$value]);
        $item->stats()->whereNotIn('stat_definition_id',$ids->values())->delete();
        $ammoKeys = [];
        foreach ($this->ammoFacts($raw['ammo'] ?? []) as $index=>$data) {
            $key = Str::slug(($data['name'] ?? 'basic').'-'.($data['ammo_type'] ?? 'unknown').'-'.$index);
            $ammoKeys[] = $key;
            $record = $item->ammo()->updateOrCreate(['key'=>$key],[
                'name'=>$data['name'],'ammo_type'=>$data['ammo_type'],'damage'=>$data['damage'],
                'velocity'=>$data['velocity'],'loaded'=>$data['loaded'],'reserve'=>$data['reserve'],
                'facts'=>['max_distance'=>$data['max_distance'],'stats'=>$data['stats'],
                    'damage_types'=>$data['damage_types'],'hits'=>$data['hits']],
            ]);
            $record->falloffPoints()->delete();
            foreach ($data['envelope'] as $point) if (count($point) >= 2 && is_numeric($point[0]) && is_numeric($point[1])) {
                $record->falloffPoints()->create(['distance'=>$point[0],'damage'=>($data['damage'] ?? 0)*$point[1]]);
            }
        }
        $item->ammo()->whereNotIn('key',$ammoKeys)->delete();
        $traitIds = [];
        foreach ($raw['recommendedTraits'] ?? [] as $trait) if (! empty($trait['id'])) {
            $traitIds[] = EquipmentTrait::updateOrCreate(['external_id'=>$trait['id']],['name'=>$trait['name'] ?? $trait['id']])->id;
        }
        $item->traits()->sync($traitIds);
        $skinIds = [];
        $skins = collect($raw['_skins'] ?? [])->keyBy('id');
        foreach ($raw['skinIds'] ?? [] as $skinId) {
            $skinIds[] = $skinId;
            $skin = $skins->get($skinId,[]);
            $item->skins()->updateOrCreate(['external_id'=>$skinId],[
                'name'=>$skin['name'] ?? null,'rarity'=>$skin['rarity'] ?? null,
                'source_url'=>'https://wiki.huntify.win/Hunt/modules/Skins/data.js',
                'original_asset_url'=>$this->assetUrl($skin['icon'] ?? null),
            ]);
        }
        $item->skins()->whereNotIn('external_id',$skinIds)->delete();
    }

    private function change(EquipmentSyncRun $run, ?EquipmentItem $item, string $id, string $type, string $field, mixed $old, mixed $new): void
    {
        EquipmentSyncChange::create(['sync_run_id'=>$run->id,'equipment_item_id'=>$item?->id,'external_id'=>$id,
            'change_type'=>$type,'field'=>$field,'old_value'=>$old,'new_value'=>$new]);
    }
}
