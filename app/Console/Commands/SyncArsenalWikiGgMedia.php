<?php

namespace App\Console\Commands;

use App\Models\EquipmentItem;
use App\Services\Equipment\WikiGgEquipmentSource;
use App\Services\Equipment\WikiGgMediaImportService;
use Illuminate\Console\Command;

class SyncArsenalWikiGgMedia extends Command
{
    protected $signature = 'arsenal:wiki-media
        {--item= : HNT item slug or external ID}
        {--dry-run : Preview wiki.gg media and skin metadata without writes}
        {--apply : Cache media and persist skin metadata for this one item}
        {--reviewed-revision= : Required wiki.gg revision ID for --apply}';

    protected $description = 'Preview or import wiki.gg Arsenal images and skin metadata for one HNT item.';

    public function handle(WikiGgEquipmentSource $source, WikiGgMediaImportService $media): int
    {
        $itemKey = trim((string) $this->option('item'));
        if ($itemKey === '') {
            $this->error('Please provide --item=<slug-or-source-id>.');

            return self::FAILURE;
        }

        if ((bool) $this->option('dry-run') === (bool) $this->option('apply')) {
            $this->error('Choose exactly one mode: --dry-run or --apply.');

            return self::FAILURE;
        }

        $item = EquipmentItem::query()
            ->with(['family', 'skins'])
            ->where(function ($query) use ($itemKey): void {
                $query->where('slug', $itemKey)->orWhere('external_id', $itemKey);
            })
            ->first();

        if (! $item) {
            $this->error('Arsenal item not found: '.$itemKey);

            return self::FAILURE;
        }

        try {
            $wiki = $media->preview($item, $source);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('wiki.gg media preview');
        $this->line('Item: '.$item->name);
        $this->line('Page: '.$wiki['page_url']);
        $this->line('Family: '.($wiki['family'] ?? '—'));
        $this->line('Revision: '.($wiki['revision_id'] ?? '—').' · '.($wiki['revision_timestamp'] ?? '—'));
        $this->line('Page images: '.$wiki['image_candidates']);
        $this->line('Base image: '.($wiki['base_image_file'] ?? 'NOT FOUND'));
        $this->line('Base URL: '.($wiki['base_image']['url'] ?? '—'));
        $this->line('Base license metadata: '.($wiki['base_image']['license'] ?? '—'));
        $mediaPlan = $media->plan($item, $wiki);
        $this->line('Base action: '.$mediaPlan['base']['action'].' · '.($mediaPlan['base']['reason'] ?? 'ready'));
        $this->newLine();

        $skinRows = [];
        foreach ($wiki['skins'] as $index => $skin) {
            $decision = $mediaPlan['skins'][$index] ?? null;
            $skinRows[] = [
                $skin['name'] ?? '—',
                $skin['rarity'] ?? '—',
                $skin['price'] ?? '—',
                $skin['source'] ?? '—',
                $skin['update'] ?? '—',
                $skin['image_file'] ?? 'NOT FOUND',
                ! empty($skin['image']['url']) ? 'YES' : 'NO',
                $decision['match_confidence'] ?? 0,
                $decision['action'] ?? 'SKIP',
                $decision['image_confidence'] ?? 0,
                $decision['image_action'] ?? 'SKIP',
                $decision['reason'] ?? $decision['image_reason'] ?? '—',
            ];
        }

        if ($skinRows) {
            $this->table(['Skin', 'Rarity', 'Price', 'Source', 'Update', 'Image file', 'Resolved', 'Match score', 'Match action', 'Image score', 'Image action', 'Reason'], $skinRows);
        } else {
            $this->warn('No skin infoboxes detected.');
        }

        $this->line('Patch history rows detected: '.count($wiki['patch_history']));
        $this->line('Ammo types detected: '.implode(', ', $wiki['ammo_types']));

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('READ-ONLY: no files or database rows changed.');

            return self::SUCCESS;
        }

        if (trim((string) $this->option('reviewed-revision')) === '' ||
            trim((string) $this->option('reviewed-revision')) !== (string) ($wiki['revision_id'] ?? '')) {
            $this->error('--apply requires the exact revision from the reviewed dry-run.');
            return self::FAILURE;
        }

        try {
            $counts = $media->apply($item, $wiki);
        } catch (\Throwable $e) {
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('wiki.gg media imported');
        foreach ($counts as $key => $value) {
            $this->line(ucwords(str_replace('_', ' ', $key)).': '.$value);
        }

        return self::SUCCESS;
    }
}
