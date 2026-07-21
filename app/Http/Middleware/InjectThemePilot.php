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

        $hasSharedHeader = str_contains($html, 'data-hnt-shared-header');
        $pilot = match (true) {
            $request->routeIs('feed.index') || str_contains($html, 'data-page="feed"') => 'feed',
            $request->routeIs('account.settings.edit') => 'settings',
            $request->routeIs('profile.*') || str_contains($html, 'data-page="profile"') || str_contains($html, 'data-page="profile-edit"') => 'profile',
            $hasSharedHeader => 'shared',
            default => null,
        };

        if ($pilot === null) {
            return $response;
        }

        $themeHead = view('themes.hnt_preview.partials.theme-head', [
            'themePreference' => $request->user()?->theme_preference
                ?? $request->cookie('hnt_theme_preference', 'light'),
            'themePilot' => $pilot,
        ])->render();

        $profileForceStyle = '';

        if ($pilot === 'profile') {
            $profileForceFile = public_path('assets/themes/hnt_preview/dashboard-profile/profile-dark-mode-force.css');

            if (is_file($profileForceFile)) {
                $profileForceCss = file_get_contents($profileForceFile);

                if (is_string($profileForceCss) && $profileForceCss !== '') {
                    $profileForceCss = str_ireplace('</style', '<\/style', $profileForceCss);
                    $profileForceStyle = "<style data-hnt-profile-force>\n{$profileForceCss}\n</style>";
                    $themeHead .= "\n".$profileForceStyle;
                }
            }
        }

        if (! str_contains($html, 'data-hnt-theme-tokens')) {
            $html = preg_replace(
                '/<\/head>/i',
                $themeHead."\n</head>",
                $html,
                1
            ) ?? $html;
        } elseif ($pilot === 'profile' && $profileForceStyle !== '' && ! str_contains($html, 'data-hnt-profile-force')) {
            $html = preg_replace(
                '/<\/head>/i',
                $profileForceStyle."\n</head>",
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
