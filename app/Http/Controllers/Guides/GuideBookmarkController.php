<?php

namespace App\Http\Controllers\Guides;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Services\UserBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuideBookmarkController extends Controller
{
    public function toggle(Request $request, Guide $guide, UserBlockService $blocks): JsonResponse
    {
        abort_unless($guide->isPublished(), 404);
        $guide->loadMissing('author');
        abort_if($blocks->areBlocked($request->user(), $guide->author), 404);

        [$saved, $count] = DB::transaction(function () use ($request, $guide): array {
            $locked = Guide::query()->lockForUpdate()->findOrFail($guide->id);
            $bookmark = $locked->bookmarks()->where('user_id', $request->user()->id)->first();

            if ($bookmark) {
                $bookmark->delete();
                $saved = false;
            } else {
                $locked->bookmarks()->create(['user_id' => $request->user()->id]);
                $saved = true;
            }

            $count = $locked->bookmarks()->count();
            $locked->updateQuietly(['bookmarks_count' => $count]);

            return [$saved, $count];
        });

        return response()->json([
            'ok' => true,
            'saved' => $saved,
            'count' => $count,
            'message' => $saved ? __('guides.bookmark.saved') : __('guides.bookmark.removed'),
        ]);
    }
}
