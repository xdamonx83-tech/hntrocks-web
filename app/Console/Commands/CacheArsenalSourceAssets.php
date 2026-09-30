<?php

namespace App\Console\Commands;

use App\Services\Equipment\EquipmentSourceAssetCacheService;
use Illuminate\Console\Command;

class CacheArsenalSourceAssets extends Command
{
    protected $signature = 'arsenal:assets:cache-source
        {--dry-run : Quellen abrufen und prüfen, aber nichts lokal speichern}
        {--item= : Nur einen Item-Slug oder eine externe ID verarbeiten}
        {--limit= : Höchstens N Items verarbeiten}
        {--force : Bereits lokal vorhandene Bilder erneut abrufen}';

    protected $description = 'Cached vorhandene Arsenal-Source-Bilder sicher in HNT public storage.';

    public function handle(EquipmentSourceAssetCacheService $service): int
    {
        $limit = $this->option('limit');
        $limit = $limit !== null && $limit !== '' ? max(1, (int) $limit) : null;

        $result = $service->cache(
            item: ($this->option('item') ?: null),
            limit: $limit,
            dryRun: (bool) $this->option('dry-run'),
            force: (bool) $this->option('force'),
        );

        $counts = $result['counts'];

        $this->line('Candidates: '.$counts['candidates']);
        $this->line('Would cache: '.$counts['would_cache']);
        $this->line('Cached: '.$counts['cached']);
        $this->line('Unchanged: '.$counts['unchanged']);
        $this->line('Skipped: '.$counts['skipped']);
        $this->line('Invalid: '.$counts['invalid']);
        $this->line('Errors: '.$counts['errors']);

        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        return $counts['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
