<?php

namespace App\Http\Controllers\Moments;

use App\Http\Controllers\Controller;
use App\Models\Moment;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MomentReactionController extends Controller
{
    public function toggle(Request $request, Moment $moment, GamificationService $gamification, NotificationService $notifications): RedirectResponse
    {
        abort_unless(($moment->status === 'published' && $moment->visibility !== 'private') || $moment->canBeManagedBy($request->user()), 404);

        $reaction = $moment->reactions()
            ->where('user_id', $request->user()->id)
            ->where('type', 'like')
            ->first();

        if ($reaction) {
            $reaction->delete();
            $moment->decrement('likes_count');
            return back()->with('status', 'Like wurde entfernt.');
        }

        $reaction = $moment->reactions()->create([
            'user_id' => $request->user()->id,
            'type' => 'like',
        ]);

        $moment->increment('likes_count');
        $gamification->award($request->user(), 'moment_like_given', source: $reaction, description: 'Moment geliked');
        $gamification->award($moment->user, 'moment_like_received', source: $reaction, description: 'Like auf Moment erhalten');

        $notifications->send(
            $moment->user,
            $request->user(),
            'moment_like_new',
            'Neuer Like auf deinem Moment',
            $request->user()->username.' gefällt dein Moment.',
            route('moments.show', $moment)
        );

        return back()->with('status', 'Moment wurde geliked.');
    }
}
