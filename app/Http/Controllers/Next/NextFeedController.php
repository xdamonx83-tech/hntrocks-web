<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Feed\FeedController;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reuse the existing feed's authorization, visibility and filters.
 * Only the Blade presentation changes under /next/feed.
 */
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
}
