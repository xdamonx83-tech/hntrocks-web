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

    public function test_standard_gamification_entries_have_complete_english_payloads(): void
    {
        $user = $this->user();

        $this->attachBadge($user, 'early-hunter', 'Early Hunter', 'Du hast deine ersten XP gesammelt.', 'Early Hunter', 'You collected your first XP.', 10);
        $this->attachBadge($user, 'profile-scout', 'Profil-Scout', 'Dein Profil ist mindestens zur Hälfte gepflegt.', 'Profile Scout', 'Your profile is at least half complete.', 20);
        $this->attachBadge($user, 'team-founder', 'Team-Gründer', 'Du hast ein Team erstellt.', 'Team Founder', 'You created a team.', 30);
        $this->attachBadge($user, 'media-scout', 'Medien-Scout', 'Du hast Medien in die Mediathek geladen.', 'Media Scout', 'You uploaded media to the media library.', 40);
        $this->attachBadge($user, 'cup-organizer', 'Cup-Organizer', 'Du hast deinen ersten Cup erstellt.', 'Cup Organizer', 'You created your first Cup.', 50);

        $this->createQuest('create-team', 'Team gründen', 'Erstelle dein erstes Team.', 'Create a Team', 'Create your first team.', 10);
        $this->createQuest('upload-media', 'Erstes Medium', 'Lade ein Medium in deine Mediathek.', 'First Media Upload', 'Upload media to your media library.', 20);
        $this->createQuest('first-cup-team', 'Cup-Einstieg', 'Erstelle dein erstes Cup-Team.', 'Cup Entry', 'Create your first Cup team.', 30);
        $this->createQuest('first-comment', 'Misch dich ein', 'Schreibe deinen ersten Kommentar.', 'Join the Conversation', 'Write your first comment.', 40);

        $payload = $this->withHeader('X-HNT-Locale', 'en')
            ->getAs($user, '/api/v1/me')
            ->assertOk()
            ->json('profile_summary');

        $badges = collect($payload['latest_badges'])->keyBy('slug');
        $quests = collect($payload['quests'])->keyBy('slug');

        $this->assertSame('Media Scout', $badges['media-scout']['name']);
        $this->assertSame('Team Founder', $badges['team-founder']['name']);
        $this->assertSame('Cup Organizer', $badges['cup-organizer']['name']);
        $this->assertSame('You collected your first XP.', $badges['early-hunter']['description']);
        $this->assertSame('Profile Scout', $badges['profile-scout']['name']);
        $this->assertSame('Create a Team', $quests['create-team']['name']);
        $this->assertSame('First Media Upload', $quests['upload-media']['name']);
        $this->assertSame('Cup Entry', $quests['first-cup-team']['name']);
        $this->assertSame('Create your first team.', $quests['create-team']['description']);
        $this->assertNotSame('Erstelle dein erstes Team.', $quests['create-team']['description']);
    }

    public function test_profile_update_response_keeps_request_locale_for_gamification_summary(): void
    {
        $user = $this->userWithGamification();

        $this->withHeader('X-HNT-Locale', 'en')
            ->postAs($user, '/api/v1/me/profile', ['headline' => 'Ready for the Bayou'])
            ->assertOk()
            ->assertJsonPath('profile_summary.latest_badges.0.name', 'Conversation Starter')
            ->assertJsonPath('profile_summary.quests.0.name', 'Join the Conversation')
            ->assertJsonPath('profile_summary.quests.0.description', 'Write your first comment.');
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

    private function attachBadge(
        User $user,
        string $slug,
        string $name,
        string $description,
        string $nameEn,
        string $descriptionEn,
        int $sortOrder,
    ): void {
        $badge = Badge::query()->create([
            'slug' => $slug,
            'name' => $name,
            'name_de' => $name,
            'name_en' => $nameEn,
            'category' => 'test',
            'rarity' => 'common',
            'icon' => '*',
            'description' => $description,
            'description_de' => $description,
            'description_en' => $descriptionEn,
            'xp_reward' => 15,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);

        $user->badges()->attach($badge->id, [
            'awarded_at' => now()->addSeconds($sortOrder),
        ]);
    }

    private function createQuest(
        string $slug,
        string $name,
        string $description,
        string $nameEn,
        string $descriptionEn,
        int $sortOrder,
    ): void {
        Quest::query()->create([
            'slug' => $slug,
            'name' => $name,
            'name_de' => $name,
            'name_en' => $nameEn,
            'category' => 'test',
            'action' => $slug,
            'target_count' => 1,
            'xp_reward' => 15,
            'badge_slug' => null,
            'description' => $description,
            'description_de' => $description,
            'description_en' => $descriptionEn,
            'period' => null,
            'is_repeatable' => false,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
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
