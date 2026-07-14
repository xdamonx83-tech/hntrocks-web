<?php

namespace App\Services\Cups;

use App\Models\Cup;
use App\Models\CupSubmission;
use App\Models\CupTeam;
use App\Models\CupTeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CupOverviewService
{
    public function forViewer(?User $viewer): array
    {
        $featuredCup = $this->visibleCups($viewer)
            ->withCount(['activeTeams', 'submissions', 'pendingSubmissions'])
            ->whereIn('status', ['active', 'planned'])
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderByRaw('coalesce(starts_at, registration_closes_at, created_at) asc')
            ->first();

        $upcomingCups = $this->visibleCups($viewer)
            ->withCount(['activeTeams', 'submissions'])
            ->where('status', 'planned')
            ->when($featuredCup, fn (Builder $query) => $query->whereKeyNot($featuredCup->getKey()))
            ->orderByRaw('coalesce(starts_at, registration_closes_at, created_at) asc')
            ->limit(2)
            ->get();

        $viewerTeam = $viewer
            ? CupTeam::query()
                ->with([
                    'cup',
                    'owner:id,name,username,avatar_path',
                    'members.user:id,name,username,avatar_path',
                ])
                ->withCount('submissions')
                ->where('status', 'active')
                ->whereHas('members', fn (Builder $query) => $query
                    ->where('user_id', $viewer->id)
                    ->where('status', 'active'))
                ->whereHas('cup', function (Builder $query) use ($viewer): void {
                    $query->whereIn('status', ['active', 'planned']);
                    $this->constrainVisibility($query, $viewer);
                })
                ->latest('id')
                ->first()
            : null;

        $topTeams = CupTeam::query()
            ->with([
                'cup:id,title,slug,status,visibility',
                'owner:id,name,username,avatar_path',
            ])
            ->where('status', 'active')
            ->whereHas('cup', function (Builder $query) use ($viewer): void {
                $query->where('status', 'finished');
                $this->constrainVisibility($query, $viewer);
            })
            ->orderByDesc('points_total')
            ->orderByDesc('bounty_tokens_total')
            ->orderByDesc('kills_total')
            ->orderByDesc('submissions_approved_count')
            ->limit(3)
            ->get();

        return [
            'stats' => [
                'active' => $this->visibleCups($viewer)->where('status', 'active')->count(),
                'planned' => $this->visibleCups($viewer)->where('status', 'planned')->count(),
                'finished' => $this->visibleCups($viewer)->where('status', 'finished')->count(),
                'teams' => CupTeam::query()
                    ->where('status', 'active')
                    ->whereHas('cup', fn (Builder $query) => $this->constrainVisibility($query, $viewer))
                    ->count(),
                'hunters' => CupTeamMember::query()
                    ->where('status', 'active')
                    ->whereHas('cupTeam', function (Builder $query) use ($viewer): void {
                        $query->where('status', 'active')
                            ->whereHas('cup', fn (Builder $cupQuery) => $this->constrainVisibility($cupQuery, $viewer));
                    })
                    ->count(),
                'scores' => CupSubmission::query()
                    ->whereIn('status', CupSubmission::scoredStatuses())
                    ->whereHas('cup', fn (Builder $query) => $this->constrainVisibility($query, $viewer))
                    ->count(),
                'pending' => CupSubmission::query()
                    ->whereIn('status', ['pending', 'review_required'])
                    ->whereHas('cup', fn (Builder $query) => $this->constrainVisibility($query, $viewer))
                    ->count(),
            ],
            'featuredCup' => $featuredCup,
            'upcomingCups' => $upcomingCups,
            'viewerTeam' => $viewerTeam,
            'topTeams' => $topTeams,
        ];
    }

    private function visibleCups(?User $viewer): Builder
    {
        $query = Cup::query();
        $this->constrainVisibility($query, $viewer);

        return $query;
    }

    private function constrainVisibility(Builder $query, ?User $viewer): void
    {
        if (! $viewer?->isAdmin()) {
            $query->where('visibility', 'public');
        }
    }
}
