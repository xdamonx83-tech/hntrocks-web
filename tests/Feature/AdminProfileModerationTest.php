<?php

namespace Tests\Feature;

use App\Models\ProfileModerationFlag;
use App\Models\User;
use App\Services\ProfileModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProfileModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_user_editor(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $user->profile()->create([
            'profile_visibility' => 'public',
            'headline' => 'Hunter',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $user));

        $response->assertOk();
        $response->assertSee($user->username);
        $response->assertSee('Nutzer bearbeiten');
    }

    public function test_non_admin_cannot_open_user_editor(): void
    {
        $viewer = User::factory()->create(['is_admin' => false]);
        $user = User::factory()->create();

        $response = $this->actingAs($viewer)->get(route('admin.users.edit', $user));

        $response->assertForbidden();
    }

    public function test_admin_can_confirm_and_dismiss_profile_flags(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $profile = $user->profile()->create([
            'profile_visibility' => 'public',
            'bio' => 'Buy cheap Hunt accounts: https://example.test/shop',
        ]);

        app(ProfileModerationService::class)->scan($profile);

        $flag = ProfileModerationFlag::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.profile-moderation.review', $flag), [
                'decision' => ProfileModerationFlag::STATUS_CONFIRMED,
                'admin_note' => 'Manuell geprüft.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('profile_moderation_flags', [
            'id' => $flag->id,
            'status' => ProfileModerationFlag::STATUS_CONFIRMED,
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_user_profile_update_scans_bio_automatically(): void
    {
        $user = User::factory()->create();

        $user->profile()->create([
            'profile_visibility' => 'public',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'headline' => null,
                'bio' => 'Buy cheap Hunt accounts: https://example.test/shop',
                'platform' => null,
                'playstyle' => null,
                'region' => null,
                'language' => null,
                'hunt_role' => null,
                'discord_name' => null,
                'steam_url' => null,
                'twitch_url' => null,
                'youtube_url' => null,
                'is_lfg_available' => false,
                'profile_visibility' => 'public',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('profile_moderation_flags', [
            'user_id' => $user->id,
            'field' => 'bio',
            'category' => 'spam_advertising',
            'status' => ProfileModerationFlag::STATUS_PENDING,
        ]);
    }
}
