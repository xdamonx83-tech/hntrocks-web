<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NextSavedController extends Controller
{
    public function __invoke(Request $request): View
    {
        $viewer = $request->user();
        $viewerId = (int) $viewer->id;
        $page = max(1, min(50, (int) $request->query('page', 1)));
        $limit = $page * 10;

        // Match the existing saved-feed API visibility criteria; bookmarked posts
        // are private to the authenticated viewer. A final model policy filter also
        // prevents a shared post from exposing an inaccessible original.
        $query = FeedPost::query()
            ->with([
                'user.profile', 'team', 'team.members', 'cupTeam.cup',
                'sharedPost.user.profile', 'sharedPost.team',
                'sharedPost.cupTeam.cup', 'sharedPost.media.mediaAsset',
                'media.mediaAsset', 'comments.user.profile',
                'comments.media.mediaAsset', 'comments.reactions',
                'comments.viewerReaction', 'reactions', 'viewerReaction',
                'viewerBookmark', 'poll.options.votes', 'poll.votes',
            ])
            ->withCount(['comments', 'reactions', 'bookmarks'])
            ->withCount('sharedByPosts as shares_count')
            ->withMax([
                'bookmarks as viewer_bookmarked_at' => fn ($query) => $query->where('user_id', $viewerId),
            ], 'created_at')
            ->where('status', 'published')
            ->whereHas('bookmarks', fn ($query) => $query->where('user_id', $viewerId))
            ->where(function ($query) use ($viewerId): void {
                $query->where(function ($normal) use ($viewerId): void {
                    $normal->whereNull('team_id')
                        ->where(fn ($visibility) => $visibility
                            ->whereIn('visibility', ['public', 'followers'])
                            ->orWhere(fn ($private) => $private
                                ->where('visibility', 'private')->where('user_id', $viewerId)));
                })->orWhere(function ($team) use ($viewerId): void {
                    $team->whereNotNull('team_id')
                        ->whereHas('team', fn ($teamQuery) => $teamQuery
                            ->where('visibility', '!=', 'private')
                            ->orWhereHas('activeMembers', fn ($members) => $members->where('user_id', $viewerId)));
                });
            })
            ->orderByDesc('viewer_bookmarked_at')
            ->latest('id')
            ;
        $posts = collect();
        foreach ($query->lazy(50) as $candidate) {
            if (! $candidate->canBeViewedBy($viewer)) {
                continue;
            }
            $posts->push($candidate);
            if ($posts->count() > $limit) {
                break;
            }
        }

        return view('next.feed', [
            'posts' => $posts->take($limit),
            'viewer' => $viewer,
            'feedFilter' => 'saved',
            'hasMoreFeedPosts' => $posts->count() > $limit,
            'nextFeedPage' => $page + 1,
            'widgets' => NextFeedWidgetData::forViewer($viewer),
        ]);
    }
}
