<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web'])
                ->group(base_path('routes/view-mode.php'));

            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/guides.php'));

            Route::middleware(['api', 'api.token', 'block.visibility'])
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api-guides.php'));

            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/rocks.php'));

            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/ready-lobbies.php'));
        },
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            'hh_cookie_consent',
            'hh_vid',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\ResolveHntViewMode::class,
            \App\Http\Middleware\EnforceSecuritySessionVersion::class,
            \App\Http\Middleware\EnforceUserBlockVisibility::class,
            \App\Http\Middleware\TrackVisitorEvent::class,
            \App\Http\Middleware\AddSecurityHeaders::class,
            \App\Http\Middleware\InjectThemePilot::class,
            \App\Http\Middleware\AppendCupCrosspostsToDashboardFeed::class,
            \App\Http\Middleware\ActivateDashboardFeed::class,
            \App\Http\Middleware\ActivateHallOfFameRedesign::class,
            \App\Http\Middleware\ActivateProfileRedesign::class,
            \App\Http\Middleware\ActivateProfileEditRedesign::class,
            \App\Http\Middleware\ValidateProfileCoverDisplayMode::class,
            \App\Http\Middleware\PreviewDashboardStreak::class,
            \App\Http\Middleware\PreviewDashboardCommunity::class,
            \App\Http\Middleware\PreviewDashboardHeader::class,
            \App\Http\Middleware\PreviewDashboardNoFlash::class,
        ]);

        $middleware->alias([
            'api.token' => \App\Http\Middleware\AuthenticateApiToken::class,
            'block.visibility' => \App\Http\Middleware\EnforceUserBlockVisibility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Central exception handling will be defined once the core modules exist.
    })->create();
