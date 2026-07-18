<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSecurityEvent;
use Illuminate\Http\Request;

class SecurityLogService
{
    public function record(?User $user, string $event, Request $request, array $meta = []): void
    {
        $location = $this->approximateLocation($request);

        if ($location !== null && ! array_key_exists('location', $meta)) {
            $meta['location'] = $location;
        }

        UserSecurityEvent::create([
            'user_id' => $user?->id,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'meta' => $meta ?: null,
        ]);
    }

    private function approximateLocation(Request $request): ?array
    {
        $location = [
            'country_code' => $this->firstHeader($request, [
                'CF-IPCountry',
                'CloudFront-Viewer-Country',
                'X-Vercel-IP-Country',
                'X-AppEngine-Country',
            ]),
            'region' => $this->firstHeader($request, [
                'CF-Region',
                'CloudFront-Viewer-Country-Region-Name',
                'X-Vercel-IP-Country-Region',
                'X-AppEngine-Region',
            ]),
            'region_code' => $this->firstHeader($request, [
                'CF-Region-Code',
                'CloudFront-Viewer-Country-Region',
            ]),
            'city' => $this->firstHeader($request, [
                'CF-IPCity',
                'CloudFront-Viewer-City',
                'X-Vercel-IP-City',
                'X-AppEngine-City',
            ]),
        ];

        $location = array_filter($location, static fn (?string $value): bool => filled($value));

        return $location === [] ? null : $location;
    }

    private function firstHeader(Request $request, array $names): ?string
    {
        foreach ($names as $name) {
            $value = trim(rawurldecode((string) $request->header($name, '')));
            $value = str_replace(["\r", "\n"], '', $value);

            if ($value !== '') {
                return mb_substr($value, 0, 120);
            }
        }

        return null;
    }
}
