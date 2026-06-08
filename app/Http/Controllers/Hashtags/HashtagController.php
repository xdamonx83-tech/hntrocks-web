<?php

namespace App\Http\Controllers\Hashtags;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use App\Models\LfgPost;
use App\Models\Moment;
use App\Support\Hashtag;
use App\Support\HntTheme;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HashtagController extends Controller
{
    public function show(Request $request, string $tag): View
    {
        $tag = Hashtag::normalize($tag);
        abort_unless($tag, 404);

        $viewer = $request->user();
        $viewerId = (int) ($viewer?->id ?? 0);
        $like = '%#'.$tag.'%';

        $posts = FeedPost::query()
            ->with([
                'user.profile',
                'team',
                'sharedPost.user.profile',
                'sharedPost.team',
                'sharedPost.media.mediaAsset',
                'media.mediaAsset',
                'comments.user.profile',
                'comments.reactions',
                'comments.viewerReaction',
                'reactions',
                'viewerReaction',
                'viewerBookmark',
                'poll.options.votes',
                'poll.votes',
            ])
            ->withCount(['comments', 'reactions', 'bookmarks'])
            ->withCount('sharedByPosts as shares_count')
            ->where('status', 'published')
            ->whereNotNull('body')
            ->where('body', 'like', $like)
            ->latest()
            ->limit(60)
            ->get()
            ->filter(fn (FeedPost $post): bool => $post->canBeViewedBy($viewer) && Hashtag::contains($post->body, $tag))
            ->take(15)
            ->values();

        $moments = Moment::query()
            ->with(['user.profile', 'media', 'cover'])
            ->withCount(['comments', 'reactions as likes_count' => fn ($query) => $query->where('type', 'like'), 'bookmarks'])
            ->published()
            ->where(function ($query) use ($like): void {
                $query->where('caption', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->latest('published_at')
            ->latest('id')
            ->limit(60)
            ->get()
            ->filter(fn (Moment $moment): bool => Hashtag::contains($moment->caption, $tag) || Hashtag::contains($moment->description, $tag))
            ->take(12)
            ->values();

        $lfgPosts = LfgPost::query()
            ->with(['user.profile', 'pendingApplications'])
            ->withCount(['pendingApplications as pending_count'])
            ->whereIn('status', ['open', 'full'])
            ->where(function ($query) use ($viewerId): void {
                $query->where('visibility', 'public');

                if ($viewerId > 0) {
                    $query->orWhere('user_id', $viewerId);
                }
            })
            ->where(function ($query) use ($like): void {
                $query->where('title', 'like', $like)
                    ->orWhere('body', 'like', $like);
            })
            ->latest()
            ->limit(60)
            ->get()
            ->filter(fn (LfgPost $post): bool => Hashtag::contains($post->title, $tag) || Hashtag::contains($post->body, $tag))
            ->take(12)
            ->values();

        $relatedTags = collect()
            ->merge($posts->flatMap(fn (FeedPost $post): array => Hashtag::extract($post->body)))
            ->merge($moments->flatMap(fn (Moment $moment): array => array_merge(Hashtag::extract($moment->caption), Hashtag::extract($moment->description))))
            ->merge($lfgPosts->flatMap(fn (LfgPost $post): array => array_merge(Hashtag::extract($post->title), Hashtag::extract($post->body))))
            ->filter(fn (string $candidate): bool => $candidate !== $tag)
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(10)
            ->values();

        return HntTheme::view('hashtags.show', [
            'tag' => $tag,
            'posts' => $posts,
            'moments' => $moments,
            'lfgPosts' => $lfgPosts,
            'relatedTags' => $relatedTags,
            'totalCount' => $posts->count() + $moments->count() + $lfgPosts->count(),
        ]);
    }
}
