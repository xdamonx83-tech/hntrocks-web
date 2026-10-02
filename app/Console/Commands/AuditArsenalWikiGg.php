<?php

namespace App\Console\Commands;

use App\Models\EquipmentItem;
use App\Services\Equipment\WikiGgEquipmentSource;
use App\Services\Equipment\WikiGgMediaConfidenceAudit;
use App\Services\Equipment\WikiGgMediaImportService;
use Illuminate\Console\Command;

class AuditArsenalWikiGg extends Command
{
    protected $signature = 'arsenal:wiki-audit
        {--type=weapon : Item type to audit: weapon, tool or consumable}
        {--limit=0 : Maximum number of items; 0 means all}
        {--sleep-ms=250 : Delay between wiki.gg requests}
        {--show=25 : Maximum number of mismatch/error rows to print}
        {--media-confidence : Resolve image metadata and audit safe skin image imports}';

    protected $description = 'Read-only coverage audit of HNT Arsenal items against wiki.gg.';

    public function handle(
        WikiGgEquipmentSource $source,
        WikiGgMediaImportService $media,
        WikiGgMediaConfidenceAudit $mediaAudit,
    ): int
    {
        $type = strtolower(trim((string) $this->option('type')));
        if (! in_array($type, ['weapon', 'tool', 'consumable'], true)) {
            $this->error('Invalid --type. Use weapon, tool or consumable.');

            return self::FAILURE;
        }

        $limit = max(0, (int) $this->option('limit'));
        $sleepMs = max(0, min(5000, (int) $this->option('sleep-ms')));
        $show = max(0, min(100, (int) $this->option('show')));
        $mediaConfidence = (bool) $this->option('media-confidence');

        $query = EquipmentItem::query()
            ->with($mediaConfidence ? ['family', 'skins'] : ['family'])
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
            'resolved_direct' => 0,
            'resolved_search' => 0,
            'low_confidence' => 0,
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

        $errors = [];
        $searchMatches = [];
        $familyChanges = [];
        $imageIssues = [];

        $this->info('wiki.gg Arsenal coverage audit');
        $this->line('Type: '.$type);
        $this->line('Items: '.$items->count());
        $this->line('Mode: READ-ONLY');
        $this->line($mediaConfidence
            ? 'Media imageinfo is read for resolved pages; no image bytes are downloaded.'
            : 'Media URLs and revision metadata are skipped in this bulk audit.');
        $this->newLine();

        foreach ($items as $index => $item) {
            try {
                // The default mode remains one MediaWiki parse request per item.
                $wiki = $source->preview($item, $mediaConfidence, false);
                if ($mediaConfidence) {
                    $mediaAudit->add($item, $wiki, $media->plan($item, $wiki), $show);
                }
                $counts['resolved']++;
                if (($wiki['resolution_method'] ?? 'direct') === 'search') {
                    $counts['resolved_search']++;
                    $score = (int) ($wiki['resolution_score'] ?? 0);
                    if ($score < 70) {
                        $counts['low_confidence']++;
                    }
                    if (count($searchMatches) < $show) {
                        $searchMatches[] = [
                            $item->slug,
                            (string) ($item->name ?? '—'),
                            (string) ($wiki['page_title'] ?? '—'),
                            (string) $score,
                            $score >= 70 ? 'OK' : 'REVIEW',
                        ];
                    }
                } else {
                    $counts['resolved_direct']++;
                }
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

                        if (count($familyChanges) < $show) {
                            $familyChanges[] = [
                                $item->slug,
                                $currentFamily !== '' ? $currentFamily : '—',
                                $wikiFamily,
                                (string) ($wiki['page_title'] ?? '—'),
                            ];
                        }
                    }
                }

                if (empty($wiki['base_image_file']) && count($imageIssues) < $show) {
                    $imageIssues[] = [
                        $item->slug,
                        'BASE',
                        'No base image candidate',
                        (string) ($wiki['page_title'] ?? '—'),
                    ];
                }

                $missingSkinImages = array_filter($skins, fn (array $skin) => empty($skin['image_file']));
                if ($missingSkinImages && count($imageIssues) < $show) {
                    $imageIssues[] = [
                        $item->slug,
                        'SKIN',
                        (string) count($missingSkinImages).' missing of '.count($skins),
                        implode(', ', array_slice(array_values(array_filter(array_map(
                            fn (array $skin) => empty($skin['image_file']) ? (string) ($skin['name'] ?? 'unnamed') : null,
                            $skins
                        ))), 0, 5)),
                    ];
                }
            } catch (\Throwable $e) {
                $counts['failed']++;
                if (count($errors) < $show) {
                    $errors[] = [
                        $item->slug,
                        (string) ($item->name ?? '—'),
                        (string) ($item->family?->name ?? '—'),
                        $this->short($e->getMessage()),
                    ];
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
            ['Resolved direct', $counts['resolved_direct']],
            ['Resolved by search', $counts['resolved_search']],
            ['Low-confidence search matches', $counts['low_confidence']],
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

        if ($mediaConfidence) {
            $mediaCounts = $mediaAudit->counts();
            $this->newLine();
            $this->warn('Skin media confidence:');
            $this->table(['Metric', 'Count'], [
                ['Items total', $counts['total']],
                ['Items resolved', $counts['resolved']],
                ['Skins detected', $mediaCounts['skins_detected']],
                ['Skin images resolved', $mediaCounts['skin_images_resolved']],
                ['Image Score 100', $mediaCounts['image_score_100']],
                ['Image Score 80-99', $mediaCounts['image_score_80_99']],
                ['Image Score 65-79', $mediaCounts['image_score_65_79']],
                ['Image Score <65', $mediaCounts['image_score_below_65']],
                ['Ambiguous image matches', $mediaCounts['ambiguous_image_matches']],
                ['Missing image', $mediaCounts['missing_image']],
                ['Auto-importable', $mediaCounts['auto_importable']],
                ['Review required', $mediaCounts['review_required']],
                ['Score 100 but blocked', $mediaCounts['score_100_but_blocked']],
            ]);
            if ($mediaCounts['score_100_but_blocked'] > 0) {
                $this->newLine();
                $this->warn('Score 100 but blocked by reason:');
                $this->table(
                    ['Block reason', 'Count'],
                    collect($mediaAudit->blockedScore100Reasons())
                        ->map(fn (int $count, string $reason) => [$reason, $count])->values()->all(),
                );
                $this->table(
                    ['Item', 'Item name', 'Skin', 'Image file', 'Match', 'Image', 'Local path', 'Action', 'Blocked reason'],
                    array_map(fn (array $row) => [
                        $row['item_slug'], $row['item_name'], $row['skin_name'], $row['image_file'],
                        $row['match_score'], $row['image_score'], $row['local_asset_path'] ?? '—',
                        $row['action'], $row['blocked_reason'],
                    ], array_slice($mediaAudit->blockedScore100(), 0, $show)),
                );
            }
            if ($mediaAudit->problems()) {
                $this->newLine();
                $this->warn('Skin media problems (maximum '.$show.'):');
                $this->table(
                    ['Item', 'Skin', 'Image file', 'Match score', 'Image score', 'Action', 'Reason'],
                    $mediaAudit->problems(),
                );
            }
        }

        if ($errors) {
            $this->newLine();
            $this->error('Unresolved wiki.gg items:');
            $this->table(['Item', 'Name', 'Current family', 'Error'], $errors);
        }

        if ($searchMatches) {
            $this->newLine();
            $this->warn('Items resolved by wiki search:');
            $this->table(['Item', 'Name', 'wiki.gg page', 'Score', 'Status'], $searchMatches);
        }

        if ($familyChanges) {
            $this->newLine();
            $this->warn('Planned family corrections:');
            $this->table(['Item', 'Current family', 'wiki.gg family', 'wiki.gg page'], $familyChanges);
        }

        if ($imageIssues) {
            $this->newLine();
            $this->warn('Image issues:');
            $this->table(['Item', 'Type', 'Problem', 'Details'], $imageIssues);
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
