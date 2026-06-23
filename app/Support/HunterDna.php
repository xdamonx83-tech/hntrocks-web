<?php

namespace App\Support;

use App\Models\UserProfile;

class HunterDna
{
    private const PROFILE_TRAITS = [
        'platform',
        'region',
        'language',
        'playstyle',
        'hunt_role',
    ];

    private const DNA_TRAITS = [
        'voice',
        'preferred_mode',
        'experience',
        'temper',
        'goals',
        'mentor',
    ];

    private const TOTAL_TRAITS = 11;

    public static function normalize(?array $hunterDna): array
    {
        $normalized = [];

        foreach (['voice', 'preferred_mode', 'experience', 'temper'] as $key) {
            $value = trim((string) ($hunterDna[$key] ?? ''));
            if ($value !== '') {
                $normalized[$key] = $value;
            }
        }

        $goals = [];
        foreach ((array) ($hunterDna['goals'] ?? []) as $goal) {
            $goal = trim((string) $goal);
            if ($goal !== '' && ! in_array($goal, $goals, true)) {
                $goals[] = $goal;
            }
        }
        if ($goals !== []) {
            $normalized['goals'] = $goals;
        }

        if (array_key_exists('mentor', $hunterDna ?? []) && $hunterDna['mentor'] !== null) {
            $normalized['mentor'] = (bool) $hunterDna['mentor'];
        }

        return $normalized;
    }

    public static function payload(?array $hunterDna): array
    {
        $hunterDna = self::normalize($hunterDna);

        if ($hunterDna === []) {
            return [];
        }

        return [
            'voice' => $hunterDna['voice'] ?? null,
            'preferred_mode' => $hunterDna['preferred_mode'] ?? null,
            'experience' => $hunterDna['experience'] ?? null,
            'temper' => $hunterDna['temper'] ?? null,
            'goals' => $hunterDna['goals'] ?? [],
            'mentor' => (bool) ($hunterDna['mentor'] ?? false),
        ];
    }

    public static function isComplete(?array $hunterDna): bool
    {
        return self::dnaTraitsCount(self::normalize($hunterDna)) >= 4;
    }

    public static function summary(?UserProfile $profile): array
    {
        $traitsCount = 0;

        foreach (self::PROFILE_TRAITS as $trait) {
            if (trim((string) ($profile?->{$trait} ?? '')) !== '') {
                $traitsCount++;
            }
        }

        $hunterDna = self::normalize($profile?->hunter_dna);
        $traitsCount += self::dnaTraitsCount($hunterDna);

        return [
            'completed' => self::isComplete($hunterDna),
            'completion_score' => (int) round(($traitsCount / self::TOTAL_TRAITS) * 100),
            'traits_count' => $traitsCount,
        ];
    }

    private static function dnaTraitsCount(array $hunterDna): int
    {
        $count = 0;

        foreach (self::DNA_TRAITS as $trait) {
            if ($trait === 'mentor') {
                $count += array_key_exists($trait, $hunterDna) ? 1 : 0;
                continue;
            }

            if ($trait === 'goals') {
                $count += ($hunterDna[$trait] ?? []) !== [] ? 1 : 0;
                continue;
            }

            $count += trim((string) ($hunterDna[$trait] ?? '')) !== '' ? 1 : 0;
        }

        return $count;
    }
}
