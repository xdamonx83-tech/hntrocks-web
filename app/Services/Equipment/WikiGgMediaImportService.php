<?php

namespace App\Services\Equipment;

use App\Models\EquipmentItem;
use App\Models\EquipmentSkin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class WikiGgMediaImportService
{
    private const MAX_BYTES = 20 * 1024 * 1024;

    public function __construct(private EquipmentSourceSnapshotService $snapshots) {}

    public function preview(EquipmentItem $item, WikiGgEquipmentSource $source): array
    {
        return $source->preview($item);
    }

    public function plan(EquipmentItem $item, array $wiki): array
    {
        $item->loadMissing('skins');
        $confidence = ($wiki['resolution_method'] ?? 'direct') === 'direct'
            ? (int) ($wiki['resolution_score'] ?? 100) : 0;
        $base = $this->imageDecision(
            $item->local_asset_path,
            data_get($item->facts, 'wiki_gg.image.source_sha1'),
            $wiki['base_image'] ?? null,
            (int) ($wiki['base_image_confidence'] ?? 0),
            $confidence
        );
        $skinRows = [];
        foreach ($wiki['skins'] ?? [] as $skinData) {
            if (! is_array($skinData)) continue;
            [$existing, $matchScore, $matchReason] = $this->matchSkin($item, (string) ($skinData['name'] ?? ''));
            $image = $this->imageDecision(
                $existing?->local_asset_path,
                data_get($existing?->facts, 'wiki_gg.image.source_sha1'),
                $skinData['image'] ?? null,
                (int) ($skinData['image_confidence'] ?? 0),
                $confidence
            );
            $action = $matchReason === null ? ($existing ? 'MATCH' : 'CREATE') : 'REVIEW_REQUIRED';
            if ($confidence < 70) {
                $action = 'REVIEW_REQUIRED';
                $matchReason = 'Resolver confidence below 70';
            }
            if ($action === 'REVIEW_REQUIRED') {
                $image = ['action' => 'REVIEW_REQUIRED', 'reason' => $matchReason];
            }
            $skinRows[] = [
                'name' => $skinData['name'] ?? null,
                'existing_skin_id' => $existing?->id,
                'match_confidence' => $matchScore,
                'action' => $action,
                'reason' => $matchReason,
                'image_file' => $skinData['image_file'] ?? null,
                'image_confidence' => (int) ($skinData['image_confidence'] ?? 0),
                'image_action' => $image['action'],
                'image_reason' => $image['reason'],
            ];
        }

        return ['base' => $base, 'skins' => $skinRows];
    }

    public function apply(EquipmentItem $item, array $wiki): array
    {
        $plan = $this->plan($item, $wiki);
        return DB::transaction(function () use ($item, $wiki, $plan): array {
            $snapshot = $this->snapshots->recordWiki($item, $wiki);
            $counts = [
                'base_image' => 0,
                'skins_seen' => count($plan['skins']),
                'skins_created' => 0,
                'skins_updated' => 0,
                'skin_images_cached' => 0,
                'images_skipped' => 0,
                'review_required' => 0,
            ];

            if ($plan['base']['action'] === 'IMPORT' && ! empty($wiki['base_image']['url'])) {
                $image = $wiki['base_image'];
                $cached = $this->cacheImage((string) $image['url'], 'arsenal/wiki/items/'.$item->slug.'-'.substr(hash('sha256', (string) $image['url']), 0, 12));
                if ($cached) {
                    $facts = is_array($item->facts) ? $item->facts : [];
                    $facts['wiki_gg'] = array_merge($facts['wiki_gg'] ?? [], [
                        'page_title' => $wiki['page_title'] ?? null,
                        'page_url' => $wiki['page_url'] ?? null,
                        'revision_id' => $wiki['revision_id'] ?? null,
                        'revision_timestamp' => $wiki['revision_timestamp'] ?? null,
                        'image' => $this->imageMetadata($wiki, $image, $cached, $snapshot->id),
                    ]);
                    $item->forceFill([
                        'facts' => $facts,
                        'original_asset_url' => $image['url'],
                        'local_asset_path' => $cached['path'],
                        'license_note' => $this->licenseNote($image),
                    ])->save();
                    $counts['base_image']++;
                } else $counts['images_skipped']++;
            } elseif ($plan['base']['action'] === 'REVIEW_REQUIRED') {
                $counts['review_required']++;
            } else $counts['images_skipped']++;

            foreach ($plan['skins'] as $index => $row) {
                if ($row['action'] === 'REVIEW_REQUIRED') {
                    $counts['review_required']++;
                    continue;
                }
                $skinData = $wiki['skins'][$index];
                $skin = $row['existing_skin_id']
                    ? EquipmentSkin::findOrFail($row['existing_skin_id'])
                    : new EquipmentSkin;
                $created = ! $skin->exists;
                if ($created) {
                    $skin->equipment_item_id = $item->id;
                    $skin->external_id = 'wikigg-'.Str::slug((string) $row['name']);
                    $skin->name = $row['name'];
                }
                $facts = is_array($skin->facts) ? $skin->facts : [];
                $facts['wiki_gg'] = array_merge($facts['wiki_gg'] ?? [], [
                    'price' => $skinData['price'] ?? null,
                    'source' => $skinData['source'] ?? null,
                    'update' => $skinData['update'] ?? null,
                    'image_file' => $skinData['image_file'] ?? null,
                ]);
                $skin->rarity = $skin->rarity ?: ($skinData['rarity'] ?? null);
                $skin->source_url = $skin->source_url ?: ($wiki['page_url'] ?? null);
                if ($row['image_action'] === 'IMPORT' && ! empty($skinData['image']['url'])) {
                    $image = $skinData['image'];
                    $cached = $this->cacheImage((string) $image['url'], 'arsenal/wiki/skins/'.$item->slug.'/'.Str::slug((string) $row['name']).'-'.substr(hash('sha256', (string) $image['url']), 0, 12));
                    if ($cached) {
                        $facts['wiki_gg']['image'] = $this->imageMetadata($wiki, $image, $cached, $snapshot->id);
                        $skin->original_asset_url = $image['url'];
                        $skin->local_asset_path = $cached['path'];
                        $skin->license_note = $this->licenseNote($image);
                        $counts['skin_images_cached']++;
                    } else $counts['images_skipped']++;
                } elseif ($row['image_action'] === 'REVIEW_REQUIRED') {
                    $counts['review_required']++;
                } else $counts['images_skipped']++;
                $skin->facts = $facts;
                $skin->save();
                if ($created) $counts['skins_created']++;
                else $counts['skins_updated']++;
            }
            return $counts;
        });
    }

    private function imageDecision(?string $localPath, ?string $oldSha1, ?array $image, int $imageConfidence, int $resolverConfidence): array
    {
        if (empty($image['url'])) return ['action' => 'SKIP', 'reason' => 'No resolved image URL'];
        if ($resolverConfidence < 70 || $imageConfidence < 85) {
            return ['action' => 'REVIEW_REQUIRED', 'reason' => 'Low resolver or image confidence'];
        }
        if ($localPath) {
            if ($oldSha1 && $oldSha1 === ($image['sha1'] ?? null)) {
                return ['action' => 'SKIP', 'reason' => 'Existing local image has unchanged source hash'];
            }
            return ['action' => 'REVIEW_REQUIRED', 'reason' => 'Existing local image must be reviewed before replacement'];
        }
        return ['action' => 'IMPORT', 'reason' => null];
    }

    private function matchSkin(EquipmentItem $item, string $name): array
    {
        $key = Str::slug($name);
        if ($key === '') return [null, 0, 'Skin name is empty'];
        foreach ($item->skins as $skin) {
            if (Str::slug((string) $skin->name) === $key) return [$skin, 100, null];
        }
        $tokens = array_values(array_filter(explode('-', $key)));
        $scores = [];
        foreach ($item->skins as $skin) {
            $other = array_values(array_filter(explode('-', Str::slug((string) $skin->name))));
            $union = count(array_unique(array_merge($tokens, $other)));
            $score = $union ? (int) round(count(array_intersect($tokens, $other)) / $union * 100) : 0;
            $scores[] = [$skin, $score];
        }
        usort($scores, fn ($a, $b) => $b[1] <=> $a[1]);
        $best = $scores[0] ?? null;
        if (! $best || $best[1] < 45) return [null, 100, null];
        if ($best[1] >= 85 && $best[1] - ($scores[1][1] ?? 0) >= 15) {
            return [$best[0], $best[1], null];
        }
        return [$best[0], $best[1], 'Skin mapping is ambiguous'];
    }

    private function imageMetadata(array $wiki, array $image, array $cached, int $snapshotId): array
    {
        return [
            'source_key' => 'wiki_gg',
            'source_snapshot_id' => $snapshotId,
            'source_page' => $wiki['page_title'] ?? null,
            'source_file_name' => $image['file'] ?? null,
            'source_file_url' => $image['url'] ?? null,
            'source_description_url' => $image['description_url'] ?? null,
            'source_revision' => $wiki['revision_id'] ?? null,
            'source_revision_timestamp' => $wiki['revision_timestamp'] ?? null,
            'source_mime' => $image['mime'] ?? $cached['mime'],
            'source_sha1' => $image['sha1'] ?? null,
            'cached_sha1' => $cached['sha1'],
            'imported_at' => now()->toIso8601String(),
            'license' => $image['license'] ?? null,
            'artist' => $image['artist'] ?? null,
            'credit' => $image['credit'] ?? null,
            'copyrighted' => $image['copyrighted'] ?? null,
        ];
    }

    private function cacheImage(string $url, string $pathWithoutExtension): ?array
    {
        if (! $this->allowedMediaUrl($url)) {
            return null;
        }

        $response = Http::withHeaders([
            'Accept' => 'image/avif,image/webp,image/png,image/jpeg,*/*;q=0.5',
            'User-Agent' => 'HNT.ROCKS Arsenal Wiki Media Cache/1.0 (+https://hnt.rocks)',
        ])
            ->connectTimeout(8)
            ->timeout(25)
            ->retry(2, 350, throw: false)
            ->get($url);

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();
        if ($body === '' || strlen($body) > self::MAX_BYTES) {
            return null;
        }

        $info = @getimagesizefromstring($body);
        if (! is_array($info) || empty($info['mime'])) {
            return null;
        }

        $extension = match (strtolower((string) $info['mime'])) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            default => null,
        };

        if ($extension === null) {
            return null;
        }

        $path = $pathWithoutExtension.'.'.$extension;
        if (! Storage::disk('public')->put($path, $body)) {
            throw new RuntimeException('wiki.gg image could not be written to public storage.');
        }

        return ['path' => $path, 'mime' => $info['mime'], 'sha1' => sha1($body)];
    }

    private function allowedMediaUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        return $host === 'huntshowdown.wiki.gg'
            || $host === 'wiki.gg'
            || str_ends_with($host, '.wiki.gg');
    }

    private function licenseNote(array $image): string
    {
        $license = trim((string) ($image['license'] ?? ''));
        $descriptionUrl = trim((string) ($image['description_url'] ?? ''));

        $parts = ['Media imported via huntshowdown.wiki.gg'];
        if ($license !== '') {
            $parts[] = 'file metadata: '.$license;
        } else {
            $parts[] = 'game asset rights remain with Crytek / the respective rights holder';
        }
        if ($descriptionUrl !== '') {
            $parts[] = 'file page: '.$descriptionUrl;
        }

        return implode(' · ', $parts);
    }
}
