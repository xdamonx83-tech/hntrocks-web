<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const ALLOWED_LOCALES = ['de', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->validLocale(Session::get('locale'));
        $rememberDetectedLocale = false;

        if ($locale === null) {
            $locale = $this->validLocale($request->cookie('locale'));
        }

        if ($locale === null) {
            $locale = $this->detectLocaleFromRequest($request);
            $rememberDetectedLocale = true;
        }

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        $response = $next($request);

        if ($rememberDetectedLocale) {
            return $response->withCookie(
                Cookie::make('locale', $locale, 60 * 24 * 365, null, null, $request->isSecure(), true, false, 'lax')
            );
        }

        return $response;
    }

    private function validLocale(mixed $locale): ?string
    {
        if (! is_string($locale)) {
            return null;
        }

        $locale = strtolower(trim($locale));

        return in_array($locale, self::ALLOWED_LOCALES, true) ? $locale : null;
    }

    private function detectLocaleFromRequest(Request $request): string
    {
        $acceptLanguage = strtolower((string) $request->headers->get('Accept-Language', ''));

        if ($this->acceptsGerman($acceptLanguage)) {
            return 'de';
        }

        return 'en';
    }

    private function acceptsGerman(string $acceptLanguage): bool
    {
        if ($acceptLanguage === '') {
            return false;
        }

        foreach (explode(',', $acceptLanguage) as $part) {
            $language = trim(explode(';', $part, 2)[0] ?? '');

            if ($language === 'de' || str_starts_with($language, 'de-')) {
                return true;
            }
        }

        return false;
    }
}
