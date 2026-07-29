<?php

namespace App\Providers;

use App\Http\Controllers\Api\V1\ApiProfileBadgeEarnersController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ProfileBadgeEarnersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['api', 'api.token'])
            ->prefix('api/v1')
            ->name('api.v1.')
            ->group(function (): void {
                Route::get('/users/{user:username}/profile-badge-earners', ApiProfileBadgeEarnersController::class)
                    ->name('users.profile-badge-earners.index');
            });
    }
}
