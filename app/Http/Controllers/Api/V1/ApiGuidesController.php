<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\GuideOverviewResource;
use App\Models\Guide;
use App\Models\GuideComment;
use App\Models\GuideMedia;
use App\Services\Guides\GuideReputationService;
use App\Services\UserPrivacyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiGuidesController extends Controller
{
    private const LANGUAGES = ['de', 'en'];
    private const DIFFICULTIES = ['beginner', 'advanced', 'expert'];
    private const PLATFORMS = ['all', 'pc', 'playstation', 'xbox'];
    private const SORTS = ['newest', 'popular', 'helpful'];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:120'],
            'language' => ['nullable', Rule::in(self::LANGUAGES)],
            'difficulty' => ['nullable', Rule::in(self::DIFFICULTIES)],
            'platform' => ['nullable', Rule::in(self::PLATFORMS)],
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $locale = app()->getLocale() === 'en' ? 'en' : 'de';
        $publishedBase = Guide::query()->published();

        $stats = [
            'guides_count' => (clone $publishedBase)->count(),
            'authors_count' => (clone $publishedBase)->distinct('author_id')->count('author_id'),
            'helpful_count' => (int) (clone $publishedBase)->sum('helpful_count'),
            'bookmarks_count' => (int) (clone $publishedBase)->sum('bookmarks_count'),
            'comments_count' => (int) (clone $publishedBase)->sum('comments_count'),
        ];

        $categoryCounts = (clone $publishedBase)
            ->join('guide_revisions as published_revisions', 'published_revisions.id', '=', 'guides.current_published_revision_id')
            ->join('guide_categories', 'guide_categories.id', '=', 'published_revisions.category_id')
            ->where('guide_categories.is_active', true)
            ->selectRaw('guide_categories.id, guide_categories.slug, guide_categories.name_de, guide_categories.name_en, guide_categories.sort_order, count(*) as aggregate')
            ->groupBy([
                'guide_categories.id',
                'guide_categories.slug',
                'guide_categories.name_de',
                'guide_categories.name_en',
                'guide_categories.sort_order',
            ])
            ->orderBy('guide_categories.sort_order')
            ->orderBy($locale === 'en' ? 'guide_categories.name_en' : 'guide_categories.name_de')
            ->get()
            ->map(fn ($category): array => [
                'id' => (int) $category->id,
                'slug' => (string) $category->slug,
                'label' => (string) ($locale === 'en' ? $category->name_en : $category->name_de),
                'count' => (int) $category->aggregate,
            ])
            ->values()
            ->all();

        $query = Guide::query()
            ->published()
            ->with([
                'author.profile',
                'publishedRevision.category',
                'publishedRevision.coverMedia.mediaAsset',
            ])
            ->withCount([
                'bookmarks as viewer_bookmarked' => fn (Builder $bookmarkQuery) => $bookmarkQuery
                    ->where('user_id', $request->user()->id),
            ]);

        $search = trim((string) ($validated['search'] ?? ''));
        if ($search !== '') {
            $like = '%'.addcslashes(mb_substr($search, 0, 100), '\\%_').'%';
            $query->where(function (Builder $guideQuery) use ($like): void {
                $guideQuery
                    ->whereHas('publishedRevision', function (Builder $revisionQuery) use ($like): void {
                        $revisionQuery
                            ->where('title', 'like', $like)
                            ->orWhere('summary', 'like', $like)
                            ->orWhere('tags', 'like', $like)
                            ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery
                                ->where('name_de', 'like', $like)
                                ->orWhere('name_en', 'like', $like));
                    })
                    ->orWhereHas('author', fn (Builder $authorQuery) => $authorQuery
                        ->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like));
            });
        }

        $category = trim((string) ($validated['category'] ?? ''));
        if ($category !== '') {
            $query->whereHas(
                'publishedRevision.category',
                fn (Builder $categoryQuery) => $categoryQuery->where('slug', $category),
            );
        }

        $language = $validated['language'] ?? null;
        if ($language !== null) {
            $query->whereHas(
                'publishedRevision',
                fn (Builder $revisionQuery) => $revisionQuery->where('language', $language),
            );
        }

        $difficulty = $validated['difficulty'] ?? null;
        if ($difficulty !== null) {
            $query->whereHas(
                'publishedRevision',
                fn (Builder $revisionQuery) => $revisionQuery->where('difficulty', $difficulty),
            );
        }

        $platform = $validated['platform'] ?? null;
        if ($platform !== null && $platform !== 'all') {
            $query->whereHas('publishedRevision', function (Builder $revisionQuery) use ($platform): void {
                $revisionQuery->where(function (Builder $platformQuery) use ($platform): void {
                    $platformQuery->where('platform', $platform)->orWhere('platform', 'all');
                });
            });
        }

        $query->orderByDesc('is_featured');
        match ($validated['sort'] ?? 'newest') {
            'helpful' => $query->orderByDesc('helpful_count')->orderByDesc('published_at'),
            'popular' => $query
                ->orderByRaw('(helpful_count + bookmarks_count + comments_count) desc')
                ->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at'),
        };

        $guides = $query->paginate((int) ($validated['per_page'] ?? 12));

        return response()->json([
            'data' => [
                'guides' => collect($guides->items())
                    ->map(fn (Guide $guide): array => (new GuideOverviewResource($guide))->resolve($request))
                    ->values()
                    ->all(),
                'pagination' => [
                    'current_page' => $guides->currentPage(),
                    'last_page' => $guides->lastPage(),
                    'per_page' => $guides->perPage(),
                    'total' => $guides->total(),
                    'has_more_pages' => $guides->hasMorePages(),
                ],
                'category_counts' => $categoryCounts,
                'available_filters' => [
                    'categories' => array_map(
                        fn (array $categoryItem): array => [
                            'slug' => $categoryItem['slug'],
                            'label' => $categoryItem['label'],
                        ],
                        $categoryCounts,
                    ),
                    'languages' => self::LANGUAGES,
                    'difficulties' => self::DIFFICULTIES,
                    'platforms' => self::PLATFORMS,
                    'sorts' => self::SORTS,
                ],
                'overview_stats' => $stats,
            ],
        ]);
    }

    public function show(
        Request $request,
        Guide $guide,
        GuideReputationService $reputation,
        UserPrivacyService $privacy,
    ): JsonResponse {
        abort_unless($guide->isPublished(), 404);

        $guide->loadMissing([
            'author.profile',
            'author.privacySettings',
            'publishedRevision.category',
            'publishedRevision.coverMedia',
        ]);

        $revision = $guide->publishedRevision;
        abort_unless($revision !== null, 404);

        $viewer = $request->user();
        $author = $guide->author;
        $canViewGamification = $author
            ? $privacy->canViewGamification($viewer, $author)
            : false;

        $relatedGuides = Guide::query()
            ->published()
            ->whereKeyNot($guide->id)
            ->when($revision->category_id, fn (Builder $query) => $query
                ->whereHas('publishedRevision', fn (Builder $publishedRevision) => $publishedRevision
                    ->where('category_id', $revision->category_id)))
            ->with([
                'author.profile',
                'publishedRevision.category',
                'publishedRevision.coverMedia',
            ])
            ->withCount([
                'bookmarks as viewer_bookmarked' => fn (Builder $bookmarkQuery) => $bookmarkQuery
                    ->where('user_id', $viewer->id),
            ])
            ->orderByDesc('helpful_count')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get()
            ->map(fn (Guide $related): array => (new GuideOverviewResource($related))->resolve($request))
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'guide' => [
                    'id' => (int) $guide->id,
                    'slug' => (string) $guide->slug,
                    'title' => (string) $revision->title,
                    'summary' => (string) $revision->summary,
                    'cover_url' => $revision->cover_media_id
                        ? 'guides/media/'.(int) $revision->cover_media_id
                        : null,
                    'category' => $revision->category ? [
                        'id' => (int) $revision->category->id,
                        'slug' => (string) $revision->category->slug,
                        'label' => $revision->category->label(app()->getLocale() === 'en' ? 'en' : 'de'),
                    ] : null,
                    'tags' => array_values(array_filter((array) $revision->tags)),
                    'language' => (string) $revision->language,
                    'difficulty' => (string) $revision->difficulty,
                    'platform' => (string) $revision->platform,
                    'reading_time_minutes' => max(1, (int) $revision->reading_time_minutes),
                    'version' => (int) $revision->version,
                    'published_at' => $guide->published_at?->toISOString(),
                    'updated_at' => ($revision->reviewed_at ?: $revision->updated_at)?->toISOString(),
                    'content_blocks' => $this->contentBlocks((array) $revision->content_blocks),
                ],
                'author' => $author ? [
                    'id' => (int) $author->id,
                    'username' => (string) $author->username,
                    'display_name' => (string) ($author->name ?: $author->username),
                    'avatar_url' => $author->avatarUrl(),
                    'published_guides_count' => Guide::query()
                        ->published()
                        ->where('author_id', $author->id)
                        ->count(),
                    'reputation' => $canViewGamification ? $reputation->totalFor($author) : null,
                    'badge_count' => $canViewGamification ? $author->badges()->count() : null,
                ] : null,
                'viewer' => [
                    'helpful' => $guide->isHelpfulFor($viewer),
                    'bookmarked' => $guide->isBookmarkedBy($viewer),
                    'owns_guide' => $guide->isOwnedBy($viewer),
                    'can_edit' => $viewer?->can('update', $guide) ?? false,
                ],
                'counts' => [
                    'helpful' => (int) $guide->helpful_count,
                    'bookmarks' => (int) $guide->bookmarks_count,
                    'comments' => (int) $guide->comments_count,
                ],
                'related_guides' => $relatedGuides,
            ],
        ]);
    }

    public function comments(Request $request, Guide $guide): JsonResponse
    {
        abort_unless($guide->isPublished(), 404);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $comments = GuideComment::query()
            ->withTrashed()
            ->with([
                'guide',
                'user.profile',
                'replies' => fn ($query) => $query
                    ->withTrashed()
                    ->with(['guide', 'user.profile']),
            ])
            ->where('guide_id', $guide->id)
            ->whereNull('parent_id')
            ->oldest()
            ->paginate((int) ($validated['per_page'] ?? 12));

        return response()->json([
            'data' => [
                'comments' => collect($comments->items())
                    ->map(fn (GuideComment $comment): array => $this->commentPayload($comment, $request))
                    ->values()
                    ->all(),
                'pagination' => [
                    'current_page' => $comments->currentPage(),
                    'last_page' => $comments->lastPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                    'has_more_pages' => $comments->hasMorePages(),
                ],
            ],
        ]);
    }

    public function media(GuideMedia $media): StreamedResponse
    {
        $media->loadMissing('guide.publishedRevision');
        $guide = $media->guide;
        $publishedRevision = $guide?->publishedRevision;

        $contentMediaIds = collect((array) $publishedRevision?->content_blocks)
            ->filter(fn ($block): bool => is_array($block) && ($block['type'] ?? null) === 'image')
            ->map(fn (array $block): int => (int) ($block['media_id'] ?? 0))
            ->filter()
            ->all();

        abort_unless(
            $guide instanceof Guide
                && $guide->isPublished()
                && (
                    (int) $publishedRevision?->cover_media_id === (int) $media->id
                    || in_array((int) $media->id, $contentMediaIds, true)
                ),
            404,
        );

        $disk = $media->disk ?: 'local';
        abort_unless(Storage::disk($disk)->exists($media->path), 404);

        return Storage::disk($disk)->response(
            $media->path,
            null,
            [
                'Content-Type' => $media->mime_type,
                'Cache-Control' => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function contentBlocks(array $blocks): array
    {
        return collect($blocks)
            ->filter(fn ($block): bool => is_array($block))
            ->map(function (array $block): array {
                $type = (string) ($block['type'] ?? '');
                $allowed = ['heading', 'paragraph', 'steps', 'list', 'image', 'notice', 'warning'];
                if (! in_array($type, $allowed, true)) {
                    return [];
                }

                $payload = [
                    'id' => isset($block['id']) ? (string) $block['id'] : null,
                    'type' => $type,
                ];

                if (in_array($type, ['heading', 'paragraph', 'notice', 'warning'], true)) {
                    $payload['text'] = (string) ($block['text'] ?? '');
                }
                if ($type === 'heading') {
                    $payload['level'] = max(2, min(4, (int) ($block['level'] ?? 2)));
                }
                if (in_array($type, ['steps', 'list'], true)) {
                    $payload['items'] = array_values(array_map(
                        static fn ($item): string => (string) $item,
                        array_filter((array) ($block['items'] ?? []), 'is_scalar'),
                    ));
                }
                if (in_array($type, ['notice', 'warning'], true)) {
                    $payload['title'] = (string) ($block['title'] ?? '');
                }
                if ($type === 'image') {
                    $mediaId = (int) ($block['media_id'] ?? 0);
                    $payload['media_id'] = $mediaId;
                    $payload['media_url'] = $mediaId > 0 ? 'guides/media/'.$mediaId : null;
                    $payload['caption'] = (string) ($block['caption'] ?? '');
                }

                return $payload;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function commentPayload(GuideComment $comment, Request $request): array
    {
        $deleted = $comment->trashed();
        $viewer = $request->user();

        return [
            'id' => (int) $comment->id,
            'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
            'body' => $deleted ? null : (string) $comment->body,
            'deleted' => $deleted,
            'created_at' => $comment->created_at?->toISOString(),
            'is_guide_author' => (int) $comment->user_id === (int) $comment->guide?->author_id,
            'author' => $comment->user ? [
                'id' => (int) $comment->user->id,
                'display_name' => (string) ($comment->user->name ?: $comment->user->username),
                'username' => (string) $comment->user->username,
                'avatar_url' => $comment->user->avatarUrl(),
            ] : null,
            'actions' => [
                'can_reply' => ! $deleted && $comment->parent_id === null,
                'can_edit' => ! $deleted && $comment->canEdit($viewer),
                'can_delete' => ! $deleted && $comment->canDelete($viewer),
                'can_report' => ! $deleted && (int) $comment->user_id !== (int) $viewer->id,
            ],
            'replies' => $comment->relationLoaded('replies')
                ? $comment->replies
                    ->map(fn (GuideComment $reply): array => $this->commentPayload($reply, $request))
                    ->values()
                    ->all()
                : [],
        ];
    }
}
