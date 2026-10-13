<?php

namespace Tests\Unit;

use App\Services\AiCommunityClaimRiskInspector;
use PHPUnit\Framework\TestCase;

class AiCommunityClaimRiskInspectorTest extends TestCase
{
    public function test_v9_crow_proximity_claim_receives_an_editorial_note(): void
    {
        $flags = AiCommunityClaimRiskInspector::inspect(
            'Die Krähen kreischen oft direkt, wenn man nah an Gegnern ist, ziemlich nützlich.'
        );

        $this->assertSame('crow_proximity', $flags[0]['code'] ?? null);
    }

    public function test_v9_choke_effect_timer_wish_receives_an_editorial_note(): void
    {
        $flags = AiCommunityClaimRiskInspector::inspect(
            'What if the tool selection UI showed active tool effects like Choke Bomb timers directly?'
        );

        $this->assertSame('choke_timer', $flags[0]['code'] ?? null);
    }

    public function test_an_unverified_patch_reference_is_flagged_for_source_review(): void
    {
        $flags = AiCommunityClaimRiskInspector::inspect('Patch 2.9.1 changes the tools again.');
        $this->assertSame('version_or_news', $flags[0]['code'] ?? null);
    }

    public function test_general_preferences_and_benign_game_terms_are_not_flagged(): void
    {
        foreach ([
            'Romero oder Vetterli für ein günstiges Loadout?',
            'Which map do you prefer, Stillwater Bayou or DeSalle?',
            'Die Krähen im Bayou sind ziemlich laut.',
            'Choke Bombs can extinguish burning bodies.',
            'Would you rather have a Choke Bomb or something else?',
            'I prefer the plain Hunter outfits.',
        ] as $post) {
            $this->assertSame([], AiCommunityClaimRiskInspector::inspect($post), $post);
        }
    }
}
