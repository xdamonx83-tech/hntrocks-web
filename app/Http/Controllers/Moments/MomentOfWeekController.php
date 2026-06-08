<?php

namespace App\Http\Controllers\Moments;

use App\Http\Controllers\Controller;
use App\Models\Moment;
use App\Models\MomentSpotlight;
use App\Support\HntTheme;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MomentOfWeekController extends Controller
{
    public function index(Request $request): View
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
            ->paginate(9)
            ->withQueryString();

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $topMoments = Moment::query()
            ->with(['user.profile', 'media', 'cover'])
            ->published()
            ->where('visibility', 'public')
            ->whereBetween('published_at', [$weekStart, $weekEnd])
            ->orderByDesc('likes_count')
            ->orderByDesc('comments_count')
            ->orderByDesc('views_count')
            ->limit(6)
            ->get();

        if ($topMoments->isEmpty()) {
            $topMoments = Moment::query()
                ->with(['user.profile', 'media', 'cover'])
                ->published()
                ->where('visibility', 'public')
                ->latest('published_at')
                ->limit(6)
                ->get();
        }

        return HntTheme::view('moment-of-week.index', [
            'currentSpotlight' => $current,
            'archive' => $archive,
            'topMoments' => $topMoments,
        ]);
    }
}
