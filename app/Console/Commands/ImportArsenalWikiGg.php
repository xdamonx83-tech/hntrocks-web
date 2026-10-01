<?php

namespace App\Console\Commands;

use App\Models\EquipmentItem;
use App\Services\Equipment\WikiGgEquipmentSource;
use App\Services\Equipment\WikiGgImportService;
use Illuminate\Console\Command;

class ImportArsenalWikiGg extends Command
{
    protected $signature = 'arsenal:wiki-import
        {--type=weapon : Item type to process}
        {--item= : Single item slug or external ID}
        {--limit=0 : Limit number of items; 0 means all}
        {--dry-run : Build and display the import plan without writes}
        {--apply : Apply wiki.gg data}
        {--media : During --apply also resolve/cache base and skin images}
        {--show=50 : Maximum detail rows to print}';

    protected $description = 'Plan or apply structured Arsenal data from wiki.gg.';

    public function handle(WikiGgEquipmentSource $source, WikiGgImportService $import): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $apply = (bool) $this->option('apply');

        if ($dryRun === $apply) {
            $this->error('Choose exactly one mode: --dry-run or --apply.');

            return self::FAILURE;
        }

        $type = strtolower(trim((string) $this->option('type')));
        if (! in_array($type, ['weapon', 'tool', 'consumable'], true)) {
            $this->error('Invalid --type. Use weapon, tool or consumable.');

            return self::FAILURE;
        }

        $query = EquipmentItem::query()
            ->with(['family', 'stats.definition', 'ammo', 'traits', 'skins'])
            ->where('source_status', 'active')
            ->where('item_type', $type)
            ->orderBy('id');

        $itemKey = trim((string) $this->option('item'));
        if ($itemKey !== '') {
            $query->where(function ($builder) use ($itemKey): void {
                $builder->where('slug', $itemKey)->orWhere('external_id', $itemKey);
            });
        }

        $limit = max(0, (int) $this->option('limit'));
        if ($limit > 0) {
            $query->limit($limit);
        }

        $items = $query->get();
        if ($items->isEmpty()) {
            $this->warn('No matching Arsenal items found.');

            return self::SUCCESS;
        }

        $show = max(0, min(200, (int) $this->option('show')));
        $withMedia = $apply && (bool) $this->option('media');

        $counts = [
            'total' => $items->count(),
            'resolved' => 0,
            'failed' => 0,
            'items_with_changes' => 0,
            'family_changes' => 0,
            'price_changes' => 0,
            'slot_changes' => 0,
            'ammo_type_changes' => 0,
            'loaded_changes' => 0,
            'reserve_changes' => 0,
            'stat_changes' => 0,
            'trait_changes' => 0,
            'patch_rows' => 0,
            'skins' => 0,
            'skin_images' => 0,
            'base_images' => 0,
            'applied' => 0,
        ];

        $details = [];
        $errors = [];

        $this->info('wiki.gg Arsenal import '.($dryRun ? 'PLAN' : 'APPLY'));
        $this->line('Type: '.$type);
        $this->line('Items: '.$items->count());
        $this->line('Media: '.($withMedia ? 'YES' : 'NO'));
        $this->line('Fists or any other unresolved wiki item remains unchanged.');
        $this->newLine();

        foreach ($items as $index => $item) {
            try {
                $wiki = $source->preview($item, $withMedia, $apply);
                $plan = $import->plan($item, $wiki);
                $counts['resolved']++;

                $fieldChanges = $plan['field_changes'];
                foreach ([
                    'family' => 'family_changes',
                    'price' => 'price_changes',
                    'slot_size' => 'slot_changes',
                    'ammo_type' => 'ammo_type_changes',
                    'loaded' => 'loaded_changes',
                    'reserve' => 'reserve_changes',
                ] as $field => $counter) {
                    if (isset($fieldChanges[$field])) $counts[$counter]++;
                }

                $counts['stat_changes'] += count($plan['stat_changes']);
                if ($plan['traits_changed']) $counts['trait_changes']++;
                $counts['patch_rows'] += $plan['patch_rows'];
                $counts['skins'] += $plan['skins'];
                $counts['skin_images'] += $plan['skin_image_candidates'];
                if ($plan['base_image_candidate']) $counts['base_images']++;

                $hasChanges = $fieldChanges !== [] || $plan['stat_changes'] !== [] || $plan['traits_changed'];
                if ($hasChanges) $counts['items_with_changes']++;

                if (count($details) < $show && $hasChanges) {
                    $changes = [];

                    foreach ($fieldChanges as $field => $change) {
                        $changes[] = $field.': '.$this->value($change['from']).' → '.$this->value($change['to']);
                    }

                    if ($plan['stat_changes']) {
                        $changes[] = 'stats: '.count($plan['stat_changes']);
                    }

                    if ($plan['traits_changed']) {
                        $changes[] = 'traits';
                    }

                    $details[] = [
                        $item->slug,
                        (string) ($wiki['page_title'] ?? '—'),
                        implode(' · ', $changes),
                    ];
                }

                if ($apply) {
                    $import->apply($item, $wiki, $withMedia);
                    $counts['applied']++;
                }
            } catch (\Throwable $e) {
                $counts['failed']++;
                if (count($errors) < $show) {
                    $errors[] = [
                        $item->slug,
                        $item->name,
                        $this->short($e->getMessage()),
                    ];
                }
            }

            if (($index + 1) % 10 === 0 || ($index + 1) === $items->count()) {
                $this->line(sprintf(
                    '[%d/%d] resolved=%d failed=%d%s',
                    $index + 1,
                    $items->count(),
                    $counts['resolved'],
                    $counts['failed'],
                    $apply ? ' applied='.$counts['applied'] : '',
                ));
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Total', $counts['total']],
            ['Resolved', $counts['resolved']],
            ['Failed / unchanged fallback', $counts['failed']],
            ['Items with field/stat/trait changes', $counts['items_with_changes']],
            ['Family changes', $counts['family_changes']],
            ['Price changes', $counts['price_changes']],
            ['Slot changes', $counts['slot_changes']],
            ['Ammo type changes', $counts['ammo_type_changes']],
            ['Loaded changes', $counts['loaded_changes']],
            ['Reserve changes', $counts['reserve_changes']],
            ['Individual stat changes', $counts['stat_changes']],
            ['Trait set changes', $counts['trait_changes']],
            ['Patch rows available', $counts['patch_rows']],
            ['Skins detected', $counts['skins']],
            ['Skin image candidates', $counts['skin_images']],
            ['Base image candidates', $counts['base_images']],
            ['Applied', $counts['applied']],
        ]);

        if ($details) {
            $this->newLine();
            $this->warn('First planned changes:');
            $this->table(['Item', 'wiki.gg page', 'Changes'], $details);
        }

        if ($errors) {
            $this->newLine();
            $this->warn('Unresolved / skipped items:');
            $this->table(['Item', 'Name', 'Reason'], $errors);
        }

        $this->newLine();
        if ($dryRun) {
            $this->info('READ-ONLY: no database rows or files were changed.');
        } else {
            $this->info('wiki.gg import completed.');
        }

        return self::SUCCESS;
    }

    private function value(mixed $value): string
    {
        if ($value === null || $value === '') return '—';
        if (is_bool($value)) return $value ? 'true' : 'false';

        return (string) $value;
    }

    private function short(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_strlen($value) > 140 ? mb_substr($value, 0, 137).'...' : $value;
    }
}
