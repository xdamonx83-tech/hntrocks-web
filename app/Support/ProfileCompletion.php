<?php

namespace App\Support;

use App\Models\User;

class ProfileCompletion
{
    /**
     * Authoritative profile completion checks.
     *
     * Only the visible core fields are required. Social/stream links,
     * Discord, avatar, cover, headline, Hunt role, region and language stay optional.
     *
     * @return array<string, bool>
     */
    public static function checks(User $user): array
    {
        $user->loadMissing('profile');
        $profile = $user->profile;

        return [
            'name' => filled($user->name),
            'username' => filled($user->username),
            'bio' => filled($profile?->bio),
            'platform' => filled($profile?->platform),
            'playstyle' => filled($profile?->playstyle),
        ];
    }

    public static function score(User $user): int
    {
        $checks = self::checks($user);

        if ($checks === []) {
            return 0;
        }

        $done = count(array_filter($checks));

        return (int) round(($done / count($checks)) * 100);
    }
}
