<?php

namespace Tests\Feature\Guides;

use App\Models\ApiAccessToken;
use App\Models\Guide;
use App\Models\GuideCategory;
use App\Models\GuideRevision;
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

    public function test_guides_overview_uses_existing_published_revisions(): void
    {
        $viewer = $this->user('viewer');
        $author = $this->user('author');
        $category = GuideCategory::query()->create([
            'slug' => 'strategy',
            'name_de' => 'Strategie',
            'name_en' => 'Strategy',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $published = $this->publishedGuide($author, $category, [
            'slug' => 'boss-strategy',
            'title' => 'Boss Strategie',
            'summary' => 'Eine echte veröffentlichte Revision.',
            'language' => 'de',
            'difficulty' => 'advanced',
            'platform' => 'pc',
        ]);

        Guide::query()->create([
            'author_id' => $author->id,
            'slug' => 'draft-without-published-revision',
            'status' => 'draft',
        ]);

        $this->getAs($viewer, '/api/v1/guides?category=strategy&language=de&difficulty=advanced&platform=pc')
            ->assertOk()
            ->assertJsonCount(1, 'data.guides')
            ->assertJsonPath('data.guides.0.id', $published->id)
            ->assertJsonPath('data.guides.0.slug', 'boss-strategy')
            ->assertJsonPath('data.guides.0.title', 'Boss Strategie')
            ->assertJsonPath('data.guides.0.category.slug', 'strategy')
            ->assertJsonPath('data.guides.0.category.label', 'Strategie')
            ->assertJsonPath('data.guides.0.language', 'de')
            ->assertJsonPath('data.guides.0.difficulty', 'advanced')
            ->assertJsonPath('data.guides.0.platform', 'pc')
            ->assertJsonPath('data.guides.0.author.username', $author->username)
            ->assertJsonPath('data.overview_stats.guides_count', 1)
            ->assertJsonPath('data.overview_stats.authors_count', 1)
            ->assertJsonPath('data.category_counts.0.slug', 'strategy')
            ->assertJsonPath('data.category_counts.0.count', 1);
    }

    public function test_search_and_platform_all_follow_the_web_guide_rules(): void
    {
        $viewer = $this->user('viewer_search');
        $author = $this->user('author_search');
        $category = GuideCategory::query()->create([
            'slug' => 'loadouts',
            'name_de' => 'Loadouts',
            'name_en' => 'Loadouts',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->publishedGuide($author, $category, [
            'slug' => 'universal-loadout',
            'title' => 'Universal Loadout',
            'summary' => 'Funktioniert auf jeder Plattform.',
            'platform' => 'all',
        ]);

        $this->getAs($viewer, '/api/v1/guides?search=Universal&platform=xbox')
            ->assertOk()
            ->assertJsonCount(1, 'data.guides')
            ->assertJsonPath('data.guides.0.slug', 'universal-loadout');
    }

    private function publishedGuide(User $author, GuideCategory $category, array $attributes): Guide
    {
        $guide = Guide::query()->create([
            'author_id' => $author->id,
            'slug' => $attributes['slug'],
            'status' => 'published',
            'is_featured' => (bool) ($attributes['is_featured'] ?? false),
            'show_in_profile' => true,
            'helpful_count' => (int) ($attributes['helpful_count'] ?? 0),
            'bookmarks_count' => (int) ($attributes['bookmarks_count'] ?? 0),
            'comments_count' => (int) ($attributes['comments_count'] ?? 0),
            'published_at' => now()->subMinute(),
        ]);

        $revision = GuideRevision::query()->create([
            'guide_id' => $guide->id,
            'version' => 1,
            'author_id' => $author->id,
            'category_id' => $category->id,
            'title' => $attributes['title'],
            'summary' => $attributes['summary'] ?? '',
            'tags' => $attributes['tags'] ?? [],
            'language' => $attributes['language'] ?? 'de',
            'difficulty' => $attributes['difficulty'] ?? 'beginner',
            'platform' => $attributes['platform'] ?? 'all',
            'content_blocks' => [],
            'reading_time_minutes' => 4,
            'status' => 'published',
            'reviewed_at' => now()->subMinute(),
        ]);

        $guide->forceFill([
            'current_published_revision_id' => $revision->id,
            'working_revision_id' => $revision->id,
        ])->save();

        return $guide->fresh();
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->getJson($uri);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Guides overview API test')['access_token'];
    }

    private function user(string $prefix): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create([
            'name' => ucfirst($prefix).' '.$suffix,
            'username' => $prefix.'_'.$suffix,
            'email' => $prefix.'_'.$suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
    }
}
