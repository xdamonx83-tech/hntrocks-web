<?php

namespace App\Providers;

use App\Models\Cup;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\FeedReaction;
use App\Models\Guide;
use App\Models\Team;
use App\Models\UserProfile;
use App\Observers\CupObserver;
use App\Observers\FeedCommentTeamActivityObserver;
use App\Observers\FeedPostTeamActivityObserver;
use App\Observers\FeedReactionTeamActivityObserver;
use App\Observers\TeamProgressionObserver;
use App\Observers\UserProfileObserver;
use App\Policies\TeamPolicy;
use App\Policies\GuidePolicy;
use App\Services\Cups\CommunityCupSubmissionAnalysisService;
use App\Services\UserBlockService;
use App\Services\Cups\CupSubmissionAnalysisService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(UserBlockService::class);

        $this->app->bind(
            CupSubmissionAnalysisService::class,
            CommunityCupSubmissionAnalysisService::class
        );
    }

    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if (request()->hasSession()) {
                request()->session()->put(
                    'security_session_version',
                    (int) ($event->user->security_session_version ?? 0)
                );
            }
        });

        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Guide::class, GuidePolicy::class);
        UserProfile::observe(UserProfileObserver::class);
        Cup::observe(CupObserver::class);
        FeedPost::observe(FeedPostTeamActivityObserver::class);
        FeedComment::observe(FeedCommentTeamActivityObserver::class);
        FeedReaction::observe(FeedReactionTeamActivityObserver::class);
        Team::observe(TeamProgressionObserver::class);
    }
}
