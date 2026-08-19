<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FeedCommentResource;
use App\Http\Resources\Api\FeedPostResource;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\FeedReaction;
use App\Services\Feed\FeedCommentTreeService;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Services\MentionService;
use App\Services\NotificationService;
use App\Services\Translation\FeedTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ApiFeedEngagementController extends Controller
{
    public function reactions(Request $request, FeedPost $post): JsonResponse
    {
        abort_unless($this->canUsePost($request, $post), 403);

        $reactions = $post->reactions()
            ->with('user.profile')
            ->latest()
            ->get()
            ->filter(fn (FeedReaction $reaction) => $reaction->user !== null)
            ->values();

        return response()->json([
            'total' => $reactions->count(),
            'users' => $reactions->map(function (FeedReaction $reaction): array {
                $user = $reaction->user;

                return [
                    'id' => (int) $user->id,
                    'name' => (string) ($user->name ?: $user->username ?: 'Hunter'),
                    'username' => (string) ($user->username ?: ''),
                    'avatar_url' => $user->avatarUrl(),
                    'type' => (string) ($reaction->type ?: 'like'),
                    'reacted_at' => optional($reaction->created_at)->diffForHumans(),
                ];
            })->values(),
        ]);
    }

    public function comments(Request $request, FeedPost $post): AnonymousResourceCollection
    {
        abort_unless($this->canUsePost($request, $post), 404);

        $comments = $post->comments()
            ->with([
                'user.profile',
                'media.mediaAsset',
                'viewerReaction',
                'replies' => function ($query): void {
                    $query
                        ->with(['user.profile', 'media.mediaAsset', 'viewerReaction'])
                        ->withCount('reactions')
                        ->oldest();
                },
            ])
            ->withCount('reactions')
            ->whereNull('parent_id')
            ->oldest()
            ->paginate((int) $request->integer('per_page', 20));

        return FeedCommentResource::collection($comments);
    }

    public function storeComment(
        Request $request,
        FeedPost $post,
        NotificationService $notifications,
        GamificationService $gamification,
        MediaService $mediaService,
        MentionService $mentions,
        FeedTranslationService $translations
    ): JsonResponse {
        abort_unless($this->canUsePost($request, $post), 403);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:50000'],
            'parent_id' => ['nullable', 'integer', 'exists:feed_comments,id'],
            'media' => ['nullable', 'array', 'max:'.config('hunthub.upload_limits.comment_media_count', 4)],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.config('hunthub.upload_limits.comment_media_kb', 10240)],
        ]);

        $files = $request->file('media', []);
        if ($files && ! is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $mediaService->assertAllowed($file, $request->user(), 'feed');
        }

        $body = trim((string) ($validated['body'] ?? ''));
        if ($body === '' && count($files) === 0) {
            return response()->json([
                'message' => __('ui.comment_body_or_media_required'),
                'errors' => [
                    'body' => [__('ui.comment_body_or_media_required')],
                ],
            ], 422);
        }

        $parentId = null;
        $parent = null;
        if (! empty($validated['parent_id'])) {
            $parent = FeedComment::query()
                ->where('feed_post_id', $post->id)
                ->find($validated['parent_id']);

            if ($parent) {
                $parentId = $parent->parent_id ?: $parent->id;
            }
        }

        $comment = DB::transaction(function () use ($post, $request, $parentId, $body, $translations, $files, $mediaService): FeedComment {
            $comment = $post->comments()->create([
                'user_id' => $request->user()->id,
                'parent_id' => $parentId,
                'body' => $body !== '' ? $body : null,
                'source_language' => $translations->detectLanguage($body !== '' ? $body : null),
            ]);

            foreach ($files as $index => $file) {
                $asset = $mediaService->store($file, $request->user(), 'feed', [
                    'attachable' => $comment,
                    'visibility' => $post->visibility,
                ]);

                $comment->media()->create([
                    'user_id' => $request->user()->id,
                    'media_asset_id' => $asset->id,
                    'disk' => $asset->disk,
                    'path' => $asset->path,
                    'mime_type' => $asset->mime_type,
                    'original_name' => $asset->original_name,
                    'size_bytes' => $asset->size_bytes,
                    'sort_order' => $index,
                ]);
            }

            return $comment;
        });

        try {
            $gamification->award($request->user(), 'feed_comment_created', source: $comment);

            $post->loadMissing(['user', 'team']);
            if ($post->user && (int) $post->user_id !== (int) $request->user()->id) {
                $gamification->award($post->user, 'feed_comment_received', source: $comment);
            }

            if (filled($comment->body)) {
                $mentions->syncForFeedComment($comment, $request->user(), $comment->body, $notifications);
            }
            $this->sendCommentNotifications($post, $comment, $request->user(), $notifications);
        } catch (\Throwable $exception) {
            report($exception);
        }

        $comment->loadMissing(['user.profile', 'viewerReaction', 'media.mediaAsset']);
        $comment->loadCount('reactions');
        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');
        $post->loadMissing(['user.profile', 'media.mediaAsset', 'viewerReaction', 'viewerBookmark']);

        return response()->json([
            'message' => __('ui.comment_published'),
            'comment' => new FeedCommentResource($comment),
            'post' => new FeedPostResource($post),
        ], 201);
    }


    public function updateComment(
        Request $request,
        FeedComment $comment,
        MentionService $mentions,
        NotificationService $notifications,
        FeedTranslationService $translations
    ): JsonResponse {
        $comment->loadMissing(['post.user', 'post.team']);
        $post = $comment->post;

        abort_unless($post && $this->canUsePost($request, $post), 403);
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:50000'],
        ]);

        $body = trim((string) $validated['body']);

        $comment->update([
            'body' => $body,
            'source_language' => $translations->detectLanguage($body),
        ]);

        $comment->translations()->delete();
        $mentions->syncForFeedComment($comment, $request->user(), $comment->body, $notifications);

        $comment->loadMissing(['user.profile', 'viewerReaction', 'media.mediaAsset']);
        $comment->loadCount('reactions');
        $post->loadMissing(['user.profile', 'media.mediaAsset', 'viewerReaction', 'viewerBookmark']);
        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');

        return response()->json([
            'message' => __('ui.comment_updated'),
            'comment' => new FeedCommentResource($comment),
            'post' => new FeedPostResource($post),
        ]);
    }

    public function destroyComment(Request $request, FeedComment $comment, FeedCommentTreeService $commentTrees): JsonResponse
    {
        $comment->loadMissing(['post.user', 'post.team']);
        $post = $comment->post;

        abort_unless($post && $this->canUsePost($request, $post), 403);
        abort_unless(
            (int) $comment->user_id === (int) $request->user()->id
            || (int) $post->user_id === (int) $request->user()->id
            || $request->user()->isAdmin(),
            403
        );

        $commentId = (int) $comment->id;
        $postId = (int) $post->id;
        $commentTrees->deleteTree($comment);

        $post->loadMissing(['user.profile', 'media.mediaAsset', 'viewerReaction', 'viewerBookmark']);
        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');

        return response()->json([
            'ok' => true,
            'message' => __('ui.comment_deleted'),
            'id' => $commentId,
            'post_id' => $postId,
            'post' => new FeedPostResource($post),
        ]);
    }

    public function toggleReaction(Request $request, FeedPost $post, NotificationService $notifications, GamificationService $gamification): JsonResponse
    {
        abort_unless($this->canUsePost($request, $post), 403);

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:like,love,dislike,happy,funny,wow,angry,sad'],
            'mode' => ['nullable', 'string', 'in:toggle,set'],
        ]);

        $type = $validated['type'] ?? 'like';
        $mode = $validated['mode'] ?? 'toggle';
        $reaction = $post->reactions()->where('user_id', $request->user()->id)->first();

        if ($reaction && $mode !== 'set') {
            $reaction->delete();
            $post->loadMissing(['user.profile', 'media.mediaAsset', 'viewerReaction', 'viewerBookmark']);
            $post->loadCount(['comments', 'reactions', 'bookmarks']);
            $post->loadCount('sharedByPosts as shares_count');

            return response()->json([
                'reacted' => false,
                'count' => (int) $post->reactions_count,
                'type' => null,
                'post' => new FeedPostResource($post),
                'reaction_stats' => $this->reactionStatsForUser((int) $request->user()->id),
            ]);
        }

        $created = false;
        if ($reaction) {
            $reaction->update(['type' => $type]);
        } else {
            $reaction = $post->reactions()->create([
                'user_id' => $request->user()->id,
                'type' => $type,
            ]);
            $created = true;
        }

        if ($created) {
            try {
                $gamification->award($request->user(), 'feed_like_given', source: $reaction);

                $post->loadMissing(['user', 'team']);
                if ($post->user && (int) $post->user_id !== (int) $request->user()->id) {
                    $gamification->award($post->user, 'feed_like_received', source: $reaction);
                    $notifications->send(
                        $post->user,
                        $request->user(),
                        $post->isTeamPost() ? 'team_feed_like' : 'feed_like',
                        __('ui.reaction_new_title'),
                        __('ui.feed_reaction_notification_body', ['name' => $request->user()->name]),
                        $post->permalink()
                    );
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $post->loadMissing(['user.profile', 'media.mediaAsset', 'viewerReaction', 'viewerBookmark']);
        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');

        return response()->json([
            'reacted' => true,
            'count' => (int) $post->reactions_count,
            'type' => $type,
            'post' => new FeedPostResource($post),
            'reaction_stats' => $this->reactionStatsForUser((int) $request->user()->id),
        ]);
    }


    public function toggleCommentReaction(Request $request, FeedComment $comment, NotificationService $notifications): JsonResponse
    {
        $comment->loadMissing(['post.user', 'post.team', 'user']);
        $post = $comment->post;

        abort_unless($post && $this->canUsePost($request, $post), 403);

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:like,love,dislike,happy,funny,wow,angry,sad'],
            'mode' => ['nullable', 'string', 'in:toggle,set'],
        ]);

        $type = $validated['type'] ?? 'like';
        $mode = $validated['mode'] ?? 'toggle';
        $reaction = $comment->reactions()->where('user_id', $request->user()->id)->first();

        if ($reaction && $mode !== 'set') {
            $reaction->delete();
            $comment->loadMissing(['user.profile', 'viewerReaction', 'media.mediaAsset']);
            $comment->loadCount('reactions');
            $post->loadMissing(['user.profile', 'media.mediaAsset', 'viewerReaction', 'viewerBookmark']);
            $post->loadCount(['comments', 'reactions', 'bookmarks']);
            $post->loadCount('sharedByPosts as shares_count');

            return response()->json([
                'reacted' => false,
                'count' => (int) $comment->reactions_count,
                'type' => null,
                'comment' => new FeedCommentResource($comment),
                'post' => new FeedPostResource($post),
            ]);
        }

        $created = false;
        if ($reaction) {
            $reaction->update(['type' => $type]);
        } else {
            $comment->reactions()->create([
                'user_id' => $request->user()->id,
                'type' => $type,
            ]);
            $created = true;
        }

        if ($created && $comment->user && (int) $comment->user_id !== (int) $request->user()->id) {
            try {
                $notifications->send(
                    $comment->user,
                    $request->user(),
                    $post->isTeamPost() ? 'team_feed_comment_reaction' : 'feed_comment_reaction',
                    __('ui.comment_reaction_new_title'),
                    __('ui.comment_reaction_notification_body', ['name' => $request->user()->name]),
                    $post->permalink($comment)
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $comment->loadMissing(['user.profile', 'viewerReaction', 'media.mediaAsset']);
        $comment->loadCount('reactions');
        $post->loadMissing(['user.profile', 'media.mediaAsset', 'viewerReaction', 'viewerBookmark']);
        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');

        return response()->json([
            'reacted' => true,
            'count' => (int) $comment->reactions_count,
            'type' => $type,
            'comment' => new FeedCommentResource($comment),
            'post' => new FeedPostResource($post),
        ]);
    }

    private function canUsePost(Request $request, FeedPost $post): bool
    {
        return $post->status === 'published' && $post->canBeViewedBy($request->user());
    }

    private function sendCommentNotifications(FeedPost $post, FeedComment $comment, $actor, NotificationService $notifications): void
    {
        $post->loadMissing(['user', 'team']);
        $comment->loadMissing(['parent.user']);

        $notifiedUserIds = [];
        $sendOnce = function ($recipient, string $type, string $title, string $body) use (&$notifiedUserIds, $actor, $comment, $post, $notifications): void {
            if (! $recipient) {
                return;
            }

            $recipientId = (int) $recipient->id;
            if ($recipientId === (int) $actor->id || isset($notifiedUserIds[$recipientId])) {
                return;
            }

            $notification = $notifications->send(
                $recipient,
                $actor,
                $type,
                $title,
                $body,
                $post->permalink($comment)
            );

            if ($notification) {
                $notifiedUserIds[$recipientId] = true;
            }
        };

        if ($comment->parent && $comment->parent->user) {
            $sendOnce(
                $comment->parent->user,
                $post->isTeamPost() ? 'team_feed_comment_reply' : 'feed_comment_reply',
                __('ui.comment_reply_title'),
                __('ui.comment_reply_body', ['name' => $actor->name])
            );
        }

        if ($post->user && (int) $post->user_id !== (int) $actor->id) {
            $sendOnce(
                $post->user,
                $post->isTeamPost() ? 'team_feed_comment' : 'feed_comment',
                __('ui.comment_new_title'),
                __('ui.comment_new_body', ['name' => $actor->name])
            );
        }
    }

    private function reactionStatsForUser(int $userId): array
    {
        return FeedReaction::query()
            ->whereHas('post', fn ($query) => $query->whereNull('team_id')->where('user_id', $userId))
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
