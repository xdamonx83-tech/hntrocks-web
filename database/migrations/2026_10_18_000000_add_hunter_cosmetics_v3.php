<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // [key, type, slot, de, en, es, ru, descDe, descEn, descEs, descRu,
    //  price, rarity, css variant, icon]
    private const ITEMS = [
        ['avatar_frame_ember_wreath', 'avatar_frame', 'avatar_frame', 'Glutkranz', 'Ember Wreath', 'Aro de brasas', 'Огненный венец', 'Ein kreisender Glutkranz um deinen Avatar.', 'A rotating halo of embers around your avatar.', 'Un halo de brasas gira alrededor de tu avatar.', 'Вокруг аватара вращается огненный венец.', 290, 'rare', 'ember-wreath', 'flame'],
        ['avatar_frame_soul_vortex', 'avatar_frame', 'avatar_frame', 'Seelenwirbel', 'Soul Vortex', 'Vórtice de almas', 'Вихрь душ', 'Bläuliche Seelenenergie pulsiert im Rahmen.', 'A pulse of blue soul energy surrounds your portrait.', 'Energía azul de almas rodea tu retrato.', 'Портрет окружён пульсирующей энергией душ.', 540, 'epic', 'soul-vortex', 'sparkle'],
        ['avatar_frame_venom_bloom', 'avatar_frame', 'avatar_frame', 'Giftblüte', 'Venom Bloom', 'Flor venenosa', 'Ядовитый цветок', 'Giftgrüne Strahlen wandern um dein Bild.', 'Toxic green light traces the edge of your portrait.', 'Un brillo verde tóxico recorre el retrato.', 'Ядовито-зелёное свечение огибает портрет.', 390, 'rare', 'venom-bloom', 'leaf'],
        ['avatar_frame_frost_halo', 'avatar_frame', 'avatar_frame', 'Frosthalo', 'Frost Halo', 'Halo helado', 'Ледяной ореол', 'Eiskalte Funken gleiten über einen Frostkreis.', 'Icy sparks glide along a frozen halo.', 'Chispas heladas recorren un halo congelado.', 'Ледяные искры скользят по морозному ореолу.', 660, 'epic', 'frost-halo', 'snowflake'],
        ['avatar_frame_gold_ascendant', 'avatar_frame', 'avatar_frame', 'Goldaufstieg', 'Gilded Ascendant', 'Ascenso dorado', 'Золотое восхождение', 'Ein seltener, rotierender Goldrahmen mit warmem Glanz.', 'A rotating golden frame with a brilliant aura.', 'Un marco dorado giratorio con un aura intensa.', 'Вращающаяся золотая рамка с ярким сиянием.', 980, 'legendary', 'gold-ascendant', 'crown'],
        ['avatar_frame_blood_eclipse', 'avatar_frame', 'avatar_frame', 'Blutfinsternis', 'Blood Eclipse', 'Eclipse sangriento', 'Кровавое затмение', 'Dunkle Schatten und rotes Licht kreisen um den Avatar.', 'Crimson light and shadows orbit the avatar.', 'Luces carmesí y sombras rodean el avatar.', 'Алый свет и тени кружат вокруг аватара.', 1150, 'legendary', 'blood-eclipse', 'moon'],
        ['title_gravebound', 'title', 'profile_title', 'Grabgebunden', 'Gravebound', 'Atado a la tumba', 'Узы могилы', 'Ein Titel für Hunter, die den Tod nicht fürchten.', 'A title for hunters who do not fear death.', 'Título para quienes no temen a la muerte.', 'Титул для охотников, не боящихся смерти.', 170, 'common', 'title-gravebound', 'tag'],
        ['title_night_stalker', 'title', 'profile_title', 'Nachtpirscher', 'Night Stalker', 'Acechador nocturno', 'Ночной охотник', 'Für lautlose Jagden nach Sonnenuntergang.', 'For silent hunts after sundown.', 'Para cacerías silenciosas al anochecer.', 'Для бесшумной охоты после заката.', 260, 'rare', 'title-night-stalker', 'moon'],
        ['title_dustborn', 'title', 'profile_title', 'Staubgeboren', 'Dustborn', 'Nacido del polvo', 'Рождённый в пыли', 'Ein staubiger Ehrentitel aus dem Bayou.', 'A dusty badge of honor from the bayou.', 'Un honor curtido en el polvo del pantano.', 'Почётное звание из пыльных болот.', 150, 'common', 'title-dustborn', 'tag'],
        ['title_oathkeeper', 'title', 'profile_title', 'Eidwächter', 'Oathkeeper', 'Guardián del juramento', 'Хранитель клятвы', 'Für Hunter, die ihr Wort bis zur Extraktion halten.', 'For hunters who keep their word until extraction.', 'Para quienes cumplen su palabra hasta la extracción.', 'Для тех, кто держит слово до эвакуации.', 310, 'rare', 'title-oathkeeper', 'shield'],
        ['title_token_hunter', 'title', 'profile_title', 'Tokenjäger', 'Token Hunter', 'Cazador de fichas', 'Охотник за жетонами', 'Der Bounty gehört dir – zumindest auf dem Papier.', 'The bounty is yours, at least on paper.', 'La recompensa es tuya, al menos en papel.', 'Награда твоя — хотя бы на бумаге.', 420, 'epic', 'title-token-hunter', 'crosshair'],
        ['title_last_stand', 'title', 'profile_title', 'Letztes Gefecht', 'Last Stand', 'Última resistencia', 'Последний рубеж', 'Für den letzten Hunter, der noch steht.', 'For the last hunter standing.', 'Para el último cazador en pie.', 'Для последнего охотника на ногах.', 560, 'epic', 'title-last-stand', 'trophy'],
        ['title_banish_lord', 'title', 'profile_title', 'Bannfürst', 'Banish Lord', 'Señor del destierro', 'Повелитель изгнания', 'Legendärer Titel aus Rauch und Asche.', 'A legendary title forged in smoke and ash.', 'Un título legendario entre humo y cenizas.', 'Легендарный титул, закалённый дымом и пеплом.', 900, 'legendary', 'title-banish-lord', 'crown'],
        ['title_crowned_outcast', 'title', 'profile_title', 'Gekrönter Geächteter', 'Crowned Outcast', 'Proscrito coronado', 'Коронованный изгой', 'Eine Krone für alle, die ihren eigenen Weg gehen.', 'A crown for hunters who walk their own path.', 'Una corona para quienes siguen su camino.', 'Корона для тех, кто идёт своим путём.', 1040, 'legendary', 'title-crowned-outcast', 'crown'],
        ['profile_card_ashen_ledger', 'profile_card', 'profile_card', 'Aschenakte', 'Ashen Ledger', 'Registro de cenizas', 'Пепельное досье', 'Hunter-Karte im Stil einer alten Ermittlungsakte.', 'A hunter card styled as an old case file.', 'Una ficha de cazador como un expediente antiguo.', 'Карточка охотника в стиле старого досье.', 300, 'rare', 'ashen-ledger', 'identification-card'],
        ['profile_card_bayou_nocturne', 'profile_card', 'profile_card', 'Bayou-Nocturne', 'Bayou Nocturne', 'Nocturno del pantano', 'Ноктюрн болот', 'Dunkle Hunter-Karte mit mondblauem Licht.', 'A dark hunter card illuminated by moonlight.', 'Una ficha oscura iluminada por la luna.', 'Тёмная карточка охотника в лунном свете.', 480, 'epic', 'bayou-nocturne', 'identification-card'],
        ['profile_card_revenant_file', 'profile_card', 'profile_card', 'Wiedergängerakte', 'Revenant File', 'Archivo del retornado', 'Досье призрака', 'Verwitterte Akte mit geistergrünem Schimmer.', 'A weathered dossier with eerie green light.', 'Un expediente gastado con luz espectral verde.', 'Старинное досье с призрачным зелёным светом.', 420, 'epic', 'revenant-file', 'identification-card'],
        ['profile_card_blood_contract', 'profile_card', 'profile_card', 'Blutvertrag', 'Blood Contract', 'Contrato de sangre', 'Кровавый договор', 'Eine Hunter-Karte mit rotem Siegel und Blutglanz.', 'A hunter card bearing a crimson seal.', 'Una ficha de cazador con sello carmesí.', 'Карточка охотника с багровой печатью.', 790, 'legendary', 'blood-contract', 'identification-card'],
        ['profile_card_gilded_legend', 'profile_card', 'profile_card', 'Vergoldete Legende', 'Gilded Legend', 'Leyenda dorada', 'Позолоченная легенда', 'Eine goldene Hunter-Karte für die Legenden des Bayou.', 'A golden hunter card worthy of a bayou legend.', 'Una ficha dorada para leyendas del pantano.', 'Золотая карточка для легенд болот.', 1100, 'legendary', 'gilded-legend', 'crown'],
        ['profile_card_frost_witness', 'profile_card', 'profile_card', 'Frostzeuge', 'Frost Witness', 'Testigo helado', 'Ледяной свидетель', 'Eine stahlblaue Hunter-Karte mit kühlem Glühen.', 'A steel-blue hunter card with a cold shimmer.', 'Una ficha azul acero con brillo helado.', 'Стально-синяя карточка с холодным сиянием.', 570, 'epic', 'frost-witness', 'identification-card'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('crown_shop_items')) {
            return;
        }

        foreach (self::ITEMS as $index => $item) {
            [$key, $type, $slot, $de, $en, $es, $ru, $descDe,
                $descEn, $descEs, $descRu, $price, $rarity, $variant, $icon] = $item;

            // Never update existing prices or overwrite administrator changes.
            DB::table('crown_shop_items')->insertOrIgnore([
                'key' => $key,
                'type' => $type,
                'slot' => $slot,
                'name_de' => $de,
                'name_en' => $en,
                'description_de' => $descDe,
                'description_en' => $descEn,
                'price' => $price,
                'rarity' => $rarity,
                'icon' => $icon,
                'preview_class' => $variant,
                'is_active' => true,
                'is_limited' => false,
                'sort_order' => 300 + $index,
                'metadata' => json_encode([
                    'names' => ['es' => $es, 'ru' => $ru],
                    'descriptions' => ['es' => $descEs, 'ru' => $descRu],
                    'animated' => $type === 'avatar_frame',
                    'cosmetic_v3' => true,
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('crown_shop_items')) {
            return;
        }

        // Leave purchased or prestige-awarded cosmetics in place.
        foreach (self::ITEMS as [$key]) {
            $item = DB::table('crown_shop_items')->where('key', $key)->first();
            if ($item && ! DB::table('crown_inventory_items')->where('shop_item_id', $item->id)->exists()) {
                DB::table('crown_shop_items')->where('id', $item->id)->delete();
            }
        }
    }
};
