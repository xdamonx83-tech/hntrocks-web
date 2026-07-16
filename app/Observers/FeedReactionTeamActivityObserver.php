<?php

namespace App\Observers;

use App\Models\FeedReaction;
use App\Services\Teams\TeamActivityRecorder;

class FeedReactionTeamActivityObserver
{
    public function __construct(private readonly TeamActivityRecorder $recorder) {}

    public function created(FeedReaction $reaction): void
    {
        $this->recorder->recordReaction($reaction);
    }
}
