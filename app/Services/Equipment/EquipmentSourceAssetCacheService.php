<?php

namespace App\Services\Equipment;

use App\Models\EquipmentItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class EquipmentSourceAssetCacheService
{
    private const MAX_BYTES = 20 * 1024 * 1024;

    public function cache(?string $item = null, ?int $limit = null, bool $dryRun = false, bool $force = false): array
    {
        $query = EquipmentItem::query()
            ->where('source_status', 'active')
            ->whereNotNull('original_asset_url')
            ->where('original_asset_url', '!=', '')
            ->orderBy('id');

        if ($item !== null && $item !== '') {
            $query->where(function ($q) use ($item): void {
                $q->where('slug', $item)->orWhere('external_id', $item);
            });
        }

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $items = $query->get();

        $counts = [
            'candidates' => $items->count(),
            'would_cache' => 0,
            'cached' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'invalid' => 0,
            'errors' => 0,
        ];
        $errors = [];

        foreach ($items as $record) {
            $url = (string) $record->original_asset_url;

            if (! $this->isAllowedSourceUrl($url)) {
                $counts['skipped']++;
                continue;
            }

            if (! $force && $record->local_asset_path && Storage::disk('public')->exists($record->local_asset_path)) {
                $counts['unchanged']++;
                continue;
            }

            try {
                $response = Http::withHeaders([
                    'Accept' => 'image/avif,image/webp,image/png,image/jpeg,*/*;q=0.5',
                    'User-Agent' => 'HNT.ROCKS Arsenal Asset Cache/1.0',
                ])
                    ->connectTimeout(8)
                    ->timeout(20)
                    ->retry(2, 250, throw: false)
                    ->get($url);

                if (! $response->successful()) {
                    throw new RuntimeException('HTTP '.$response->status());
                }

                $body = $response->body();
                $length = strlen($body);

                if ($length < 1 || $length > self::MAX_BYTES) {
                    $counts['invalid']++;
                    continue;
                }

                $extension = $this->imageExtension($body);
                if ($extension === null) {
                    $counts['invalid']++;
                    continue;
                }

                $destination = 'arsenal/items/'.$record->slug.'.'.$extension;

                if ($dryRun) {
                    $counts['would_cache']++;
                    continue;
                }

                if (! Storage::disk('public')->put($destination, $body)) {
                    throw new RuntimeException('Lokale Bilddatei konnte nicht gespeichert werden.');
                }

                $record->forceFill([
                    'local_asset_path' => $destination,
                    'license_note' => $record->license_note ?: 'Source asset cached locally from wiki.huntify.win; rights remain with the respective rights holder.',
                ])->save();

                $counts['cached']++;
            } catch (\Throwable $e) {
                $counts['errors']++;
                if (count($errors) < 25) {
                    $errors[] = $record->slug.': '.$e->getMessage();
                }
            }
        }

        return ['counts' => $counts, 'errors' => $errors];
    }

    private function isAllowedSourceUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return false;
        }

        return strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === 'wiki.huntify.win'
            && str_starts_with((string) ($parts['path'] ?? ''), '/Hunt/assets/');
    }

    private function imageExtension(string $body): ?string
    {
        $info = @getimagesizefromstring($body);
        if (! is_array($info) || empty($info['mime'])) {
            return null;
        }

        return match (strtolower((string) $info['mime'])) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            default => null,
        };
    }
}
