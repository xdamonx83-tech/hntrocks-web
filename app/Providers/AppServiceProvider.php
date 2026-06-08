<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Hunthub services module by module.
    }

    public function boot(): void
    {
        // Bootstrapping stays intentionally small in the foundation phase.
    }
}
