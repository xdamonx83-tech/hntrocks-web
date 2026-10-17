<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Friendship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NextTopbarController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $viewer = $request->user();
        // Expand a single panel in batches of three, never exposing other users' data.
        $limits = collect(['notifications', 'messages', 'friends'])->mapWithKeys(
            fn (string $key) => [$key => max(3, min(60, $request->integer($key . '_limit', 3)))]
        );
        $notificationQuery = $viewer->notificationItems()->standard();
        $notificationsTotal = (clone $notificationQuery)->count();
        $notifications = $notificationQuery
            ->with('actor.profile')->latest()->limit($limits['notifications'])->get();

        $conversationQuery = Conversation::query()->forUser($viewer)
            ->whereIn('type', ['private', 'team'])->whereHas('messages');
        $conversationsTotal = (clone $conversationQuery)->count();
        $conversations = $conversationQuery
            ->with(['users.profile', 'latestMessage'])->latest('updated_at')
            ->limit($limits['messages'])->get();

        $pendingQuery = Friendship::query()->where('recipient_id', $viewer->id)
            ->where('status', Friendship::STATUS_PENDING);
        $pendingTotal = (clone $pendingQuery)->count();
        $pending = $pendingQuery->with('requester.profile')
            ->latest('id')->limit($limits['friends'])->get();

        return response()->json([
            'ok' => true,
            'notifications' => [
                'unread' => $viewer->notificationItems()->standard()->unread()->count(),
                'total' => $notificationsTotal,
                'items' => $notifications->map(fn ($n) => [
                    'id' => $n->id,
                    'title' => $n->displayTitle(),
                    'body' => $n->displayBody(),
                    'url' => $n->actionUrl() ?: route('notifications.index'),
                    'time' => $n->created_at?->diffForHumans(),
                    'unread' => $n->isUnread(),
                    'avatar' => $n->displayActorAvatarUrl(),
                ])->values(),
            ],
            'messages' => [
                'unread' => $viewer->unreadMessagesCount(['private','team']),
                'total' => $conversationsTotal,
                'items' => $conversations->map(function ($c) use ($viewer) {
                    $other = $c->otherParticipant($viewer);
                    $latest = $c->latestMessage;
                    return [
                        'id' => $c->id,
                        'name' => $c->displayTitleFor($viewer),
                        'avatar' => $other?->avatarUrl(),
                        'body' => (string) ($latest?->body ?: 'Medienanhang'),
                        'time' => $latest?->created_at?->diffForHumans(),
                        'unread' => $c->unreadCountFor($viewer),
                        'url' => route('messages.show', $c),
                    ];
                })->values(),
            ],
            'friends' => [
                'pending' => $pendingTotal,
                'total' => $pendingTotal,
                'items' => $pending->map(fn ($f) => [
                    'id' => $f->id,
                    'name' => $f->requester?->name ?: 'Hunter',
                    'avatar' => $f->requester?->avatarUrl(),
                    'time' => $f->created_at?->diffForHumans(),
                ])->values(),
            ],
        ]);
    }
}
