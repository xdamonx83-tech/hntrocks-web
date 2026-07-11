<?php

namespace App\Http\Middleware;

use App\Models\FeedPost;
use App\Support\HntTheme;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PreviewFeedCommentMedia
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('admin.theme-preview.shell')) {
            return $next($request);
        }

        if ($request->filled('comment_media_post_id')) {
            return $this->commentMediaResponse($request);
        }

        $response = $next($request);

        if ($response instanceof JsonResponse || $request->boolean('data')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        $content = $response->getContent();

        if (! is_string($content) || ! str_contains($contentType, 'text/html') || ! str_contains($content, '</body>')) {
            return $response;
        }

        $scriptPath = public_path('assets/themes/hnt_preview/dashboard-feed/real-feed-comment-media.js');
        $version = is_file($scriptPath) ? filemtime($scriptPath) : time();
        $script = '<script src="' . asset('assets/themes/hnt_preview/dashboard-feed/real-feed-comment-media.js') . '?v=' . $version . '"></script>';

        $response->setContent(str_replace('</body>', $script . '</body>', $content));

        return $response;
    }

    private function commentMediaResponse(Request $request): JsonResponse
    {
        $viewer = $request->user();

        abort_unless(
            $viewer?->isAdmin() && HntTheme::previewActive($viewer),
            404
        );

        $post = FeedPost::query()
            ->with(['comments.media.mediaAsset'])
            ->findOrFail((int) $request->query('comment_media_post_id'));

        abort_unless(
            $post->status === 'published' && $post->canBeViewedBy($viewer),
            404
        );

        return response()->json([
            'post_id' => (int) $post->id,
            'comments' => $post->comments
                ->map(function ($comment): array {
                    $media = $comment->media
                        ->map(function ($item): ?array {
                            try {
                                $asset = $item->mediaAsset;
                                $mime = (string) ($asset?->mime_type ?: $item->mime_type);
                                $url = $asset ? $asset->url() : $item->url();
                            } catch (Throwable) {
                                return null;
                            }

                            $type = str_starts_with($mime, 'image/')
                                ? 'image'
                                : (str_starts_with($mime, 'video/') ? 'video' : 'file');

                            return [
                                'id' => (int) $item->id,
                                'type' => $type,
                                'mime' => $mime,
                                'url' => $url,
                                'name' => (string) ($item->original_name ?: 'Anhang'),
                            ];
                        })
                        ->filter()
                        ->values();

                    return [
                        'id' => (int) $comment->id,
                        'media' => $media,
                    ];
                })
                ->filter(fn (array $comment): bool => $comment['media']->isNotEmpty())
                ->values(),
        ]);
    }
}
