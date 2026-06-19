<?php

namespace App\Http\Resources\Api;

use App\Services\Translation\FeedTranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->feed_post_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'source_language' => $this->source_language,
            'translation' => $this->translationPayload($request),
            'author' => new UserResource($this->whenLoaded('user')),
            'media' => FeedCommentMediaResource::collection($this->whenLoaded('media')),
            'reactions_count' => (int) ($this->reactions_count ?? 0),
            'replies_count' => $this->whenLoaded('replies', fn () => $this->replies->count()),
            'replies' => FeedCommentResource::collection($this->whenLoaded('replies')),
            'viewer' => [
                'reaction' => $this->whenLoaded('viewerReaction', fn () => $this->viewerReaction?->type),
                'can_edit' => $request->user() && (int) $request->user()->id === (int) $this->user_id,
                'can_delete' => $request->user() && (int) $request->user()->id === (int) $this->user_id,
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
}
