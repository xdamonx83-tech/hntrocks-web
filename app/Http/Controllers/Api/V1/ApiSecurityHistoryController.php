<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserSecurityEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSecurityHistoryController extends Controller
{
    private const LOGIN_EVENTS = [
        'api_login_success',
        'api_login_failed',
        'api_login_blocked_suspended',
        'api_two_factor_success',
        'api_two_factor_failed',
        'mobile_social_login_success',
        'native_google_login_success',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $events = UserSecurityEvent::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('event', self::LOGIN_EVENTS)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(function (UserSecurityEvent $event): array {
                [$device, $browser] = $this->describeUserAgent((string) $event->user_agent);

                return [
                    'id' => (int) $event->id,
                    'event' => (string) $event->event,
                    'status' => $this->statusFor((string) $event->event),
                    'ip_address' => $event->ip_address ?: null,
                    'user_agent' => $event->user_agent ?: null,
                    'device' => $device,
                    'browser' => $browser,
                    'occurred_at' => $event->created_at?->toISOString(),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Login history loaded.',
            'events' => $events,
        ]);
    }

    private function statusFor(string $event): string
    {
        if (str_contains($event, 'blocked')) {
            return 'blocked';
        }

        if (str_contains($event, 'failed')) {
            return 'failed';
        }

        if (str_contains($event, 'success')) {
            return 'success';
        }

        return 'unknown';
    }

    private function describeUserAgent(string $userAgent): array
    {
        $agent = strtolower($userAgent);

        $device = match (true) {
            str_contains($agent, 'iphone') => 'iPhone',
            str_contains($agent, 'ipad') => 'iPad',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'macintosh'), str_contains($agent, 'mac os') => 'macOS',
            str_contains($agent, 'linux') => 'Linux',
            default => 'unknown',
        };

        $browser = match (true) {
            str_contains($agent, 'edg/') => 'Microsoft Edge',
            str_contains($agent, 'opr/'), str_contains($agent, 'opera') => 'Opera',
            str_contains($agent, 'firefox/') => 'Firefox',
            str_contains($agent, 'chrome/') => 'Chrome',
            str_contains($agent, 'safari/') => 'Safari',
            $userAgent === '' => 'unknown',
            default => 'App/API',
        };

        return [$device, $browser];
    }
}
