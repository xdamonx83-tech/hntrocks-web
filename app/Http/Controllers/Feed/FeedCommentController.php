<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Services\Feed\FeedCommentTreeService;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Services\NotificationService;
use App\Services\MentionService;
use App\Services\Translation\FeedTranslationService;
use App\Support\FeedTextRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedCommentController extends Controller
{
    public function store(Request $request, FeedPost $post, NotificationService $notifications, GamificationService $gamification, MediaService $mediaService, MentionService $mentions, FeedTranslationService $translations): RedirectResponse|JsonResponse
    {
        abort_unless($post->canBeViewedBy($request->user()), 403);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:50000'],
            'parent_id' => ['nullable', 'integer', 'exists:feed_comments,id'],
            'media' => ['nullable', 'array', 'max:'.config('hunthub.upload_limits.comment_media_count', 4)],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov', 'max:'.config('hunthub.upload_limits.comment_media_kb', 10240)],
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
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('ui.comment_body_or_media_required'),
                    'errors' => [
                        'body' => [__('ui.comment_body_or_media_required')],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['body' => __('ui.comment_body_or_media_required')])
                ->withInput();
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

        if (filled($comment->body)) {
            $mentions->syncForFeedComment($comment, $request->user(), $comment->body, $notifications);
        }

        if ($request->expectsJson()) {
            $this->runCommentAjaxSideEffects(
                $post,
                $comment,
                $request->user(),
                $notifications,
                $gamification
            );

            $comment->loadMissing(['user', 'reactions', 'viewerReaction', 'media.mediaAsset']);

            return response()->json([
                'ok' => true,
                'id' => $comment->id,
                'parent_id' => $parentId,
                'root_id' => $parentId ?: $comment->id,
                'is_reply' => (bool) $parentId,
                'comment' => $this->commentPayload($post, $comment, $request->user(), $parentId),
                'viewer' => $this->viewerPayload($request->user()),
                'comment_count_delta' => 1,
            ]);
        }

        $this->runCommentSideEffects(
            $post,
            $comment,
            $request->user(),
            $notifications,
            $gamification
        );

        return redirect($post->permalink($comment))->with('status', __('ui.comment_published'));
    }

    private function runCommentAjaxSideEffects(FeedPost $post, FeedComment $comment, $actor, NotificationService $notifications, GamificationService $gamification): void
    {
        try {
            $gamification->award($actor, 'feed_comment_created', source: $comment);

            $post->loadMissing(['user', 'team']);

            if ($post->user && (int) $post->user_id !== (int) $actor->id) {
                $gamification->award($post->user, 'feed_comment_received', source: $comment);
            }

            $this->sendCommentNotifications($post, $comment, $actor, $notifications);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function runCommentSideEffects(FeedPost $post, FeedComment $comment, $actor, NotificationService $notifications, GamificationService $gamification): void
    {
        $gamification->award($actor, 'feed_comment_created', source: $comment);

        $post->loadMissing(['user', 'team']);

        if ($post->user && (int) $post->user_id !== (int) $actor->id) {
            $gamification->award($post->user, 'feed_comment_received', source: $comment);
        }

        $this->sendCommentNotifications($post, $comment, $actor, $notifications);
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


    private function commentPayload(FeedPost $post, FeedComment $comment, $viewer, ?int $rootId): array
    {
        $comment->loadMissing(['user', 'reactions', 'viewerReaction', 'media.mediaAsset']);
        $commentUser = $comment->user;
        $viewerId = (int) ($viewer?->id ?? 0);
        $viewerIsAdmin = $viewer && method_exists($viewer, 'isAdmin') && $viewer->isAdmin();
        $canEdit = $viewerId > 0 && (int) $comment->user_id === $viewerId;
        $canDelete = $canEdit || $viewerIsAdmin || ($viewerId > 0 && (int) $post->user_id === $viewerId);
        $profileUrl = $commentUser && $viewerId > 0 && (int) $commentUser->id === $viewerId
            ? route('profile.show')
            : ($commentUser ? route('profile.public', $commentUser) : '#');

        return [
            'id' => $comment->id,
            'post_id' => $post->id,
            'parent_id' => $comment->parent_id,
            'root_id' => $rootId ?: $comment->id,
            'is_reply' => (bool) $comment->parent_id,
            'body' => $comment->body,
            'body_html' => FeedTextRenderer::render((string) $comment->body),
            'media' => $this->mediaPayload($comment),
            'created_at_label' => $comment->created_at?->diffForHumans() ?? '',
            'reaction_count' => $comment->relationLoaded('reactions') ? $comment->reactions->count() : 0,
            'viewer_reaction' => $comment->relationLoaded('viewerReaction') ? $comment->viewerReaction?->type : null,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'user' => [
                'id' => $commentUser?->id,
                'name' => $commentUser?->name ?? 'User',
                'level' => $commentUser?->level ?? 1,
                'avatar_url' => $commentUser?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg'),
                'profile_url' => $profileUrl,
            ],
            'mention_context' => $post->isTeamPost() ? 'team_feed_comment' : 'feed_comment',
            'mention_team_id' => $post->isTeamPost() ? $post->team_id : null,
            'translation' => $this->translationPayload($comment),
            'routes' => [
                'store' => route('feed.comments.store', $post),
                'reaction' => route('feed.comments.reactions.toggle', $comment),
                'update' => $canEdit ? route('feed.comments.update', $comment) : null,
                'delete' => $canDelete ? route('feed.comments.destroy', $comment) : null,
                'translation' => route('feed.translation.comment', $comment),
            ],
        ];
    }

    private function mediaPayload(FeedComment $comment): array
    {
        if (! $comment->relationLoaded('media')) {
            return [];
        }

        return $comment->media->map(function ($media): array {
            $asset = $media->mediaAsset;
            $mimeType = $asset?->mime_type ?: $media->mime_type;
            $type = $asset?->type ?: (str_starts_with((string) $mimeType, 'image/') ? 'image' : 'file');

            return [
                'id' => $media->id,
                'type' => $type,
                'mime_type' => $mimeType,
                'url' => $asset ? $asset->url() : $media->url(),
                'thumbnail_url' => $asset?->thumbnailUrl(),
                'status' => $asset?->status ?: 'ready',
                'sort_order' => (int) $media->sort_order,
            ];
        })->values()->all();
    }


    private function translationPayload(FeedComment $comment): array
    {
        $translations = app(FeedTranslationService::class);
        $targetLocale = app()->getLocale();
        $meta = $translations->translationMeta($comment->body, $comment->source_language, $targetLocale);

        return [
            'should_offer' => $meta['should_offer'],
            'source_locale' => $meta['source_locale'],
            'target_locale' => $meta['target_locale'],
            'show_label' => __('ui.translation_show'),
            'loading_label' => __('ui.translation_loading'),
            'error_label' => __('ui.translation_error'),
            'url' => route('feed.translation.comment', $comment),
        ];
    }

    private function viewerPayload($viewer): array
    {
        return [
            'id' => $viewer?->id,
            'name' => $viewer?->name ?? 'User',
            'level' => $viewer?->level ?? 1,
            'avatar_url' => $viewer?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg'),
        ];
    }

    public function update(Request $request, FeedComment $comment, MentionService $mentions, NotificationService $notifications, FeedTranslationService $translations): RedirectResponse|JsonResponse
    {
        $comment->loadMissing('post');
        $post = $comment->post;

        abort_unless($post && $post->canBeViewedBy($request->user()), 403);
        abort_unless($comment->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:50000'],
        ]);

        $comment->update([
            'body' => $validated['body'],
            'source_language' => $translations->detectLanguage($validated['body']),
        ]);

        $comment->translations()->delete();

        $mentions->syncForFeedComment($comment, $request->user(), $comment->body, $notifications);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $comment->id,
                'body' => $comment->body,
                'body_html' => FeedTextRenderer::render($comment->body),
            ]);
        }

        return redirect($post->permalink($comment))->with('status', __('ui.comment_updated'));
    }

    public function destroy(Request $request, FeedComment $comment, FeedCommentTreeService $commentTrees): RedirectResponse|JsonResponse
    {
        $comment->loadMissing('post');
        $post = $comment->post;

        abort_unless($post && $post->canBeViewedBy($request->user()), 403);
        $viewer = $request->user();
        abort_unless(
            (int) $comment->user_id === (int) $viewer->id
            || (int) $post->user_id === (int) $viewer->id
            || ($viewer && method_exists($viewer, 'isAdmin') && $viewer->isAdmin()),
            403
        );

        $redirectTo = $post->permalink();
        $postId = $post->id;
        $commentId = $comment->id;

        $commentTrees->deleteTree($comment);

        if ($request->expectsJson()) {
            $commentCount = $post->comments()->count();

            return response()->json([
                'ok' => true,
                'id' => $commentId,
                'post_id' => $postId,
                'comment_count' => $commentCount,
                'comment_count_label' => trans_choice('ui.comment_count', $commentCount, ['count' => $commentCount]),
            ]);
        }

        return redirect($redirectTo)->with('status', __('ui.comment_deleted'));
    }
}
