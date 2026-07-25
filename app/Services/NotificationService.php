<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Notifications\NotificationLocaleResolver;
use App\Services\Notifications\PushPayloadResolver;
use App\Services\Push\FcmPushService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    public function __construct(private readonly UserBlockService $blocks)
    {
    }

    public function send(?User $recipient, ?User $actor, string $type, string $title, string $body, ?string $actionUrl = null): ?UserNotification
    {
        if (! $recipient) {
            return null;
        }

        if ($actor && (int) $recipient->id === (int) $actor->id) {
            return null;
        }

        if ($actor && $this->blocks->areBlocked($recipient, $actor)) {
            return null;
        }

        if (! $this->recipientAllows($recipient, $type)) {
            return null;
        }

        $actionUrl = UserNotification::normalizeActionUrl($actionUrl);

        $notification = UserNotification::create([
            'user_id' => $recipient->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
        ]);

        $this->dispatchPush($notification);
        $this->dispatchBroadcast($notification);

        return $notification;
    }

    public function sendMany(iterable $recipients, ?User $actor, string $type, string $title, string $body, ?string $actionUrl = null): void
    {
        collect($recipients)
            ->filter()
            ->unique('id')
            ->each(fn (User $recipient) => $this->send($recipient, $actor, $type, $title, $body, $actionUrl));
    }

    private function recipientAllows(User $recipient, string $type): bool
    {
        $settings = $recipient->relationLoaded('notificationSettings')
            ? $recipient->notificationSettings
            : $recipient->notificationSettings()->first();

        if (! $settings) {
            return true;
        }

        return $settings->allows($type);
    }

    private function dispatchPush(UserNotification $notification): void
    {
        try {
            /** @var FcmPushService $push */
            $push = app(FcmPushService::class);

            if (! $push->isConfigured()) {
                return;
            }

            $notification->loadMissing(['user', 'actor.profile']);
            $recipient = $notification->user;

            if (! $recipient) {
                return;
            }

            /** @var NotificationLocaleResolver $resolver */
            $resolver = app(NotificationLocaleResolver::class);
            $localizedMessages = $resolver->messages($notification);
            $fallbackLocale = $resolver->normalize(App::currentLocale());
            $fallback = $localizedMessages[$fallbackLocale]
                ?? $localizedMessages['de']
                ?? [
                    'title' => 'hnt.rocks',
                    'body' => 'Du hast eine neue Benachrichtigung.',
                ];

            $push->sendToUser(
                $recipient,
                $fallback['title'],
                $fallback['body'],
                $notification->actionUrl(),
                app(PushPayloadResolver::class)->forNotification($notification),
                $localizedMessages
            );
        } catch (Throwable $error) {
            Log::warning('Push dispatch for user notification failed.', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'type' => $notification->type,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function dispatchBroadcast(UserNotification $notification): void
    {
        try {
            event(new UserNotificationCreated($notification));
        } catch (Throwable $error) {
            Log::warning('Broadcast dispatch for user notification failed.', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'type' => $notification->type,
                'error' => $error->getMessage(),
            ]);
        }
    }
}
