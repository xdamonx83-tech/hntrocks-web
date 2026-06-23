<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LiveLobbyFeedbackRequestResource;
use App\Http\Resources\Api\LiveLobbyFeedbackResource;
use App\Models\LiveLobbyFeedback;
use App\Models\LiveLobbyFeedbackRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApiLiveLobbyFeedbackController extends Controller
{
    private const POSITIVE_TAGS = [
        'reliable', 'chill', 'teamplayer', 'good_communication', 'helpful',
        'beginner_friendly', 'would_play_again',
    ];

    private const PRIVATE_FLAGS = ['no_show', 'left_early', 'not_again', 'uncomfortable'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = LiveLobbyFeedbackRequest::query()
            ->with(['lobby', 'targetUser'])
            ->where('reviewer_id', $request->user()->id)
            ->where('status', 'pending')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderBy('expires_at')
            ->paginate(20);

        return LiveLobbyFeedbackRequestResource::collection($requests);
    }

    public function submit(Request $request, LiveLobbyFeedbackRequest $feedbackRequest): JsonResponse
    {
        $this->authorizeReviewer($request, $feedbackRequest);
        $validated = $request->validate([
            'positive_tags' => ['nullable', 'array', 'max:5'],
            'positive_tags.*' => ['string', Rule::in(self::POSITIVE_TAGS)],
            'private_flags' => ['nullable', 'array', 'max:3'],
            'private_flags.*' => ['string', Rule::in(self::PRIVATE_FLAGS)],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $feedback = DB::transaction(function () use ($feedbackRequest, $validated): LiveLobbyFeedback {
            $locked = LiveLobbyFeedbackRequest::query()->whereKey($feedbackRequest->id)->lockForUpdate()->firstOrFail();
            $this->ensureActionable($locked);

            $feedback = LiveLobbyFeedback::query()->create([
                'feedback_request_id' => $locked->id,
                'live_lobby_id' => $locked->live_lobby_id,
                'reviewer_id' => $locked->reviewer_id,
                'target_user_id' => $locked->target_user_id,
                'positive_tags' => $this->uniqueValues($validated['positive_tags'] ?? []),
                'private_flags' => $this->uniqueValues($validated['private_flags'] ?? []),
                'comment' => $validated['comment'] ?? null,
            ]);

            $locked->forceFill(['status' => 'completed'])->save();

            return $feedback->load(['request', 'lobby']);
        });

        return response()->json([
            'success' => true,
            'request' => new LiveLobbyFeedbackRequestResource($feedback->request->load(['lobby', 'targetUser'])),
            'feedback' => new LiveLobbyFeedbackResource($feedback),
        ]);
    }

    public function dismiss(Request $request, LiveLobbyFeedbackRequest $feedbackRequest): JsonResponse
    {
        $this->authorizeReviewer($request, $feedbackRequest);

        $feedbackRequest = DB::transaction(function () use ($feedbackRequest): LiveLobbyFeedbackRequest {
            $locked = LiveLobbyFeedbackRequest::query()->whereKey($feedbackRequest->id)->lockForUpdate()->firstOrFail();
            $this->ensureActionable($locked);
            $locked->forceFill(['status' => 'dismissed'])->save();

            return $locked->load(['lobby', 'targetUser']);
        });

        return response()->json([
            'success' => true,
            'request' => new LiveLobbyFeedbackRequestResource($feedbackRequest),
        ]);
    }

    private function authorizeReviewer(Request $request, LiveLobbyFeedbackRequest $feedbackRequest): void
    {
        abort_unless((int) $feedbackRequest->reviewer_id === (int) $request->user()->id, 403);
        abort_if((int) $feedbackRequest->reviewer_id === (int) $feedbackRequest->target_user_id, 422, 'Self-feedback is not allowed.');
    }

    private function ensureActionable(LiveLobbyFeedbackRequest $feedbackRequest): void
    {
        if ($feedbackRequest->status !== 'pending') {
            abort(422, 'This feedback request is no longer pending.');
        }

        if (! $feedbackRequest->available_at || $feedbackRequest->available_at->isFuture()) {
            abort(422, 'This feedback request is not available yet.');
        }

        if ($feedbackRequest->expires_at?->isPast()) {
            abort(422, 'This feedback request has expired.');
        }
    }

    private function uniqueValues(array $values): array
    {
        return array_values(array_unique($values, SORT_STRING));
    }
}
