<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Models\Moment;
use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SecureMomentMedia extends Command
{
    protected $signature = 'hnt:moments-secure-media {--apply : Dateien wirklich auf den zur Sichtbarkeit passenden Disk verschieben}';

    protected $description = 'Prüft bzw. sichert Moment-Medien gegen direkten öffentlichen Zugriff.';

    public function handle(MediaService $mediaService): int
    {
        $apply = (bool) $this->option('apply');
        $checked = 0;
        $changed = 0;
        $errors = 0;

        Moment::query()
            ->withTrashed()
            ->with(['media', 'cover'])
            ->orderBy('id')
            ->chunkById(100, function ($moments) use ($mediaService, $apply, &$checked, &$changed, &$errors): void {
                foreach ($moments as $moment) {
                    $targetDisk = $moment->visibility === 'public' ? 'public' : 'local';

                    foreach (collect([$moment->media, $moment->cover])->filter()->unique('id') as $asset) {
                        $checked++;
                        $needsChange = $asset->disk !== $targetDisk || $asset->visibility !== $moment->visibility;

                        if (! $needsChange) {
                            continue;
                        }

                        $changed++;
                        if (! $this->sourceFilesExist($asset)) {
                            $errors++;
                            $this->error("Moment #{$moment->id} / Media #{$asset->id}: mindestens eine Quelldatei fehlt.");
                            continue;
                        }

                        $this->line(sprintf(
                            '%s Moment #%d / Media #%d: %s -> %s, visibility %s -> %s',
                            $apply ? 'APPLY' : 'DRY-RUN',
                            $moment->id,
                            $asset->id,
                            $asset->disk,
                            $targetDisk,
                            $asset->visibility,
                            $moment->visibility,
                        ));

                        if (! $apply) {
                            continue;
                        }

                        try {
                            $mediaService->relocateAsset($asset, $targetDisk);
                            if ($asset->visibility !== $moment->visibility) {
                                $asset->update(['visibility' => $moment->visibility]);
                            }
                        } catch (Throwable $exception) {
                            $errors++;
                            $this->error("Moment #{$moment->id} / Media #{$asset->id}: {$exception->getMessage()}");
                        }
                    }
                }
            });

        MediaAsset::query()
            ->where('context', 'moment_studio_source')
            ->where('visibility', '!=', 'public')
            ->where('disk', '!=', 'local')
            ->orderBy('id')
            ->chunkById(100, function ($assets) use ($mediaService, $apply, &$checked, &$changed, &$errors): void {
                foreach ($assets as $asset) {
                    $checked++;
                    $changed++;

                    if (! $this->sourceFilesExist($asset)) {
                        $errors++;
                        $this->error("Studio-Quelle Media #{$asset->id}: mindestens eine Quelldatei fehlt.");
                        continue;
                    }

                    $this->line(sprintf(
                        '%s Studio-Quelle Media #%d: %s -> local',
                        $apply ? 'APPLY' : 'DRY-RUN',
                        $asset->id,
                        $asset->disk,
                    ));

                    if (! $apply) {
                        continue;
                    }

                    try {
                        $mediaService->relocateAsset($asset, 'local');
                    } catch (Throwable $exception) {
                        $errors++;
                        $this->error("Studio-Quelle Media #{$asset->id}: {$exception->getMessage()}");
                    }
                }
            });

        $this->info("Geprüft: {$checked}; abweichend: {$changed}; Fehler: {$errors}; Modus: ".($apply ? 'APPLY' : 'DRY-RUN'));

        return $errors === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function sourceFilesExist(MediaAsset $asset): bool
    {
        $disk = Storage::disk($asset->disk);

        foreach (array_values(array_unique(array_filter([$asset->path, $asset->thumbnail_path]))) as $path) {
            if (! $disk->exists($path)) {
                return false;
            }
        }

        return true;
    }
}
