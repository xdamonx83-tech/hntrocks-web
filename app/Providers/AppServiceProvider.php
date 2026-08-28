<?php

namespace App\Providers;

use App\Models\Guide;
use App\Models\UserProfile;
use App\Observers\UserProfileObserver;
use App\Policies\GuidePolicy;
use App\Services\UserBlockService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(UserBlockService::class);
    }

    public function boot(): void
    {
        Gate::policy(Guide::class, GuidePolicy::class);
        UserProfile::observe(UserProfileObserver::class);
    }
}
