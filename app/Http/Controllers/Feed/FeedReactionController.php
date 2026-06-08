<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use App\Models\FeedReaction;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedReactionController extends Controller
{
    public function index(Request $request, FeedPost $post): JsonResponse
    {
        abort_unless($post->canBeViewedBy($request->user()), 403);

        $reactionLabels = [
            'like' => __('ui.reaction_like'),
            'love' => __('ui.reaction_love'),
            'funny' => __('ui.reaction_funny'),
            'wow' => __('ui.reaction_wow'),
            'sad' => __('ui.reaction_sad'),
            'angry' => __('ui.reaction_angry'),
            'dislike' => __('ui.reaction_dislike'),
            'happy' => __('ui.reaction_happy'),
        ];

        $reactionEmojis = [
            'like' => '👍',
            'love' => '❤️',
            'funny' => '😂',
            'wow' => '😯',
            'sad' => '😢',
            'angry' => '😡',
            'dislike' => '👎',
            'happy' => '😊',
        ];

        $reactions = $post->reactions()
            ->with('user')
            ->latest()
            ->get()
            ->filter(fn (FeedReaction $reaction) => $reaction->user !== null)
            ->values();

        $stats = $reactions
            ->groupBy(fn (FeedReaction $reaction) => (string) ($reaction->type ?: 'like'))
            ->map(fn ($items, $type) => [
                'type' => (string) $type,
                'label' => $reactionLabels[$type] ?? ucfirst((string) $type),
                'emoji' => $reactionEmojis[$type] ?? '👍',
                'count' => $items->count(),
            ])
            ->values()
            ->sortByDesc('count')
            ->values();

        return response()->json([
            'total' => $reactions->count(),
            'stats' => $stats,
            'users' => $reactions->map(function (FeedReaction $reaction) use ($reactionLabels, $reactionEmojis) {
                $user = $reaction->user;
                $type = (string) ($reaction->type ?: 'like');

                return [
                    'id' => $user->id,
                    'name' => $user->name ?: 'HNT Hunter',
                    'username' => $user->username ? '@' . $user->username : '',
                    'avatar' => $user->avatarUrl(),
                    'profile_url' => $user->username
                        ? ((int) $user->id === (int) auth()->id() ? route('profile.show') : route('profile.public', $user))
                        : '#',
                    'type' => $type,
                    'reaction_label' => $reactionLabels[$type] ?? ucfirst($type),
                    'reaction_emoji' => $reactionEmojis[$type] ?? '👍',
                    'reacted_at' => optional($reaction->created_at)->diffForHumans(),
                ];
            })->values(),
        ]);
    }

    public function toggle(Request $request, FeedPost $post, NotificationService $notifications, GamificationService $gamification): RedirectResponse|JsonResponse
    {
        abort_unless($post->canBeViewedBy($request->user()), 403);

        $allowedTypes = ['like', 'love', 'dislike', 'happy', 'funny', 'wow', 'angry', 'sad'];
        $type = (string) $request->input('type', 'like');
        $mode = (string) $request->input('mode', 'toggle');

        if (! in_array($type, $allowedTypes, true)) {
            $type = 'like';
        }

        $reaction = $post->reactions()->where('user_id', $request->user()->id)->first();

        if ($reaction && $mode !== 'set') {
            $reaction->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'reacted' => false,
                    'count' => $post->reactions()->count(),
                    'type' => null,
                    'reaction_stats' => $this->reactionStatsForUser($request->user()->id),
                ]);
            }

            return redirect($post->permalink())->with('status', __('ui.reaction_removed'));
        }

        if ($reaction) {
            $reaction->update(['type' => $type]);
        } else {
            $reaction = $post->reactions()->create([
                'user_id' => $request->user()->id,
                'type' => $type,
            ]);

            $gamification->award($request->user(), 'feed_like_given', source: $reaction);

            $post->loadMissing(['user', 'team']);
            if ($post->user && $post->user_id !== $request->user()->id) {
                $gamification->award($post->user, 'feed_like_received', source: $reaction);
                $notifications->send(
                    $post->user,
                    $request->user(),
                    $post->isTeamPost() ? 'team_feed_like' : 'feed_like',
                    __('ui.reaction_new_title'),
                    __('ui.feed_reaction_notification_body', ['name' => $request->user()->name]),
                    $post->permalink()
                );
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'reacted' => true,
                'count' => $post->reactions()->count(),
                'type' => $type,
                'reaction_stats' => $this->reactionStatsForUser($request->user()->id),
            ]);
        }

        return redirect($post->permalink())->with('status', __('ui.reaction_saved'));
    }

    private function reactionStatsForUser(int $userId): array
    {
        return FeedReaction::query()
            ->whereHas('post', fn ($query) => $query->whereNull('team_id')->where('user_id', $userId))
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
