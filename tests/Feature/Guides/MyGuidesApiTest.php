<?php

namespace Tests\Feature\Guides;

use App\Models\ApiAccessToken;
use App\Models\Guide;
use App\Models\GuideCategory;
use App\Models\GuideMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyGuidesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_withdraw_and_delete_own_draft(): void
    {
        $user = $this->user('owner');
        $category = $this->category();
        $created = $this->postAs($user, '/api/v1/guides/drafts')->assertCreated();
        $guide = Guide::query()->findOrFail($created->json('data.guide.id'));
        $revision = $guide->workingRevision()->firstOrFail();
        $cover = $this->cover($guide, $revision->id, $user);

        $this->patchAs($user, '/api/v1/guides/drafts/'.$guide->slug, [
            'title' => 'My complete guide title',
            'summary' => 'This summary contains more than twenty characters for submission.',
            'category_id' => $category->id,
            'cover_media_id' => $cover->id,
            'tags' => ['solo', 'boss'],
            'language' => 'de',
            'difficulty' => 'beginner',
            'platform' => 'all',
            'show_in_profile' => true,
            'content_blocks' => [
                ['id' => 'one', 'type' => 'heading', 'level' => 2, 'text' => 'Start'],
                ['id' => 'two', 'type' => 'paragraph', 'text' => 'Prepare every required item.'],
                ['id' => 'three', 'type' => 'steps', 'items' => ['First', 'Second']],
            ],
        ])->assertOk();

        $list = $this->getAs($user, '/api/v1/guides/mine');
        $list->assertOk()
            ->assertJsonPath('data.guides.0.id', $guide->id)
            ->assertJsonPath('data.guides.0.actions.edit', true)
            ->assertJsonPath('data.guides.0.actions.delete', true)
            ->assertJsonPath('data.status_counts.draft', 1)
            ->assertJsonPath('data.stats.total', 1);

        $this->postAs($user, '/api/v1/guides/drafts/'.$guide->slug.'/submit')
            ->assertOk();
        $guide->refresh();

        $pending = $this->getAs($user, '/api/v1/guides/mine?status=pending_review');
        $pending->assertOk()
            ->assertJsonPath('data.guides.0.status', 'pending_review')
            ->assertJsonPath('data.guides.0.actions.withdraw', true)
            ->assertJsonPath('data.guides.0.actions.edit', false);

        $this->postAs($user, '/api/v1/guides/drafts/'.$guide->slug.'/withdraw')
            ->assertOk()
            ->assertJsonPath('data.guide.status', 'draft');

        $this->deleteAs($user, '/api/v1/guides/drafts/'.$guide->slug)
            ->assertOk();

        $this->assertDatabaseMissing('guides', ['id' => $guide->id]);
    }

    public function test_user_cannot_delete_another_users_guide(): void
    {
        $owner = $this->user('owner');
        $other = $this->user('other');
        $created = $this->postAs($owner, '/api/v1/guides/drafts')->assertCreated();
        $guide = Guide::query()->findOrFail($created->json('data.guide.id'));

        $this->deleteAs($other, '/api/v1/guides/drafts/'.$guide->slug)
            ->assertForbidden();

        $this->assertDatabaseHas('guides', ['id' => $guide->id]);
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

    private function deleteAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->deleteJson($uri);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'My guides API test')['access_token'];
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
