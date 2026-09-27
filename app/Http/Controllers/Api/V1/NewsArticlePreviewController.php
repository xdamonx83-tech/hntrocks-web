<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MediaAssetResource;
use App\Http\Resources\Api\NewsArticleTranslationResource;
use App\Models\MediaAsset;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class NewsArticlePreviewController extends Controller
{
    public function issue(Request $request, NewsArticle $article): JsonResponse
    {
        $data = $request->validate(['locale' => ['required', Rule::in(NewsArticle::LOCALES)]]);
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $expiresAt = now()->addMinutes(10);
        $url = URL::temporarySignedRoute(
            'api.v1.news.preview.show',
            $expiresAt,
            ['article' => $article->id, 'locale' => $data['locale']],
        );

        return response()->json([
            'preview_url' => $url,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function show(NewsArticle $article, string $locale): JsonResponse
    {
        abort_unless(in_array($locale, NewsArticle::LOCALES, true), 404);
        $translation = $article->translationForLocale($locale);
        abort_unless($translation, 404);

        $mediaIds = $article->hero_media_asset_id ? [(int) $article->hero_media_asset_id] : [];
        foreach (($translation->content_json ?? []) as $block) {
            if (! is_array($block)) {
                continue;
            }

            foreach (['media_id', 'poster_media_id', 'before_media_id', 'after_media_id'] as $key) {
                if (! empty($block[$key])) {
                    $mediaIds[] = (int) $block[$key];
                }
            }
        }
        $media = MediaAsset::query()
            ->where('context', 'news')
            ->where('visibility', 'private')
            ->where('status', 'ready')
            ->whereIn('id', array_values(array_unique($mediaIds)))
            ->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [
                $asset->id => (new MediaAssetResource($asset))->toArray(request()),
            ]);

        return response()->json([
            'data' => [
                'id' => $article->id,
                'status' => $article->status,
                'category_key' => $article->category_key,
                'tags' => $article->tags ?? [],
                'featured' => (bool) $article->featured,
                'comments_enabled' => (bool) $article->comments_enabled,
                'hero_media' => $article->heroMedia ? (new MediaAssetResource($article->heroMedia))->toArray(request()) : null,
                'translation' => (new NewsArticleTranslationResource($translation))->toArray(request()),
                'media' => $media,
            ],
        ]);
    }
}
