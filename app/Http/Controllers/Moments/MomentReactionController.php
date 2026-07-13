<?php

namespace App\Http\Controllers\Moments;

use App\Http\Controllers\Controller;
use App\Models\Moment;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MomentReactionController extends Controller
{
    public function toggle(Request $request, Moment $moment, GamificationService $gamification, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        abort_unless(($moment->status === 'published' && $moment->visibility !== 'private') || $moment->canBeManagedBy($request->user()), 404);

        $reaction = $moment->reactions()
            ->where('user_id', $request->user()->id)
            ->where('type', 'like')
            ->first();

        if ($reaction) {
            $reaction->delete();
            if ((int) $moment->likes_count > 0) {
                $moment->decrement('likes_count');
            }

            $count = $moment->reactions()->where('type', 'like')->count();
            $moment->updateQuietly(['likes_count' => $count]);

            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'liked' => false, 'count' => $count]);
            }

            return back()->with('status', 'Like wurde entfernt.');
        }

        $reaction = $moment->reactions()->create([
            'user_id' => $request->user()->id,
            'type' => 'like',
        ]);

        $count = $moment->reactions()->where('type', 'like')->count();
        $moment->updateQuietly(['likes_count' => $count]);
        $gamification->award($request->user(), 'moment_like_given', source: $reaction, description: 'Moment geliked');

        if ((int) $moment->user_id !== (int) $request->user()->id) {
            $gamification->award($moment->user, 'moment_like_received', source: $reaction, description: 'Like auf Moment erhalten');

            $notifications->send(
                $moment->user,
                $request->user(),
                'moment_like_new',
                'Neuer Like auf deinem Moment',
                $request->user()->username.' gefällt dein Moment.',
                route('moments.show', $moment)
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'liked' => true, 'count' => $count]);
        }

        return back()->with('status', 'Moment wurde geliked.');
    }
}
