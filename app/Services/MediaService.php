<?php

namespace App\Services;

use App\Jobs\TranscodeFeedVideo;
use App\Models\MediaAsset;
use App\Models\Moment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MediaService
{
    public function __construct(private readonly MediaModerationService $mediaModeration)
    {
    }

    public function assertAllowed(UploadedFile $file, ?User $user = null, string $context = 'library'): void
    {
        $this->mediaModeration->assertAllowed($file, $user, $context);
    }

    public function store(UploadedFile $file, User $user, string $context = 'library', array $options = []): MediaAsset
    {
        $this->assertAllowed($file, $user, $context);

        $visibility = $options['visibility'] ?? 'registered';
        $disk = $options['disk'] ?? $this->defaultDiskFor($context, (string) $visibility);
        $attachable = $options['attachable'] ?? null;
        $metadata = $options['metadata'] ?? [];

        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin'));
        $type = $this->typeFromMimeType((string) $mimeType);
        $directory = trim($context, '/').'/'.date('Y/m');
        $filename = (string) Str::uuid().'.'.$extension;
        $path = $file->storeAs($directory, $filename, $disk);

        $storedFile = $this->prepareStoredFile($disk, $path, $context, $type, (string) $mimeType);
        $path = $storedFile['path'] ?? $path;
        $mimeType = $storedFile['mime_type'] ?? $mimeType;
        $extension = $storedFile['extension'] ?? $extension;
        $width = $storedFile['width'];
        $height = $storedFile['height'];
        $sizeBytes = $storedFile['size_bytes'] ?? $file->getSize() ?: 0;

        if (! empty($storedFile['metadata'])) {
            $metadata = array_merge($metadata, $storedFile['metadata']);
        }

        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'attachable_type' => $attachable instanceof Model ? $attachable->getMorphClass() : null,
            'attachable_id' => $attachable instanceof Model ? $attachable->getKey() : null,
            'context' => $context,
            'disk' => $disk,
            'path' => $path,
            'thumbnail_path' => null,
            'type' => $type,
            'mime_type' => $mimeType,
            'original_name' => $file->getClientOriginalName(),
            'extension' => $extension,
            'size_bytes' => $sizeBytes,
            'width' => $width,
            'height' => $height,
            'duration_seconds' => null,
            'visibility' => $visibility,
            'status' => 'ready',
            'alt_text' => $options['alt_text'] ?? null,
            'metadata' => $metadata ?: null,
        ]);

        $this->queueVideoTranscoding($asset);

        return $asset;
    }

    public function attach(MediaAsset $asset, Model $attachable): MediaAsset
    {
        $asset->update([
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
        ]);

        return $asset->fresh() ?? $asset;
    }

    public function syncMomentMediaVisibility(Moment $moment, string $visibility): void
    {
        if (! in_array($visibility, ['public', 'registered', 'private'], true)) {
            throw new RuntimeException('Ungültige Moment-Sichtbarkeit.');
        }

        $moment->loadMissing(['media', 'cover']);
        $targetDisk = $visibility === 'public' ? 'public' : 'local';

        collect([$moment->media, $moment->cover])
            ->filter()
            ->unique(fn (MediaAsset $asset): int => (int) $asset->id)
            ->each(function (MediaAsset $asset) use ($targetDisk, $visibility): void {
                $this->relocateAsset($asset, $targetDisk);

                if ($asset->visibility !== $visibility) {
                    $asset->update(['visibility' => $visibility]);
                }
            });
    }

    public function relocateAsset(MediaAsset $asset, string $targetDisk): MediaAsset
    {
        if (! in_array($targetDisk, ['public', 'local'], true)) {
            throw new RuntimeException('Ungültiger Ziel-Disk für MediaAsset.');
        }

        $sourceDisk = (string) $asset->disk;
        if ($sourceDisk === $targetDisk) {
            return $asset;
        }

        $source = Storage::disk($sourceDisk);
        $target = Storage::disk($targetDisk);
        $paths = array_values(array_unique(array_filter([$asset->path, $asset->thumbnail_path])));
        $createdTargets = [];

        try {
            foreach ($paths as $path) {
                if (! $source->exists($path)) {
                    throw new RuntimeException("Quelldatei fehlt: {$sourceDisk}:{$path}");
                }

                $sourceSize = $source->size($path);
                if ($target->exists($path)) {
                    if ($target->size($path) !== $sourceSize) {
                        throw new RuntimeException("Zieldatei existiert mit anderer Größe: {$targetDisk}:{$path}");
                    }

                    continue;
                }

                $stream = $source->readStream($path);
                if ($stream === false) {
                    throw new RuntimeException("Quelldatei konnte nicht gelesen werden: {$sourceDisk}:{$path}");
                }

                try {
                    if (! $target->writeStream($path, $stream)) {
                        throw new RuntimeException("Zieldatei konnte nicht geschrieben werden: {$targetDisk}:{$path}");
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                $createdTargets[] = $path;

                if (! $target->exists($path) || $target->size($path) !== $sourceSize) {
                    throw new RuntimeException("Zieldatei konnte nicht verifiziert werden: {$targetDisk}:{$path}");
                }
            }

            $asset->update(['disk' => $targetDisk]);

            if ($paths !== [] && ! $source->delete($paths)) {
                throw new RuntimeException("Quelldateien konnten nach erfolgreichem Kopieren nicht gelöscht werden: {$sourceDisk}");
            }
        } catch (Throwable $exception) {
            if ((string) $asset->disk === $sourceDisk && $createdTargets !== []) {
                $target->delete($createdTargets);
            }

            throw $exception;
        }

        return $asset->fresh() ?? $asset;
    }

    public function delete(MediaAsset $asset): void
    {
        $paths = array_values(array_filter([$asset->path, $asset->thumbnail_path]));

        if ($paths !== []) {
            Storage::disk($asset->disk)->delete($paths);
        }

        $asset->update(['status' => 'deleted']);
        $asset->delete();
    }

    private function defaultDiskFor(string $context, string $visibility): string
    {
        if (
            in_array($context, ['moments', 'moments_cover', 'moment_studio_source'], true)
            && $visibility !== 'public'
        ) {
            return 'local';
        }

        return 'public';
    }

    private function queueVideoTranscoding(MediaAsset $asset): void
    {
        if (! $asset->isVideo()) {
            return;
        }

        $metadataKey = match ($asset->context) {
            'feed', 'team_feed' => 'feed_transcoding',
            'moments' => 'moment_transcoding',
            default => null,
        };

        if ($metadataKey === null) {
            return;
        }

        if (in_array($asset->context, ['feed', 'team_feed'], true) && ! (bool) config('hunthub.feed_video_transcoding.enabled', true)) {
            return;
        }

        if ($asset->context === 'moments' && ! (bool) config('hunthub.moment_video_transcoding.enabled', true)) {
            return;
        }

        $metadata = is_array($asset->metadata) ? $asset->metadata : [];
        $metadata[$metadataKey] = array_merge($metadata[$metadataKey] ?? [], [
            'status' => 'queued',
            'queued_at' => now()->toIso8601String(),
            'original_path' => $asset->path,
            'original_size_bytes' => (int) $asset->size_bytes,
        ]);

        $asset->update([
            'status' => 'processing',
            'metadata' => $metadata,
        ]);

        TranscodeFeedVideo::dispatchAfterResponse($asset->id);
    }

    private function typeFromMimeType(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            default => 'file',
        };
    }

    private function prepareStoredFile(string $disk, string $path, string $context, string $type, string $mimeType): array
    {
        $info = [
            'path' => $path,
            'mime_type' => $mimeType,
            'extension' => strtolower((string) pathinfo($path, PATHINFO_EXTENSION)),
            'width' => null,
            'height' => null,
            'size_bytes' => $this->storedFileSize($disk, $path),
            'metadata' => [],
        ];

        if ($type !== 'image') {
            return $info;
        }

        $imageInfo = $this->imageInfoFromStorage($disk, $path);
        if ($imageInfo !== null) {
            $info['width'] = $imageInfo['width'];
            $info['height'] = $imageInfo['height'];
        }

        if (! $this->shouldOptimizeImage($context, $mimeType, $imageInfo['type'] ?? null)) {
            return $info;
        }

        $optimized = $this->optimizeStoredImage($disk, $path, $imageInfo);
        if ($optimized === null) {
            $info['size_bytes'] = $this->storedFileSize($disk, $path) ?? $info['size_bytes'];
            $imageInfo = $this->imageInfoFromStorage($disk, $path);
            if ($imageInfo !== null) {
                $info['width'] = $imageInfo['width'];
                $info['height'] = $imageInfo['height'];
            }

            return $info;
        }

        $info['path'] = $optimized['path'];
        $info['mime_type'] = $optimized['mime_type'];
        $info['extension'] = $optimized['extension'];
        $info['width'] = $optimized['width'];
        $info['height'] = $optimized['height'];
        $info['size_bytes'] = $optimized['size_bytes'];
        $info['metadata'] = [
            'image_optimization' => [
                'status' => $optimized['converted_to_webp'] ? 'converted_to_webp' : 'optimized',
                'converted_to_webp' => $optimized['converted_to_webp'],
                'original_path' => $optimized['original_path'],
                'optimized_path' => $optimized['path'],
                'original_mime_type' => $optimized['original_mime_type'],
                'optimized_mime_type' => $optimized['mime_type'],
                'original_size_bytes' => $optimized['original_size_bytes'],
                'optimized_size_bytes' => $optimized['size_bytes'],
                'original_width' => $optimized['original_width'],
                'original_height' => $optimized['original_height'],
                'width' => $optimized['width'],
                'height' => $optimized['height'],
            ],
        ];

        return $info;
    }

    private function shouldOptimizeImage(string $context, string $mimeType, ?int $imageType): bool
    {
        if (! (bool) config('hunthub.media_image_optimization.enabled', true)) {
            return false;
        }

        $excludedContexts = config('hunthub.media_image_optimization.excluded_contexts', ['cups/screenshots']);
        if (in_array(trim($context, '/'), array_map(static fn ($value): string => trim((string) $value, '/'), (array) $excludedContexts), true)) {
            return false;
        }

        $mimeType = strtolower($mimeType);
        if (in_array($mimeType, ['image/gif', 'image/svg+xml'], true)) {
            return false;
        }

        $canReadSource = match ($imageType) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg'),
            IMAGETYPE_PNG => function_exists('imagecreatefrompng'),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp'),
            default => false,
        };

        if (! $canReadSource) {
            return false;
        }

        if ((bool) config('hunthub.media_image_optimization.convert_to_webp', true)) {
            return function_exists('imagewebp');
        }

        return match ($imageType) {
            IMAGETYPE_JPEG => function_exists('imagejpeg'),
            IMAGETYPE_PNG => function_exists('imagepng'),
            IMAGETYPE_WEBP => function_exists('imagewebp'),
            default => false,
        };
    }

    private function optimizeStoredImage(string $disk, string $path, ?array $imageInfo): ?array
    {
        if ($imageInfo === null) {
            return null;
        }

        $absolutePath = $this->absoluteStoragePath($disk, $path);
        if ($absolutePath === null || ! is_file($absolutePath) || ! is_writable($absolutePath)) {
            return null;
        }

        $originalWidth = (int) $imageInfo['width'];
        $originalHeight = (int) $imageInfo['height'];
        $sourcePixels = $originalWidth * $originalHeight;
        if ($sourcePixels <= 0 || $sourcePixels > (int) config('hunthub.media_image_optimization.max_source_pixels', 32000000)) {
            return null;
        }

        $originalBytes = filesize($absolutePath) ?: 0;
        if ($originalBytes <= 0) {
            return null;
        }

        $source = $this->createImageResource($absolutePath, (int) $imageInfo['type']);
        if ($source === null) {
            return null;
        }

        $source = $this->applyJpegOrientation($source, $absolutePath, (int) $imageInfo['type']);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $maxWidth = (int) config('hunthub.media_image_optimization.max_width', 1920);
        $maxHeight = (int) config('hunthub.media_image_optimization.max_height', 1920);
        $scale = min(1, $maxWidth / max(1, $sourceWidth), $maxHeight / max(1, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $wasResized = $targetWidth !== $sourceWidth || $targetHeight !== $sourceHeight;

        $targetImageType = (bool) config('hunthub.media_image_optimization.convert_to_webp', true)
            ? IMAGETYPE_WEBP
            : (int) $imageInfo['type'];
        $targetRelativePath = $this->optimizedImagePath($path, $targetImageType);
        $targetAbsolutePath = $this->absoluteStoragePath($disk, $targetRelativePath);
        if ($targetAbsolutePath === null) {
            imagedestroy($source);
            return null;
        }

        $canvas = $source;
        if ($wasResized) {
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($canvas === false) {
                imagedestroy($source);
                return null;
            }

            $this->prepareAlphaChannel($canvas, $targetImageType);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        }

        $temporaryPath = $targetAbsolutePath.'.opt-'.Str::random(8).'.tmp';
        $saved = $this->writeImageResource($canvas, $temporaryPath, $targetImageType);

        if ($canvas !== $source) {
            imagedestroy($canvas);
        }
        imagedestroy($source);

        if (! $saved || ! is_file($temporaryPath)) {
            @unlink($temporaryPath);
            return null;
        }

        $optimizedBytes = filesize($temporaryPath) ?: 0;
        $minSavings = (int) config('hunthub.media_image_optimization.min_savings_bytes', 32768);
        $savedEnough = $optimizedBytes > 0 && ($originalBytes - $optimizedBytes) >= $minSavings;
        $convertedToWebp = $targetImageType === IMAGETYPE_WEBP && (int) $imageInfo['type'] !== IMAGETYPE_WEBP;
        $conversionReducedSize = $convertedToWebp && $optimizedBytes > 0 && $optimizedBytes < $originalBytes;

        if (! $wasResized && ! $savedEnough && ! $conversionReducedSize) {
            @unlink($temporaryPath);
            return null;
        }

        if (! @rename($temporaryPath, $targetAbsolutePath)) {
            @unlink($temporaryPath);
            return null;
        }

        if ($targetAbsolutePath !== $absolutePath && is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        clearstatcache(true, $targetAbsolutePath);

        return [
            'path' => $targetRelativePath,
            'mime_type' => $this->mimeTypeForImageType($targetImageType),
            'extension' => $this->extensionForImageType($targetImageType),
            'original_path' => $path,
            'original_mime_type' => $this->mimeTypeForImageType((int) $imageInfo['type']),
            'converted_to_webp' => $convertedToWebp,
            'original_size_bytes' => $originalBytes,
            'size_bytes' => filesize($targetAbsolutePath) ?: $optimizedBytes,
            'original_width' => $originalWidth,
            'original_height' => $originalHeight,
            'width' => $targetWidth,
            'height' => $targetHeight,
        ];
    }

    private function optimizedImagePath(string $path, int $imageType): string
    {
        $extension = $this->extensionForImageType($imageType);
        if ($extension === '') {
            return $path;
        }

        $directory = trim((string) pathinfo($path, PATHINFO_DIRNAME), '.');
        $filename = (string) pathinfo($path, PATHINFO_FILENAME);
        $optimizedFilename = $filename.'.'.$extension;

        return $directory === '' ? $optimizedFilename : $directory.'/'.$optimizedFilename;
    }

    private function extensionForImageType(int $imageType): string
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => '',
        };
    }

    private function mimeTypeForImageType(int $imageType): string
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_WEBP => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    private function createImageResource(string $path, int $imageType): mixed
    {
        $resource = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => false,
        };

        return $resource ?: null;
    }

    private function writeImageResource(mixed $resource, string $path, int $imageType): bool
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => @imagejpeg($resource, $path, (int) config('hunthub.media_image_optimization.jpeg_quality', 82)),
            IMAGETYPE_PNG => @imagepng($resource, $path, (int) config('hunthub.media_image_optimization.png_compression', 7)),
            IMAGETYPE_WEBP => @imagewebp($resource, $path, (int) config('hunthub.media_image_optimization.webp_quality', 82)),
            default => false,
        };
    }

    private function applyJpegOrientation(mixed $source, string $path, int $imageType): mixed
    {
        if ($imageType !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $source;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($source, 180, 0),
            6 => imagerotate($source, -90, 0),
            8 => imagerotate($source, 90, 0),
            default => false,
        };

        if ($rotated === false) {
            return $source;
        }

        imagedestroy($source);

        return $rotated;
    }

    private function prepareAlphaChannel(mixed $image, int $imageType): void
    {
        if (! in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
        }
    }

    private function imageInfoFromStorage(string $disk, string $path): ?array
    {
        $absolutePath = $this->absoluteStoragePath($disk, $path);
        if ($absolutePath === null || ! is_file($absolutePath)) {
            return null;
        }

        $size = @getimagesize($absolutePath);
        if (! is_array($size)) {
            return null;
        }

        return [
            'width' => (int) ($size[0] ?? 0) ?: null,
            'height' => (int) ($size[1] ?? 0) ?: null,
            'type' => (int) ($size[2] ?? 0),
        ];
    }

    private function storedFileSize(string $disk, string $path): ?int
    {
        try {
            return Storage::disk($disk)->size($path);
        } catch (\Throwable) {
            return null;
        }
    }

    private function absoluteStoragePath(string $disk, string $path): ?string
    {
        try {
            return Storage::disk($disk)->path($path);
        } catch (\Throwable) {
            return null;
        }
    }

    private function imageDimensions(UploadedFile $file, string $type): array
    {
        if ($type !== 'image') {
            return [null, null];
        }

        $size = @getimagesize($file->getRealPath());

        if (! is_array($size)) {
            return [null, null];
        }

        return [(int) ($size[0] ?? 0) ?: null, (int) ($size[1] ?? 0) ?: null];
    }
}
