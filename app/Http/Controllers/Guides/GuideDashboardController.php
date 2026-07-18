<?php

namespace App\Http\Controllers\Guides;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guides\SaveGuideRevisionRequest;
use App\Models\Guide;
use App\Models\GuideCategory;
use App\Services\Guides\GuideReputationService;
use App\Services\Guides\GuideWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideDashboardController extends Controller
{
    public function mine(Request $request, GuideReputationService $reputation): View
    {
        $status = (string) $request->query('status', 'all');
        if ($status !== 'all' && ! in_array($status, Guide::STATUSES, true)) {
            $status = 'all';
        }

        $query = Guide::query()
            ->forAuthor($request->user())
            ->with([
                'workingRevision.category',
                'workingRevision.coverMedia',
                'publishedRevision.category',
                'publishedRevision.coverMedia',
            ])
            ->latest('updated_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $counts = collect(Guide::STATUSES)
            ->mapWithKeys(fn (string $guideStatus): array => [
                $guideStatus => Guide::query()->forAuthor($request->user())->where('status', $guideStatus)->count(),
            ]);

        return view('themes.hnt_preview.guides.mine', [
            'guides' => $query->paginate(12)->withQueryString(),
            'activeStatus' => $status,
            'statusCounts' => $counts,
            'guideReputation' => $reputation->totalFor($request->user()),
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

    public function preview(Request $request, Guide $guide, GuideReputationService $reputation): View
    {
        $this->authorize('preview', $guide);
        $guide->loadMissing(['author.profile', 'workingRevision.category', 'workingRevision.coverMedia', 'publishedRevision.category']);
        $revision = $guide->workingRevision ?: $guide->publishedRevision;
        abort_unless($revision, 404);

        return view('themes.hnt_preview.guides.show', [
            'guide' => $guide,
            'revision' => $revision,
            'comments' => collect(),
            'isPreview' => true,
            'viewerHelpful' => false,
            'viewerBookmarked' => false,
            'authorReputation' => $reputation->totalFor($guide->author),
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
}
