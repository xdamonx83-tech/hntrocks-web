<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Badge;
use App\Models\Quest;
use App\Models\User;
use App\Models\XpEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GamificationDashboardApiTest extends TestCase
{
    /**
     * Isolated schema: the legacy full migration chain contains a MySQL-only
     * foreign-key rewrite, unrelated to this API and unsupported by SQLite.
     * Never access production DB or modify existing migrations from this test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->unsignedInteger('xp_total')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('trust_score')->default(0);
            $table->timestamps();
        });

        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->text('bio')->nullable();
            $table->string('platform')->nullable();
            $table->string('playstyle')->nullable();
            $table->timestamps();
        });

        Schema::create('api_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('name');
            $table->string('token_hash');
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('badges', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_de')->nullable();
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_de')->nullable();
            $table->text('description_en')->nullable();
            $table->string('category');
            $table->string('rarity')->default('common');
            $table->string('icon_path')->nullable();
            $table->unsignedInteger('xp_reward')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('badge_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('badge_id');
            $table->timestamp('awarded_at')->nullable();
            $table->unsignedBigInteger('awarded_by')->nullable();
            $table->text('award_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('quests', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_de')->nullable();
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_de')->nullable();
            $table->text('description_en')->nullable();
            $table->string('category');
            $table->string('action');
            $table->string('period')->nullable();
            $table->unsignedInteger('target_count')->default(1);
            $table->unsignedInteger('xp_reward')->default(0);
            $table->boolean('is_weekly_contract')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('badge_slug')->nullable();
            $table->string('icon_path')->nullable();
            $table->timestamps();
        });

        Schema::create('quest_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('quest_id');
            $table->unsignedInteger('progress_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reward_claimed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('xp_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('action');
            $table->integer('points');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

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
