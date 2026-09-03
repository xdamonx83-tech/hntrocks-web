<?php

namespace App\Providers;

use App\Http\Controllers\Api\V1\Arcade\ArcadeMatchController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ArcadeLifecycleRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['api', 'api.token'])
            ->prefix('api/v1')
            ->name('api.v1.')
            ->group(function (): void {
                Route::post('/arcade/matches/{match}/forfeit', [ArcadeMatchController::class, 'forfeit'])
                    ->middleware('throttle:20,1')
                    ->name('arcade.matches.forfeit');
            });
    }
}
