<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guides\ModerateGuideRequest;
use App\Models\Guide;
use App\Models\GuideCategory;
use App\Models\GuideRevision;
use App\Services\Guides\GuideWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminGuideController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $status = (string) $request->query('status', 'pending_review');
        $allowedStatuses = ['pending_review', 'changes_requested', 'rejected', 'published', 'archived', 'all'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'pending_review';

        $queue = GuideRevision::query()
            ->with(['guide.author.profile', 'category', 'coverMedia', 'moderator'])
            ->when($status === 'archived', fn ($query) => $query->whereHas('guide', fn ($guide) => $guide->where('status', 'archived')))
            ->when($status !== 'all' && $status !== 'archived', fn ($query) => $query->where('status', $status))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when(in_array($request->query('language'), ['de', 'en'], true), fn ($query) => $query->where('language', $request->query('language')))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $like = '%'.addcslashes(mb_substr((string) $request->query('q'), 0, 80), '\\%_').'%';
                $query->where(function ($search) use ($like): void {
                    $search->where('title', 'like', $like)
                        ->orWhereHas('guide.author', fn ($author) => $author
                            ->where('username', 'like', $like)
                            ->orWhere('name', 'like', $like));
                });
            })
            ->when($request->filled('from'), fn ($query) => $query->whereDate('submitted_at', '>=', $request->date('from')))
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $selectedId = $request->integer('revision') ?: optional($queue->first())->id;
        $selected = $selectedId
            ? GuideRevision::query()->with([
                'guide.author.profile',
                'guide.publishedRevision.category',
                'guide.publishedRevision.coverMedia',
                'guide.revisions.moderator',
                'guide.moderationEvents.actor',
                'category',
                'coverMedia',
                'moderator',
            ])->find($selectedId)
            : null;

        return view('admin.guides.index', [
            'queue' => $queue,
            'selected' => $selected,
            'activeStatus' => $status,
            'categories' => GuideCategory::query()->active()->get(),
            'pendingCount' => GuideRevision::query()->where('status', 'pending_review')->count(),
        ]);
    }

    public function moderate(
        ModerateGuideRequest $request,
        GuideRevision $revision,
        GuideWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->moderate(
            $revision,
            $request->user(),
            $request->validated('action'),
            $request->validated('reason')
        );

        return redirect()->route('admin.guides.index')->with('status', __('guides.admin.decision_saved'));
    }

    public function archive(Request $request, Guide $guide, GuideWorkflowService $workflow): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:3000']]);
        $workflow->archive($guide, $request->user(), $validated['reason']);

        return back()->with('status', __('guides.admin.archived'));
    }

    public function restore(Request $request, Guide $guide, GuideWorkflowService $workflow): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $workflow->restore($guide, $request->user());

        return back()->with('status', __('guides.admin.restored'));
    }

    public function feature(Request $request, Guide $guide, GuideWorkflowService $workflow): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($guide->isPublished(), 422);
        $validated = $request->validate(['is_featured' => ['required', 'boolean']]);
        $workflow->setFeatured($guide, $request->user(), (bool) $validated['is_featured']);

        return back()->with('status', __('guides.admin.featured_saved'));
    }
}
