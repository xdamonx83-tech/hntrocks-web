<?php

namespace App\Http\Controllers\Guides;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuideCategory;
use App\Models\GuideComment;
use App\Services\Guides\GuideReputationService;
use App\Services\UserPrivacyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request): View
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
        $categories = GuideCategory::query()->active()->get();

        return view('themes.hnt_preview.guides.index', [
            'guides' => $guides,
            'categories' => $categories,
            'filters' => $filters,
            'publishedCount' => Guide::query()->published()->count(),
            'authorCount' => Guide::query()->published()->distinct('author_id')->count('author_id'),
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

        return view('themes.hnt_preview.guides.show', [
            'guide' => $guide,
            'revision' => $guide->publishedRevision,
            'comments' => $comments,
            'isPreview' => false,
            'viewerHelpful' => $guide->isHelpfulFor($request->user()),
            'viewerBookmarked' => $guide->isBookmarkedBy($request->user()),
            'authorReputation' => $privacy->canViewGamification($request->user(), $guide->author)
                ? $reputation->totalFor($guide->author)
                : null,
        ]);
    }
}
