<?php

namespace App\Http\Resources\Api;

use App\Services\Translation\FeedTranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'source_language' => $this->source_language,
            'translation' => $this->translationPayload($request),
            'background_style' => $this->background_style,
            'feeling_key' => $this->feeling_key,
            'feeling' => $this->feelingMeta(),
            'poll' => $this->whenLoaded('poll', fn () => $this->pollPayload($request)),
            'gif' => $this->gifPayload(),
            'visibility' => $this->visibility,
            'status' => $this->status,
            'is_pinned' => (bool) $this->is_pinned,
            'ai_user_declared' => (bool) $this->ai_user_declared,
            'ai_label_visible' => $this->hasVisibleAiContentLabel(),
            'author' => new UserResource($this->whenLoaded('user')),
            'media' => FeedPostMediaResource::collection($this->whenLoaded('media')),
            'shared_post' => $this->whenLoaded('sharedPost', fn () => $this->sharedPost ? new self($this->sharedPost) : null),
            'comments_preview' => FeedCommentResource::collection($this->whenLoaded('previewComments')),
            'reactions_preview' => $this->whenLoaded('previewReactions', function () {
                return UserResource::collection(
                    $this->previewReactions
                        ->pluck('user')
                        ->filter()
                        ->values()
                );
            }),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'reactions_count' => (int) ($this->reactions_count ?? 0),
            'bookmarks_count' => (int) ($this->bookmarks_count ?? 0),
            'shares_count' => (int) ($this->shares_count ?? 0),
            'viewer' => [
                'reaction' => $this->whenLoaded('viewerReaction', fn () => $this->viewerReaction?->type),
                'bookmarked' => $this->whenLoaded('viewerBookmark', fn () => $this->viewerBookmark !== null),
                'can_edit' => $request->user() && (int) $request->user()->id === (int) $this->user_id,
                'can_delete' => $request->user() && ((int) $request->user()->id === (int) $this->user_id || $request->user()->isAdmin()),
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }


    private function translationPayload(Request $request): array
    {
        $targetLocale = $request->query('locale') ?: $request->header('X-HNT-Locale') ?: app()->getLocale();

        return app(FeedTranslationService::class)->translationMeta(
            $this->body,
            $this->source_language,
            $targetLocale
        );
    }

    private function pollPayload(Request $request): ?array
    {
        $poll = $this->poll;
        if (! $poll) {
            return null;
        }

        $options = $poll->relationLoaded('options') ? $poll->options : collect();
        $votes = $poll->relationLoaded('votes') ? $poll->votes : collect();
        $totalVotes = $votes->count();
        $viewerVote = $request->user() ? $votes->firstWhere('user_id', $request->user()->id) : null;
        $viewerOptionId = $viewerVote?->feed_post_poll_option_id;

        return [
            'id' => $poll->id,
            'question' => $poll->question,
            'total_votes' => $totalVotes,
            'viewer_option_id' => $viewerOptionId,
            'is_closed' => $poll->isClosed(),
            'options' => $options->map(function ($option) use ($totalVotes, $viewerOptionId): array {
                $voteCount = $option->relationLoaded('votes') ? $option->votes->count() : 0;

                return [
                    'id' => $option->id,
                    'body' => $option->body,
                    'votes_count' => $voteCount,
                    'percent' => $totalVotes > 0 ? round(($voteCount / $totalVotes) * 100, 1) : 0,
                    'viewer_selected' => $viewerOptionId !== null && (int) $viewerOptionId === (int) $option->id,
                ];
            })->values()->all(),
        ];
    }
}
