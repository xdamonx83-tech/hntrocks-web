<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuideMedia;
use App\Services\Guides\GuideWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminGuideController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $status = (string) $request->query('status', 'pending_review');
        if ($status !== 'all' && ! in_array($status, Guide::STATUSES, true)) {
            $status = 'pending_review';
        }

        $search = trim((string) $request->query('q', ''));

        $guides = Guide::query()
            ->with([
                'author.profile',
                'workingRevision.category',
                'publishedRevision.category',
            ])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('slug', 'like', '%'.$search.'%')
                        ->orWhereHas('author', function ($authorQuery) use ($search): void {
                            $authorQuery
                                ->where('username', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('workingRevision', function ($revisionQuery) use ($search): void {
                            $revisionQuery
                                ->where('title', 'like', '%'.$search.'%')
                                ->orWhere('summary', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('publishedRevision', function ($revisionQuery) use ($search): void {
                            $revisionQuery
                                ->where('title', 'like', '%'.$search.'%')
                                ->orWhere('summary', 'like', '%'.$search.'%');
                        });
                });
            })
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        $stats = collect(Guide::STATUSES)
            ->mapWithKeys(fn (string $guideStatus): array => [
                $guideStatus => Guide::query()->where('status', $guideStatus)->count(),
            ])
            ->all();
        $stats['all'] = Guide::query()->count();

        return view('admin.guides.index', [
            'guides' => $guides,
            'status' => $status,
            'search' => $search,
            'stats' => $stats,
            'statuses' => $this->statusLabels(),
        ]);
    }

    public function show(Request $request, Guide $guide): View
    {
        $this->guardAdmin($request);

        $guide->load([
            'author.profile',
            'workingRevision.category',
            'workingRevision.media',
            'publishedRevision.category',
            'publishedRevision.media',
            'moderationEvents.actor',
            'moderationEvents.revision',
        ]);

        $revision = $guide->workingRevision ?: $guide->publishedRevision;
        abort_unless($revision, 404);

        return view('admin.guides.show', [
            'guide' => $guide,
            'revision' => $revision,
            'statuses' => $this->statusLabels(),
        ]);
    }

    public function moderate(Request $request, Guide $guide, GuideWorkflowService $workflow): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'changes', 'reject'])],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $guide->loadMissing('workingRevision');
        $revision = $guide->workingRevision;
        abort_unless($revision, 404);

        $workflow->moderate(
            $revision,
            $request->user(),
            (string) $validated['action'],
            isset($validated['reason']) ? trim((string) $validated['reason']) : null,
        );

        return redirect()
            ->route('admin.guides.show', $guide)
            ->with('status', match ($validated['action']) {
                'approve' => 'Guide wurde freigegeben.',
                'changes' => 'Änderungswunsch wurde an den Autor gesendet.',
                'reject' => 'Guide wurde abgelehnt.',
            });
    }

    public function archive(Request $request, Guide $guide, GuideWorkflowService $workflow): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $workflow->archive($guide, $request->user(), trim((string) $validated['reason']));

        return redirect()
            ->route('admin.guides.show', $guide)
            ->with('status', 'Guide wurde archiviert.');
    }

    public function restore(Request $request, Guide $guide, GuideWorkflowService $workflow): RedirectResponse
    {
        $this->guardAdmin($request);

        $workflow->restore($guide, $request->user());

        return redirect()
            ->route('admin.guides.show', $guide)
            ->with('status', 'Guide wurde wiederhergestellt.');
    }

    public function featured(Request $request, Guide $guide, GuideWorkflowService $workflow): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'featured' => ['required', 'boolean'],
        ]);

        $workflow->setFeatured($guide, $request->user(), (bool) $validated['featured']);

        return redirect()
            ->route('admin.guides.show', $guide)
            ->with('status', (bool) $validated['featured']
                ? 'Guide ist jetzt hervorgehoben.'
                : 'Hervorhebung wurde entfernt.');
    }

    public function media(Request $request, GuideMedia $media): StreamedResponse
    {
        $this->guardAdmin($request);

        abort_unless($media->disk && $media->path, 404);
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);

        return $disk->response(
            $media->path,
            $media->original_name ?: basename($media->path),
            ['Content-Type' => $media->mime_type ?: 'application/octet-stream'],
            'inline',
        );
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return [
            'all' => 'Alle',
            'pending_review' => 'Zur Prüfung',
            'published' => 'Veröffentlicht',
            'changes_requested' => 'Änderungen angefordert',
            'rejected' => 'Abgelehnt',
            'draft' => 'Entwürfe',
            'archived' => 'Archiviert',
        ];
    }
}
