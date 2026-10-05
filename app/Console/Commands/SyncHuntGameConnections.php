<?php

namespace App\Console\Commands;

use App\Models\HuntGameConnection;
use App\Models\HuntGameLinkAttempt;
use App\Services\Hunt\SteamHuntStatsProvider;
use Illuminate\Console\Command;

class SyncHuntGameConnections extends Command
{
    protected $signature = 'hnt:hunt:steam-sync {--limit=50}';

    protected $description = 'Refresh Hunt-only Steam stats for linked accounts';

    public function handle(SteamHuntStatsProvider $steam): int
    {
        HuntGameLinkAttempt::query()->where('expires_at', '<', now())->delete();

        if (! config('hunt_platform.steam.enabled') || blank(config('hunt_platform.steam.api_key'))) {
            $this->warn('Hunt Steam sync not configured.');
            return self::SUCCESS;
        }

        $hours = max(1, (int) config('hunt_platform.steam.sync_hours', 24));
        $limit = max(1, min(250, (int) $this->option('limit')));

        $accounts = HuntGameConnection::query()
            ->where('provider', 'steam')
            ->where(function ($query) use ($hours): void {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<=', now()->subHours($hours));
            })
            // Throttle repeated failures and avoid hammering Steam.
            ->where('updated_at', '<=', now()->subHour())
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        foreach ($accounts as $account) {
            $steam->sync($account);
        }

        $this->info('Processed Hunt Steam links: '.$accounts->count());

        return self::SUCCESS;
    }
}
