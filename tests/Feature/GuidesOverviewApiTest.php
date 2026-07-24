<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidesOverviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guides_overview_requires_authentication(): void
    {
        $this->getJson('/api/v1/guides')->assertUnauthorized();
    }

    public function test_guides_overview_only_returns_published_guides(): void
    {
        $author = $this->user();
        $published = $this->guide($author, [
            'title' => 'Published Guide',
            'slug' => 'published-guide',
            'status' => Guide::STATUS_PUBLISHED,
            'published_at' => now()->subMinute(),
            'is_featured' => true,
        ]);
        $this->guide($author, [
            'title' => 'Draft Guide',
            'slug' => 'draft-guide',
            'status' => Guide::STATUS_DRAFT,
            'published_at' => null,
        ]);
        $this->guide($author, [
            'title' => 'Future Guide',
            'slug' => 'future-guide',
            'status' => Guide::STATUS_PUBLISHED,
            'published_at' => now()->addDay(),
        ]);

        $this->getAs($author, '/api/v1/guides')
            ->assertOk()
            ->assertJsonCount(1, 'data.guides')
            ->assertJsonPath('data.guides.0.id', $published->id)
            ->assertJsonPath('data.guides.0.slug', 'published-guide')
            ->assertJsonPath('data.guides.0.author.username', $author->username)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.overview_stats.guides_count', 1);
    }

    public function test_guides_overview_supports_search_and_filters(): void
    {
        $author = $this->user();
        $this->guide($author, [
            'title' => 'Solo Beginner Loadout',
            'slug' => 'solo-beginner-loadout',
            'category' => 'loadouts',
            'difficulty' => 'beginner',
            'platform' => 'all',
            'language' => 'de',
            'views_count' => 20,
            'helpful_count' => 7,
        ]);
        $this->guide($author, [
            'title' => 'Advanced Console Rotation',
            'slug' => 'advanced-console-rotation',
            'category' => 'strategy',
            'difficulty' => 'advanced',
            'platform' => 'xbox',
            'language' => 'en',
            'views_count' => 100,
            'helpful_count' => 15,
        ]);

        $this->getAs(
            $author,
            '/api/v1/guides?search=Solo&category=loadouts&difficulty=beginner&platform=pc&language=de&sort=helpful',
        )
            ->assertOk()
            ->assertJsonCount(1, 'data.guides')
            ->assertJsonPath('data.guides.0.slug', 'solo-beginner-loadout')
            ->assertJsonPath('data.category_counts.0.key', 'loadouts')
            ->assertJsonPath('data.pagination.total', 1);
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->getJson($uri);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Guides overview API test')['access_token'];
    }

    private function user(array $attributes = []): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create(array_merge([
            'name' => 'Guide Author '.$suffix,
            'username' => 'guide_author_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ], $attributes));
    }

    private function guide(User $author, array $attributes = []): Guide
    {
        $suffix = bin2hex(random_bytes(4));

        return Guide::query()->create(array_merge([
            'user_id' => $author->id,
            'title' => 'Guide '.$suffix,
            'slug' => 'guide-'.$suffix,
            'summary' => 'A concise guide summary.',
            'category' => 'general',
            'tags' => ['hunt', 'community'],
            'language' => 'de',
            'difficulty' => 'beginner',
            'platform' => 'all',
            'status' => Guide::STATUS_PUBLISHED,
            'is_featured' => false,
            'reading_time_minutes' => 5,
            'views_count' => 0,
            'helpful_count' => 0,
            'comments_count' => 0,
            'published_at' => now()->subMinute(),
        ], $attributes));
    }
}
