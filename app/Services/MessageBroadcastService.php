<?php

namespace App\Services;

use App\Events\ConversationMessageCreated;
use App\Models\Message;
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
}
