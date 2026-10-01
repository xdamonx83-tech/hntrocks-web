<?php

namespace App\Console\Commands;

use App\Models\EquipmentItem;
use App\Services\Equipment\WikiGgEquipmentSource;
use Illuminate\Console\Command;

class AuditArsenalWikiGg extends Command
{
    protected $signature = 'arsenal:wiki-audit
        {--type=weapon : Item type to audit: weapon, tool or consumable}
        {--limit=0 : Maximum number of items; 0 means all}
        {--sleep-ms=250 : Delay between wiki.gg requests}
        {--show=25 : Maximum number of mismatch/error rows to print}';

    protected $description = 'Read-only coverage audit of HNT Arsenal items against wiki.gg.';

    public function handle(WikiGgEquipmentSource $source): int
    {
        $type = strtolower(trim((string) $this->option('type')));
        if (! in_array($type, ['weapon', 'tool', 'consumable'], true)) {
            $this->error('Invalid --type. Use weapon, tool or consumable.');

            return self::FAILURE;
        }

        $limit = max(0, (int) $this->option('limit'));
        $sleepMs = max(0, min(5000, (int) $this->option('sleep-ms')));
        $show = max(0, min(100, (int) $this->option('show')));

        $query = EquipmentItem::query()
            ->with('family')
            ->where('source_status', 'active')
            ->where('item_type', $type)
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $items = $query->get();
        if ($items->isEmpty()) {
            $this->warn('No matching Arsenal items found.');

            return self::SUCCESS;
        }

        $counts = [
            'total' => $items->count(),
            'resolved' => 0,
            'failed' => 0,
            'family_match' => 0,
            'family_change' => 0,
            'base_image' => 0,
            'skins' => 0,
            'skin_images' => 0,
            'stats' => 0,
            'traits' => 0,
            'ammo_types' => 0,
            'patch_rows' => 0,
        ];

        $issues = [];

        $this->info('wiki.gg Arsenal coverage audit');
        $this->line('Type: '.$type);
        $this->line('Items: '.$items->count());
        $this->line('Mode: READ-ONLY');
        $this->line('Media URLs and revision metadata are skipped in this bulk audit.');
        $this->newLine();

        foreach ($items as $index => $item) {
            try {
                // One MediaWiki parse request per item: enough to audit page/family/stats/media filenames.
                $wiki = $source->preview($item, false, false);
                $counts['resolved']++;
                $counts['stats'] += count($wiki['stats'] ?? []);
                $counts['traits'] += count($wiki['recommended_traits'] ?? []);
                $counts['ammo_types'] += count($wiki['ammo_types'] ?? []);
                $counts['patch_rows'] += count($wiki['patch_history'] ?? []);

                if (! empty($wiki['base_image_file'])) {
                    $counts['base_image']++;
                }

                $skins = is_array($wiki['skins'] ?? null) ? $wiki['skins'] : [];
                $counts['skins'] += count($skins);
                $counts['skin_images'] += count(array_filter($skins, fn (array $skin) => ! empty($skin['image_file'])));

                if ($type === 'weapon') {
                    $currentFamily = trim((string) ($item->family?->name ?? ''));
                    $wikiFamily = trim((string) ($wiki['family'] ?? ''));

                    if ($wikiFamily !== '' && strcasecmp($currentFamily, $wikiFamily) === 0) {
                        $counts['family_match']++;
                    } elseif ($wikiFamily !== '') {
                        $counts['family_change']++;

                        if (count($issues) < $show) {
                            $issues[] = [
                                $item->slug,
                                'FAMILY',
                                $currentFamily !== '' ? $currentFamily : '—',
                                $wikiFamily,
                            ];
                        }
                    }
                }

                if (empty($wiki['base_image_file']) && count($issues) < $show) {
                    $issues[] = [$item->slug, 'IMAGE', '—', 'No base image candidate'];
                }

                $missingSkinImages = array_filter($skins, fn (array $skin) => empty($skin['image_file']));
                if ($missingSkinImages && count($issues) < $show) {
                    $issues[] = [
                        $item->slug,
                        'SKIN IMAGE',
                        (string) count($missingSkinImages).' missing',
                        (string) count($skins).' skins detected',
                    ];
                }
            } catch (\Throwable $e) {
                $counts['failed']++;
                if (count($issues) < $show) {
                    $issues[] = [$item->slug, 'ERROR', '—', $this->short($e->getMessage())];
                }
            }

            if (($index + 1) % 10 === 0 || ($index + 1) === $items->count()) {
                $this->line(sprintf(
                    '[%d/%d] resolved=%d failed=%d',
                    $index + 1,
                    $items->count(),
                    $counts['resolved'],
                    $counts['failed'],
                ));
            }

            if ($sleepMs > 0 && ($index + 1) < $items->count()) {
                usleep($sleepMs * 1000);
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Total', $counts['total']],
            ['Resolved wiki pages', $counts['resolved']],
            ['Failed', $counts['failed']],
            ['Family matches', $counts['family_match']],
            ['Family changes', $counts['family_change']],
            ['Base image candidates', $counts['base_image']],
            ['Skins detected', $counts['skins']],
            ['Skin image candidates', $counts['skin_images']],
            ['Structured stat values', $counts['stats']],
            ['Recommended traits', $counts['traits']],
            ['Ammo types', $counts['ammo_types']],
            ['Patch history rows', $counts['patch_rows']],
        ]);

        if ($issues) {
            $this->newLine();
            $this->warn('First audit issues / planned family corrections:');
            $this->table(['Item', 'Type', 'Current', 'wiki.gg'], $issues);
        }

        $this->newLine();
        $this->info('Audit complete. No database rows or image files were changed.');

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function short(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_strlen($value) > 120 ? mb_substr($value, 0, 117).'...' : $value;
    }
}
