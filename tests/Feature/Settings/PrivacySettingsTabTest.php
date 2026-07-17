<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacySettingsTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrated_privacy_tab_updates_only_enforced_settings(): void
    {
        $user = User::factory()->create();
        $user->privacySettings()->create([
            'profile_visibility' => 'public',
            'allow_messages_from' => 'following',
            'allow_team_invites' => false,
            'allow_lfg_invites' => true,
            'show_online_status' => true,
            'show_activity_feed' => false,
            'show_gamification' => false,
            'data_usage_consent' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('settings.privacy.update'), [
                'settings_section' => 'privacy',
                'profile_visibility' => 'private',
                'show_activity_feed' => '1',
            ]);

        $response->assertRedirect(route('account.settings.edit').'#privacy');

        $settings = $user->privacySettings()->firstOrFail()->refresh();
        $this->assertSame('private', $settings->profile_visibility);
        $this->assertFalse($settings->show_online_status);
        $this->assertTrue($settings->show_activity_feed);

        $this->assertSame('following', $settings->allow_messages_from);
        $this->assertFalse($settings->allow_team_invites);
        $this->assertTrue($settings->allow_lfg_invites);
        $this->assertFalse($settings->show_gamification);
        $this->assertTrue($settings->data_usage_consent);

        $this->assertSame('private', $user->profile()->value('profile_visibility'));
    }
}
