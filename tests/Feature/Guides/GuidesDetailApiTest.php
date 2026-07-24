<?php

namespace Tests\Feature\Guides;

use App\Models\ApiAccessToken;
use App\Models\Guide;
use App\Models\GuideCategory;
use App\Models\GuideComment;
use App\Models\GuideMedia;
use App\Models\GuideRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuidesDetailApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_requires_authentication(): void
    {
        $this->getJson('/api/v1/guides/example')->assertUnauthorized();
    }

    public function test_detail_uses_the_published_revision_and_viewer_state(): void
    {
        $viewer = $this->user('viewer');
        $author = $this->user('author');
        [$guide, $revision, $cover, $inline] = $this->publishedGuide($author);

        $guide->bookmarks()->create(['user_id' => $viewer->id]);
        $guide->helpfulVotes()->create(['user_id' => $viewer->id]);
        $guide->updateQuietly(['bookmarks_count' => 1, 'helpful_count' => 1]);

        $this->getAs($viewer, '/api/v1/guides/'.$guide->slug)
            ->assertOk()
            ->assertJsonPath('data.guide.slug', $guide->slug)
            ->assertJsonPath('data.guide.title', $revision->title)
            ->assertJsonPath('data.guide.cover_url', 'guides/media/'.$cover->id)
            ->assertJsonPath('data.guide.content_blocks.1.type', 'image')
            ->assertJsonPath('data.guide.content_blocks.1.media_url', 'guides/media/'.$inline->id)
            ->assertJsonPath('data.viewer.helpful', true)
            ->assertJsonPath('data.viewer.bookmarked', true)
            ->assertJsonPath('data.viewer.owns_guide', false)
            ->assertJsonPath('data.counts.helpful', 1)
            ->assertJsonPath('data.counts.bookmarks', 1);
    }

    public function test_comments_are_paginated_with_one_reply_level(): void
    {
        $viewer = $this->user('viewer_comments');
        $author = $this->user('author_comments');
        [$guide] = $this->publishedGuide($author);

        $root = GuideComment::query()->create([
            'guide_id' => $guide->id,
            'user_id' => $viewer->id,
            'body' => 'Root comment',
        ]);
        GuideComment::query()->create([
            'guide_id' => $guide->id,
            'user_id' => $author->id,
            'parent_id' => $root->id,
            'body' => 'Author reply',
        ]);

        $this->getAs($viewer, '/api/v1/guides/'.$guide->slug.'/comments')
            ->assertOk()
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonPath('data.comments.0.body', 'Root comment')
            ->assertJsonPath('data.comments.0.replies.0.body', 'Author reply')
            ->assertJsonPath('data.comments.0.replies.0.is_guide_author', true)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_media_endpoint_only_streams_published_cover_and_content_images(): void
    {
        Storage::fake('local');

        $viewer = $this->user('viewer_media');
        $author = $this->user('author_media');
        [$guide, , $cover, $inline] = $this->publishedGuide($author);

        Storage::disk('local')->put($cover->path, 'cover-bytes');
        Storage::disk('local')->put($inline->path, 'inline-bytes');

        $orphan = GuideMedia::query()->create([
            'guide_id' => $guide->id,
            'revision_id' => $guide->current_published_revision_id,
            'uploaded_by' => $author->id,
            'kind' => 'image',
            'disk' => 'local',
            'path' => 'guides/orphan.jpg',
            'original_name' => 'orphan.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 12,
        ]);
        Storage::disk('local')->put($orphan->path, 'orphan-bytes');

        $this->getAs($viewer, '/api/v1/guides/media/'.$cover->id)->assertOk();
        $this->getAs($viewer, '/api/v1/guides/media/'.$inline->id)->assertOk();
        $this->getAs($viewer, '/api/v1/guides/media/'.$orphan->id)->assertNotFound();
    }

    private function publishedGuide(User $author): array
    {
        $category = GuideCategory::query()->create([
            'slug' => 'strategy',
            'name_de' => 'Strategie',
            'name_en' => 'Strategy',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $guide = Guide::query()->create([
            'author_id' => $author->id,
            'slug' => 'detail-'.bin2hex(random_bytes(4)),
            'status' => 'published',
            'show_in_profile' => true,
            'published_at' => now()->subMinute(),
        ]);

        $revision = GuideRevision::query()->create([
            'guide_id' => $guide->id,
            'version' => 2,
            'author_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Published detail guide',
            'summary' => 'Real revision content.',
            'tags' => ['boss', 'strategy'],
            'language' => 'de',
            'difficulty' => 'advanced',
            'platform' => 'pc',
            'content_blocks' => [],
            'reading_time_minutes' => 5,
            'status' => 'published',
            'reviewed_at' => now()->subMinute(),
        ]);

        $cover = GuideMedia::query()->create([
            'guide_id' => $guide->id,
            'revision_id' => $revision->id,
            'uploaded_by' => $author->id,
            'kind' => 'cover',
            'disk' => 'local',
            'path' => 'guides/cover.jpg',
            'original_name' => 'cover.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 11,
        ]);
        $inline = GuideMedia::query()->create([
            'guide_id' => $guide->id,
            'revision_id' => $revision->id,
            'uploaded_by' => $author->id,
            'kind' => 'image',
            'disk' => 'local',
            'path' => 'guides/inline.jpg',
            'original_name' => 'inline.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 12,
        ]);

        $revision->update([
            'cover_media_id' => $cover->id,
            'content_blocks' => [
                ['id' => 'intro', 'type' => 'paragraph', 'text' => 'Intro text'],
                ['id' => 'map', 'type' => 'image', 'media_id' => $inline->id, 'caption' => 'Map'],
            ],
        ]);
        $guide->forceFill([
            'current_published_revision_id' => $revision->id,
            'working_revision_id' => $revision->id,
        ])->save();

        return [$guide->fresh(), $revision->fresh(), $cover, $inline];
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->get($uri, ['Accept' => 'application/json']);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Guides detail API test')['access_token'];
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
