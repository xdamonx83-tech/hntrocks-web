<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Feed\DashboardFeedLiveController;
use App\Support\HntTheme;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivateDashboardFeed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! HntTheme::dashboardFeedLive()) {
            return $next($request);
        }

        if ($request->routeIs('cups.index')) {
            if ($request->user()) {
                $request->attributes->set('hnt_dashboard_feed_live', true);
            }

            return $next($request);
        }

        if ($request->routeIs('feed.show') && $request->boolean('hnt_preview_comments')) {
            abort_unless($request->user(), 401);
            $request->attributes->set('hnt_dashboard_feed_live', true);

            return $next($request);
        }

        if (! $request->routeIs('feed.index')) {
            return $next($request);
        }

        abort_unless($request->user(), 401);

        $request->attributes->set('hnt_dashboard_feed_live', true);

        return app(DashboardFeedLiveController::class)($request);
    }
}
