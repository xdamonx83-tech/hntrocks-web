<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupRandomizerDraw;
use App\Models\CupTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CupRandomizerDrawController extends Controller
{
    public function store(Request $request, Cup $cup): RedirectResponse
    {
        abort_unless($cup->canManage($request->user()), 403);

        if (! Schema::hasTable('cup_randomizer_draws')) {
            return back()->withErrors(['randomizer' => __('ui.cup_randomizer_error_migration_missing')]);
        }

        $validated = $request->validate([
            'prize_label' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:1200'],
        ]);

        $eligibleTeams = $cup->randomizerEligibleTeams();

        if ($eligibleTeams->isEmpty()) {
            return back()->withErrors(['randomizer' => __('ui.cup_randomizer_no_eligible_teams')]);
        }

        $draw = DB::transaction(function () use ($cup, $request, $validated, $eligibleTeams): CupRandomizerDraw {
            $freshEligibleTeams = CupTeam::query()
                ->whereKey($eligibleTeams->pluck('id')->all())
                ->with(['owner:id,name,username,avatar_path', 'members.user:id,name,username,avatar_path'])
                ->get()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            if ($freshEligibleTeams->isEmpty()) {
                abort(422, __('ui.cup_randomizer_no_eligible_teams'));
            }

            $winner = $freshEligibleTeams[random_int(0, $freshEligibleTeams->count() - 1)];
            $members = $winner->members
                ->where('status', 'active')
                ->map(fn ($member): array => [
                    'id' => $member->user?->id,
                    'name' => $member->user?->name,
                    'username' => $member->user?->username,
                    'role' => $member->role,
                ])
                ->values()
                ->all();

            return CupRandomizerDraw::create([
                'cup_id' => $cup->id,
                'cup_team_id' => $winner->id,
                'drawn_by' => $request->user()?->id,
                'title' => __('ui.cup_randomizer_default_title'),
                'prize_label' => trim((string) ($validated['prize_label'] ?? '')) ?: __('ui.cup_randomizer_default_prize'),
                'eligible_team_ids' => $freshEligibleTeams->pluck('id')->values()->all(),
                'eligible_team_count' => $freshEligibleTeams->count(),
                'team_snapshot' => [
                    'id' => $winner->id,
                    'name' => $winner->displayName(),
                    'points_total' => $winner->points_total,
                    'kills_total' => $winner->kills_total,
                    'submissions_approved_count' => $winner->submissions_approved_count,
                    'captain' => [
                        'id' => $winner->owner?->id,
                        'name' => $winner->owner?->name,
                        'username' => $winner->owner?->username,
                    ],
                    'members' => $members,
                ],
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            ]);
        });

        return redirect()
            ->route('cups.show.section', [$cup, 'submissions'])
            ->with('status', __('ui.cup_randomizer_drawn_status', ['team' => $draw->winnerName()]));
    }
}
