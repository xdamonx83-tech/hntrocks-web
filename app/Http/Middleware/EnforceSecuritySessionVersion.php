<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSecuritySessionVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $currentVersion = (int) ($user->security_session_version ?? 0);
        $sessionVersion = $request->session()->get('security_session_version');

        if ($sessionVersion === null && $currentVersion === 0) {
            $request->session()->put('security_session_version', 0);

            return $next($request);
        }

        if ($sessionVersion === null || (int) $sessionVersion !== $currentVersion) {
            return $this->endInvalidatedSession($request);
        }

        return $next($request);
    }

    private function endInvalidatedSession(Request $request): Response
    {
        $message = __('settings.security_session_expired');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()->route('login')->withErrors(['login' => $message]);
    }
}
