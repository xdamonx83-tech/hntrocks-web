<?php

namespace App\Console\Commands;

use App\Models\Cup;
use App\Services\Cups\CupFeedCrosspostService;
use Illuminate\Console\Command;

class BackfillCupFeedCrossposts extends Command
{
    protected $signature = 'cups:crosspost-feed
        {--include-ended : Include finished and archived public cups}';

    protected $description = 'Create missing system feed crossposts for public cups.';

    public function handle(CupFeedCrosspostService $crossposts): int
    {
        $query = Cup::query()
            ->where('visibility', 'public')
            ->orderBy('id');

        if (! $this->option('include-ended')) {
            $query->whereIn('status', ['planned', 'active']);
        }

        $created = 0;
        $skipped = 0;

        $query->chunkById(100, function ($cups) use ($crossposts, &$created, &$skipped): void {
            foreach ($cups as $cup) {
                $before = $cup->feedCupCard()->exists();
                $post = $crossposts->publish($cup);

                if ($post && ! $before) {
                    $created++;
                } else {
                    $skipped++;
                }
            }
        });

        $this->info(sprintf('Cup crossposts created: %d; unchanged/skipped: %d', $created, $skipped));

        return self::SUCCESS;
    }
}
