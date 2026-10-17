<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ITEMS = [
        ['like_effect_heart_obsidian', 'Obsidian Hearts', 'Obsidian Hearts', 'Schwarze Herzen steigen beim Liken auf.', 'Black hearts float up when you like a post.', 140, 'common', 'heart-obsidian', 'Corazones negros aparecen al dar Me gusta.', 'Чёрные сердечки взлетают при лайке.', 220],
        ['like_effect_heart_blood', 'Blood Oath Hearts', 'Blood Oath Hearts', 'Blutrote Herzen steigen beim Liken auf.', 'Blood-red hearts float up when you like a post.', 210, 'rare', 'heart-blood', 'Corazones rojo sangre aparecen al dar Me gusta.', 'Кроваво-красные сердечки взлетают при лайке.', 221],
        ['like_effect_heart_neon', 'Neon Venom Hearts', 'Neon Venom Hearts', 'Neongruene Herzen steigen beim Liken auf.', 'Neon-green hearts float up when you like a post.', 340, 'epic', 'heart-neon', 'Corazones verde neon aparecen al dar Me gusta.', 'Неоново-зелёные сердечки взлетают при лайке.', 222],
        ['like_effect_heart_pink', 'Neon Pulse Hearts', 'Neon Pulse Hearts', 'Neonpinke Herzen steigen beim Liken auf.', 'Neon-pink hearts float up when you like a post.', 340, 'epic', 'heart-pink', 'Corazones rosa neon aparecen al dar Me gusta.', 'Неоново-розовые сердечки взлетают при лайке.', 223],
        ['like_effect_heart_gold', 'Gilded Hearts', 'Gilded Hearts', 'Goldene Herzen steigen beim Liken auf.', 'Golden hearts float up when you like a post.', 240, 'rare', 'heart-gold', 'Corazones dorados aparecen al dar Me gusta.', 'Золотые сердечки взлетают при лайке.', 224],
        ['like_effect_heart_frost', 'Frostfire Hearts', 'Frostfire Hearts', 'Eisblaue Herzen steigen beim Liken auf.', 'Frost-blue hearts float up when you like a post.', 420, 'legendary', 'heart-frost', 'Corazones azul hielo aparecen al dar Me gusta.', 'Ледяно-голубые сердечки взлетают при лайке.', 225],
        ['like_effect_thumb_obsidian', 'Obsidian Thumbs Up', 'Obsidian Thumbs Up', 'Schwarze Daumen-hoch-Symbole steigen beim Liken auf.', 'Black thumbs-up icons float up when you like a post.', 140, 'common', 'thumb-obsidian', 'Pulgares arriba negros aparecen al dar Me gusta.', 'Чёрные лайки взлетают при лайке.', 226],
        ['like_effect_thumb_blood', 'Blood Oath Thumbs Up', 'Blood Oath Thumbs Up', 'Blutrote Daumen-hoch-Symbole steigen beim Liken auf.', 'Blood-red thumbs-up icons float up when you like a post.', 210, 'rare', 'thumb-blood', 'Pulgares arriba rojo sangre aparecen al dar Me gusta.', 'Кроваво-красные лайки взлетают при лайке.', 227],
        ['like_effect_thumb_neon', 'Neon Venom Thumbs Up', 'Neon Venom Thumbs Up', 'Neongruene Daumen-hoch-Symbole steigen beim Liken auf.', 'Neon-green thumbs-up icons float up when you like a post.', 340, 'epic', 'thumb-neon', 'Pulgares arriba verde neon aparecen al dar Me gusta.', 'Неоново-зелёные лайки взлетают при лайке.', 228],
        ['like_effect_thumb_pink', 'Neon Pulse Thumbs Up', 'Neon Pulse Thumbs Up', 'Neonpinke Daumen-hoch-Symbole steigen beim Liken auf.', 'Neon-pink thumbs-up icons float up when you like a post.', 340, 'epic', 'thumb-pink', 'Pulgares arriba rosa neon aparecen al dar Me gusta.', 'Неоново-розовые лайки взлетают при лайке.', 229],
        ['like_effect_thumb_gold', 'Gilded Thumbs Up', 'Gilded Thumbs Up', 'Goldene Daumen-hoch-Symbole steigen beim Liken auf.', 'Golden thumbs-up icons float up when you like a post.', 240, 'rare', 'thumb-gold', 'Pulgares arriba dorados aparecen al dar Me gusta.', 'Золотые лайки взлетают при лайке.', 230],
        ['like_effect_thumb_frost', 'Frostfire Thumbs Up', 'Frostfire Thumbs Up', 'Eisblaue Daumen-hoch-Symbole steigen beim Liken auf.', 'Frost-blue thumbs-up icons float up when you like a post.', 420, 'legendary', 'thumb-frost', 'Pulgares arriba azul hielo aparecen al dar Me gusta.', 'Ледяно-голубые лайки взлетают при лайке.', 231],
    ];

    public function up(): void
    {
        foreach (self::ITEMS as [$key,$de,$en,$descriptionDe,$descriptionEn,$price,$rarity,$variant,$es,$ru,$sort]) {
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
                'icon' => 'heart',
                'preview_class' => $variant,
                'is_active' => true,
                'is_limited' => false,
                'sort_order' => $sort,
                'metadata' => json_encode(['effect'=>$variant,'preview_supported'=>true,'descriptions'=>['es'=>$es,'ru'=>$ru]],JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Never remove an item that a Hunter already owns.
        foreach (self::ITEMS as [$key]) {
            $item = DB::table('crown_shop_items')->where('key',$key)->first();
            if ($item && ! DB::table('crown_inventory_items')->where('shop_item_id',$item->id)->exists()) {
                DB::table('crown_shop_items')->where('id',$item->id)->delete();
            }
        }
    }
};
