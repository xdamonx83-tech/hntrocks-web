<?php

namespace App\Providers;

use App\Http\Controllers\Admin\AdminArcadeGameController;
use App\Http\Controllers\Admin\AdminArcadeGameReleaseController;
use App\Http\Controllers\Api\V1\Arcade\ArcadeLaunchTicketController;
use App\Http\Middleware\ArcadeDynamicExchangeCors;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ArcadeDynamicPublisherServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                Route::get('/arcade-games/create', [AdminArcadeGameController::class, 'create'])->name('arcade-games.create');
                Route::post('/arcade-games', [AdminArcadeGameController::class, 'store'])->name('arcade-games.store');
                Route::post('/arcade-games/{game:key}/releases', [AdminArcadeGameReleaseController::class, 'store'])->name('arcade-games.releases.store');
                Route::post('/arcade-games/{game:key}/releases/{release}/publish', [AdminArcadeGameReleaseController::class, 'publish'])->name('arcade-games.releases.publish');
                Route::post('/arcade-games/{game:key}/releases/{release}/retire', [AdminArcadeGameReleaseController::class, 'retire'])->name('arcade-games.releases.retire');
            });

        Route::middleware('api')
            ->prefix('api/v1')
            ->name('api.v1.')
            ->group(function (): void {
                Route::options('/arcade/launch-tickets/exchange', fn () => response()->noContent())
                    ->middleware(ArcadeDynamicExchangeCors::class);

                Route::post('/arcade/launch-tickets/exchange', [ArcadeLaunchTicketController::class, 'exchange'])
                    ->middleware([ArcadeDynamicExchangeCors::class, 'throttle:60,1'])
                    ->name('arcade.launch-tickets.exchange');

                Route::middleware('api.token')->group(function (): void {
                    Route::post('/arcade/games/{game}/launch-tickets', [ArcadeLaunchTicketController::class, 'store'])
                        ->where('game', '[A-Za-z0-9-]+')
                        ->middleware('throttle:30,1')
                        ->name('arcade.games.launch-tickets.store');
                });
            });
    }
}
