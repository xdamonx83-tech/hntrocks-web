<?php

namespace Tests\Feature;

use App\Models\AiCommunityCharacter;
use App\Models\AiCommunityDraft;
use App\Models\FeedPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiCommunityAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_or_generate(): void
    {
        $character = $this->character();
        $this->actingAs($this->user())->get('/admin/ai-community')->assertForbidden();
        $this->actingAs($this->user())->post('/admin/ai-community/'.$character->id.'/generate')->assertForbidden();
    }

    public function test_fake_generation_creates_only_a_draft_and_blocks_duplicates(): void
    {
        config()->set('ai-community.enabled', true);
        config()->set('ai-community.key', 'test-key');
        Http::fake(['api.openai.com/*' => Http::response([
            'model' => 'test-model',
            'choices' => [['message' => ['content' => '{"body":"Which rifle pairs best with a quiet rotation through Stillwater?"}']]],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 30],
        ])]);
        $character = $this->character();
        $character->update(['enabled' => true]);
        $admin = $this->user(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/ai-community/'.$character->id.'/generate')->assertRedirect();
        $this->assertDatabaseCount('ai_community_drafts', 1);
        $this->assertDatabaseCount('feed_posts', 0);
        $this->actingAs($admin)->post('/admin/ai-community/'.$character->id.'/generate')->assertSessionHasErrors('generation');
        $this->assertDatabaseCount('ai_community_drafts', 1);
    }

    public function test_approval_and_disclosed_account_are_required_to_publish_once(): void
    {
        $character = $this->character();
        $admin = $this->user(['is_admin' => true]);
        $draft = $character->drafts()->create([
            'body' => 'How do you rotate around a compound?', 'body_hash' => AiCommunityDraft::hashBody('How do you rotate around a compound?'),
            'model' => 'test', 'status' => 'draft',
        ]);
        $this->actingAs($admin)->post('/admin/ai-community/drafts/'.$draft->id.'/publish')->assertStatus(409);
        $this->actingAs($admin)->post('/admin/ai-community/drafts/'.$draft->id.'/review', ['decision' => 'approved'])->assertRedirect();
        $this->actingAs($admin)->post('/admin/ai-community/drafts/'.$draft->id.'/publish')->assertStatus(409);
        $this->actingAs($admin)->post('/admin/ai-community/'.$character->id.'/provision')->assertRedirect();
        $character->refresh()->load('user.profile');
        $this->assertNotNull($character->user, 'Provisioning must assign a user');
        $this->assertTrue($character->user->is_ai_character, 'Provisioned user must be marked synthetic');
        $this->assertSame($character->name, $character->user->username);
        $this->assertNotNull($character->user->profile, 'Provisioning must attach a profile');
        $this->assertSame('public', $character->user->profile->profile_visibility);
        $this->assertStringContainsString($character->disclosure(), (string) $character->user->profile->bio);
        $this->actingAs($admin)->post('/admin/ai-community/drafts/'.$draft->id.'/publish')->assertRedirect();
        $this->assertSame(1, FeedPost::count());
        $this->assertTrue(FeedPost::first()->ai_user_declared);
        $this->actingAs($admin)->post('/admin/ai-community/drafts/'.$draft->id.'/publish')->assertStatus(409);
        $this->assertSame(1, FeedPost::count());
    }

    private function character(): AiCommunityCharacter
    {
        return AiCommunityCharacter::create(['name' => 'TestCharacter', 'language' => 'en', 'persona_voice' => 'Measured', 'topics' => 'Loadouts']);
    }

    private function user(array $attributes = []): User
    {
        $id = bin2hex(random_bytes(5));
        return User::create(array_merge(['name' => 'Tester', 'username' => 'tester_'.$id, 'email' => $id.'@example.test', 'password' => 'password'], $attributes));
    }
}
