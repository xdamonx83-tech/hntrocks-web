<?php

namespace App\Events;

use App\Models\UserNotification;
use App\Services\Notifications\PushPayloadResolver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class UserNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public UserNotification $notification)
    {
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.'.$this->notification->user_id);
    }

    public function broadcastAs(): string
    {
        return 'user.notification.created';
    }

    public function broadcastWith(): array
    {
        $notification = $this->notification;
        $this->loadNotificationRelations($notification);
        $actionUrl = $this->actionUrl($notification);
        $pushPayload = app(PushPayloadResolver::class)->forNotification($notification);
        $body = $this->displayBody($notification);

        return [
            'notification_id' => $notification->id,
            'type' => (string) $notification->type,
            'title' => $this->displayTitle($notification),
            'body' => $body,
            'message' => $body,
            'action_url' => $actionUrl,
            'actor_id' => $notification->actor_id,
            'data' => (array) ($notification->data ?? []),
            'created_at' => $notification->created_at?->toISOString(),
            'target' => $pushPayload['target'] ?? 'notification',
            'payload' => $pushPayload,
        ];
    }

    private function displayTitle(UserNotification $notification): string
    {
        try {
            $title = trim($notification->displayTitle());
        } catch (Throwable) {
            $title = trim((string) $notification->title);
        }

        return $title !== '' ? $title : 'hnt.rocks';
    }

    private function displayBody(UserNotification $notification): string
    {
        try {
            $body = trim((string) $notification->displayBody());
        } catch (Throwable) {
            $body = trim((string) $notification->body);
        }

        return $body !== '' ? $body : 'Du hast eine neue Benachrichtigung.';
    }

    private function actionUrl(UserNotification $notification): ?string
    {
        try {
            return $notification->actionUrl();
        } catch (Throwable) {
            return $notification->action_url;
        }
    }

    private function loadNotificationRelations(UserNotification $notification): void
    {
        try {
            $notification->loadMissing(['actor.profile']);
        } catch (Throwable) {
            // Optional relations must not prevent the base notification payload.
        }
    }
}
