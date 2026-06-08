<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Services\GamificationService;
use App\Services\NotificationService;
use App\Services\MentionService;
use App\Services\Translation\FeedTranslationService;
use App\Support\FeedTextRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedCommentController extends Controller
{
    public function store(Request $request, FeedPost $post, NotificationService $notifications, GamificationService $gamification, MentionService $mentions, FeedTranslationService $translations): RedirectResponse|JsonResponse
    {
        abort_unless($post->canBeViewedBy($request->user()), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:50000'],
            'parent_id' => ['nullable', 'integer', 'exists:feed_comments,id'],
        ]);

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

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $parentId,
            'body' => $validated['body'],
            'source_language' => $translations->detectLanguage($validated['body']),
        ]);

        $mentions->syncForFeedComment($comment, $request->user(), $comment->body, $notifications);

        if ($request->expectsJson()) {
            $this->runCommentAjaxSideEffects(
                $post,
                $comment,
                $request->user(),
                $notifications,
                $gamification
            );

            $comment->loadMissing(['user', 'reactions', 'viewerReaction']);

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
        $comment->loadMissing(['user', 'reactions', 'viewerReaction']);
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
            'body_html' => FeedTextRenderer::render($comment->body),
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

    public function destroy(Request $request, FeedComment $comment): RedirectResponse|JsonResponse
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

        if (empty($comment->parent_id)) {
            FeedComment::query()
                ->where('parent_id', $comment->id)
                ->delete();
        }

        $comment->delete();

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
