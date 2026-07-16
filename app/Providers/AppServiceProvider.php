<?php

namespace App\Providers;

use App\Models\Cup;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\FeedReaction;
use App\Models\Team;
use App\Models\UserProfile;
use App\Observers\CupObserver;
use App\Observers\FeedCommentTeamActivityObserver;
use App\Observers\FeedPostTeamActivityObserver;
use App\Observers\FeedReactionTeamActivityObserver;
use App\Observers\TeamProgressionObserver;
use App\Observers\UserProfileObserver;
use App\Policies\TeamPolicy;
use App\Services\Cups\CommunityCupSubmissionAnalysisService;
use App\Services\Cups\CupSubmissionAnalysisService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Gate::policy(Team::class, TeamPolicy::class);
        UserProfile::observe(UserProfileObserver::class);
        Cup::observe(CupObserver::class);
        FeedPost::observe(FeedPostTeamActivityObserver::class);
        FeedComment::observe(FeedCommentTeamActivityObserver::class);
        FeedReaction::observe(FeedReactionTeamActivityObserver::class);
        Team::observe(TeamProgressionObserver::class);
    }
}
