<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MediaAssetResource;
use App\Models\MediaAsset;
use App\Models\NewsArticle;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class NewsArticleMediaController extends Controller
{
    public function store(Request $request, MediaService $mediaService): JsonResponse
    {
        $kind = $request->input('kind');
        $image = $kind === 'image';
        $video = $kind === 'video';
        $limit = $image
            ? (int) config('hunthub.upload_limits.news_image_kb', 20 * 1024)
            : (int) config('hunthub.upload_limits.news_video_kb', 200 * 1024);

        $fileRules = ['required', 'file', 'max:'.$limit];
        if ($image) {
            $fileRules = [...$fileRules, 'image', 'mimetypes:image/jpeg,image/png,image/webp,image/avif', 'mimes:jpg,jpeg,png,webp,avif'];
        } elseif ($video) {
            $fileRules = [...$fileRules, 'mimetypes:video/mp4,video/quicktime,video/webm', 'mimes:mp4,mov,webm'];
        }

        $validated = $request->validate([
            'kind' => ['required', Rule::in(['image', 'video'])],
            'file' => $fileRules,
            'alt_text' => ['nullable', 'string', 'max:300'],
            'article_id' => ['nullable', 'integer', 'exists:news_articles,id'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $options = [
            'disk' => 'local',
            'visibility' => 'private',
            'alt_text' => $validated['alt_text'] ?? null,
            'metadata' => ['purpose' => 'news-editor', 'media_kind' => $validated['kind']],
        ];

        if (! empty($validated['article_id'])) {
            $options['attachable'] = NewsArticle::query()->findOrFail($validated['article_id']);
        }

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $asset = $mediaService->store($file, $user, 'news', $options);

        return MediaAssetResource::make($asset)->response()->setStatusCode(201);
    }

    public function showAdmin(MediaAsset $asset): MediaAssetResource
    {
        abort_unless($asset->context === 'news' && $asset->visibility === 'private' && $asset->status === 'ready', 404);

        return MediaAssetResource::make($asset);
    }

    public function show(MediaAsset $asset, string $variant): Response
    {
        abort_unless($asset->context === 'news' && $asset->visibility === 'private' && $asset->status === 'ready', 404);

        $path = match ($variant) {
            'original' => $asset->path,
            'thumbnail' => $asset->thumbnail_path,
            default => null,
        };
        abort_unless(is_string($path) && $path !== '', 404);
        abort_unless(Storage::disk($asset->disk)->exists($path), 404);

        return Storage::disk($asset->disk)->response($path, $asset->original_name, [
            'Content-Type' => (string) $asset->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=60',
        ], 'inline');
    }

    public function articleMedia(NewsArticle $article): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $ids = [];
        if ($article->hero_media_asset_id) {
            $ids[] = (int) $article->hero_media_asset_id;
        }

        foreach ($article->translations()->get(['content_json']) as $translation) {
            foreach (($translation->content_json ?? []) as $block) {
                if (! is_array($block)) {
                    continue;
                }

                foreach (['media_id', 'poster_media_id', 'before_media_id', 'after_media_id'] as $key) {
                    if (! empty($block[$key])) {
                        $ids[] = (int) $block[$key];
                    }
                }
            }
        }

        $ids = array_merge($ids, $article->mediaAssets()
            ->where('context', 'news')->where('visibility', 'private')->where('status', 'ready')
            ->pluck('media_assets.id')->map(fn ($id) => (int) $id)->all());

        $assets = MediaAsset::query()
            ->where('context', 'news')
            ->where('visibility', 'private')
            ->where('status', 'ready')
            ->whereIn('id', array_values(array_unique($ids)))
            ->get();

        return MediaAssetResource::collection($assets);
    }
}
