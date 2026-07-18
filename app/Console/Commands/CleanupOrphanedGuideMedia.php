<?php

namespace App\Console\Commands;

use App\Models\GuideMedia;
use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanupOrphanedGuideMedia extends Command
{
    protected $signature = 'hnt:guides-cleanup-media
        {--hours=48 : Minimum age of orphaned draft media}
        {--limit=100 : Maximum number of media rows per run}
        {--dry-run : List eligible media without deleting files or rows}';

    protected $description = 'Delete old guide draft media that is no longer referenced by its revision';

    public function handle(MediaService $mediaService): int
    {
        if (! Schema::hasTable('guide_media')) {
            $this->warn('guide_media table is not available. Cleanup skipped.');

            return self::SUCCESS;
        }

        $hours = max(1, (int) $this->option('hours'));
        $limit = max(1, min(500, (int) $this->option('limit')));
        $dryRun = (bool) $this->option('dry-run');
        $mediaRows = GuideMedia::query()
            ->with('mediaAsset')
            ->whereNotNull('orphaned_at')
            ->where('orphaned_at', '<=', now()->subHours($hours))
            ->oldest('orphaned_at')
            ->limit($limit)
            ->get();

        if ($mediaRows->isEmpty()) {
            $this->info('No orphaned guide media is due for cleanup.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $failed = 0;

        foreach ($mediaRows as $media) {
            $label = "GuideMedia #{$media->id} ({$media->disk}:{$media->path})";

            if ($dryRun) {
                $this->line('[DRY] '.$label);
                continue;
            }

            try {
                if ($media->mediaAsset) {
                    $mediaService->delete($media->mediaAsset);
                } else {
                    Storage::disk($media->disk)->delete($media->path);
                }

                $media->delete();
                $deleted++;
                $this->info('[OK] '.$label);
            } catch (Throwable $exception) {
                $failed++;
                $this->error('[FAILED] '.$label.' · '.$exception->getMessage());
            }
        }

        $this->line("Deleted: {$deleted}; failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
