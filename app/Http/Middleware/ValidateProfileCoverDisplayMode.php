<?php

namespace App\Http\Middleware;

use App\Models\UserProfile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ValidateProfileCoverDisplayMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('profile.update') && $request->has('cover_display_mode')) {
            $request->validate([
                'cover_display_mode' => [
                    'required',
                    'string',
                    Rule::in(UserProfile::COVER_DISPLAY_MODES),
                ],
            ]);
        }

        return $next($request);
    }
}
