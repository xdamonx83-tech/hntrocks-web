<?php

namespace App\Observers;

use App\Models\FeedComment;
use App\Services\Teams\TeamActivityRecorder;

class FeedCommentTeamActivityObserver
{
    public function __construct(private readonly TeamActivityRecorder $recorder) {}

    public function created(FeedComment $comment): void
    {
        $this->recorder->recordComment($comment);
    }
}
