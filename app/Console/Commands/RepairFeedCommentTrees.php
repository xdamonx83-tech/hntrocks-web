<?php

namespace App\Console\Commands;

use App\Services\Feed\FeedCommentTreeService;
use Illuminate\Console\Command;

class RepairFeedCommentTrees extends Command
{
    protected $signature = 'feed:repair-comment-trees
        {--post-id= : Optional FeedPost ID}
        {--execute : Soft-delete invalid active reply trees instead of dry-run only}';

    protected $description = 'Find and optionally soft-delete active feed replies whose root parent is missing, deleted or invalid.';

    public function handle(FeedCommentTreeService $commentTrees): int
    {
        $postIdOption = $this->option('post-id');
        $postId = null;

        if ($postIdOption !== null && $postIdOption !== '') {
            if (! ctype_digit((string) $postIdOption) || (int) $postIdOption < 1) {
                $this->error('--post-id must be a positive integer.');

                return self::FAILURE;
            }

            $postId = (int) $postIdOption;
        }

        $invalidIds = $commentTrees->invalidActiveCommentIds($postId);

        $this->line('Invalid active feed comment rows: '.$invalidIds->count());

        if ($invalidIds->isNotEmpty()) {
            $this->line('Sample IDs: '.$invalidIds->take(25)->implode(', '));
        }

        if (! $this->option('execute')) {
            $this->warn('DRY-RUN only. No comments were changed. Use --execute after review.');

            return self::SUCCESS;
        }

        $deleted = $commentTrees->repairInvalidTrees($postId);
        $remaining = $commentTrees->invalidActiveCommentIds($postId);

        $this->info('Soft-deleted invalid feed comment rows: '.$deleted);

        if ($remaining->isNotEmpty()) {
            $this->error('Repair incomplete. Remaining invalid IDs: '.$remaining->take(25)->implode(', '));

            return self::FAILURE;
        }

        $this->info('Repair complete. No invalid active feed replies remain in scope.');

        return self::SUCCESS;
    }
}
