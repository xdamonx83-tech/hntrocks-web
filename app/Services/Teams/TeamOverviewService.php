<?php

namespace App\Services\Teams;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;

class TeamOverviewService
{
    public function forViewer(User $viewer): array
    {
        $visibleTeams = Team::query()
            ->where('status', 'active')
            ->where(function ($query) use ($viewer): void {
                $query->where('visibility', 'public')
                    ->orWhere('owner_id', $viewer->id)
                    ->orWhereHas('members', function ($memberQuery) use ($viewer): void {
                        $memberQuery
                            ->where('user_id', $viewer->id)
                            ->where('status', 'active');
                    });
            });

        $visibleTeamIds = (clone $visibleTeams)->select('teams.id');

        return [
            'all' => (clone $visibleTeams)->count(),
            'mine' => TeamMember::query()
                ->where('user_id', $viewer->id)
                ->where('status', 'active')
                ->whereHas('team', fn ($teamQuery) => $teamQuery->where('status', 'active'))
                ->count(),
            'recruiting' => (clone $visibleTeams)
                ->where('recruitment_status', 'open')
                ->count(),
            'new_this_week' => (clone $visibleTeams)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'members' => TeamMember::query()
                ->where('status', 'active')
                ->whereIn('team_id', $visibleTeamIds)
                ->count(),
        ];
    }
}
