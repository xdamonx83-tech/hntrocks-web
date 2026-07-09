<?php

namespace App\Support;

class LiveLobbyPlatform
{
    public static function poolForProfile(mixed $platform): ?string
    {
        return match (self::value($platform)) {
            'pc' => 'pc',
            'playstation', 'ps', 'ps5', 'xbox', 'crossplay', 'console' => 'console',
            default => null,
        };
    }

    public static function profileAliasesForPool(?string $pool): array
    {
        return match ($pool) {
            'pc' => ['pc'],
            'console' => ['playstation', 'ps', 'ps5', 'xbox', 'crossplay', 'console'],
            default => [],
        };
    }

    private static function value(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return $value === '' ? null : $value;
    }
}
