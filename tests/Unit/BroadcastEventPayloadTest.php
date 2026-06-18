<?php

namespace Tests\Unit;

use App\Events\ConversationMessageCreated;
use App\Events\ConversationTyping;
use App\Events\UserNotificationCreated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BroadcastEventPayloadTest extends TestCase
{
    public function test_notification_event_payload_uses_safe_notification_fields(): void
    {
        $notification = new UserNotification([
            'user_id' => 7,
            'actor_id' => null,
            'type' => 'feed_comment',
            'title' => 'New comment',
            'body' => 'Hunter replied.',
            'action_url' => '/feed/posts/123/comments',
        ]);
        $notification->id = 55;
        $notification->created_at = CarbonImmutable::parse('2026-06-17 12:00:00');

        $payload = (new UserNotificationCreated($notification))->broadcastWith();

        $this->assertSame(55, $payload['notification_id']);
        $this->assertSame('feed_comment', $payload['type']);
        $this->assertSame('New comment', $payload['title']);
        $this->assertSame('Hunter replied.', $payload['body']);
        $this->assertNull($payload['actor_id']);
        $this->assertSame('comment', $payload['target']);
        $this->assertSame('123', $payload['payload']['post_id']);
    }

    public function test_message_event_payload_keeps_message_payload_small(): void
    {
        $sender = new User([
            'name' => 'Damon',
            'username' => 'xdamo',
        ]);
        $sender->id = 3;

        $conversation = new Conversation(['type' => 'private']);
        $conversation->id = 9;

        $message = new Message([
            'conversation_id' => 9,
            'user_id' => 3,
            'type' => 'user',
            'body' => '<p>Hello   there, this is a message.</p>',
        ]);
        $message->id = 44;
        $message->created_at = CarbonImmutable::parse('2026-06-17 12:05:00');
        $message->setRelation('conversation', $conversation);
        $message->setRelation('user', $sender);

        $payload = (new ConversationMessageCreated($message))->broadcastWith();

        $this->assertSame(44, $payload['message_id']);
        $this->assertSame(9, $payload['conversation_id']);
        $this->assertSame(3, $payload['sender_id']);
        $this->assertSame('xdamo', $payload['sender_username']);
        $this->assertSame('Damon', $payload['sender_name']);
        $this->assertSame('Hello there, this is a message.', $payload['body_preview']);
        $this->assertArrayNotHasKey('attachments', $payload);
    }

    public function test_typing_event_payload_uses_safe_user_fields(): void
    {
        $sender = new User([
            'name' => 'Damon',
            'username' => 'xdamo',
            'email' => 'damon@example.test',
        ]);
        $sender->id = 3;

        $conversation = new Conversation(['type' => 'private']);
        $conversation->id = 9;

        $payload = (new ConversationTyping($conversation, $sender, true))->broadcastWith();

        $this->assertSame(9, $payload['conversation_id']);
        $this->assertSame(3, $payload['user_id']);
        $this->assertSame('xdamo', $payload['username']);
        $this->assertSame('Damon', $payload['display_name']);
        $this->assertTrue($payload['is_typing']);
        $this->assertSame(4, $payload['expires_in_seconds']);
        $this->assertArrayNotHasKey('email', $payload);
    }
}
