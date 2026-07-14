<?php

namespace App\Providers;

use App\Models\UserProfile;
use App\Observers\UserProfileObserver;
use App\Services\Cups\CommunityCupSubmissionAnalysisService;
use App\Services\Cups\CupSubmissionAnalysisService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CupSubmissionAnalysisService::class,
            CommunityCupSubmissionAnalysisService::class
        );
    }

    public function boot(): void
    {
        UserProfile::observe(UserProfileObserver::class);
    }
}
