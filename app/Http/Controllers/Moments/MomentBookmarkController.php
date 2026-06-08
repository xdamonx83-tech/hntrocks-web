<?php

namespace App\Http\Controllers\Moments;

use App\Http\Controllers\Controller;
use App\Models\Moment;
use App\Services\GamificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MomentBookmarkController extends Controller
{
    public function toggle(Request $request, Moment $moment, GamificationService $gamification): RedirectResponse
    {
        abort_unless(($moment->status === 'published' && $moment->visibility !== 'private') || $moment->canBeManagedBy($request->user()), 404);

        $bookmark = $moment->bookmarks()->where('user_id', $request->user()->id)->first();

        if ($bookmark) {
            $bookmark->delete();
            $moment->decrement('bookmarks_count');
            return back()->with('status', 'Moment wurde aus deinen gespeicherten Inhalten entfernt.');
        }

        $bookmark = $moment->bookmarks()->create([
            'user_id' => $request->user()->id,
        ]);

        $moment->increment('bookmarks_count');
        $gamification->award($request->user(), 'moment_saved', source: $bookmark, description: 'Moment gespeichert');

        return back()->with('status', 'Moment wurde gespeichert.');
    }
}
