<?php

namespace App\Http\Controllers\Guides;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuideComment;
use App\Services\NotificationService;
use App\Services\UserBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuideCommentController extends Controller
{
    public function store(
        Request $request,
        Guide $guide,
        UserBlockService $blocks,
        NotificationService $notifications,
    ): RedirectResponse|JsonResponse {
        abort_unless($guide->isPublished(), 404);
        $guide->loadMissing('author');
        abort_if($blocks->areBlocked($request->user(), $guide->author), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parent = null;
        if (! empty($validated['parent_id'])) {
            $parent = GuideComment::query()
                ->with('user')
                ->where('guide_id', $guide->id)
                ->whereNull('parent_id')
                ->findOrFail($validated['parent_id']);
            abort_if($blocks->areBlocked($request->user(), $parent->user), 404);
        }

        $comment = $guide->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $parent?->id,
            'body' => trim($validated['body']),
        ]);
        $guide->updateQuietly(['comments_count' => $guide->comments()->count()]);

        if ($parent) {
            $notifications->send(
                $parent->user,
                $request->user(),
                'guide_comment_reply',
                'guides.notifications.reply_title',
                'guides.notifications.reply_body',
                route('guides.show', $guide).'#comment-'.$comment->id
            );
            if ((int) $parent->user_id !== (int) $guide->author_id) {
                $notifications->send(
                    $guide->author,
                    $request->user(),
                    'guide_comment_reply',
                    'guides.notifications.reply_title',
                    'guides.notifications.reply_body',
                    route('guides.show', $guide).'#comment-'.$comment->id
                );
            }
        } else {
            $notifications->send(
                $guide->author,
                $request->user(),
                'guide_comment_new',
                'guides.notifications.comment_title',
                'guides.notifications.comment_body',
                route('guides.show', $guide).'#comment-'.$comment->id
            );
        }

        if ($request->expectsJson()) {
            $comment->load(['user.profile', 'guide']);

            return response()->json([
                'ok' => true,
                'message' => __('guides.comments.created'),
                'comment' => $this->payload($comment),
                'count' => $guide->comments_count,
            ], 201);
        }

        return back()->with('status', __('guides.comments.created'));
    }

    public function update(Request $request, GuideComment $comment): RedirectResponse|JsonResponse
    {
        abort_unless($comment->canEdit($request->user()), 403);
        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $comment->update(['body' => trim($validated['body'])]);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'body' => $comment->body])
            : back()->with('status', __('guides.comments.updated'));
    }

    public function destroy(Request $request, GuideComment $comment): RedirectResponse|JsonResponse
    {
        $comment->loadMissing('guide');
        abort_unless($comment->canDelete($request->user()), 403);
        $comment->delete();
        $comment->guide->updateQuietly(['comments_count' => $comment->guide->comments()->count()]);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'id' => $comment->id, 'count' => $comment->guide->comments_count])
            : back()->with('status', __('guides.comments.deleted'));
    }

    /** @return array<string, mixed> */
    private function payload(GuideComment $comment): array
    {
        $viewer = request()->user();

        return [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'body' => $comment->body,
            'created_at' => $comment->created_at?->diffForHumans(),
            'is_guide_author' => (int) $comment->user_id === (int) $comment->guide?->author_id,
            'author' => [
                'name' => $comment->user?->name ?: $comment->user?->username,
                'handle' => $comment->user?->username ? '@'.$comment->user->username : '',
                'avatar' => $comment->user?->avatarUrl(),
            ],
            'actions' => [
                'can_reply' => $comment->parent_id === null,
                'can_edit' => $comment->canEdit($viewer),
                'can_delete' => $comment->canDelete($viewer),
                'can_report' => $viewer !== null && (int) $comment->user_id !== (int) $viewer->id,
                'update_url' => route('guides.comments.update', $comment),
                'delete_url' => route('guides.comments.destroy', $comment),
                'report_url' => route('reports.store'),
            ],
        ];
    }
}
