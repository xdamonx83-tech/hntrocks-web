<?php

namespace App\Services\AppConfig;

use App\Models\AppRemoteConfig;
use App\Models\AppRemoteFeedCard;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AppRemoteConfigService
{
    public const DEFAULT_KEY = 'default';

    private const ALLOWED_THEME_VARIANTS = [
        'hnt_default',
        'hnt_gold_event',
        'hnt_winter',
        'hnt_maintenance',
    ];

    private const ALLOWED_ACCENT_TOKENS = [
        'gold',
        'amber',
        'muted',
        'danger_limited',
    ];

    private const ALLOWED_CARD_VARIANTS = [
        'gold_glass',
        'warning',
        'maintenance',
        'neutral',
    ];

    private const ALLOWED_AUDIENCES = [
        'all',
        'admins',
        'user_ids',
        'app_version_below',
        'app_version_above',
    ];

    private const INTERNAL_SCHEMES = [
        'hntrocks://notifications',
        'hntrocks://messages',
        'hntrocks://feed',
        'hntrocks://moments',
        'hntrocks://lfg',
        'hntrocks://shop',
        'hntrocks://profile',
    ];

    private const PATH_PATTERNS = [
        '#^/feed/posts/[0-9]+$#',
        '#^/moments/[0-9]+$#',
        '#^/moments/r/[0-9]+$#',
        '#^/lfg/[0-9]+$#',
        '#^/cups/[A-Za-z0-9_-]+$#',
        '#^/u/[A-Za-z0-9_.-]+$#',
    ];

    private const STORAGE_BRANDING_PATTERN = '#^/storage/app-branding/[A-Za-z0-9._/-]+$#';

    private const DEFAULT_THEME_PALETTE = [
        'canvas' => '#1A1A18',
        'canvas_deep' => '#141412',
        'surface' => '#20201E',
        'surface_raised' => '#262622',
        'surface_soft' => '#2F302D',
        'primary' => '#CFA149',
        'primary_strong' => '#D6A84F',
        'primary_muted' => '#8F7337',
        'text' => '#F2E8D8',
        'text_muted' => '#A79C8E',
        'text_faint' => '#746B60',
        'line' => '#343025',
        'danger' => '#B8463A',
        'success' => '#8FAF72',
        'warning' => '#D6A84F',
    ];

    public function defaults(): array
    {
        return [
            'schema_version' => 1,
            'android' => [
                'min_version_code' => 8,
                'recommended_version_code' => 8,
                'force_update' => false,
                'play_store_url' => 'https://play.google.com/store/apps/details?id=rocks.hnt.app',
            ],
            'features' => [
                'feed_remote_cards_enabled' => true,
                'moments_upload_enabled' => true,
                'moments_studio_enabled' => true,
                'lfg_create_enabled' => true,
                'messages_enabled' => true,
                'shop_enabled' => true,
                'bounty_marks_enabled' => true,
            ],
            'maintenance' => [
                'enabled' => false,
                'message_de' => '',
                'message_en' => '',
            ],
            'theme' => [
                'variant' => 'hnt_default',
                'accent_token' => 'gold',
                'palette_enabled' => false,
                'palette' => self::DEFAULT_THEME_PALETTE,
            ],
            'branding' => [
                'logo_enabled' => false,
                'logo_url' => null,
                'logo_dark_url' => null,
                'logo_updated_at' => null,
            ],
            'limits' => [
                'moment_upload_max_mb' => 250,
                'feed_video_upload_max_mb' => 250,
            ],
        ];
    }

    public function activeConfig(): array
    {
        $record = AppRemoteConfig::query()
            ->where('key', self::DEFAULT_KEY)
            ->where('is_active', true)
            ->first();

        return $this->normalizeConfig($record?->config_json ?? []);
    }

    public function previewConfig(?AppRemoteConfig $record = null): array
    {
        return $this->normalizeConfig($record?->config_json ?? []);
    }

    public function normalizeConfig(array $input): array
    {
        $defaults = $this->defaults();

        $config = array_replace_recursive($defaults, Arr::only($input, [
            'schema_version',
            'android',
            'features',
            'maintenance',
            'theme',
            'branding',
            'limits',
        ]));

        $config['schema_version'] = 1;
        $config['android']['min_version_code'] = max(0, (int) $config['android']['min_version_code']);
        $config['android']['recommended_version_code'] = max(
            $config['android']['min_version_code'],
            (int) $config['android']['recommended_version_code']
        );
        $config['android']['force_update'] = (bool) $config['android']['force_update'];
        $config['android']['play_store_url'] = $defaults['android']['play_store_url'];

        foreach (array_keys($defaults['features']) as $feature) {
            $config['features'][$feature] = (bool) $config['features'][$feature];
        }

        $config['maintenance']['enabled'] = (bool) $config['maintenance']['enabled'];
        $config['maintenance']['message_de'] = Str::limit(trim((string) $config['maintenance']['message_de']), 400, '');
        $config['maintenance']['message_en'] = Str::limit(trim((string) $config['maintenance']['message_en']), 400, '');

        if (! in_array($config['theme']['variant'], self::ALLOWED_THEME_VARIANTS, true)) {
            $config['theme']['variant'] = $defaults['theme']['variant'];
        }

        if (! in_array($config['theme']['accent_token'], self::ALLOWED_ACCENT_TOKENS, true)) {
            $config['theme']['accent_token'] = $defaults['theme']['accent_token'];
        }

        $config['theme']['palette_enabled'] = (bool) $config['theme']['palette_enabled'];
        $config['theme']['palette'] = $this->normalizePalette($config['theme']['palette'] ?? [], $defaults['theme']['palette']);

        $config['branding']['logo_enabled'] = (bool) $config['branding']['logo_enabled'];
        $config['branding']['logo_url'] = $this->normalizeBrandingUrl($config['branding']['logo_url'] ?? null);
        $config['branding']['logo_dark_url'] = $this->normalizeBrandingUrl($config['branding']['logo_dark_url'] ?? null);
        $config['branding']['logo_updated_at'] = $this->normalizeNullableString($config['branding']['logo_updated_at'] ?? null, 80);

        $config['limits']['moment_upload_max_mb'] = $this->clampInt($config['limits']['moment_upload_max_mb'], 1, 500, 250);
        $config['limits']['feed_video_upload_max_mb'] = $this->clampInt($config['limits']['feed_video_upload_max_mb'], 1, 500, 250);

        return $config;
    }

    public function validateActionUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (in_array($url, self::INTERNAL_SCHEMES, true)) {
            return $url;
        }

        foreach (self::PATH_PATTERNS as $pattern) {
            if (preg_match($pattern, $url) === 1) {
                return $url;
            }
        }

        return null;
    }

    public function normalizeBrandingUrl(mixed $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || preg_match('#^(javascript|data):#i', $url) === 1 || str_contains($url, '<svg')) {
            return null;
        }

        if (preg_match(self::STORAGE_BRANDING_PATTERN, $url) === 1 && ! str_contains($url, '..')) {
            return $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'], $parts['path'])) {
            return null;
        }

        if (! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $allowedHosts = array_filter(array_unique([$appHost, 'hnt.rocks']));

        if (! in_array($host, $allowedHosts, true)) {
            return null;
        }

        $path = (string) $parts['path'];

        if (preg_match(self::STORAGE_BRANDING_PATTERN, $path) !== 1 || str_contains($path, '..')) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].$path;
    }

    public function cardStyleVariant(?string $variant): string
    {
        $variant = trim((string) $variant);

        return in_array($variant, self::ALLOWED_CARD_VARIANTS, true) ? $variant : 'gold_glass';
    }

    public function audienceType(?string $audience): string
    {
        $audience = trim((string) $audience);

        return in_array($audience, self::ALLOWED_AUDIENCES, true) ? $audience : 'all';
    }

    public function visibleFeedCards(User $user, ?int $appVersionCode = null, string $locale = 'de'): Collection
    {
        $dismissedIds = $user->remoteFeedCardDismissals()
            ->pluck('remote_id')
            ->all();

        return AppRemoteFeedCard::query()
            ->visibleNow()
            ->whereNotIn('remote_id', $dismissedIds)
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->filter(fn (AppRemoteFeedCard $card): bool => $this->matchesAudience($card, $user, $appVersionCode))
            ->map(fn (AppRemoteFeedCard $card): array => $this->feedCardPayload($card, $locale))
            ->values();
    }

    public function feedCardPayload(AppRemoteFeedCard $card, string $locale = 'de'): array
    {
        $isEnglish = strtolower($locale) === 'en';
        $actionUrl = $this->validateActionUrl($card->action_url);

        return [
            'id' => $card->remote_id,
            'title' => $this->localized($isEnglish, $card->title_de, $card->title_en),
            'body' => $this->localized($isEnglish, $card->body_de, $card->body_en),
            'cta_label' => $this->localized($isEnglish, $card->cta_label_de, $card->cta_label_en),
            'action_url' => $actionUrl,
            'style_variant' => $this->cardStyleVariant($card->style_variant),
            'dismissible' => (bool) $card->dismissible,
            'priority' => (int) $card->priority,
            'starts_at' => $card->starts_at?->toIso8601String(),
            'ends_at' => $card->ends_at?->toIso8601String(),
        ];
    }

    private function matchesAudience(AppRemoteFeedCard $card, User $user, ?int $appVersionCode): bool
    {
        $payload = is_array($card->audience_payload) ? $card->audience_payload : [];

        return match ($this->audienceType($card->audience_type)) {
            'admins' => $user->isAdmin(),
            'user_ids' => in_array((int) $user->id, array_map('intval', (array) ($payload['user_ids'] ?? [])), true),
            'app_version_below' => $appVersionCode !== null && $appVersionCode < (int) ($payload['version_code'] ?? 0),
            'app_version_above' => $appVersionCode !== null && $appVersionCode > (int) ($payload['version_code'] ?? PHP_INT_MAX),
            default => true,
        };
    }

    private function localized(bool $isEnglish, ?string $de, ?string $en): ?string
    {
        $primary = trim((string) ($isEnglish ? $en : $de));
        $fallback = trim((string) ($isEnglish ? $de : $en));

        return $primary !== '' ? $primary : ($fallback !== '' ? $fallback : null);
    }

    private function clampInt(mixed $value, int $min, int $max, int $fallback): int
    {
        $value = is_numeric($value) ? (int) $value : $fallback;

        return min($max, max($min, $value));
    }

    private function normalizePalette(mixed $palette, array $defaults): array
    {
        $palette = is_array($palette) ? $palette : [];
        $normalized = [];

        foreach ($defaults as $key => $default) {
            $normalized[$key] = $this->normalizeHexColor($palette[$key] ?? null, $default);
        }

        return $normalized;
    }

    private function normalizeHexColor(mixed $value, string $default): string
    {
        $value = strtoupper(trim((string) $value));

        return preg_match('/^#[0-9A-F]{6}$/', $value) === 1 ? $value : $default;
    }

    private function normalizeNullableString(mixed $value, int $limit): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, $limit, '');
    }
}
