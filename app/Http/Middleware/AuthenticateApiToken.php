<?php

namespace App\Http\Middleware;

use App\Models\ApiAccessToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = ApiAccessToken::findValidPlainToken($request->bearerToken());

        if (! $token || ! $token->user) {
            // Read-only public Guide endpoints. All drafts, editor endpoints
            // and every write operation remain token-protected.
            if ($request->isMethod('GET') && (
                $request->is('api/v1/app/remote-config')
                || $request->routeIs(
                    'api.v1.guides.index',
                    'api.v1.guides.show',
                    'api.v1.guides.comments.index',
                    'api.v1.guides.media.show',
                )
            )) {
                return $next($request);
            }

            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_access_token', $token);

        return $next($request);
    }
}
