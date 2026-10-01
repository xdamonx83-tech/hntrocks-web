<?php

namespace App\Services\Equipment;

use App\Models\EquipmentFamily;
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

    public function preview(EquipmentItem $item, WikiGgEquipmentSource $source): array
    {
        return $source->preview($item);
    }

    public function apply(EquipmentItem $item, array $wiki): array
    {
        return DB::transaction(function () use ($item, $wiki): array {
            $counts = [
                'base_image' => 0,
                'skins_seen' => count($wiki['skins'] ?? []),
                'skins_created' => 0,
                'skins_updated' => 0,
                'skin_images_cached' => 0,
                'images_skipped' => 0,
            ];

            $itemFacts = is_array($item->facts) ? $item->facts : [];
            $itemFacts['wiki_gg'] = [
                'page_title' => $wiki['page_title'] ?? null,
                'page_url' => $wiki['page_url'] ?? null,
                'revision_id' => $wiki['revision_id'] ?? null,
                'family' => $wiki['family'] ?? null,
                'update' => $wiki['update'] ?? null,
                'unlock' => $wiki['unlock'] ?? null,
                'loaded_raw' => $wiki['loaded'] ?? null,
                'reserve_raw' => $wiki['reserve'] ?? null,
                'image_file' => $wiki['base_image_file'] ?? null,
            ];

            $itemUpdate = [
                'facts' => $itemFacts,
            ];

            if ($item->item_type === 'weapon' && ! empty($wiki['family'])) {
                $familyName = trim((string) $wiki['family']);
                $family = EquipmentFamily::updateOrCreate(
                    ['key' => Str::slug($familyName)],
                    ['name' => $familyName]
                );
                $itemUpdate['family_id'] = $family->id;
            }

            if (! empty($wiki['base_image']['url'])) {
                $cached = $this->cacheImage(
                    (string) $wiki['base_image']['url'],
                    'arsenal/wiki/items/'.$item->slug,
                );

                if ($cached) {
                    $itemUpdate['original_asset_url'] = $wiki['base_image']['url'];
                    $itemUpdate['local_asset_path'] = $cached['path'];
                    $itemUpdate['license_note'] = $this->licenseNote($wiki['base_image']);
                    $counts['base_image'] = 1;
                } else {
                    $counts['images_skipped']++;
                }
            }

            $item->forceFill($itemUpdate)->save();

            $existingSkins = EquipmentSkin::query()
                ->where('equipment_item_id', $item->id)
                ->get()
                ->keyBy(fn (EquipmentSkin $skin) => Str::lower(trim((string) $skin->name)));

            foreach ($wiki['skins'] ?? [] as $skinData) {
                $name = trim((string) ($skinData['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $key = Str::lower($name);
                $skin = $existingSkins->get($key);
                $created = false;

                if (! $skin) {
                    $skin = new EquipmentSkin;
                    $skin->equipment_item_id = $item->id;
                    $skin->external_id = 'wikigg-'.Str::slug($name);
                    $skin->name = $name;
                    $created = true;
                }

                $facts = is_array($skin->facts) ? $skin->facts : [];
                $facts['wiki_gg'] = [
                    'price' => $skinData['price'] ?? null,
                    'source' => $skinData['source'] ?? null,
                    'update' => $skinData['update'] ?? null,
                    'image_file' => $skinData['image_file'] ?? null,
                    'image_description_url' => $skinData['image']['description_url'] ?? null,
                    'image_width' => $skinData['image']['width'] ?? null,
                    'image_height' => $skinData['image']['height'] ?? null,
                ];

                $skin->rarity = $skinData['rarity'] ?? $skin->rarity;
                $skin->source_url = $wiki['page_url'] ?? $skin->source_url;
                $skin->facts = $facts;

                if (! empty($skinData['image']['url'])) {
                    $cached = $this->cacheImage(
                        (string) $skinData['image']['url'],
                        'arsenal/wiki/skins/'.$item->slug.'/'.Str::slug($name),
                    );

                    if ($cached) {
                        $skin->original_asset_url = $skinData['image']['url'];
                        $skin->local_asset_path = $cached['path'];
                        $skin->license_note = $this->licenseNote($skinData['image']);
                        $counts['skin_images_cached']++;
                    } else {
                        $counts['images_skipped']++;
                    }
                }

                $skin->save();

                if ($created) {
                    $counts['skins_created']++;
                    $existingSkins->put($key, $skin);
                } else {
                    $counts['skins_updated']++;
                }
            }

            return $counts;
        });
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

        return ['path' => $path, 'mime' => $info['mime']];
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
