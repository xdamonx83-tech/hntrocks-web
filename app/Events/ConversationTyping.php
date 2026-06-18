<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationTyping implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public const EXPIRES_IN_SECONDS = 4;

    public function __construct(
        public Conversation $conversation,
        public User $user,
        public bool $isTyping = true,
    ) {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $this->conversation->loadMissing('users');

        return $this->conversation->users
            ->filter(fn (User $recipient): bool => (int) $recipient->id !== (int) $this->user->id)
            ->map(fn (User $recipient): Channel => new PrivateChannel('user.'.$recipient->id))
            ->values()
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'conversation.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'username' => (string) ($this->user->username ?? ''),
            'display_name' => (string) ($this->user->name ?? ''),
            'is_typing' => $this->isTyping,
            'expires_in_seconds' => self::EXPIRES_IN_SECONDS,
        ];
    }
}
