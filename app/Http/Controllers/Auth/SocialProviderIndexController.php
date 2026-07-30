<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\SocialProviderService;
use Illuminate\Http\JsonResponse;
use Throwable;

final class SocialProviderIndexController
{
    /**
     * Return the providers that should be shown by the React login screen.
     *
     * The four core providers stay visible so the login layout remains stable.
     * Optional providers are only returned when they are fully usable.
     */
    public function __invoke(SocialProviderService $socialProviders): JsonResponse
    {
        $coreProviders = ['google', 'discord', 'twitch', 'microsoft'];
        $optionalProviders = ['steam', 'facebook'];
        $providers = [];

        foreach ([...$coreProviders, ...$optionalProviders] as $provider) {
            $available = true;

            try {
                $socialProviders->assertUsable($provider);
            } catch (Throwable) {
                $available = false;
            }

            if (in_array($provider, $optionalProviders, true) && ! $available) {
                continue;
            }

            $providers[] = [
                'key' => $provider,
                'available' => $available,
                'redirect_url' => url("/auth/{$provider}/redirect"),
            ];
        }

        return response()->json([
            'providers' => $providers,
        ]);
    }
}
