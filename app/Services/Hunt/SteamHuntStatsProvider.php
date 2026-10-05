<?php

namespace App\Services\Hunt;

use App\Models\HuntGameConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class SteamHuntStatsProvider
{
    private const API = 'https://api.steampowered.com';

    public function sync(HuntGameConnection $account): void
    {
        if ($account->provider !== 'steam') {
            throw new RuntimeException('unsupported_provider');
        }

        $key = trim((string) config('hunt_platform.steam.api_key'));
        if (! config('hunt_platform.steam.enabled') || $key === '') {
            $this->failure($account, 'not_configured');
            return;
        }

        $game = (int) config('hunt_platform.steam.app_id', 594650);

        try {
            // appids_filter prevents requesting games other than Hunt.
            $owned = Http::acceptJson()->timeout(12)->get(self::API.'/IPlayerService/GetOwnedGames/v0001/', [
                'key' => $key,
                'steamid' => $account->provider_user_id,
                'format' => 'json',
                'include_appinfo' => 0,
                'include_played_free_games' => 1,
                'appids_filter[0]' => $game,
            ]);
            $achievements = Http::acceptJson()->timeout(12)->get(self::API.'/ISteamUserStats/GetPlayerAchievements/v0001/', [
                'key' => $key,
                'steamid' => $account->provider_user_id,
                'appid' => $game,
                'l' => 'english',
            ]);

            if (! $owned->successful() && ! $achievements->successful()) {
                $this->failure($account, 'provider_unavailable');
                return;
            }

            $games = $owned->successful() ? $owned->json('response.games') : null;
            $hunt = is_array($games)
                ? collect($games)->first(fn ($item) => is_array($item) && (int) ($item['appid'] ?? 0) === $game)
                : null;
            $minutes = is_array($hunt) && is_numeric($hunt['playtime_forever'] ?? null)
                ? max(0, (int) $hunt['playtime_forever']) : null;

            $stats = $achievements->successful() ? $achievements->json('playerstats') : null;
            $items = is_array($stats) && ($stats['success'] ?? false) === true
                ? ($stats['achievements'] ?? null) : null;

            // Never interpret an inaccessible/private library as zero hours.
            // An unavailable achievement response is likewise not zero wins.
            $total = is_array($items) && count($items) > 0 ? count($items) : null;
            $unlocked = $total === null ? null
                : count(array_filter($items, fn ($item) => is_array($item) && (int) ($item['achieved'] ?? 0) === 1));

            $payload = [
                'app_id' => $game,
                'playtime_minutes' => $minutes,
                'achievements_unlocked' => $unlocked,
                'achievements_total' => $total,
                'achievements_percent' => $total ? round($unlocked / $total * 100, 1) : null,
                'achievements' => is_array($items) ? array_values(array_map(
                    fn ($item) => [
                        'key' => (string) ($item['apiname'] ?? ''),
                        'unlocked' => (int) ($item['achieved'] ?? 0) === 1,
                    ], array_filter($items, 'is_array'))) : null,
            ];

            $account->forceFill([
                'hunt_stats' => $payload,
                'sync_status' => $minutes === null && $total === null ? 'unavailable' : 'ok',
                'sync_error' => $minutes === null && $total === null ? 'private_or_unavailable' : null,
                'last_synced_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            // HTTP exception messages may contain request URLs with the
            // Steam Web API key. Never log the raw provider exception.
            report(new RuntimeException('Steam Hunt sync failed ('.get_class($exception).')'));
            $this->failure($account, 'provider_unavailable');
        }
    }

    private function failure(HuntGameConnection $account, string $code): void
    {
        // Retain previously verified data when Steam temporarily fails.
        $account->forceFill([
            'sync_status' => 'failed',
            'sync_error' => $code,
        ])->save();
    }
}
