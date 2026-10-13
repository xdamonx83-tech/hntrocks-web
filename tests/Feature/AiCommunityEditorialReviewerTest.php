<?php

namespace Tests\Feature;

use App\Models\AiCommunityCharacter;
use App\Models\AiCommunityDraft;
use App\Models\FeedPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiCommunityEditorialReviewerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $fakeReviewResponse;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ai-community.enabled', true);
        config()->set('ai-community.review_enabled', true);
        config()->set('ai-community.key', 'test-key');
        $this->fakeReviewResponse = $this->validResponse();
        Http::fake(fn () => Http::response($this->fakeReviewResponse));
    }

    public function test_non_admin_cannot_review(): void
    {
        $draft = $this->draft();
        $this->actingAs($this->user())->post($this->url($draft))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_disabled_or_missing_key_sends_no_request(): void
    {
        $draft = $this->draft();
        $admin = $this->user(['is_admin' => true]);
        config()->set('ai-community.review_enabled', false);
        $this->actingAs($admin)->post($this->url($draft))->assertSessionHasErrors('quality_review');
        config()->set('ai-community.review_enabled', true);
        config()->set('ai-community.key', null);
        $this->actingAs($admin)->post($this->url($draft))->assertSessionHasErrors('quality_review');
        Http::assertNothingSent();
    }

    public function test_success_stores_advice_without_changing_draft_or_publishing(): void
    {
        $draft = $this->draft();
        $body = $draft->body;
        $this->actingAs($this->user(['is_admin' => true]))->post($this->url($draft))->assertRedirect();
        $draft->refresh();
        $this->assertSame('draft', $draft->status);
        $this->assertSame($body, $draft->body);
        $this->assertSame('revise', $draft->quality_review_decision);
        $this->assertSame(3, $draft->quality_review_naturalness);
        $this->assertSame(4, $draft->quality_review_accuracy);
        $this->assertNotNull($draft->quality_reviewed_at);
        $this->assertSame(120, $draft->quality_review_input_tokens);
        $this->assertSame(35, $draft->quality_review_output_tokens);
        $this->assertGreaterThan(0, $draft->quality_review_cost_microusd);
        $this->assertSame(0, FeedPost::count());
        Http::assertSent(fn ($request) => $request['temperature'] <= 0.2
            && $request['max_tokens'] === 240
            && str_contains($request['messages'][1]['content'], $body));
        $this->actingAs($this->user(['is_admin' => true]))->get('/admin/ai-community')
            ->assertSee('KI-Einschätzung')->assertSee('REVISE')->assertSee('Natürlichkeit 3/5');
    }

    public function test_prompt_uses_strict_calibrated_v6_criteria(): void
    {
        $draft = $this->draft();
        $reviewer = new \App\Services\AiCommunityEditorialReviewer();
        $messages = $reviewer->promptContext($draft);
        $this->assertStringContainsString('Naturalness rubric:', $messages[0]['content']);
        $this->assertStringContainsString('PASS only if both scores >= 4', $messages[0]['content']);
        $this->assertStringContainsString('ein typisches Hunt-Audio-Signal', $messages[0]['content']);
        $this->assertStringContainsString('Bucht', $messages[0]['content']);
        $this->assertStringContainsString('NO browsing', $messages[0]['content']);
    }

    public function test_an_inconsistent_ai_pass_is_downgraded_to_revise(): void
    {
        $this->fakeReviewResponse = [
            'choices' => [['message' => ['content' => json_encode([
                'decision' => 'pass', 'naturalness' => 2, 'hunt_accuracy' => 5,
                'reason' => 'This is an artificial-sounding and stiff line.',
            ])]]],
            'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 35],
        ];
        $draft = $this->draft();
        $this->actingAs($this->user(['is_admin' => true]))->post($this->url($draft))->assertRedirect();
        $draft->refresh();
        $this->assertSame('revise', $draft->quality_review_decision);
        $this->assertSame(2, $draft->quality_review_naturalness);
        $this->assertStringContainsString('Scores below the PASS threshold', $draft->quality_review_reason);
        $this->assertDatabaseCount('feed_posts', 0);
    }

    public function test_high_quality_scores_can_remain_an_advisory_pass(): void
    {
        $this->fakeReviewResponse = [
            'choices' => [['message' => ['content' => json_encode([
                'decision' => 'pass', 'naturalness' => 4, 'hunt_accuracy' => 4,
                'reason' => 'No significant defects are evident in the draft.',
            ])]]],
            'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 35],
        ];
        $draft = $this->draft();
        $this->actingAs($this->user(['is_admin' => true]))->post($this->url($draft))->assertRedirect();
        $this->assertSame('pass', $draft->refresh()->quality_review_decision);
        $this->assertSame('draft', $draft->status);
        $this->assertDatabaseCount('feed_posts', 0);
    }

    public function test_repeat_review_is_blocked_without_second_request(): void
    {
        $draft = $this->draft();
        $this->actingAs($this->user(['is_admin' => true]))->post($this->url($draft))->assertRedirect();
        $this->post($this->url($draft))->assertSessionHasErrors('quality_review');
        Http::assertSentCount(1);
    }

    public function test_invalid_json_does_not_store_a_pass(): void
    {
        $this->fakeReviewResponse = [
            'choices' => [['message' => ['content' => '{"decision":"pass", broken']]],
            'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 35],
        ];
        $draft = $this->draft();
        $this->actingAs($this->user(['is_admin' => true]))->post($this->url($draft))->assertSessionHasErrors('quality_review');
        $this->assertNull($draft->refresh()->quality_review_decision);
        $this->assertNull($draft->quality_reviewed_at);
        $this->assertSame(0, $draft->quality_review_cost_microusd);
    }

    public function test_edit_clears_advice_but_keeps_cost_accounting(): void
    {
        $draft = $this->draft();
        $this->actingAs($this->user(['is_admin' => true]))->post($this->url($draft))->assertRedirect();
        $cost = $draft->refresh()->quality_review_cost_microusd;
        $this->put('/admin/ai-community/drafts/'.$draft->id, ['body' => 'Is the Romero your pick for a simple loadout?'])->assertRedirect();
        $draft->refresh();
        $this->assertNull($draft->quality_review_decision);
        $this->assertNull($draft->quality_review_naturalness);
        $this->assertNull($draft->quality_review_accuracy);
        $this->assertNull($draft->quality_review_reason);
        $this->assertSame($cost, $draft->quality_review_cost_microusd);
    }

    public function test_budget_blocks_request_when_generation_cost_used_it(): void
    {
        $draft = $this->draft();
        $draft->update(['estimated_cost_microusd' => 1000000]);
        config()->set('ai-community.monthly_budget_microusd', 1000000);
        $this->actingAs($this->user(['is_admin' => true]))->post($this->url($draft))->assertSessionHasErrors('quality_review');
        Http::assertNothingSent();
    }

    public function test_review_cost_counts_against_generation_budget(): void
    {
        $draft = $this->draft();
        $admin = $this->user(['is_admin' => true]);
        $this->actingAs($admin)->post($this->url($draft))->assertRedirect();
        $cost = $draft->refresh()->quality_review_cost_microusd;
        config()->set('ai-community.monthly_budget_microusd', $cost);
        $draft->character->update(['enabled' => true]);
        $this->post('/admin/ai-community/'.$draft->character_id.'/generate')->assertSessionHasErrors('generation');
        Http::assertSentCount(1);
    }

    public function test_approved_draft_cannot_be_quality_reviewed(): void
    {
        $draft = $this->draft();
        $draft->update(['status' => 'approved']);
        $this->actingAs($this->user(['is_admin' => true]))->post($this->url($draft))->assertSessionHasErrors('quality_review');
        Http::assertNothingSent();
    }

    private function validResponse(): array
    {
        return [
            'choices' => [['message' => ['content' => json_encode([
                'decision' => 'revise', 'naturalness' => 3, 'hunt_accuracy' => 4,
                'reason' => 'The wording sounds a little stiff.',
            ])]]],
            'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 35],
        ];
    }

    private function draft(): AiCommunityDraft
    {
        $character = AiCommunityCharacter::create([
            'name' => 'ReviewTester', 'language' => 'en', 'persona_voice' => 'Casual', 'topics' => 'Loadouts',
        ]);
        $body = 'Which weapon fits a relaxed Hunt night?';
        return $character->drafts()->create([
            'body' => $body, 'body_hash' => AiCommunityDraft::hashBody($body),
            'model' => 'test', 'status' => 'draft', 'post_kind' => 'open_question',
            'editorial_angle' => 'one_weapon_choice',
        ]);
    }

    private function user(array $attributes = []): User
    {
        $id = bin2hex(random_bytes(5));
        return User::create(array_merge([
            'name' => 'Tester', 'username' => 'tester_'.$id,
            'email' => $id.'@example.test', 'password' => 'password',
        ], $attributes));
    }

    private function url(AiCommunityDraft $draft): string
    {
        return '/admin/ai-community/drafts/'.$draft->id.'/quality-review';
    }
}
