<?php

namespace App\Http\Controllers\Guides;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guides\SaveGuideRevisionRequest;
use App\Models\Guide;
use App\Models\GuideCategory;
use App\Models\User;
use App\Services\Guides\GuideDeletionService;
use App\Services\Guides\GuideReputationService;
use App\Services\Guides\GuideWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideDashboardController extends Controller
{
    use AuthorizesRequests;

    public function mine(Request $request, GuideReputationService $reputation): View
    {
        $author = $request->user();
        $status = (string) $request->query('status', 'all');
        if ($status !== 'all' && ! in_array($status, Guide::STATUSES, true)) {
            $status = 'all';
        }

        $search = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'updated');
        if (! in_array($sort, ['updated', 'status', 'helpful'], true)) {
            $sort = 'updated';
        }

        $query = $this->statusQuery($author, $status)
            ->with([
                'workingRevision.category',
                'workingRevision.coverMedia',
                'publishedRevision.category',
                'publishedRevision.coverMedia',
            ])
            ->withExists([
                'revisions as has_published_revision' => fn (Builder $revisionQuery) => $revisionQuery
                    ->where('status', 'published'),
            ]);

        if ($search !== '') {
            $query->where(function (Builder $guideQuery) use ($search): void {
                $searchRevision = static function (Builder $revisionQuery) use ($search): void {
                    $revisionQuery->where(function (Builder $fields) use ($search): void {
                        $fields->where('title', 'like', '%'.$search.'%')
                            ->orWhere('summary', 'like', '%'.$search.'%')
                            ->orWhere('tags', 'like', '%'.$search.'%');
                    });
                };

                $guideQuery
                    ->whereHas('workingRevision', $searchRevision)
                    ->orWhereHas('publishedRevision', $searchRevision);
            });
        }

        match ($sort) {
            'helpful' => $query->orderByDesc('helpful_count')->latest('updated_at'),
            'status' => $query->orderBy('status')->latest('updated_at'),
            default => $query->latest('updated_at'),
        };

        $totalGuides = Guide::query()->forAuthor($author)->count();
        $statusCounts = collect(['all' => $totalGuides]);
        foreach (Guide::STATUSES as $guideStatus) {
            $statusCounts->put($guideStatus, $this->statusQuery($author, $guideStatus)->count());
        }

        $guideReputation = $reputation->totalFor($author);
        $publishedCount = Guide::query()
            ->forAuthor($author)
            ->whereNotNull('current_published_revision_id')
            ->count();
        $helpfulTotal = (int) Guide::query()->forAuthor($author)->sum('helpful_count');
        $helpfulGuideCount = Guide::query()
            ->forAuthor($author)
            ->where('helpful_count', '>', 0)
            ->count();

        $latestDecision = Guide::query()
            ->forAuthor($author)
            ->whereIn('status', ['changes_requested', 'published', 'rejected', 'archived'])
            ->with([
                'workingRevision.category',
                'publishedRevision.category',
            ])
            ->latest('updated_at')
            ->first();

        $versionGuide = Guide::query()
            ->forAuthor($author)
            ->whereNotNull('current_published_revision_id')
            ->with(['workingRevision', 'publishedRevision'])
            ->latest('updated_at')
            ->first();

        return view('themes.hnt_preview.guides.mine', [
            'guides' => $query->paginate(12)->withQueryString(),
            'activeStatus' => $status,
            'activeSort' => $sort,
            'search' => $search,
            'statusCounts' => $statusCounts,
            'totalGuides' => $totalGuides,
            'guideReputation' => $guideReputation,
            'publishedCount' => $publishedCount,
            'helpfulTotal' => $helpfulTotal,
            'helpfulGuideCount' => $helpfulGuideCount,
            'latestDecision' => $latestDecision,
            'versionGuide' => $versionGuide,
        ]);
    }

    public function store(Request $request, GuideWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('create', Guide::class);
        $guide = $workflow->create($request->user());

        return redirect()->route('guides.edit', $guide);
    }

    public function edit(Request $request, Guide $guide, GuideWorkflowService $workflow): View
    {
        $this->authorize('update', $guide);
        $revision = $workflow->ensureWorkingRevision($guide, $request->user());
        $guide->loadMissing('publishedRevision');

        return view('themes.hnt_preview.guides.editor', [
            'guide' => $guide,
            'revision' => $revision->load(['category', 'coverMedia']),
            'categories' => GuideCategory::query()->active()->get(),
        ]);
    }

    public function update(
        SaveGuideRevisionRequest $request,
        Guide $guide,
        GuideWorkflowService $workflow,
    ): RedirectResponse|JsonResponse {
        $revision = $workflow->save($guide, $request->user(), $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('guides.editor.saved'),
                'saved_at' => now()->toIso8601String(),
                'version' => $revision->version,
                'reading_time_minutes' => $revision->reading_time_minutes,
            ]);
        }

        return back()->with('status', __('guides.editor.saved'));
    }

    public function destroy(
        Request $request,
        Guide $guide,
        GuideDeletionService $deletion,
    ): RedirectResponse|JsonResponse {
        $this->authorize('delete', $guide);
        $deletion->deleteDraft($guide, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('guides_mine.deleted'),
            ]);
        }

        return redirect()->route('guides.mine')->with('status', __('guides_mine.deleted'));
    }

    public function preview(Request $request, Guide $guide, GuideReputationService $reputation): View
    {
        $this->authorize('preview', $guide);
        $guide->loadMissing(['author.profile', 'workingRevision.category', 'workingRevision.coverMedia', 'publishedRevision.category']);
        $revision = $guide->workingRevision ?: $guide->publishedRevision;
        abort_unless($revision, 404);

        $relatedGuides = Guide::query()
            ->published()
            ->where('guides.id', '!=', $guide->id)
            ->when($revision->category_id, fn ($query) => $query
                ->whereHas('publishedRevision', fn ($publishedRevision) => $publishedRevision
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
            'revision' => $revision,
            'comments' => collect(),
            'isPreview' => true,
            'viewerHelpful' => false,
            'viewerBookmarked' => false,
            'authorReputation' => $reputation->totalFor($guide->author),
            'authorPublishedGuideCount' => Guide::query()
                ->published()
                ->where('author_id', $guide->author_id)
                ->count(),
            'authorBadgeCount' => $guide->author?->badges()->count(),
            'relatedGuides' => $relatedGuides,
        ]);
    }

    public function submit(Request $request, Guide $guide, GuideWorkflowService $workflow): RedirectResponse|JsonResponse
    {
        $this->authorize('submit', $guide);
        $workflow->submit($guide, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => __('guides.editor.submitted')]);
        }

        return redirect()->route('guides.mine')->with('status', __('guides.editor.submitted'));
    }

    public function withdraw(Request $request, Guide $guide, GuideWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('withdraw', $guide);
        $workflow->withdraw($guide, $request->user());

        return back()->with('status', __('guides.editor.withdrawn'));
    }

    private function statusQuery(User $author, string $status): Builder
    {
        $query = Guide::query()->forAuthor($author);

        return match ($status) {
            'all' => $query,
            'draft' => $query
                ->where('status', 'draft')
                ->whereNull('current_published_revision_id'),
            'published' => $query->whereNotNull('current_published_revision_id'),
            default => $query->where('status', $status),
        };
    }
}
