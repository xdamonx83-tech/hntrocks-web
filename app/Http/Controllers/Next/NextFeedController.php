<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Feed\FeedController;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Private-development UI entry point. The URL is deliberately unlisted,
 * but not restricted by admin ID: regular Laravel authentication still applies.
 * The existing FeedController remains the only source of feed visibility rules.
 */
final class NextFeedController extends Controller
{
    public function __invoke(Request $request): View
    {
        // Reuse all existing feed filters, eager loading, visibility and
        // permission checks. No duplicated post query in the new frontend.
        $legacy = app(FeedController::class)->index($request);

        abort_unless($legacy instanceof View, 404);

        $data = $legacy->getData();

        return view('next.feed', [
            'posts' => $data['posts'] ?? collect(),
            'feedFilter' => $data['feedFilter'] ?? 'all',
            'nextPage' => (int) ($data['feedPage'] ?? $request->integer('page', 1)) + 1,
            'hasMore' => (bool) ($data['hasMoreFeedPosts'] ?? ($data['posts']?->hasMorePages() ?? false)),
            'viewer' => $request->user(),
        ]);
    }
}
