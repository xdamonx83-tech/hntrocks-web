<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedPollController extends Controller
{
    public function vote(Request $request, FeedPost $post): RedirectResponse|JsonResponse
    {
        abort_unless($post->status === 'published' && $post->canBeViewedBy($request->user()), 404);

        $validated = $request->validate([
            'poll_option_id' => ['required', 'integer'],
        ]);

        $poll = $post->poll()->with('options')->firstOrFail();
        abort_if($poll->isClosed(), 422, __('ui.feed_poll_closed'));

        $option = $poll->options->firstWhere('id', (int) $validated['poll_option_id']);
        abort_unless($option, 422);

        $poll->votes()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['feed_post_poll_option_id' => $option->id]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('ui.feed_poll_vote_saved'),
            ]);
        }

        return redirect($post->redirectRoute())->with('status', __('ui.feed_poll_vote_saved'));
    }
}
