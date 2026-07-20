<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectThemePilot
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('feed.index', 'account.settings.edit')) {
            return $response;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && ! str_contains(strtolower($contentType), 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || ! str_contains($html, '</head>') || ! preg_match('/<body\b/i', $html)) {
            return $response;
        }

        $pilot = $request->routeIs('feed.index') ? 'feed' : 'settings';
        $themeHead = view('themes.hnt_preview.partials.theme-head', [
            'themePreference' => $request->user()?->theme_preference ?? 'light',
            'themePilot' => $pilot,
        ])->render();

        if (! str_contains($html, 'data-hnt-theme-tokens')) {
            $html = preg_replace(
                '/<\/head>/i',
                $themeHead."\n</head>",
                $html,
                1
            ) ?? $html;
        }

        if (! str_contains($html, 'data-hnt-theme-pilot=')) {
            $html = preg_replace(
                '/<body\b([^>]*)>/i',
                '<body$1 data-hnt-theme-pilot="'.$pilot.'">',
                $html,
                1
            ) ?? $html;
        }

        $response->setContent($html);

        return $response;
    }
}
