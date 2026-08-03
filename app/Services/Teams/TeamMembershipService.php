<?php

namespace App\Services\Teams;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamMembershipService
{
    public function activeMembership(User $user): ?TeamMember
    {
        return TeamMember::query()
            ->with('team')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
    }

    public function hasActiveMembership(User $user): bool
    {
        return TeamMember::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    public function assertCanRequest(User $user, Team $team): void
    {
        $activeMembership = $this->activeMembership($user);

        if ($activeMembership !== null && (int) $activeMembership->team_id !== (int) $team->id) {
            throw ValidationException::withMessages([
                'team' => __('ui.team_single_membership'),
            ]);
        }
    }

    public function createOwnedTeam(User $user, array $attributes): Team
    {
        return DB::transaction(function () use ($user, $attributes): Team {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($this->hasActiveMembership($lockedUser)) {
                throw ValidationException::withMessages([
                    'team' => __('ui.team_single_membership'),
                ]);
            }

            $team = Team::query()->create([
                ...$attributes,
                'owner_id' => $lockedUser->id,
            ]);

            $team->members()->create([
                'user_id' => $lockedUser->id,
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            $this->withdrawPendingMemberships($lockedUser);

            return $team;
        });
    }

    public function acceptMembership(TeamMember $member, User $acceptedBy): ?TeamMember
    {
        return DB::transaction(function () use ($member, $acceptedBy): ?TeamMember {
            $lockedMember = TeamMember::query()->lockForUpdate()->findOrFail($member->id);
            $lockedUser = User::query()->lockForUpdate()->findOrFail($lockedMember->user_id);

            if ($lockedMember->status !== 'pending') {
                throw ValidationException::withMessages([
                    'team' => __('ui.team_request_not_pending'),
                ]);
            }

            $activeMembership = TeamMember::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', 'active')
                ->whereKeyNot($lockedMember->id)
                ->lockForUpdate()
                ->first();

            if ($activeMembership !== null) {
                $lockedMember->forceFill([
                    'status' => 'withdrawn',
                    'role' => 'member',
                    'accepted_by' => null,
                    'joined_at' => null,
                ])->save();

                return null;
            }

            $lockedMember->forceFill([
                'status' => 'active',
                'role' => 'member',
                'accepted_by' => $acceptedBy->id,
                'joined_at' => now(),
            ])->save();

            $this->withdrawPendingMemberships($lockedUser, $lockedMember->id);

            return $lockedMember->fresh(['team', 'user']);
        });
    }

    public function archiveTeam(Team $team): void
    {
        DB::transaction(function () use ($team): void {
            $lockedTeam = Team::query()->lockForUpdate()->findOrFail($team->id);

            TeamMember::query()
                ->where('team_id', $lockedTeam->id)
                ->delete();

            $lockedTeam->forceFill(['status' => 'archived'])->save();
            $lockedTeam->delete();
        });
    }

    private function withdrawPendingMemberships(User $user, ?int $exceptMembershipId = null): int
    {
        return TeamMember::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->when(
                $exceptMembershipId !== null,
                fn ($query) => $query->whereKeyNot($exceptMembershipId)
            )
            ->update([
                'status' => 'withdrawn',
                'active_user_id' => null,
                'accepted_by' => null,
                'joined_at' => null,
                'updated_at' => now(),
            ]);
    }
}
