<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MomentCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $viewerReactionLoaded = $this->relationLoaded('reactions');
        $viewerLiked = $viewerReactionLoaded
            ? $this->reactions->contains(fn ($reaction) => (int) $reaction->user_id === (int) ($viewer?->id ?? 0) && $reaction->type === 'like')
            : $this->isLikedBy($viewer);

        return [
            'id' => $this->id,
            'moment_id' => $this->moment_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'author' => new UserResource($this->whenLoaded('user')),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'replies_count' => (int) ($this->replies_count ?? 0),
            'is_liked' => (bool) $viewerLiked,
            'can_edit' => $this->canBeEditedBy($viewer),
            'can_delete' => $this->canBeDeletedBy($viewer),
            'replies' => MomentCommentResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_local' => $this->created_at?->toDateTimeString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->toISOString(),
            'updated_at_local' => $this->updated_at?->toDateTimeString(),
            'edited' => $this->updated_at && $this->created_at && $this->updated_at->gt($this->created_at->copy()->addSeconds(2)),
        ];
    }
}
