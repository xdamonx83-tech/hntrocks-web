<?php

namespace App\Services\Equipment;

use App\Models\EquipmentItem;

class WikiGgMediaBatchService
{
    public function __construct(private WikiGgMediaImportService $media) {}

    public function plan(EquipmentItem $item, array $wiki): array
    {
        $mediaPlan = $this->media->plan($item, $wiki);
        $revision = (string) ($wiki['revision_id'] ?? '');
        $rows = [];

        foreach ($wiki['skins'] ?? [] as $index => $skin) {
            $decision = $mediaPlan['skins'][$index] ?? [];
            $existingId = $decision['existing_skin_id'] ?? null;
            $existing = $existingId !== null ? $item->skins->firstWhere('id', $existingId) : null;
            $imageScore = (int) ($decision['image_confidence'] ?? 0);
            $matchScore = (int) ($decision['match_confidence'] ?? 0);
            $ambiguous = (bool) ($skin['image_ambiguous'] ?? false);
            $resolved = ! empty($skin['image']['url']);

            [$action, $reason] = match (true) {
                (bool) $existing?->local_asset_path => ['SKIP', 'Existing local skin image'],
                ! $resolved => ['REVIEW_REQUIRED', 'Missing resolved image URL'],
                $revision === '' => ['REVIEW_REQUIRED', 'Wiki revision is missing'],
                $ambiguous => ['REVIEW_REQUIRED', 'Ambiguous image candidates'],
                ($wiki['resolution_method'] ?? 'direct') !== 'direct' => ['REVIEW_REQUIRED', 'Wiki item resolution needs review'],
                $existing === null || ($decision['action'] ?? null) !== 'MATCH' => ['REVIEW_REQUIRED', 'No exact existing skin mapping'],
                $matchScore !== 100 => ['REVIEW_REQUIRED', 'Skin match is not exact'],
                $imageScore !== 100 => ['REVIEW_REQUIRED', 'Image score is not 100'],
                ($decision['image_action'] ?? null) !== 'IMPORT' => ['REVIEW_REQUIRED', (string) ($decision['image_reason'] ?? 'Media policy blocks import')],
                default => ['IMPORT', null],
            };

            $rows[] = [
                'item_slug' => $item->slug,
                'skin_name' => (string) ($skin['name'] ?? ''),
                'image_file' => (string) ($decision['image_file'] ?? ''),
                'existing_skin_id' => $existingId,
                'match_score' => $matchScore,
                'image_score' => $imageScore,
                'action' => $action,
                'reason' => $reason,
                'image_url' => $skin['image']['url'] ?? null,
                'image_sha1' => $skin['image']['sha1'] ?? null,
            ];
        }

        return [
            'item_slug' => $item->slug,
            'revision' => $revision,
            'fingerprint' => $this->fingerprint($wiki),
            'rows' => $rows,
            'base_action' => $mediaPlan['base']['action'],
        ];
    }

    public function fingerprint(array $wiki): string
    {
        $images = array_map(fn (array $skin) => [
            'name' => $skin['name'] ?? null,
            'file' => $skin['image_file'] ?? null,
            'url' => $skin['image']['url'] ?? null,
            'sha1' => $skin['image']['sha1'] ?? null,
            'confidence' => $skin['image_confidence'] ?? null,
            'ambiguous' => $skin['image_ambiguous'] ?? null,
        ], $wiki['skins'] ?? []);

        return hash('sha256', json_encode([
            'page_title' => $wiki['page_title'] ?? null,
            'revision_id' => $wiki['revision_id'] ?? null,
            'resolution_method' => $wiki['resolution_method'] ?? null,
            'skins' => $images,
        ], JSON_THROW_ON_ERROR));
    }
}
