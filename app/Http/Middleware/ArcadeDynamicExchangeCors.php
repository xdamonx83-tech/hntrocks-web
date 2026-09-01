<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ArcadeDynamicExchangeCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigin = rtrim((string) config('arcade.dynamic_web_origin', 'https://games.hnt.rocks'), '/');

        if ($origin !== null && ! hash_equals($allowedOrigin, $origin)) {
            abort(403, 'Origin is not allowed for Arcade launch ticket exchange.');
        }

        $response = $request->isMethod('OPTIONS') ? response()->noContent() : $next($request);

        if ($origin === $allowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept');
            $response->headers->set('Access-Control-Max-Age', '300');
            $response->headers->set('Vary', 'Origin');
        }

        return $response;
    }
}
