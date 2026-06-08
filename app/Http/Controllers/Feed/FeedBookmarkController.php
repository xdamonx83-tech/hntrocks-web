<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedBookmarkController extends Controller
{
    public function toggle(Request $request, FeedPost $post): RedirectResponse|JsonResponse
    {
        abort_unless($post->canBeViewedBy($request->user()), 403);

        $bookmark = $post->bookmarks()->where('user_id', $request->user()->id)->first();

        if ($bookmark) {
            $bookmark->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'bookmarked' => false,
                    'count' => $post->bookmarks()->count(),
                    'message' => __('ui.feed_bookmark_removed'),
                ]);
            }

            return redirect($post->permalink())->with('status', __('ui.feed_bookmark_removed'));
        }

        $post->bookmarks()->create([
            'user_id' => $request->user()->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'bookmarked' => true,
                'count' => $post->bookmarks()->count(),
                'message' => __('ui.feed_bookmark_saved'),
            ]);
        }

        return redirect($post->permalink())->with('status', __('ui.feed_bookmark_saved'));
    }
}
