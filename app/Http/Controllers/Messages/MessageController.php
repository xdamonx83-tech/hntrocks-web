<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\MessagePushService;
use App\Support\HntTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $activeMessageType = $this->normalizeMessageType($request->query('type'));

        return view(HntTheme::resolve('messages.index'), [
            'conversations' => $this->conversationList($request->user(), $activeMessageType),
            'selectedConversation' => null,
            'messages' => collect(),
            'recipients' => $this->recipientList($request->user()),
            'activeMessageType' => $activeMessageType,
            'messageTypeCounts' => $this->messageTypeCounts($request->user()),
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        abort_unless($conversation->isParticipant($request->user()), 403);

        $activeMessageType = $this->conversationCategory($conversation);

        $conversation->markReadFor($request->user());
        $conversation->load(['users.profile', 'messages.user.profile']);

        return view(HntTheme::resolve('messages.index'), [
            'conversations' => $this->conversationList($request->user(), $activeMessageType),
            'selectedConversation' => $conversation,
            'messages' => $this->visibleMessagesQuery($conversation, $request->user())->with('user.profile')->oldest()->get(),
            'recipients' => $this->recipientList($request->user()),
            'activeMessageType' => $activeMessageType,
            'messageTypeCounts' => $this->messageTypeCounts($request->user()),
        ]);
    }

    public function withUser(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $sender = $request->user();

        if ((int) $user->id === (int) $sender->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('ui.message_cannot_self'),
                ], 422);
            }

            return redirect()->route('messages.index')->withErrors(['recipient_id' => __('ui.message_cannot_self')]);
        }

        if ($sender->hasBlocked($user) || $user->hasBlocked($sender)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('ui.message_blocked_unavailable')], 403);
            }

            return redirect()->route('messages.index')->withErrors(['recipient_id' => __('ui.message_blocked_unavailable')]);
        }

        $conversation = $this->privateConversationFor($sender, $user);

        if ($request->expectsJson()) {
            $conversation->markReadFor($sender);
            $conversation->setRelation('users', collect([$sender, $user]));

            $messages = $this->visibleMessagesQuery($conversation, $sender)
                ->with('user.profile')
                ->latest('created_at')
                ->limit(30)
                ->get()
                ->reverse()
                ->values();

            return response()->json([
                'message' => __('ui.message_drawer_opened'),
                'conversation_id' => $conversation->id,
                'panel_key' => 'conversation-'.$conversation->id,
                'panel_html' => view('partials.chat-dock-conversation-panel', [
                    'hhChatConversation' => $conversation,
                    'hhChatUser' => $sender,
                    'hhChatPartner' => $user,
                    'hhChatMessages' => $messages,
                ])->render(),
                'show_url' => route('messages.show', $conversation),
            ]);
        }

        $conversation->markReadFor($sender);

        return redirect()->route('messages.show', $conversation);
    }


    public function chatTab(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->isParticipant($request->user()), 403);
        abort_unless($conversation->type === 'private', 404);

        $conversation->markReadFor($request->user());
        $conversation->load(['users.profile']);

        $messages = $this->visibleMessagesQuery($conversation, $request->user())
            ->with('user.profile')
            ->latest('created_at')
            ->limit(60)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'message' => __('ui.message_chat_tab_opened'),
            'conversation_id' => $conversation->id,
            'html' => view(HntTheme::resolve('messages.partials.chat-tab'), [
                'conversation' => $conversation,
                'viewer' => $request->user(),
                'messages' => $messages,
            ])->render(),
            'unread_messages' => $request->user()->unreadMessagesCount(),
        ]);
    }


    public function chatTabMessages(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->isParticipant($request->user()), 403);
        abort_unless($conversation->type === 'private', 404);

        $conversation->markReadFor($request->user());
        $conversation->load(['users.profile']);

        $messages = $this->visibleMessagesQuery($conversation, $request->user())
            ->with('user.profile')
            ->latest('created_at')
            ->limit(60)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'conversation_id' => $conversation->id,
            'html' => view('themes.hnt_preview.messages.partials.chat-tab-messages', [
                'conversation' => $conversation,
                'viewer' => $request->user(),
                'messages' => $messages,
                'conversationTitle' => $conversation->displayTitleFor($request->user()),
            ])->render(),
            'last_message_id' => optional($messages->last())->id,
            'unread_messages' => $request->user()->unreadMessagesCount(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function start(Request $request, MessagePushService $messagePush): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $sender = $request->user();
        $recipient = User::findOrFail($validated['recipient_id']);

        if ((int) $recipient->id === (int) $sender->id) {
            return back()->withErrors(['recipient_id' => __('ui.message_cannot_self')]);
        }

        if ($sender->hasBlocked($recipient) || $recipient->hasBlocked($sender)) {
            return back()->withErrors(['recipient_id' => __('ui.message_blocked_unavailable')]);
        }

        $conversation = $this->privateConversationFor($sender, $recipient);
        $message = $conversation->messages()->create([
            'user_id' => $sender->id,
            'type' => 'user',
            'body' => $validated['body'],
        ]);

        $conversation->touch();
        $conversation->markReadFor($sender);
        $messagePush->sendForMessage($message);

        return redirect()->route('messages.show', $conversation)->with('status', __('ui.message_sent'));
    }

    public function store(Request $request, Conversation $conversation, MessagePushService $messagePush): RedirectResponse|JsonResponse
    {
        abort_unless($conversation->isParticipant($request->user()), 403);

        if (! $this->canMessage($conversation, $request->user())) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('ui.message_blocked_unavailable')], 403);
            }

            return back()->withErrors(['body' => __('ui.message_blocked_unavailable')]);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'type' => 'user',
            'body' => $validated['body'],
        ]);

        $conversation->touch();
        $conversation->markReadFor($request->user());
        $messagePush->sendForMessage($message);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('ui.message_sent'),
                'conversation_id' => $conversation->id,
                'conversation_type' => $conversation->type,
                'body' => $message->body,
                'created_at_label' => $message->created_at?->format('H:i'),
                'unread_messages' => $request->user()->unreadMessagesCount(),
            ]);
        }

        return redirect()->route('messages.show', $conversation)->with('status', __('ui.message_sent'));
    }

    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->isParticipant($request->user()), 403);

        $conversation->markReadFor($request->user());

        return response()->json([
            'conversation_id' => $conversation->id,
            'unread_messages' => $request->user()->unreadMessagesCount(),
        ]);
    }

    private function conversationList(User $user, string $messageType = 'private')
    {
        return Conversation::query()
            ->forUser($user)
            ->whereIn('type', $this->conversationTypesFor($messageType))
            ->with(['users.profile', 'latestMessage.user'])
            ->latest('updated_at')
            ->paginate(12)
            ->appends(['type' => $messageType]);
    }

    private function recipientList(User $user)
    {
        return User::query()
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'username', 'avatar_path']);
    }

    private function privateConversationFor(User $first, User $second): Conversation
    {
        $conversationId = DB::table('conversation_participants as participant_self')
            ->join('conversation_participants as participant_other', 'participant_self.conversation_id', '=', 'participant_other.conversation_id')
            ->join('conversations', 'conversations.id', '=', 'participant_self.conversation_id')
            ->where('conversations.type', 'private')
            ->where('participant_self.user_id', $first->id)
            ->where('participant_other.user_id', $second->id)
            ->orderByDesc('conversations.updated_at')
            ->value('conversations.id');

        if ($conversationId) {
            return Conversation::findOrFail($conversationId);
        }

        $conversation = Conversation::create([
            'type' => 'private',
            'created_by' => $first->id,
        ]);

        $conversation->users()->attach([
            $first->id => ['last_read_at' => now()],
            $second->id => ['last_read_at' => null],
        ]);

        return $conversation;
    }


    private function visibleMessagesQuery(Conversation $conversation, User $user)
    {
        $participant = $conversation->users()->where('users.id', $user->id)->first()?->pivot;

        return $conversation->messages()
            ->when($participant?->cleared_at, fn ($query, $clearedAt) => $query->where('created_at', '>', $clearedAt));
    }

    private function canMessage(Conversation $conversation, User $viewer): bool
    {
        $partner = $conversation->otherParticipant($viewer);
        if (! $partner) {
            return true;
        }

        return ! $viewer->hasBlocked($partner) && ! $partner->hasBlocked($viewer);
    }

    private function normalizeMessageType(?string $messageType): string
    {
        return $messageType === 'lfg' ? 'lfg' : 'private';
    }

    private function conversationCategory(Conversation $conversation): string
    {
        return in_array($conversation->type, ['lfg', 'team_lfg'], true) ? 'lfg' : 'private';
    }

    private function conversationTypesFor(string $messageType): array
    {
        return $messageType === 'lfg' ? ['lfg', 'team_lfg'] : ['private'];
    }

    private function messageTypeCounts(User $user): array
    {
        return [
            'private' => $this->conversationTypeCount($user, ['private']),
            'lfg' => $this->conversationTypeCount($user, ['lfg', 'team_lfg']),
        ];
    }

    private function conversationTypeCount(User $user, array $types): int
    {
        return Conversation::query()
            ->forUser($user)
            ->whereIn('type', $types)
            ->count();
    }
}
