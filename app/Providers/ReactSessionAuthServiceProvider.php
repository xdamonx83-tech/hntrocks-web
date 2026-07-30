<?php

namespace App\Providers;

use App\Http\Controllers\Auth\ReactSessionAuthController;
use App\Http\Controllers\Auth\SocialProviderIndexController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ReactSessionAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'throttle:60,1'])
            ->get('/auth/social/providers', SocialProviderIndexController::class)
            ->name('react.auth.social-providers');

        Route::middleware(['web', 'auth', 'throttle:10,1'])
            ->get('/auth/react/session', ReactSessionAuthController::class)
            ->name('react.auth.session');
    }
}
