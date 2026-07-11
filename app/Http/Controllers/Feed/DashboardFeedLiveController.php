<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Admin\AdminThemePreviewController;
use App\Http\Controllers\Controller;
use App\Http\Middleware\PreviewDashboardCommunity;
use App\Http\Middleware\PreviewDashboardHeader;
use App\Http\Middleware\PreviewDashboardNoFlash;
use App\Http\Middleware\PreviewDashboardStreak;
use App\Services\Economy\CrownDailyStreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DashboardFeedLiveController extends Controller
{
    public function __invoke(Request $request): SymfonyResponse
    {
        $viewer = $request->user();

        abort_unless($viewer, 401);

        if ($request->boolean('dashboard_community')) {
            return response()->json([
                'community' => $this->invokePrivate(
                    app(PreviewDashboardCommunity::class),
                    'communityPayload',
                    [$viewer]
                ),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        if ($request->boolean('dashboard_header')) {
            return response()->json([
                'header' => $this->invokePrivate(
                    app(PreviewDashboardHeader::class),
                    'payload',
                    [$viewer]
                ),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        if ($request->boolean('dashboard_agenda')) {
            return response()->json([
                'agenda' => $this->invokePrivate(
                    app(PreviewDashboardStreak::class),
                    'agendaPayload',
                    [$viewer]
                ),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        if ($request->boolean('dashboard_streak')) {
            return response()->json([
                'daily_streak' => app(CrownDailyStreakService::class)->status($viewer),
                'activity' => $this->invokePrivate(
                    app(PreviewDashboardStreak::class),
                    'activityPayload',
                    [(int) $viewer->id]
                ),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        if ($request->boolean('data')) {
            /** @var JsonResponse $response */
            $response = $this->invokePrivate(
                app(AdminThemePreviewController::class),
                'dashboardFeedData',
                [$request]
            );

            return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return $this->renderDashboard($request);
    }

    private function renderDashboard(Request $request): Response
    {
        $viewer = $request->user();
        $viewerName = $viewer->name ?: ($viewer->username ?: 'HNT Hunter');
        $viewerHandle = $viewer->username ? '@'.$viewer->username : '@hunter';
        $viewerAvatar = $viewer->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');

        $html = view('themes.hnt_preview.feed.live')->render();

        $assets = [
            'real-feed.css',
            'real-feed-live.css',
            'real-feed-polish.css',
            'real-feed-comments.css',
        ];

        $scripts = [
            'real-feed-live.js',
            'real-feed.js',
            'real-feed-polish.js',
            'real-feed-comments.js',
        ];

        $html = str_replace(
            [
                'Hello Valentina',
                '>Valentina<',
                '@valentina',
                asset('assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg'),
                '<title>HNT.rocks — Feed Preview</title>',
            ],
            [
                'Hello '.e($viewerName),
                '>'.e($viewerName).'<',
                e($viewerHandle),
                e($viewerAvatar),
                '<title>HNT.rocks — Feed</title>',
            ],
            $html
        );

        $styles = '<meta name="csrf-token" content="'.e(csrf_token()).'">';
        foreach ($assets as $asset) {
            $path = public_path('assets/themes/hnt_preview/dashboard-feed/'.$asset);
            $version = is_file($path) ? filemtime($path) : time();
            $styles .= '<link href="'.asset('assets/themes/hnt_preview/dashboard-feed/'.$asset).'?v='.$version.'" rel="stylesheet">';
        }

        $javascript = '';
        foreach ($scripts as $script) {
            $path = public_path('assets/themes/hnt_preview/dashboard-feed/'.$script);
            $version = is_file($path) ? filemtime($path) : time();
            $javascript .= '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/'.$script).'?v='.$version.'"></script>';
        }

        $html = str_replace('</head>', $styles.'</head>', $html);
        $html = str_replace('</body>', $javascript.'</body>', $html);

        $response = response($html)
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');

        return $this->applyNoFlash($request, $response);
    }

    private function applyNoFlash(Request $request, Response $response): Response
    {
        $route = $request->route();

        if (! is_object($route) || ! method_exists($route, 'getAction') || ! method_exists($route, 'setAction')) {
            return $response;
        }

        $originalAction = $route->getAction();
        $previewAction = $originalAction;
        $previewAction['as'] = 'admin.theme-preview.shell';
        $route->setAction($previewAction);

        try {
            /** @var Response $enhanced */
            $enhanced = app(PreviewDashboardNoFlash::class)->handle(
                $request,
                static fn (Request $ignored): Response => $response
            );

            return $enhanced;
        } finally {
            $route->setAction($originalAction);
        }
    }

    private function invokePrivate(object $instance, string $method, array $arguments = []): mixed
    {
        if (! method_exists($instance, $method)) {
            throw new RuntimeException(sprintf(
                'Dashboard feed dependency %s::%s is unavailable.',
                $instance::class,
                $method
            ));
        }

        $reflection = new ReflectionMethod($instance, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($instance, $arguments);
    }
}
