<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Admin\AdminThemePreviewController;
use App\Http\Controllers\Controller;
use App\Http\Middleware\PreviewDashboardCommunity;
use App\Http\Middleware\PreviewDashboardHeader;
use App\Http\Middleware\PreviewDashboardNoFlash;
use App\Http\Middleware\PreviewDashboardStreak;
use App\Models\User;
use App\Services\Economy\CrownDailyStreakService;
use App\Support\DashboardProgressPayload;
use App\Support\DashboardPrototypeLocalizer;
use App\Support\DashboardPrototypeSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DashboardFeedLiveController extends Controller
{
    private const SHARED_HEADER_PLACEHOLDER = '<!-- HNT_SHARED_HEADER_COMPONENT -->';

    public function __invoke(Request $request): SymfonyResponse
    {
        $viewer = $request->user();

        abort_unless($viewer, 401);

        $seenAt = now();

        // Every live dashboard request is itself a reliable presence signal.
        DB::table('users')
            ->where('id', $viewer->id)
            ->update(['last_seen_at' => $seenAt]);

        if ($request->boolean('dashboard_community')) {
            $community = $this->invokePrivate(
                app(PreviewDashboardCommunity::class),
                'communityPayload',
                [$viewer]
            );

            $onlineWindowSeconds = max(User::ONLINE_WINDOW_SECONDS, 180);
            $community['online_now'] = max(
                1,
                User::query()
                    ->whereNotNull('last_seen_at')
                    ->where('last_seen_at', '>=', $seenAt->copy()->subSeconds($onlineWindowSeconds))
                    ->count()
            );

            return response()->json([
                'community' => $community,
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

        if ($request->boolean('dashboard_progress')) {
            return response()->json([
                'progress' => app(DashboardProgressPayload::class)->forUser($viewer),
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
        /** @var User $viewer */
        $viewer = $request->user();
        $viewerName = $viewer->name ?: ($viewer->username ?: 'HNT Hunter');
        $viewerHandle = $viewer->username ? '@'.$viewer->username : '@hunter';
        $viewerAvatar = $viewer->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
        $greeting = __('hnt_preview.dashboard.hello', ['name' => $viewerName]);

        $initialHeader = $this->invokePrivate(
            app(PreviewDashboardHeader::class),
            'payload',
            [$viewer]
        );
        $initialProgress = app(DashboardProgressPayload::class)->forUser($viewer);
        $initialStreak = app(CrownDailyStreakService::class)->status($viewer);

        $html = view('themes.hnt_preview.feed.live')->render();

        $assets = [
            'real-feed.css',
            'real-feed-live.css',
            'real-feed-polish.css',
            'real-feed-comments.css',
            'real-feed-likes.css',
        ];

        $scripts = [
            'real-feed-live.js',
            'real-feed.js',
            'real-dashboard-progress-live.js',
            'real-feed-polish.js',
            'real-feed-comments.js',
            'real-feed-likes.js',
            'real-feed-content-badges.js',
            'real-feed-i18n.js',
            'real-feed-functionality.js',
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
                e($greeting),
                '>'.e($viewerName).'<',
                e($viewerHandle),
                e($viewerAvatar),
                '<title>HNT.rocks — Feed</title>',
            ],
            $html
        );

        // The feed still uses its prototype sanitizer for feed content, but the
        // shared topbar is detached first and restored unchanged afterwards.
        // This keeps the exact same Blade component, CSS and JavaScript on every
        // page instead of rebuilding the header inside the feed response.
        [$html, $sharedHeader] = $this->detachSharedHeader($html);

        $html = app(DashboardPrototypeSanitizer::class)->sanitize(
            $html,
            $viewer,
            $initialHeader,
            $initialProgress,
            $initialStreak
        );
        $html = app(DashboardPrototypeLocalizer::class)->localize($html);

        if ($sharedHeader !== null) {
            $html = str_replace(self::SHARED_HEADER_PLACEHOLDER, $sharedHeader, $html);
        }

        $dashboardI18n = json_encode(
            trans('hnt_preview.dashboard'),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?: '{}';

        $styles = '<meta name="csrf-token" content="'.e(csrf_token()).'">'
            .'<script>window.HNT_DASHBOARD_FEED_LIVE=true;window.HNT_DASHBOARD_I18N='.$dashboardI18n.';</script>';

        foreach ($assets as $asset) {
            $path = public_path('assets/themes/hnt_preview/dashboard-feed/'.$asset);
            $version = is_file($path) ? filemtime($path) : time();
            $styles .= '<link href="'.asset('assets/themes/hnt_preview/dashboard-feed/'.$asset).'?v='.$version.'" rel="stylesheet">';
        }

        $javascript = '';
        foreach ($scripts as $script) {
            $path = public_path('assets/themes/hnt_preview/dashboard-feed/'.$script);
            $version = is_file($path) ? filemtime($path) : time();
            $progressAttribute = $script === 'real-dashboard-progress-live.js'
                ? ' data-real-dashboard-progress="1"'
                : '';
            $javascript .= '<script'.$progressAttribute.' src="'.asset('assets/themes/hnt_preview/dashboard-feed/'.$script).'?v='.$version.'"></script>';
        }

        $heartbeatPath = public_path('assets/socialite/js/hnt-presence-heartbeat.js');
        $heartbeatVersion = is_file($heartbeatPath) ? filemtime($heartbeatPath) : time();
        $javascript .= '<script src="'.asset('assets/socialite/js/hnt-presence-heartbeat.js').'?v='.$heartbeatVersion.'"></script>';

        $presenceSyncPath = public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-presence-sync.js');
        $presenceSyncVersion = is_file($presenceSyncPath) ? filemtime($presenceSyncPath) : time();
        $javascript .= '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-presence-sync.js').'?v='.$presenceSyncVersion.'"></script>';

        $html = str_replace('</head>', $styles.'</head>', $html);
        $html = str_replace('</body>', $javascript.'</body>', $html);

        $response = response($html)
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');

        return $this->applyNoFlash($request, $response);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function detachSharedHeader(string $html): array
    {
        if (! preg_match(
            '~<header\b[^>]*data-hnt-shared-header[^>]*>~i',
            $html,
            $openingMatch,
            PREG_OFFSET_CAPTURE
        )) {
            return [$html, null];
        }

        $start = (int) $openingMatch[0][1];
        $tail = substr($html, $start);

        if (! preg_match_all('~</?header\b[^>]*>~i', $tail, $tagMatches, PREG_OFFSET_CAPTURE)) {
            return [$html, null];
        }

        $depth = 0;
        foreach ($tagMatches[0] as [$tag, $relativeOffset]) {
            $isClosingTag = str_starts_with(strtolower($tag), '</header');
            $depth += $isClosingTag ? -1 : 1;

            if ($depth !== 0) {
                continue;
            }

            $end = $start + (int) $relativeOffset + strlen($tag);
            $sharedHeader = substr($html, $start, $end - $start);
            $withoutHeader = substr_replace(
                $html,
                self::SHARED_HEADER_PLACEHOLDER,
                $start,
                $end - $start
            );

            return [$withoutHeader, $sharedHeader];
        }

        return [$html, null];
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
