<?php

namespace Tests\Unit;

use App\Models\AiCommunityCharacter;
use App\Services\AiCommunityDraftQualityGuard;
use App\Services\AiCommunityGenerationService;
use App\Services\AiCommunityVerifiedFacts;
use PHPUnit\Framework\TestCase;

class AiCommunityVerifiedFactsTest extends TestCase
{
    public function test_every_fact_is_traceable_to_a_specific_official_source(): void
    {
        foreach (AiCommunityVerifiedFacts::catalog() as $fact) {
            $this->assertSame('2026-10-13', $fact['checked_on']);
            $this->assertStringStartsWith('https://www.huntshowdown.com/', $fact['source_url']);
            $this->assertNotEmpty($fact['caution']);
            $this->assertNotEmpty($fact['fact']);
            $this->assertNotEmpty($fact['source_title']);
        }
        $this->assertCount(4, AiCommunityVerifiedFacts::catalog());
    }

    public function test_only_the_matched_fact_context_is_included(): void
    {
        $this->assertSame(['sound_traps_animals'], array_column(AiCommunityVerifiedFacts::forAngle('crow_audio'), 'id'));
        $this->assertSame(['choke_burning'], array_column(AiCommunityVerifiedFacts::forAngle('tool_slot_ui'), 'id'));
        $this->assertSame(['hunt_maps'], array_column(AiCommunityVerifiedFacts::forAngle('stillwater_vs_desalle'), 'id'));
        $this->assertSame([], AiCommunityVerifiedFacts::forAngle('unreviewed_patch_news'));
        $this->assertSame([], AiCommunityVerifiedFacts::forAngle(null));
    }

    public function test_actual_laravel_generation_prompt_receives_sources_without_recent_patch_claims(): void
    {
        $character = new AiCommunityCharacter([
            'name' => 'Tester', 'language' => 'de', 'persona_voice' => 'Kurz', 'topics' => 'Krähen',
        ]);
        $kind = 'sound_and_mood';
        $angle = ['id' => 'crow_audio', 'focus' => 'What do crows sound like?', 'terms' => ['Krähen']];
        $style = \App\Services\AiCommunityStyleLibrary::styles()['casual_one_liner'];
        $messages = (new AiCommunityGenerationService(new AiCommunityDraftQualityGuard))
            ->promptContext($character, [], $kind, $angle, $style);
        $context = json_decode($messages[1]['content'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['sound_traps_animals'], array_column($context['source_reviewed_facts'], 'id'));
        $this->assertStringContainsString('Only make factual mechanics or map claims grounded', $messages[0]['content']);
        $this->assertStringContainsString('NEVER treat them as a current patch', $messages[0]['content']);
        $this->assertSame('crow_audio', $context['editorial_angle']);
    }
}
