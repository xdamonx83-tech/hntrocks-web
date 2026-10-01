<?php

namespace App\Console\Commands;

use App\Models\EquipmentItem;
use App\Services\Equipment\WikiGgEquipmentSource;
use Illuminate\Console\Command;

class PreviewArsenalWikiGgSync extends Command
{
    protected $signature = 'arsenal:wiki-sync
        {--item= : HNT item slug or external ID}
        {--dry-run : Required for the prototype; no database writes are performed}';

    protected $description = 'Preview structured Arsenal data from huntshowdown.wiki.gg via the MediaWiki API.';

    public function handle(WikiGgEquipmentSource $source): int
    {
        if (! $this->option('dry-run')) {
            $this->error('Prototype is read-only. Run with --dry-run.');

            return self::FAILURE;
        }

        $itemKey = trim((string) $this->option('item'));
        if ($itemKey === '') {
            $this->error('Please provide --item=<slug-or-source-id>.');

            return self::FAILURE;
        }

        $item = EquipmentItem::query()
            ->with(['family', 'stats.definition', 'ammo', 'traits', 'skins', 'patchHistory'])
            ->where(function ($query) use ($itemKey): void {
                $query->where('slug', $itemKey)->orWhere('external_id', $itemKey);
            })
            ->first();

        if (! $item) {
            $this->error('Arsenal item not found: '.$itemKey);

            return self::FAILURE;
        }

        try {
            $wiki = $source->preview($item);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('wiki.gg Arsenal preview');
        $this->line('Item: '.$item->name.' ('.$item->slug.')');
        $this->line('Page: '.$wiki['page_url']);
        $this->line('Mode: READ-ONLY DRY RUN');
        $this->newLine();

        $currentStats = $item->stats
            ->filter(fn ($stat) => $stat->definition?->key)
            ->mapWithKeys(fn ($stat) => [$stat->definition->key => $stat->value])
            ->all();

        $primaryAmmo = $item->ammo->first();

        $rows = [
            $this->row('Name', $item->name, $wiki['name']),
            $this->row('Price', $item->price, $wiki['price']),
            $this->row('Slots', $item->slot_size, $wiki['slot_size']),
            $this->row('Ammo Type', $item->ammo_type, $wiki['ammo_type']),
            $this->row('Update', $item->facts['release_pack'] ?? null, $wiki['update']),
            $this->row('Loaded', $primaryAmmo?->loaded, $wiki['loaded']),
            $this->row('Reserve', $primaryAmmo?->reserve, $wiki['reserve']),
        ];

        $statMap = [
            'damage' => 'Damage',
            'dropRange' => 'Drop Range',
            'rateOfFire' => 'Rate of Fire',
            'cycleTime' => 'Cycle Time',
            'spread' => 'Spread',
            'sway' => 'Sway',
            'recoil' => 'Vertical Recoil',
            'reload' => 'Reload Speed',
            'muzzleVelocity' => 'Muzzle Velocity',
            'swapSpeed' => 'Swap Speed',
            'radius' => 'Effect Radius',
            'effectDuration' => 'Effect Duration',
            'throwRange' => 'Throw Range',
            'fuseTimer' => 'Fuse Timer',
            'melee' => 'Melee Damage',
            'heavyMelee' => 'Heavy Melee Damage',
            'stamina' => 'Stamina Consumption',
            'heavyStamina' => 'Heavy Stamina Consumption',
            'throwStamina' => 'Throw Stamina Consumption',
        ];

        foreach ($statMap as $key => $label) {
            if (! array_key_exists($key, $wiki['stats'])) {
                continue;
            }

            $currentKey = $key === 'dropRange' ? 'effectiveRange' : $key;
            $current = $currentStats[$currentKey] ?? null;

            if ($key === 'damage' && $current === null) {
                $current = $primaryAmmo?->damage;
            }

            if ($key === 'muzzleVelocity' && $current === null) {
                $current = $primaryAmmo?->velocity;
            }

            $rows[] = $this->row($label, $current, $wiki['stats'][$key]);
        }

        $this->table(['Field', 'HNT current', 'wiki.gg', 'Status'], $rows);

        $differences = collect($rows)->where(3, 'DIFF')->count();
        $matches = collect($rows)->where(3, 'MATCH')->count();
        $missingCurrent = collect($rows)->where(3, 'HNT EMPTY')->count();

        $this->newLine();
        $this->line('Traits current: '.$item->traits->pluck('name')->implode(', '));
        $this->line('Traits wiki.gg: '.implode(', ', $wiki['recommended_traits']));
        $this->line('Skins current: '.$item->skins->count());
        $this->line('Skins wiki.gg detected: '.count($wiki['skins']));
        $this->line('Ammo types wiki.gg detected: '.implode(', ', $wiki['ammo_types']));
        $this->line('Patch rows current: '.$item->patchHistory->count());
        $this->line('Patch rows wiki.gg detected: '.count($wiki['patch_history']));
        $this->newLine();
        $this->info("Summary: {$matches} matching · {$differences} different · {$missingCurrent} missing in HNT");
        $this->line('No database writes were performed.');

        return self::SUCCESS;
    }

    private function row(string $label, mixed $current, mixed $wiki): array
    {
        return [
            $label,
            $this->display($current),
            $this->display($wiki),
            $this->status($current, $wiki),
        ];
    }

    private function status(mixed $current, mixed $wiki): string
    {
        if ($wiki === null || $wiki === '') {
            return 'WIKI EMPTY';
        }

        if ($current === null || $current === '') {
            return 'HNT EMPTY';
        }

        return $this->comparable($current) === $this->comparable($wiki) ? 'MATCH' : 'DIFF';
    }

    private function comparable(mixed $value): string
    {
        if (is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
        }

        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $value) ?? (string) $value));
    }

    private function display(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_array($value)) {
            return implode(' / ', array_map(fn ($entry) => $this->display($entry), $value));
        }

        return (string) $value;
    }
}
