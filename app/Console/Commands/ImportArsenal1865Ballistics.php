<?php

namespace App\Console\Commands;

use App\Models\EquipmentItem;
use App\Services\Equipment\CarbineBallisticsPilot;
use Illuminate\Console\Command;

class ImportArsenal1865Ballistics extends Command
{
    protected $signature = 'arsenal:1865-ballistics
        {--dry-run : Read-only plan}
        {--apply : Write CREATE fields only}';

    protected $description = 'Audit or safely import verified public 1865 Carbine ballistics.';

    public function handle(CarbineBallisticsPilot $pilot): int
    {
        if ((bool) $this->option('dry-run') === (bool) $this->option('apply')) {
            $this->error('Choose exactly one of --dry-run or --apply.');
            return self::FAILURE;
        }
        $item = EquipmentItem::query()->where('slug', '1865-carbine')
            ->where('item_type', 'weapon')->where('source_status', 'active')->first();
        if ($item === null) {
            $this->error('Active 1865 Carbine not found.');
            return self::FAILURE;
        }
        $apply = (bool) $this->option('apply');
        $result = $apply ? $pilot->apply($item) : $pilot->plan($item);
        $this->info($apply ? '1865 pilot apply: CREATE only.' : '1865 pilot plan: READ-ONLY; DB writes: 0.');
        $this->table(['Field', 'Current HNT', 'Source value', 'Action', 'Confidence', 'Reason'],
            array_map(fn ($row) => [
                $row['field'], $this->display($row['current_hnt']), $this->display($row['source_value']),
                $apply ? $row['result'] : $row['action'], $row['confidence'], $row['reason'] ?? '—',
            ], $result['rows']));
        $this->line('Canonical values written: '.($result['written'] ?? 0).'.');
        return self::SUCCESS;
    }

    private function display(mixed $value): string
    {
        if ($value === null) return '—';
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return (string) $value;
    }
}
