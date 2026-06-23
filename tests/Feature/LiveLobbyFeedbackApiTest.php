<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\LiveLobby;
use App\Models\LiveLobbyFeedbackRequest;
use App\Models\User;
use App\Services\LiveLobbyFeedbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LiveLobbyFeedbackApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_creates_only_participant_pairs_without_self_feedback_and_is_idempotent(): void
    {
        $creator = $this->user();
        $member = $this->user();
        $outsider = $this->user();
        $lobby = $this->lobby($creator, $member);
        $service = app(LiveLobbyFeedbackService::class);

        $this->assertSame(2, $service->createRequestsForLobby($lobby));
        $this->assertSame(0, $service->createRequestsForLobby($lobby));
        $this->assertDatabaseCount('live_lobby_feedback_requests', 2);
        $this->assertDatabaseMissing('live_lobby_feedback_requests', ['reviewer_id' => $creator->id, 'target_user_id' => $creator->id]);
        $this->assertDatabaseMissing('live_lobby_feedback_requests', ['reviewer_id' => $member->id, 'target_user_id' => $member->id]);
        $this->assertDatabaseMissing('live_lobby_feedback_requests', ['reviewer_id' => $outsider->id]);
        $this->assertDatabaseMissing('live_lobby_feedback_requests', ['target_user_id' => $outsider->id]);
    }

    public function test_notifications_are_only_sent_once_the_request_is_available(): void
    {
        Carbon::setTestNow('2026-06-23 12:00:00');

        try {
            $creator = $this->user();
            $member = $this->user();
            $lobby = $this->lobby($creator, $member);
            $service = app(LiveLobbyFeedbackService::class);
            $service->createRequestsForLobby($lobby);

            $this->assertSame(0, $service->sendAvailableNotifications());
            $this->assertDatabaseCount('user_notifications', 0);

            Carbon::setTestNow(now()->addMinutes(61));
            $this->assertSame(2, $service->sendAvailableNotifications());
            $this->assertSame(0, $service->sendAvailableNotifications());
            $this->assertDatabaseCount('user_notifications', 2);
            $this->assertDatabaseHas('user_notifications', [
                'user_id' => $creator->id,
                'actor_id' => null,
                'type' => 'live_lobby_feedback',
                'action_url' => '/ready-lobbies/'.$lobby->public_id.'/feedback',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_command_creates_expires_and_notifies_feedback_requests(): void
    {
        $creator = $this->user();
        $member = $this->user();
        $lobby = $this->lobby($creator, $member);
        $lobby->forceFill(['status' => 'closed', 'created_at' => now()->subMinutes(61)])->save();

        $this->artisan('live-lobbies:queue-feedback')
            ->expectsOutputToContain('created_requests: 2')
            ->expectsOutputToContain('notifications_sent: 2')
            ->expectsOutputToContain('expired_requests: 0')
            ->assertSuccessful();
    }

    public function test_reviewer_can_submit_and_values_are_deduplicated_in_request_order(): void
    {
        [$feedbackRequest, $reviewer] = $this->feedbackRequest();

        $response = $this->postAs($reviewer, '/api/v1/ready-lobby-feedback/'.$feedbackRequest->public_id.'/submit', [
            'positive_tags' => ['helpful', 'reliable', 'helpful'],
            'private_flags' => ['left_early', 'not_again', 'left_early'],
            'comment' => 'Private note',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('request.status', 'completed')
            ->assertJsonPath('feedback.positive_tags', ['helpful', 'reliable'])
            ->assertJsonPath('feedback.private_flags', ['left_early', 'not_again']);

        $response->assertJsonMissingPath('request.target_user.private_flags');
        $this->assertDatabaseHas('live_lobby_feedback_requests', ['id' => $feedbackRequest->id, 'status' => 'completed']);
        $this->assertDatabaseHas('live_lobby_feedback', [
            'feedback_request_id' => $feedbackRequest->id,
            'reviewer_id' => $reviewer->id,
            'comment' => 'Private note',
        ]);
    }

    public function test_foreign_user_cannot_submit_another_reviewers_request(): void
    {
        [$feedbackRequest] = $this->feedbackRequest();

        $this->postAs($this->user(), '/api/v1/ready-lobby-feedback/'.$feedbackRequest->public_id.'/submit')
            ->assertForbidden();
    }

    public function test_invalid_tags_and_flags_are_rejected(): void
    {
        [$feedbackRequest, $reviewer] = $this->feedbackRequest();

        $this->postAs($reviewer, '/api/v1/ready-lobby-feedback/'.$feedbackRequest->public_id.'/submit', [
            'positive_tags' => ['toxic_label'],
            'private_flags' => ['public_downvote'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['positive_tags.0', 'private_flags.0']);
    }

    public function test_reviewer_can_dismiss_a_pending_request(): void
    {
        [$feedbackRequest, $reviewer] = $this->feedbackRequest();

        $this->postAs($reviewer, '/api/v1/ready-lobby-feedback/'.$feedbackRequest->public_id.'/dismiss')
            ->assertOk()
            ->assertJsonPath('request.status', 'dismissed');

        $this->assertDatabaseHas('live_lobby_feedback_requests', ['id' => $feedbackRequest->id, 'status' => 'dismissed']);
    }

    public function test_expired_request_cannot_be_submitted(): void
    {
        [$feedbackRequest, $reviewer] = $this->feedbackRequest(['expires_at' => now()->subMinute()]);

        $this->postAs($reviewer, '/api/v1/ready-lobby-feedback/'.$feedbackRequest->public_id.'/submit')
            ->assertUnprocessable();

        $this->assertDatabaseMissing('live_lobby_feedback', ['feedback_request_id' => $feedbackRequest->id]);
    }

    public function test_request_cannot_be_submitted_before_it_is_available(): void
    {
        [$feedbackRequest, $reviewer] = $this->feedbackRequest(['available_at' => now()->addMinute()]);

        $this->postAs($reviewer, '/api/v1/ready-lobby-feedback/'.$feedbackRequest->public_id.'/submit')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This feedback request is not available yet.');

        $this->assertDatabaseMissing('live_lobby_feedback', ['feedback_request_id' => $feedbackRequest->id]);
        $this->assertDatabaseHas('live_lobby_feedback_requests', [
            'id' => $feedbackRequest->id,
            'status' => 'pending',
        ]);
    }

    public function test_request_list_only_contains_currently_available_pending_requests_for_the_reviewer(): void
    {
        [$feedbackRequest, $reviewer, $target] = $this->feedbackRequest();
        $otherReviewer = $this->user();
        $this->feedbackRequest([], $otherReviewer, $target);
        $this->feedbackRequest(['available_at' => now()->addMinute()], $reviewer);
        $this->feedbackRequest(['available_at' => null], $reviewer);
        $this->feedbackRequest(['expires_at' => now()->subMinute()], $reviewer);
        $this->feedbackRequest(['status' => 'completed'], $reviewer);

        $response = $this->getAs($reviewer, '/api/v1/ready-lobby-feedback/requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $feedbackRequest->public_id)
            ->assertJsonPath('data.0.target_user.id', $target->id);

        $response->assertJsonMissingPath('data.0.target_user.private_flags');
        $response->assertJsonMissingPath('data.0.private_flags');
        $response->assertJsonMissingPath('data.0.comment');
    }

    private function feedbackRequest(array $overrides = [], ?User $reviewer = null, ?User $target = null): array
    {
        $reviewer ??= $this->user();
        $target ??= $this->user();
        $lobby = $this->lobby($reviewer, $target);

        $feedbackRequest = LiveLobbyFeedbackRequest::query()->create(array_merge([
            'live_lobby_id' => $lobby->id,
            'reviewer_id' => $reviewer->id,
            'target_user_id' => $target->id,
            'status' => 'pending',
            'available_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
        ], $overrides));

        return [$feedbackRequest, $reviewer, $target];
    }

    private function lobby(User $creator, User $member): LiveLobby
    {
        $lobby = LiveLobby::query()->create([
            'creator_id' => $creator->id,
            'mode' => 'duo',
            'slots_total' => 2,
            'slots_filled' => 2,
            'platform' => 'pc',
            'crossplay_pool' => 'pc',
            'voice_required' => false,
            'status' => 'full',
            'expires_at' => now()->addMinutes(5),
            'full_at' => now(),
        ]);

        $lobby->members()->createMany([
            ['user_id' => $creator->id, 'role' => 'creator', 'platform' => 'pc', 'joined_at' => now()],
            ['user_id' => $member->id, 'role' => 'member', 'platform' => 'pc', 'joined_at' => now()],
        ]);

        return $lobby;
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->getJson($uri);
    }

    private function postAs(User $user, string $uri, array $payload = [])
    {
        return $this->withToken($this->token($user))->postJson($uri, $payload);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Live Lobby Feedback API test')['access_token'];
    }

    private function user(): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create([
            'name' => 'Hunter '.$suffix,
            'username' => 'hunter_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
    }
}
