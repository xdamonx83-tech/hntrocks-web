<?php

namespace App\Http\Controllers\Presence;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceHeartbeatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $lastSeenAt = now();

        $user->forceFill([
            'last_seen_at' => $lastSeenAt,
        ])->save();

        return response()->json([
            'message' => 'Presence updated.',
            'user_id' => (int) $user->id,
            'last_seen_at' => $lastSeenAt->toIso8601String(),
            'online_window_seconds' => User::ONLINE_WINDOW_SECONDS,
        ]);
    }
}
