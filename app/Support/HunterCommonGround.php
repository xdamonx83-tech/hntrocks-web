<?php

namespace App\Support;

use App\Models\LiveLobby;
use App\Models\UserProfile;

class HunterCommonGround
{
    public static function between(
        ?UserProfile $viewerProfile,
        LiveLobby $lobby,
        ?UserProfile $creatorProfile,
        bool $self = false,
    ): array {
        $items = [];
        $maxScore = 0;
        $viewerDna = HunterDna::normalize($viewerProfile?->hunter_dna);
        $creatorDna = HunterDna::normalize($creatorProfile?->hunter_dna);

        $viewerPlatform = self::value($viewerProfile?->platform);
        $lobbyPool = self::platformPool($lobby->crossplay_pool ?: $lobby->platform);
        if ($viewerPlatform !== null && $lobbyPool !== null) {
            $maxScore++;
            if (self::platformPool($viewerPlatform) === $lobbyPool) {
                self::add($items, 'platform', $lobbyPool);
            }
        }

        self::compare($items, $maxScore, 'region', $viewerProfile?->region, $lobby->region);
        self::compare($items, $maxScore, 'language', $viewerProfile?->language, $lobby->language);

        $preferredMode = self::value($viewerDna['preferred_mode'] ?? null);
        $lobbyMode = self::value($lobby->mode);
        if ($preferredMode !== null && $lobbyMode !== null) {
            $maxScore++;
            if (($preferredMode === 'flexible' && in_array($lobbyMode, ['duo', 'trio'], true))
                || $preferredMode === $lobbyMode) {
                self::add($items, 'preferred_mode', $lobbyMode);
            }
        }

        self::compare($items, $maxScore, 'playstyle', $viewerProfile?->playstyle, $lobby->playstyle);
        self::compare($items, $maxScore, 'hunt_role', $viewerProfile?->hunt_role, $creatorProfile?->hunt_role);

        $voice = self::value($viewerDna['voice'] ?? null);
        if ($voice !== null) {
            $maxScore++;
            $voiceMatches = $lobby->voice_required
                ? in_array($voice, ['yes', 'optional'], true)
                : in_array($voice, ['no', 'optional'], true);
            if ($voiceMatches) {
                self::add($items, 'voice', $voice);
            }
        }

        self::compare($items, $maxScore, 'temper', $viewerDna['temper'] ?? null, $creatorDna['temper'] ?? null);
        self::compare($items, $maxScore, 'experience', $viewerDna['experience'] ?? null, $creatorDna['experience'] ?? null);

        $viewerGoals = self::values($viewerDna['goals'] ?? []);
        $creatorGoals = self::values($creatorDna['goals'] ?? []);
        if ($viewerGoals !== [] && $creatorGoals !== []) {
            $maxScore += min(3, count($viewerGoals), count($creatorGoals));
            $creatorGoalLookup = array_fill_keys($creatorGoals, true);
            foreach (array_slice(array_values(array_filter(
                $viewerGoals,
                fn (string $goal): bool => isset($creatorGoalLookup[$goal]),
            )), 0, 3) as $goal) {
                self::add($items, 'goals', $goal);
            }
        }

        if (array_key_exists('mentor', $viewerDna) && array_key_exists('mentor', $creatorDna)) {
            $maxScore++;
            if ($viewerDna['mentor'] === true && $creatorDna['mentor'] === true) {
                self::add($items, 'mentor', 'true', 'Beginners welcome');
            }
        }

        return [
            'score' => count($items),
            'max_score' => $maxScore,
            'self' => $self,
            'items' => $items,
        ];
    }

    private static function compare(array &$items, int &$maxScore, string $key, mixed $viewerValue, mixed $targetValue): void
    {
        $targetLabel = trim((string) $targetValue);
        $viewerValue = self::value($viewerValue);
        $targetValue = self::value($targetValue);
        if ($viewerValue === null || $targetValue === null) {
            return;
        }

        $maxScore++;
        if ($viewerValue === $targetValue) {
            self::add($items, $key, $targetLabel);
        }
    }

    private static function add(array &$items, string $key, string $value, ?string $label = null): void
    {
        $items[] = [
            'key' => $key,
            'label' => $label ?? $value,
            'value' => $value,
        ];
    }

    private static function platformPool(mixed $platform): ?string
    {
        return match (self::value($platform)) {
            'pc' => 'pc',
            'playstation', 'xbox', 'console' => 'console',
            default => null,
        };
    }

    private static function value(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return $value === '' ? null : $value;
    }

    private static function values(array $values): array
    {
        $normalized = [];
        foreach ($values as $value) {
            $value = self::value($value);
            if ($value !== null && ! in_array($value, $normalized, true)) {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }
}
