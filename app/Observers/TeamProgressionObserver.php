<?php

namespace App\Observers;

use App\Models\Team;
use App\Services\Teams\TeamProgressionService;

class TeamProgressionObserver
{
    public function __construct(private readonly TeamProgressionService $progression) {}

    public function created(Team $team): void
    {
        $this->progression->progressionFor($team);
    }
}
