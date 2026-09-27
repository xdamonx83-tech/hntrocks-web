<?php

namespace Tests\Feature;

use App\Models\ProfileModerationFlag;
use App\Models\User;
use App\Services\ProfileModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mild_gaming_profanity_does_not_enter_review_queue(): void
    {
        $user = User::factory()->create();
        $profile = $user->profile()->create([
            'profile_visibility' => 'public',
            'bio' => "I'm mainly here to fuck around because the concept is funny to me.",
        ]);

        app(ProfileModerationService::class)->scan($profile);

        $this->assertDatabaseCount('profile_moderation_flags', 0);
    }

    public function test_advertising_with_external_link_is_flagged(): void
    {
        $user = User::factory()->create();
        $profile = $user->profile()->create([
            'profile_visibility' => 'public',
            'bio' => 'Buy cheap Hunt accounts now: https://example.test/shop',
        ]);

        app(ProfileModerationService::class)->scan($profile);

        $this->assertDatabaseHas('profile_moderation_flags', [
            'user_id' => $user->id,
            'field' => 'bio',
            'category' => 'spam_advertising',
            'status' => ProfileModerationFlag::STATUS_PENDING,
            'score' => 80,
        ]);
    }

    public function test_direct_threat_is_high_priority(): void
    {
        $user = User::factory()->create();
        $profile = $user->profile()->create([
            'profile_visibility' => 'public',
            'bio' => 'I will kill you when I see you.',
        ]);

        app(ProfileModerationService::class)->scan($profile);

        $this->assertDatabaseHas('profile_moderation_flags', [
            'user_id' => $user->id,
            'category' => 'threats',
            'status' => ProfileModerationFlag::STATUS_PENDING,
            'score' => 90,
        ]);
    }

    public function test_old_pending_flag_becomes_superseded_after_text_is_cleaned(): void
    {
        $user = User::factory()->create();
        $profile = $user->profile()->create([
            'profile_visibility' => 'public',
            'bio' => 'Buy cheap Hunt accounts now: https://example.test/shop',
        ]);

        $service = app(ProfileModerationService::class);
        $service->scan($profile);

        $profile->forceFill([
            'bio' => 'Chill Hunt player looking for trios.',
        ])->save();

        $service->scan($profile);

        $this->assertDatabaseHas('profile_moderation_flags', [
            'user_id' => $user->id,
            'category' => 'spam_advertising',
            'status' => ProfileModerationFlag::STATUS_SUPERSEDED,
        ]);
    }
}
