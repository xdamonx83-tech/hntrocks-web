<?php

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\NewsArticle;
use App\Models\NewsArticleComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NewsEngagementController extends Controller
{
    public function index(Request $request, NewsArticle $article): JsonResponse
    {
        $this->assertPublished($article);
        $comments = $article->comments()->with('user')->withCount('likes')
            ->latest()->orderByDesc('id')->limit(100)->get();

        return response()->json(['data' => [
            'comments_enabled' => (bool) $article->comments_enabled,
            'comments_count' => $article->comments()->count(),
            'likes_count' => $article->interactions()->where('liked', true)->count(),
            'comments' => $comments->map(fn (NewsArticleComment $comment) => $this->commentPayload($request, $comment))->values(),
        ]]);
    }

    public function viewer(Request $request, NewsArticle $article): JsonResponse
    {
        $this->assertPublished($article);
        $interaction = $article->interactions()->where('user_id', $request->user()->id)->first();
        return response()->json(['data' => [
            'liked' => (bool) $interaction?->liked,
            'saved' => (bool) $interaction?->saved,
            'liked_comment_ids' => NewsArticleComment::query()->where('news_article_id', $article->id)
                ->whereHas('likes', fn ($query) => $query->where('user_id', $request->user()->id))
                ->pluck('id')->all(),
        ]]);
    }

    public function store(Request $request, NewsArticle $article): JsonResponse
    {
        $this->assertPublished($article);
        abort_unless($article->comments_enabled, 403);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer'],
        ]);
        $body = trim($data['body']);
        if ($body === '') throw ValidationException::withMessages(['body' => 'A comment is required.']);

        $parent = null;
        if (! empty($data['parent_id'])) {
            $parent = $article->comments()->whereNull('parent_id')->findOrFail($data['parent_id']);
        }
        $comment = $article->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $parent?->id,
            'body' => $body,
        ]);
        $comment->load('user')->loadCount('likes');

        return response()->json(['data' => [
            'comment' => $this->commentPayload($comment),
            'comments_count' => $article->comments()->count(),
        ]], 201);
    }

    public function toggleLike(Request $request, NewsArticle $article): JsonResponse
    {
        return $this->toggleInteraction($request, $article, 'liked');
    }

    public function toggleSave(Request $request, NewsArticle $article): JsonResponse
    {
        return $this->toggleInteraction($request, $article, 'saved');
    }

    public function toggleCommentLike(Request $request, NewsArticle $article, NewsArticleComment $comment): JsonResponse
    {
        $this->assertPublished($article);
        abort_unless((int) $comment->news_article_id === (int) $article->id && ! $comment->trashed(), 404);
        $liked = DB::transaction(function () use ($request, $comment): bool {
            $existing = $comment->likes()->where('user_id', $request->user()->id)->first();
            if ($existing) {
                $existing->delete();
                return false;
            }
            $comment->likes()->create(['user_id' => $request->user()->id]);
            return true;
        });
        return response()->json(['data' => ['liked' => $liked, 'likes_count' => $comment->likes()->count()]]);
    }

    private function toggleInteraction(Request $request, NewsArticle $article, string $field): JsonResponse
    {
        $this->assertPublished($article);
        $active = DB::transaction(function () use ($request, $article, $field): bool {
            $interaction = $article->interactions()->firstOrCreate(['user_id' => $request->user()->id]);
            $interaction->update([$field => ! $interaction->{$field}]);
            return (bool) $interaction->{$field};
        });
        return response()->json(['data' => [
            $field => $active,
            'likes_count' => $article->interactions()->where('liked', true)->count(),
        ]]);
    }

    private function assertPublished(NewsArticle $article): void
    {
        abort_unless($article->status === NewsArticle::STATUS_PUBLISHED && $article->published_at?->lte(now())
            && $article->translations()->publishable()->exists(), 404);
    }

    private function commentPayload(Request $request, NewsArticleComment $comment): array
    {
        $user = $comment->user;
        $userPayload = $user ? (new UserResource($user))->resolve($request) : null;

        return [
            'id' => (int) $comment->id,
            'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
            'body' => $comment->body,
            'created_at' => $comment->created_at?->toIso8601String(),
            'likes_count' => (int) ($comment->likes_count ?? 0),
            'user' => [
                'id' => (int) $user?->id,
                'name' => $user?->name ?? 'User',
                'avatar_url' => $userPayload['avatar_url'] ?? ($user?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg')),
                'crown_cosmetics' => $userPayload['crown_cosmetics'] ?? null,
            ],
        ];
    }
}
