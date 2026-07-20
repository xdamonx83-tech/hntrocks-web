<?php

namespace App\Support;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class HntViewMode
{
    public const AUTO = 'auto';
    public const DESKTOP = 'desktop';
    public const MOBILE = 'mobile';

    public static function validModes(): array
    {
        return [self::AUTO, self::DESKTOP, self::MOBILE];
    }

    public static function explicitPreference(Request $request): ?string
    {
        $view = strtolower(trim((string) $request->query('view', '')));
        if (in_array($view, self::validModes(), true)) {
            return $view;
        }

        if (self::truthy($request->query('mobile')) || self::truthy($request->query('hnt_mobile'))) {
            return self::MOBILE;
        }

        if (self::truthy($request->query('desktop')) || self::truthy($request->query('hnt_desktop'))) {
            return self::DESKTOP;
        }

        if (self::truthy($request->query('auto')) || self::truthy($request->query('hnt_auto'))) {
            return self::AUTO;
        }

        return null;
    }

    public static function preference(Request $request): string
    {
        $explicit = self::explicitPreference($request);
        if ($explicit !== null) {
            return $explicit;
        }

        $cookie = strtolower(trim((string) $request->cookie((string) config('hnt_view.cookie_name', 'hnt_view_mode'), '')));
        if (in_array($cookie, self::validModes(), true)) {
            return $cookie;
        }

        $default = strtolower((string) config('hnt_view.default_preference', self::AUTO));

        return in_array($default, self::validModes(), true) ? $default : self::AUTO;
    }

    public static function resolvedMode(Request $request, ?string $preference = null): string
    {
        $preference ??= self::preference($request);

        if ($preference === self::MOBILE || $preference === self::DESKTOP) {
            return $preference;
        }

        return self::isMobileDevice($request) ? self::MOBILE : self::DESKTOP;
    }

    public static function isMobileDevice(Request $request): bool
    {
        $clientHint = trim((string) $request->header('Sec-CH-UA-Mobile', ''));
        if ($clientHint === '?1' || $clientHint === '1') {
            return true;
        }

        $userAgent = strtolower((string) $request->userAgent());
        if ($userAgent === '') {
            return false;
        }

        return (bool) preg_match('/android|iphone|ipad|ipod|windows phone|blackberry|bb10|opera mini|opera mobi|mobile|tablet|silk|kindle/', $userAgent);
    }

    public static function mobileViewFor(Request $request, ViewContract $desktopView): ?string
    {
        $routeName = (string) optional($request->route())->getName();
        $routeViews = (array) config('hnt_view.route_views', []);

        if ($routeName !== '' && isset($routeViews[$routeName])) {
            $mapped = (string) $routeViews[$routeName];
            if ($mapped !== '' && View::exists($mapped)) {
                return $mapped;
            }
        }

        $desktopName = method_exists($desktopView, 'name') ? (string) $desktopView->name() : '';
        if ($desktopName === '') {
            return null;
        }

        $mobilePrefix = (string) config('hnt_view.mobile_view_prefix', 'themes.hnt_mobile.');
        if (Str::startsWith($desktopName, $mobilePrefix)) {
            return $desktopName;
        }

        foreach ((array) config('hnt_view.desktop_view_prefixes', []) as $desktopPrefix) {
            $desktopPrefix = (string) $desktopPrefix;
            if ($desktopPrefix !== '' && Str::startsWith($desktopName, $desktopPrefix)) {
                $candidate = $mobilePrefix.Str::after($desktopName, $desktopPrefix);

                return View::exists($candidate) ? $candidate : null;
            }
        }

        $candidate = $mobilePrefix.$desktopName;

        return View::exists($candidate) ? $candidate : null;
    }

    public static function routeIsExcluded(Request $request): bool
    {
        $name = (string) optional($request->route())->getName();
        if ($name === '') {
            return false;
        }

        foreach ((array) config('hnt_view.excluded_routes', []) as $pattern) {
            if (Str::is((string) $pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    private static function truthy(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
