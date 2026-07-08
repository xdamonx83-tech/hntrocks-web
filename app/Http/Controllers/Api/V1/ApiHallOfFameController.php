<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CupLeaderboardEntryResource;
use App\Http\Resources\Api\CupResource;
use App\Models\Cup;
use App\Models\CupTeam;
use Illuminate\Http\JsonResponse;

class ApiHallOfFameController extends Controller
{
    public function index(): JsonResponse
    {
        $cups = Cup::query()
            ->visible()
            ->where('status', 'finished')
            ->with([
                'owner.profile',
                'teams' => function ($query): void {
                    $query->where('status', 'active')
                        ->with('owner.profile')
                        ->orderByDesc('points_total')
                        ->orderByDesc('bounty_tokens_total')
                        ->orderByDesc('kills_total')
                        ->orderByDesc('submissions_approved_count')
                        ->orderBy('id');
                },
            ])
            ->withCount(['activeTeams', 'submissions'])
            ->orderByRaw('COALESCE(ends_at, starts_at, created_at) desc')
            ->latest()
            ->get();

        $hallCups = $cups->map(function (Cup $cup): array {
            $leaderboard = $cup->teams
                ->sortBy([
                    ['points_total', 'desc'],
                    ['bounty_tokens_total', 'desc'],
                    ['kills_total', 'desc'],
                    ['submissions_approved_count', 'desc'],
                    ['id', 'asc'],
                ])
                ->values();

            return [
                'cup' => new CupResource($cup),
                'top_three' => CupLeaderboardEntryResource::collection($leaderboard->take(3)->values()),
                'top_five' => CupLeaderboardEntryResource::collection($leaderboard->take(5)->values()),
                'winner' => $leaderboard->first() ? new CupLeaderboardEntryResource($leaderboard->first()) : null,
                'stats' => [
                    'teams_count' => (int) ($cup->active_teams_count ?? 0),
                    'submissions_count' => (int) ($cup->submissions_count ?? 0),
                    'finalists_count' => $leaderboard->take(5)->count(),
                ],
            ];
        })->values();

        $winnerCount = $hallCups->sum(fn (array $entry): int => collect($entry['top_three']->resolve())->count());
        $finalistCount = $hallCups->sum(fn (array $entry): int => collect($entry['top_five']->resolve())->count());
        $totalPoints = $cups->sum(fn (Cup $cup): int => (int) $cup->teams->sum(fn (CupTeam $team): int => (int) $team->points_total));

        return response()->json([
            'data' => [
                'cups' => $hallCups,
                'stats' => [
                    'cups_count' => $cups->count(),
                    'winner_count' => $winnerCount,
                    'finalist_count' => $finalistCount,
                    'total_points' => $totalPoints,
                ],
            ],
        ]);
    }
}
