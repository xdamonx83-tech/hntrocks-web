<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\Teams\TeamPermissionService;
use App\Services\Teams\TeamProgressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiTeamProgressController extends Controller
{
    public function show(Request $request, Team $team, TeamProgressionService $progression, TeamPermissionService $permissions): JsonResponse
    {
        Gate::authorize('viewTeamProgress', $team);

        return response()->json(['data' => [
            ...$progression->summary($team),
            'events' => $team->xpEvents()->latest()->limit(20)->get()->map(fn ($event): array => [
                'id' => $event->id,
                'type' => $event->event_type,
                'amount' => (int) $event->amount,
                'level_before' => (int) $event->level_before,
                'level_after' => (int) $event->level_after,
                'created_at' => $event->created_at?->toISOString(),
            ])->all(),
            'viewer_permissions' => $permissions->for($request->user(), $team),
        ]]);
    }
}
