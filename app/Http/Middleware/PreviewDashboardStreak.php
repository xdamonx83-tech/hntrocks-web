<?php

namespace App\Http\Middleware;

use App\Services\Economy\CrownDailyStreakService;
use App\Support\HntTheme;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreviewDashboardStreak
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('admin.theme-preview.shell') || ! $request->boolean('dashboard_streak')) {
            return $next($request);
        }

        $user = $request->user();

        abort_unless(
            $user?->isAdmin() && HntTheme::previewActive($user),
            403
        );

        return response()->json([
            'daily_streak' => app(CrownDailyStreakService::class)->status($user),
        ]);
    }
}
