<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FeedPostResource;
use App\Models\FeedPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiSavedFeedController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $viewerId = (int) $request->user()->id;

        $posts = FeedPost::query()
            ->with([
                'user.profile',
                'team',
                'sharedPost.user.profile',
                'sharedPost.team',
                'sharedPost.media.mediaAsset',
                'media.mediaAsset',
                'viewerReaction',
                'viewerBookmark',
                'poll.options.votes',
                'poll.votes',
            ])
            ->withCount(['comments', 'reactions', 'bookmarks'])
            ->withCount('sharedByPosts as shares_count')
            ->withMax([
                'bookmarks as viewer_bookmarked_at' => fn ($query) => $query->where('user_id', $viewerId),
            ], 'created_at')
            ->where('status', 'published')
            ->whereHas('bookmarks', fn ($query) => $query->where('user_id', $viewerId))
            ->where(function ($query) use ($request): void {
                $query->where(function ($normalPosts) use ($request): void {
                    $normalPosts->whereNull('team_id')
                        ->where(function ($visibility) use ($request): void {
                            $visibility->whereIn('visibility', ['public', 'followers'])
                                ->orWhere(function ($private) use ($request): void {
                                    $private->where('visibility', 'private')
                                        ->where('user_id', $request->user()->id);
                                });
                        });
                })->orWhere(function ($teamPosts) use ($request): void {
                    $teamPosts->whereNotNull('team_id')
                        ->whereHas('team', function ($teamQuery) use ($request): void {
                            $teamQuery->where('visibility', '!=', 'private')
                                ->orWhereHas('activeMembers', fn ($memberQuery) => $memberQuery->where('user_id', $request->user()->id));
                        });
                });
            })
            ->orderByDesc('viewer_bookmarked_at')
            ->latest()
            ->paginate(20);

        return FeedPostResource::collection($posts);
    }
}
