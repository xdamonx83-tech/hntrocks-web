<?php

namespace App\Services;

/**
 * Editorial formats, not scripted conversations or fictitious gameplay reports.
 * Avoids repeating the same kind of post from recently generated drafts.
 */
final class AiCommunityPostFormatSelector
{
    /** @return array<string, array{weight: int, label: string, instruction: string}> */
    public static function formats(): array
    {
        return [
            'open_question' => [
                'weight' => 3, 'label' => 'Community-Frage',
                'instruction' => 'Ask ONE direct, casual community question about a preference, favorite hunter, weapon feel, skin, sound design or game mode. No leading advice paragraph, no claims of personal experience.',
            ],
            'small_opinion' => [
                'weight' => 3, 'label' => 'Kurze Meinung',
                'instruction' => 'Give a brief, debatable opinion about a Hunt weapon, skin, map atmosphere or mechanic. One or two short conversational sentences. Do NOT ask a question or claim to have played a match.',
            ],
            'gear_choice' => [
                'weight' => 3, 'label' => 'Ausrüstungs-Vergleich',
                'instruction' => 'Put two plausible Hunt weapons, tools or consumables side by side with ONE concrete trade-off or short choice question. No invented damage figures, pricing, kit incompatibilities or personal loadout claim.',
            ],
            'hunter_styles' => [
                'weight' => 2, 'label' => 'Hunter & Skins',
                'instruction' => 'Talk casually about hunter appearance, legendary skins, atmosphere or personal style preferences as a discussion topic. No fake owned skins, screenshots or stories of past matches.',
            ],
            'map_chat' => [
                'weight' => 2, 'label' => 'Map-Gespräch',
                'instruction' => 'A short, everyday thought or question about Hunt maps, atmosphere, weather or environments. Avoid long rotation tutorials, fabricated current map changes, or claims from your own rounds.',
            ],
            'bayou_banter' => [
                'weight' => 2, 'label' => 'Bayou-Humor',
                'instruction' => 'One light, witty observation about common Hunt mechanics or audio cues; deadpan humor rather than inspirational writing. No fake firsthand events, quotes from invented teammates or insults.',
            ],
            'feature_wish' => [
                'weight' => 2, 'label' => 'Feature-Wunsch',
                'instruction' => 'Suggest ONE modest, hypothetical Hunt quality-of-life change, UI improvement, or feature and perhaps ask whether that would help. Do not pretend the change exists or is confirmed.',
            ],
            'sound_and_mood' => [
                'weight' => 2, 'label' => 'Sound & Atmosphäre',
                'instruction' => 'Mention one distinctive Hunt sound, audio-design choice or Bayou mood as a brief observation, without exaggeration, imaginary match scenes or detailed gameplay guide.',
            ],
        ];
    }

    /** @return list<string> */
    public static function kinds(): array
    {
        return array_keys(self::formats());
    }

    public static function instruction(string $kind): string
    {
        return self::formats()[$kind]['instruction'] ?? self::formats()['open_question']['instruction'];
    }

    /**
     * @param list<string|null> $recentKinds
     */
    public static function select(array $recentKinds): string
    {
        $formats = self::formats();
        $recent = array_values(array_filter($recentKinds, static fn ($kind) => is_string($kind) && isset($formats[$kind])));
        $weighted = [];
        $lastTwo = array_slice($recent, 0, 2);
        foreach ($formats as $kind => $options) {
            if (in_array($kind, $lastTwo, true)) {
                continue;
            }
            $uses = count(array_filter(array_slice($recent, 0, 12), static fn ($past) => $past === $kind));
            $weight = max(1, $options['weight'] - $uses);
            for ($i = 0; $i < $weight; $i++) {
                $weighted[] = $kind;
            }
        }
        // Eight formats ensure at least six remain eligible after excluding two.
        return $weighted[random_int(0, count($weighted) - 1)];
    }
}
