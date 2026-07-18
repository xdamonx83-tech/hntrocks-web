<?php

namespace Tests\Feature\Settings;

use App\Models\Conversation;
use App\Models\FeedPost;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\NotificationService;
use App\Services\UserBlockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteUserBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocked_authors_are_hidden_in_both_block_directions(): void
    {
        $viewer = User::factory()->create();
        $blockedByViewer = User::factory()->create();
        $viewerBlockedBy = User::factory()->create();
        $visibleAuthor = User::factory()->create();

        UserBlock::create([
            'user_id' => $viewer->id,
            'blocked_user_id' => $blockedByViewer->id,
        ]);
        UserBlock::create([
            'user_id' => $viewerBlockedBy->id,
            'blocked_user_id' => $viewer->id,
        ]);

        $hiddenOne = FeedPost::withoutGlobalScopes()->create([
            'user_id' => $blockedByViewer->id,
            'body' => 'Hidden outgoing block',
            'visibility' => 'public',
            'status' => 'published',
        ]);
        $hiddenTwo = FeedPost::withoutGlobalScopes()->create([
            'user_id' => $viewerBlockedBy->id,
            'body' => 'Hidden incoming block',
            'visibility' => 'public',
            'status' => 'published',
        ]);
        $visible = FeedPost::withoutGlobalScopes()->create([
            'user_id' => $visibleAuthor->id,
            'body' => 'Visible post',
            'visibility' => 'public',
            'status' => 'published',
        ]);

        $this->actingAs($viewer);

        $this->assertSame(
            [$visible->id],
            FeedPost::query()->orderBy('id')->pluck('id')->all()
        );
        $this->assertNull(FeedPost::query()->find($hiddenOne->id));
        $this->assertNull(FeedPost::query()->find($hiddenTwo->id));
    }

    public function test_blocked_profiles_and_direct_social_urls_return_not_found(): void
    {
        $viewer = User::factory()->create();
        $blocked = User::factory()->create(['username' => 'blocked-route-user']);
        $post = FeedPost::withoutGlobalScopes()->create([
            'user_id' => $blocked->id,
            'body' => 'Hidden direct post',
            'visibility' => 'public',
            'status' => 'published',
        ]);

        UserBlock::create([
            'user_id' => $viewer->id,
            'blocked_user_id' => $blocked->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('profile.public', $blocked))
            ->assertNotFound();

        $this->actingAs($viewer)
            ->get(route('feed.show', $post))
            ->assertNotFound();
    }

    public function test_blocking_a_user_removes_the_existing_friendship(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create(['username' => 'former-friend']);
        [$one, $two] = Friendship::pairIds($viewer, $target);

        Friendship::create([
            'user_one_id' => $one,
            'user_two_id' => $two,
            'requester_id' => $viewer->id,
            'recipient_id' => $target->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->post(route('settings.privacy.blocks.store'), [
                'settings_section' => 'blocked',
                'username' => $target->username,
            ])
            ->assertRedirect(route('account.settings.edit').'#blocked');

        $this->assertDatabaseHas('user_blocks', [
            'user_id' => $viewer->id,
            'blocked_user_id' => $target->id,
        ]);
        $this->assertDatabaseMissing('friendships', [
            'user_one_id' => $one,
            'user_two_id' => $two,
        ]);
    }

    public function test_blocked_conversations_are_removed_from_message_queries(): void
    {
        $viewer = User::factory()->create();
        $blocked = User::factory()->create();
        $visibleUser = User::factory()->create();

        UserBlock::create([
            'user_id' => $viewer->id,
            'blocked_user_id' => $blocked->id,
        ]);

        $hiddenConversation = Conversation::create([
            'type' => 'private',
            'created_by' => $viewer->id,
        ]);
        $hiddenConversation->users()->attach([$viewer->id, $blocked->id]);
        Message::withoutGlobalScopes()->create([
            'conversation_id' => $hiddenConversation->id,
            'user_id' => $blocked->id,
            'type' => 'text',
            'body' => 'Hidden message',
        ]);

        $visibleConversation = Conversation::create([
            'type' => 'private',
            'created_by' => $viewer->id,
        ]);
        $visibleConversation->users()->attach([$viewer->id, $visibleUser->id]);
        Message::withoutGlobalScopes()->create([
            'conversation_id' => $visibleConversation->id,
            'user_id' => $visibleUser->id,
            'type' => 'text',
            'body' => 'Visible message',
        ]);

        $this->actingAs($viewer);
        $query = Conversation::query()->forUser($viewer);

        $this->assertSame(
            [$visibleConversation->id],
            app(UserBlockService::class)
                ->applyToConversationQuery($query, $viewer)
                ->orderBy('id')
                ->pluck('id')
                ->all()
        );
    }

    public function test_blocked_actors_cannot_create_notifications(): void
    {
        $viewer = User::factory()->create();
        $blocked = User::factory()->create();

        UserBlock::create([
            'user_id' => $blocked->id,
            'blocked_user_id' => $viewer->id,
        ]);

        $notification = app(NotificationService::class)->send(
            $viewer,
            $blocked,
            'feed_comment',
            'Hidden notification',
            'This must not be delivered.'
        );

        $this->assertNull($notification);
        $this->assertDatabaseMissing('user_notifications', [
            'title' => 'Hidden notification',
        ]);
    }
}
