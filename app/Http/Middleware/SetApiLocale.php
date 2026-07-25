<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    private const ALLOWED_LOCALES = ['de', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->detectLocale($request));

        return $next($request);
    }

    private function detectLocale(Request $request): string
    {
        $explicit = $this->normalizeLocale($request->headers->get('X-HNT-Locale'));
        if ($explicit !== null) {
            return $explicit;
        }

        $acceptLanguage = strtolower((string) $request->headers->get('Accept-Language', ''));
        foreach (explode(',', $acceptLanguage) as $part) {
            $language = trim(explode(';', $part, 2)[0] ?? '');
            $normalized = $this->normalizeLocale($language);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return $this->normalizeLocale((string) config('app.locale')) ?? 'de';
    }

    private function normalizeLocale(mixed $locale): ?string
    {
        if (! is_string($locale)) {
            return null;
        }

        $normalized = strtolower(trim($locale));
        if ($normalized === '') {
            return null;
        }

        $language = explode('-', str_replace('_', '-', $normalized), 2)[0];

        return in_array($language, self::ALLOWED_LOCALES, true) ? $language : null;
    }
}
