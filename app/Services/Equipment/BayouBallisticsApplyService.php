<?php

namespace App\Services\Equipment;

use App\Models\EquipmentAmmo;
use App\Models\EquipmentFieldProvenance;
use App\Models\EquipmentItem;
use App\Models\EquipmentSourceSnapshot;
use App\Models\EquipmentStat;
use App\Models\EquipmentStatDefinition;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BayouBallisticsApplyService
{
    public const AUDIT_SLUGS = [
        '1865-carbine', 'drilling', 'berthier-1892-deadeye',
        'mosin-nagant-sniper', 'sparks',
    ];

    private const AMMO_FIELDS = [
        'baseDamage', 'upperTorsoMultiplier', 'torsoMultiplier',
        'armMultiplier', 'legMultiplier',
    ];

    public function __construct(
        private readonly BayouBallisticsPlanner $planner,
        private readonly BayouWeaponMatcher $matcher,
        private readonly EquipmentSourceSnapshotService $snapshots,
    ) {}

    public function apply(EquipmentItem $item, array $source): array
    {
        $slug = (string) ($source['source_page_slug'] ?? '');
        if (! in_array($slug, self::AUDIT_SLUGS, true) || $item->slug !== $slug
            || ($source['source_key'] ?? null) !== 'bayou_index'
            || ($source['source_url'] ?? null) !== 'https://bayouindex.com/weapons/'.$slug.'/'
            || ! preg_match('/\A[a-f0-9]{64}\z/', (string) ($source['payload_hash'] ?? ''))) {
            throw new InvalidArgumentException('Bayou apply requires an audited weapon and its exact source page.');
        }
        $payload = [
            'name' => $source['name'] ?? null,
            'ammo_type' => $source['ammo_type'] ?? null,
            'fields' => $source['fields'] ?? null,
            'checks' => $source['checks'] ?? null,
        ];
        if (! hash_equals($source['payload_hash'], hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)))) {
            throw new InvalidArgumentException('Bayou numeric source payload changed after it was observed.');
        }

        return DB::transaction(function () use ($item, $source): array {
            $locked = EquipmentItem::query()->whereKey($item->getKey())
                ->where('slug', $item->slug)->where('item_type', 'weapon')
                ->where('source_status', 'active')->lockForUpdate()->firstOrFail();
            $match = $this->matcher->match($source, EquipmentItem::query()
                ->where('item_type', 'weapon')->where('source_status', 'active')
                ->with('family')->get());
            if ($match['item']?->getKey() !== $locked->getKey() || $match['confidence'] < 95) {
                throw new InvalidArgumentException('Bayou weapon identity is ambiguous or no longer matches.');
            }

            $locked->load(['stats.definition', 'ammo']);
            $plan = $this->planner->plan($locked, $source, $match['confidence']);
            $snapshot = null;
            $rows = [];
            $written = 0;

            foreach ($plan['rows'] as $row) {
                if ($row['action'] !== 'CREATE') {
                    $rows[] = $row + ['result' => $row['action']];
                    continue;
                }

                $field = $row['field'];
                $key = $field === 'stat.zoom' ? 'zoom' : null;
                $ammoKey = null;
                if (preg_match('/\Aammo\.([a-zA-Z0-9_-]+)\.stat\.([a-zA-Z]+)\z/', $field, $parts)
                    && in_array($parts[2], self::AMMO_FIELDS, true)) {
                    $ammoKey = $parts[1];
                    $key = $parts[2];
                }
                if ($key === null || ($row['check_only'] ?? true)
                    || ($source['fields'][$key] ?? null) === null
                    || ! is_numeric($row['bayou']) || ! is_finite((float) $row['bayou'])
                    || (float) $row['bayou'] <= 0
                    || (float) $source['fields'][$key] !== (float) $row['bayou']) {
                    $rows[] = array_merge($row, ['result' => 'BLOCKED_SOURCE', 'reason' => 'Field is not an approved numeric CREATE source.']);
                    continue;
                }

                $definition = EquipmentStatDefinition::query()->where('key', $key)->first();
                if ($definition === null) {
                    $rows[] = array_merge($row, ['result' => 'BLOCKED_DEFINITION', 'reason' => 'Canonical stat definition is absent.']);
                    continue;
                }

                $provenanceKeys = [$field];
                if ($ammoKey !== null) $provenanceKeys[] = 'stat.'.$key;
                $existingProvenance = EquipmentFieldProvenance::query()
                    ->where('equipment_item_id', $locked->id)->whereIn('field_key', $provenanceKeys)
                    ->lockForUpdate()->get();
                if ($existingProvenance->isNotEmpty()) {
                    $manual = $existingProvenance->contains(fn ($entry) => $entry->is_manual_override);
                    $rows[] = array_merge($row, [
                        'result' => $manual ? 'BLOCKED_MANUAL' : 'BLOCKED_PROVENANCE',
                        'reason' => $manual ? 'Manual override is protected.' : 'An existing source already owns this field.',
                    ]);
                    continue;
                }

                // A legacy item-level stat also owns the effective value of a base ammo mode.
                $existingStat = EquipmentStat::query()->where('equipment_item_id', $locked->id)
                    ->where('stat_definition_id', $definition->id)->lockForUpdate()->first();
                if ($existingStat !== null) {
                    $rows[] = array_merge($row, ['result' => 'BLOCKED_EXISTING', 'reason' => 'Canonical field already has a value.']);
                    continue;
                }

                $ammo = null;
                $facts = null;
                if ($ammoKey !== null) {
                    if ($ammoKey !== $plan['base_ammo_key']) {
                        $rows[] = array_merge($row, ['result' => 'BLOCKED_AMMO', 'reason' => 'Base ammunition mode is not unique.']);
                        continue;
                    }
                    $ammo = EquipmentAmmo::query()->where('equipment_item_id', $locked->id)
                        ->where('key', $ammoKey)->lockForUpdate()->first();
                    $baseModes = EquipmentAmmo::query()->where('equipment_item_id', $locked->id)
                        ->where('ammo_type', $locked->ammo_type)->get()
                        ->filter(fn ($mode) => in_array(strtolower(trim($mode->name)), ['basic', 'stock'], true));
                    if ($ammo === null || $baseModes->count() !== 1 || $baseModes->first()->id !== $ammo->id
                        || strcasecmp((string) $source['ammo_type'], (string) $ammo->ammo_type) !== 0) {
                        $rows[] = array_merge($row, ['result' => 'BLOCKED_AMMO', 'reason' => 'Base ammunition mode changed or is ambiguous.']);
                        continue;
                    }
                    $facts = $ammo->facts ?? [];
                    if (! is_array($facts) || (isset($facts['stats']) && ! is_array($facts['stats']))
                        || (isset($facts['stats']) && array_key_exists($key, $facts['stats'])
                            && $facts['stats'][$key] !== null)) {
                        $rows[] = array_merge($row, ['result' => 'BLOCKED_EXISTING', 'reason' => 'Canonical ammo field already has a value.']);
                        continue;
                    }
                }

                $snapshot ??= $this->snapshots->recordBayouBallistics($locked, $source);
                if ($ammo !== null) {
                    $facts['stats'][$key] = (float) $row['bayou'];
                    $ammo->facts = $facts;
                    $ammo->save();
                } else {
                    EquipmentStat::query()->create([
                        'equipment_item_id' => $locked->id,
                        'stat_definition_id' => $definition->id,
                        'value' => $row['bayou'],
                    ]);
                }
                $this->recordProvenance($locked, $snapshot, $row, $source);
                $written++;
                $rows[] = $row + ['result' => 'WRITTEN'];
            }

            return ['item' => $locked->slug, 'rows' => $rows, 'written' => $written,
                'snapshot_id' => $snapshot?->id];
        });
    }

    private function recordProvenance(
        EquipmentItem $item, EquipmentSourceSnapshot $snapshot, array $row, array $source,
    ): void {
        EquipmentFieldProvenance::query()->create([
            'equipment_item_id' => $item->id,
            'field_key' => $row['field'],
            'source_key' => 'bayou_index',
            'source_snapshot_id' => $snapshot->id,
            'source_revision_id' => null,
            'is_manual_override' => false,
            'verified_at' => null,
            'metadata' => [
                'source_url' => $source['source_url'],
                'observed_at' => $source['observed_at'],
                'source_value' => (float) $row['bayou'],
                'confidence' => $row['confidence'],
                'payload_hash' => $source['payload_hash'],
            ],
        ]);
    }
}
