<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\FeedComment;
use App\Models\FeedCommentReaction;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedCommentReactionController extends Controller
{
    public function index(Request $request, FeedComment $comment): JsonResponse
    {
        $comment->loadMissing('post');
        $post = $comment->post;

        abort_unless($post && $post->canBeViewedBy($request->user()), 403);

        $reactions = $comment->reactions()
            ->with('user')
            ->latest()
            ->get()
            ->filter(fn (FeedCommentReaction $reaction) => $reaction->user !== null)
            ->values();

        return response()->json([
            'total' => $reactions->count(),
            'users' => $reactions->map(function (FeedCommentReaction $reaction) {
                $user = $reaction->user;

                return [
                    'id' => $user->id,
                    'name' => $user->name ?: 'HNT Hunter',
                    'username' => $user->username ? '@' . $user->username : '',
                    'avatar' => $user->avatarUrl(),
                    'profile_url' => $user->username
                        ? ((int) $user->id === (int) auth()->id() ? route('profile.show') : route('profile.public', $user))
                        : '#',
                    'reacted_at' => optional($reaction->created_at)->diffForHumans(),
                ];
            })->values(),
        ]);
    }

    public function toggle(Request $request, FeedComment $comment, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        $comment->loadMissing(['post', 'user']);
        $post = $comment->post;

        abort_unless($post && $post->canBeViewedBy($request->user()), 403);

        $allowedTypes = ['like', 'love', 'dislike', 'happy', 'funny', 'wow', 'angry', 'sad'];
        $type = (string) $request->input('type', 'like');
        $mode = (string) $request->input('mode', 'toggle');

        if (! in_array($type, $allowedTypes, true)) {
            $type = 'like';
        }

        $reaction = $comment->reactions()->where('user_id', $request->user()->id)->first();
        $createdReaction = false;

        if ($reaction && $mode !== 'set') {
            $reaction->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'reacted' => false,
                    'count' => $comment->reactions()->count(),
                    'type' => null,
                ]);
            }

            return redirect($post->permalink($comment))->with('status', __('ui.comment_reaction_removed'));
        }

        if ($reaction) {
            $reaction->update(['type' => $type]);
        } else {
            $comment->reactions()->create([
                'user_id' => $request->user()->id,
                'type' => $type,
            ]);
            $createdReaction = true;
        }

        if ($createdReaction && $comment->user && (int) $comment->user_id !== (int) $request->user()->id) {
            $notifications->send(
                $comment->user,
                $request->user(),
                $post->isTeamPost() ? 'team_feed_comment_reaction' : 'feed_comment_reaction',
                __('ui.comment_reaction_new_title'),
                __('ui.comment_reaction_notification_body', ['name' => $request->user()->name]),
                $post->permalink($comment)
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'reacted' => true,
                'count' => $comment->reactions()->count(),
                'type' => $type,
            ]);
        }

        return redirect($post->permalink($comment))->with('status', __('ui.comment_reaction_saved'));
    }
}
