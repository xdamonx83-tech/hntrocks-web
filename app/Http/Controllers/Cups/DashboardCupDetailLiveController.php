<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class DashboardCupDetailLiveController extends Controller
{
    public function __invoke(Request $request, Cup $cup, string $section = 'overview'): Response
    {
        $allowedSections = ['overview', 'rules', 'prizes', 'leaderboard', 'participants', 'submit', 'submissions'];
        $activeSection = in_array($section, $allowedSections, true) ? $section : 'overview';

        if ($activeSection === 'leaderboard') {
            $activeSection = 'participants';
        }

        $cup->load([
            'owner:id,name,username,avatar_path',
            'teams.owner:id,name,username,avatar_path',
            'teams.members.user:id,name,username,avatar_path',
            'submissions.team',
            'submissions.submitter:id,name,username,avatar_path',
            'submissions.screenshot',
        ]);

        abort_unless(
            $cup->visibility === 'public' || $cup->isOwner($request->user()) || $cup->canManage($request->user()),
            404
        );

        $viewerTeam = $cup->teamFor($request->user());
        $canManage = $cup->canManage($request->user());

        $viewerTeamChatMessagesCount = 0;
        if ($viewerTeam && ! $cup->isSoloLeaderboard() && Schema::hasTable('cup_team_chat_messages')) {
            $viewerTeamChatMessagesCount = $viewerTeam->chatMessages()->count();
        }

        $leaderboard = $cup->teams
            ->where('status', 'active')
            ->sortBy([
                ['points_total', 'desc'],
                ['bounty_tokens_total', 'desc'],
                ['kills_total', 'desc'],
                ['submissions_approved_count', 'desc'],
                ['id', 'asc'],
            ])
            ->values();

        return response()->view('themes.hnt_preview.cups.detail-live', [
            'cup' => $cup,
            'viewerTeam' => $viewerTeam,
            'canManage' => $canManage,
            'leaderboard' => $leaderboard,
            'activeSection' => $activeSection,
            'viewerTeamChatMessagesCount' => $viewerTeamChatMessagesCount,
        ]);
    }
}
