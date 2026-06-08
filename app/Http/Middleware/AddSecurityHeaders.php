<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! (bool) config('hunthub.security_headers.enabled', true)) {
            return $response;
        }

        $this->setHeaderIfMissing($response, 'X-Content-Type-Options', 'nosniff');
        $this->setHeaderIfMissing($response, 'X-Frame-Options', (string) config('hunthub.security_headers.frame_options', 'SAMEORIGIN'));
        $this->setHeaderIfMissing($response, 'Referrer-Policy', (string) config('hunthub.security_headers.referrer_policy', 'strict-origin-when-cross-origin'));

        $permissionsPolicy = trim((string) config('hunthub.security_headers.permissions_policy', ''));
        if ($permissionsPolicy !== '') {
            $this->setHeaderIfMissing($response, 'Permissions-Policy', $permissionsPolicy);
        }

        if ((bool) config('hunthub.security_headers.hsts.enabled', false) && $this->shouldSendHsts($request)) {
            $this->setHeaderIfMissing($response, 'Strict-Transport-Security', $this->buildHstsValue());
        }

        $cspReportOnly = trim((string) config('hunthub.security_headers.csp_report_only', ''));
        if ($cspReportOnly !== '') {
            $this->setHeaderIfMissing($response, 'Content-Security-Policy-Report-Only', $cspReportOnly);
        }

        return $response;
    }

    private function setHeaderIfMissing(Response $response, string $name, string $value): void
    {
        if (! $response->headers->has($name)) {
            $response->headers->set($name, $value);
        }
    }

    private function shouldSendHsts(Request $request): bool
    {
        if ($request->isSecure()) {
            return true;
        }

        return str_starts_with((string) config('app.url'), 'https://');
    }

    private function buildHstsValue(): string
    {
        $maxAge = max(0, (int) config('hunthub.security_headers.hsts.max_age', 31536000));
        $parts = ['max-age=' . $maxAge];

        if ((bool) config('hunthub.security_headers.hsts.include_subdomains', false)) {
            $parts[] = 'includeSubDomains';
        }

        if ((bool) config('hunthub.security_headers.hsts.preload', false)) {
            $parts[] = 'preload';
        }

        return implode('; ', $parts);
    }
}
