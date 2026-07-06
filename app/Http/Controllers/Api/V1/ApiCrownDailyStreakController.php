<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Economy\CrownDailyStreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiCrownDailyStreakController extends Controller
{
    public function show(Request $request, CrownDailyStreakService $dailyStreak): JsonResponse
    {
        return response()->json([
            'data' => $dailyStreak->status($request->user()),
        ]);
    }

    public function claim(Request $request, CrownDailyStreakService $dailyStreak): JsonResponse
    {
        $result = $dailyStreak->claim($request->user());

        if (! (bool) ($result['claimed'] ?? false)) {
            return response()->json([
                'message' => (bool) ($result['already_claimed'] ?? false)
                    ? 'Tägliche Belohnung wurde heute bereits eingesammelt.'
                    : 'Tägliche Belohnung ist aktuell nicht verfügbar.',
                'data' => [
                    'claimed' => false,
                    'already_claimed' => (bool) ($result['already_claimed'] ?? false),
                    'daily_streak' => $result['daily_streak'] ?? $dailyStreak->status($request->user()),
                ],
            ]);
        }

        return response()->json([
            'message' => 'Tägliche Belohnung eingesammelt.',
            'data' => [
                'claimed' => true,
                'amount' => (int) $result['amount'],
                'streak_day' => (int) $result['streak_day'],
                'current_streak' => (int) $result['current_streak'],
                'balance' => (int) $result['balance'],
                'lifetime_earned' => (int) $result['lifetime_earned'],
                'daily_streak' => $result['daily_streak'],
            ],
        ]);
    }

    public function dismiss(Request $request, CrownDailyStreakService $dailyStreak): JsonResponse
    {
        return response()->json([
            'message' => 'Tägliche Belohnung wird heute nicht erneut angezeigt.',
            'data' => [
                'daily_streak' => $dailyStreak->dismiss($request->user()),
            ],
        ]);
    }
}
