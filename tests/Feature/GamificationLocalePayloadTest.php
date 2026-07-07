<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Badge;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationLocalePayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_profile_summary_uses_x_hnt_locale_for_badges_and_quests(): void
    {
        $user = $this->userWithGamification();

        $response = $this->withHeader('X-HNT-Locale', 'en')
            ->getAs($user, '/api/v1/me')
            ->assertOk();

        $response
            ->assertJsonPath('profile_summary.latest_badges.0.name', 'Conversation Starter')
            ->assertJsonPath('profile_summary.latest_badges.0.description', 'You wrote your first comment.')
            ->assertJsonPath('profile_summary.quests.0.name', 'Join the Conversation');
    }

    public function test_me_profile_summary_uses_german_locale_and_fallbacks(): void
    {
        $user = $this->userWithGamification();

        $this->withHeader('X-HNT-Locale', 'de')
            ->getAs($user, '/api/v1/me')
            ->assertOk()
            ->assertJsonPath('profile_summary.latest_badges.0.name', 'Gespraechsstarter')
            ->assertJsonPath('profile_summary.latest_badges.0.description', 'Du hast deinen ersten Kommentar geschrieben.')
            ->assertJsonPath('profile_summary.quests.0.name', 'Misch dich ein');
    }

    public function test_query_locale_en_takes_precedence(): void
    {
        $user = $this->userWithGamification();

        $this->withHeader('X-HNT-Locale', 'de')
            ->getAs($user, '/api/v1/me?locale=en')
            ->assertOk()
            ->assertJsonPath('profile_summary.latest_badges.0.name', 'Conversation Starter')
            ->assertJsonPath('profile_summary.quests.0.name', 'Join the Conversation');
    }

    public function test_accept_language_en_is_supported(): void
    {
        $user = $this->userWithGamification();

        $this->withHeader('Accept-Language', 'en-US,en;q=0.9,de;q=0.8')
            ->getAs($user, '/api/v1/me')
            ->assertOk()
            ->assertJsonPath('profile_summary.latest_badges.0.name', 'Conversation Starter')
            ->assertJsonPath('profile_summary.quests.0.name', 'Join the Conversation');
    }

    public function test_empty_english_fields_fall_back_to_legacy_text(): void
    {
        $user = $this->userWithGamification([
            'badge_name_en' => null,
            'badge_description_en' => null,
            'quest_name_en' => null,
            'quest_description_en' => null,
        ]);

        $this->withHeader('X-HNT-Locale', 'en')
            ->getAs($user, '/api/v1/me')
            ->assertOk()
            ->assertJsonPath('profile_summary.latest_badges.0.name', 'Gespraechsstarter')
            ->assertJsonPath('profile_summary.latest_badges.0.description', 'Du hast deinen ersten Kommentar geschrieben.')
            ->assertJsonPath('profile_summary.quests.0.name', 'Misch dich ein');
    }

    public function test_profile_sections_are_locale_aware(): void
    {
        $user = $this->userWithGamification();

        $this->withHeader('X-HNT-Locale', 'en')
            ->getAs($user, '/api/v1/me/profile-sections/badges')
            ->assertOk()
            ->assertJsonPath('items.0.name', 'Conversation Starter')
            ->assertJsonPath('items.0.description', 'You wrote your first comment.');

        $this->withHeader('X-HNT-Locale', 'en')
            ->getAs($user, '/api/v1/me/profile-sections/quests')
            ->assertOk()
            ->assertJsonPath('items.0.name', 'Join the Conversation');
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->getJson($uri);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Gamification locale API test')['access_token'];
    }

    private function userWithGamification(array $overrides = []): User
    {
        $user = $this->user();

        $badge = Badge::query()->create([
            'slug' => 'conversation-starter',
            'name' => 'Gespraechsstarter',
            'name_de' => 'Gespraechsstarter',
            'name_en' => array_key_exists('badge_name_en', $overrides) ? $overrides['badge_name_en'] : 'Conversation Starter',
            'category' => 'feed',
            'rarity' => 'common',
            'icon' => '*',
            'description' => 'Du hast deinen ersten Kommentar geschrieben.',
            'description_de' => 'Du hast deinen ersten Kommentar geschrieben.',
            'description_en' => array_key_exists('badge_description_en', $overrides) ? $overrides['badge_description_en'] : 'You wrote your first comment.',
            'xp_reward' => 15,
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $user->badges()->attach($badge->id, [
            'awarded_at' => now(),
        ]);

        Quest::query()->create([
            'slug' => 'first-comment',
            'name' => 'Misch dich ein',
            'name_de' => 'Misch dich ein',
            'name_en' => array_key_exists('quest_name_en', $overrides) ? $overrides['quest_name_en'] : 'Join the Conversation',
            'category' => 'start',
            'action' => 'feed_comment_created',
            'target_count' => 1,
            'xp_reward' => 15,
            'badge_slug' => 'conversation-starter',
            'description' => 'Schreibe deinen ersten Kommentar.',
            'description_de' => 'Schreibe deinen ersten Kommentar.',
            'description_en' => array_key_exists('quest_description_en', $overrides) ? $overrides['quest_description_en'] : 'Write your first comment.',
            'period' => null,
            'is_repeatable' => false,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        return $user;
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
