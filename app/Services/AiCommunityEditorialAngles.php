<?php

namespace App\Services;

/**
 * Hand-curated conversation prompts, NOT claims of player experience.
 * The topic supplies a narrow angle and known names, never current patch facts.
 */
final class AiCommunityEditorialAngles
{
    /** @return array<string, list<array{id: string, focus: string, terms: list<string>}>> */
    public static function catalog(): array
    {
        return [
            'open_question' => [
                ['id' => 'favorite_hunter_look', 'focus' => 'Ask which existing Hunter or Hunter skin design people like most, purely for its look.', 'terms' => ['Hunter', 'Hunter-Skin']],
                ['id' => 'one_weapon_choice', 'focus' => 'Ask which ONE weapon people would choose for a casual loadout; do not imply it is the best statistically.', 'terms' => ['Romero', 'Vetterli', 'Mosin']],
                ['id' => 'map_preference', 'focus' => 'Ask which Hunt map people enjoy aesthetically without describing invented locations, weather, geometry or current rotation.', 'terms' => ['Stillwater Bayou', 'Lawson Delta', 'DeSalle']],
            ],
            'small_opinion' => [
                ['id' => 'sound_identity', 'focus' => 'One short subjective opinion about how audio contributes to the distinctive atmosphere of Hunt, no claimed match.', 'terms' => ['Hunt', 'Sounddesign']],
                ['id' => 'romero_simplicity', 'focus' => 'One mild opinion about the appeal of straightforward equipment choices; Romero may be mentioned but no damage or price claim.', 'terms' => ['Romero']],
                ['id' => 'hunter_style_matters', 'focus' => 'One short opinion that the visual style of a Hunter is a fun reason to discuss cosmetics; no skin ownership claim.', 'terms' => ['Hunter', 'Skins']],
            ],
            'gear_choice' => [
                ['id' => 'romero_vetterli', 'focus' => 'Ask which gun people prefer as the MAIN weapon in a budget-friendly loadout, Romero shotgun or Vetterli rifle. Do not assert weapon stats or prices.', 'terms' => ['Romero', 'Vetterli']],
                ['id' => 'chokes_or_other_tools', 'focus' => 'Ask if players tend to reserve a tool slot for Choke Bombs or prefer another tool. Choke Bomb is a TOOL, not a consumable.', 'terms' => ['Choke Bomb']],
                ['id' => 'ammo_box_consumable', 'focus' => 'Discuss Ammo Box as a consumable and ask when players consider taking it. Do not invent UI counters, mechanics, or quantities.', 'terms' => ['Ammo Box']],
            ],
            'hunter_styles' => [
                ['id' => 'cosmetic_favorite', 'focus' => 'Ask which Hunter skin has the strongest visual design; do not name a skin unless verified.', 'terms' => ['Hunter', 'Skins']],
                ['id' => 'simple_vs_flashy', 'focus' => 'Contrast understated and elaborate Hunter outfits as a matter of personal aesthetic taste.', 'terms' => ['Hunter']],
                ['id' => 'favorite_theme', 'focus' => 'Ask whether players prefer eerie, historical-looking or more eccentric hunter designs, without inventing named cosmetics.', 'terms' => ['Hunter']],
            ],
            'map_chat' => [
                ['id' => 'stillwater_vs_desalle', 'focus' => 'Ask which map players prefer, Stillwater Bayou or DeSalle, without describing specific invented terrain or compounds.', 'terms' => ['Stillwater Bayou', 'DeSalle']],
                ['id' => 'lawson_mood', 'focus' => 'A simple opinion or question about the atmosphere of Lawson Delta; do NOT compare Lawson to itself or invent terrain specifics.', 'terms' => ['Lawson Delta']],
                ['id' => 'favorite_map_audio', 'focus' => 'Ask whether players choose favorite maps for atmosphere or practical gameplay, without claiming a certain map always has a feature.', 'terms' => ['Hunt']],
            ],
            'bayou_banter' => [
                ['id' => 'crows_are_loud', 'focus' => 'A dry joke about how crows alert others, no fake match or invented teammate.', 'terms' => ['Krähen', 'crows']],
                ['id' => 'choke_discussion', 'focus' => 'A light one-line joke about how seriously loadout debates can get over a Choke Bomb, no false mechanics.', 'terms' => ['Choke Bomb']],
                ['id' => 'romero_fan_club', 'focus' => 'A wry comment about how passionate weapon fans can be when arguing Romero against other weapons; no fake other user quotes.', 'terms' => ['Romero']],
            ],
            'feature_wish' => [
                ['id' => 'loadout_qol', 'focus' => 'A HYPOTHETICAL wish for easier loadout comparison or saving. Make clear it is a suggestion; do not say a feature already exists or is announced.', 'terms' => ['Loadout']],
                ['id' => 'favorite_skin_ui', 'focus' => 'A HYPOTHETICAL cosmetic sorting/filtering UI improvement, not a claim that Hunt lacks it today.', 'terms' => ['Hunter-Skins']],
                ['id' => 'tool_slot_ui', 'focus' => 'A HYPOTHETICAL convenience improvement for selecting tools, not an invented actual tool mechanic.', 'terms' => ['Tools', 'Choke Bomb']],
            ],
            'sound_and_mood' => [
                ['id' => 'crow_audio', 'focus' => 'One grounded observation that the sound of crows is a recognizable Hunt audio cue; no claims about specific rounds.', 'terms' => ['Krähen', 'crows']],
                ['id' => 'game_ambience', 'focus' => 'One short opinion on Hunt audio atmosphere in general, no fictional sound source at extraction points.', 'terms' => ['Hunt']],
                ['id' => 'sound_discussion', 'focus' => 'Ask which kind of recognizable Hunt sound players consider most distinctive; no invented interactions.', 'terms' => ['Hunt']],
            ],
        ];
    }

    /** @return array{id: string, focus: string, terms: list<string>} */
    public static function select(string $kind, array $recentIds = []): array
    {
        $choices = self::catalog()[$kind] ?? self::catalog()['open_question'];
        $available = array_values(array_filter(
            $choices,
            static fn (array $item): bool => ! in_array($item['id'], array_slice($recentIds, 0, 20), true)
        ));
        // Eventually all topics will have been seen. Reuse only after exhausting
        // the local pool; the existing text similarity guard remains active.
        if ($available === []) {
            $available = $choices;
        }

        return $available[random_int(0, count($available) - 1)];
    }
}
