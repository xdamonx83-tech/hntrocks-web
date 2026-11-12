<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyModerationReportsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_list_and_detail_are_visible_only_to_reporter(): void
    {
        $owner = $this->user();
        $other = $this->user();

        $own = $this->report($owner, [
            'status' => 'resolved',
            'body' => 'Offensive post',
            'resolution_note' => 'INTERNAL: Do not disclose staff details.',
            'public_response' => 'We reviewed the report.',
        ]);
        $foreign = $this->report($other, [
            'body' => 'Private report from a different hunter',
            'resolution_note' => 'Private moderation action',
        ]);

        $this->withToken($this->token($owner))
            ->getJson('/api/v1/reports/mine')
            ->assertOk()
            ->assertJsonPath('data.0.id', $own->id)
            ->assertJsonPath('data.0.status', 'resolved')
            ->assertJsonPath('data.0.public_response', 'We reviewed the report.')
            ->assertJsonPath('pagination.total', 1)
            ->assertDontSee('INTERNAL:')
            ->assertDontSee('Private report from a different hunter');

        $this->withToken($this->token($owner))
            ->getJson('/api/v1/reports/mine/'.$own->id)
            ->assertOk()
            ->assertJsonPath('data.body', 'Offensive post')
            ->assertJsonPath('data.public_response', 'We reviewed the report.')
            ->assertDontSee('resolution_note');

        $this->withToken($this->token($owner))
            ->getJson('/api/v1/reports/mine/'.$foreign->id)
            ->assertNotFound();
    }

    public function test_moderation_report_history_requires_authentication(): void
    {
        $this->getJson('/api/v1/reports/mine')->assertUnauthorized();
        $this->getJson('/api/v1/reports/mine/1')->assertUnauthorized();
    }

    public function test_moderation_report_history_is_paginated(): void
    {
        $owner = $this->user();
        for ($i = 0; $i < 22; $i++) {
            $this->report($owner, ['body' => 'Test report '.$i]);
        }

        $this->withToken($this->token($owner))
            ->getJson('/api/v1/reports/mine?page=1')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('pagination.total', 22);

        $this->withToken($this->token($owner))
            ->getJson('/api/v1/reports/mine?page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    private function report(User $owner, array $changes = []): Report
    {
        return Report::query()->create(array_merge([
            'reporter_id' => $owner->id,
            'reportable_type' => User::class,
            'reportable_id' => $this->user()->id,
            'category' => 'user',
            'reason' => 'abuse',
            'body' => 'Content report',
            'status' => 'open',
        ], $changes));
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'My reports API test')['access_token'];
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
