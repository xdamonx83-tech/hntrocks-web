<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_feedback_ticket(): void
    {
        $user = $this->user();

        $this->postAs($user, '/api/v1/feedback-tickets', [
            'type' => 'bug',
            'subject' => 'Map marker does not open',
            'message' => 'Tapping a map marker does nothing on my device.',
            'meta' => [
                'platform' => 'android',
                'language' => 'de',
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'bug')
            ->assertJsonPath('data.subject', 'Map marker does not open')
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('feedback_tickets', [
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Map marker does not open',
            'status' => 'open',
        ]);
    }

    public function test_feedback_ticket_requires_valid_fields(): void
    {
        $this->postAs($this->user(), '/api/v1/feedback-tickets', [
            'type' => 'abuse',
            'subject' => '',
            'message' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'subject', 'message']);
    }

    public function test_feedback_ticket_requires_authentication(): void
    {
        $this->postJson('/api/v1/feedback-tickets', [
            'type' => 'idea',
            'subject' => 'Better filters',
            'message' => 'Please add more filters.',
        ])->assertUnauthorized();
    }

    private function postAs(User $user, string $uri, array $payload = [])
    {
        return $this->withToken($this->token($user))->postJson($uri, $payload);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Feedback ticket API test')['access_token'];
    }

    private function user(array $attributes = []): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create(array_merge([
            'name' => 'Hunter '.$suffix,
            'username' => 'hunter_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ], $attributes));
    }
}
