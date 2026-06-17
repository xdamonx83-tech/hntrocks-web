<?php

namespace Tests\Unit;

use App\Services\Notifications\PushPayloadResolver;
use PHPUnit\Framework\TestCase;

class PushPayloadResolverTest extends TestCase
{
    private PushPayloadResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new PushPayloadResolver();
    }

    public function test_feed_post_action_url_targets_post(): void
    {
        $payload = $this->resolver->forRaw('feed_like', '/feed/posts/123');

        $this->assertSame('post', $payload['target']);
        $this->assertSame('123', $payload['post_id']);
    }

    public function test_feed_comments_action_url_targets_comment(): void
    {
        $payload = $this->resolver->forRaw('feed_comment', 'https://hnt.rocks/feed/posts/123/comments');

        $this->assertSame('comment', $payload['target']);
        $this->assertSame('123', $payload['post_id']);
    }

    public function test_feed_comment_fragment_action_url_targets_comment(): void
    {
        $payload = $this->resolver->forRaw('feed_comment_reply', '/feed/posts/123#comment-456');

        $this->assertSame('comment', $payload['target']);
        $this->assertSame('123', $payload['post_id']);
    }

    public function test_moment_action_url_targets_moment(): void
    {
        $payload = $this->resolver->forRaw('moment_like_new', '/moments/r/42');

        $this->assertSame('moment', $payload['target']);
        $this->assertSame('42', $payload['moment_id']);
    }

    public function test_friend_request_type_targets_friend_request(): void
    {
        $payload = $this->resolver->forRaw('friend_request_received', '/u/hunter');

        $this->assertSame('friend_request', $payload['target']);
        $this->assertArrayNotHasKey('username', $payload);
    }

    public function test_cup_action_url_targets_cup(): void
    {
        $payload = $this->resolver->forRaw('cup_team_created', '/cups/summer-cup/participants');

        $this->assertSame('cup', $payload['target']);
        $this->assertSame('summer-cup', $payload['cup_slug']);
        $this->assertSame('summer-cup', $payload['slug']);
    }

    public function test_profile_action_url_targets_profile(): void
    {
        $payload = $this->resolver->forRaw('profile_followed', '/u/hunter');

        $this->assertSame('profile', $payload['target']);
        $this->assertSame('hunter', $payload['username']);
    }

    public function test_unknown_action_url_keeps_notification_fallback(): void
    {
        $payload = $this->resolver->forRaw('unknown_type', '/settings/privacy', '99', '12');

        $this->assertSame('unknown_type', $payload['type']);
        $this->assertSame('notification', $payload['target']);
        $this->assertSame('99', $payload['notification_id']);
        $this->assertSame('/settings/privacy', $payload['action_url']);
        $this->assertSame('12', $payload['actor_id']);
        $this->assertArrayNotHasKey('post_id', $payload);
        $this->assertArrayNotHasKey('moment_id', $payload);
    }
}
