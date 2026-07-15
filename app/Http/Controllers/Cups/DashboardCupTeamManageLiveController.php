<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupTeamFinderPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class DashboardCupTeamManageLiveController extends Controller
{
    public function __invoke(Request $request, Cup $cup): Response
    {
        abort_if($cup->isSoloLeaderboard(), 404);

        $viewer = $request->user();
        abort_unless($viewer, 401);

        $cup->loadMissing('owner:id,name,username,avatar_path');
        $team = $cup->teamFor($viewer);
        abort_unless($team, 404);

        $team->load([
            'cup',
            'owner:id,name,username,avatar_path,level,last_seen_at',
            'members.user:id,name,username,avatar_path,level,last_seen_at',
            'members.user.profile',
            'members.user.crownWallet',
            'submissions.submitter:id,name,username,avatar_path',
            'submissions.screenshot',
        ]);

        $chatMessages = collect();
        $chatMessagesCount = 0;

        if (Schema::hasTable('cup_team_chat_messages')) {
            $chatMessages = $team->chatMessages()
                ->with('user:id,name,username,avatar_path,level')
                ->limit(30)
                ->get()
                ->reverse()
                ->values();
            $chatMessagesCount = $team->chatMessages()->count();
        }

        $finderPosts = collect();
        $teamFinderEnabled = Schema::hasTable('cup_team_finder_posts')
            && Schema::hasColumn('cup_teams', 'is_recruiting');

        if ($teamFinderEnabled) {
            $finderPosts = CupTeamFinderPost::query()
                ->where('cup_id', $cup->id)
                ->where('status', 'active')
                ->where('user_id', '!=', $viewer->id)
                ->with(['user:id,name,username,avatar_path,level', 'user.profile'])
                ->latest()
                ->limit(12)
                ->get();
        }

        return response()->view('themes.hnt_preview.cups.team-manage-live', [
            'cup' => $cup,
            'team' => $team,
            'viewer' => $viewer,
            'canManageTeam' => $team->canManage($viewer),
            'isCaptain' => $team->isCaptain($viewer),
            'chatMessages' => $chatMessages,
            'chatMessagesCount' => $chatMessagesCount,
            'finderPosts' => $finderPosts,
            'teamFinderEnabled' => $teamFinderEnabled,
        ]);
    }
}
