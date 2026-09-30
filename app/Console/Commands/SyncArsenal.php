<?php
namespace App\Console\Commands;

use App\Services\Equipment\{EquipmentSourceInterface,EquipmentSyncService,HuntifyEquipmentSource};
use Illuminate\Console\Command;

class SyncArsenal extends Command
{
    protected $signature = 'arsenal:sync {--dry-run : Preview without database writes} {--item= : Source ID or slug}';
    protected $description = 'Synchronize the HNT equipment database from a source adapter';

    public function handle(EquipmentSyncService $service, HuntifyEquipmentSource $source): int
    {
        $counts = $service->sync($source,(bool)$this->option('dry-run'),$this->option('item'));
        foreach ($counts as $key=>$count) $this->line(ucfirst($key).': '.$count);
        return $counts['errors'] ? self::FAILURE : self::SUCCESS;
    }
}
