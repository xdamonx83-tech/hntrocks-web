<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\Teams\TeamPermissionService;
use App\Services\Teams\TeamParticipationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiTeamParticipationController extends Controller
{
    public function index(Request $request, Team $team, TeamPermissionService $permissions, TeamParticipationService $participation): JsonResponse
    {
        Gate::authorize('viewTeamParticipation', $team);
        $participation->ensureForTeam($team);
        $rows = $team->participations()->with('user')->orderByDesc('points_total')->paginate(50);

        return response()->json(['data' => [
            'members' => $rows->getCollection()->map(fn ($row): array => [
                'user' => $row->user ? ['id' => $row->user->id, 'username' => $row->user->username, 'display_name' => $row->user->name, 'avatar_url' => $row->user->avatarUrl()] : null,
                'points_total' => (int) $row->points_total,
                'badge_key' => $row->badge_key,
                'last_activity_at' => $row->last_activity_at?->toISOString(),
                'is_viewer' => (int) $row->user_id === (int) $request->user()->id,
            ])->all(),
            'pagination' => ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'per_page' => $rows->perPage(), 'total' => $rows->total()],
            'viewer_permissions' => $permissions->for($request->user(), $team),
        ]]);
    }
}
