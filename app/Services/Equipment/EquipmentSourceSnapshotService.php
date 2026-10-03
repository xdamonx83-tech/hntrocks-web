<?php

namespace App\Services\Equipment;

use App\Models\EquipmentItem;
use App\Models\EquipmentSource;
use App\Models\EquipmentSourceSnapshot;
use Illuminate\Support\Facades\DB;

class EquipmentSourceSnapshotService
{
    public function normalizeWiki(array $wiki): array
    {
        $skins = array_map(fn (array $skin) => [
            'name' => $skin['name'] ?? null,
            'rarity' => $skin['rarity'] ?? null,
            'price' => $skin['price'] ?? null,
            'source' => $skin['source'] ?? null,
            'update' => $skin['update'] ?? null,
            'image_file' => $skin['image_file'] ?? null,
            'image' => $this->normalizeImage($skin['image'] ?? null),
        ], array_values(array_filter($wiki['skins'] ?? [], 'is_array')));

        return [
            'page_title' => $wiki['page_title'] ?? null,
            'family' => $wiki['family'] ?? null,
            'name' => $wiki['name'] ?? null,
            'price' => $wiki['price'] ?? null,
            'slot_size' => $wiki['slot_size'] ?? null,
            'ammo_type' => $wiki['ammo_type'] ?? null,
            'update' => $wiki['update'] ?? null,
            'unlock' => $wiki['unlock'] ?? null,
            'loaded' => $wiki['loaded'] ?? null,
            'reserve' => $wiki['reserve'] ?? null,
            'quantity' => $wiki['quantity'] ?? null,
            'rarity' => $wiki['rarity'] ?? null,
            'stats' => array_filter((array) ($wiki['stats'] ?? []), 'is_numeric'),
            'recommended_traits' => array_values((array) ($wiki['recommended_traits'] ?? [])),
            'ammo_types' => array_values((array) ($wiki['ammo_types'] ?? [])),
            'skins' => $skins,
            'base_image_file' => $wiki['base_image_file'] ?? null,
            'base_image' => $this->normalizeImage($wiki['base_image'] ?? null),
        ];
    }

    public function hash(array $payload): string
    {
        return hash('sha256', json_encode($this->sorted($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public function recordWiki(EquipmentItem $item, array $wiki): EquipmentSourceSnapshot
    {
        $payload = $this->normalizeWiki($wiki);
        return $this->record(
            $item, 'wiki_gg', $payload, null, $wiki['page_title'] ?? null,
            isset($wiki['revision_id']) ? (string) $wiki['revision_id'] : null,
            $wiki['revision_timestamp'] ?? null, $wiki['page_url'] ?? null
        );
    }

    public function recordHuntify(EquipmentItem $item, EquipmentSource $source, array $facts): EquipmentSourceSnapshot
    {
        return $this->record(
            $item, $source->key, $facts, $source->id, $item->external_id,
            null, null, $facts['source_url'] ?? null
        );
    }

    public function recordBayouBallistics(EquipmentItem $item, array $source): EquipmentSourceSnapshot
    {
        // Persist only the parsed numeric preview and identity, never page HTML or assets.
        $payload = [
            'name' => $source['name'],
            'ammo_type' => $source['ammo_type'],
            'observed_at' => $source['observed_at'],
            'fields' => $source['fields'],
            'checks' => $source['checks'],
            'source_payload_hash' => $source['payload_hash'],
        ];

        return $this->record(
            $item, 'bayou_index', $payload, null, $source['source_page_slug'],
            null, null, $source['source_url']
        );
    }

    public function recordBayouCatalog(EquipmentItem $item, array $weapon, array $catalog): EquipmentSourceSnapshot
    {
        return $this->record(
            $item, 'bayou_index', [
                'name' => $weapon['name'],
                'source_weapon_id' => $weapon['id'],
                'source_build_id' => $catalog['build_id'],
                'data_sha256' => $catalog['data_sha256'],
                'model_sha256' => $catalog['model_sha256'],
                'zoom' => $weapon['zoom'],
                'modes' => $weapon['modes'],
            ], null, $weapon['id'], $catalog['build_id'], null, $weapon['source_url']
        );
    }

    public function recordBayouCarbinePilot(EquipmentItem $item, array $source): EquipmentSourceSnapshot
    {
        return $this->record(
            $item, 'bayou_index', $source, null, $source['slug'],
            $source['source_build_id'], null, $source['source_url']
        );
    }

    private function record(
        EquipmentItem $item,
        string $sourceKey,
        array $payload,
        ?int $sourceId,
        ?string $externalId,
        ?string $revisionId,
        ?string $revisionTimestamp,
        ?string $sourceUrl,
    ): EquipmentSourceSnapshot {
        $payloadHash = $this->hash($payload);
        $identity = hash('sha256', implode('|', [$sourceKey, (string) $item->getKey(), $payloadHash]));

        return DB::transaction(fn () => EquipmentSourceSnapshot::firstOrCreate(
            ['identity_hash' => $identity],
            [
                'equipment_item_id' => $item->getKey(),
                'equipment_source_id' => $sourceId,
                'source_key' => $sourceKey,
                'source_external_id' => $externalId,
                'source_revision_id' => $revisionId,
                'source_revision_timestamp' => $revisionTimestamp,
                'source_url' => $sourceUrl,
                'payload_hash' => $payloadHash,
                'normalized_payload' => $payload,
                'fetched_at' => now(),
            ]
        ));
    }

    private function sorted(array $data): array
    {
        if (! array_is_list($data)) {
            ksort($data);
        }
        foreach ($data as &$value) {
            if (is_array($value)) $value = $this->sorted($value);
        }
        return $data;
    }

    private function normalizeImage(?array $image): ?array
    {
        if (! $image) return null;
        return [
            'file' => $image['file'] ?? null,
            'url' => $image['url'] ?? null,
            'description_url' => $image['description_url'] ?? null,
            'mime' => $image['mime'] ?? null,
            'sha1' => $image['sha1'] ?? null,
            'size' => $image['size'] ?? null,
            'width' => $image['width'] ?? null,
            'height' => $image['height'] ?? null,
            'license' => $image['license'] ?? null,
        ];
    }
}
