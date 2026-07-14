<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Cups\DashboardCupDetailLiveController;
use App\Http\Controllers\Cups\DashboardCupsLiveController;
use App\Http\Controllers\Feed\DashboardFeedLiveController;
use App\Models\Cup;
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
            if (! $request->user()) {
                return $next($request);
            }

            $request->attributes->set('hnt_dashboard_feed_live', true);

            return app(DashboardCupsLiveController::class)($request);
        }

        if ($request->routeIs('cups.show', 'cups.show.section')) {
            if (! $request->user()) {
                return $next($request);
            }

            $cup = $request->route('cup');
            if (! $cup instanceof Cup) {
                return $next($request);
            }

            $request->attributes->set('hnt_dashboard_feed_live', true);
            $section = (string) ($request->route('section') ?: 'overview');

            return app(DashboardCupDetailLiveController::class)($request, $cup, $section);
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
