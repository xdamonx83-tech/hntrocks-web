<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crown_shop_items')) {
            return;
        }

        $now = now();
        $items = [
            [
                'key' => 'moment_studio_fade',
                'type' => 'moment_studio_feature',
                'slot' => 'moment_studio_feature',
                'name_de' => 'Moment Studio: Ein-/Ausblenden',
                'name_en' => 'Moment Studio: Fade In/Out',
                'description_de' => 'Schaltet Ein- und Ausblenden für Clips im Moment Studio dauerhaft frei.',
                'description_en' => 'Permanently unlocks clip fade-in and fade-out in Moment Studio.',
                'price' => 150,
                'rarity' => 'rare',
                'icon' => 'circle-half',
                'preview_class' => 'moment-studio-fade',
                'sort_order' => 810,
            ],
            [
                'key' => 'moment_studio_filters',
                'type' => 'moment_studio_feature',
                'slot' => 'moment_studio_feature',
                'name_de' => 'Moment Studio: Filter',
                'name_en' => 'Moment Studio: Filters',
                'description_de' => 'Schaltet die Filterbibliothek im Moment Studio dauerhaft frei.',
                'description_en' => 'Permanently unlocks the filter library in Moment Studio.',
                'price' => 220,
                'rarity' => 'rare',
                'icon' => 'sliders',
                'preview_class' => 'moment-studio-filters',
                'sort_order' => 820,
            ],
            [
                'key' => 'moment_studio_effects',
                'type' => 'moment_studio_feature',
                'slot' => 'moment_studio_feature',
                'name_de' => 'Moment Studio: Effekte',
                'name_en' => 'Moment Studio: Effects',
                'description_de' => 'Schaltet dynamische Effekte wie VHS, Glitch und Zoom im Moment Studio dauerhaft frei.',
                'description_en' => 'Permanently unlocks dynamic effects such as VHS, glitch and zoom in Moment Studio.',
                'price' => 320,
                'rarity' => 'epic',
                'icon' => 'sparkle',
                'preview_class' => 'moment-studio-effects',
                'sort_order' => 830,
            ],
            [
                'key' => 'moment_studio_color_adjust',
                'type' => 'moment_studio_feature',
                'slot' => 'moment_studio_feature',
                'name_de' => 'Moment Studio: Farben anpassen',
                'name_en' => 'Moment Studio: Color Adjustments',
                'description_de' => 'Schaltet Belichtung, Kontrast, Sättigung, Temperatur und Transparenz im Moment Studio dauerhaft frei.',
                'description_en' => 'Permanently unlocks exposure, contrast, saturation, temperature and transparency in Moment Studio.',
                'price' => 220,
                'rarity' => 'rare',
                'icon' => 'palette',
                'preview_class' => 'moment-studio-colors',
                'sort_order' => 840,
            ],
            [
                'key' => 'moment_studio_transitions',
                'type' => 'moment_studio_feature',
                'slot' => 'moment_studio_feature',
                'name_de' => 'Moment Studio: Übergänge',
                'name_en' => 'Moment Studio: Transitions',
                'description_de' => 'Schaltet Übergänge zwischen Clips im Moment Studio dauerhaft frei.',
                'description_en' => 'Permanently unlocks transitions between clips in Moment Studio.',
                'price' => 300,
                'rarity' => 'epic',
                'icon' => 'arrows-left-right',
                'preview_class' => 'moment-studio-transitions',
                'sort_order' => 850,
            ],
        ];

        foreach ($items as $item) {
            $existing = DB::table('crown_shop_items')->where('key', $item['key'])->first();
            DB::table('crown_shop_items')->updateOrInsert(
                ['key' => $item['key']],
                array_merge($item, [
                    'is_active' => true,
                    'is_limited' => false,
                    'available_from' => null,
                    'available_until' => null,
                    'metadata' => json_encode(['source' => 'moment_studio', 'permanent_unlock' => true]),
                    'created_at' => $existing ? $existing->created_at : $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('crown_shop_items')) {
            return;
        }

        DB::table('crown_shop_items')->whereIn('key', [
            'moment_studio_fade',
            'moment_studio_filters',
            'moment_studio_effects',
            'moment_studio_color_adjust',
            'moment_studio_transitions',
        ])->delete();
    }
};
