<?php

namespace App\Http\Controllers\Moments;

use App\Http\Controllers\Controller;
use App\Models\Moment;
use App\Models\MomentComment;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MomentCommentController extends Controller
{
    public function store(Request $request, Moment $moment, GamificationService $gamification, NotificationService $notifications): RedirectResponse
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

        return back()->with('status', 'Kommentar wurde gespeichert.');
    }

    public function update(Request $request, MomentComment $comment): RedirectResponse
    {
        abort_unless($comment->canBeEditedBy($request->user()), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $comment->update([
            'body' => $validated['body'],
        ]);

        return back()->with('status', 'Kommentar wurde aktualisiert.');
    }

    public function toggleReaction(Request $request, MomentComment $comment): RedirectResponse
    {
        abort_unless($comment->moment && (($comment->moment->status === 'published' && $comment->moment->visibility !== 'private') || $comment->moment->canBeManagedBy($request->user())), 404);

        $reaction = $comment->reactions()
            ->where('user_id', $request->user()->id)
            ->where('type', 'like')
            ->first();

        if ($reaction) {
            $reaction->delete();
            if ((int) $comment->likes_count > 0) {
                $comment->decrement('likes_count');
            }
        } else {
            $comment->reactions()->create([
                'user_id' => $request->user()->id,
                'type' => 'like',
            ]);
            $comment->increment('likes_count');
        }

        return back();
    }

    public function destroy(Request $request, MomentComment $comment): RedirectResponse
    {
        $comment->load(['moment', 'replies']);

        abort_unless($comment->canBeDeletedBy($request->user()), 403);

        $removedCount = 1 + ($comment->parent_id ? 0 : $comment->replies()->count());

        if ($comment->parent_id) {
            $comment->parent?->decrement('replies_count');
        } else {
            $comment->replies()->delete();
        }

        $moment = $comment->moment;
        $comment->delete();

        if ($moment && (int) $moment->comments_count > 0) {
            $moment->decrement('comments_count', min($removedCount, (int) $moment->comments_count));
        }

        return back()->with('status', 'Kommentar wurde entfernt.');
    }
}
