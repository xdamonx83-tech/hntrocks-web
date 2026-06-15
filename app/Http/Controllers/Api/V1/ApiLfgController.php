<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LfgPostResource;
use App\Models\Conversation;
use App\Models\LfgApplication;
use App\Models\LfgPost;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\MentionService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiLfgController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = LfgPost::query()
            ->with([
                'user.profile',
                'applications' => fn ($query) => $query->where('user_id', $request->user()->id),
                'pendingApplications.user.profile',
            ])
            ->where(function ($query) use ($request): void {
                $query->where('visibility', 'public')
                    ->orWhere('user_id', $request->user()->id);
            })
            ->whereIn('status', ['open', 'full', 'closed'])
            ->when($request->filled('platform'), fn ($query) => $query->where('platform', $request->string('platform')))
            ->when($request->filled('playstyle'), fn ($query) => $query->where('playstyle', $request->string('playstyle')))
            ->when($request->filled('region'), fn ($query) => $query->where('region', $request->string('region')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20);

        return LfgPostResource::collection($posts);
    }

    public function show(Request $request, LfgPost $post): JsonResponse
    {
        abort_unless($post->visibility === 'public' || (int) $post->user_id === (int) $request->user()->id, 403);

        $post = $this->freshPostForResponse($post, $request);

        return response()->json([
            'lfg' => new LfgPostResource($post),
            'data' => new LfgPostResource($post),
        ]);
    }

    public function store(
        Request $request,
        GamificationService $gamification,
        MentionService $mentions,
        NotificationService $notifications
    ): JsonResponse {
        $validated = $this->validatedPostData($request);

        $user = $request->user();

        $post = LfgPost::query()->create([
            ...$validated,
            'user_id' => $user->id,
            'voice_required' => $request->boolean('voice_required'),
            'slots_filled' => 1,
            'status' => 'open',
        ]);

        $gamification->award($user, 'lfg_post_created', source: $post);
        $mentions->syncForLfgPost($post, $user, (string) ($post->body ?? ''), $notifications);

        $post->load(['user.profile', 'pendingApplications.user.profile']);

        return response()->json([
            'message' => __('ui.lfg_created_status'),
            'lfg' => new LfgPostResource($post),
            'data' => new LfgPostResource($post),
        ], 201);
    }

    public function update(
        Request $request,
        LfgPost $post,
        MentionService $mentions,
        NotificationService $notifications
    ): JsonResponse {
        abort_unless($post->canManage($request->user()), 403);

        $validated = $this->validatedPostData($request);
        if ((int) $post->slots_filled > (int) $validated['slots_total']) {
            return response()->json([
                'message' => __('ui.lfg_error_slots_filled_too_high'),
            ], 422);
        }

        $post->fill([
            ...$validated,
            'voice_required' => $request->boolean('voice_required'),
        ]);

        if ($post->status === 'open' && $post->slots_filled >= $post->slots_total) {
            $post->status = 'full';
        } elseif ($post->status === 'full' && $post->slots_filled < $post->slots_total) {
            $post->status = 'open';
        }

        $post->save();
        $mentions->syncForLfgPost($post, $request->user(), (string) ($post->body ?? ''), $notifications);

        $freshPost = $this->freshPostForResponse($post, $request);

        return response()->json([
            'message' => __('ui.lfg_saved_status'),
            'lfg' => new LfgPostResource($freshPost),
            'data' => new LfgPostResource($freshPost),
        ]);
    }

    public function close(Request $request, LfgPost $post): JsonResponse
    {
        abort_unless($post->canManage($request->user()), 403);

        $post->update(['status' => 'closed']);
        $freshPost = $this->freshPostForResponse($post, $request);

        return response()->json([
            'message' => __('ui.lfg_closed_status'),
            'lfg' => new LfgPostResource($freshPost),
            'data' => new LfgPostResource($freshPost),
        ]);
    }

    public function destroy(Request $request, LfgPost $post): JsonResponse
    {
        abort_unless($post->canDelete($request->user()), 403);

        $post->update(['status' => 'archived']);
        $post->delete();

        return response()->json([
            'message' => __('ui.lfg_deleted_status'),
            'data' => null,
        ]);
    }

    public function apply(
        Request $request,
        LfgPost $post,
        NotificationService $notifications,
        GamificationService $gamification
    ): JsonResponse {
        $post->loadMissing(['user', 'user.profile', 'applications']);
        $user = $request->user();

        if (! $post->isOpen()) {
            return response()->json(['message' => __('ui.lfg_error_not_open')], 422);
        }

        if ($post->isOwner($user)) {
            return response()->json(['message' => __('ui.lfg_error_own_lfg')], 422);
        }

        if ($post->applicationFor($user)) {
            return response()->json(['message' => __('ui.lfg_error_already_applied')], 422);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:800'],
        ]);

        $application = $post->applications()->create([
            'user_id' => $user->id,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        $gamification->award($user, 'lfg_application_sent', source: $application);
        $notifications->send(
            $post->user,
            $user,
            'lfg_application',
            __('ui.lfg_notification_application_title'),
            __('ui.lfg_notification_application_body', ['user' => $user->name]),
            route('lfg.show', $post) . '#lfg-applications'
        );

        $post->setRelation('applications', collect([$application]));
        $post->loadMissing('user.profile');

        return response()->json([
            'message' => __('ui.lfg_application_sent'),
            'application' => $this->applicationPayload($application),
            'lfg' => new LfgPostResource($post),
            'data' => new LfgPostResource($post),
        ], 201);
    }

    public function acceptApplication(
        Request $request,
        LfgPost $post,
        LfgApplication $application,
        NotificationService $notifications,
        GamificationService $gamification
    ): JsonResponse {
        $post->loadMissing(['user', 'user.profile']);
        abort_unless($post->canManage($request->user()), 403);
        abort_unless((int) $application->lfg_post_id === (int) $post->id, 404);

        if ($application->status !== 'pending') {
            return response()->json(['message' => __('ui.lfg_error_application_not_pending')], 422);
        }

        if (! $post->isOpen()) {
            return response()->json(['message' => __('ui.lfg_error_no_slots')], 422);
        }

        $application->update([
            'status' => 'accepted',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $post->slots_filled = min((int) $post->slots_total, (int) $post->slots_filled + 1);
        if ($post->slots_filled >= $post->slots_total) {
            $post->status = 'full';
        }
        $post->save();

        $application->loadMissing('user');
        if ($application->user) {
            $conversation = $this->lfgConversationFor($post, $application->user, $request->user());
            $this->appendLfgSystemMessage($conversation, $request->user(), $this->applicationCreatedMessage($post, $application));
            $this->appendLfgSystemMessage($conversation, $request->user(), __('ui.lfg_chat_application_accepted_system', ['user' => $request->user()->name]));

            $gamification->award($application->user, 'lfg_application_accepted', source: $application);
            $notifications->send(
                $application->user,
                $request->user(),
                'lfg_application_accepted',
                __('ui.lfg_notification_accepted_title'),
                __('ui.lfg_notification_accepted_body'),
                route('messages.show', $conversation)
            );
        }

        $freshPost = $this->freshPostForResponse($post, $request);

        return response()->json([
            'message' => __('ui.lfg_application_accepted_status'),
            'application' => $this->applicationPayload($application->fresh()),
            'lfg' => new LfgPostResource($freshPost),
            'data' => new LfgPostResource($freshPost),
        ]);
    }

    public function rejectApplication(
        Request $request,
        LfgPost $post,
        LfgApplication $application,
        NotificationService $notifications
    ): JsonResponse {
        $post->loadMissing(['user', 'user.profile']);
        abort_unless($post->canManage($request->user()), 403);
        abort_unless((int) $application->lfg_post_id === (int) $post->id, 404);

        if ($application->status !== 'pending') {
            return response()->json(['message' => __('ui.lfg_error_application_not_pending')], 422);
        }

        $application->update([
            'status' => 'rejected',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $application->loadMissing('user');
        if ($application->user) {
            $notifications->send(
                $application->user,
                $request->user(),
                'lfg_application_rejected',
                __('ui.lfg_notification_rejected_title'),
                __('ui.lfg_notification_rejected_body'),
                route('lfg.show', $post)
            );
        }

        $freshPost = $this->freshPostForResponse($post, $request);

        return response()->json([
            'message' => __('ui.lfg_application_rejected_status'),
            'application' => $this->applicationPayload($application->fresh()),
            'lfg' => new LfgPostResource($freshPost),
            'data' => new LfgPostResource($freshPost),
        ]);
    }

    private function freshPostForResponse(LfgPost $post, Request $request): LfgPost
    {
        return LfgPost::query()
            ->with([
                'user.profile',
                'applications' => fn ($query) => $query->where('user_id', $request->user()->id),
                'pendingApplications.user.profile',
            ])
            ->findOrFail($post->id);
    }

    private function validatedPostData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2800'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'preferred_time' => ['nullable', 'string', 'max:80'],
            'experience_level' => ['nullable', 'string', 'max:60'],
            'voice_required' => ['nullable', 'boolean'],
            'slots_total' => ['required', 'integer', 'min:2', 'max:4'],
            'visibility' => ['required', 'string', 'in:public,private'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);
    }

    private function applicationPayload(?LfgApplication $application): ?array
    {
        if (! $application) {
            return null;
        }

        return [
            'id' => $application->id,
            'status' => $application->status,
            'message' => $application->message,
            'created_at' => $application->created_at?->toISOString(),
            'decided_at' => $application->decided_at?->toISOString(),
        ];
    }

    private function lfgConversationFor(LfgPost $post, User $applicant, User $actor): Conversation
    {
        $post->loadMissing('user');

        $conversation = Conversation::query()
            ->where('type', 'lfg')
            ->where('context_type', 'lfg_post')
            ->where('context_id', $post->id)
            ->whereHas('users', fn ($query) => $query->where('users.id', $post->user_id))
            ->whereHas('users', fn ($query) => $query->where('users.id', $applicant->id))
            ->withCount('users')
            ->get()
            ->firstWhere('users_count', 2);

        if (! $conversation) {
            $conversation = Conversation::create([
                'type' => 'lfg',
                'title' => __('ui.lfg_conversation_title', ['title' => $post->title]),
                'context_type' => 'lfg_post',
                'context_id' => $post->id,
                'context_label' => __('ui.lfg_conversation_title', ['title' => $post->title]),
                'context_url' => route('lfg.show', $post),
                'created_by' => $actor->id,
            ]);
        }

        $conversation->users()->syncWithoutDetaching([
            $post->user_id => ['last_read_at' => (int) $actor->id === (int) $post->user_id ? now() : null],
            $applicant->id => ['last_read_at' => (int) $actor->id === (int) $applicant->id ? now() : null],
        ]);

        return $conversation;
    }

    private function appendLfgSystemMessage(Conversation $conversation, User $actor, string $body): void
    {
        $conversation->messages()->create([
            'user_id' => $actor->id,
            'type' => 'system',
            'body' => $body,
        ]);

        $conversation->touch();
        $conversation->markReadFor($actor);
    }

    private function applicationCreatedMessage(LfgPost $post, LfgApplication $application): string
    {
        $application->loadMissing('user');

        $body = __('ui.lfg_chat_application_created_system', [
            'user' => $application->user?->name ?: __('ui.player'),
            'title' => $post->title,
        ]);

        $message = trim((string) $application->message);
        if ($message !== '') {
            $body .= "\n\n" . __('ui.lfg_chat_application_message', ['message' => $message]);
        }

        return $body;
    }
}
