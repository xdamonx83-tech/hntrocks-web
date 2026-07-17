<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingsTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_save_real_notification_categories(): void
    {
        $user = User::factory()->create();
        $payload = array_fill_keys(UserNotificationSetting::FIELDS, true);
        $payload['referrals'] = false;
        $payload['settings_section'] = 'notifications';

        $response = $this
            ->actingAs($user)
            ->put(route('account.settings.update'), $payload);

        $response->assertRedirect(route('account.settings.edit').'#notifications');

        $settings = $user->notificationSettings()->firstOrFail();
        foreach (UserNotificationSetting::FIELDS as $field) {
            $this->assertSame($field !== 'referrals', (bool) $settings->{$field});
        }
    }

    public function test_omitted_notification_categories_are_saved_as_disabled(): void
    {
        $user = User::factory()->create();
        $user->notificationSettings()->create(
            array_fill_keys(UserNotificationSetting::FIELDS, true)
        );

        $this
            ->actingAs($user)
            ->put(route('account.settings.update'), [
                'settings_section' => 'notifications',
            ])
            ->assertRedirect(route('account.settings.edit').'#notifications');

        $settings = $user->notificationSettings()->firstOrFail()->refresh();
        foreach (UserNotificationSetting::FIELDS as $field) {
            $this->assertFalse((bool) $settings->{$field});
        }
    }
}
