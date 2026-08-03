<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\GamificationService;
use App\Services\NotificationService;
use App\Services\Teams\TeamMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamMembershipController extends Controller
{
    public function __construct(private readonly TeamMembershipService $teamMemberships)
    {
    }

    public function join(Request $request, Team $team, NotificationService $notifications, GamificationService $gamification): RedirectResponse
    {
        $team->loadMissing(['members', 'owner']);

        if ($team->recruitment_status !== 'open') {
            return back()->withErrors(['team' => __('ui.team_not_recruiting')]);
        }

        if ($team->isActiveMember($request->user())) {
            return back()->with('status', __('ui.team_already_member'));
        }

        $this->teamMemberships->assertCanRequest($request->user(), $team);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $membership = $team->members()->where('user_id', $request->user()->id)->first();

        if ($membership && $membership->status === 'pending') {
            return back()->with('status', __('ui.team_join_pending'));
        }

        if ($membership) {
            $membership->update([
                'role' => 'member',
                'status' => 'pending',
                'message' => $validated['message'] ?? null,
                'accepted_by' => null,
                'joined_at' => null,
            ]);
        } else {
            $membership = $team->members()->create([
                'user_id' => $request->user()->id,
                'role' => 'member',
                'status' => 'pending',
                'message' => $validated['message'] ?? null,
            ]);
        }

        $gamification->award($request->user(), 'team_join_requested', source: $membership);
        $notifications->send($team->owner, $request->user(), 'team_join_request', __('ui.team_join_request_title'), __('ui.team_join_request_body', ['name' => $request->user()->name]), route('teams.show', $team));

        return back()->with('status', __('ui.team_join_sent'));
    }

    public function leave(Request $request, Team $team): RedirectResponse
    {
        $team->loadMissing('members');
        $membership = $team->membershipFor($request->user());

        if (! $membership) {
            return back()->with('status', __('ui.team_not_member'));
        }

        if ($membership->role === 'owner') {
            return back()->withErrors(['team' => __('ui.team_owner_leave_blocked')]);
        }

        $membership->delete();

        return redirect()->route('teams.index')->with('status', __('ui.team_left'));
    }

    public function promote(Request $request, Team $team, TeamMember $member): RedirectResponse
    {
        $team->loadMissing('members');

        abort_unless($team->isOwner($request->user()), 403);
        abort_unless((int) $member->team_id === (int) $team->id, 404);

        if ($member->status !== 'active') {
            return back()->withErrors(['team' => __('ui.team_member_required_officer')]);
        }

        if ($member->role === 'owner') {
            return back()->withErrors(['team' => __('ui.team_owner_top_role')]);
        }

        if ($member->role === 'officer') {
            return back()->with('status', __('ui.team_already_officer'));
        }

        $member->update([
            'role' => 'officer',
            'accepted_by' => $request->user()->id,
        ]);

        return back()->with('status', __('ui.team_promoted_officer'));
    }

    public function demote(Request $request, Team $team, TeamMember $member): RedirectResponse
    {
        $team->loadMissing('members');

        abort_unless($team->isOwner($request->user()), 403);
        abort_unless((int) $member->team_id === (int) $team->id, 404);

        if ($member->status !== 'active') {
            return back()->withErrors(['team' => __('ui.team_member_required_manage')]);
        }

        if ($member->role === 'owner') {
            return back()->withErrors(['team' => __('ui.team_owner_cannot_demote')]);
        }

        if ($member->role !== 'officer') {
            return back()->with('status', __('ui.team_not_officer'));
        }

        $member->update([
            'role' => 'member',
            'accepted_by' => $request->user()->id,
        ]);

        return back()->with('status', __('ui.team_demoted_member'));
    }

    public function accept(Request $request, Team $team, TeamMember $member, NotificationService $notifications, GamificationService $gamification): RedirectResponse
    {
        $team->loadMissing('members');
        abort_unless($team->canManage($request->user()), 403);
        abort_unless($member->team_id === $team->id, 404);

        $acceptedMember = $this->teamMemberships->acceptMembership($member, $request->user());

        if ($acceptedMember === null) {
            return back()->withErrors(['team' => __('ui.team_member_joined_elsewhere')]);
        }

        $member = $acceptedMember;
        $member->loadMissing('user');
        $gamification->award($member->user, 'team_joined', source: $member);
        $notifications->send($member->user, $request->user(), 'team_join_accepted', __('ui.team_join_accepted_title'), __('ui.team_join_accepted_body', ['team' => $team->name]), route('teams.show', $team));

        return back()->with('status', __('ui.team_join_accepted_status'));
    }

    public function reject(Request $request, Team $team, TeamMember $member, NotificationService $notifications): RedirectResponse
    {
        $team->loadMissing('members');
        abort_unless($team->canManage($request->user()), 403);
        abort_unless($member->team_id === $team->id, 404);

        $member->update([
            'status' => 'declined',
            'accepted_by' => $request->user()->id,
        ]);

        $member->loadMissing('user');
        $notifications->send($member->user, $request->user(), 'team_join_rejected', __('ui.team_join_rejected_title'), __('ui.team_join_rejected_body', ['team' => $team->name]), route('teams.show', $team));

        return back()->with('status', __('ui.team_join_rejected_status'));
    }
}
