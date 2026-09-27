<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewsPublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_list_uses_only_published_localized_translations_and_returns_facets(): void
    {
        $article = $this->article('published', [
            'de' => ['slug' => 'fruehlings-event', 'title' => 'Frühlings-Event', 'excerpt' => 'Deutsch.', 'content_json' => [['type' => 'paragraph', 'text' => 'Inhalt.']]],
            'en' => ['slug' => 'spring-event', 'title' => 'Spring event', 'excerpt' => 'English.', 'content_json' => [['type' => 'paragraph', 'text' => 'Story.']]],
        ], ['category_key' => 'events', 'tags' => ['spring', 'community'], 'featured' => true]);
        $this->article('draft', ['de' => ['slug' => 'draft-story', 'title' => 'Draft', 'content_json' => [['type' => 'paragraph', 'text' => 'Private.']]]]);
        $this->article('scheduled', ['de' => ['slug' => 'scheduled-story', 'title' => 'Scheduled', 'content_json' => [['type' => 'paragraph', 'text' => 'Later.']]]], ['scheduled_at' => now()->addDay()]);
        $this->article('published', ['de' => ['slug' => 'future-story', 'title' => 'Future', 'content_json' => [['type' => 'paragraph', 'text' => 'Not yet.']]]], ['published_at' => now()->addDay()]);

        $this->getJson('/api/v1/news?locale=de&category=events&tag=spring&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $article->id)
            ->assertJsonPath('data.0.title', 'Frühlings-Event')
            ->assertJsonPath('data.0.featured', true)
            ->assertJsonPath('facets.categories.0', 'events')
            ->assertJsonMissingPath('data.0.status')
            ->assertJsonMissingPath('data.0.lock_version')
            ->assertJsonMissingPath('data.0.seo_title');
    }

    public function test_localized_detail_never_falls_back_to_another_translation(): void
    {
        $article = $this->article('published', [
            'de' => ['slug' => 'abendjagd', 'title' => 'Abendjagd', 'content_json' => [['type' => 'paragraph', 'text' => 'Deutscher Text.']]],
            'en' => ['slug' => 'evening-hunt', 'title' => 'Evening hunt', 'content_json' => [['type' => 'paragraph', 'text' => 'English text.']]],
        ]);

        $this->getJson('/api/v1/news/de/abendjagd')
            ->assertOk()
            ->assertJsonPath('data.id', $article->id)
            ->assertJsonPath('data.title', 'Abendjagd')
            ->assertJsonPath('alternates.en', route('news.article', ['locale' => 'en', 'slug' => 'evening-hunt']));

        $this->getJson('/api/v1/news/es/abendjagd')->assertNotFound();
    }

    public function test_public_media_route_serves_only_assets_referenced_by_published_articles(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('news/public/hero.png', 'published image');
        $user = $this->user();
        $asset = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(), 'user_id' => $user->id, 'context' => 'news',
            'disk' => 'local', 'path' => 'news/public/hero.png', 'type' => 'image', 'mime_type' => 'image/png',
            'original_name' => 'hero.png', 'extension' => 'png', 'size_bytes' => 16,
            'visibility' => 'private', 'status' => 'ready',
        ]);
        $published = $this->article('published', ['en' => ['slug' => 'published-with-image', 'title' => 'Published', 'content_json' => [['type' => 'paragraph', 'text' => 'Public.']]]], ['hero_media_asset_id' => $asset->id]);
        $draft = $this->article('draft', ['en' => ['slug' => 'draft-with-image', 'title' => 'Draft', 'content_json' => [['type' => 'paragraph', 'text' => 'Private.']]]], ['hero_media_asset_id' => $asset->id]);

        $this->get(route('news.media', ['article' => $published->id, 'asset' => $asset->id, 'variant' => 'original']))->assertOk();
        $this->get(route('news.media', ['article' => $draft->id, 'asset' => $asset->id, 'variant' => 'original']))->assertNotFound();
    }

    private function article(string $status, array $translations, array $attributes = []): NewsArticle
    {
        $article = NewsArticle::query()->create(array_merge([
            'status' => $status,
            'category_key' => 'updates',
            'tags' => [],
            'featured' => false,
            'comments_enabled' => false,
            'published_at' => $status === 'published' ? now()->subMinute() : null,
            'lock_version' => 1,
        ], $attributes));

        foreach ($translations as $locale => $translation) {
            $article->translations()->create(array_merge([
                'locale' => $locale,
                'slug' => null,
                'title' => null,
                'excerpt' => null,
                'content_json' => [],
                'seo_title' => 'Private editor SEO title',
                'seo_description' => 'Private editor SEO description',
                'canonical_url' => null,
            ], $translation));
        }

        return $article;
    }

    private function user(): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create([
            'name' => 'News public test '.$suffix,
            'username' => 'news_public_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
    }
}
