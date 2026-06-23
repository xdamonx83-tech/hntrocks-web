<?php

namespace App\Console\Commands;

use App\Services\LiveLobbyFeedbackService;
use Illuminate\Console\Command;

class QueueLiveLobbyFeedback extends Command
{
    protected $signature = 'live-lobbies:queue-feedback';

    protected $description = 'Create, expire and notify Ready Lobby after-hunt feedback requests';

    public function handle(LiveLobbyFeedbackService $feedback): int
    {
        $stats = $feedback->queueEligibleLobbies();

        $this->line('created_requests: '.$stats['created_requests']);
        $this->line('notifications_sent: '.$stats['notifications_sent']);
        $this->line('expired_requests: '.$stats['expired_requests']);

        return self::SUCCESS;
    }
}
