<?php

namespace App\Events;

use App\Models\UserNotification;
use App\Services\Notifications\NotificationLocaleResolver;
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
        $pushPayload = (new PushPayloadResolver())->forRaw(
            (string) $notification->type,
            $actionUrl,
            (string) $notification->id,
            (string) ($notification->actor_id ?? '')
        );

        /** @var NotificationLocaleResolver $resolver */
        $resolver = app(NotificationLocaleResolver::class);
        $translations = $resolver->messages($notification);
        $preferredLocale = $resolver->normalize($this->preferredLocale($notification));
        $message = $translations[$preferredLocale]
            ?? $translations['de']
            ?? [
                'title' => 'hnt.rocks',
                'body' => 'Du hast eine neue Benachrichtigung.',
            ];

        return [
            'notification_id' => $notification->id,
            'type' => (string) $notification->type,
            'title' => $message['title'],
            'body' => $message['body'],
            'message' => $message['body'],
            'locale' => $preferredLocale,
            'translations' => $translations,
            'action_url' => $actionUrl,
            'actor_id' => $notification->actor_id,
            'created_at' => $notification->created_at?->toISOString(),
            'target' => $pushPayload['target'] ?? 'notification',
            'payload' => $pushPayload,
        ];
    }

    private function preferredLocale(UserNotification $notification): ?string
    {
        try {
            $user = $notification->user;
            if (! $user) {
                return null;
            }

            return $user->pushDevices()
                ->active()
                ->latest('last_seen_at')
                ->value('locale');
        } catch (Throwable) {
            return null;
        }
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
            $notification->loadMissing(['user', 'actor.profile']);
        } catch (Throwable) {
            // Optional relations must not prevent the base notification payload.
        }
    }
}
