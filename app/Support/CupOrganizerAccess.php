<?php

namespace App\Support;

use App\Models\Cup;
use App\Models\User;

final class CupOrganizerAccess
{
    public const VERIFICATION_MANUAL = 'manual';
    public const VERIFICATION_AI = 'ai';
    public const AI_MANAGER_USER_ID = 49;

    public static function canManage(Cup $cup, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || (int) $cup->owner_id === (int) $user->id;
    }

    public static function canUseAi(?User $user): bool
    {
        return $user !== null && (int) $user->id === self::AI_MANAGER_USER_ID;
    }

    public static function verificationMode(Cup $cup): string
    {
        $settings = is_array($cup->settings) ? $cup->settings : [];
        $mode = (string) data_get($settings, 'verification_mode', '');

        if (in_array($mode, [self::VERIFICATION_MANUAL, self::VERIFICATION_AI], true)) {
            return $mode;
        }

        // Existing cups owned by the HNT.ROCKS AI manager keep their current
        // automatic workflow. All other legacy cups safely fall back to manual.
        return (int) $cup->owner_id === self::AI_MANAGER_USER_ID
            ? self::VERIFICATION_AI
            : self::VERIFICATION_MANUAL;
    }

    public static function usesAi(Cup $cup): bool
    {
        return self::verificationMode($cup) === self::VERIFICATION_AI;
    }

    public static function sanitizeRequestedVerificationMode(?User $user, mixed $requested): string
    {
        if (self::canUseAi($user) && (string) $requested === self::VERIFICATION_AI) {
            return self::VERIFICATION_AI;
        }

        return self::VERIFICATION_MANUAL;
    }
}
