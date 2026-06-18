<?php

namespace App\Services;

use App\Events\ConversationMessageCreated;
use App\Events\ConversationTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessageBroadcastService
{
    public function broadcastCreated(Message $message): void
    {
        try {
            if ($message->isSystemMessage()) {
                return;
            }

            event(new ConversationMessageCreated($message));
        } catch (Throwable $error) {
            Log::warning('Message broadcast dispatch failed.', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function broadcastTyping(Conversation $conversation, User $user, bool $isTyping): void
    {
        try {
            event(new ConversationTyping($conversation, $user, $isTyping));
        } catch (Throwable $error) {
            Log::warning('Message typing broadcast dispatch failed.', [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'error' => $error->getMessage(),
            ]);
        }
    }
}
