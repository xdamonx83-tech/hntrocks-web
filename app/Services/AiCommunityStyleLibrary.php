<?php

namespace App\Services;

/** Writing structures only: no sampled player text, screenshots, news, or media. */
final class AiCommunityStyleLibrary
{
    /** @return array<string, array{id: string, label: string, languages: list<string>, syntax: string, target_length: string, length_band: string, ending: string, source_requirement: string, post_kinds: list<string>, weight: int}> */
    public static function styles(): array
    {
        return [
            'headline_question' => [
                'id' => 'headline_question', 'label' => 'Kurze Schlagzeilenfrage', 'languages' => ['de', 'en'],
                'syntax' => 'Write a direct, self-contained headline-like question. No preamble, invented incident, or implied screenshot.',
                'target_length' => '35–85 characters', 'length_band' => 'short', 'ending' => 'question', 'source_requirement' => 'none',
                'post_kinds' => ['open_question', 'gear_choice', 'hunter_styles', 'map_chat', 'sound_and_mood'], 'weight' => 3,
            ],
            'german_discussion' => [
                'id' => 'german_discussion', 'label' => 'Gesprächseinstieg auf Deutsch', 'languages' => ['de'],
                'syntax' => 'Use one to three conversational German sentences about the editorial focus. End with one concrete discussion question; no personal play, group, voice chat, or Discord claim.',
                'target_length' => '100–230 characters', 'length_band' => 'long', 'ending' => 'question', 'source_requirement' => 'none',
                'post_kinds' => ['open_question', 'gear_choice', 'hunter_styles', 'map_chat', 'feature_wish', 'sound_and_mood'], 'weight' => 2,
            ],
            'casual_one_liner' => [
                'id' => 'casual_one_liner', 'label' => 'Lockerer Einzeiler', 'languages' => ['de', 'en'],
                'syntax' => 'Write one very short, casual observation or reaction as a statement. No question, greeting, punchline setup, personal anecdote, or supposed image.',
                'target_length' => '20–65 characters', 'length_band' => 'short', 'ending' => 'statement', 'source_requirement' => 'none',
                'post_kinds' => ['small_opinion', 'bayou_banter', 'sound_and_mood', 'hunter_styles', 'map_chat'], 'weight' => 3,
            ],
            'preference_question' => [
                'id' => 'preference_question', 'label' => 'Direkte Präferenzfrage', 'languages' => ['de', 'en'],
                'syntax' => 'Ask one plain preference question with concrete options from the editorial focus when available. Do not add an answer or assert a best choice.',
                'target_length' => '55–125 characters', 'length_band' => 'medium', 'ending' => 'question', 'source_requirement' => 'none',
                'post_kinds' => ['open_question', 'gear_choice', 'hunter_styles', 'map_chat', 'sound_and_mood'], 'weight' => 3,
            ],
            'conversational_question' => [
                'id' => 'conversational_question', 'label' => 'Gesprächsfrage', 'languages' => ['de', 'en'],
                'syntax' => 'Use one brief context clause about the editorial focus, followed by one concrete question. No anecdote, padding, or claimed observation from a match.',
                'target_length' => '100–190 characters', 'length_band' => 'long', 'ending' => 'question', 'source_requirement' => 'none',
                'post_kinds' => ['open_question', 'gear_choice', 'hunter_styles', 'map_chat', 'sound_and_mood'], 'weight' => 2,
            ],
            'mild_opinion' => [
                'id' => 'mild_opinion', 'label' => 'Milde Meinung', 'languages' => ['de', 'en'],
                'syntax' => 'State one modest, subjective take in one or two natural sentences. No question, sweeping claim, fake consensus, or personal match evidence.',
                'target_length' => '75–150 characters', 'length_band' => 'medium', 'ending' => 'statement', 'source_requirement' => 'none',
                'post_kinds' => ['small_opinion', 'bayou_banter', 'sound_and_mood', 'hunter_styles', 'map_chat'], 'weight' => 3,
            ],
            'hypothetical_feedback' => [
                'id' => 'hypothetical_feedback', 'label' => 'Hypothetische Feedbackfrage', 'languages' => ['de', 'en'],
                'syntax' => 'Propose one clearly hypothetical, specific improvement tied to the editorial focus, then ask whether it would help. Never imply it exists or is announced.',
                'target_length' => '120–260 characters', 'length_band' => 'long', 'ending' => 'question', 'source_requirement' => 'none',
                'post_kinds' => ['feature_wish'], 'weight' => 3,
            ],
            'concise_wish' => [
                'id' => 'concise_wish', 'label' => 'Kurzer Wunsch', 'languages' => ['de', 'en'],
                'syntax' => 'State one small, explicitly hypothetical improvement as a casual wish. End as a statement; no claim about the current UI or an announced update.',
                'target_length' => '45–95 characters', 'length_band' => 'short', 'ending' => 'statement', 'source_requirement' => 'none',
                'post_kinds' => ['feature_wish'], 'weight' => 3,
            ],
            'wish_question' => [
                'id' => 'wish_question', 'label' => 'Kurze Wunschfrage', 'languages' => ['de', 'en'],
                'syntax' => 'Ask one compact what-if question about the hypothetical improvement in the editorial focus. No preamble or implication that the change exists.',
                'target_length' => '65–125 characters', 'length_band' => 'medium', 'ending' => 'question', 'source_requirement' => 'none',
                'post_kinds' => ['feature_wish'], 'weight' => 2,
            ],
            'concise_contrast' => [
                'id' => 'concise_contrast', 'label' => 'Knappes Gegenüberstellen', 'languages' => ['de', 'en'],
                'syntax' => 'Contrast two aesthetic or thematic sides from the editorial focus in one compact statement. No factual gear trade-off or question.',
                'target_length' => '65–125 characters', 'length_band' => 'medium', 'ending' => 'statement', 'source_requirement' => 'none',
                'post_kinds' => ['small_opinion', 'hunter_styles', 'map_chat', 'sound_and_mood'], 'weight' => 2,
            ],
            'dry_observation' => [
                'id' => 'dry_observation', 'label' => 'Trockene Beobachtung', 'languages' => ['de', 'en'],
                'syntax' => 'Make one understated, specific observation about the editorial focus. End as a statement; no staged scene, quotation, or claimed experience.',
                'target_length' => '85–175 characters', 'length_band' => 'medium', 'ending' => 'statement', 'source_requirement' => 'none',
                'post_kinds' => ['small_opinion', 'bayou_banter', 'sound_and_mood', 'hunter_styles', 'map_chat'], 'weight' => 2,
            ],
        ];
    }

    /** @param list<string|null> $recentKeys */
    public static function select(string $language, string $postKind, array $recentKeys = [], ?string $angleId = null): array
    {
        $styles = self::styles();
        $eligible = array_filter($styles, static fn (array $style) =>
            $style['source_requirement'] === 'none'
            && in_array($language, $style['languages'], true)
            && in_array($postKind, $style['post_kinds'], true));
        $kindEligible = $eligible;
        $questionAngles = ['favorite_hunter_look', 'one_weapon_choice', 'map_preference', 'romero_vetterli', 'chokes_or_other_tools', 'ammo_box_consumable', 'cosmetic_favorite', 'favorite_theme', 'stillwater_vs_desalle', 'favorite_map_audio', 'sound_discussion'];
        $statementAngles = ['sound_identity', 'romero_simplicity', 'hunter_style_matters', 'simple_vs_flashy', 'crows_are_loud', 'choke_discussion', 'romero_fan_club', 'crow_audio', 'game_ambience'];
        if (in_array($angleId, $questionAngles, true)) {
            $eligible = array_filter($eligible, static fn (array $style) => $style['ending'] === 'question');
        } elseif (in_array($angleId, $statementAngles, true)) {
            $eligible = array_filter($eligible, static fn (array $style) => $style['ending'] === 'statement');
        }
        if ($eligible === []) {
            $eligible = $kindEligible;
        }
        $recent = array_values(array_filter($recentKeys, static fn ($key) => is_string($key) && isset($styles[$key])));
        $withoutLastTwo = array_diff_key($eligible, array_flip(array_slice($recent, 0, 2)));
        if ($withoutLastTwo !== []) {
            $eligible = $withoutLastTwo;
        }
        $weighted = [];
        foreach ($eligible as $key => $style) {
            $weight = $style['weight'] + 4;
            foreach (array_slice($recent, 0, 2) as $pastKey) {
                if ($styles[$pastKey]['length_band'] === $style['length_band']) {
                    $weight -= 2;
                }
                if ($styles[$pastKey]['ending'] === $style['ending']) {
                    $weight -= 2;
                }
            }
            for ($i = 0; $i < max(1, $weight); $i++) {
                $weighted[] = $key;
            }
        }
        return $styles[$weighted[random_int(0, count($weighted) - 1)]];
    }

    public static function label(?string $key): ?string
    {
        return self::styles()[$key]['label'] ?? null;
    }
}
