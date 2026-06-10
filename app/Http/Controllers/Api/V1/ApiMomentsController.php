<?php

namespace App\Http\Controllers\Api\V1;

use App\Jobs\RenderMomentStudioProject;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MomentCommentResource;
use App\Http\Resources\Api\MomentResource;
use App\Models\Moment;
use App\Models\MomentComment;
use App\Models\MomentStudioProject;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiMomentsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $moments = Moment::query()
            ->with([
                'user.profile',
                'media',
                'cover',
                'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
                'bookmarks' => fn ($query) => $query->where('user_id', $request->user()->id),
            ])
            ->published()
            ->latest('published_at')
            ->latest('id')
            ->paginate(20);

        return MomentResource::collection($moments);
    }

    public function show(Request $request, Moment $moment): MomentResource
    {
        $this->authorizeMomentVisible($request, $moment);

        $moment->increment('views_count');

        $moment->load([
            'user.profile',
            'media',
            'cover',
            'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
            'bookmarks' => fn ($query) => $query->where('user_id', $request->user()->id),
        ]);

        return new MomentResource($moment);
    }

    public function store(Request $request, MediaService $mediaService, GamificationService $gamification): JsonResponse
    {
        if ($this->isStudioRenderRequest($request)) {
            return $this->storeStudioProject($request, $mediaService);
        }

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:'.config('hunthub.upload_limits.moment_video_kb', 204800)],
            'cover' => ['nullable', 'image', 'max:'.config('hunthub.upload_limits.moment_cover_kb', 8192)],
            'caption' => ['nullable', 'string', 'max:220'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['nullable', 'in:public,registered,private'],
            'trim_start_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'trim_end_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
        ]);

        $visibility = (string) ($validated['visibility'] ?? 'public');

        $mediaService->assertAllowed($request->file('video'), $user, 'moments');

        if ($request->hasFile('cover')) {
            $mediaService->assertAllowed($request->file('cover'), $user, 'moments_cover');
        }

        $video = $mediaService->store($request->file('video'), $user, 'moments', [
            'visibility' => $visibility,
            'metadata' => [
                'source' => 'api_moment_upload',
                'trim_start_seconds' => $validated['trim_start_seconds'] ?? null,
                'trim_end_seconds' => $validated['trim_end_seconds'] ?? null,
            ],
        ]);

        $cover = null;
        if ($request->hasFile('cover')) {
            $cover = $mediaService->store($request->file('cover'), $user, 'moments_cover', [
                'visibility' => $visibility,
                'metadata' => ['source' => 'api_moment_cover_upload'],
            ]);
        }

        $moment = Moment::create([
            'user_id' => $user->id,
            'media_asset_id' => $video->id,
            'cover_media_asset_id' => $cover?->id,
            'caption' => $validated['caption'] ?? null,
            'description' => $validated['description'] ?? null,
            'visibility' => $visibility,
            'status' => 'published',
            'processing_status' => $video->status === 'processing' ? 'processing' : 'ready',
            'trim_start_seconds' => $validated['trim_start_seconds'] ?? null,
            'trim_end_seconds' => $validated['trim_end_seconds'] ?? null,
            'published_at' => now(),
        ]);

        $mediaService->attach($video, $moment);
        if ($cover) {
            $mediaService->attach($cover, $moment);
        }

        $gamification->award($user, 'moment_created', source: $moment, description: 'Moment veröffentlicht');

        $moment->load([
            'user.profile',
            'media',
            'cover',
            'reactions' => fn ($query) => $query->where('user_id', $user->id)->where('type', 'like'),
            'bookmarks' => fn ($query) => $query->where('user_id', $user->id),
        ]);

        return response()->json([
            'message' => $moment->processing_status === 'processing'
                ? 'Moment wurde gespeichert und wird jetzt komprimiert.'
                : 'Moment wurde veröffentlicht.',
            'data' => new MomentResource($moment),
        ], 201);
    }

    public function studioStatus(Request $request, MomentStudioProject $project): JsonResponse
    {
        abort_unless((int) $project->user_id === (int) $request->user()->id, 404);

        $project->refresh();

        $payload = [
            'status' => $project->status,
            'project_id' => $project->id,
            'moment_id' => $project->moment_id,
            'message' => match ($project->status) {
                'queued' => 'Moment render is queued.',
                'rendering' => 'Moment render is running.',
                'published' => 'Moment render is published.',
                'failed' => 'Moment render failed.',
                default => 'Moment render is preparing.',
            },
        ];

        if ($project->status === 'published' && $project->moment_id) {
            $moment = Moment::query()
                ->with([
                    'user.profile',
                    'media',
                    'cover',
                    'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
                    'bookmarks' => fn ($query) => $query->where('user_id', $request->user()->id),
                ])
                ->find($project->moment_id);

            if ($moment) {
                $payload['data'] = (new MomentResource($moment))->resolve($request);
            }
        }

        return response()->json($payload);
    }

    private function isStudioRenderRequest(Request $request): bool
    {
        $payload = trim((string) $request->input('studio_payload', ''));

        return $payload !== '' && $request->hasFile('studio_videos') && count(Arr::wrap($request->file('studio_videos'))) >= 1;
    }

    private function storeStudioProject(Request $request, MediaService $mediaService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'studio_videos' => ['required', 'array', 'min:1', 'max:5'],
            'studio_videos.*' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:'.config('hunthub.upload_limits.moment_video_kb', 204800)],
            'studio_payload' => ['required', 'string', 'max:20000'],
            'caption' => ['nullable', 'string', 'max:220'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['nullable', 'in:public,registered,private'],
        ]);

        $payload = json_decode((string) $validated['studio_payload'], true);
        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'studio_payload' => 'Studio payload could not be read.',
            ]);
        }

        /** @var array<int, UploadedFile> $files */
        $files = array_values(Arr::wrap($request->file('studio_videos')));
        $clips = $this->normalizedStudioClips($payload, count($files));
        $totalDuration = array_sum(array_map(static fn (array $clip): float => (float) $clip['duration'], $clips));
        $textLayers = $this->normalizedStudioTextLayers($payload, $totalDuration);
        $visibility = (string) ($validated['visibility'] ?? 'public');

        if ($totalDuration <= 0 || $totalDuration > 60.05) {
            throw ValidationException::withMessages([
                'studio_payload' => 'The rendered Moment must be between 1 and 60 seconds long.',
            ]);
        }

        $project = null;
        $sourceAssets = [];

        try {
            DB::transaction(function () use (&$project, &$sourceAssets, $user, $validated, $visibility, $files, $clips, $textLayers, $mediaService): void {
                $project = MomentStudioProject::create([
                    'user_id' => $user->id,
                    'status' => 'uploading',
                    'visibility' => $visibility,
                    'caption' => $validated['caption'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'timeline' => ['clips' => $clips, 'text_layers' => $textLayers],
                    'source_media_asset_ids' => [],
                    'total_duration_seconds' => (int) ceil(array_sum(array_map(static fn (array $clip): float => (float) $clip['duration'], $clips))),
                    'expires_at' => now()->addDay(),
                ]);

                foreach ($files as $index => $file) {
                    $mediaService->assertAllowed($file, $user, 'moments');
                    $asset = $mediaService->store($file, $user, 'moment_studio_source', [
                        'visibility' => 'private',
                        'attachable' => $project,
                        'metadata' => [
                            'source' => 'moment_studio_source',
                            'studio_project_id' => $project->id,
                            'clip_index' => $index,
                            'trim_start_seconds' => $clips[$index]['start'],
                            'trim_end_seconds' => $clips[$index]['end'],
                            'duration_seconds' => $clips[$index]['duration'],
                        ],
                    ]);
                    $sourceAssets[] = $asset;
                }

                $project->update([
                    'status' => 'queued',
                    'source_media_asset_ids' => array_map(static fn ($asset): int => (int) $asset->id, $sourceAssets),
                    'queued_at' => now(),
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            foreach ($sourceAssets as $asset) {
                $paths = array_values(array_filter([$asset->path, $asset->thumbnail_path]));
                if ($paths !== []) {
                    Storage::disk($asset->disk)->delete($paths);
                }
                $asset->delete();
            }

            throw $exception;
        }

        if ($project) {
            RenderMomentStudioProject::dispatchAfterResponse($project->id);
        }

        return response()->json([
            'status' => $project?->status ?? 'queued',
            'message' => 'Moment was saved and is rendering.',
            'project_id' => $project?->id,
            'status_endpoint' => $project ? route('api.v1.moments.studio.status', $project) : null,
        ], 202);
    }

    /** @return array<int, array<string, mixed>> */
    private function normalizedStudioClips(array $payload, int $fileCount): array
    {
        $clips = $payload['clips'] ?? [];
        if (! is_array($clips) || count($clips) !== $fileCount) {
            throw ValidationException::withMessages([
                'studio_payload' => 'Studio clip count does not match uploaded files.',
            ]);
        }

        $normalized = [];
        foreach (array_values($clips) as $index => $clip) {
            if (! is_array($clip)) {
                throw ValidationException::withMessages(['studio_payload' => 'Invalid clip data.']);
            }

            $fileIndex = (int) ($clip['file_index'] ?? $index);
            if ($fileIndex !== $index) {
                throw ValidationException::withMessages(['studio_payload' => 'Clip order does not match upload order.']);
            }

            $start = max(0.0, (float) ($clip['start'] ?? 0));
            $end = max(0.0, (float) ($clip['end'] ?? 0));
            if ($end <= $start) {
                throw ValidationException::withMessages(['studio_payload' => 'A clip has invalid start or end times.']);
            }

            $duration = min(60.0, max(0.1, $end - $start));
            $normalized[] = [
                'file_index' => $index,
                'start' => round($start, 3),
                'end' => round($end, 3),
                'duration' => round($duration, 3),
                'fade_in' => false,
                'fade_out' => false,
                'filter' => 'none',
                'effect' => 'none',
                'colors' => ['exposure' => 0, 'contrast' => 0, 'saturation' => 0, 'temperature' => 0, 'transparency' => 0],
                'transition_out' => 'none',
            ];
        }

        return $normalized;
    }

    /** @return array<int, array<string, float|string>> */
    private function normalizedStudioTextLayers(array $payload, float $totalDuration): array
    {
        $layers = $payload['text_layers'] ?? [];
        if (! is_array($layers)) {
            return [];
        }

        $normalized = [];
        foreach (array_values($layers) as $layer) {
            if (! is_array($layer)) {
                continue;
            }

            $text = trim((string) ($layer['text'] ?? ''));
            $text = preg_replace('/\s+/u', ' ', $text) ?: '';
            if ($text === '') {
                continue;
            }

            $start = max(0.0, (float) ($layer['start'] ?? 0));
            $end = max($start + 0.1, (float) ($layer['end'] ?? min($totalDuration, $start + 4.0)));
            $end = min(max(0.1, $totalDuration), $end);
            if ($end <= $start) {
                continue;
            }

            $normalized[] = [
                'text' => function_exists('mb_substr') ? mb_substr($text, 0, 90) : substr($text, 0, 90),
                'start' => round($start, 3),
                'end' => round($end, 3),
                'x' => round(min(95, max(5, (float) ($layer['x'] ?? 50))), 2),
                'y' => round(min(92, max(7, (float) ($layer['y'] ?? 78))), 2),
            ];

            if (count($normalized) >= 5) {
                break;
            }
        }

        return $normalized;
    }

    public function toggleLike(Request $request, Moment $moment, GamificationService $gamification, NotificationService $notifications): JsonResponse
    {
        $this->authorizeMomentVisible($request, $moment);

        $reaction = $moment->reactions()
            ->where('user_id', $request->user()->id)
            ->where('type', 'like')
            ->first();

        if ($reaction) {
            $reaction->delete();

            if ((int) $moment->likes_count > 0) {
                $moment->decrement('likes_count');
            }

            $liked = false;
        } else {
            $reaction = $moment->reactions()->create([
                'user_id' => $request->user()->id,
                'type' => 'like',
            ]);

            $moment->increment('likes_count');

            $gamification->award($request->user(), 'moment_like_given', source: $reaction, description: 'Moment geliked');

            if ((int) $moment->user_id !== (int) $request->user()->id) {
                $gamification->award($moment->user, 'moment_like_received', source: $reaction, description: 'Like auf Moment erhalten');

                $notifications->send(
                    $moment->user,
                    $request->user(),
                    'moment_like_new',
                    'Neuer Like auf deinem Moment',
                    $request->user()->username.' gefällt dein Moment.',
                    route('moments.show', $moment)
                );
            }

            $liked = true;
        }

        $moment->refresh()->load([
            'user.profile',
            'media',
            'cover',
            'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
            'bookmarks' => fn ($query) => $query->where('user_id', $request->user()->id),
        ]);

        return response()->json([
            'liked' => $liked,
            'data' => new MomentResource($moment),
        ]);
    }

    public function toggleBookmark(Request $request, Moment $moment, GamificationService $gamification): JsonResponse
    {
        $this->authorizeMomentVisible($request, $moment);

        $bookmark = $moment->bookmarks()->where('user_id', $request->user()->id)->first();

        if ($bookmark) {
            $bookmark->delete();

            if ((int) $moment->bookmarks_count > 0) {
                $moment->decrement('bookmarks_count');
            }

            $bookmarked = false;
        } else {
            $bookmark = $moment->bookmarks()->create([
                'user_id' => $request->user()->id,
            ]);

            $moment->increment('bookmarks_count');
            $gamification->award($request->user(), 'moment_saved', source: $bookmark, description: 'Moment gespeichert');

            $bookmarked = true;
        }

        $moment->refresh()->load([
            'user.profile',
            'media',
            'cover',
            'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
            'bookmarks' => fn ($query) => $query->where('user_id', $request->user()->id),
        ]);

        return response()->json([
            'bookmarked' => $bookmarked,
            'data' => new MomentResource($moment),
        ]);
    }

    public function comments(Request $request, Moment $moment): AnonymousResourceCollection
    {
        $this->authorizeMomentVisible($request, $moment);

        $viewerId = (int) $request->user()->id;

        $comments = $moment->comments()
            ->whereNull('parent_id')
            ->reorder()
            ->with([
                'moment',
                'user.profile',
                'reactions' => fn ($query) => $query->where('user_id', $viewerId)->where('type', 'like'),
                'replies' => fn ($query) => $query->oldest(),
                'replies.moment',
                'replies.user.profile',
                'replies.reactions' => fn ($query) => $query->where('user_id', $viewerId)->where('type', 'like'),
            ])
            ->oldest()
            ->paginate(30);

        return MomentCommentResource::collection($comments);
    }

    public function storeComment(Request $request, Moment $moment, GamificationService $gamification, NotificationService $notifications): JsonResponse
    {
        $this->authorizeMomentVisible($request, $moment);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:moment_comments,id'],
        ]);

        $parent = null;
        if (! empty($validated['parent_id'])) {
            $parent = MomentComment::query()
                ->where('moment_id', $moment->id)
                ->whereNull('parent_id')
                ->findOrFail($validated['parent_id']);
        }

        $comment = $moment->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $parent?->id,
            'body' => $validated['body'],
        ]);

        $moment->increment('comments_count');
        if ($parent) {
            $parent->increment('replies_count');
        }

        $gamification->award($request->user(), 'moment_comment_created', source: $comment, description: 'Moment kommentiert');

        if ((int) $moment->user_id !== (int) $request->user()->id) {
            $notifications->send(
                $moment->user,
                $request->user(),
                'moment_comment_new',
                'Neuer Kommentar auf deinem Moment',
                $request->user()->username.' hat deinen Moment kommentiert.',
                route('moments.show', $moment)
            );
        }

        if ($parent && (int) $parent->user_id !== (int) $request->user()->id && (int) $parent->user_id !== (int) $moment->user_id) {
            $notifications->send(
                $parent->user,
                $request->user(),
                'moment_comment_new',
                'Neue Antwort auf deinen Kommentar',
                $request->user()->username.' hat auf deinen Kommentar geantwortet.',
                route('moments.show', $moment)
            );
        }

        $comment->load([
            'moment',
            'user.profile',
            'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
        ]);

        $moment->refresh()->load([
            'user.profile',
            'media',
            'cover',
            'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
            'bookmarks' => fn ($query) => $query->where('user_id', $request->user()->id),
        ]);

        return response()->json([
            'data' => new MomentCommentResource($comment),
            'moment' => new MomentResource($moment),
        ], 201);
    }

    public function updateComment(Request $request, MomentComment $comment): JsonResponse
    {
        $comment->load('moment');

        abort_unless($comment->moment, 404);
        $this->authorizeMomentVisible($request, $comment->moment);
        abort_unless($comment->canBeEditedBy($request->user()), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $comment->update([
            'body' => $validated['body'],
        ]);

        $comment->refresh()->load([
            'moment',
            'user.profile',
            'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
            'replies' => fn ($query) => $query->oldest(),
            'replies.moment',
            'replies.user.profile',
            'replies.reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
        ]);

        return response()->json([
            'data' => new MomentCommentResource($comment),
        ]);
    }

    public function toggleCommentLike(Request $request, MomentComment $comment): JsonResponse
    {
        $comment->load('moment');

        abort_unless($comment->moment, 404);
        $this->authorizeMomentVisible($request, $comment->moment);

        $reaction = $comment->reactions()
            ->where('user_id', $request->user()->id)
            ->where('type', 'like')
            ->first();

        if ($reaction) {
            $reaction->delete();
            if ((int) $comment->likes_count > 0) {
                $comment->decrement('likes_count');
            }
            $liked = false;
        } else {
            $comment->reactions()->create([
                'user_id' => $request->user()->id,
                'type' => 'like',
            ]);
            $comment->increment('likes_count');
            $liked = true;
        }

        $comment->refresh()->load([
            'moment',
            'user.profile',
            'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
            'replies' => fn ($query) => $query->oldest(),
            'replies.moment',
            'replies.user.profile',
            'replies.reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
        ]);

        return response()->json([
            'liked' => $liked,
            'data' => new MomentCommentResource($comment),
        ]);
    }

    public function destroyComment(Request $request, MomentComment $comment): JsonResponse
    {
        $comment->load(['moment', 'replies']);

        abort_unless($comment->moment, 404);
        $this->authorizeMomentVisible($request, $comment->moment);
        abort_unless($comment->canBeDeletedBy($request->user()), 403);

        $removedCount = 1 + ($comment->parent_id ? 0 : $comment->replies()->count());

        if ($comment->parent_id) {
            $comment->parent?->decrement('replies_count');
        } else {
            $comment->replies()->delete();
        }

        $comment->delete();

        if ((int) $comment->moment->comments_count > 0) {
            $comment->moment->decrement('comments_count', min($removedCount, (int) $comment->moment->comments_count));
        }

        return response()->json([
            'ok' => true,
            'removed_count' => $removedCount,
        ]);
    }

    private function authorizeMomentVisible(Request $request, Moment $moment): void
    {
        abort_unless(
            ($moment->status === 'published' && $moment->visibility !== 'private')
            || $moment->canBeManagedBy($request->user()),
            404
        );
    }
}
