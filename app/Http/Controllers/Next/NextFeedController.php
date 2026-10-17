<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Feed\FeedController;
use App\Models\FeedPost;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

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
            'widgets' => NextFeedWidgetData::forViewer($request->user()),
        ]);
    }

    /** Create via the canonical feed store, then persist the requested poll expiry. */
    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'poll_duration_hours' => ['sometimes', 'integer', 'in:24,72,168'],
        ]);
        $response = app()->call([app(FeedController::class), 'store'], ['request' => $request]);
        abort_unless($response instanceof JsonResponse, 500);
        if ($response->isSuccessful() && $request->has('poll_options')) {
            $data = $response->getData(true);
            if (($data['ok'] ?? false) && isset($data['post_id'])) {
                $post = FeedPost::query()
                    ->where('user_id', $request->user()->id)
                    ->findOrFail((int) $data['post_id']);
                $post->poll()->update([
                    'closed_at' => now()->addHours((int) ($input['poll_duration_hours'] ?? 24)),
                ]);
            }
        }
        return $response;
    }

    /**
     * Private, view-authorized voter detail data for a real poll.
     * Identities are only available after the viewer has voted or the poll closed.
     */
    public function pollVoters(Request $request, FeedPost $post): JsonResponse
    {
        abort_unless($post->status === 'published' && $post->canBeViewedBy($request->user()), 404);
        $poll = $post->poll()->with('options')->firstOrFail();
        $viewerHasVoted = $poll->votes()->where('user_id', $request->user()->id)->exists();
        abort_unless($viewerHasVoted || $poll->isClosed(), 403, 'Bitte zuerst abstimmen.');

        $validated = $request->validate([
            'option_id' => ['nullable', 'integer'],
            'offset' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
        $requestedOption = $validated['option_id'] ?? null;
        $offset = (int) ($validated['offset'] ?? 0);
        $options = $requestedOption !== null
            ? $poll->options->where('id', (int) $requestedOption)
            : $poll->options;
        abort_if($requestedOption !== null && $options->isEmpty(), 404);
        $total = $poll->votes()->count();
        $pageSize = 24;
        $result = $options->map(function ($option) use ($poll, $requestedOption, $offset, $pageSize, $total): array {
            $votes = $poll->votes()->where('feed_post_poll_option_id', $option->id);
            $count = (clone $votes)->count();
            $skip = $requestedOption !== null ? $offset : 0;
            $entries = $votes->with('user.profile')->orderBy('id')->skip($skip)->take($pageSize)->get();
            $voters = $entries->filter(fn ($entry) => $entry->user !== null)->map(fn ($entry): array => [
                'id' => (int) $entry->user->id,
                'name' => (string) $entry->user->name,
                'avatar' => (string) $entry->user->avatarUrl(),
            ])->values()->all();
            return [
                'id' => (int) $option->id,
                'body' => (string) $option->body,
                'count' => $count,
                'percent' => $total > 0 ? (int) round($count * 100 / $total) : 0,
                'voters' => $voters,
                'next_offset' => $skip + $entries->count() < $count ? $skip + $entries->count() : null,
            ];
        })->values();
        return response()->json([
            'ok' => true,
            'post_id' => (int) $post->id,
            'question' => (string) ($poll->question ?: 'Community-Umfrage'),
            'total' => $total,
            'closed' => $poll->isClosed(),
            'options' => $result,
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
            'sharedPost.media.mediaAsset',
            'media.mediaAsset',
            'comments.user.profile',
            'viewerReaction',
            'viewerBookmark',
            'poll.options.votes',
            'poll.votes',
        ])->loadCount(['comments', 'reactions', 'bookmarks']);

        return view('next.partials.hntv3-post', [
            'post' => $post,
            'feedFilter' => 'all',
        ]);
    }
}
