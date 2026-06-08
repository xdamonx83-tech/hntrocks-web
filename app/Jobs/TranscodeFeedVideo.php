<?php

namespace App\Jobs;

use App\Models\FeedPostMedia;
use App\Models\MediaAsset;
use App\Models\Moment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TranscodeFeedVideo implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout;
    public int $tries = 1;

    public function __construct(public readonly int $mediaAssetId)
    {
        $this->onQueue((string) config('hunthub.feed_video_transcoding.queue', 'media'));
        $this->timeout = max(
            (int) config('hunthub.feed_video_transcoding.timeout', 900),
            (int) config('hunthub.moment_video_transcoding.timeout', 900)
        );
    }

    public function handle(): void
    {
        /** @var MediaAsset|null $asset */
        $asset = MediaAsset::query()->find($this->mediaAssetId);

        if (! $asset || ! $asset->isVideo() || ! $this->isSupportedContext($asset)) {
            return;
        }

        $metadataKey = $this->metadataKey($asset);
        $metadata = is_array($asset->metadata) ? $asset->metadata : [];

        if (($metadata[$metadataKey]['status'] ?? null) === 'ready') {
            $this->markMomentReadyIfNeeded($asset, $metadataKey);
            return;
        }

        if (! function_exists('exec')) {
            $this->markFailed($asset, $metadataKey, 'PHP exec() ist nicht verfügbar. ffmpeg kann nicht gestartet werden.');
            return;
        }

        $disk = Storage::disk($asset->disk);

        try {
            $inputPath = $disk->path($asset->path);
        } catch (Throwable $exception) {
            $this->markFailed($asset, $metadataKey, 'Der Storage-Disk unterstützt keinen lokalen Pfad: '.$exception->getMessage());
            return;
        }

        if (! is_file($inputPath) || ! is_readable($inputPath)) {
            $this->markFailed($asset, $metadataKey, 'Originalvideo ist nicht lesbar.');
            return;
        }

        $asset->update([
            'status' => 'processing',
            'metadata' => $this->mergeTranscodingMetadata($asset, $metadataKey, [
                'status' => 'processing',
                'started_at' => now()->toIso8601String(),
                'original_path' => $asset->path,
                'original_size_bytes' => (int) $asset->size_bytes,
            ]),
        ]);
        $this->markMomentProcessingIfNeeded($asset);

        $outputPath = $this->outputPath($asset);
        $outputFullPath = $disk->path($outputPath);
        $outputDirectory = dirname($outputFullPath);

        if (! is_dir($outputDirectory) && ! @mkdir($outputDirectory, 0775, true) && ! is_dir($outputDirectory)) {
            $this->markFailed($asset, $metadataKey, 'Zielordner für komprimiertes Video konnte nicht erstellt werden.');
            return;
        }

        @unlink($outputFullPath);

        $command = $this->buildTranscodeCommand($asset, $inputPath, $outputFullPath);

        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        if ($exitCode !== 0 || ! is_file($outputFullPath) || filesize($outputFullPath) <= 0) {
            @unlink($outputFullPath);
            $this->markFailed($asset, $metadataKey, 'ffmpeg fehlgeschlagen: '.Str::limit(implode("\n", $output), 1200));
            return;
        }

        $oldPath = $asset->path;
        $oldThumbnailPath = $asset->thumbnail_path;
        $newSize = filesize($outputFullPath) ?: 0;
        $thumbnailPath = $this->generateThumbnail($asset, $disk, $outputPath, $outputFullPath);
        $profile = $this->profileFor($asset);
        $probe = $this->probeVideo($outputFullPath, $profile);

        $asset->update([
            'path' => $outputPath,
            'thumbnail_path' => $thumbnailPath ?: $asset->thumbnail_path,
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'size_bytes' => $newSize,
            'width' => $probe['width'] ?? $asset->width,
            'height' => $probe['height'] ?? $asset->height,
            'duration_seconds' => $probe['duration_seconds'] ?? $asset->duration_seconds,
            'status' => 'ready',
            'metadata' => $this->mergeTranscodingMetadata($asset, $metadataKey, [
                'status' => 'ready',
                'finished_at' => now()->toIso8601String(),
                'original_path' => $oldPath,
                'original_size_bytes' => (int) ($asset->metadata[$metadataKey]['original_size_bytes'] ?? $asset->size_bytes),
                'transcoded_path' => $outputPath,
                'transcoded_size_bytes' => $newSize,
                'thumbnail_path' => $thumbnailPath,
                'width' => $probe['width'] ?? null,
                'height' => $probe['height'] ?? null,
                'duration_seconds' => $probe['duration_seconds'] ?? null,
                'crf' => $profile['crf'],
                'preset' => $profile['preset'],
                'fps' => $profile['fps'],
                'profile' => $asset->context === 'moments' ? 'moment_9_16' : 'feed_max_side',
            ]),
        ]);

        FeedPostMedia::query()
            ->where('media_asset_id', $asset->id)
            ->update([
                'path' => $outputPath,
                'mime_type' => 'video/mp4',
                'size_bytes' => $newSize,
            ]);

        $this->markMomentReadyIfNeeded($asset->fresh() ?? $asset, $metadataKey);
        $this->deleteOriginalFiles($disk, $oldPath, $oldThumbnailPath, $outputPath, $thumbnailPath, $asset);
    }

    private function buildTranscodeCommand(MediaAsset $asset, string $inputPath, string $outputFullPath): string
    {
        $profile = $this->profileFor($asset);
        $ffmpeg = (string) $profile['ffmpeg_binary'];
        $filter = $this->videoFilter($asset, $profile);
        $trim = $this->trimArguments($asset);

        $parts = [
            escapeshellcmd($ffmpeg),
            '-y',
        ];

        if ($trim['start'] !== null) {
            $parts[] = '-ss';
            $parts[] = (string) $trim['start'];
        }

        $parts[] = '-i';
        $parts[] = escapeshellarg($inputPath);

        if ($trim['duration'] !== null) {
            $parts[] = '-t';
            $parts[] = (string) $trim['duration'];
        }

        return implode(' ', array_merge($parts, [
            '-map', escapeshellarg('0:v:0'),
            '-map', escapeshellarg('0:a?'),
            '-vf', escapeshellarg($filter),
            '-c:v', 'libx264',
            '-preset', escapeshellarg((string) $profile['preset']),
            '-crf', (string) $profile['crf'],
            '-profile:v', 'high',
            '-level', '4.1',
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac',
            '-b:a', escapeshellarg((string) $profile['audio_bitrate']),
            '-movflags', '+faststart',
            escapeshellarg($outputFullPath),
            '2>&1',
        ]));
    }

    private function videoFilter(MediaAsset $asset, array $profile): string
    {
        $fps = max(15, min(60, (int) $profile['fps']));

        if ($asset->context === 'moments') {
            $width = max(360, (int) $profile['width']);
            $height = max(640, (int) $profile['height']);

            return "scale={$width}:{$height}:force_original_aspect_ratio=increase,crop={$width}:{$height},fps={$fps},format=yuv420p";
        }

        $maxWidth = max(240, (int) $profile['max_width']);
        $maxHeight = max(240, (int) $profile['max_height']);

        return "scale=w='min({$maxWidth},iw)':h='min({$maxHeight},ih)':force_original_aspect_ratio=decrease,fps={$fps},format=yuv420p";
    }

    private function trimArguments(MediaAsset $asset): array
    {
        if ($asset->context !== 'moments') {
            return ['start' => null, 'duration' => null];
        }

        $metadata = is_array($asset->metadata) ? $asset->metadata : [];
        $start = $metadata['trim_start_seconds'] ?? null;
        $end = $metadata['trim_end_seconds'] ?? null;
        $start = is_numeric($start) ? max(0, (int) $start) : null;
        $end = is_numeric($end) ? max(0, (int) $end) : null;

        if ($start === null && $end === null) {
            return ['start' => null, 'duration' => null];
        }

        if ($start !== null && $end !== null && $end > $start) {
            return ['start' => $start, 'duration' => $end - $start];
        }

        return ['start' => $start, 'duration' => null];
    }

    private function generateThumbnail(MediaAsset $asset, $disk, string $outputPath, string $outputFullPath): ?string
    {
        $profile = $this->profileFor($asset);

        if (! (bool) ($profile['generate_thumbnail'] ?? true)) {
            return null;
        }

        $thumbnailPath = $this->thumbnailPath($outputPath);
        $thumbnailFullPath = $disk->path($thumbnailPath);
        $thumbnailDirectory = dirname($thumbnailFullPath);

        if (! is_dir($thumbnailDirectory) && ! @mkdir($thumbnailDirectory, 0775, true) && ! is_dir($thumbnailDirectory)) {
            return null;
        }

        @unlink($thumbnailFullPath);

        $ffmpeg = (string) $profile['ffmpeg_binary'];
        $maxWidth = max(360, (int) ($profile['thumbnail_width'] ?? 720));
        $command = implode(' ', [
            escapeshellcmd($ffmpeg),
            '-y',
            '-ss', '0.35',
            '-i', escapeshellarg($outputFullPath),
            '-frames:v', '1',
            '-vf', escapeshellarg("scale='min({$maxWidth},iw)':-2"),
            '-q:v', '3',
            escapeshellarg($thumbnailFullPath),
            '2>&1',
        ]);

        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        if ($exitCode !== 0 || ! is_file($thumbnailFullPath) || filesize($thumbnailFullPath) <= 0) {
            @unlink($thumbnailFullPath);
            Log::warning('Video thumbnail generation failed.', [
                'media_asset_id' => $asset->id,
                'context' => $asset->context,
                'output' => Str::limit(implode("\n", $output), 800),
            ]);

            return null;
        }

        return $thumbnailPath;
    }

    private function probeVideo(string $path, array $profile): array
    {
        $ffprobe = (string) ($profile['ffprobe_binary'] ?? 'ffprobe');
        $command = implode(' ', [
            escapeshellcmd($ffprobe),
            '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height:format=duration',
            '-of', 'json',
            escapeshellarg($path),
            '2>&1',
        ]);

        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            return [];
        }

        $data = json_decode(implode("\n", $output), true);

        if (! is_array($data)) {
            return [];
        }

        $stream = $data['streams'][0] ?? [];
        $format = $data['format'] ?? [];
        $duration = isset($format['duration']) ? (int) round((float) $format['duration']) : null;

        return [
            'width' => isset($stream['width']) ? (int) $stream['width'] : null,
            'height' => isset($stream['height']) ? (int) $stream['height'] : null,
            'duration_seconds' => $duration ?: null,
        ];
    }

    private function markFailed(MediaAsset $asset, string $metadataKey, string $message): void
    {
        $asset->update([
            'status' => 'ready',
            'metadata' => $this->mergeTranscodingMetadata($asset, $metadataKey, [
                'status' => 'failed',
                'failed_at' => now()->toIso8601String(),
                'error' => $message,
            ]),
        ]);

        if ($asset->context === 'moments') {
            Moment::query()
                ->where('media_asset_id', $asset->id)
                ->update(['processing_status' => 'failed']);
        }

        Log::warning('Video transcoding failed.', [
            'media_asset_id' => $asset->id,
            'context' => $asset->context,
            'error' => $message,
        ]);
    }

    private function mergeTranscodingMetadata(MediaAsset $asset, string $metadataKey, array $transcoding): array
    {
        $metadata = is_array($asset->metadata) ? $asset->metadata : [];
        $metadata[$metadataKey] = array_merge($metadata[$metadataKey] ?? [], $transcoding);

        return $metadata;
    }

    private function outputPath(MediaAsset $asset): string
    {
        $directory = trim(dirname($asset->path), '.');
        $basename = pathinfo($asset->path, PATHINFO_FILENAME);

        return trim($directory, '/').'/'.$basename.'-optimized.mp4';
    }

    private function thumbnailPath(string $outputPath): string
    {
        $directory = trim(dirname($outputPath), '.');
        $basename = pathinfo($outputPath, PATHINFO_FILENAME);

        return trim($directory, '/').'/'.$basename.'-thumb.jpg';
    }

    private function deleteOriginalFiles($disk, ?string $oldPath, ?string $oldThumbnailPath, string $outputPath, ?string $thumbnailPath, MediaAsset $asset): void
    {
        $profile = $this->profileFor($asset);

        if (! (bool) ($profile['delete_original'] ?? true)) {
            return;
        }

        $paths = collect([$oldPath, $oldThumbnailPath])
            ->filter()
            ->reject(fn (string $path): bool => $path === $outputPath || $path === $thumbnailPath)
            ->unique()
            ->values()
            ->all();

        if ($paths === []) {
            return;
        }

        try {
            $disk->delete($paths);
        } catch (Throwable $exception) {
            Log::warning('Video original could not be deleted after transcoding.', [
                'media_asset_id' => $asset->id,
                'paths' => $paths,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function profileFor(MediaAsset $asset): array
    {
        if ($asset->context === 'moments') {
            return [
                'ffmpeg_binary' => (string) config('hunthub.moment_video_transcoding.ffmpeg_binary', 'ffmpeg'),
                'ffprobe_binary' => (string) config('hunthub.moment_video_transcoding.ffprobe_binary', 'ffprobe'),
                'width' => (int) config('hunthub.moment_video_transcoding.width', 720),
                'height' => (int) config('hunthub.moment_video_transcoding.height', 1280),
                'fps' => (int) config('hunthub.moment_video_transcoding.fps', 30),
                'crf' => (int) config('hunthub.moment_video_transcoding.crf', 24),
                'preset' => (string) config('hunthub.moment_video_transcoding.preset', 'medium'),
                'audio_bitrate' => (string) config('hunthub.moment_video_transcoding.audio_bitrate', '128k'),
                'thumbnail_width' => (int) config('hunthub.moment_video_transcoding.thumbnail_width', 720),
                'generate_thumbnail' => (bool) config('hunthub.moment_video_transcoding.generate_thumbnail', true),
                'delete_original' => (bool) config('hunthub.moment_video_transcoding.delete_original', true),
            ];
        }

        return [
            'ffmpeg_binary' => (string) config('hunthub.feed_video_transcoding.ffmpeg_binary', 'ffmpeg'),
            'ffprobe_binary' => (string) config('hunthub.feed_video_transcoding.ffprobe_binary', 'ffprobe'),
            'max_width' => (int) config('hunthub.feed_video_transcoding.max_width', 1280),
            'max_height' => (int) config('hunthub.feed_video_transcoding.max_height', 1280),
            'fps' => (int) config('hunthub.feed_video_transcoding.fps', 30),
            'crf' => (int) config('hunthub.feed_video_transcoding.crf', 24),
            'preset' => (string) config('hunthub.feed_video_transcoding.preset', 'medium'),
            'audio_bitrate' => (string) config('hunthub.feed_video_transcoding.audio_bitrate', '128k'),
            'thumbnail_width' => (int) config('hunthub.feed_video_transcoding.thumbnail_width', 720),
            'generate_thumbnail' => (bool) config('hunthub.feed_video_transcoding.generate_thumbnail', true),
            'delete_original' => (bool) config('hunthub.feed_video_transcoding.delete_original', true),
        ];
    }

    private function metadataKey(MediaAsset $asset): string
    {
        return $asset->context === 'moments' ? 'moment_transcoding' : 'feed_transcoding';
    }

    private function isSupportedContext(MediaAsset $asset): bool
    {
        return in_array($asset->context, ['feed', 'team_feed', 'moments'], true);
    }

    private function markMomentProcessingIfNeeded(MediaAsset $asset): void
    {
        if ($asset->context !== 'moments') {
            return;
        }

        Moment::query()
            ->where('media_asset_id', $asset->id)
            ->update(['processing_status' => 'processing']);
    }

    private function markMomentReadyIfNeeded(MediaAsset $asset, string $metadataKey): void
    {
        if ($asset->context !== 'moments') {
            return;
        }

        $metadata = is_array($asset->metadata) ? $asset->metadata : [];
        $status = (string) ($metadata[$metadataKey]['status'] ?? 'ready');

        Moment::query()
            ->where('media_asset_id', $asset->id)
            ->update([
                'processing_status' => $status === 'failed' ? 'failed' : 'ready',
                'duration_seconds' => $asset->duration_seconds,
            ]);
    }
}
