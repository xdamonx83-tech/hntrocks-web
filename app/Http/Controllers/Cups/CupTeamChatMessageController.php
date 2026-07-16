<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupTeam;
use App\Models\CupTeamChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CupTeamChatMessageController extends Controller
{
    private const HNT_CHAT_MESSAGE_VIEW = 'themes.hnt_preview.cups.partials.chat-message';

    public function index(Request $request, Cup $cup, CupTeam $team): JsonResponse
    {
        $this->guardTeamAccess($request, $cup, $team);

        if (! Schema::hasTable('cup_team_chat_messages')) {
            return response()->json([
                'html' => '',
                'count' => 0,
                'message' => __('ui.cup_team_chat_not_ready'),
            ], 503);
        }

        $messages = $team->chatMessages()
            ->with('user:id,name,username,avatar_path,level')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'html' => $messages->map(fn (CupTeamChatMessage $chatMessage): string => view(self::HNT_CHAT_MESSAGE_VIEW, [
                'chatMessage' => $chatMessage,
            ])->render())->implode(''),
            'count' => $team->chatMessages()->count(),
        ]);
    }

    public function store(Request $request, Cup $cup, CupTeam $team): RedirectResponse|JsonResponse
    {
        $this->guardTeamAccess($request, $cup, $team);

        if (! Schema::hasTable('cup_team_chat_messages')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('ui.cup_team_chat_not_ready')], 503);
            }

            return back()->withErrors(['body' => __('ui.cup_team_chat_not_ready')]);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1200'],
        ]);

        $message = $team->chatMessages()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ])->load('user');

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view(self::HNT_CHAT_MESSAGE_VIEW, ['chatMessage' => $message])->render(),
                'count' => $team->chatMessages()->count(),
                'message' => __('ui.cup_team_chat_message_saved'),
            ]);
        }

        return redirect(route('cups.teams.index', $cup).'#cup-team-chat')
            ->with('status', __('ui.cup_team_chat_message_saved'));
    }

    private function guardTeamAccess(Request $request, Cup $cup, CupTeam $team): void
    {
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);

        $viewer = $request->user();

        abort_unless($viewer && ($team->hasMember($viewer) || $cup->canManage($viewer)), 403);
    }
}
