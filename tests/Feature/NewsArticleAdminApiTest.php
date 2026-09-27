<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\MediaAsset;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewsArticleAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_admin_api_requires_a_valid_admin_token(): void
    {
        $this->getJson('/api/v1/admin/news/articles')->assertUnauthorized();

        $user = $this->user();
        $this->withToken($this->token($user))
            ->getJson('/api/v1/admin/news/articles')
            ->assertForbidden();

        $this->withToken($this->token($this->user(['is_admin' => true])))
            ->getJson('/api/v1/admin/news/articles')
            ->assertOk();
    }

    public function test_admin_can_create_autosave_and_restore_a_news_article_revision(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $token = $this->token($admin);

        $created = $this->withToken($token)->postJson('/api/v1/admin/news/articles', [
            'category_key' => 'updates',
            'tags' => ['update-2-0', 'winter'],
            'featured' => true,
            'comments_enabled' => true,
            'translations' => [
                'de' => [
                    'title' => 'Neue Jagd',
                    'slug' => 'neue-jagd',
                    'excerpt' => 'Ein kurzer Teaser.',
                    'content_json' => [['type' => 'paragraph', 'text' => 'Der erste Absatz.']],
                    'seo_title' => 'Neue Jagd | HNT.ROCKS',
                    'seo_description' => 'SEO Beschreibung.',
                ],
            ],
        ])->assertCreated()->assertJsonPath('data.status', 'draft')->assertJsonPath('data.lock_version', 1);

        $articleId = (int) $created->json('data.id');
        $this->assertDatabaseCount('news_article_revisions', 1);

        $updated = $this->withToken($token)->patchJson('/api/v1/admin/news/articles/'.$articleId, [
            'lock_version' => 1,
            'translations' => ['de' => ['title' => 'Neue Jagd: Update']],
            'tags' => ['update-2-0', 'winter', 'karten'],
        ])->assertOk()->assertJsonPath('data.lock_version', 2)
            ->assertJsonPath('data.translations.de.title', 'Neue Jagd: Update');

        $revision = $this->withToken($token)->getJson('/api/v1/admin/news/articles/'.$articleId.'/revisions')
            ->assertOk()
            ->assertJsonPath('data.0.revision_number', 2)
            ->json('data.1');

        $this->withToken($token)->postJson('/api/v1/admin/news/articles/'.$articleId.'/revisions/'.$revision['id'].'/restore', [
            'lock_version' => $updated->json('data.lock_version'),
        ])->assertOk()->assertJsonPath('data.lock_version', 3)
            ->assertJsonPath('data.translations.de.title', 'Neue Jagd');

        $this->assertDatabaseCount('news_article_revisions', 4);
    }

    public function test_stale_autosaves_are_rejected_with_a_conflict(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $token = $this->token($admin);
        $article = $this->createDraft($admin, $token);

        $this->withToken($token)->patchJson('/api/v1/admin/news/articles/'.$article->id, [
            'lock_version' => 1,
            'featured' => true,
        ])->assertOk();

        $this->withToken($token)->patchJson('/api/v1/admin/news/articles/'.$article->id, [
            'lock_version' => 1,
            'featured' => false,
        ])->assertConflict();
    }

    public function test_publishing_requires_all_four_complete_locales_and_scheduling_publishes_when_due(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $token = $this->token($admin);
        $article = $this->createDraft($admin, $token);

        $this->withToken($token)->postJson('/api/v1/admin/news/articles/'.$article->id.'/workflow', [
            'action' => 'publish',
            'lock_version' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('translations');

        $translationData = [];
        foreach (NewsArticle::LOCALES as $locale) {
            $translationData[$locale] = [
                'title' => 'Story '.$locale,
                'slug' => 'story-'.$locale,
                'content_json' => [['type' => 'paragraph', 'text' => 'A translated story.']],
            ];
        }

        $updated = $this->withToken($token)->patchJson('/api/v1/admin/news/articles/'.$article->id, [
            'lock_version' => 1,
            'translations' => $translationData,
        ])->assertOk();

        $scheduledFor = now()->addMinute();
        $scheduled = $this->withToken($token)->postJson('/api/v1/admin/news/articles/'.$article->id.'/workflow', [
            'action' => 'schedule',
            'lock_version' => $updated->json('data.lock_version'),
            'scheduled_at' => $scheduledFor->toIso8601String(),
        ])->assertOk()->assertJsonPath('data.status', 'scheduled');

        $this->travelTo($scheduledFor->copy()->addSecond());
        $this->artisan('hnt:news:publish-scheduled')->assertSuccessful();
        $this->assertDatabaseHas('news_articles', [
            'id' => $article->id,
            'status' => NewsArticle::STATUS_PUBLISHED,
        ]);

        $archived = $this->withToken($token)->postJson('/api/v1/admin/news/articles/'.$article->id.'/workflow', [
            'action' => 'archive',
            'lock_version' => 4,
        ])->assertOk()->assertJsonPath('data.status', 'archived');

        $this->withToken($token)->postJson('/api/v1/admin/news/articles/'.$article->id.'/workflow', [
            'action' => 'draft',
            'lock_version' => $archived->json('data.lock_version'),
        ])->assertOk()->assertJsonPath('data.status', 'draft');

        $this->travelBack();
    }

    public function test_signed_preview_exposes_draft_locale_temporarily_and_rejects_tampering(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $token = $this->token($admin);
        $article = $this->createDraft($admin, $token);

        $issued = $this->withToken($token)->postJson('/api/v1/admin/news/articles/'.$article->id.'/preview', [
            'locale' => 'de',
        ])->assertOk();

        $parts = parse_url($issued->json('preview_url'));
        $uri = $parts['path'].'?'.$parts['query'];
        $this->getJson($uri)->assertOk()->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.translation.title', 'Entwurf');

        $tampered = str_replace('/de?', '/en?', $uri);
        $this->getJson($tampered)->assertForbidden();
    }

    public function test_before_after_blocks_require_two_uploaded_image_assets(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $token = $this->token($admin);
        $article = $this->createDraft($admin, $token);
        $before = $this->imageAsset($admin, 'before.png');
        $after = $this->imageAsset($admin, 'after.png');

        $invalid = [[
            'type' => 'before_after',
            'before_media_id' => $before->id,
            'before_alt' => 'Before',
            'before_label' => 'Before',
            'after_label' => 'After',
        ]];
        $this->withToken($token)->patchJson('/api/v1/admin/news/articles/'.$article->id, [
            'lock_version' => 1,
            'translations' => ['de' => ['content_json' => $invalid]],
        ])->assertUnprocessable()->assertJsonValidationErrors('translations.de.content_json');

        $valid = [[
            'type' => 'before_after',
            'before_media_id' => $before->id,
            'after_media_id' => $after->id,
            'before_alt' => 'Earlier look',
            'after_alt' => 'Current look',
            'before_label' => 'Before',
            'after_label' => 'After',
        ]];
        $this->withToken($token)->patchJson('/api/v1/admin/news/articles/'.$article->id, [
            'lock_version' => 1,
            'translations' => ['de' => ['content_json' => $valid]],
        ])->assertOk();
    }

    public function test_media_upload_accepts_an_image_and_rejects_an_invalid_video_mime(): void
    {
        Storage::fake('local');
        $admin = $this->user(['is_admin' => true]);
        $token = $this->token($admin);

        $uploaded = $this->withToken($token)->postJson('/api/v1/admin/news/media', [
            'kind' => 'image',
            'alt_text' => 'A map preview',
            'file' => UploadedFile::fake()->image('preview.png', 1280, 720),
        ])->assertCreated()->assertJsonPath('data.type', 'image')->assertJsonPath('data.alt_text', 'A map preview');

        $this->assertDatabaseHas('media_assets', [
            'id' => $uploaded->json('data.id'),
            'context' => 'news',
            'type' => 'image',
            'disk' => 'local',
        ]);

        $url = parse_url($uploaded->json('data.url'));
        $this->get($url['path'])->assertForbidden();
        $this->get($url['path'].'?'.$url['query'])->assertOk();

        $this->withToken($token)->postJson('/api/v1/admin/news/media', [
            'kind' => 'video',
            'file' => UploadedFile::fake()->create('clip.txt', 16, 'text/plain'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    private function createDraft(User $admin, string $token): NewsArticle
    {
        $response = $this->withToken($token)->postJson('/api/v1/admin/news/articles', [
            'translations' => [
                'de' => [
                    'title' => 'Entwurf',
                    'slug' => 'entwurf',
                    'content_json' => [['type' => 'paragraph', 'text' => 'Inhalt.']],
                ],
            ],
        ])->assertCreated();

        return NewsArticle::query()->findOrFail($response->json('data.id'));
    }

    private function imageAsset(User $user, string $name): MediaAsset
    {
        return MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'context' => 'news',
            'disk' => 'public',
            'path' => 'news/test/'.$name,
            'type' => 'image',
            'mime_type' => 'image/png',
            'original_name' => $name,
            'extension' => 'png',
            'size_bytes' => 1024,
            'visibility' => 'private',
            'status' => 'ready',
        ]);
    }

    private function user(array $attributes = []): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create(array_merge([
            'name' => 'News editor '.$suffix,
            'username' => 'news_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ], $attributes));
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'News editor API test')['access_token'];
    }
}
