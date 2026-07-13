<?php

namespace App\Http\Controllers\Moments;

use App\Http\Controllers\Controller;
use App\Models\Moment;
use App\Models\MomentComment;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MomentCommentController extends Controller
{
    public function store(Request $request, Moment $moment, GamificationService $gamification, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        abort_unless(($moment->status === 'published' && $moment->visibility !== 'private') || $moment->canBeManagedBy($request->user()), 404);

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

        $count = $moment->comments()->count();
        $moment->updateQuietly(['comments_count' => $count]);
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

        $comment->load('user.profile');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'count' => $count,
                'comment' => $this->commentPayload($comment),
            ], 201);
        }

        return back()->with('status', 'Kommentar wurde gespeichert.');
    }

    public function update(Request $request, MomentComment $comment): RedirectResponse|JsonResponse
    {
        abort_unless($comment->canBeEditedBy($request->user()), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $comment->update([
            'body' => $validated['body'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'body' => $comment->body]);
        }

        return back()->with('status', 'Kommentar wurde aktualisiert.');
    }

    public function toggleReaction(Request $request, MomentComment $comment): RedirectResponse|JsonResponse
    {
        abort_unless($comment->moment && (($comment->moment->status === 'published' && $comment->moment->visibility !== 'private') || $comment->moment->canBeManagedBy($request->user())), 404);

        $reaction = $comment->reactions()
            ->where('user_id', $request->user()->id)
            ->where('type', 'like')
            ->first();

        if ($reaction) {
            $reaction->delete();
            $liked = false;
        } else {
            $comment->reactions()->create([
                'user_id' => $request->user()->id,
                'type' => 'like',
            ]);
            $liked = true;
        }

        $count = $comment->reactions()->where('type', 'like')->count();
        $comment->updateQuietly(['likes_count' => $count]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'liked' => $liked, 'count' => $count]);
        }

        return back();
    }

    public function destroy(Request $request, MomentComment $comment): RedirectResponse|JsonResponse
    {
        $comment->load(['moment', 'replies']);

        abort_unless($comment->canBeDeletedBy($request->user()), 403);

        $moment = $comment->moment;
        $commentId = (int) $comment->id;
        $parentId = $comment->parent_id ? (int) $comment->parent_id : null;

        if ($comment->parent_id) {
            if ($comment->parent && (int) $comment->parent->replies_count > 0) {
                $comment->parent->decrement('replies_count');
            }
        } else {
            $comment->replies()->delete();
        }

        $comment->delete();

        $count = $moment ? $moment->comments()->count() : 0;
        if ($moment) {
            $moment->updateQuietly(['comments_count' => $count]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $commentId,
                'parent_id' => $parentId,
                'count' => $count,
            ]);
        }

        return back()->with('status', 'Kommentar wurde entfernt.');
    }

    /** @return array<string, mixed> */
    private function commentPayload(MomentComment $comment): array
    {
        $user = $comment->user;

        return [
            'id' => (int) $comment->id,
            'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
            'body' => (string) $comment->body,
            'likes_count' => (int) $comment->likes_count,
            'liked' => false,
            'created_at' => $comment->created_at?->diffForHumans() ?: __('ui.just_now'),
            'author' => [
                'name' => $user?->name ?: ($user?->username ?: 'HNT Hunter'),
                'handle' => $user?->username ? '@'.$user->username : '@hunter',
                'avatar' => $user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
            ],
            'reaction_url' => route('moments.comments.reactions.toggle', $comment),
            'update_url' => route('moments.comments.update', $comment),
            'delete_url' => route('moments.comments.destroy', $comment),
            'can_edit' => $comment->canBeEditedBy(request()->user()),
            'can_delete' => $comment->canBeDeletedBy(request()->user()),
        ];
    }
}
