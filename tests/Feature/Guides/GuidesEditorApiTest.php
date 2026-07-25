<?php

namespace Tests\Feature\Guides;

use App\Models\ApiAccessToken;
use App\Models\Guide;
use App\Models\GuideCategory;
use App\Models\GuideMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidesEditorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_options_and_draft_creation_are_available_to_active_users(): void
    {
        $user = $this->user('editor');
        $category = $this->category();

        $this->getAs($user, '/api/v1/guides/editor/options')
            ->assertOk()
            ->assertJsonPath('data.categories.0.id', $category->id)
            ->assertJsonPath('data.minimum_submit_blocks', null)
            ->assertJsonPath('data.limits.minimum_submit_blocks', 3)
            ->assertJsonPath('data.block_types.0', 'heading');

        $response = $this->postAs($user, '/api/v1/guides/drafts');

        $response
            ->assertCreated()
            ->assertJsonPath('data.guide.status', 'draft')
            ->assertJsonPath('data.guide.editable', true)
            ->assertJsonPath('data.revision.version', 1)
            ->assertJsonPath('data.revision.language', 'de');

        $this->assertDatabaseHas('guides', [
            'id' => $response->json('data.guide.id'),
            'author_id' => $user->id,
            'status' => 'draft',
        ]);
    }

    public function test_complete_draft_can_be_saved_and_submitted(): void
    {
        $user = $this->user('submitter');
        $category = $this->category();
        $created = $this->postAs($user, '/api/v1/guides/drafts')->assertCreated();
        $guide = Guide::query()->findOrFail($created->json('data.guide.id'));
        $revision = $guide->workingRevision()->firstOrFail();
        $cover = $this->cover($guide, $revision->id, $user);

        $payload = $this->validPayload($category->id, $cover->id);

        $this->patchAs($user, '/api/v1/guides/drafts/'.$guide->slug, $payload)
            ->assertOk()
            ->assertJsonPath('data.revision.title', 'Complete guide title')
            ->assertJsonCount(3, 'data.revision.content_blocks');

        $submitted = $this->postAs($user, '/api/v1/guides/drafts/'.$guide->slug.'/submit');

        $submitted
            ->assertOk()
            ->assertJsonPath('data.guide.status', 'pending_review')
            ->assertJsonPath('data.revision.status', 'pending_review');

        $guide->refresh();
        $this->assertSame('pending_review', $guide->status);
        $this->assertFalse(str_starts_with($guide->slug, 'guide-'));
    }

    public function test_submission_requires_at_least_three_complete_blocks(): void
    {
        $user = $this->user('minimum');
        $category = $this->category();
        $created = $this->postAs($user, '/api/v1/guides/drafts')->assertCreated();
        $guide = Guide::query()->findOrFail($created->json('data.guide.id'));
        $revision = $guide->workingRevision()->firstOrFail();
        $cover = $this->cover($guide, $revision->id, $user);
        $payload = $this->validPayload($category->id, $cover->id);
        $payload['content_blocks'] = array_slice($payload['content_blocks'], 0, 2);

        $this->patchAs($user, '/api/v1/guides/drafts/'.$guide->slug, $payload)->assertOk();

        $this->postAs($user, '/api/v1/guides/drafts/'.$guide->slug.'/submit')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content_blocks');
    }

    private function validPayload(int $categoryId, int $coverId): array
    {
        return [
            'title' => 'Complete guide title',
            'summary' => 'A complete summary that is comfortably longer than twenty characters.',
            'category_id' => $categoryId,
            'cover_media_id' => $coverId,
            'tags' => ['boss', 'strategy'],
            'language' => 'de',
            'difficulty' => 'advanced',
            'platform' => 'all',
            'show_in_profile' => true,
            'content_blocks' => [
                ['id' => 'heading', 'type' => 'heading', 'level' => 2, 'text' => 'Vorbereitung'],
                ['id' => 'intro', 'type' => 'paragraph', 'text' => 'Sammle zuerst alle benötigten Werkzeuge.'],
                ['id' => 'steps', 'type' => 'steps', 'items' => ['Schritt eins', 'Schritt zwei']],
            ],
        ];
    }

    private function category(): GuideCategory
    {
        return GuideCategory::query()->create([
            'slug' => 'strategy',
            'name_de' => 'Strategie',
            'name_en' => 'Strategy',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function cover(Guide $guide, int $revisionId, User $user): GuideMedia
    {
        return GuideMedia::query()->create([
            'guide_id' => $guide->id,
            'revision_id' => $revisionId,
            'uploaded_by' => $user->id,
            'kind' => 'cover',
            'disk' => 'local',
            'path' => 'guides/test-cover.jpg',
            'original_name' => 'test-cover.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'width' => 1600,
            'height' => 900,
        ]);
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->get($uri, ['Accept' => 'application/json']);
    }

    private function postAs(User $user, string $uri, array $data = [])
    {
        return $this->withToken($this->token($user))->postJson($uri, $data);
    }

    private function patchAs(User $user, string $uri, array $data)
    {
        return $this->withToken($this->token($user))->patchJson($uri, $data);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Guides editor API test')['access_token'];
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
