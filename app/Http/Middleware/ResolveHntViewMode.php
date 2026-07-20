<?php

namespace App\Http\Middleware;

use App\Support\HntViewMode;
use Closure;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveHntViewMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $preference = HntViewMode::preference($request);
        $resolvedMode = HntViewMode::resolvedMode($request, $preference);

        $request->attributes->set('hnt.view_preference', $preference);
        $request->attributes->set('hnt.view_mode', $resolvedMode);
        $request->attributes->set('hnt.mobile', $resolvedMode === HntViewMode::MOBILE);

        /** @var Response $response */
        $response = $next($request);

        $mobileView = null;
        if ($this->maySwapView($request, $response) && $resolvedMode === HntViewMode::MOBILE) {
            $originalContent = method_exists($response, 'getOriginalContent')
                ? $response->getOriginalContent()
                : null;

            if ($originalContent instanceof ViewContract) {
                $mobileView = HntViewMode::mobileViewFor($request, $originalContent);
                if ($mobileView !== null) {
                    $response->setContent(view($mobileView, $originalContent->getData()));
                    $request->attributes->set('hnt.mobile_view', $mobileView);
                }
            }
        }

        $this->addDebugHeaders($response, $preference, $resolvedMode, $mobileView !== null);
        $this->appendVaryHeader($response);

        if (HntViewMode::explicitPreference($request) !== null) {
            $response->headers->setCookie(cookie(
                (string) config('hnt_view.cookie_name', 'hnt_view_mode'),
                $preference,
                (int) config('hnt_view.cookie_minutes', 259200),
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            ));
        }

        return $response;
    }

    private function maySwapView(Request $request, Response $response): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return false;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }

        if ($request->boolean('fragment') || $request->boolean('data') || $request->boolean('dashboard_header')) {
            return false;
        }

        return ! HntViewMode::routeIsExcluded($request);
    }

    private function addDebugHeaders(Response $response, string $preference, string $resolvedMode, bool $mobileViewLoaded): void
    {
        $response->headers->set('X-HNT-View-Preference', $preference);
        $response->headers->set('X-HNT-View-Mode', $resolvedMode);

        if ($resolvedMode === HntViewMode::MOBILE) {
            $response->headers->set('X-HNT-Mobile-Template', $mobileViewLoaded ? 'loaded' : 'desktop-fallback');
        }
    }

    private function appendVaryHeader(Response $response): void
    {
        $existing = array_filter(array_map('trim', explode(',', (string) $response->headers->get('Vary', ''))));
        $vary = array_values(array_unique(array_merge($existing, ['Cookie', 'Sec-CH-UA-Mobile', 'User-Agent'])));
        $response->headers->set('Vary', implode(', ', $vary));
    }
}
