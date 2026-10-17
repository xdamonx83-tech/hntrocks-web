<?php
namespace App\Http\Controllers\Next;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/** Scope the four supported design-preview languages to /next only. */
final class NextLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // The legacy app loads resources/lang; HNTv3 Prestige groups live in lang.
        // Add the supplemental path without replacing existing site translations.
        app('translation.loader')->addPath(base_path('lang'));
        $locale = $request->session()->get('hnt_next_locale');
        if (is_string($locale) && in_array($locale, ['de','en','es','ru'], true)) {
            App::setLocale($locale);
        }
        return $next($request);
    }
}
