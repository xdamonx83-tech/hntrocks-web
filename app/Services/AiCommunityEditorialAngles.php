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
                ['id' => 'sound_identity', 'focus' => 'One compact opinion about a recognizable real sound cue in Hunt. Do not write a game-review sentence praising general sound design, or a vague emotional description; no claimed match.', 'terms' => ['Hunt', 'Sounddesign']],
                ['id' => 'romero_simplicity', 'focus' => 'One mild opinion about the appeal of straightforward equipment choices; Romero may be mentioned but no damage or price claim.', 'terms' => ['Romero']],
                ['id' => 'hunter_style_matters', 'focus' => 'A compact, concrete opinion on understated versus elaborate Hunter visual designs. Avoid vague praise that Hunter visuals add another layer to the game; no ownership claims.', 'terms' => ['Hunter', 'Skins']],
            ],
            'gear_choice' => [
                ['id' => 'romero_vetterli', 'focus' => 'Ask which gun people prefer as the MAIN weapon in a budget-friendly loadout, Romero shotgun or Vetterli rifle. Do not assert weapon stats or prices.', 'terms' => ['Romero', 'Vetterli']],
                ['id' => 'chokes_or_other_tools', 'focus' => 'Ask whether players prioritize a tool slot for Choke Bombs or a different tool. Choke Bomb is a TOOL, not a consumable. Chokes extinguish burning bodies/fires and can briefly obscure or discourage an area; do NOT describe them as explosive damage or generic crowd control.', 'terms' => ['Choke Bomb']],
                ['id' => 'ammo_box_consumable', 'focus' => 'Discuss Ammo Box as a consumable and ask when players consider taking it. Do not invent UI counters, mechanics, or quantities.', 'terms' => ['Ammo Box']],
            ],
            'hunter_styles' => [
                ['id' => 'cosmetic_favorite', 'focus' => 'Ask which Hunter skin has the strongest visual design; do not name a skin unless verified.', 'terms' => ['Hunter', 'Skins']],
                ['id' => 'simple_vs_flashy', 'focus' => 'Contrast understated and elaborate Hunter outfits as a matter of personal aesthetic taste.', 'terms' => ['Hunter']],
                ['id' => 'favorite_theme', 'focus' => 'Ask whether players prefer eerie, historical-looking or more eccentric hunter designs, without inventing named cosmetics.', 'terms' => ['Hunter']],
            ],
            'map_chat' => [
                ['id' => 'stillwater_vs_desalle', 'focus' => 'Ask which map players prefer, Stillwater Bayou or DeSalle, without describing specific invented terrain or compounds.', 'terms' => ['Stillwater Bayou', 'DeSalle']],
                ['id' => 'lawson_mood', 'focus' => 'A plain question about liking Lawson Delta compared with other maps, or a very short, grounded subjective preference. Avoid poetic descriptions of silence, the environment waiting, fog, or fictional weather. Do not invent terrain specifics.', 'terms' => ['Lawson Delta']],
                ['id' => 'favorite_map_audio', 'focus' => 'Ask whether players choose favorite maps for atmosphere or practical gameplay, without claiming a certain map always has a feature.', 'terms' => ['Hunt']],
            ],
            'bayou_banter' => [
                ['id' => 'crows_are_loud', 'focus' => 'A dry joke about how crows alert others, no fake match or invented teammate.', 'terms' => ['Krähen', 'crows']],
                ['id' => 'choke_discussion', 'focus' => 'A light one-line joke about how seriously loadout debates can get over a Choke Bomb, no false mechanics.', 'terms' => ['Choke Bomb']],
                ['id' => 'romero_fan_club', 'focus' => 'A wry comment about how passionate weapon fans can be when arguing Romero against other weapons; no fake other user quotes.', 'terms' => ['Romero']],
            ],
            'feature_wish' => [
                ['id' => 'loadout_qol', 'focus' => 'A HYPOTHETICAL wish for a more convenient comparison between loadout presets. Do not assert the game currently lacks saved loadouts, preset switching, or UI features; no announcement claims.', 'terms' => ['Loadout']],
                ['id' => 'favorite_skin_ui', 'focus' => 'A HYPOTHETICAL cosmetic sorting/filtering UI improvement, not a claim that Hunt lacks it today.', 'terms' => ['Hunter-Skins']],
                ['id' => 'tool_slot_ui', 'focus' => 'Ask whether users would appreciate a hypothetical small improvement to the existing tool-selection UI. Do not claim the game has no swapping, shortcut, presets, or counters; avoid inventing features.', 'terms' => ['Tools', 'Choke Bomb']],
            ],
            'sound_and_mood' => [
                ['id' => 'crow_audio', 'focus' => 'One brief grounded statement that crow alerts are noticeable in Hunt, without calling them spies, monsters, a symphony, or dramatic metaphors. No claim about any specific round.', 'terms' => ['Krähen', 'crows']],
                ['id' => 'game_ambience', 'focus' => 'One specific real audio element as the subject of a compact observation or preference. Avoid generic 'dense atmosphere', 'eerie vibe', nature personification, or invented audio/weather.', 'terms' => ['Hunt']],
                ['id' => 'sound_discussion', 'focus' => 'Ask ONE direct question about a familiar sound cue, optionally comparing two, without listing three atmospheric examples plus a redundant second question.', 'terms' => ['Hunt']],
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
