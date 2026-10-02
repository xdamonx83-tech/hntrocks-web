<?php

use App\Services\Equipment\EquipmentStatCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private const KEYS = [
        'baseDamage', 'zoom', 'headMultiplier', 'upperTorsoMultiplier',
        'torsoMultiplier', 'armMultiplier', 'legMultiplier',
    ];

    public function up(): void
    {
        foreach (EquipmentStatCatalog::DEFINITIONS as $index => [$key, $label, $unit, $direction, $group]) {
            if (! in_array($key, self::KEYS, true)) continue;

            DB::table('equipment_stat_definitions')->insertOrIgnore([
                'key' => $key,
                'label' => $label,
                'unit' => $unit,
                'comparison_direction' => $direction,
                'group' => $group,
                'sort_order' => ($index + 1) * 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Definitions may predate this migration or already be in use. Keep them intact.
    }
};
