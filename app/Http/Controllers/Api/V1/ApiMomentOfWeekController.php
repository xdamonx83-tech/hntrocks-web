<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MomentResource;
use App\Models\Moment;
use App\Models\MomentSpotlight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiMomentOfWeekController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $current = MomentSpotlight::query()
            ->with(['moment.user.profile', 'moment.media', 'moment.cover', 'selectedBy.profile'])
            ->published()
            ->whereHas('moment', fn ($query) => $query->published()->where('visibility', 'public'))
            ->latest('week_starts_at')
            ->latest('id')
            ->first();

        $archive = MomentSpotlight::query()
            ->with(['moment.user.profile', 'moment.media', 'moment.cover'])
            ->whereHas('moment', fn ($query) => $query->published()->where('visibility', 'public'))
            ->where(function ($query): void {
                $query->where('status', MomentSpotlight::STATUS_ACTIVE)
                    ->orWhere('status', MomentSpotlight::STATUS_ARCHIVED);
            })
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->latest('week_starts_at')
            ->latest('id')
            ->limit(12)
            ->get();

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $candidates = Moment::query()
            ->with([
                'user.profile',
                'media',
                'cover',
                'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
                'bookmarks' => fn ($query) => $query->where('user_id', $request->user()->id),
            ])
            ->published()
            ->where('visibility', 'public')
            ->whereBetween('published_at', [$weekStart, $weekEnd])
            ->orderByDesc('likes_count')
            ->orderByDesc('comments_count')
            ->orderByDesc('views_count')
            ->limit(6)
            ->get();

        if ($candidates->isEmpty()) {
            $candidates = Moment::query()
                ->with([
                    'user.profile',
                    'media',
                    'cover',
                    'reactions' => fn ($query) => $query->where('user_id', $request->user()->id)->where('type', 'like'),
                    'bookmarks' => fn ($query) => $query->where('user_id', $request->user()->id),
                ])
                ->published()
                ->where('visibility', 'public')
                ->latest('published_at')
                ->limit(6)
                ->get();
        }

        return response()->json([
            'data' => [
                'current' => $current ? $this->spotlightPayload($request, $current) : null,
                'archive' => $archive->map(fn (MomentSpotlight $spotlight): array => $this->spotlightPayload($request, $spotlight))->values(),
                'candidates' => MomentResource::collection($candidates)->resolve($request),
                'stats' => [
                    'archive_count' => $archive->count(),
                    'candidates_count' => $candidates->count(),
                    'week_start' => $weekStart->toDateString(),
                    'week_end' => $weekEnd->toDateString(),
                ],
            ],
        ]);
    }

    private function spotlightPayload(Request $request, MomentSpotlight $spotlight): array
    {
        $moment = $spotlight->moment;

        return [
            'id' => $spotlight->id,
            'title' => $spotlight->title,
            'note' => $spotlight->note,
            'status' => $spotlight->status,
            'status_label' => $spotlight->statusLabel(),
            'date_label' => $spotlight->dateLabel(),
            'week_starts_at' => $spotlight->week_starts_at?->toDateString(),
            'week_ends_at' => $spotlight->week_ends_at?->toDateString(),
            'published_at' => $spotlight->published_at?->toISOString(),
            'selected_by' => $spotlight->selectedBy ? [
                'id' => $spotlight->selectedBy->id,
                'name' => $spotlight->selectedBy->name,
                'username' => $spotlight->selectedBy->username,
            ] : null,
            'moment' => $moment ? (new MomentResource($moment))->resolve($request) : null,
        ];
    }
}
