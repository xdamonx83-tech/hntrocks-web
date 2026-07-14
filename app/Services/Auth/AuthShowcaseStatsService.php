<?php

namespace App\Services\Auth;

use App\Models\FeedPost;
use App\Models\HntMap;
use App\Models\Moment;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AuthShowcaseStatsService
{
    private const CACHE_KEY = 'auth_showcase.stats.v1';

    public function get(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), fn (): array => [
            'members' => $this->countWhenTableExists('users', fn (): int => (int) User::query()
                ->where('status', 'active')
                ->count()),
            'posts' => $this->countWhenTableExists('feed_posts', fn (): int => (int) FeedPost::query()
                ->where('status', 'published')
                ->where('visibility', '!=', 'private')
                ->count()),
            'moments' => $this->countWhenTableExists('moments', fn (): int => (int) Moment::query()
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->count()),
            'maps' => $this->countWhenTableExists('hnt_maps', fn (): int => (int) HntMap::query()
                ->where('is_active', true)
                ->count()),
        ]);
    }

    private function countWhenTableExists(string $table, Closure $callback): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return max(0, (int) $callback());
    }
}
