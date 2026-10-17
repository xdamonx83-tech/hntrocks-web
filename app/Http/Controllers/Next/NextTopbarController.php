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
        $notifications = $viewer->notificationItems()->standard()
            ->with('actor.profile')->latest()->limit(8)->get();

        $conversations = Conversation::query()->forUser($viewer)
            ->whereIn('type', ['private', 'team'])
            ->with(['users.profile', 'latestMessage'])
            ->whereHas('messages')->latest('updated_at')->limit(7)->get();

        $pending = Friendship::query()->where('recipient_id', $viewer->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->with('requester.profile')->latest('id')->limit(8)->get();

        return response()->json([
            'ok' => true,
            'notifications' => [
                'unread' => $viewer->notificationItems()->standard()->unread()->count(),
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
                'pending' => Friendship::query()->where('recipient_id', $viewer->id)
                    ->where('status', Friendship::STATUS_PENDING)->count(),
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
