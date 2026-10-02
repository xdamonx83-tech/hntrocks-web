<?php

namespace App\Console\Commands;

use App\Models\EquipmentItem;
use App\Services\Equipment\WikiGgEquipmentSource;
use App\Services\Equipment\WikiGgMediaBatchService;
use App\Services\Equipment\WikiGgMediaImportService;
use Illuminate\Console\Command;

class ImportArsenalWikiGgMediaBatch extends Command
{
    protected $signature = 'arsenal:wiki-media-batch
        {--type=weapon : Item type: weapon, tool or consumable}
        {--dry-run : Plan only; no database or storage writes}
        {--apply : Import only exact existing-skin matches with no local image}
        {--limit=0 : Maximum items in this run; 0 means all}
        {--offset=0 : Skip this many active items ordered by ID}
        {--sleep-ms=250 : Delay between wiki.gg item previews}
        {--show=100 : Maximum problem rows to print, at most 100}
        {--score=100 : Required exact image score; only 100 is accepted}
        {--resume : Skip items whose existing HNT skins all have local images}
        {--stop-on-error : Stop after the first failed item}';

    protected $description = 'Plan or safely import wiki.gg skin images in bounded item batches.';

    public function handle(
        WikiGgEquipmentSource $source,
        WikiGgMediaBatchService $batch,
        WikiGgMediaImportService $media,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $apply = (bool) $this->option('apply');
        if ($dryRun === $apply) {
            $this->error('Choose exactly one mode: --dry-run or --apply.');
            return self::FAILURE;
        }
        $type = strtolower(trim((string) $this->option('type')));
        if (! in_array($type, ['weapon', 'tool', 'consumable'], true)) {
            $this->error('Invalid --type.');
            return self::FAILURE;
        }
        if ((int) $this->option('score') !== 100) {
            $this->error('Safe batch import requires --score=100.');
            return self::FAILURE;
        }

        $limit = max(0, (int) $this->option('limit'));
        $offset = max(0, (int) $this->option('offset'));
        $sleepMs = max(0, min(5000, (int) $this->option('sleep-ms')));
        $show = max(0, min(100, (int) $this->option('show')));
        $items = EquipmentItem::query()->with(['family', 'skins'])
            ->where('source_status', 'active')->where('item_type', $type)
            ->orderBy('id')->get()->slice($offset, $limit ?: null)->values();

        $counts = [
            'items_scanned' => $items->count(), 'items_resolved' => 0, 'items_resumed' => 0,
            'skins_scanned' => 0, 'image_score_100' => 0, 'auto_importable' => 0,
            'already_local' => 0, 'review_required' => 0, 'missing_image' => 0,
            'failed_items' => 0, 'planned_downloads' => 0,
            'images_downloaded' => 0, 'skins_updated' => 0, 'skins_created' => 0,
            'images_skipped' => 0, 'failures' => 0,
        ];
        $problems = [];

        $this->info('wiki.gg Arsenal skin media batch '.($dryRun ? 'PLAN' : 'APPLY'));
        $this->line('Type: '.$type.' · Items: '.$items->count().' · Offset: '.$offset.' · Score: 100');
        $this->line('Base images and existing local skin images are never replaced.');

        foreach ($items as $index => $item) {
            if ((bool) $this->option('resume') && $item->skins->isNotEmpty() &&
                $item->skins->every(fn ($skin) => (bool) $skin->local_asset_path)) {
                $counts['items_resumed']++;
                $counts['already_local'] += $item->skins->count();
                $counts['images_skipped'] += $item->skins->count();
                continue;
            }

            try {
                $wiki = $source->preview($item, true, true);
                $plan = $batch->plan($item, $wiki);
                $counts['items_resolved']++;
                foreach ($plan['rows'] as $row) {
                    $counts['skins_scanned']++;
                    if ($row['image_score'] === 100) $counts['image_score_100']++;
                    if ($row['action'] === 'IMPORT') {
                        $counts['auto_importable']++;
                        $counts['planned_downloads']++;
                    } elseif ($row['action'] === 'SKIP') {
                        $counts['already_local']++;
                        $counts['images_skipped']++;
                    } else {
                        $counts['review_required']++;
                        if ($row['reason'] === 'Missing resolved image URL') $counts['missing_image']++;
                    }
                    if ($row['action'] !== 'IMPORT') $this->problem($problems, $row, $show);
                }

                if ($apply && in_array('IMPORT', array_column($plan['rows'], 'action'), true)) {
                    $confirmed = $source->preview($item, true, true);
                    if ((string) ($confirmed['revision_id'] ?? '') === '' ||
                        (string) $confirmed['revision_id'] !== $plan['revision'] ||
                        $batch->fingerprint($confirmed) !== $plan['fingerprint']) {
                        foreach ($plan['rows'] as $row) {
                            if ($row['action'] !== 'IMPORT') continue;
                            $counts['review_required']++;
                            $row['action'] = 'REVIEW_REQUIRED';
                            $row['reason'] = 'Wiki revision or image metadata changed after plan';
                            $this->problem($problems, $row, $show);
                        }
                    } else {
                        $result = $media->applySafeSkinBatch($item, $confirmed, $plan);
                        foreach ($result as $key => $value) $counts[$key] += $value;
                    }
                }
            } catch (\Throwable $exception) {
                $counts['failed_items']++;
                $counts['failures']++;
                $this->problem($problems, [
                    'item_slug' => $item->slug, 'skin_name' => '—', 'image_file' => '—',
                    'match_score' => 0, 'image_score' => 0, 'action' => 'FAILED',
                    'reason' => $exception->getMessage(),
                ], $show);
                if ((bool) $this->option('stop-on-error')) break;
            }

            if (($index + 1) % 10 === 0 || $index + 1 === $items->count()) {
                $this->line(sprintf('[%d/%d] resolved=%d failed=%d', $index + 1, $items->count(),
                    $counts['items_resolved'], $counts['failed_items']));
            }
            if ($sleepMs > 0 && $index + 1 < $items->count()) usleep($sleepMs * 1000);
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], array_map(
            fn (string $key, int $value) => [ucwords(str_replace('_', ' ', $key)), $value],
            array_keys($counts), array_values($counts),
        ));
        if ($problems) {
            $this->newLine();
            $this->warn('Problems (maximum '.$show.'):');
            $this->table(['Item', 'Skin', 'Image file', 'Match', 'Image', 'Action', 'Reason'], $problems);
        }
        $this->info($dryRun
            ? 'READ-ONLY: no database rows or image files changed.'
            : 'Batch complete. Existing skin and base images were not replaced.');

        return $counts['failed_items'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function problem(array &$problems, array $row, int $show): void
    {
        if (count($problems) >= $show) return;
        $problems[] = [
            $row['item_slug'], $row['skin_name'], $row['image_file'],
            $row['match_score'], $row['image_score'], $row['action'],
            preg_replace('/\s+/', ' ', (string) $row['reason']),
        ];
    }
}
