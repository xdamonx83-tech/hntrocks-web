<?php

namespace App\Console\Commands;

use App\Services\Equipment\EquipmentAssetImportService;
use Illuminate\Console\Command;

class ImportArsenalFanKitAssets extends Command
{
    protected $signature = 'arsenal:assets:import-fankit
        {path : Entpacktes offizielles Crytek Hunt Fan-Kit Verzeichnis}
        {--dry-run : Nur Zuordnung prüfen, keine Dateien oder DB-Werte ändern}';

    protected $description = 'Importiert passende Arsenal-Bilder aus einem lokal bereitgestellten offiziellen Crytek Hunt Fan Kit.';

    public function handle(EquipmentAssetImportService $service): int
    {
        try {
            $result = $service->importFanKit((string) $this->argument('path'), (bool) $this->option('dry-run'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $counts = $result['counts'];

        $this->line('Files: '.$counts['files']);
        $this->line('Matched: '.$counts['matched']);
        $this->line(($this->option('dry-run') ? 'Would import: ' : 'Imported: ').$counts['imported']);
        $this->line('Unchanged: '.$counts['unchanged']);
        $this->line('Unmatched: '.$counts['unmatched']);
        $this->line('Ambiguous: '.$counts['ambiguous']);
        $this->line('Invalid: '.$counts['invalid']);
        $this->line('Errors: '.$counts['errors']);

        return $counts['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
