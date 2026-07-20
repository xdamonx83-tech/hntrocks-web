<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Cups\HallOfFameLiveController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivateHallOfFameRedesign
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('hall-of-fame.index')) {
            return $next($request);
        }

        return app(HallOfFameLiveController::class)();
    }
}
