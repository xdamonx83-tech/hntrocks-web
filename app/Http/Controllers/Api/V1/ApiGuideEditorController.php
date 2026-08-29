<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guides\SaveGuideRevisionRequest;
use App\Http\Requests\Guides\UploadGuideMediaRequest;
use App\Models\Guide;
use App\Models\GuideCategory;
use App\Models\GuideMedia;
use App\Models\GuideRevision;
use App\Services\Guides\GuideWorkflowService;
use App\Services\MediaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ApiGuideEditorController extends Controller
{
    use AuthorizesRequests;

    private const LANGUAGES = ['de', 'en'];
    private const DIFFICULTIES = ['beginner', 'advanced', 'expert'];
    private const PLATFORMS = ['all', 'pc', 'playstation', 'xbox'];
    private const BLOCK_TYPES = ['heading', 'paragraph', 'steps', 'list', 'image', 'notice', 'warning'];

    public function options(): JsonResponse
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'de';

        return response()->json([
            'data' => [
                'categories' => GuideCategory::query()
                    ->active()
                    ->get()
                    ->map(fn (GuideCategory $category): array => [
                        'id' => (int) $category->id,
                        'slug' => (string) $category->slug,
                        'label' => $category->label($locale),
                        'description' => $category->description($locale),
                    ])
                    ->values()
                    ->all(),
                'languages' => self::LANGUAGES,
                'difficulties' => self::DIFFICULTIES,
                'platforms' => self::PLATFORMS,
                'block_types' => self::BLOCK_TYPES,
                'limits' => [
                    'title' => 160,
                    'summary' => 420,
                    'tags' => 8,
                    'tag' => 30,
                    'blocks' => 60,
                    'minimum_submit_blocks' => 3,
                    'upload_max_kb' => (int) config('guides.upload_max_kb', 8192),
                ],
            ],
        ]);
    }

    public function store(Request $request, GuideWorkflowService $workflow): JsonResponse
    {
        $this->authorize('create', Guide::class);

        $guide = $workflow->create($request->user());
        $revision = $guide->workingRevision;
        abort_unless($revision instanceof GuideRevision, 500);

        return response()->json([
            'message' => __('guides.editor.saved'),
            'data' => $this->draftPayload($guide, $revision, $request),
        ], 201);
    }

    public function show(Request $request, Guide $guide): JsonResponse
    {
        $this->authorize('preview', $guide);

        $guide->loadMissing(['workingRevision.category', 'publishedRevision.category']);
        $revision = $guide->workingRevision ?: $guide->publishedRevision;
        abort_unless($revision instanceof GuideRevision, 404);

        return response()->json([
            'data' => $this->draftPayload($guide, $revision, $request),
        ]);
    }

    public function beginRevision(
        Request $request,
        Guide $guide,
        GuideWorkflowService $workflow,
    ): JsonResponse {
        $this->authorize('update', $guide);
        $revision = $workflow->ensureWorkingRevision($guide, $request->user());
        $freshGuide = $guide->fresh(['workingRevision.category', 'publishedRevision.category']);

        return response()->json([
            'message' => __('guides.editor.saved'),
            'data' => $this->draftPayload($freshGuide, $revision, $request),
        ]);
    }

    public function update(
        SaveGuideRevisionRequest $request,
        Guide $guide,
        GuideWorkflowService $workflow,
    ): JsonResponse {
        $revision = $workflow->save($guide, $request->user(), $request->validated());

        return response()->json([
            'message' => __('guides.editor.saved'),
            'saved_at' => now()->toIso8601String(),
            'data' => $this->draftPayload($guide->fresh(), $revision, $request),
        ]);
    }

    public function media(
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
            'data' => [
                'media' => [
                    'id' => (int) $media->id,
                    'kind' => (string) $media->kind,
                    'url' => 'guides/drafts/media/'.(int) $media->id,
                    'width' => $media->width ? (int) $media->width : null,
                    'height' => $media->height ? (int) $media->height : null,
                ],
                'cover_media_id' => $revision->fresh()->cover_media_id
                    ? (int) $revision->fresh()->cover_media_id
                    : null,
            ],
        ], 201);
    }

    public function submit(Request $request, Guide $guide, GuideWorkflowService $workflow): JsonResponse
    {
        $this->authorize('submit', $guide);
        $revision = $workflow->submit($guide, $request->user());
        $guide->refresh();

        return response()->json([
            'message' => __('guides.editor.submitted'),
            'data' => [
                'guide' => [
                    'id' => (int) $guide->id,
                    'slug' => (string) $guide->slug,
                    'status' => (string) $guide->status,
                    'show_in_profile' => (bool) $guide->show_in_profile,
                ],
                'revision' => [
                    'id' => (int) $revision->id,
                    'version' => (int) $revision->version,
                    'status' => (string) $revision->status,
                    'submitted_at' => $revision->submitted_at?->toISOString(),
                ],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function draftPayload(Guide $guide, GuideRevision $revision, Request $request): array
    {
        $revision->loadMissing('category');
        $guide->loadMissing('author');

        return [
            'guide' => [
                'id' => (int) $guide->id,
                'slug' => (string) $guide->slug,
                'status' => (string) $guide->status,
                'show_in_profile' => (bool) $guide->show_in_profile,
                'editable' => $request->user()->can('update', $guide),
                'can_submit' => $request->user()->can('submit', $guide),
            ],
            'revision' => [
                'id' => (int) $revision->id,
                'version' => (int) $revision->version,
                'status' => (string) $revision->status,
                'title' => (string) ($revision->title ?? ''),
                'summary' => (string) ($revision->summary ?? ''),
                'category_id' => $revision->category_id ? (int) $revision->category_id : null,
                'category' => $revision->category ? [
                    'id' => (int) $revision->category->id,
                    'slug' => (string) $revision->category->slug,
                    'label' => $revision->category->label(app()->getLocale() === 'en' ? 'en' : 'de'),
                ] : null,
                'cover_media_id' => $revision->cover_media_id ? (int) $revision->cover_media_id : null,
                'cover_url' => $revision->cover_media_id
                    ? 'guides/drafts/media/'.(int) $revision->cover_media_id
                    : null,
                'tags' => array_values(array_filter((array) $revision->tags)),
                'language' => (string) ($revision->language ?: 'de'),
                'difficulty' => (string) ($revision->difficulty ?: 'beginner'),
                'platform' => (string) ($revision->platform ?: 'all'),
                'content_blocks' => $this->contentBlocks((array) $revision->content_blocks),
                'reading_time_minutes' => max(1, (int) $revision->reading_time_minutes),
                'moderation_reason' => $revision->moderation_reason,
                'updated_at' => $revision->updated_at?->toISOString(),
            ],
        ];
    }

    /** @param array<int, mixed> $blocks */
    private function contentBlocks(array $blocks): array
    {
        return collect($blocks)
            ->filter(fn ($block): bool => is_array($block))
            ->map(function (array $block): array {
                $type = (string) ($block['type'] ?? '');
                if (! in_array($type, self::BLOCK_TYPES, true)) {
                    return [];
                }

                $payload = [
                    'id' => isset($block['id']) ? (string) $block['id'] : null,
                    'type' => $type,
                ];

                if (in_array($type, ['heading', 'paragraph', 'notice', 'warning'], true)) {
                    $payload['text'] = (string) ($block['text'] ?? '');
                }
                if ($type === 'heading') {
                    $payload['level'] = max(2, min(4, (int) ($block['level'] ?? 2)));
                }
                if (in_array($type, ['steps', 'list'], true)) {
                    $payload['items'] = array_values(array_map(
                        static fn ($item): string => (string) $item,
                        array_filter((array) ($block['items'] ?? []), 'is_scalar'),
                    ));
                }
                if (in_array($type, ['notice', 'warning'], true)) {
                    $payload['title'] = (string) ($block['title'] ?? '');
                }
                if ($type === 'image') {
                    $mediaId = (int) ($block['media_id'] ?? 0);
                    $payload['media_id'] = $mediaId > 0 ? $mediaId : null;
                    $payload['media_url'] = $mediaId > 0 ? 'guides/drafts/media/'.$mediaId : null;
                    $payload['caption'] = (string) ($block['caption'] ?? '');
                }

                return $payload;
            })
            ->filter()
            ->values()
            ->all();
    }
}
