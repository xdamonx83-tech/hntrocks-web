<?php

namespace App\Http\Controllers\Maps;

use App\Http\Controllers\Controller;
use App\Models\HntMapMarker;
use App\Models\HntMapMarkerVote;
use App\Support\MapVoteVisitorIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MapMarkerVoteController extends Controller
{
    public function store(Request $request, HntMapMarker $marker, MapVoteVisitorIdentity $visitorIdentity): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'integer', Rule::in([1, -1])],
        ]);

        abort_unless($marker->type === 'cash' && $marker->status === 'approved', 404);

        $userId = $request->user()?->id;
        $visitor = $userId === null ? $visitorIdentity->resolve($request) : null;

        $viewerVote = DB::transaction(function () use ($request, $marker, $validated, $userId, $visitor, $visitorIdentity): ?int {
            $lockedMarker = HntMapMarker::query()->lockForUpdate()->findOrFail($marker->id);

            abort_unless($lockedMarker->type === 'cash' && $lockedMarker->status === 'approved', 404);

            $voteQuery = HntMapMarkerVote::query()
                ->where('hnt_map_marker_id', $lockedMarker->id);

            if ($userId !== null) {
                $voteQuery->where('user_id', $userId);
            } else {
                $voteQuery->where('visitor_hash', $visitor['hash']);
            }

            $vote = $voteQuery->lockForUpdate()->first();
            $value = (int) $validated['value'];

            if ($vote?->value === $value) {
                $vote->delete();

                return null;
            }

            if ($vote) {
                $vote->update([
                    'value' => $value,
                    ...($userId === null ? [
                        'ip_hash' => $visitorIdentity->requestValueHash($request->ip()),
                        'user_agent_hash' => $visitorIdentity->requestValueHash($request->userAgent()),
                    ] : []),
                ]);
            } else {
                $lockedMarker->votes()->create([
                    'user_id' => $userId,
                    'visitor_hash' => $visitor['hash'] ?? null,
                    'ip_hash' => $userId === null ? $visitorIdentity->requestValueHash($request->ip()) : null,
                    'user_agent_hash' => $userId === null ? $visitorIdentity->requestValueHash($request->userAgent()) : null,
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

        $response = response()->json([
            'ok' => true,
            'up_count' => (int) ($counts[1] ?? 0),
            'down_count' => (int) ($counts[-1] ?? 0),
            'viewer_vote' => $viewerVote,
        ]);

        if ($visitor !== null && $visitor['is_new']) {
            $response->withCookie($visitorIdentity->cookie($visitor['token'], $request));
        }

        return $response;
    }
}
