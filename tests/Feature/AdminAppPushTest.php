<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppPushTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_open_or_send_admin_push(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/admin/app-push')->assertForbidden();
        $this->actingAs($user)->post('/admin/app-push', [
            'target_user_id' => $user->id,
            'title' => 'Test',
            'body' => 'Body',
            'confirm_send' => '1',
        ])->assertForbidden();
    }

    public function test_admin_push_requires_allowed_action_url(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $target = $this->user();

        $this->actingAs($admin)->post('/admin/app-push', [
            'target_user_id' => $target->id,
            'title' => 'Test',
            'body' => 'Body',
            'action_url' => 'https://example.com/nope',
            'confirm_send' => '1',
        ])->assertSessionHasErrors('action_url');
    }

    public function test_admin_push_writes_log_when_fcm_is_not_configured(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $target = $this->user();

        $this->actingAs($admin)->post('/admin/app-push', [
            'target_user_id' => $target->id,
            'title' => 'Wichtiger Hinweis',
            'body' => 'Bitte Notifications oeffnen.',
            'action_url' => 'hntrocks://notifications',
            'confirm_send' => '1',
        ])->assertSessionHasErrors('fcm');

        $this->assertDatabaseHas('app_push_logs', [
            'admin_user_id' => $admin->id,
            'target_user_id' => $target->id,
            'title' => 'Wichtiger Hinweis',
            'action_url' => 'hntrocks://notifications',
            'sent_count' => 0,
            'failed_count' => 0,
        ]);
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
