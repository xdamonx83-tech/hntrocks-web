<?php

namespace App\Providers;

use App\Http\Controllers\Api\V1\Arcade\ArcadeStatsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ArcadeStatsRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api/v1')
            ->name('api.v1.')
            ->group(function (): void {
                Route::get('/arcade/games/{game}/leaderboard', [ArcadeStatsController::class, 'leaderboard'])
                    ->where('game', '[A-Za-z0-9_-]+')
                    ->name('arcade.games.leaderboard');

                Route::middleware('api.token')
                    ->get('/arcade/games/{game}/stats/me', [ArcadeStatsController::class, 'mine'])
                    ->where('game', '[A-Za-z0-9_-]+')
                    ->name('arcade.games.stats.me');
            });
    }
}
