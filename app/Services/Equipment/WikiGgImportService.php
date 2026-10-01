<?php

namespace App\Services\Equipment;

use App\Models\EquipmentFamily;
use App\Models\EquipmentItem;
use App\Models\EquipmentPatchHistory;
use App\Models\EquipmentStatDefinition;
use App\Models\EquipmentTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WikiGgImportService
{
    public function __construct(
        private EquipmentStatCatalog $catalog,
        private WikiGgMediaImportService $media,
    ) {}

    public function plan(EquipmentItem $item, array $wiki): array
    {
        $currentStats = $item->stats
            ->filter(fn ($stat) => $stat->definition?->key)
            ->mapWithKeys(fn ($stat) => [$stat->definition->key => (float) $stat->value])
            ->all();

        $wikiStats = $this->mappedStats($wiki);
        $statChanges = [];

        foreach ($wikiStats as $key => $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $current = $currentStats[$key] ?? null;
            if ($current === null || abs((float) $current - (float) $value) > 0.0005) {
                $statChanges[$key] = ['from' => $current, 'to' => $value];
            }
        }

        $currentTraits = $item->traits->pluck('name')->filter()->map(fn ($v) => trim((string) $v))->sort()->values()->all();
        $wikiTraits = collect($wiki['recommended_traits'] ?? [])->filter()->map(fn ($v) => trim((string) $v))->unique()->sort()->values()->all();

        $currentFamily = trim((string) ($item->family?->name ?? ''));
        $wikiFamily = trim((string) ($wiki['family'] ?? ''));

        $fieldChanges = [];
        $this->fieldChange($fieldChanges, 'family', $currentFamily ?: null, $wikiFamily ?: null);
        $this->fieldChange($fieldChanges, 'price', $item->price, $wiki['price'] ?? null);
        $this->fieldChange($fieldChanges, 'slot_size', $item->slot_size, $wiki['slot_size'] ?? null);
        $this->fieldChange($fieldChanges, 'ammo_type', $item->ammo_type, $wiki['ammo_type'] ?? null);

        $currentBaseAmmo = $item->ammo->first();
        $loaded = $this->loadedNumber($wiki['loaded'] ?? null);
        $reserve = $this->integerOrNull($wiki['reserve'] ?? null);
        if ($currentBaseAmmo) {
            $this->fieldChange($fieldChanges, 'loaded', $currentBaseAmmo->loaded, $loaded);
            $this->fieldChange($fieldChanges, 'reserve', $currentBaseAmmo->reserve, $reserve);
        }

        return [
            'slug' => $item->slug,
            'name' => $item->name,
            'page_title' => $wiki['page_title'] ?? null,
            'page_url' => $wiki['page_url'] ?? null,
            'resolution_method' => $wiki['resolution_method'] ?? 'direct',
            'resolution_score' => $wiki['resolution_score'] ?? 100,
            'field_changes' => $fieldChanges,
            'stat_changes' => $statChanges,
            'traits_changed' => $wikiTraits !== [] && $wikiTraits !== $currentTraits,
            'traits_from' => $currentTraits,
            'traits_to' => $wikiTraits,
            'patch_rows' => count($wiki['patch_history'] ?? []),
            'skins' => count($wiki['skins'] ?? []),
            'skin_image_candidates' => count(array_filter($wiki['skins'] ?? [], fn (array $skin) => ! empty($skin['image_file']))),
            'base_image_candidate' => ! empty($wiki['base_image_file']),
        ];
    }

    public function apply(EquipmentItem $item, array $wiki, bool $withMedia = true): array
    {
        $this->catalog->ensure();

        $result = DB::transaction(function () use ($item, $wiki): array {
            $item->loadMissing(['family', 'stats.definition', 'ammo', 'traits']);

            $updates = [];
            if (! empty($wiki['family']) && $item->item_type === 'weapon') {
                $familyName = trim((string) $wiki['family']);
                $family = EquipmentFamily::updateOrCreate(
                    ['key' => Str::slug($familyName)],
                    ['name' => $familyName],
                );
                $updates['family_id'] = $family->id;
            }

            foreach (['price' => 'price', 'slot_size' => 'slot_size', 'ammo_type' => 'ammo_type'] as $wikiKey => $column) {
                if (($wiki[$wikiKey] ?? null) !== null && $wiki[$wikiKey] !== '') {
                    $updates[$column] = $wiki[$wikiKey];
                }
            }

            $facts = is_array($item->facts) ? $item->facts : [];
            $facts['wiki_gg'] = array_merge($facts['wiki_gg'] ?? [], [
                'page_title' => $wiki['page_title'] ?? null,
                'page_url' => $wiki['page_url'] ?? null,
                'revision_id' => $wiki['revision_id'] ?? null,
                'revision_timestamp' => $wiki['revision_timestamp'] ?? null,
                'family' => $wiki['family'] ?? null,
                'canonical_name' => $wiki['name'] ?? null,
                'update' => $wiki['update'] ?? null,
                'unlock' => $wiki['unlock'] ?? null,
                'loaded_raw' => $wiki['loaded'] ?? null,
                'reserve_raw' => $wiki['reserve'] ?? null,
                'rarity' => $wiki['rarity'] ?? null,
                'quantity' => $wiki['quantity'] ?? null,
                'ammo_types' => array_values($wiki['ammo_types'] ?? []),
                'resolution_method' => $wiki['resolution_method'] ?? 'direct',
                'resolution_score' => $wiki['resolution_score'] ?? 100,
            ]);
            $updates['facts'] = $facts;
            $updates['last_synced_at'] = now();

            $item->forceFill($updates)->save();

            $definitions = EquipmentStatDefinition::query()
                ->whereIn('key', array_keys($this->mappedStats($wiki)))
                ->pluck('id', 'key');

            $statWrites = 0;
            foreach ($this->mappedStats($wiki) as $key => $value) {
                if (! is_numeric($value) || ! isset($definitions[$key])) {
                    continue;
                }
                $item->stats()->updateOrCreate(
                    ['stat_definition_id' => $definitions[$key]],
                    ['value' => $value],
                );
                $statWrites++;
            }

            $ammo = $item->ammo()->orderBy('id')->first();
            if ($ammo) {
                $ammoUpdates = [];
                $loaded = $this->loadedNumber($wiki['loaded'] ?? null);
                $reserve = $this->integerOrNull($wiki['reserve'] ?? null);
                if ($loaded !== null) $ammoUpdates['loaded'] = $loaded;
                if ($reserve !== null) $ammoUpdates['reserve'] = $reserve;
                if (is_numeric($wiki['stats']['damage'] ?? null)) $ammoUpdates['damage'] = $wiki['stats']['damage'];
                if (is_numeric($wiki['stats']['muzzleVelocity'] ?? null)) $ammoUpdates['velocity'] = $wiki['stats']['muzzleVelocity'];
                if ($ammoUpdates) $ammo->update($ammoUpdates);
            }

            $traitNames = collect($wiki['recommended_traits'] ?? [])
                ->filter()
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique()
                ->values();

            if ($traitNames->isNotEmpty()) {
                $traitIds = [];
                foreach ($traitNames as $name) {
                    $trait = EquipmentTrait::query()->where('name', $name)->first();
                    if (! $trait) {
                        $trait = EquipmentTrait::create([
                            'external_id' => 'wikigg-'.Str::slug($name),
                            'name' => $name,
                        ]);
                    }
                    $traitIds[] = $trait->id;
                }
                $item->traits()->sync($traitIds);
            }

            if (! empty($wiki['patch_history'])) {
                EquipmentPatchHistory::query()
                    ->where('equipment_item_id', $item->id)
                    ->where('source_url', $wiki['page_url'])
                    ->delete();

                foreach ($wiki['patch_history'] as $row) {
                    $patch = trim((string) ($row['patch'] ?? ''));
                    $note = trim((string) ($row['note'] ?? ''));
                    if ($patch === '' && $note === '') continue;

                    EquipmentPatchHistory::create([
                        'equipment_item_id' => $item->id,
                        'patch' => $patch !== '' ? $patch : 'wiki.gg',
                        'source_url' => $wiki['page_url'],
                        'note' => $note !== '' ? $note : null,
                    ]);
                }
            }

            return [
                'stats_written' => $statWrites,
                'traits_written' => $traitNames->count(),
                'patch_rows_written' => count($wiki['patch_history'] ?? []),
            ];
        });

        if ($withMedia) {
            $result['media'] = $this->media->apply($item->fresh(['family', 'skins']), $wiki);
        }

        return $result;
    }

    private function mappedStats(array $wiki): array
    {
        $stats = $wiki['stats'] ?? [];

        return array_filter([
            'damage' => $stats['damage'] ?? null,
            'effectiveRange' => $stats['dropRange'] ?? null,
            'rateOfFire' => $stats['rateOfFire'] ?? null,
            'cycleTime' => $stats['cycleTime'] ?? null,
            'spread' => $stats['spread'] ?? null,
            'sway' => $stats['sway'] ?? null,
            'recoil' => $stats['recoil'] ?? null,
            'reload' => $stats['reload'] ?? null,
            'muzzleVelocity' => $stats['muzzleVelocity'] ?? null,
            'swapSpeed' => $stats['swapSpeed'] ?? null,
            'melee' => $stats['melee'] ?? null,
            'heavyMelee' => $stats['heavyMelee'] ?? null,
            'stamina' => $stats['stamina'] ?? null,
            'heavyStamina' => $stats['heavyStamina'] ?? null,
            'throwStamina' => $stats['throwStamina'] ?? null,
            'throwRange' => $stats['throwRange'] ?? null,
            'fuseTimer' => $stats['fuseTimer'] ?? null,
            'radius' => $stats['radius'] ?? null,
            'effectDuration' => $stats['effectDuration'] ?? null,
            'price' => $wiki['price'] ?? null,
            'slotSize' => $wiki['slot_size'] ?? null,
            'magazine' => $this->loadedNumber($wiki['loaded'] ?? null),
            'reserve' => $this->integerOrNull($wiki['reserve'] ?? null),
        ], fn ($value) => is_numeric($value));
    }

    private function fieldChange(array &$changes, string $field, mixed $from, mixed $to): void
    {
        if ($to === null || $to === '') return;

        $same = is_numeric($from) && is_numeric($to)
            ? abs((float) $from - (float) $to) < 0.0005
            : trim((string) $from) === trim((string) $to);

        if (! $same) {
            $changes[$field] = ['from' => $from, 'to' => $to];
        }
    }

    private function loadedNumber(mixed $value): ?int
    {
        if ($value === null) return null;
        if (is_numeric($value)) return (int) $value;

        $text = trim((string) $value);
        if (preg_match('/^(\d+)/', $text, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    private function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (int) $value;
        if (preg_match('/-?\d+/', (string) $value, $match)) return (int) $match[0];

        return null;
    }
}
