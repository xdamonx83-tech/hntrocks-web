<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewTeamProgress(User $user, Team $team): bool
    {
        return $team->visibility === 'public' || $this->isActiveMember($user, $team);
    }

    public function viewInternalContracts(User $user, Team $team): bool
    {
        return $this->isActiveMember($user, $team);
    }

    public function manageTeamContracts(User $user, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    public function viewTeamSessions(User $user, Team $team): bool
    {
        return $this->isActiveMember($user, $team);
    }

    public function createTeamSession(User $user, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    public function updateTeamSession(User $user, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    public function cancelTeamSession(User $user, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    public function completeTeamSession(User $user, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    public function respondToTeamSession(User $user, Team $team): bool
    {
        return $this->isActiveMember($user, $team);
    }

    public function viewTeamParticipation(User $user, Team $team): bool
    {
        return $this->isActiveMember($user, $team);
    }

    public function manageTeamMembers(User $user, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    private function isActiveMember(User $user, Team $team): bool
    {
        $team->loadMissing('members');
        $membership = $team->members->firstWhere('user_id', $user->id);

        return $membership !== null && $membership->status === 'active';
    }

    private function canManage(User $user, Team $team): bool
    {
        $team->loadMissing('members');
        $membership = $team->members->firstWhere('user_id', $user->id);

        return $membership !== null
            && $membership->status === 'active'
            && in_array($membership->role, ['owner', 'officer'], true);
    }
}
