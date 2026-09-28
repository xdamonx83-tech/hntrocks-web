<?php

namespace App\Http\Controllers\Moments;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\Moment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MomentMediaController extends Controller
{
    public function __invoke(Moment $moment, MediaAsset $asset, string $variant): StreamedResponse
    {
        abort_unless(in_array($variant, ['original', 'thumbnail'], true), 404);
        abort_unless($moment->visibility !== 'public', 404);
        abort_unless($asset->visibility === $moment->visibility, 404);
        abort_unless(in_array($asset->context, ['moments', 'moments_cover'], true), 404);
        abort_unless(in_array($asset->status, ['ready', 'processing'], true), 404);
        abort_unless(
            $asset->attachable_type === $moment->getMorphClass()
                && (int) $asset->attachable_id === (int) $moment->id,
            404
        );
        abort_unless(
            in_array((int) $asset->id, array_filter([
                (int) $moment->media_asset_id,
                $moment->cover_media_asset_id ? (int) $moment->cover_media_asset_id : null,
            ]), true),
            404
        );

        $path = $variant === 'thumbnail' ? $asset->thumbnail_path : $asset->path;
        abort_unless(is_string($path) && $path !== '', 404);

        $disk = Storage::disk($asset->disk);
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path)
            ?: ($variant === 'thumbnail' ? 'image/jpeg' : ($asset->mime_type ?: 'application/octet-stream'));

        return $disk->response($path, $asset->original_name, [
            'Content-Type' => (string) $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ], 'inline');
    }
}
