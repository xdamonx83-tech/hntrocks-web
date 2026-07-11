<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\Str;
use Throwable;

class HntTheme
{
    public const PREVIEW_SESSION_KEY = 'hnt_theme_preview_active';

    public static function enabled(): bool
    {
        return self::previewLive() || (bool) config('hunthub.theme.enabled', false) || self::previewActive();
    }

    public static function previewLive(): bool
    {
        return (bool) config('hunthub.theme.preview_live', false);
    }

    public static function dashboardFeedLive(): bool
    {
        return (bool) config('hunthub.theme.dashboard_feed_live', false);
    }

    public static function feedEnabled(): bool
    {
        if (self::previewLive() || self::previewActive()) {
            return true;
        }

        return self::enabled() && (bool) config('hunthub.theme.feed_enabled', false);
    }

    public static function profileEnabled(): bool
    {
        if (self::previewLive() || self::previewActive()) {
            return true;
        }

        return self::enabled() && (bool) config('hunthub.theme.profile_enabled', false);
    }

    public static function settingsEnabled(): bool
    {
        if (self::previewLive() || self::previewActive()) {
            return true;
        }

        return self::enabled() && (bool) config('hunthub.theme.settings_enabled', false);
    }

    public static function teamsEnabled(): bool
    {
        return self::enabled() && (bool) config('hunthub.theme.teams_enabled', false);
    }

    public static function lfgEnabled(): bool
    {
        if (self::previewLive() || self::previewActive()) {
            return true;
        }

        return self::enabled() && (bool) config('hunthub.theme.lfg_enabled', false);
    }

    public static function teamLfgEnabled(): bool
    {
        return self::enabled() && (bool) config('hunthub.theme.team_lfg_enabled', false);
    }

    public static function active(): string
    {
        if (self::previewLive() || self::previewActive()) {
            return self::previewTheme();
        }

        return self::normalizeTheme((string) config('hunthub.theme.active', 'vikinger'));
    }

    public static function fallback(): string
    {
        if (self::previewLive() || self::previewActive()) {
            return self::previewFallbackTheme();
        }

        return self::normalizeTheme((string) config('hunthub.theme.fallback', 'vikinger'));
    }

    public static function previewTheme(): string
    {
        return self::normalizeTheme((string) config('hunthub.theme.preview.theme', 'hnt_preview'));
    }

    public static function previewFallbackTheme(): string
    {
        return self::normalizeTheme((string) config('hunthub.theme.preview.fallback', 'socialite'));
    }

    public static function previewRestrictionConfigured(): bool
    {
        return (bool) config('hunthub.theme.preview.allow_any_admin', false)
            || count(self::previewAllowedUserIds()) > 0
            || count(self::previewAllowedEmails()) > 0;
    }

    public static function previewAvailableFor(?User $user = null): bool
    {
        $user ??= self::authenticatedUser();

        if (! $user?->isAdmin()) {
            return false;
        }

        if ((bool) config('hunthub.theme.preview.allow_any_admin', false)) {
            return true;
        }

        $allowedUserIds = self::previewAllowedUserIds();
        $allowedEmails = self::previewAllowedEmails();

        if ($allowedUserIds === [] && $allowedEmails === []) {
            return false;
        }

        if (in_array((int) $user->id, $allowedUserIds, true)) {
            return true;
        }

        $email = Str::of((string) $user->email)->lower()->trim()->toString();

        return $email !== '' && in_array($email, $allowedEmails, true);
    }

    public static function previewActive(?User $user = null): bool
    {
        if (self::dashboardFeedRequestActive()) {
            $user ??= self::authenticatedUser();

            return $user instanceof User;
        }

        return self::previewSessionEnabled() && self::previewAvailableFor($user);
    }

    public static function activatePreview(): void
    {
        try {
            session()->put(self::PREVIEW_SESSION_KEY, true);
        } catch (Throwable) {
            // No session available, for example in CLI context.
        }
    }

    public static function deactivatePreview(): void
    {
        try {
            session()->forget(self::PREVIEW_SESSION_KEY);
        } catch (Throwable) {
            // No session available, for example in CLI context.
        }
    }

    /**
     * Resolve a normal Laravel view name to a theme override when available.
     *
     * Example:
     *  HntTheme::resolve('feed.index')
     *  -> themes.socialite.feed.index, if HH_THEME_ENABLED=true and the file exists
     *  -> feed.index otherwise
     *
     * In preview mode the order is:
     *  -> themes.hnt_preview.*
     *  -> themes.socialite.* (or configured preview fallback)
     *  -> existing Laravel view
     */
    public static function resolve(string $view): string
    {
        foreach (self::candidates($view) as $candidate) {
            if ($candidate === $view || ViewFactory::exists($candidate)) {
                return $candidate;
            }
        }

        return trim($view);
    }

    public static function view(string $view, array $data = [], array $mergeData = []): View
    {
        return view(self::resolve($view), $data, $mergeData);
    }

    /**
     * Return view names in the preferred order for view()->first(...).
     */
    public static function candidates(string $view): array
    {
        $view = trim($view);

        if ($view === '' || ! self::enabled()) {
            return [$view];
        }

        $candidates = [];

        foreach (array_unique([self::active(), self::fallback()]) as $theme) {
            // The dashboard prototype is a private, session-bound admin preview.
            // Never let a production theme/fallback ENV accidentally expose it
            // (or trigger its intentional 404 guard) for normal users.
            if ($theme === 'hnt_preview' && ! self::previewActive()) {
                continue;
            }

            $candidates[] = self::themeViewName($view, $theme);
        }

        $candidates[] = $view;

        return array_values(array_unique($candidates));
    }

    public static function hasOverride(string $view): bool
    {
        return ViewFactory::exists(self::themeViewName($view, self::active()));
    }

    public static function hasResolvedOverride(string $view): bool
    {
        return self::resolve($view) !== trim($view);
    }

    public static function themeViewName(string $view, ?string $theme = null): string
    {
        $theme = self::normalizeTheme($theme ?: self::active());
        $viewRoot = trim((string) config('hunthub.theme.view_root', 'themes'), '.');

        return $viewRoot . '.' . $theme . '.' . ltrim($view, '.');
    }

    public static function asset(string $path = '', ?string $theme = null): string
    {
        $theme = self::normalizeTheme($theme ?: self::active());
        $assetPaths = (array) config('hunthub.theme.asset_paths', []);
        $base = trim((string) ($assetPaths[$theme] ?? ('assets/themes/' . $theme)), '/');
        $path = trim($path, '/');

        return asset($path === '' ? $base : $base . '/' . $path);
    }

    public static function is(string $theme): bool
    {
        return self::active() === self::normalizeTheme($theme);
    }

    public static function previewAllowedUserIds(): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value): int => (int) $value,
            (array) config('hunthub.theme.preview.allowed_user_ids', [])
        ), static fn (int $value): bool => $value > 0)));
    }

    public static function previewAllowedEmails(): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => Str::of((string) $value)->lower()->trim()->toString(),
            (array) config('hunthub.theme.preview.allowed_emails', [])
        ), static fn (string $value): bool => $value !== '')));
    }

    private static function dashboardFeedRequestActive(): bool
    {
        if (! self::dashboardFeedLive()) {
            return false;
        }

        try {
            return (bool) request()->attributes->get('hnt_dashboard_feed_live', false);
        } catch (Throwable) {
            return false;
        }
    }

    private static function previewSessionEnabled(): bool
    {
        try {
            return (bool) session()->get(self::PREVIEW_SESSION_KEY, false);
        } catch (Throwable) {
            return false;
        }
    }

    private static function authenticatedUser(): ?User
    {
        try {
            $user = auth()->user();

            return $user instanceof User ? $user : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function normalizeTheme(string $theme): string
    {
        $theme = Str::of($theme)->lower()->replaceMatches('/[^a-z0-9_\-]/', '')->toString();

        return $theme !== '' ? $theme : 'vikinger';
    }
}
