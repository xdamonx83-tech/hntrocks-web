<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\UserBlockService;
use App\Services\UserPrivacyService;
use App\Models\UserBlock;
use App\Services\MessageBroadcastService;
use App\Services\MessagePushService;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiMessageController extends Controller
{
    public function __construct(
        private readonly UserBlockService $blocks,
        private readonly UserPrivacyService $privacy,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $this->normalizeType($request->query('type'));

        $query = Conversation::query()
            ->forUser($user)
            ->whereIn('type', $this->typesFor($type));

        $conversations = $this->blocks->applyToConversationQuery($query, $user)
            ->with(['users.profile', 'users.privacySettings', 'latestMessage.user.profile', 'latestMessage.user.privacySettings'])
            ->latest('updated_at')
            ->paginate(30);

        return response()->json([
            'data' => $conversations->getCollection()
                ->map(fn (Conversation $conversation): array => $this->conversationPayload($request, $conversation, $user))
                ->values(),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
                'last_page' => $conversations->lastPage(),
                'has_more' => $conversations->hasMorePages(),
            ],
            'counts' => $this->counts($user),
            'type' => $type,
        ]);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->isParticipant($user), 403);

        $conversation->markReadFor($user);
        $conversation->loadMissing(['users.profile', 'users.privacySettings', 'latestMessage.user.profile', 'latestMessage.user.privacySettings']);

        $messages = $this->visibleMessagesQuery($conversation, $user)
            ->with(['user.profile', 'user.privacySettings', 'attachments'])
            ->oldest('created_at')
            ->limit(120)
            ->get()
            ->map(fn (Message $message): array => $this->messagePayload($request, $message, $user, $conversation))
            ->values();

        return response()->json([
            'data' => [
                'conversation' => $this->conversationPayload($request, $conversation, $user),
                'messages' => $messages,
            ],
            'counts' => $this->counts($user),
        ]);
    }

    public function withUser(Request $request, User $user): JsonResponse
    {
        $sender = $request->user();

        abort_unless($user->status === 'active', 404);

        if ((int) $user->id === (int) $sender->id) {
            return response()->json([
                'message' => __('ui.message_cannot_self'),
            ], 422);
        }

        if ($sender->hasBlocked($user) || $user->hasBlocked($sender)) {
            abort(403, __('ui.message_blocked_unavailable'));
        }

        if (! $this->privacy->canMessage($sender, $user)) {
            abort(403, __('settings.privacy_messages_denied'));
        }

        $conversation = $this->privateConversationFor($sender, $user);
        $conversation->markReadFor($sender);
        $conversation->loadMissing(['users.profile', 'users.privacySettings', 'latestMessage.user.profile', 'latestMessage.user.privacySettings']);

        $messages = $this->visibleMessagesQuery($conversation, $sender)
            ->with(['user.profile', 'user.privacySettings', 'attachments'])
            ->oldest('created_at')
            ->limit(120)
            ->get()
            ->map(fn (Message $message): array => $this->messagePayload($request, $message, $sender, $conversation))
            ->values();

        return response()->json([
            'message' => __('ui.message_drawer_opened'),
            'conversation_id' => $conversation->id,
            'data' => [
                'conversation' => $this->conversationPayload($request, $conversation, $sender),
                'messages' => $messages,
            ],
            'counts' => $this->counts($sender),
        ]);
    }

    public function store(Request $request, Conversation $conversation, MessagePushService $messagePush, MediaService $mediaService, MessageBroadcastService $messageBroadcast): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->isParticipant($user), 403);

        if (! $this->canMessage($conversation, $user)) {
            abort(403, $this->messageDeniedText($conversation, $user));
        }

        $validator = Validator::make($request->all(), [
            'body' => ['nullable', 'string', 'max:3000', 'required_without:attachments.0'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm', 'max:51200'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'type' => 'user',
            'body' => trim((string) ($validated['body'] ?? '')),
        ]);

        $files = $request->file('attachments', []);
        if ($files && ! is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $mediaService->store($file, $user, 'messages', [
                'visibility' => 'registered',
                'attachable' => $message,
            ]);
        }

        $conversation->touch();
        $conversation->markReadFor($user);
        $messagePush->sendForMessage($message);
        $messageBroadcast->broadcastCreated($message);
        $conversation->loadMissing(['users.profile', 'users.privacySettings']);
        $message->loadMissing(['user.profile', 'user.privacySettings', 'attachments']);

        return response()->json([
            'message' => 'Message sent.',
            'data' => [
                'conversation' => $this->conversationPayload($request, $conversation->fresh(['users.profile', 'users.privacySettings', 'latestMessage.user.profile', 'latestMessage.user.privacySettings']), $user),
                'message' => $this->messagePayload($request, $message, $user, $conversation),
            ],
            'counts' => $this->counts($user),
        ], 201);
    }

    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->isParticipant($user), 403);

        $conversation->markReadFor($user);

        return response()->json([
            'message' => 'Conversation marked as read.',
            'conversation_id' => $conversation->id,
            'counts' => $this->counts($user),
        ]);
    }

    public function typing(Request $request, Conversation $conversation, MessageBroadcastService $messageBroadcast): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($conversation->isParticipant($user), 403);
        abort_unless($conversation->type === 'private', 403);

        if (! $this->canMessage($conversation, $user)) {
            abort(403, $this->messageDeniedText($conversation, $user));
        }

        $validator = Validator::make($request->all(), [
            'is_typing' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $isTyping = $request->has('is_typing') ? $request->boolean('is_typing') : true;

        $conversation->loadMissing('users');
        $messageBroadcast->broadcastTyping($conversation, $user, $isTyping);

        return response()->json([
            'message' => 'Typing broadcasted.',
            'conversation_id' => $conversation->id,
        ]);
    }

    public function clear(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->isParticipant($user), 403);

        $conversation->users()->updateExistingPivot($user->id, [
            'cleared_at' => now(),
            'last_read_at' => now(),
        ]);
        $conversation->loadMissing(['users.profile', 'users.privacySettings', 'latestMessage.user.profile', 'latestMessage.user.privacySettings']);

        return response()->json([
            'message' => 'Conversation cleared.',
            'data' => [
                'conversation' => $this->conversationPayload($request, $conversation, $user),
                'messages' => [],
            ],
            'counts' => $this->counts($user),
        ]);
    }

    private function conversationPayload(Request $request, Conversation $conversation, User $user): array
    {
        $conversation->loadMissing(['users.profile', 'users.privacySettings', 'latestMessage.user.profile', 'latestMessage.user.privacySettings']);
        $partner = $conversation->otherParticipant($user);
        $latest = $this->visibleMessagesQuery($conversation, $user)
            ->with(['user.profile', 'user.privacySettings', 'attachments'])
            ->latest('created_at')
            ->first();
        $title = $conversation->displayTitleFor($user);
        $hasBlocked = $partner ? $user->hasBlocked($partner) : false;
        $isBlockedBy = $partner ? $partner->hasBlocked($user) : false;

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'category' => $conversation->isLfgConversation() ? 'request' : 'private',
            'title' => $title,
            'subtitle' => $conversation->context_label ?: ($partner?->username ? '@'.$partner->username : ''),
            'context_label' => $conversation->context_label,
            'context_url' => $conversation->context_url,
            'partner' => $partner ? (new UserResource($partner))->resolve($request) : null,
            'latest_message' => $latest ? $this->messagePayload($request, $latest, $user, $conversation) : null,
            'last_message' => $latest?->body,
            'last_message_at' => $latest?->created_at?->toISOString() ?: $conversation->updated_at?->toISOString(),
            'last_message_at_label' => ($latest?->created_at ?: $conversation->updated_at)?->diffForHumans(null, true, false, 2),
            'unread_count' => $this->unreadCountFor($conversation, $user),
            'updated_at' => $conversation->updated_at?->toISOString(),
            'viewer_state' => [
                'has_blocked' => $hasBlocked,
                'is_blocked_by' => $isBlockedBy,
                'can_message' => $this->canMessage($conversation, $user),
            ],
        ];
    }

    private function messagePayload(Request $request, Message $message, User $user, ?Conversation $conversation = null): array
    {
        $message->loadMissing(['user.profile', 'user.privacySettings', 'attachments']);
        $conversation ??= $message->conversation;
        $readByPartner = $this->isReadByPartner($conversation, $message, $user);

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'type' => $message->type ?: 'user',
            'body' => $message->body,
            'is_mine' => (int) $message->user_id === (int) $user->id,
            'is_read_by_partner' => $readByPartner,
            'user' => (new UserResource($message->user))->resolve($request),
            'created_at' => $message->created_at?->toISOString(),
            'created_at_label' => $message->created_at?->format('H:i'),
            'attachments' => $message->attachments->map(fn ($asset): array => [
                'id' => $asset->id,
                'type' => $asset->type,
                'mime_type' => $asset->mime_type,
                'url' => $asset->url(),
                'thumbnail_url' => $asset->thumbnailUrl(),
                'original_name' => $asset->original_name,
                'size_bytes' => $asset->size_bytes,
                'width' => $asset->width,
                'height' => $asset->height,
            ])->values(),
        ];
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

    private function normalizeType(mixed $type): string
    {
        return in_array($type, ['requests', 'request', 'lfg'], true) ? 'requests' : 'private';
    }

    private function typesFor(string $type): array
    {
        return $type === 'requests' ? ['lfg', 'team_lfg'] : ['private'];
    }

    private function counts(User $user): array
    {
        return [
            'unread_messages' => $user->unreadMessagesCount(),
            'unread_private_messages' => $user->unreadMessagesCount(['private']),
            'unread_request_messages' => $user->unreadMessagesCount(['lfg', 'team_lfg']),
        ];
    }

    private function visibleMessagesQuery(Conversation $conversation, User $user)
    {
        $participant = $conversation->users()->where('users.id', $user->id)->first()?->pivot;

        return $conversation->messages()
            ->when($participant?->cleared_at, fn ($query, $clearedAt) => $query->where('created_at', '>', $clearedAt));
    }

    private function unreadCountFor(Conversation $conversation, User $user): int
    {
        $participant = $conversation->users()->where('users.id', $user->id)->first()?->pivot;

        return $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->when($participant?->last_read_at, fn ($query, $lastReadAt) => $query->where('created_at', '>', $lastReadAt))
            ->when($participant?->cleared_at, fn ($query, $clearedAt) => $query->where('created_at', '>', $clearedAt))
            ->count();
    }

    private function isReadByPartner(Conversation $conversation, Message $message, User $viewer): bool
    {
        if ((int) $message->user_id !== (int) $viewer->id) {
            return false;
        }

        $conversation->loadMissing('users');
        $partner = $conversation->users->firstWhere('id', '!=', $viewer->id);
        $lastReadAt = $partner?->pivot?->last_read_at;

        return $lastReadAt !== null && $message->created_at !== null && $message->created_at->lessThanOrEqualTo($lastReadAt);
    }

    private function canMessage(Conversation $conversation, User $viewer): bool
    {
        $partner = $conversation->otherParticipant($viewer);
        if (! $partner) {
            return true;
        }

        if ($viewer->hasBlocked($partner) || $partner->hasBlocked($viewer)) {
            return false;
        }

        return $conversation->type !== 'private' || $this->privacy->canMessage($viewer, $partner);
    }

    private function messageDeniedText(Conversation $conversation, User $viewer): string
    {
        $partner = $conversation->otherParticipant($viewer);

        if ($partner && ($viewer->hasBlocked($partner) || $partner->hasBlocked($viewer))) {
            return __('ui.message_blocked_unavailable');
        }

        return __('settings.privacy_messages_denied');
    }
}
