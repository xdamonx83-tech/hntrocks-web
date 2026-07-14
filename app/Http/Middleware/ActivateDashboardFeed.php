<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Cups\CommunityCupReviewController;
use App\Http\Controllers\Cups\CommunityCupStoreController;
use App\Http\Controllers\Cups\DashboardCupCreateLiveController;
use App\Http\Controllers\Cups\DashboardCupDetailLiveController;
use App\Http\Controllers\Cups\DashboardCupsLiveController;
use App\Http\Controllers\Feed\DashboardFeedLiveController;
use App\Models\Cup;
use App\Models\CupSubmission;
use App\Support\CupOrganizerAccess;
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

        if ($request->routeIs('cups.create')) {
            $request->attributes->set('hnt_dashboard_feed_live', true);

            return app(DashboardCupCreateLiveController::class)($request);
        }

        if ($request->routeIs('cups.store')) {
            $request->attributes->set('hnt_dashboard_feed_live', true);

            return app()->call(
                [app(CommunityCupStoreController::class), '__invoke'],
                ['request' => $request]
            );
        }

        if ($request->routeIs(
            'cups.submissions.screenshot',
            'cups.submissions.approve',
            'cups.submissions.reject',
            'cups.submissions.manual-score',
            'cups.submissions.rescore'
        )) {
            $cup = $this->resolveCup($request);
            $submission = $this->resolveSubmission($request);

            if (! $cup || ! $submission) {
                return $next($request);
            }

            if ($request->routeIs('cups.submissions.rescore')) {
                abort_unless(
                    CupOrganizerAccess::canUseAi($request->user())
                    && CupOrganizerAccess::usesAi($cup),
                    403
                );

                return $next($request);
            }

            $controller = app(CommunityCupReviewController::class);

            return match (true) {
                $request->routeIs('cups.submissions.screenshot') => $controller->screenshot($request, $cup, $submission),
                $request->routeIs('cups.submissions.approve') => app()->call([$controller, 'approve'], compact('request', 'cup', 'submission')),
                $request->routeIs('cups.submissions.reject') => app()->call([$controller, 'reject'], compact('request', 'cup', 'submission')),
                $request->routeIs('cups.submissions.manual-score') => $controller->manualScore($request, $cup, $submission),
                default => $next($request),
            };
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

            $cup = $this->resolveCup($request);
            if (! $cup) {
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

    private function resolveCup(Request $request): ?Cup
    {
        $parameter = $request->route('cup');

        if ($parameter instanceof Cup) {
            return $parameter;
        }

        return Cup::query()->where('slug', (string) $parameter)->first();
    }

    private function resolveSubmission(Request $request): ?CupSubmission
    {
        $parameter = $request->route('submission');

        if ($parameter instanceof CupSubmission) {
            return $parameter;
        }

        return CupSubmission::query()->find($parameter);
    }
}
