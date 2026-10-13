<?php

namespace App\Services;

/**
 * Source-reviewed, deliberately narrow Hunt fact snippets for editorial context.
 *
 * Not a live patch database or proof of continuing game state. Every fact has an
 * official Crytek citation and review date; absence of facts is intentional.
 */
final class AiCommunityVerifiedFacts
{
    /** @return array<string, array{id: string, fact: string, source_title: string, source_url: string, checked_on: string, caution: string}> */
    public static function catalog(): array
    {
        return [
            'sound_traps_animals' => [
                'id' => 'sound_traps_animals',
                'fact' => 'In Hunt, approaching crows/ravens and certain other animals can trigger noise that may alert other Hunters to a position. A crow sound alone does not prove an enemy is standing near the listener.',
                'source_title' => 'Crytek Hunt: Showdown Game Manual, Interacting with the World',
                'source_url' => 'https://www.huntshowdown.com/manual',
                'checked_on' => '2026-10-13',
                'caution' => 'General sound-trap guidance; the manual is older. Never claim a specific trigger, current sound range, or immediate enemy proximity.',
            ],
            'hunt_maps' => [
                'id' => 'hunt_maps',
                'fact' => 'The official Hunt: Showdown 1896 player guide names Stillwater Bayou, Lawson Delta, DeSalle and Mammon\'s Gulch as four Hunt maps.',
                'source_title' => 'Crytek Hunt: Showdown 1896 Player Guides, Breakdown of Maps',
                'source_url' => 'https://www.huntshowdown.com/player-guide/en_US',
                'checked_on' => '2026-10-13',
                'caution' => 'Do not claim that all four are available in the current mission rotation or that map features are unchanged.',
            ],
            'choke_burning' => [
                'id' => 'choke_burning',
                'fact' => 'Crytek describes entering a Choke Bomb cloud while burning as a way to stop the burning effect.',
                'source_title' => 'Crytek Hunt: Showdown 1896, Inferno is Live Now',
                'source_url' => 'https://www.huntshowdown.com/news/inferno-is-live-now',
                'checked_on' => '2026-10-13',
                'caution' => 'Do not invent Choke Bomb timers, remaining-effect UI, exact range, explosive damage or current balance changes.',
            ],
            'spatial_audio' => [
                'id' => 'spatial_audio',
                'fact' => 'Crytek describes CrySpatial as Hunt\'s directional 3D audio solution, helping locate whether sound is above, below, ahead or behind.',
                'source_title' => 'Crytek Hunt: Showdown 1896, A deep dive into 3D Audio',
                'source_url' => 'https://www.huntshowdown.com/news/dev-insight-a-deep-dive-into-3d-audio-in-hunt',
                'checked_on' => '2026-10-13',
                'caution' => 'General technical audio context only, not proof of present bug fixes, audible ranges or current audio behavior.',
            ],
        ];
    }

    /** @return list<array{id: string, fact: string, source_title: string, source_url: string, checked_on: string, caution: string}> */
    public static function forAngle(?string $angleId): array
    {
        $mapping = [
            'crow_audio' => ['sound_traps_animals'],
            'crows_are_loud' => ['sound_traps_animals'],
            'game_ambience' => ['spatial_audio'],
            'sound_discussion' => ['spatial_audio', 'sound_traps_animals'],
            'sound_identity' => ['spatial_audio'],
            'stillwater_vs_desalle' => ['hunt_maps'],
            'map_preference' => ['hunt_maps'],
            'lawson_mood' => ['hunt_maps'],
            'favorite_map_audio' => ['hunt_maps', 'spatial_audio'],
            'chokes_or_other_tools' => ['choke_burning'],
            'choke_discussion' => ['choke_burning'],
            'tool_slot_ui' => ['choke_burning'],
        ];
        $catalog = self::catalog();

        return array_values(array_map(
            static fn (string $id): array => $catalog[$id],
            $mapping[$angleId ?? ''] ?? []
        ));
    }
}
