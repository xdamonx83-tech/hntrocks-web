<?php

namespace App\Services\Twitch;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class TwitchLiveStatusService
{
    public function statusForUrl(?string $url): array
    {
        return $this->statusForChannel($this->channelFromUrl($url));
    }

    public function warmStatusesForUrls(iterable $urls): void
    {
        $channels = [];

        foreach ($urls as $url) {
            $channel = $this->channelFromUrl(is_string($url) ? $url : null);
            if ($channel !== null) {
                $channels[$channel] = $channel;
            }
        }

        $missing = array_values(array_filter(
            $channels,
            fn (string $channel): bool => ! is_array(Cache::get('twitch:stream-status:'.sha1($channel)))
        ));

        if ($missing === []) {
            return;
        }

        $clientId = trim((string) config('social.providers.twitch.client_id'));
        $clientSecret = trim((string) config('social.providers.twitch.client_secret'));

        if ($clientId === '' || $clientSecret === '') {
            return;
        }

        try {
            $token = $this->appAccessToken($clientId, $clientSecret);
            if ($token === null) {
                return;
            }

            $responses = Http::pool(function (Pool $pool) use ($missing, $clientId, $token): array {
                $requests = [];
                foreach ($missing as $channel) {
                    $requests[$channel] = $pool->as($channel)
                        ->timeout(6)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Client-ID' => $clientId,
                            'Authorization' => 'Bearer '.$token,
                        ])
                        ->get((string) config('social.providers.twitch.streams_url', 'https://api.twitch.tv/helix/streams'), [
                            'user_login' => $channel,
                        ]);
                }
                return $requests;
            });

            foreach ($missing as $channel) {
                $response = $responses[$channel] ?? null;
                $status = $this->emptyStatus($channel);

                if ($response && $response->successful()) {
                    $stream = Arr::first((array) $response->json('data', []));
                    if (is_array($stream)) {
                        $status = [
                            'available' => true,
                            'state' => 'live',
                            'is_live' => true,
                            'channel' => $channel,
                            'display_name' => trim((string) Arr::get($stream, 'user_name')) ?: $channel,
                            'title' => trim((string) Arr::get($stream, 'title')) ?: null,
                            'game_name' => trim((string) Arr::get($stream, 'game_name')) ?: null,
                            'viewer_count' => max(0, (int) Arr::get($stream, 'viewer_count', 0)),
                            'started_at' => Arr::get($stream, 'started_at'),
                        ];
                    } else {
                        $status = [
                            ...$this->emptyStatus($channel),
                            'available' => true,
                            'state' => 'offline',
                        ];
                    }
                }

                Cache::put('twitch:stream-status:'.sha1($channel), $status, now()->addSeconds(60));
            }
        } catch (Throwable) {
            // Feed rendering must never fail because Twitch is unavailable.
        }
    }

    public function statusForChannel(?string $channel): array
    {
        $channel = strtolower(trim((string) $channel));

        if (! preg_match('/^[a-z0-9_]{4,25}$/', $channel)) {
            return $this->emptyStatus(null);
        }

        return Cache::remember(
            'twitch:stream-status:'.sha1($channel),
            now()->addSeconds(60),
            fn (): array => $this->fetchStatus($channel),
        );
    }

    public function channelFromUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?: '';

        if ($host !== 'twitch.tv') {
            return null;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $channel = strtolower((string) (explode('/', $path)[0] ?? ''));

        return preg_match('/^[a-z0-9_]{4,25}$/', $channel) ? $channel : null;
    }

    private function fetchStatus(string $channel): array
    {
        $clientId = trim((string) config('social.providers.twitch.client_id'));
        $clientSecret = trim((string) config('social.providers.twitch.client_secret'));

        if ($clientId === '' || $clientSecret === '') {
            return $this->emptyStatus($channel);
        }

        try {
            $token = $this->appAccessToken($clientId, $clientSecret);

            if ($token === null) {
                return $this->emptyStatus($channel);
            }

            $response = Http::timeout(6)
                ->acceptJson()
                ->withHeaders(['Client-ID' => $clientId])
                ->withToken($token)
                ->get((string) config('social.providers.twitch.streams_url', 'https://api.twitch.tv/helix/streams'), [
                    'user_login' => $channel,
                ]);

            if (! $response->successful()) {
                return $this->emptyStatus($channel);
            }

            $stream = Arr::first((array) $response->json('data', []));

            if (! is_array($stream)) {
                return [
                    ...$this->emptyStatus($channel),
                    'available' => true,
                    'state' => 'offline',
                ];
            }

            return [
                'available' => true,
                'state' => 'live',
                'is_live' => true,
                'channel' => $channel,
                'display_name' => trim((string) Arr::get($stream, 'user_name')) ?: $channel,
                'title' => trim((string) Arr::get($stream, 'title')) ?: null,
                'game_name' => trim((string) Arr::get($stream, 'game_name')) ?: null,
                'viewer_count' => max(0, (int) Arr::get($stream, 'viewer_count', 0)),
                'started_at' => Arr::get($stream, 'started_at'),
            ];
        } catch (Throwable) {
            return $this->emptyStatus($channel);
        }
    }

    private function appAccessToken(string $clientId, string $clientSecret): ?string
    {
        $cacheKey = 'twitch:app-access-token:'.sha1($clientId);
        $cachedToken = Cache::get($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $response = Http::asForm()
            ->timeout(6)
            ->post((string) config('social.providers.twitch.token_url', 'https://id.twitch.tv/oauth2/token'), [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            return null;
        }

        $expiresIn = max(180, (int) $response->json('expires_in', 3600));
        Cache::put($cacheKey, $token, now()->addSeconds(max(60, $expiresIn - 120)));

        return $token;
    }

    private function emptyStatus(?string $channel): array
    {
        return [
            'available' => false,
            'state' => 'unknown',
            'is_live' => false,
            'channel' => $channel,
            'display_name' => $channel,
            'title' => null,
            'game_name' => null,
            'viewer_count' => null,
            'started_at' => null,
        ];
    }
}
