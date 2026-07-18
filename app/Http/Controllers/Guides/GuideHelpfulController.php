<?php

namespace App\Http\Controllers\Guides;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Services\Guides\GuideReputationService;
use App\Services\NotificationService;
use App\Services\UserBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuideHelpfulController extends Controller
{
    public function toggle(
        Request $request,
        Guide $guide,
        UserBlockService $blocks,
        GuideReputationService $reputation,
        NotificationService $notifications,
    ): JsonResponse {
        abort_unless($guide->isPublished(), 404);
        $guide->loadMissing('author');
        abort_if($guide->isOwnedBy($request->user()), 422, __('guides.helpful.own_forbidden'));
        abort_if($blocks->areBlocked($request->user(), $guide->author), 404);

        [$active, $count] = DB::transaction(function () use ($request, $guide, $reputation, $notifications): array {
            $locked = Guide::query()->lockForUpdate()->findOrFail($guide->id);
            $vote = $locked->helpfulVotes()->where('user_id', $request->user()->id)->first();

            if ($vote) {
                $vote->delete();
                $active = false;
                $reputation->reverseHelpful($locked, $request->user());
            } else {
                $locked->helpfulVotes()->create(['user_id' => $request->user()->id]);
                $active = true;
                $reputation->awardHelpful($locked, $request->user());
                $notifications->send(
                    $locked->author,
                    $request->user(),
                    'guide_helpful_added',
                    'guides.notifications.helpful_title',
                    'guides.notifications.helpful_body',
                    route('guides.show', $locked)
                );
            }

            $count = $locked->helpfulVotes()->count();
            $locked->updateQuietly(['helpful_count' => $count]);

            return [$active, $count];
        });

        return response()->json([
            'ok' => true,
            'helpful' => $active,
            'count' => $count,
            'message' => $active ? __('guides.helpful.added') : __('guides.helpful.removed'),
        ]);
    }
}
