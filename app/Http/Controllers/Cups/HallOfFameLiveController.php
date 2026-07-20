<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use Illuminate\Http\Response;

class HallOfFameLiveController extends Controller
{
    public function __invoke(): Response
    {
        $cups = Cup::query()
            ->visible()
            ->where('status', 'finished')
            ->with([
                'owner:id,name,username,avatar_path,level',
                'teams' => function ($query): void {
                    $query->where('status', 'active')
                        ->with([
                            'owner:id,name,username,avatar_path,level',
                            'members.user:id,name,username,avatar_path,level',
                        ])
                        ->orderByDesc('points_total')
                        ->orderByDesc('bounty_tokens_total')
                        ->orderByDesc('kills_total')
                        ->orderByDesc('submissions_approved_count')
                        ->orderBy('id');
                },
                'submissions.submitter:id,name,username,avatar_path,level',
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
                'cup' => $cup,
                'topThree' => $leaderboard->take(3)->values(),
                'topFive' => $leaderboard->take(5)->values(),
            ];
        });

        $winnerCount = $hallCups->sum(fn (array $entry): int => $entry['topThree']->count());
        $finalistCount = $hallCups->sum(fn (array $entry): int => $entry['topFive']->count());

        return response()
            ->view('themes.hnt_preview.cups.hall-of-fame', compact('hallCups', 'winnerCount', 'finalistCount'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
