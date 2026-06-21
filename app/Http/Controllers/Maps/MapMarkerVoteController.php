<?php

namespace App\Http\Controllers\Maps;

use App\Http\Controllers\Controller;
use App\Models\HntMapMarker;
use App\Models\HntMapMarkerVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MapMarkerVoteController extends Controller
{
    public function store(Request $request, HntMapMarker $marker): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'integer', Rule::in([1, -1])],
        ]);

        abort_unless($marker->type === 'cash' && $marker->status === 'approved', 404);

        $viewerVote = DB::transaction(function () use ($request, $marker, $validated): ?int {
            $lockedMarker = HntMapMarker::query()->lockForUpdate()->findOrFail($marker->id);

            abort_unless($lockedMarker->type === 'cash' && $lockedMarker->status === 'approved', 404);

            $vote = HntMapMarkerVote::query()
                ->where('hnt_map_marker_id', $lockedMarker->id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();
            $value = (int) $validated['value'];

            if ($vote?->value === $value) {
                $vote->delete();

                return null;
            }

            if ($vote) {
                $vote->update(['value' => $value]);
            } else {
                $lockedMarker->votes()->create([
                    'user_id' => $request->user()->id,
                    'value' => $value,
                ]);
            }

            return $value;
        });

        $counts = HntMapMarkerVote::query()
            ->where('hnt_map_marker_id', $marker->id)
            ->selectRaw('value, COUNT(*) as aggregate')
            ->groupBy('value')
            ->pluck('aggregate', 'value');

        return response()->json([
            'ok' => true,
            'up_count' => (int) ($counts[1] ?? 0),
            'down_count' => (int) ($counts[-1] ?? 0),
            'viewer_vote' => $viewerVote,
        ]);
    }
}
