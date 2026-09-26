<?php

namespace App\Services\AppConfig;

use App\Models\AppRemoteConfig;
use Illuminate\Support\Facades\Cache;

class WebAppearanceService
{
    public const KEY = 'web_appearance';

    public const SLOTS = ['auth', 'landing', 'app', 'topbar', 'sidebar'];

    private const CACHE_KEY = 'app_remote_config:active:web_appearance';

    public function __construct(private readonly AppRemoteConfigService $remoteConfig) {}

    public function defaults(): array
    {
        return ['backgrounds' => array_fill_keys(self::SLOTS, ['url' => null, 'version' => 0])];
    }

    public function normalize(array $input): array
    {
        $config = $this->defaults();
        $entries = is_array($input['backgrounds'] ?? null) ? $input['backgrounds'] : [];

        foreach (self::SLOTS as $slot) {
            $entry = $entries[$slot] ?? [];
            if (! is_array($entry)) {
                continue;
            }

            $config['backgrounds'][$slot] = [
                'url' => $this->remoteConfig->normalizeAppearanceUrl($entry['url'] ?? null),
                'version' => max(0, (int) ($entry['version'] ?? 0)),
            ];
        }

        return $config;
    }

    public function active(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function (): array {
            $record = AppRemoteConfig::query()->where('key', self::KEY)->where('is_active', true)->first();

            return $this->normalize($record?->config_json ?? []);
        });
    }

    public function publicBackgrounds(): array
    {
        $backgrounds = $this->active()['backgrounds'];

        return array_map(static fn (array $entry): ?string => $entry['url'], $backgrounds);
    }

    public function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
