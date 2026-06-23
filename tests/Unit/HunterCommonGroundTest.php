<?php

namespace Tests\Unit;

use App\Models\LiveLobby;
use App\Models\UserProfile;
use App\Support\HunterCommonGround;
use PHPUnit\Framework\TestCase;

class HunterCommonGroundTest extends TestCase
{
    public function test_pc_and_console_crossplay_are_matched(): void
    {
        $pc = HunterCommonGround::between(
            new UserProfile(['platform' => 'pc']),
            new LiveLobby(['platform' => 'pc', 'crossplay_pool' => 'pc']),
            null,
        );
        $console = HunterCommonGround::between(
            new UserProfile(['platform' => 'xbox']),
            new LiveLobby(['platform' => 'playstation', 'crossplay_pool' => 'console']),
            null,
        );

        $this->assertSame('pc', $pc['items'][0]['value']);
        $this->assertSame('console', $console['items'][0]['value']);
    }

    public function test_flexible_mode_matches_duo_and_trio(): void
    {
        foreach (['duo', 'trio'] as $mode) {
            $result = HunterCommonGround::between(
                new UserProfile(['hunter_dna' => ['preferred_mode' => 'flexible']]),
                new LiveLobby(['mode' => $mode]),
                null,
            );

            $this->assertSame($mode, $result['items'][0]['value']);
        }
    }

    public function test_goal_intersection_is_deduplicated_and_limited_to_three_items(): void
    {
        $result = HunterCommonGround::between(
            new UserProfile(['hunter_dna' => ['goals' => ['boss', 'pvp', 'boss', 'learn', 'events']]]),
            new LiveLobby(),
            new UserProfile(['hunter_dna' => ['goals' => ['events', 'boss', 'learn', 'pvp']]]),
        );

        $this->assertSame(3, $result['score']);
        $this->assertSame(3, $result['max_score']);
        $this->assertSame(['boss', 'pvp', 'learn'], array_column($result['items'], 'value'));
    }

    public function test_missing_creator_profile_excludes_creator_traits_from_score(): void
    {
        $result = HunterCommonGround::between(
            new UserProfile([
                'platform' => 'pc',
                'hunt_role' => 'support',
                'hunter_dna' => [
                    'experience' => 'experienced',
                    'temper' => 'focused',
                    'goals' => ['boss'],
                    'mentor' => true,
                ],
            ]),
            new LiveLobby(['platform' => 'pc', 'crossplay_pool' => 'pc']),
            null,
        );

        $this->assertSame(1, $result['score']);
        $this->assertSame(1, $result['max_score']);
        $this->assertSame(['platform'], array_column($result['items'], 'key'));
    }
}
