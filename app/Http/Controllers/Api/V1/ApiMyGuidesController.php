<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuideRevision;
use App\Services\Guides\GuideDeletionService;
use App\Services\Guides\GuideReputationService;
use App\Services\Guides\GuideWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiMyGuidesController extends Controller
{
    use AuthorizesRequests;

    private const SORTS = ['updated', 'status', 'helpful'];

    public function index(Request $request, GuideReputationService $reputation): JsonResponse
    {
        $author = $request->user();
        $status = (string) $request->query('status', 'all');
        if ($status !== 'all' && ! in_array($status, Guide::STATUSES, true)) {
            $status = 'all';
        }

        $sort = (string) $request->query('sort', 'updated');
        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'updated';
        }

        $search = trim((string) $request->query('search', $request->query('q', '')));
        $perPage = min(24, max(1, (int) $request->query('per_page', 12)));

        $query = $this->statusQuery($author->id, $status)
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

        $page = $query->paginate($perPage);
        $total = Guide::query()->where('author_id', $author->id)->count();
        $statusCounts = ['all' => $total];
        foreach (Guide::STATUSES as $guideStatus) {
            $statusCounts[$guideStatus] = $this->statusQuery($author->id, $guideStatus)->count();
        }

        return response()->json([
            'data' => [
                'guides' => collect($page->items())
                    ->map(fn (Guide $guide): array => $this->guidePayload($guide, $request))
                    ->values()
                    ->all(),
                'status_counts' => $statusCounts,
                'stats' => [
                    'total' => $total,
                    'pending_review' => (int) ($statusCounts['pending_review'] ?? 0),
                    'published' => Guide::query()
                        ->where('author_id', $author->id)
                        ->whereNotNull('current_published_revision_id')
                        ->count(),
                    'helpful' => (int) Guide::query()
                        ->where('author_id', $author->id)
                        ->sum('helpful_count'),
                    'reputation' => $reputation->totalFor($author),
                ],
                'filters' => [
                    'statuses' => array_merge(['all'], Guide::STATUSES),
                    'sorts' => self::SORTS,
                ],
                'pagination' => [
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total(),
                    'has_more_pages' => $page->hasMorePages(),
                ],
            ],
        ]);
    }

    public function withdraw(Request $request, Guide $guide, GuideWorkflowService $workflow): JsonResponse
    {
        $this->authorize('withdraw', $guide);
        $revision = $workflow->withdraw($guide, $request->user());
        $guide->refresh();

        return response()->json([
            'message' => __('guides.editor.withdrawn'),
            'data' => [
                'guide' => [
                    'id' => (int) $guide->id,
                    'slug' => (string) $guide->slug,
                    'status' => (string) $guide->status,
                ],
                'revision' => [
                    'id' => (int) $revision->id,
                    'status' => (string) $revision->status,
                ],
            ],
        ]);
    }

    public function destroy(Request $request, Guide $guide, GuideDeletionService $deletion): JsonResponse
    {
        $this->authorize('delete', $guide);
        $deletion->deleteDraft($guide, $request->user());

        return response()->json([
            'message' => __('guides_mine.deleted'),
        ]);
    }

    private function statusQuery(int $authorId, string $status): Builder
    {
        $query = Guide::query()->where('author_id', $authorId);

        return match ($status) {
            'all' => $query,
            'draft' => $query
                ->where('status', 'draft')
                ->whereNull('current_published_revision_id'),
            'published' => $query->whereNotNull('current_published_revision_id'),
            default => $query->where('status', $status),
        };
    }

    /** @return array<string, mixed> */
    private function guidePayload(Guide $guide, Request $request): array
    {
        $working = $guide->workingRevision;
        $published = $guide->publishedRevision;
        $revision = $working ?: $published;
        $hasPublished = $published instanceof GuideRevision;
        $displayStatus = $hasPublished && $guide->status === 'draft' && $working
            ? 'published_with_draft'
            : (string) $guide->status;

        return [
            'id' => (int) $guide->id,
            'slug' => (string) $guide->slug,
            'status' => (string) $guide->status,
            'display_status' => $displayStatus,
            'show_in_profile' => (bool) $guide->show_in_profile,
            'helpful_count' => (int) $guide->helpful_count,
            'bookmarks_count' => (int) $guide->bookmarks_count,
            'comments_count' => (int) $guide->comments_count,
            'is_published' => $guide->isPublished(),
            'has_published_revision' => $hasPublished,
            'has_working_revision' => $working instanceof GuideRevision,
            'updated_at' => $guide->updated_at?->toISOString(),
            'published_at' => $guide->published_at?->toISOString(),
            'actions' => [
                'edit' => $request->user()->can('update', $guide),
                'preview' => $request->user()->can('preview', $guide),
                'withdraw' => $request->user()->can('withdraw', $guide),
                'delete' => $request->user()->can('delete', $guide),
                'open' => $guide->isPublished(),
            ],
            'revision' => $revision ? $this->revisionPayload($revision, $working === $revision) : null,
            'published_revision' => $published ? [
                'id' => (int) $published->id,
                'version' => (int) $published->version,
                'title' => (string) ($published->title ?? ''),
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function revisionPayload(GuideRevision $revision, bool $isWorking): array
    {
        $blocks = collect((array) $revision->content_blocks);
        $completeBlocks = $blocks->filter(function ($block): bool {
            $block = is_array($block) ? $block : [];
            $type = (string) ($block['type'] ?? '');

            return match ($type) {
                'heading', 'paragraph' => trim((string) ($block['text'] ?? '')) !== '',
                'steps', 'list' => collect((array) ($block['items'] ?? []))
                    ->contains(fn ($item): bool => trim((string) $item) !== ''),
                'image' => ! empty($block['media_id']),
                'notice', 'warning' => trim((string) ($block['text'] ?? '')) !== '',
                default => false,
            };
        })->count();
        $completionTotal = 4 + max(1, $blocks->count());
        $completionDone = ($revision->title ? 1 : 0)
            + ($revision->summary ? 1 : 0)
            + ($revision->category_id ? 1 : 0)
            + ($revision->cover_media_id ? 1 : 0)
            + $completeBlocks;

        return [
            'id' => (int) $revision->id,
            'version' => (int) $revision->version,
            'status' => (string) $revision->status,
            'title' => (string) ($revision->title ?? ''),
            'summary' => (string) ($revision->summary ?? ''),
            'category' => $revision->category ? [
                'id' => (int) $revision->category->id,
                'slug' => (string) $revision->category->slug,
                'label' => $revision->category->label(app()->getLocale() === 'en' ? 'en' : 'de'),
            ] : null,
            'cover_media_id' => $revision->cover_media_id ? (int) $revision->cover_media_id : null,
            'cover_url' => $revision->cover_media_id
                ? ($isWorking ? 'guides/drafts/media/' : 'guides/media/').(int) $revision->cover_media_id
                : null,
            'language' => (string) ($revision->language ?: 'de'),
            'difficulty' => (string) ($revision->difficulty ?: 'beginner'),
            'platform' => (string) ($revision->platform ?: 'all'),
            'reading_time_minutes' => max(1, (int) $revision->reading_time_minutes),
            'moderation_reason' => $revision->moderation_reason,
            'submitted_at' => $revision->submitted_at?->toISOString(),
            'updated_at' => $revision->updated_at?->toISOString(),
            'completion' => min(100, (int) round(($completionDone / $completionTotal) * 100)),
        ];
    }
}
