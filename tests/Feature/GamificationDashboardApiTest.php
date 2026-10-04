<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Badge;
use App\Models\Quest;
use App\Models\User;
use App\Models\XpEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_badges_quests_and_xp_without_mutating_them(): void
    {
        $user = User::query()->create([
            'name' => 'Hunter',
            'username' => 'hunter_'.bin2hex(random_bytes(5)),
            'email' => bin2hex(random_bytes(5)).'@example.test',
            'password' => 'password',
            'status' => 'active',
            'xp_total' => 375,
            'level' => 2,
        ]);

        $earned = $this->badge('earned', 'Verdient', 'Earned');
        $locked = $this->badge('locked', 'Gesperrt', 'Locked');
        $user->badges()->attach($earned->id, ['awarded_at' => now()]);

        $quest = $this->quest('comment', false);
        $this->quest('weekly', true);
        $user->questProgress()->create([
            'quest_id' => $quest->id,
            'progress_count' => 2,
        ]);

        XpEvent::query()->create([
            'user_id' => $user->id,
            'action' => 'feed_comment_created',
            'points' => 8,
            'description' => 'Kommentar',
        ]);

        $token = ApiAccessToken::createForUser($user, 'Dashboard test')['access_token'];
        $response = $this->withToken($token)
            ->getJson('/api/v1/gamification?locale=en')
            ->assertOk()
            ->assertJsonPath('data.profile.level', 2)
            ->assertJsonPath('data.profile.xp_total', 375)
            ->assertJsonPath('data.profile.xp_to_next_level', 125)
            ->assertJsonPath('data.stats.badges_total', 2)
            ->assertJsonPath('data.stats.badges_unlocked', 1)
            ->assertJsonPath('data.stats.quests_total', 1)
            ->assertJsonPath('data.quests.0.progress_count', 2)
            ->assertJsonPath('data.quests.0.target_count', 4)
            ->assertJsonPath('data.xp_events.0.points', 8);

        $badges = collect($response->json('data.badges'))->keyBy('slug');
        $this->assertTrue($badges['earned']['unlocked']);
        $this->assertFalse($badges['locked']['unlocked']);
        $this->assertSame('Earned', $badges['earned']['name']);
        $this->assertSame('Locked', $badges['locked']['name']);
        $this->assertDatabaseCount('badge_user', 1);
        $this->assertDatabaseCount('xp_events', 1);
        $this->assertDatabaseCount('quest_user', 1);
    }

    public function test_react_shell_routes_are_outside_legacy_login_middleware(): void
    {
        $routes = app('router')->getRoutes();

        foreach (['gamification.index', 'contracts.index'] as $name) {
            $route = $routes->getByName($name);
            $this->assertNotNull($route);
            $this->assertNotContains('auth', $route->gatherMiddleware());
        }
    }

    private function badge(string $slug, string $de, string $en): Badge
    {
        return Badge::query()->create([
            'slug' => $slug,
            'name' => $de,
            'name_de' => $de,
            'name_en' => $en,
            'category' => 'community',
            'rarity' => 'rare',
            'description' => 'Beschreibung',
            'xp_reward' => 40,
            'is_active' => true,
        ]);
    }

    private function quest(string $slug, bool $weekly): Quest
    {
        return Quest::query()->create([
            'slug' => $slug,
            'name' => 'Quest',
            'category' => 'community',
            'action' => 'feed_comment_created',
            'target_count' => 4,
            'xp_reward' => 20,
            'period' => 'once',
            'is_weekly_contract' => $weekly,
            'is_active' => true,
        ]);
    }
}
