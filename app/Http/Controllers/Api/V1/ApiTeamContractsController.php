<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TeamContractResource;
use App\Http\Resources\Api\TeamContractTemplateResource;
use App\Models\Team;
use App\Models\TeamContract;
use App\Models\TeamContractTemplate;
use App\Services\Teams\TeamContractService;
use App\Services\Teams\TeamPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiTeamContractsController extends Controller
{
    public function index(Request $request, Team $team, TeamPermissionService $permissions): JsonResponse
    {
        Gate::authorize('viewInternalContracts', $team);
        $active = $team->contracts()->with(['template', 'contributions'])->where('status', TeamContract::STATUS_ACTIVE)->first();

        return response()->json(['data' => [
            'active_contract' => $active ? new TeamContractResource($active) : null,
            'available_templates' => TeamContractTemplateResource::collection(TeamContractTemplate::query()->where('is_active', true)->orderBy('id')->get())->resolve($request),
            'viewer_permissions' => $permissions->for($request->user(), $team),
        ]]);
    }

    public function activate(Request $request, Team $team, TeamContractTemplate $template, TeamContractService $contracts, TeamPermissionService $permissions): JsonResponse
    {
        Gate::authorize('manageTeamContracts', $team);
        $contract = $contracts->activate($team, $template, $request->user())->load(['template', 'contributions']);

        return response()->json(['message' => 'Team-Auftrag wurde aktiviert.', 'data' => [
            'contract' => new TeamContractResource($contract),
            'viewer_permissions' => $permissions->for($request->user(), $team),
        ]], 201);
    }

    public function cancel(Request $request, Team $team, TeamContract $teamContract, TeamContractService $contracts): JsonResponse
    {
        Gate::authorize('manageTeamContracts', $team);
        abort_unless((int) $teamContract->team_id === (int) $team->id, 404);

        return response()->json([
            'message' => 'Team-Auftrag wurde abgebrochen.',
            'data' => ['contract' => new TeamContractResource($contracts->cancel($team, $teamContract)->load(['template', 'contributions']))],
        ]);
    }
}
