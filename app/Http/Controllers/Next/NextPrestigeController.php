<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use App\Services\HunterPrestigeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class NextPrestigeController extends Controller
{
    public function status(Request $request, HunterPrestigeService $prestige): JsonResponse
    {
        return response()->json($prestige->status($request->user()));
    }

    public function start(Request $request, HunterPrestigeService $prestige): JsonResponse
    {
        // Disabled until the HNTv3 confirmation modal is bound to this endpoint.
        // No mutations are allowed from the old demo interface.
        abort_unless(config('hnt_prestige.enabled'), 404);

        $validated = $request->validate([
            'reward' => ['required', Rule::in(['xp', 'cosmetic'])],
        ]);

        $history = $prestige->start($request->user(), $validated['reward']);
        $fresh = $request->user()->fresh();

        return response()->json([
            'success' => true,
            'prestige' => $prestige->status($fresh),
            'reward' => [
                'type' => $history->reward_type,
                'cosmetic' => $history->shopItem?->displayName(),
            ],
        ]);
    }
}
