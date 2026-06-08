<?php

namespace App\Http\Middleware;

use App\Models\VisitorEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackVisitorEvent
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        try {
            $this->track($request);
        } catch (Throwable) {
            // Analytics must never break the application response.
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! (bool) config('hunthub.visitor_tracking.enabled', true)) {
            return false;
        }

        if (! Schema::hasTable('visitor_events')) {
            return false;
        }

        if (! $this->hasAnalyticsConsent($request)) {
            Cookie::queue(Cookie::forget('hh_vid'));

            return false;
        }

        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }

        $path = trim($request->path(), '/');

        if ($path === 'up' || str_starts_with($path, 'api/') || str_starts_with($path, 'broadcasting/')) {
            return false;
        }

        if (preg_match('/\.(?:css|js|png|jpe?g|gif|webp|svg|ico|map|json|xml|txt|woff2?|ttf|eot|mp4|webm|mov|m4v|mp3|wav)$/i', $path)) {
            return false;
        }

        return true;
    }

    private function hasAnalyticsConsent(Request $request): bool
    {
        if (! (bool) config('hunthub.visitor_tracking.require_consent', true)) {
            return true;
        }

        $consent = (string) $request->cookies->get('hh_cookie_consent', '');

        return str_contains($consent, 'analytics=1');
    }

    private function track(Request $request): void
    {
        $routeName = $request->route()?->getName();
        $path = '/' . ltrim($request->path(), '/');
        $dedupeKey = 'hh_visit_tracked_' . sha1($path . '|' . (string) $routeName);
        $lastTrackedAt = (int) $request->session()->get($dedupeKey, 0);

        if ($lastTrackedAt > 0 && (time() - $lastTrackedAt) < 1800) {
            return;
        }

        $visitorId = (string) $request->cookies->get('hh_vid');

        if (! Str::isUuid($visitorId)) {
            $visitorId = (string) Str::uuid();

            Cookie::queue(cookie(
                'hh_vid',
                $visitorId,
                60 * 24 * 365,
                null,
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            ));
        }

        $visitorHash = $this->hashValue($visitorId);
        $sessionHash = $request->hasSession() ? $this->hashValue($request->session()->getId()) : null;
        $ipHash = $request->ip() ? $this->hashValue($request->ip()) : null;
        $userAgentHash = $request->userAgent() ? $this->hashValue($request->userAgent()) : null;
        $countryCode = $this->countryCode($request);
        $referrerHost = $this->referrerHost($request);
        $isFirstVisit = ! VisitorEvent::where('visitor_hash', $visitorHash)->exists();

        VisitorEvent::create([
            'user_id' => $request->user()?->id,
            'visitor_hash' => $visitorHash,
            'session_hash' => $sessionHash,
            'ip_hash' => $ipHash,
            'country_code' => $countryCode,
            'country_name' => $countryCode ? $this->countryName($countryCode) : null,
            'source' => $this->source($request, $referrerHost),
            'referrer_host' => $referrerHost,
            'path' => mb_substr($path, 0, 255),
            'route_name' => $routeName ? mb_substr((string) $routeName, 0, 255) : null,
            'user_agent_hash' => $userAgentHash,
            'is_first_visit' => $isFirstVisit,
            'occurred_at' => now(),
        ]);

        $request->session()->put($dedupeKey, time());
    }

    private function hashValue(string $value): string
    {
        return hash('sha256', config('app.key') . '|' . $value);
    }

    private function countryCode(Request $request): ?string
    {
        $code = strtoupper((string) (
            $request->headers->get('CF-IPCountry')
            ?: $request->headers->get('X-Appengine-Country')
            ?: $request->headers->get('CloudFront-Viewer-Country')
            ?: ''
        ));

        if ($code === '' || $code === 'XX' || ! preg_match('/^[A-Z]{2}$/', $code)) {
            return null;
        }

        return $code;
    }

    private function countryName(string $countryCode): ?string
    {
        return [
            'AR' => 'Argentina',
            'AT' => 'Austria',
            'BR' => 'Brazil',
            'CA' => 'Canada',
            'CH' => 'Switzerland',
            'DE' => 'Germany',
            'ES' => 'Spain',
            'FR' => 'France',
            'GB' => 'United Kingdom',
            'IN' => 'India',
            'IT' => 'Italy',
            'NL' => 'Netherlands',
            'RU' => 'Russia',
            'TR' => 'Turkey',
            'US' => 'United States',
        ][$countryCode] ?? $countryCode;
    }

    private function referrerHost(Request $request): ?string
    {
        $referrer = (string) $request->headers->get('referer', '');
        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? mb_strtolower($host) : null;
    }

    private function source(Request $request, ?string $referrerHost): string
    {
        if (! $referrerHost) {
            return 'direct';
        }

        $currentHost = mb_strtolower((string) $request->getHost());

        if ($referrerHost === $currentHost || str_ends_with($referrerHost, '.' . $currentHost)) {
            return 'internal';
        }

        return match (true) {
            str_contains($referrerHost, 'google.') => 'google',
            str_contains($referrerHost, 'facebook.') || str_contains($referrerHost, 'fb.') => 'facebook',
            str_contains($referrerHost, 'instagram.') => 'instagram',
            str_contains($referrerHost, 'tiktok.') => 'tiktok',
            str_contains($referrerHost, 'discord.') => 'discord',
            str_contains($referrerHost, 'youtube.') || str_contains($referrerHost, 'youtu.be') => 'youtube',
            str_contains($referrerHost, 'bing.') => 'bing',
            default => mb_substr($referrerHost, 0, 100),
        };
    }
}
