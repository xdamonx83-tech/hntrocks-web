<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Feed\FeedController;
use App\Models\FeedPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NextFeedController extends Controller
{
    public function __invoke(Request $request): View
    {
        $legacy = app(FeedController::class)->index($request);
        abort_unless($legacy instanceof View, 404);

        $data = $legacy->getData();
        $posts = $data['posts'] ?? $data['socialitePosts'] ?? null;
        abort_unless($posts !== null, 404);

        $page = max(1, min(50, (int) $request->query('page', 1)));
        $hasMore = isset($data['hasMoreFeedPosts'])
            ? (bool) $data['hasMoreFeedPosts']
            : (bool) $posts->hasMorePages();

        return view('next.feed', [
            'posts' => $posts,
            'viewer' => $request->user(),
            'feedFilter' => $data['feedFilter'] ?? 'all',
            'hasMoreFeedPosts' => $hasMore,
            'nextFeedPage' => $page + 1,
        ]);
    }

    /**
     * Return a fresh, authorized HTML card after an AJAX comment or new post.
     * This is read-only and uses the same post visibility rules as /feed.
     */
    public function card(Request $request, FeedPost $post): View
    {
        abort_unless($post->status === 'published' && $post->canBeViewedBy($request->user()), 403);

        $post->load([
            'user.profile',
            'team',
            'sharedPost.user.profile',
            'sharedPost.team',
            'media.mediaAsset',
            'comments.user.profile',
            'viewerReaction',
            'viewerBookmark',
            'poll',
        ])->loadCount(['comments', 'reactions', 'bookmarks']);

        return view('next.partials.hntv3-post', [
            'post' => $post,
            'feedFilter' => 'all',
        ]);
    }
}
