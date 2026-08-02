<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MomentCommentResource;
use App\Http\Resources\Api\MomentResource;
use App\Jobs\RenderMomentStudioProject;
use App\Models\Moment;
use App\Models\MomentBookmark;
use App\Models\MomentComment;
use App\Models\MomentStudioProject;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Services\MomentStudioProjectService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

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

    public function saved(Request $request): AnonymousResourceCollection
    {
        $viewerId = (int) $request->user()->id;

        $moments = Moment::query()
            ->with([
                'user.profile',
                'media',
                'cover',
                'reactions' => fn ($query) => $query->where('user_id', $viewerId)->where('type', 'like'),
                'bookmarks' => fn ($query) => $query->where('user_id', $viewerId),
            ])
            ->whereHas('bookmarks', fn ($query) => $query->where('user_id', $viewerId))
            ->published()
            ->orderByDesc(
                MomentBookmark::query()
                    ->select('created_at')
                    ->whereColumn('moment_id', 'moments.id')
                    ->where('user_id', $viewerId)
                    ->limit(1)
            )
            ->latest('moments.id')
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

    public function store(Request $request, MediaService $mediaService, GamificationService $gamification, MomentStudioProjectService $studioProjects): JsonResponse
    {
        if ($this->isStudioMultiClipRequest($request)) {
            return $this->storeStudioProject($request, $mediaService, $studioProjects);
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
        $message = match ($project->status) {
            'queued' => 'Studio-Projekt ist in der Warteschlange.',
            'rendering' => 'Studio-Projekt wird gerendert.',
            'published' => 'Moment wurde veröffentlicht.',
            'failed' => $project->error_message ?: 'Studio-Render ist fehlgeschlagen.',
            default => 'Studio-Projekt wird vorbereitet.',
        };

        $payload = [
            'project_id' => $project->id,
            'status' => $project->status,
            'message' => $message,
        ];

        if ($project->status === 'failed') {
            $payload['error_message'] = $project->error_message ?: $message;
        }

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
                $payload['data'] = new MomentResource($moment);
            }
        }

        return response()->json($payload);
    }

    public function update(Request $request, Moment $moment): JsonResponse
    {
        abort_unless($moment->canBeManagedBy($request->user()), 403);

        $validated = $request->validate([
            'caption' => ['sometimes', 'nullable', 'string', 'max:220'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'visibility' => ['sometimes', 'required', 'in:public,registered,private'],
        ]);

        $updates = [];
        foreach (['caption', 'description', 'visibility'] as $field) {
            if ($request->exists($field)) {
                $updates[$field] = $validated[$field] ?? null;
            }
        }

        if ($updates !== []) {
            $moment->update($updates);
        }

        $moment->refresh()->load([
            'user.profile',
            'media',
            'cover',
            'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
            'bookmarks' => fn ($query) => $query->where('user_id', $request->user()->id),
        ]);

        return response()->json([
            'message' => 'Moment wurde aktualisiert.',
            'data' => new MomentResource($moment),
        ]);
    }

    public function destroy(Request $request, Moment $moment): JsonResponse
    {
        abort_unless($moment->canBeManagedBy($request->user()), 403);

        $moment->update(['status' => 'archived']);
        $moment->delete();

        return response()->json([
            'message' => 'Moment wurde gelöscht.',
            'ok' => true,
            'id' => (int) $moment->id,
        ]);
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

    private function isStudioMultiClipRequest(Request $request): bool
    {
        $payload = trim((string) $request->input('studio_payload', ''));

        return $payload !== '' && $request->hasFile('studio_videos') && count(Arr::wrap($request->file('studio_videos'))) >= 1;
    }

    private function storeStudioProject(Request $request, MediaService $mediaService, MomentStudioProjectService $studioProjects): JsonResponse
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

        $project = $studioProjects->createQueuedProject($user, array_values(Arr::wrap($request->file('studio_videos'))), (string) $validated['studio_payload'], [
            'visibility' => (string) ($validated['visibility'] ?? 'public'),
            'caption' => $validated['caption'] ?? null,
            'description' => $validated['description'] ?? null,
        ], $mediaService);

        RenderMomentStudioProject::dispatchAfterResponse($project->id);

        return response()->json([
            'message' => 'Studio-Projekt wurde erstellt und wird gerendert.',
            'project_id' => $project->id,
            'status' => $project->status,
        ], 202);
    }
}
