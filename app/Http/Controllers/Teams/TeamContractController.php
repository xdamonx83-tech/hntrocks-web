<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TeamContractResource;
use App\Models\Team;
use App\Models\TeamContract;
use App\Models\TeamContractTemplate;
use App\Services\Teams\TeamContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamContractController extends Controller
{
    public function activate(Request $request, Team $team, TeamContractTemplate $template, TeamContractService $contracts): JsonResponse|RedirectResponse
    {
        Gate::authorize('manageTeamContracts', $team);
        $contract = $contracts->activate($team, $template, $request->user())->load(['template', 'contributions']);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Team-Auftrag wurde aktiviert.', 'data' => ['contract' => new TeamContractResource($contract)]], 201);
        }

        return back()->with('status', 'Team-Auftrag wurde aktiviert.');
    }

    public function cancel(Request $request, Team $team, TeamContract $teamContract, TeamContractService $contracts): JsonResponse|RedirectResponse
    {
        Gate::authorize('manageTeamContracts', $team);
        abort_unless((int) $teamContract->team_id === (int) $team->id, 404);
        $contract = $contracts->cancel($team, $teamContract)->load(['template', 'contributions']);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Team-Auftrag wurde abgebrochen.', 'data' => ['contract' => new TeamContractResource($contract)]]);
        }

        return back()->with('status', 'Team-Auftrag wurde abgebrochen.');
    }
}
