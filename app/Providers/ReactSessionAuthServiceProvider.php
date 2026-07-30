<?php

namespace App\Providers;

use App\Http\Controllers\Auth\ReactSessionAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ReactSessionAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'throttle:10,1'])
            ->get('/auth/react/session', ReactSessionAuthController::class)
            ->name('react.auth.session');
    }
}
