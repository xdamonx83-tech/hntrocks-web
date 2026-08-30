<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\LiveStreamComment;
use App\Models\LiveStreamHeartTotal;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\Twitch\TwitchLiveStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiLiveStreamController extends Controller
{
    public function show(
        Request $request,
        User $user,
        TwitchLiveStatusService $twitch,
    ): JsonResponse {
        $this->authorizeProfile($request, $user);
        [$status, $streamKey] = $this->resolveSession($user, $twitch);

        if ($streamKey === null) {
            return response()->json([
                'stream' => $status,
                'session_key' => null,
                'comments' => [],
                'hearts_count' => 0,
                'viewer_hearts_count' => 0,
            ]);
        }

        $afterId = max(0, (int) $request->query('after_id', 0));
        $commentsQuery = LiveStreamComment::query()
            ->with('user.profile')
            ->where('streamer_user_id', $user->id)
            ->where('stream_key', $streamKey);

        if ($afterId > 0) {
            $comments = $commentsQuery
                ->where('id', '>', $afterId)
                ->oldest('id')
                ->limit(50)
                ->get();
        } else {
            $comments = $commentsQuery
                ->latest('id')
                ->limit(50)
                ->get()
                ->reverse()
                ->values();
        }

        return response()->json([
            'stream' => $status,
            'session_key' => $streamKey,
            'comments' => $comments
                ->map(fn (LiveStreamComment $comment): array => $this->commentPayload($request, $comment))
                ->values(),
            'hearts_count' => $this->heartsCount($user, $streamKey),
            'viewer_hearts_count' => $this->viewerHeartsCount($request, $user, $streamKey),
        ]);
    }

    public function storeComment(
        Request $request,
        User $user,
        TwitchLiveStatusService $twitch,
    ): JsonResponse {
        $this->authorizeProfile($request, $user);
        [, $streamKey] = $this->resolveSession($user, $twitch);
        abort_if($streamKey === null, 409, 'Dieser Nutzer ist gerade offline.');

        $request->merge([
            'body' => trim((string) $request->input('body')),
        ]);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $comment = LiveStreamComment::query()->create([
            'streamer_user_id' => $user->id,
            'user_id' => $request->user()->id,
            'stream_key' => $streamKey,
            'body' => (string) $validated['body'],
        ]);
        $comment->load('user.profile');

        return response()->json([
            'data' => $this->commentPayload($request, $comment),
        ], 201);
    }

    public function storeHearts(
        Request $request,
        User $user,
        TwitchLiveStatusService $twitch,
    ): JsonResponse {
        $this->authorizeProfile($request, $user);
        [, $streamKey] = $this->resolveSession($user, $twitch);
        abort_if($streamKey === null, 409, 'Dieser Nutzer ist gerade offline.');

        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        $count = (int) $validated['count'];
        $viewerId = (int) $request->user()->id;

        DB::transaction(function () use ($user, $viewerId, $streamKey, $count): void {
            $total = LiveStreamHeartTotal::query()
                ->where('streamer_user_id', $user->id)
                ->where('user_id', $viewerId)
                ->where('stream_key', $streamKey)
                ->lockForUpdate()
                ->first();

            if ($total) {
                $total->increment('count', $count);
                return;
            }

            LiveStreamHeartTotal::query()->create([
                'streamer_user_id' => $user->id,
                'user_id' => $viewerId,
                'stream_key' => $streamKey,
                'count' => $count,
            ]);
        }, 3);

        return response()->json([
            'hearts_count' => $this->heartsCount($user, $streamKey),
            'viewer_hearts_count' => $this->viewerHeartsCount($request, $user, $streamKey),
        ]);
    }

    private function authorizeProfile(Request $request, User $user): void
    {
        abort_unless($user->status === 'active', 404);
        $user->loadMissing(['profile', 'privacySettings']);
        $isSelf = (int) $request->user()->id === (int) $user->id;

        if (! $isSelf && $user->profile?->profile_visibility === 'private') {
            abort(404);
        }

        if (! $isSelf && UserBlock::query()
            ->where(function ($query) use ($request, $user): void {
                $query->where('user_id', $request->user()->id)
                    ->where('blocked_user_id', $user->id);
            })
            ->orWhere(function ($query) use ($request, $user): void {
                $query->where('user_id', $user->id)
                    ->where('blocked_user_id', $request->user()->id);
            })
            ->exists()) {
            abort(404);
        }
    }

    private function resolveSession(User $user, TwitchLiveStatusService $twitch): array
    {
        $status = $twitch->statusForUrl($user->profile?->twitch_url);
        if (($status['is_live'] ?? false) !== true) {
            return [$status, null];
        }

        $identity = implode('|', [
            (string) $user->id,
            (string) ($status['channel'] ?? ''),
            (string) ($status['started_at'] ?? ''),
        ]);

        return [$status, hash('sha256', $identity)];
    }

    private function commentPayload(Request $request, LiveStreamComment $comment): array
    {
        return [
            'id' => (int) $comment->id,
            'body' => (string) $comment->body,
            'author' => (new UserResource($comment->user))->resolve($request),
            'created_at' => $comment->created_at?->toISOString(),
        ];
    }

    private function heartsCount(User $user, string $streamKey): int
    {
        return (int) LiveStreamHeartTotal::query()
            ->where('streamer_user_id', $user->id)
            ->where('stream_key', $streamKey)
            ->sum('count');
    }

    private function viewerHeartsCount(Request $request, User $user, string $streamKey): int
    {
        return (int) LiveStreamHeartTotal::query()
            ->where('streamer_user_id', $user->id)
            ->where('user_id', $request->user()->id)
            ->where('stream_key', $streamKey)
            ->value('count');
    }
}
