<?php

namespace App\Services\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TeamPermissionService
{
    public function for(User $user, Team $team): array
    {
        $gate = Gate::forUser($user);

        return [
            'view_progress' => $gate->allows('viewTeamProgress', $team),
            'view_contracts' => $gate->allows('viewInternalContracts', $team),
            'manage_contracts' => $gate->allows('manageTeamContracts', $team),
            'view_sessions' => $gate->allows('viewTeamSessions', $team),
            'create_session' => $gate->allows('createTeamSession', $team),
            'update_session' => $gate->allows('updateTeamSession', $team),
            'cancel_session' => $gate->allows('cancelTeamSession', $team),
            'complete_session' => $gate->allows('completeTeamSession', $team),
            'respond_to_session' => $gate->allows('respondToTeamSession', $team),
            'view_participation' => $gate->allows('viewTeamParticipation', $team),
            'manage_members' => $gate->allows('manageTeamMembers', $team),
        ];
    }
}
