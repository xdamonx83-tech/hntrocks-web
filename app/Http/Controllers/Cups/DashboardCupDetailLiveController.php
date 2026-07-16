<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Support\CupOrganizerAccess;
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
            $cup->visibility === 'public'
            || $cup->isOwner($request->user())
            || CupOrganizerAccess::canManage($cup, $request->user()),
            404
        );

        $canManage = CupOrganizerAccess::canManage($cup, $request->user());

        if ($request->boolean('review_capabilities')) {
            $isEnglish = app()->getLocale() === 'en';

            return response()->json([
                'can_manage' => $canManage,
                'verification_mode' => CupOrganizerAccess::verificationMode($cup),
                'can_ai_review' => CupOrganizerAccess::canUseAi($request->user()),
                'labels' => [
                    'kills' => 'Kills',
                    'bounty' => 'Bounty',
                    'points' => $isEnglish ? 'Points optional' : 'Punkte optional',
                    'note' => $isEnglish ? 'Review note' : 'Prüfnotiz',
                    'manual_score' => $isEnglish ? 'Score manually' : 'Manuell werten',
                    'ai_rescore' => $isEnglish ? 'Run AI review again' : 'KI erneut prüfen',
                    'reject' => $isEnglish ? 'Reject' : 'Ablehnen',
                ],
            ]);
        }

        $viewerTeam = $cup->teamFor($request->user());

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

        return response()->view('themes.hnt_preview.cups.show', [
            'cup' => $cup,
            'viewerTeam' => $viewerTeam,
            'canManage' => $canManage,
            'leaderboard' => $leaderboard,
            'activeSection' => $activeSection,
            'viewerTeamChatMessagesCount' => $viewerTeamChatMessagesCount,
        ]);
    }
}
