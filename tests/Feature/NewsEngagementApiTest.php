<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsEngagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_reads_comments_but_cannot_write_and_authenticated_user_can_comment_reply_like_and_save(): void
    {
        $article = $this->article(true);
        $path = '/api/v1/news/articles/'.$article->id;
        $this->getJson($path.'/engagement')->assertOk()->assertJsonPath('data.comments_count', 0);
        $this->postJson($path.'/comments', ['body' => 'Guest'])->assertUnauthorized();

        $token = ApiAccessToken::createForUser($this->user())['access_token'];
        $this->withToken($token)->getJson($path.'/engagement/viewer')
            ->assertOk()->assertJsonPath('data.liked', false)->assertJsonPath('data.saved', false);
        $first = $this->withToken($token)->postJson($path.'/comments', ['body' => 'First comment'])
            ->assertCreated()->assertJsonPath('data.comments_count', 1);
        $commentId = $first->json('data.comment.id');
        $this->withToken($token)->postJson($path.'/comments', ['body' => 'A reply', 'parent_id' => $commentId])
            ->assertCreated()->assertJsonPath('data.comment.parent_id', $commentId);
        $this->withToken($token)->postJson($path.'/comments/'.$commentId.'/like')
            ->assertOk()->assertJsonPath('data.likes_count', 1);
        $this->withToken($token)->postJson($path.'/like')->assertOk()
            ->assertJsonPath('data.liked', true)->assertJsonPath('data.likes_count', 1);
        $this->withToken($token)->postJson($path.'/save')->assertOk()->assertJsonPath('data.saved', true);
        $this->getJson($path.'/engagement')->assertOk()->assertJsonPath('data.comments_count', 2)
            ->assertJsonPath('data.comments.0.body', 'A reply')
            ->assertJsonPath('data.comments.1.likes_count', 1);
    }

    public function test_disabled_comments_block_writes_but_existing_comments_remain_readable(): void
    {
        $article = $this->article(false);
        $article->comments()->create(['user_id' => $this->user()->id, 'body' => 'Before closing']);
        $path = '/api/v1/news/articles/'.$article->id;
        $this->getJson($path.'/engagement')->assertOk()
            ->assertJsonPath('data.comments_enabled', false)->assertJsonPath('data.comments_count', 1);
        $this->withToken(ApiAccessToken::createForUser($this->user())['access_token'])
            ->postJson($path.'/comments', ['body' => 'Too late'])->assertForbidden();
    }

    public function test_unpublished_articles_do_not_expose_engagement(): void
    {
        $article = $this->article(true, 'draft');
        $this->getJson('/api/v1/news/articles/'.$article->id.'/engagement')->assertNotFound();
    }

    private function article(bool $commentsEnabled, string $status = 'published'): NewsArticle
    {
        $article = NewsArticle::query()->create([
            'status' => $status, 'tags' => [], 'featured' => false,
            'comments_enabled' => $commentsEnabled,
            'published_at' => $status === 'published' ? now()->subMinute() : null,
            'lock_version' => 1,
        ]);
        $article->translations()->create([
            'locale' => 'de', 'slug' => 'news-'.$article->id, 'title' => 'News',
            'content_json' => [['type' => 'paragraph', 'text' => 'Text.']],
        ]);
        return $article;
    }

    private function user(): User
    {
        $suffix = bin2hex(random_bytes(5));
        return User::query()->create([
            'name' => 'News reader '.$suffix, 'username' => 'news_reader_'.$suffix,
            'email' => $suffix.'@example.test', 'password' => 'password', 'status' => 'active',
        ]);
    }
}
