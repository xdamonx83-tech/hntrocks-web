<?php

namespace App\Services\Equipment;

use App\Models\EquipmentFieldProvenance;
use App\Models\EquipmentItem;
use App\Models\EquipmentStatDefinition;
use Illuminate\Support\Facades\DB;

class WikiGgImportService
{
    public function __construct(
        private EquipmentMergePolicy $policy,
        private EquipmentSourceSnapshotService $snapshots,
    ) {}

    public function plan(EquipmentItem $item, array $wiki): array
    {
        $item->loadMissing(['family', 'stats.definition', 'ammo', 'traits', 'provenance']);
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

        $confidence = (int) ($wiki['resolution_score'] ?? 100);
        $revision = isset($wiki['revision_id']) ? (string) $wiki['revision_id'] : null;
        $provenance = $item->provenance->keyBy('field_key');
        $definitions = EquipmentStatDefinition::query()->pluck('id', 'key');
        $rows = [];
        foreach ([
            'family' => [$currentFamily ?: null, $wikiFamily ?: null],
            'price' => [$item->price, $wiki['price'] ?? null],
            'slot_size' => [$item->slot_size, $wiki['slot_size'] ?? null],
            'ammo_type' => [$item->ammo_type, $wiki['ammo_type'] ?? null],
        ] as $field => [$current, $source]) {
            $rows[] = $this->planRow($item, $field, $current, $source, $confidence, $revision, $provenance->get($field));
        }
        foreach ($wikiStats as $key => $value) {
            $field = 'stat.'.$key;
            $row = $this->planRow($item, $field, $currentStats[$key] ?? null, $value, $confidence, $revision, $provenance->get($field));
            if (! isset($definitions[$key])) {
                $row['action'] = 'SKIP';
                $row['blocked_reason'] = 'Unknown or not migrated stat definition';
            }
            $rows[] = $row;
        }
        foreach ((array) ($wiki['stats'] ?? []) as $key => $value) {
            if (array_key_exists($key, $wikiStats)) continue;
            $rows[] = [
                'item' => $item->slug,
                'field' => 'source_stat.'.$key,
                'current_value' => null,
                'source_value' => $value,
                'source' => 'wiki_gg',
                'revision' => $revision,
                'action' => 'SKIP',
                'confidence' => $confidence,
                'blocked_reason' => 'No approved canonical stat mapping',
            ];
        }
        foreach ([
            'ammo.loaded' => [$currentBaseAmmo?->loaded, $loaded],
            'ammo.reserve' => [$currentBaseAmmo?->reserve, $reserve],
        ] as $field => [$current, $source]) {
            $row = $this->planRow($item, $field, $current, $source, $confidence, $revision, $provenance->get($field));
            if (in_array($row['action'], ['CREATE', 'UPDATE'], true)) {
                $row['action'] = 'REVIEW_REQUIRED';
                $row['blocked_reason'] = 'Ammo relation import needs explicit review';
            }
            $rows[] = $row;
        }
        foreach (['price' => 'stat.price', 'slot_size' => 'stat.slotSize'] as $column => $statField) {
            $columnIndex = array_search($column, array_column($rows, 'field'), true);
            $statIndex = array_search($statField, array_column($rows, 'field'), true);
            if ($columnIndex === false || $statIndex === false) continue;
            $columnAction = $rows[$columnIndex]['action'];
            $statAction = $rows[$statIndex]['action'];
            if (in_array($columnAction, ['REVIEW_REQUIRED', 'BLOCKED_MANUAL', 'BLOCKED_AMBIGUOUS'], true) &&
                in_array($statAction, ['CREATE', 'UPDATE'], true)) {
                $rows[$statIndex]['action'] = 'REVIEW_REQUIRED';
                $rows[$statIndex]['blocked_reason'] = 'Canonical item field is blocked';
            }
            if (in_array($statAction, ['REVIEW_REQUIRED', 'BLOCKED_MANUAL', 'BLOCKED_AMBIGUOUS'], true) &&
                in_array($columnAction, ['CREATE', 'UPDATE'], true)) {
                $rows[$columnIndex]['action'] = 'REVIEW_REQUIRED';
                $rows[$columnIndex]['blocked_reason'] = 'Canonical stat counterpart is blocked';
            }
        }
        if (($wiki['resolution_method'] ?? 'direct') !== 'direct') {
            foreach ($rows as &$row) {
                if (! in_array($row['action'], ['CREATE', 'UPDATE'], true)) continue;
                $row['action'] = 'REVIEW_REQUIRED';
                $row['blocked_reason'] = 'Search-resolved page needs an explicit mapping review';
            }
            unset($row);
        }

        return [
            'slug' => $item->slug,
            'name' => $item->name,
            'page_title' => $wiki['page_title'] ?? null,
            'page_url' => $wiki['page_url'] ?? null,
            'resolution_method' => $wiki['resolution_method'] ?? 'direct',
            'resolution_score' => $wiki['resolution_score'] ?? 100,
            'source' => 'wiki_gg',
            'revision' => $revision,
            'payload_hash' => $this->snapshots->hash($this->snapshots->normalizeWiki($wiki)),
            'rows' => $rows,
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

    public function apply(EquipmentItem $item, array $wiki, bool $withMedia = false): array
    {
        if ($withMedia) {
            throw new \InvalidArgumentException('Apply media separately after canonical import review.');
        }
        $result = DB::transaction(function () use ($item, $wiki): array {
            $item->refresh();
            $plan = $this->plan($item, $wiki);
            $snapshot = $this->snapshots->recordWiki($item, $wiki);
            $definitions = EquipmentStatDefinition::query()->pluck('id', 'key');
            $fieldsWritten = 0;
            $statsWritten = 0;

            foreach ($plan['rows'] as $row) {
                if (! in_array($row['action'], ['CREATE', 'UPDATE'], true)) continue;
                $field = $row['field'];
                if (str_starts_with($field, 'stat.')) {
                    $key = substr($field, 5);
                    if (! isset($definitions[$key])) continue;
                    $item->stats()->updateOrCreate(
                        ['stat_definition_id' => $definitions[$key]],
                        ['value' => $row['source_value']]
                    );
                    $statsWritten++;
                } elseif (in_array($field, ['price', 'slot_size', 'ammo_type'], true)) {
                    $item->setAttribute($field, $row['source_value']);
                    $fieldsWritten++;
                } else {
                    continue;
                }

                EquipmentFieldProvenance::query()->updateOrCreate(
                    ['equipment_item_id' => $item->id, 'field_key' => $field],
                    [
                        'source_key' => 'wiki_gg',
                        'source_snapshot_id' => $snapshot->id,
                        'source_revision_id' => $plan['revision'],
                        'is_manual_override' => false,
                        'verified_at' => null,
                    ]
                );
            }

            if ($fieldsWritten || $statsWritten) {
                $item->last_synced_at = now();
                $item->save();
            }

            return [
                'snapshot_id' => $snapshot->id,
                'fields_written' => $fieldsWritten,
                'stats_written' => $statsWritten,
                'review_required' => count(array_filter($plan['rows'], fn (array $row) => $row['action'] === 'REVIEW_REQUIRED')),
                'traits_written' => 0,
                'patch_rows_written' => 0,
            ];
        });

        return $result;
    }

    private function planRow(EquipmentItem $item, string $field, mixed $current, mixed $source, int $confidence, ?string $revision, ?EquipmentFieldProvenance $provenance): array
    {
        $decision = $this->policy->decide($item, $field, $current, $source, 'wiki_gg', $confidence, $provenance);
        return [
            'item' => $item->slug,
            'field' => $field,
            'current_value' => $current,
            'source_value' => $source,
            'source' => 'wiki_gg',
            'revision' => $revision,
            'action' => $decision['action'],
            'confidence' => $confidence,
            'blocked_reason' => $decision['reason'],
        ];
    }

    private function mappedStats(array $wiki): array
    {
        $stats = $wiki['stats'] ?? [];

        return array_filter([
            'damage' => $stats['damage'] ?? null,
            'dropRange' => $stats['dropRange'] ?? null,
            'rateOfFire' => $stats['rateOfFire'] ?? null,
            'cycleTime' => $stats['cycleTime'] ?? null,
            'spread' => $stats['spread'] ?? null,
            'sway' => $stats['sway'] ?? null,
            'recoil' => $stats['recoil'] ?? null, // wiki.gg Vertical Recoil; public API key stays stable.
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
