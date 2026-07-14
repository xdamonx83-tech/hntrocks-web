<?php

namespace App\Observers;

use App\Models\Cup;
use App\Services\Cups\CupFeedCrosspostService;
use Throwable;

class CupObserver
{
    public function created(Cup $cup): void
    {
        $this->sync($cup);
    }

    public function updated(Cup $cup): void
    {
        if ($cup->wasChanged(['visibility', 'title', 'team_size', 'platform', 'starts_at', 'registration_opens_at', 'registration_closes_at', 'status', 'settings'])) {
            $this->sync($cup);
        }
    }

    public function deleted(Cup $cup): void
    {
        try {
            app(CupFeedCrosspostService::class)->remove($cup);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function sync(Cup $cup): void
    {
        try {
            app(CupFeedCrosspostService::class)->sync($cup);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
