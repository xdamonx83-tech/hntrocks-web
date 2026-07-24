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
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:80'],
            'language' => ['nullable', Rule::in(Guide::LANGUAGES)],
            'difficulty' => ['nullable', Rule::in(Guide::DIFFICULTIES)],
            'platform' => ['nullable', Rule::in(Guide::PLATFORMS)],
            'sort' => ['nullable', Rule::in(['newest', 'popular', 'helpful'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $publishedQuery = Guide::query()->published();
        $categoryCounts = (clone $publishedQuery)
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->map(fn (Guide $guide): array => [
                'key' => (string) $guide->category,
                'count' => (int) $guide->aggregate,
            ])
            ->values()
            ->all();

        $stats = (clone $publishedQuery)
            ->selectRaw('COUNT(*) as guides_count')
            ->selectRaw('COALESCE(SUM(views_count), 0) as views_count')
            ->selectRaw('COALESCE(SUM(helpful_count), 0) as helpful_count')
            ->selectRaw('COALESCE(SUM(comments_count), 0) as comments_count')
            ->first();

        $query = Guide::query()
            ->published()
            ->with('author.profile');

        $search = trim((string) ($validated['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('summary', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            });
        }

        $category = trim((string) ($validated['category'] ?? ''));
        if ($category !== '') {
            $query->where('category', $category);
        }

        $language = $validated['language'] ?? null;
        if ($language !== null) {
            $query->where('language', $language);
        }

        $difficulty = $validated['difficulty'] ?? null;
        if ($difficulty !== null) {
            $query->where('difficulty', $difficulty);
        }

        $platform = $validated['platform'] ?? null;
        if ($platform !== null && $platform !== 'all') {
            $query->whereIn('platform', ['all', $platform]);
        }

        $query->orderByDesc('is_featured');
        match ($validated['sort'] ?? 'newest') {
            'popular' => $query->orderByDesc('views_count')->orderByDesc('published_at'),
            'helpful' => $query->orderByDesc('helpful_count')->orderByDesc('published_at'),
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
                    'categories' => array_column($categoryCounts, 'key'),
                    'languages' => Guide::LANGUAGES,
                    'difficulties' => Guide::DIFFICULTIES,
                    'platforms' => Guide::PLATFORMS,
                    'sorts' => ['newest', 'popular', 'helpful'],
                ],
                'overview_stats' => [
                    'guides_count' => (int) ($stats?->guides_count ?? 0),
                    'views_count' => (int) ($stats?->views_count ?? 0),
                    'helpful_count' => (int) ($stats?->helpful_count ?? 0),
                    'comments_count' => (int) ($stats?->comments_count ?? 0),
                ],
            ],
        ]);
    }
}
