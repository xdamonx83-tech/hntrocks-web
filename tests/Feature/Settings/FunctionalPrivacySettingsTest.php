<?php

namespace Tests\Feature\Settings;

use App\Models\Friendship;
use App\Models\User;
use App\Models\UserPrivacySetting;
use App\Services\UserPrivacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunctionalPrivacySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrated_privacy_tab_saves_all_available_rules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.privacy.update'), [
                'settings_section' => 'privacy',
                'profile_visibility' => 'registered',
                'allow_messages_from' => 'following',
                'show_online_status' => '1',
                'show_activity_feed' => '0',
                'show_gamification' => '0',
            ])
            ->assertRedirect(route('account.settings.edit').'#privacy');

        $this->assertDatabaseHas('user_privacy_settings', [
            'user_id' => $user->id,
            'profile_visibility' => 'registered',
            'allow_messages_from' => 'following',
            'show_online_status' => true,
            'show_activity_feed' => false,
            'show_gamification' => false,
        ]);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'profile_visibility' => 'registered',
        ]);
    }

    public function test_message_privacy_accepts_contacts_and_rejects_other_users(): void
    {
        $recipient = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

        UserPrivacySetting::create([
            'user_id' => $recipient->id,
            'allow_messages_from' => 'following',
        ]);

        [$one, $two] = Friendship::pairIds($recipient, $friend);
        Friendship::create([
            'user_one_id' => $one,
            'user_two_id' => $two,
            'requester_id' => $friend->id,
            'recipient_id' => $recipient->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        $privacy = app(UserPrivacyService::class);

        $this->assertTrue($privacy->canMessage($friend, $recipient));
        $this->assertFalse($privacy->canMessage($stranger, $recipient));
    }

    public function test_message_recipient_query_hides_users_who_do_not_accept_the_sender(): void
    {
        $sender = User::factory()->create();
        $available = User::factory()->create();
        $unavailable = User::factory()->create();

        UserPrivacySetting::create([
            'user_id' => $available->id,
            'allow_messages_from' => 'registered',
        ]);
        UserPrivacySetting::create([
            'user_id' => $unavailable->id,
            'allow_messages_from' => 'nobody',
        ]);

        $ids = app(UserPrivacyService::class)
            ->applyToMessageRecipientQuery(User::query()->where('id', '!=', $sender->id), $sender)
            ->pluck('id');

        $this->assertTrue($ids->contains($available->id));
        $this->assertFalse($ids->contains($unavailable->id));
    }

    public function test_activity_and_gamification_remain_visible_to_the_owner_only(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        UserPrivacySetting::create([
            'user_id' => $owner->id,
            'show_activity_feed' => false,
            'show_gamification' => false,
        ]);

        $privacy = app(UserPrivacyService::class);

        $this->assertTrue($privacy->canViewActivity($owner, $owner));
        $this->assertTrue($privacy->canViewGamification($owner, $owner));
        $this->assertFalse($privacy->canViewActivity($viewer, $owner));
        $this->assertFalse($privacy->canViewGamification($viewer, $owner));
    }
}
