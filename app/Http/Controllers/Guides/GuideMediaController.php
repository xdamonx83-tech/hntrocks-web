<?php

namespace App\Http\Controllers\Guides;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guides\UploadGuideMediaRequest;
use App\Models\Guide;
use App\Models\GuideMedia;
use App\Services\MediaService;
use App\Services\Guides\GuideWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GuideMediaController extends Controller
{
    public function store(
        UploadGuideMediaRequest $request,
        Guide $guide,
        GuideWorkflowService $workflow,
        MediaService $mediaService,
    ): JsonResponse {
        $revision = $workflow->ensureWorkingRevision($guide, $request->user());
        $image = $request->file('image');
        $disk = (string) config('guides.upload_disk', 'local');
        $asset = $mediaService->store($image, $request->user(), 'guides', [
            'disk' => $disk,
            'visibility' => 'private',
            'attachable' => $revision,
            'metadata' => [
                'guide_id' => $guide->id,
                'guide_revision_id' => $revision->id,
                'guide_kind' => $request->validated('kind'),
            ],
        ]);

        try {
            $media = GuideMedia::query()->create([
                'guide_id' => $guide->id,
                'revision_id' => $revision->id,
                'uploaded_by' => $request->user()->id,
                'media_asset_id' => $asset->id,
                'kind' => $request->validated('kind'),
                'disk' => $asset->disk,
                'path' => $asset->path,
                'original_name' => mb_substr($asset->original_name, 0, 255),
                'mime_type' => $asset->mime_type,
                'size_bytes' => $asset->size_bytes,
                'width' => $asset->width,
                'height' => $asset->height,
            ]);
        } catch (Throwable $exception) {
            $mediaService->delete($asset);

            throw $exception;
        }

        if ($media->kind === 'cover') {
            GuideMedia::query()
                ->whereKey($revision->cover_media_id)
                ->where('revision_id', $revision->id)
                ->update(['orphaned_at' => now()]);
            $revision->update(['cover_media_id' => $media->id]);
        }

        return response()->json([
            'ok' => true,
            'media' => [
                'id' => $media->id,
                'kind' => $media->kind,
                'url' => route('guides.media.show', $media),
                'width' => $media->width,
                'height' => $media->height,
            ],
        ], 201);
    }

    public function show(Request $request, GuideMedia $media): StreamedResponse
    {
        $media->loadMissing(['guide.publishedRevision', 'guide.author']);
        $guide = $media->guide;
        abort_unless($guide instanceof Guide, 404);
        $viewer = $request->user();
        $canManage = $viewer && ($guide->isOwnedBy($viewer) || $viewer->isAdmin());
        $publishedRevision = $guide->publishedRevision;
        $publishedMediaIds = collect((array) $publishedRevision?->content_blocks)
            ->where('type', 'image')
            ->pluck('media_id')
            ->map(fn ($id): int => (int) $id)
            ->push((int) $publishedRevision?->cover_media_id)
            ->filter()
            ->unique();

        abort_unless($canManage || ($guide->isPublished() && $publishedMediaIds->contains((int) $media->id)), 404);
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        return Storage::disk($media->disk)->response(
            $media->path,
            null,
            [
                'Content-Type' => $media->mime_type,
                'Cache-Control' => $guide->isPublished() ? 'public, max-age=86400' : 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
