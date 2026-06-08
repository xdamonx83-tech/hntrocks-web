<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MentionRenderer
{
    public static function render(?string $body): string
    {
        $body = (string) $body;

        if ($body === '') {
            return '';
        }

        $escaped = e($body);
        $usernames = self::extractUsernames($body);

        if ($usernames === []) {
            return nl2br($escaped);
        }

        $users = User::query()
            ->whereIn(DB::raw('LOWER(username)'), $usernames)
            ->get(['id', 'name', 'username'])
            ->keyBy(fn (User $user): string => Str::lower($user->username));

        if ($users->isEmpty()) {
            return nl2br($escaped);
        }

        $linked = preg_replace_callback('/(?<![\pL\pN_@])@([A-Za-z0-9_.-]{1,32})\b/u', function (array $match) use ($users): string {
            $lookup = Str::lower($match[1]);
            $user = $users->get($lookup);

            if (! $user) {
                return $match[0];
            }

            $label = '@'.e($user->username);
            $title = e($user->name);
            $url = e(route('profile.public', $user));

            return '<a class="hh-mention-link" href="'.$url.'" title="'.$title.'">'.$label.'</a>';
        }, $escaped);

        return nl2br($linked ?? $escaped);
    }

    public static function extractUsernames(?string $body): array
    {
        preg_match_all('/(?<![\pL\pN_@])@([A-Za-z0-9_.-]{1,32})\b/u', (string) $body, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $username): string => Str::lower($username))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
