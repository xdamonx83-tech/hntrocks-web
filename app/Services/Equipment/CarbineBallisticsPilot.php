<?php

namespace App\Services\Equipment;

use App\Models\EquipmentAmmo;
use App\Models\EquipmentFieldProvenance;
use App\Models\EquipmentItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CarbineBallisticsPilot
{
    public function __construct(private readonly EquipmentSourceSnapshotService $snapshots) {}

    public function source(): array
    {
        $source = config('arsenal_1865_ballistics');
        if (! is_array($source) || ($source['slug'] ?? null) !== '1865-carbine'
            || count($source['modes'] ?? []) !== 3) {
            throw new InvalidArgumentException('The reviewed 1865 source snapshot is missing.');
        }
        return $source;
    }

    public function plan(EquipmentItem $item): array
    {
        $source = $this->source();
        if ($item->slug !== $source['slug'] || $item->item_type !== 'weapon'
            || $item->source_status !== 'active') {
            throw new InvalidArgumentException('Only the active 1865 Carbine may use this pilot.');
        }
        $item->loadMissing(['stats.definition', 'ammo.falloffPoints']);
        $stats = $item->stats->filter(fn ($stat) => $stat->definition !== null)
            ->mapWithKeys(fn ($stat) => [$stat->definition->key => (float) $stat->value]);
        $provenance = $item->provenance()->get()->keyBy('field_key');
        $rows = [];

        foreach ($source['modes'] as $key => $mode) {
            $ammo = $item->ammo->firstWhere('key', $key);
            $identityOk = $ammo !== null && strcasecmp((string) $ammo->ammo_type, 'Medium') === 0;
            $facts = $ammo?->facts ?? [];
            $ammoStats = is_array($facts['stats'] ?? null) ? $facts['stats'] : [];
            $baseMode = $key === 'basic-medium-0' && strtolower((string) $ammo?->name) === 'basic';
            foreach ($source['shared_stats'] as $stat => $value) {
                $field = "ammo.$key.stat.$stat";
                $current = $ammoStats[$stat] ?? ($baseMode ? $stats->get($stat) : null);
                $rows[] = $this->row($field, $current, $value, $identityOk, $provenance, false, 95);
            }
            $flightIdentityOk = $identityOk && $this->same($ammo?->velocity, $mode['velocity_mps']);
            $rows[] = $this->row("ammo.$key.bullet_drop", $facts['bullet_drop'] ?? null,
                $this->profile($source, $mode), $flightIdentityOk, $provenance, false, 98);
            foreach (['velocity' => 'velocity_mps', 'loaded' => 'loaded', 'reserve' => 'reserve',
                'damage' => 'card_damage'] as $field => $sourceField) {
                $rows[] = $this->row("ammo.$key.$field", $ammo?->$field, $mode[$sourceField],
                    $identityOk, $provenance, true, 95);
            }
            $currentRatios = $ammo?->falloffPoints->sortBy('distance')->values()->map(function ($point) use ($ammo) {
                $first = $ammo->falloffPoints->sortBy('distance')->first();
                return [(float) $point->distance, $first && $first->damage > 0
                    ? round((float) $point->damage / (float) $first->damage, 5) : null];
            })->all();
            $rows[] = $this->row("ammo.$key.falloff_ratios", $currentRatios, $mode['falloff'],
                $identityOk, $provenance, true, 95);
            if (array_key_exists('reserve', $ammoStats)) {
                $rows[] = $this->row("ammo.$key.stat.reserve", $ammoStats['reserve'], $mode['reserve'],
                    $identityOk, $provenance, true, 95);
            }
            if ($baseMode) {
                $rows[] = $this->row('stat.dropRange', $stats->get('dropRange'),
                    $mode['computed_drop_range_m'], $identityOk, $provenance, true, 98);
            }
        }

        return ['item' => $item->slug, 'source' => $source['source_url'], 'rows' => $rows,
            'source_hash' => $this->snapshots->hash($source)];
    }

    public function apply(EquipmentItem $item): array
    {
        return DB::transaction(function () use ($item): array {
            $locked = EquipmentItem::query()->whereKey($item->getKey())
                ->where('slug', '1865-carbine')->where('item_type', 'weapon')
                ->where('source_status', 'active')->lockForUpdate()->firstOrFail();
            $locked->load(['stats.definition', 'ammo.falloffPoints']);
            $plan = $this->plan($locked);
            $source = $this->source();
            $snapshot = null;
            $written = 0;
            $results = [];
            foreach ($plan['rows'] as $row) {
                if ($row['action'] !== 'CREATE') {
                    $results[] = $row + ['result' => $row['action']];
                    continue;
                }
                if (! preg_match('/\Aammo\.([a-z0-9_-]+)\.(?:stat\.([a-zA-Z]+)|(bullet_drop))\z/',
                    $row['field'], $parts)) {
                    $results[] = $row + ['result' => 'SKIP'];
                    continue;
                }
                $ammo = EquipmentAmmo::query()->where('equipment_item_id', $locked->id)
                    ->where('key', $parts[1])->lockForUpdate()->firstOrFail();
                $facts = $ammo->facts ?? [];
                $stat = $parts[2] ?? '';
                if ($stat !== '') {
                    if (isset($facts['stats'][$stat])) {
                        $results[] = $row + ['result' => 'REVIEW_REQUIRED'];
                        continue;
                    }
                    $facts['stats'][$stat] = $row['source_value'];
                } else {
                    if (isset($facts['bullet_drop'])) {
                        $results[] = $row + ['result' => 'REVIEW_REQUIRED'];
                        continue;
                    }
                    $facts['bullet_drop'] = $row['source_value'];
                }
                $snapshot ??= $this->snapshots->recordBayouCarbinePilot($locked, $source);
                $ammo->facts = $facts;
                $ammo->save();
                EquipmentFieldProvenance::query()->create([
                    'equipment_item_id' => $locked->id,
                    'field_key' => $row['field'],
                    'source_key' => 'bayou_index',
                    'source_snapshot_id' => $snapshot->id,
                    'is_manual_override' => false,
                    'verified_at' => null,
                    'metadata' => ['source_url' => $source['source_url'],
                        'data_url' => $source['data_url'], 'model_url' => $source['model_url'],
                        'observed_at' => $source['observed_at'], 'confidence' => $row['confidence'],
                        'source_hash' => $plan['source_hash']],
                ]);
                $written++;
                $results[] = $row + ['result' => 'WRITTEN'];
            }
            return ['item' => $locked->slug, 'rows' => $results, 'written' => $written,
                'snapshot_id' => $snapshot?->id];
        });
    }

    private function profile(array $source, array $mode): array
    {
        return $source['flight'] + [
            'status' => 'available',
            'muzzle_velocity_mps' => $mode['velocity_mps'],
            'head_size_m' => $source['head_size_m'],
            'reference_aim' => $source['reference_aim'],
            'zone_offsets_m' => $source['zone_offsets_m'],
            'source' => ['key' => 'bayou_index', 'url' => $source['source_url'],
                'data_url' => $source['data_url'], 'model_url' => $source['model_url'],
                'build_id' => $source['source_build_id'], 'observed_at' => $source['observed_at']],
        ];
    }

    private function row(string $field, mixed $current, mixed $source, bool $identityOk,
        $provenance, bool $checkOnly, int $confidence): array
    {
        $existing = $provenance->get($field);
        if (! $identityOk) [$action, $reason] = ['REVIEW_REQUIRED', 'Ammo identity is missing or ambiguous.'];
        elseif ($existing?->is_manual_override) [$action, $reason] = ['REVIEW_REQUIRED', 'Manual override is protected.'];
        elseif ($current === null && $existing !== null) [$action, $reason] = ['REVIEW_REQUIRED', 'Existing provenance owns this field.'];
        elseif ($source === null) [$action, $reason] = ['SKIP', 'Source value is unknown.'];
        elseif ($current === null) [$action, $reason] = $checkOnly
            ? ['SKIP', 'Check-only source cannot fill a missing field.'] : ['CREATE', null];
        elseif ($this->same($current, $source)) [$action, $reason] = ['UNCHANGED', null];
        else [$action, $reason] = ['REVIEW_REQUIRED', 'Existing HNT value differs and is protected.'];
        return compact('field', 'action', 'reason', 'confidence') + [
            'current_hnt' => $current, 'source_value' => $source, 'check_only' => $checkOnly];
    }

    private function same(mixed $left, mixed $right): bool
    {
        if (is_numeric($left) && is_numeric($right)) return abs((float) $left - (float) $right) < 0.0005;
        return $left == $right;
    }
}
