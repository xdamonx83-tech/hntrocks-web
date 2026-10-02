<?php

namespace App\Services\Equipment;

use App\Models\EquipmentItem;

class WikiGgMediaConfidenceAudit
{
    private array $counts = [
        'skins_detected' => 0,
        'skin_images_resolved' => 0,
        'image_score_100' => 0,
        'image_score_80_99' => 0,
        'image_score_65_79' => 0,
        'image_score_below_65' => 0,
        'ambiguous_image_matches' => 0,
        'missing_image' => 0,
        'auto_importable' => 0,
        'review_required' => 0,
        'score_100_but_blocked' => 0,
    ];

    private array $problems = [];
    private array $blockedScore100 = [];

    public function add(EquipmentItem $item, array $wiki, array $plan, int $show): void
    {
        foreach ($wiki['skins'] ?? [] as $index => $skin) {
            $row = $plan['skins'][$index] ?? [];
            $score = (int) ($row['image_confidence'] ?? 0);
            $match = (int) ($row['match_confidence'] ?? 0);
            $resolved = ! empty($skin['image']['url']);
            $ambiguous = (bool) ($skin['image_ambiguous'] ?? false);

            $this->counts['skins_detected']++;
            if ($resolved) $this->counts['skin_images_resolved']++;
            if ($score === 100) $this->counts['image_score_100']++;
            elseif ($score >= 80) $this->counts['image_score_80_99']++;
            elseif ($score >= 65) $this->counts['image_score_65_79']++;
            else $this->counts['image_score_below_65']++;
            if ($ambiguous) $this->counts['ambiguous_image_matches']++;
            if (! $resolved) $this->counts['missing_image']++;

            $auto = $resolved && ! $ambiguous && $match === 100 && $score >= 85
                && ($row['action'] ?? null) === 'MATCH'
                && ($row['image_action'] ?? null) === 'IMPORT';

            if ($auto) {
                $this->counts['auto_importable']++;
                continue;
            }

            $this->counts['review_required']++;
            $existingId = $row['existing_skin_id'] ?? null;
            $existing = $existingId !== null && $item->relationLoaded('skins')
                ? $item->skins->firstWhere('id', $existingId) : null;

            $reason = match (true) {
                $ambiguous => 'Multiple equally ranked image candidates',
                ($row['action'] ?? null) === 'CREATE' => 'No exact existing skin mapping',
                $match < 100 => 'Skin match is not exact',
                default => $row['reason'] ?? $row['image_reason'] ?? 'No resolved image URL',
            };
            if ($score === 100) {
                $reasonKey = match (true) {
                    (bool) $existing?->local_asset_path => 'existing_local_image',
                    $ambiguous => 'ambiguous',
                    ! $resolved => 'missing_image',
                    $match < 100 => 'skin_match_not_exact',
                    ($row['action'] ?? null) === 'CREATE' => 'no_existing_skin_match',
                    default => 'other',
                };
                $this->counts['score_100_but_blocked']++;
                $this->blockedScore100[] = [
                    'item_slug' => $item->slug,
                    'item_name' => $item->name,
                    'skin_name' => (string) ($skin['name'] ?? '—'),
                    'image_file' => (string) ($row['image_file'] ?? '—'),
                    'match_score' => $match,
                    'image_score' => $score,
                    'local_asset_path' => $existing?->local_asset_path,
                    'action' => (string) ($row['image_action'] ?? 'SKIP'),
                    'blocked_reason' => (string) $reason,
                    'block_reason_key' => $reasonKey,
                ];
            }
            if (count($this->problems) >= $show) continue;
            $this->problems[] = [
                $item->slug,
                (string) ($skin['name'] ?? '—'),
                (string) ($row['image_file'] ?? '—'),
                (string) $match,
                (string) $score,
                (string) ($row['image_action'] ?? 'SKIP'),
                (string) $reason,
            ];
        }
    }

    public function counts(): array
    {
        return $this->counts;
    }

    public function problems(): array
    {
        return $this->problems;
    }

    public function blockedScore100(): array
    {
        return $this->blockedScore100;
    }

    public function blockedScore100Reasons(): array
    {
        return collect($this->blockedScore100)->countBy('block_reason_key')->all();
    }
}
