<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupTeam;
use App\Models\CupTeamChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ApiCupTeamChatController extends Controller
{
    public function index(Request $request, Cup $cup, CupTeam $team): JsonResponse
    {
        $this->guardTeamAccess($request, $cup, $team);

        if (! Schema::hasTable('cup_team_chat_messages')) {
            return $this->notReadyResponse();
        }

        $messages = $team->chatMessages()
            ->with('user:id,name,username,avatar_path')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'messages' => $messages
                ->map(fn (CupTeamChatMessage $message): array => $this->messagePayload($message, $request))
                ->all(),
            'count' => $team->chatMessages()->count(),
        ]);
    }

    public function store(Request $request, Cup $cup, CupTeam $team): JsonResponse
    {
        $this->guardTeamAccess($request, $cup, $team);

        if (! Schema::hasTable('cup_team_chat_messages')) {
            return $this->notReadyResponse();
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1200'],
        ]);

        $message = $team->chatMessages()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ])->load('user:id,name,username,avatar_path');

        return response()->json([
            'message' => $this->messagePayload($message, $request),
            'count' => $team->chatMessages()->count(),
        ], 201);
    }

    private function guardTeamAccess(Request $request, Cup $cup, CupTeam $team): void
    {
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);

        $viewer = $request->user();

        abort_unless($viewer && ($team->hasMember($viewer) || $cup->canManage($viewer)), 403);
    }

    private function notReadyResponse(): JsonResponse
    {
        return response()->json([
            'message' => __('ui.cup_team_chat_not_ready'),
        ], 503);
    }

    private function messagePayload(CupTeamChatMessage $message, Request $request): array
    {
        $user = $message->user;

        return [
            'id' => (int) $message->id,
            'body' => (string) $message->body,
            'created_at' => $message->created_at?->toISOString(),
            'created_time' => $message->created_at?->format('H:i'),
            'is_own' => (int) $message->user_id === (int) $request->user()->id,
            'user' => $user ? [
                'id' => (int) $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatarUrl(),
            ] : null,
        ];
    }
}
