<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class ConversationMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Message $message)
    {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $this->loadMessageRelations(['conversation.users', 'user.profile']);
        $conversation = $this->message->conversation;

        if (! $conversation) {
            return [];
        }

        $channels = [
            new PrivateChannel('conversation.'.$conversation->id),
        ];

        $conversation->users
            ->filter(fn (User $recipient): bool => (int) $recipient->id !== (int) $this->message->user_id)
            ->each(function (User $recipient) use (&$channels): void {
                $channels[] = new PrivateChannel('user.'.$recipient->id);
            });

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'conversation.message.created';
    }

    public function broadcastWith(): array
    {
        $this->loadMessageRelations(['conversation', 'user.profile']);
        $sender = $this->message->user;

        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'conversation_type' => (string) ($this->message->conversation?->type ?? ''),
            'sender_id' => $this->message->user_id,
            'sender_username' => (string) ($sender?->username ?? ''),
            'sender_name' => (string) ($sender?->name ?? ''),
            'body_preview' => $this->bodyPreview(),
            'created_at' => $this->message->created_at?->toISOString(),
        ];
    }

    private function bodyPreview(): string
    {
        $body = trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->message->body)) ?: '');

        if ($body === '') {
            return 'Du hast eine neue Nachricht.';
        }

        return Str::limit($body, 140);
    }

    /**
     * @param  array<int, string>  $relations
     */
    private function loadMessageRelations(array $relations): void
    {
        try {
            $this->message->loadMissing($relations);
        } catch (Throwable) {
            // Optional relations must not prevent the base message payload.
        }
    }
}
