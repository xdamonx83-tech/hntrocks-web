<?php

namespace App\Observers;

use App\Models\FeedPost;
use App\Services\Teams\TeamActivityRecorder;

class FeedPostTeamActivityObserver
{
    public function __construct(private readonly TeamActivityRecorder $recorder) {}

    public function created(FeedPost $post): void
    {
        $this->recorder->recordPost($post);
    }
}
