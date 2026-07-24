<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\GuideOverviewResource;
use App\Models\Guide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
}
