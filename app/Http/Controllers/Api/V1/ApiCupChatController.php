<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ApiCupChatController extends Controller
{
    public function index(Request $request, Cup $cup): JsonResponse
    {
        if (! Schema::hasTable('cup_chat_messages')) {
            return $this->notReadyResponse();
        }

        $messages = $cup->chatMessages()
            ->with(['cup:id,slug,title', 'user:id,name,username,avatar_path'])
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'messages' => $messages
                ->map(fn (CupChatMessage $message): array => $this->messagePayload($message, $request))
                ->all(),
            'count' => $cup->chatMessages()->count(),
            'can_write' => true,
        ]);
    }

    public function store(Request $request, Cup $cup): JsonResponse
    {
        if (! Schema::hasTable('cup_chat_messages')) {
            return $this->notReadyResponse();
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:1200'],
        ]);

        $message = $cup->chatMessages()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ])->load(['cup:id,slug,title', 'user:id,name,username,avatar_path']);

        return response()->json([
            'message' => $this->messagePayload($message, $request),
            'count' => $cup->chatMessages()->count(),
            'can_write' => true,
        ], 201);
    }

    private function notReadyResponse(): JsonResponse
    {
        return response()->json([
            'message' => __('ui.cup_chat_not_ready'),
        ], 503);
    }

    private function messagePayload(CupChatMessage $message, Request $request): array
    {
        $user = $message->user;
        $cup = $message->cup;

        return [
            'id' => (int) $message->id,
            'body' => (string) $message->body,
            'created_at' => $message->created_at?->toISOString(),
            'created_time' => $message->created_at?->format('H:i'),
            'is_own' => (int) $message->user_id === (int) $request->user()->id,
            'can_write' => true,
            'cup' => $cup ? [
                'id' => (int) $cup->id,
                'slug' => $cup->slug,
                'title' => $cup->title,
            ] : null,
            'user' => $user ? [
                'id' => (int) $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatarUrl(),
            ] : null,
        ];
    }
}
