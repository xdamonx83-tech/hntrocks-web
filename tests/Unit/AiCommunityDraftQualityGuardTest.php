<?php

namespace Tests\Unit;

use App\Models\AiCommunityCharacter;
use App\Services\AiCommunityDraftQualityGuard;
use App\Services\AiCommunityGenerationService;
use App\Services\AiCommunityPostFormatSelector;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AiCommunityDraftQualityGuardTest extends TestCase
{
    public function test_prompt_sets_content_rules_and_recent_negative_examples(): void
    {
        $character = new AiCommunityCharacter(['language' => 'de', 'persona_voice' => 'Knapp', 'topics' => 'Rotation']);
        $messages = (new AiCommunityGenerationService(new AiCommunityDraftQualityGuard))
            ->promptContext($character, ['Alte Frage über Deckung']);

        $this->assertStringContainsString('1-2 brief sentences', $messages[0]['content']);
        $this->assertStringContainsString('near-duplicates', $messages[0]['content']);
        $this->assertStringContainsString('No polished motivational sayings', $messages[0]['content']);
        $this->assertSame(['Alte Frage über Deckung'], json_decode($messages[1]['content'], true)['recent_posts_to_avoid']);
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

        foreach (['', str_repeat('a', 701), 'Visit https://example.com', 'I won my last match.', 'Wir haben gestern gespielt.', 'Ein paar Laschen sind ein Pflichtkauf.', 'Immer im Hollywood-Stil nach vorne.', 'Rückzug ist kein Zeichen von Schwäche.'] as $body) {
            try {
                $guard->validate($body, []);
                $this->fail('Expected invalid output to be rejected.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('generation', $e->errors());
            }
        }
    }
}
