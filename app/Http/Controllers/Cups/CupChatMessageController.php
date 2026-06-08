<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupChatMessage;
use App\Support\HntTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CupChatMessageController extends Controller
{
    public function index(Request $request, Cup $cup): JsonResponse
    {
        $viewer = $request->user();

        abort_unless(
            $cup->visibility === 'public' || ($viewer && ($cup->isOwner($viewer) || $cup->canManage($viewer))),
            404
        );

        if (! Schema::hasTable('cup_chat_messages')) {
            return response()->json([
                'html' => '',
                'count' => 0,
                'message' => __('ui.cup_chat_not_ready'),
            ], 503);
        }

        $chatMessageView = HntTheme::resolve('cups.partials.chat-message');

        if (! view()->exists($chatMessageView)) {
            $chatMessageView = 'themes.socialite.cups.partials.chat-message';
        }

        $messages = $cup->chatMessages()
            ->with('user:id,name,username,avatar_path,level')
            ->limit(8)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'html' => $messages->map(fn (CupChatMessage $chatMessage): string => view($chatMessageView, [
                'chatMessage' => $chatMessage,
            ])->render())->implode(''),
            'count' => $cup->chatMessages()->count(),
        ]);
    }

    public function store(Request $request, Cup $cup): RedirectResponse|JsonResponse
    {
        abort_unless($cup->visibility === 'public' || $cup->isOwner($request->user()) || $cup->canManage($request->user()), 404);

        if (! Schema::hasTable('cup_chat_messages')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('ui.cup_chat_not_ready')], 503);
            }

            return back()->withErrors(['body' => __('ui.cup_chat_not_ready')]);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1200'],
        ]);

        $message = CupChatMessage::query()->create([
            'cup_id' => $cup->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ])->load('user');

        if ($request->expectsJson()) {
            $chatMessageView = HntTheme::resolve('cups.partials.chat-message');

            if (! view()->exists($chatMessageView)) {
                $chatMessageView = 'themes.socialite.cups.partials.chat-message';
            }

            return response()->json([
                'html' => view($chatMessageView, ['chatMessage' => $message])->render(),
                'count' => $cup->chatMessages()->count(),
                'message' => __('ui.cup_chat_message_saved'),
            ]);
        }

        return redirect(route('cups.show', $cup).'#cup-chat')
            ->with('status', __('ui.cup_chat_message_saved'));
    }
}
