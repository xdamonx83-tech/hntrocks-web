<?php

namespace App\Http\Controllers\TeamLFG;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamLfgApplication;
use App\Models\TeamLfgPost;
use App\Services\GamificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamLfgApplicationController extends Controller
{
    public function store(Request $request, TeamLfgPost $post, GamificationService $gamification): RedirectResponse
    {
        $post->loadMissing(['applications', 'team.members']);

        if (! $post->isOpen()) {
            return back()->withErrors(['application' => __('ui.team_lfg_error_not_open')]);
        }

        $rules = [
            'message' => ['nullable', 'string', 'max:900'],
        ];

        if ($post->isPlayerSeekingTeam()) {
            $rules['team_id'] = ['required', 'integer', 'exists:teams,id'];
        }

        $validated = $request->validate($rules);

        if ($post->isTeamSeekingPlayers()) {
            if (! $post->canApplyAsUser($request->user())) {
                return back()->withErrors(['application' => __('ui.team_lfg_error_cannot_apply')]);
            }

            $application = $post->applications()->create([
                'user_id' => $request->user()->id,
                'message' => $validated['message'] ?? null,
                'status' => 'pending',
            ]);

            $gamification->award($request->user(), 'team_lfg_application_sent', source: $application);

            return redirect()->route('team-lfg.show', $post)->with('status', __('ui.team_lfg_application_sent_status'));
        }

        if ($post->isOwner($request->user())) {
            return back()->withErrors(['application' => __('ui.team_lfg_error_own_invite')]);
        }

        $team = Team::with('members')->findOrFail($validated['team_id']);
        abort_unless($team->canManage($request->user()), 403);

        if ($team->isActiveMember($post->user)) {
            return back()->withErrors(['application' => __('ui.team_lfg_error_already_member')]);
        }

        if ($post->hasApplicationFromTeam($team->id)) {
            return back()->withErrors(['application' => __('ui.team_lfg_error_team_already_invited')]);
        }

        $application = $post->applications()->create([
            'user_id' => $request->user()->id,
            'team_id' => $team->id,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        $gamification->award($request->user(), 'team_lfg_application_sent', source: $application);

        return redirect()->route('team-lfg.show', $post)->with('status', __('ui.team_lfg_invitation_sent_status'));
    }

    public function accept(Request $request, TeamLfgPost $post, TeamLfgApplication $application, GamificationService $gamification): RedirectResponse
    {
        abort_unless($application->team_lfg_post_id === $post->id, 404);

        $post->loadMissing(['team.members', 'user']);
        $application->loadMissing(['user', 'team.members']);

        if ($post->isTeamSeekingPlayers()) {
            abort_unless($post->canManage($request->user()), 403);

            if (! $post->isOpen()) {
                return back()->withErrors(['application' => __('ui.team_lfg_error_no_slots')]);
            }

            if (! $post->team) {
                return back()->withErrors(['application' => __('ui.team_lfg_error_no_valid_team')]);
            }

            $post->team->members()->updateOrCreate(
                ['user_id' => $application->user_id],
                [
                    'role' => 'member',
                    'status' => 'active',
                    'message' => null,
                    'accepted_by' => $request->user()->id,
                    'joined_at' => now(),
                ]
            );

            $application->update([
                'status' => 'accepted',
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ]);

            $post->slots_filled = min((int) $post->slots_total, (int) $post->slots_filled + 1);
            if ($post->slots_filled >= $post->slots_total) {
                $post->status = 'filled';
            }
            $post->save();

            $gamification->award($application->user, 'team_lfg_application_accepted', source: $application);

            return back()->with('status', __('ui.team_lfg_application_accepted_added_status'));
        }

        abort_unless($post->isOwner($request->user()), 403);

        if (! $application->team) {
            return back()->withErrors(['application' => __('ui.team_lfg_error_invitation_no_valid_team')]);
        }

        $application->team->members()->updateOrCreate(
            ['user_id' => $post->user_id],
            [
                'role' => 'member',
                'status' => 'active',
                'message' => null,
                'accepted_by' => $application->user_id,
                'joined_at' => now(),
            ]
        );

        $application->update([
            'status' => 'accepted',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $post->update(['status' => 'filled']);
        $gamification->award($post->user, 'team_lfg_application_accepted', source: $application);

        return back()->with('status', __('ui.team_lfg_invitation_accepted_added_status'));
    }

    public function reject(Request $request, TeamLfgPost $post, TeamLfgApplication $application): RedirectResponse
    {
        abort_unless($application->team_lfg_post_id === $post->id, 404);

        if ($post->isTeamSeekingPlayers()) {
            abort_unless($post->canManage($request->user()), 403);
        } else {
            abort_unless($post->isOwner($request->user()), 403);
        }

        $application->update([
            'status' => 'rejected',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return back()->with('status', __('ui.team_lfg_request_rejected_status'));
    }
}
