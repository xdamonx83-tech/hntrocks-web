<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use App\Services\Push\FcmPushService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MessagePushService
{
    public function __construct(private readonly FcmPushService $push)
    {
    }

    public function sendForMessage(Message $message): void
    {
        try {
            if (! $this->push->isConfigured() || $message->isSystemMessage()) {
                return;
            }

            $message->loadMissing(['user.profile', 'conversation.users']);
            $conversation = $message->conversation;
            $sender = $message->user;

            if (! $conversation || ! $sender) {
                return;
            }

            $conversation->users
                ->filter(fn (User $recipient): bool => (int) $recipient->id !== (int) $sender->id)
                ->filter(fn (User $recipient): bool => ($recipient->status ?? 'active') === 'active')
                ->each(function (User $recipient) use ($message, $conversation, $sender): void {
                    try {
                        $this->push->sendToUser(
                            $recipient,
                            $this->titleFor($sender, $conversation->type),
                            $this->bodyFor($message),
                            $this->actionUrlFor($conversation->id),
                            [
                                'type' => 'message_new',
                                'target' => 'message',
                                'conversation_id' => (string) $conversation->id,
                                'message_id' => (string) $message->id,
                                'sender_id' => (string) $sender->id,
                                'sender_username' => (string) ($sender->username ?? ''),
                                'conversation_type' => (string) ($conversation->type ?? 'private'),
                                'action_url' => $this->actionUrlFor($conversation->id),
                            ]
                        );
                    } catch (Throwable $error) {
                        Log::warning('Push dispatch for private message failed.', [
                            'message_id' => $message->id,
                            'conversation_id' => $conversation->id,
                            'recipient_id' => $recipient->id,
                            'error' => $error->getMessage(),
                        ]);
                    }
                });
        } catch (Throwable $error) {
            Log::warning('Message push dispatch failed.', [
                'message_id' => $message->id,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function titleFor(User $sender, ?string $conversationType): string
    {
        $name = trim((string) ($sender->name ?: $sender->username));

        if ($name === '') {
            $name = 'hnt.rocks';
        }

        if (in_array($conversationType, ['lfg', 'team_lfg'], true)) {
            return 'Neue LFG-Nachricht von '.$name;
        }

        return 'Neue Nachricht von '.$name;
    }

    private function bodyFor(Message $message): string
    {
        $body = trim(preg_replace('/\s+/', ' ', strip_tags((string) $message->body)) ?: '');

        if ($body === '') {
            return 'Du hast eine neue Nachricht.';
        }

        return Str::limit($body, 140);
    }

    private function actionUrlFor(int $conversationId): string
    {
        return route('messages.show', $conversationId);
    }
}
