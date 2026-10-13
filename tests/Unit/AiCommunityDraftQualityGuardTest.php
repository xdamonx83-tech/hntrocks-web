<?php

namespace Tests\Unit;

use App\Models\AiCommunityCharacter;
use App\Services\AiCommunityDraftQualityGuard;
use App\Services\AiCommunityEditorialAngles;
use App\Services\AiCommunityGenerationService;
use App\Services\AiCommunityPostFormatSelector;
use App\Services\AiCommunityStyleLibrary;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AiCommunityDraftQualityGuardTest extends TestCase
{
    public function test_prompt_sets_content_rules_and_recent_negative_examples(): void
    {
        $character = new AiCommunityCharacter(['language' => 'de', 'persona_voice' => 'Knapp', 'topics' => 'Rotation']);
        $messages = (new AiCommunityGenerationService(new AiCommunityDraftQualityGuard))
            ->promptContext($character, ['Alte Frage über Deckung']);

        $this->assertStringContainsString('20–300 character range', $messages[0]['content']);
        $this->assertStringContainsString('near-duplicates', $messages[0]['content']);
        $this->assertStringContainsString('No polished motivational sayings', $messages[0]['content']);
        $this->assertSame(['Alte Frage über Deckung'], json_decode($messages[1]['content'], true)['recent_posts_to_avoid']);
    }

    public function test_style_library_has_distinct_safe_lengths_and_avoids_recent_styles(): void
    {
        $styles = AiCommunityStyleLibrary::styles();
        $this->assertGreaterThanOrEqual(6, count($styles));
        $bands = array_values(array_unique(array_column($styles, 'length_band')));
        sort($bands);
        $this->assertSame(['long', 'medium', 'short'], $bands);
        $this->assertContains('question', array_column($styles, 'ending'));
        $this->assertContains('statement', array_column($styles, 'ending'));
        foreach ($styles as $key => $style) {
            $this->assertSame($key, $style['id']);
            $this->assertLessThanOrEqual(48, strlen($key));
            $this->assertSame('none', $style['source_requirement']);
            $this->assertNotEmpty($style['syntax']);
        }
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $style = AiCommunityStyleLibrary::select('en', 'open_question', ['headline_question', 'preference_question'], 'one_weapon_choice');
            $this->assertNotContains($style['id'], ['headline_question', 'preference_question']);
            $this->assertSame('question', $style['ending']);
            $this->assertSame('none', $style['source_requirement']);
            $this->assertContains('en', $style['languages']);
            $this->assertNotSame('german_discussion', $style['id']);
        }
        $this->assertSame('hypothetical_feedback', AiCommunityStyleLibrary::select('en', 'feature_wish', ['concise_wish', 'wish_question'], 'loadout_qol')['id']);
    }

    public function test_prompt_contains_only_selected_style_guidance_and_key(): void
    {
        $character = new AiCommunityCharacter(['language' => 'de', 'persona_voice' => 'Kurz', 'topics' => 'Tools']);
        $style = AiCommunityStyleLibrary::styles()['headline_question'];
        $angle = AiCommunityEditorialAngles::catalog()['gear_choice'][0];
        $messages = (new AiCommunityGenerationService(new AiCommunityDraftQualityGuard))
            ->promptContext($character, [], 'gear_choice', $angle, $style);
        $this->assertSame('headline_question', json_decode($messages[1]['content'], true)['style_key']);
        $this->assertStringContainsString($style['syntax'], $messages[0]['content']);
        $this->assertStringNotContainsString(AiCommunityStyleLibrary::styles()['mild_opinion']['syntax'], $messages[0]['content']);
    }

    public function test_post_formats_are_distinct_and_recent_kinds_are_avoided(): void
    {
        $formats = AiCommunityPostFormatSelector::formats();
        $this->assertGreaterThanOrEqual(7, count($formats));
        $this->assertContains('open_question', array_keys($formats));
        $this->assertContains('hunter_styles', array_keys($formats));
        $this->assertContains('feature_wish', array_keys($formats));

        for ($run = 0; $run < 40; $run++) {
            $picked = AiCommunityPostFormatSelector::select(['small_opinion', 'open_question']);
            $this->assertNotContains($picked, ['small_opinion', 'open_question']);
        }
        $character = new AiCommunityCharacter(['language' => 'en', 'persona_voice' => 'Dry', 'topics' => 'Maps']);
        $messages = (new AiCommunityGenerationService(new AiCommunityDraftQualityGuard))
            ->promptContext($character, [], 'hunter_styles');
        $data = json_decode($messages[1]['content'], true);
        $this->assertSame('hunter_styles', $data['post_format']);
        $this->assertStringContainsString('hunter appearance', $messages[0]['content']);
    }

    public function test_reviewed_editorial_angles_avoid_recent_selections(): void
    {
        $catalog = AiCommunityEditorialAngles::catalog();
        $this->assertCount(8, $catalog);
        $this->assertCount(3, $catalog['gear_choice']);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $angle = AiCommunityEditorialAngles::select('gear_choice', ['romero_vetterli', 'chokes_or_other_tools']);
            $this->assertSame('ammo_box_consumable', $angle['id']);
            $this->assertContains('Ammo Box', $angle['terms']);
        }

        $character = new AiCommunityCharacter(['language' => 'de', 'persona_voice' => 'Kurz', 'topics' => 'Tools']);
        $messages = (new AiCommunityGenerationService(new AiCommunityDraftQualityGuard))
            ->promptContext($character, [], 'gear_choice', $angle);
        $payload = json_decode($messages[1]['content'], true);
        $this->assertSame('ammo_box_consumable', $payload['editorial_angle']);
        $this->assertSame($angle['focus'], $payload['editorial_focus']);
        $this->assertSame(['Ammo Box'], $payload['allowed_game_terms']);
    }

    public function test_v8_editorial_prompt_avoids_generic_mood_posts_and_double_questions(): void
    {
        $character = new AiCommunityCharacter(['language' => 'de', 'persona_voice' => 'Kurz', 'topics' => 'Hunt']);
        $kind = 'sound_and_mood';
        $angle = AiCommunityEditorialAngles::catalog()[$kind][1];
        $style = AiCommunityStyleLibrary::styles()['casual_one_liner'];
        $messages = (new AiCommunityGenerationService(new AiCommunityDraftQualityGuard))
            ->promptContext($character, [], $kind, $angle, $style);

        $this->assertStringContainsString('one concrete reason someone would post', $messages[0]['content']);
        $this->assertStringContainsString('at most ONE question mark', $messages[0]['content']);
        $this->assertStringContainsString('not a translated product review', $messages[0]['content']);
        $this->assertStringContainsString('Avoid generic', $angle['focus']);
        $this->assertSame(1, AiCommunityPostFormatSelector::formats()['sound_and_mood']['weight']);
        $this->assertSame(1, AiCommunityPostFormatSelector::formats()['bayou_banter']['weight']);
    }

    public function test_repeated_questions_are_rejected_but_single_natural_question_is_allowed(): void
    {
        $guard = new AiCommunityDraftQualityGuard;
        try {
            $guard->validate('Romero oder Vetterli? Was meint ihr?', [], 'question');
            $this->fail('Expected repeated question to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('generation', $e->errors());
        }

        $guard->validate('Romero oder Vetterli für ein günstiges Loadout?', [], 'question');
        $guard->validate('Die Romero hat etwas angenehm Einfaches.', [], 'statement');
    }

    public function test_v5_label_leakage_and_structural_output_are_rejected(): void
    {
        $guard = new AiCommunityDraftQualityGuard;

        foreach ([
            ['Die Krähen sind das Warnsystem im Sumpf. statement', 'statement'],
            ['Die Romero-Diskussionen sind manchmal lauter als das Spiel. statement', 'statement'],
            ['The crows are louder than my loadout. statement', 'statement'],
            ['Would a loadout comparison be useful? statement', 'statement'],
            ['Do you prefer the Romero?', 'statement'],
            ['A good rifle choice is always personal.', 'question'],
            ['Do you take Choke Bombs for crowd control?', 'question'],
        ] as [$body, $ending]) {
            try {
                $guard->validate($body, [], $ending);
                $this->fail('Expected contaminated or style-incompatible draft to be rejected.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('generation', $e->errors());
            }
        }

        $guard->validate('Romero oder Vetterli?', [], 'question');
        $guard->validate('Die Atmosphäre in Hunt bleibt ziemlich eigen.', [], 'statement');
    }

    public function test_model_prompt_does_not_emit_a_literal_ending_label(): void
    {
        $character = new AiCommunityCharacter(['language' => 'en', 'persona_voice' => 'Dry', 'topics' => 'Sound']);
        $angle = AiCommunityEditorialAngles::catalog()['small_opinion'][0];
        $style = AiCommunityStyleLibrary::styles()['casual_one_liner'];
        $messages = (new AiCommunityGenerationService(new AiCommunityDraftQualityGuard))
            ->promptContext($character, [], 'small_opinion', $angle, $style);

        $this->assertStringContainsString('Never add editorial labels', $messages[0]['content']);
        $this->assertStringContainsString('Finish with ordinary punctuation', $messages[0]['content']);
        $this->assertStringNotContainsString('End as a statement.', $messages[0]['content']);
    }

    public function test_near_duplicate_and_repeated_opening_are_rejected(): void
    {
        $guard = new AiCommunityDraftQualityGuard;
        foreach ([
            'Quiet routes across open ground need cover and patience, not speed.',
            'Quiet routes across open ground need cover and a second exit plan.',
        ] as $body) {
            try {
                $guard->validate($body, ['Quiet routes across open ground need cover and patience.']);
                $this->fail('Expected repeated draft to be rejected.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('generation', $e->errors());
            }
        }
    }

    public function test_invalid_output_is_rejected_but_hypothetical_first_person_is_allowed(): void
    {
        $guard = new AiCommunityDraftQualityGuard;
        $guard->validate("I'd rotate left if the open path has no cover.", []);
        $guard->validate('Ich würde erst die Deckung prüfen, bevor ich quere.', []);

        foreach (['', str_repeat('a', 701), 'Visit https://example.com', 'I won my last match.', 'Wir haben gestern gespielt.', 'Ein paar Laschen sind ein Pflichtkauf.', 'Immer im Hollywood-Stil nach vorne.', 'Rückzug ist kein Zeichen von Schwäche.', 'Der 1896 ist die beste Schrotflinte.', 'The 1896 shotgun is obviously overpowered.'] as $body) {
            try {
                $guard->validate($body, []);
                $this->fail('Expected invalid output to be rejected.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('generation', $e->errors());
            }
        }
    }
}
