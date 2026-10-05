<?php

namespace App\Services\Maps;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashSpotImageOptimizer
{
    /**
     * Accept an original screenshot, strip metadata, resize and re-encode
     * server-side. Only the optimized, private image is persisted.
     *
     * @return array{path: string, mime_type: string, size: int}
     */
    public function store(UploadedFile $image): array
    {
        $realPath = $image->getRealPath();
        $info = is_string($realPath) ? @getimagesize($realPath) : false;
        $mime = is_array($info) ? ($info['mime'] ?? null) : null;
        $width = is_array($info) ? (int) $info[0] : 0;
        $height = is_array($info) ? (int) $info[1] : 0;

        // Bound decoded pixel count to prevent memory exhaustion from
        // hostile images. Original file size is separately checked by Laravel.
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
            || $width < 1 || $height < 1
            || $width > 8000 || $height > 8000
            || $width * $height > 24000000) {
            throw ValidationException::withMessages([
                'image' => 'Invalid image or excessive image dimensions.',
            ]);
        }

        // The web client already compresses screenshots to WebP. Servers
        // without GD may accept that bounded, already normalized format.
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            if ($mime !== 'image/webp' || $image->getSize() > 4 * 1024 * 1024) {
                throw ValidationException::withMessages([
                    'image' => 'This image must be compressed to WebP before upload.',
                ]);
            }

            $path = $image->storeAs(
                'maps/cash-spot-submissions', Str::uuid().'.webp', 'local',
            );
            if (! is_string($path) || $path === '') {
                throw new \RuntimeException('Could not store cash-spot image.');
            }

            return ['path' => $path, 'mime_type' => 'image/webp', 'size' => (int) $image->getSize()];
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($realPath),
            'image/png' => @imagecreatefrompng($realPath),
            'image/webp' => @imagecreatefromwebp($realPath),
        };

        if (! $source) {
            throw ValidationException::withMessages(['image' => 'Image could not be decoded.']);
        }

        $scale = min(1, 2560 / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        try {
            // Screenshots are opaque. Fill transparent regions safely before
            // encoding as WebP; never retain EXIF or other input metadata.
            imagefill($target, 0, 0, imagecolorallocate($target, 15, 22, 25));
            if (! imagecopyresampled(
                $target, $source, 0, 0, 0, 0,
                $targetWidth, $targetHeight, $width, $height,
            )) {
                throw new \RuntimeException('Image resizing failed.');
            }

            ob_start();
            try {
                $encoded = imagewebp($target, null, 84);
                $binary = ob_get_contents();
            } finally {
                ob_end_clean();
            }

            if (! $encoded || ! is_string($binary) || $binary === '') {
                throw new \RuntimeException('Image conversion to WebP failed.');
            }

            $path = 'maps/cash-spot-submissions/'.Str::uuid().'.webp';
            if (! Storage::disk('local')->put($path, $binary)) {
                throw new \RuntimeException('Could not store cash-spot image.');
            }

            return [
                'path' => $path,
                'mime_type' => 'image/webp',
                'size' => strlen($binary),
            ];
        } finally {
            imagedestroy($target);
            imagedestroy($source);
        }
    }
}
