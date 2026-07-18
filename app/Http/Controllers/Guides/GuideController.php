<?php

namespace App\Http\Controllers\Guides;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuideCategory;
use App\Models\GuideComment;
use App\Models\User;
use App\Services\Guides\GuideReputationService;
use App\Services\UserPrivacyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request, GuideReputationService $reputation): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => trim((string) $request->query('category', '')),
            'language' => trim((string) $request->query('language', '')),
            'platform' => trim((string) $request->query('platform', '')),
            'difficulty' => trim((string) $request->query('difficulty', '')),
            'sort' => trim((string) $request->query('sort', 'new')),
        ];

        $query = Guide::query()
            ->published()
            ->with([
                'author.profile',
                'publishedRevision.category',
                'publishedRevision.coverMedia',
            ]);

        $query->when($filters['q'] !== '', function (Builder $guideQuery) use ($filters): void {
            $like = '%'.addcslashes(mb_substr($filters['q'], 0, 80), '\\%_').'%';
            $guideQuery->where(function (Builder $search) use ($like): void {
                $search
                    ->whereHas('publishedRevision', function (Builder $revision) use ($like): void {
                        $revision
                            ->where('title', 'like', $like)
                            ->orWhere('summary', 'like', $like)
                            ->orWhere('tags', 'like', $like)
                            ->orWhereHas('category', fn (Builder $category) => $category
                                ->where('name_de', 'like', $like)
                                ->orWhere('name_en', 'like', $like));
                    })
                    ->orWhereHas('author', fn (Builder $author) => $author
                        ->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like));
            });
        });

        $query->when($filters['category'] !== '', fn (Builder $guideQuery) => $guideQuery
            ->whereHas('publishedRevision.category', fn (Builder $category) => $category->where('slug', $filters['category'])));
        $query->when(in_array($filters['language'], ['de', 'en'], true), fn (Builder $guideQuery) => $guideQuery
            ->whereHas('publishedRevision', fn (Builder $revision) => $revision->where('language', $filters['language'])));
        $query->when(in_array($filters['platform'], ['pc', 'playstation', 'xbox'], true), fn (Builder $guideQuery) => $guideQuery
            ->whereHas('publishedRevision', function (Builder $revision) use ($filters): void {
                $revision->where(function (Builder $platform) use ($filters): void {
                    $platform->where('platform', $filters['platform'])->orWhere('platform', 'all');
                });
            }));
        $query->when(in_array($filters['difficulty'], ['beginner', 'advanced', 'expert'], true), fn (Builder $guideQuery) => $guideQuery
            ->whereHas('publishedRevision', fn (Builder $revision) => $revision->where('difficulty', $filters['difficulty'])));

        match ($filters['sort']) {
            'helpful' => $query->orderByDesc('helpful_count')->orderByDesc('published_at'),
            'popular' => $query->orderByRaw('(helpful_count + bookmarks_count + comments_count) desc')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at'),
        };

        $guides = $query->paginate(12)->withQueryString();

        $publishedCount = Guide::query()->published()->count();
        $authorCount = Guide::query()->published()->distinct('author_id')->count('author_id');
        $helpfulCount = (int) Guide::query()->published()->sum('helpful_count');

        $categoryCounts = Guide::query()
            ->published()
            ->join('guide_revisions as published_revisions', 'published_revisions.id', '=', 'guides.current_published_revision_id')
            ->selectRaw('published_revisions.category_id as category_id, count(*) as aggregate')
            ->groupBy('published_revisions.category_id')
            ->pluck('aggregate', 'category_id');

        $categories = GuideCategory::query()
            ->active()
            ->get()
            ->each(function (GuideCategory $category) use ($categoryCounts): void {
                $category->setAttribute(
                    'published_guides_count',
                    (int) ($categoryCounts->get($category->id, 0))
                );
            });

        $featuredGuide = Guide::query()
            ->published()
            ->with([
                'author.profile',
                'publishedRevision.category',
                'publishedRevision.coverMedia',
            ])
            ->orderByDesc('is_featured')
            ->orderByDesc('helpful_count')
            ->orderByDesc('published_at')
            ->first();

        $topAuthorStats = Guide::query()
            ->published()
            ->selectRaw('guides.author_id, count(*) as published_guides_count, coalesce(sum(guides.helpful_count), 0) as helpful_total')
            ->groupBy('guides.author_id')
            ->orderByDesc('helpful_total')
            ->orderByDesc('published_guides_count')
            ->limit(3)
            ->get();

        $topAuthorModels = User::query()
            ->with('profile')
            ->whereIn('id', $topAuthorStats->pluck('author_id'))
            ->get()
            ->keyBy('id');

        $topAuthors = $topAuthorStats
            ->map(function (Guide $authorStats) use ($topAuthorModels): ?User {
                $author = $topAuthorModels->get($authorStats->author_id);

                if (! $author) {
                    return null;
                }

                $author->setAttribute('published_guides_count', (int) $authorStats->published_guides_count);
                $author->setAttribute('guides_helpful_total', (int) $authorStats->helpful_total);

                return $author;
            })
            ->filter()
            ->values();

        $viewer = $request->user();
        $viewerReputation = $viewer ? $reputation->totalFor($viewer) : null;
        $viewerGuideStats = null;
        $bookmarkedGuideIds = [];

        if ($viewer) {
            $statusCounts = Guide::query()
                ->forAuthor($viewer)
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $viewerGuideStats = [
                'drafts' => (int) $statusCounts->only(['draft', 'changes_requested', 'rejected'])->sum(),
                'review' => (int) $statusCounts->get('pending_review', 0),
                'published' => Guide::query()->forAuthor($viewer)->published()->count(),
            ];

            $bookmarkedGuideIds = $viewer->guideBookmarks()
                ->whereIn('guide_id', $guides->getCollection()->modelKeys())
                ->pluck('guide_id')
                ->map(fn ($guideId) => (int) $guideId)
                ->all();
        }

        return view('themes.hnt_preview.guides.index', [
            'guides' => $guides,
            'categories' => $categories,
            'filters' => $filters,
            'publishedCount' => $publishedCount,
            'authorCount' => $authorCount,
            'helpfulCount' => $helpfulCount,
            'featuredGuide' => $featuredGuide,
            'topAuthors' => $topAuthors,
            'viewerReputation' => $viewerReputation,
            'viewerGuideStats' => $viewerGuideStats,
            'bookmarkedGuideIds' => $bookmarkedGuideIds,
        ]);
    }

    public function show(
        Request $request,
        Guide $guide,
        GuideReputationService $reputation,
        UserPrivacyService $privacy,
    ): View {
        abort_unless($guide->isPublished(), 404);

        $guide->loadMissing([
            'author.profile',
            'author.privacySettings',
            'publishedRevision.category',
            'publishedRevision.coverMedia',
        ]);

        $comments = GuideComment::query()
            ->withTrashed()
            ->with(['user.profile', 'replies' => fn ($query) => $query->withTrashed()->with('user.profile')])
            ->where('guide_id', $guide->id)
            ->whereNull('parent_id')
            ->oldest()
            ->paginate(12, ['*'], 'comments_page')
            ->withQueryString();

        $revision = $guide->publishedRevision;
        $canViewGamification = $privacy->canViewGamification($request->user(), $guide->author);
        $relatedGuides = Guide::query()
            ->published()
            ->where('guides.id', '!=', $guide->id)
            ->when($revision?->category_id, fn (Builder $query) => $query
                ->whereHas('publishedRevision', fn (Builder $publishedRevision) => $publishedRevision
                    ->where('category_id', $revision->category_id)))
            ->with([
                'publishedRevision.category',
                'publishedRevision.coverMedia',
            ])
            ->orderByDesc('helpful_count')
            ->orderByDesc('published_at')
            ->limit(2)
            ->get();

        return view('themes.hnt_preview.guides.show', [
            'guide' => $guide,
            'revision' => $guide->publishedRevision,
            'comments' => $comments,
            'isPreview' => false,
            'viewerHelpful' => $guide->isHelpfulFor($request->user()),
            'viewerBookmarked' => $guide->isBookmarkedBy($request->user()),
            'authorReputation' => $canViewGamification
                ? $reputation->totalFor($guide->author)
                : null,
            'authorPublishedGuideCount' => Guide::query()
                ->published()
                ->where('author_id', $guide->author_id)
                ->count(),
            'authorBadgeCount' => $canViewGamification
                ? $guide->author?->badges()->count()
                : null,
            'relatedGuides' => $relatedGuides,
        ]);
    }
}
