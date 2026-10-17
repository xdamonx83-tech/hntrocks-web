<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ITEMS = [
        ['like_effect_soul_whisper', 'Soul Whisper', 'Soul Whisper', 'Grün schimmernde HR:-Geister steigen langsam auf.', 'Soft green ghostlike HR: sparks rise slowly.', 180, 'rare', 'soul-whisper'],
        ['like_effect_blood_moon', 'Blood Moon', 'Blood Moon', 'Ein roter Blutmond-Ausbruch mit warmen Glutpartikeln.', 'A crimson blood-moon burst with glowing cinders.', 280, 'rare', 'blood-moon'],
        ['like_effect_golden_hunter', 'Golden Hunter', 'Golden Hunter', 'Goldene HR:-Funken begleiten deinen Like.', 'Golden HR: sparks follow every like.', 460, 'epic', 'golden-hunter'],
        ['like_effect_ashes', 'Ashes', 'Ashes', 'Verwitterte Asche zerfällt und schwebt davon.', 'Weathered ash drifts upward and dissolves.', 160, 'common', 'ashes'],
        ['like_effect_cursed', 'Cursed', 'Cursed', 'Dunkle Partikel und giftgrüne Seelenzeichen.', 'Shadow particles with eerie green soul marks.', 650, 'legendary', 'cursed'],
        ['like_effect_bayou_mist', 'Bayou Mist', 'Bayou Mist', 'Kühle Sumpfnebel-Partikel umspielen das HR:-Logo.', 'Soft swamp mist swirls around the HR: mark.', 240, 'rare', 'bayou-mist'],
        ['like_effect_iron_sparks', 'Iron Sparks', 'Iron Sparks', 'Funken wie vom Schmiedehammer sprühen kurz auf.', 'Forged-iron sparks scatter from the like.', 130, 'common', 'iron-sparks'],
        ['like_effect_moonfire', 'Moonfire', 'Moonfire', 'Eisblaues Mondfeuer schwebt über dem Like.', 'Cold blue moonfire rises above the like.', 550, 'epic', 'moonfire'],
    ];

    private const TRANSLATIONS = [
        'like_effect_soul_whisper' => ['es' => 'Señales fantasmales verdes de HR: ascienden lentamente.', 'ru' => 'Зелёные призрачные символы HR: медленно поднимаются.'],
        'like_effect_blood_moon' => ['es' => 'Una ráfaga roja de luna sangrienta con brasas brillantes.', 'ru' => 'Вспышка кровавой луны с багровыми искрами.'],
        'like_effect_golden_hunter' => ['es' => 'Destellos dorados de HR: acompañan tu Me gusta.', 'ru' => 'Золотые искры HR: сопровождают лайк.'],
        'like_effect_ashes' => ['es' => 'La ceniza desgastada se disuelve mientras asciende.', 'ru' => 'Пепел поднимается и постепенно исчезает.'],
        'like_effect_cursed' => ['es' => 'Partículas oscuras y señales de almas verdes.', 'ru' => 'Тёмные частицы и ядовито-зелёные знаки душ.'],
        'like_effect_bayou_mist' => ['es' => 'Partículas de niebla del pantano rodean el símbolo HR:.', 'ru' => 'Частицы болотного тумана окружают символ HR:.'],
        'like_effect_iron_sparks' => ['es' => 'Breves chispas saltan como golpes de un martillo de herrero.', 'ru' => 'Яркие искры разлетаются, словно от удара кузнечного молота.'],
        'like_effect_moonfire' => ['es' => 'Fuego lunar azul helado se eleva con cada Me gusta.', 'ru' => 'Холодное голубое лунное пламя поднимается после лайка.'],
    ];

    public function up(): void
    {
        foreach (self::ITEMS as $i => [$key, $de, $en, $descriptionDe, $descriptionEn, $price, $rarity, $preview]) {
            DB::table('crown_shop_items')->insertOrIgnore([
                'key' => $key,
                'type' => 'like_effect',
                'slot' => 'like_effect',
                'name_de' => $de,
                'name_en' => $en,
                'description_de' => $descriptionDe,
                'description_en' => $descriptionEn,
                'price' => $price,
                'rarity' => $rarity,
                'icon' => 'sparkle',
                'preview_class' => $preview,
                'is_active' => true,
                'is_limited' => false,
                'sort_order' => 210 + $i,
                'metadata' => json_encode(['effect' => $preview, 'preview_supported' => true, 'descriptions' => self::TRANSLATIONS[$key]], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Never destroy a user's legitimately owned/equipped cosmetic on rollback.
        foreach (self::ITEMS as [$key]) {
            $item = DB::table('crown_shop_items')->where('key', $key)->first();
            if ($item && ! DB::table('crown_inventory_items')->where('shop_item_id', $item->id)->exists()) {
                DB::table('crown_shop_items')->where('id', $item->id)->delete();
            }
        }
    }
};
