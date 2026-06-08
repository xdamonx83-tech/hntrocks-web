<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MomentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        return [
            'id' => $this->id,
            'caption' => $this->caption,
            'description' => $this->description,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'processing_status' => $this->processing_status,
            'media_url' => $this->mediaUrl(),
            'cover_url' => $this->coverUrl(),
            'trim_start_seconds' => $this->trim_start_seconds,
            'trim_end_seconds' => $this->trim_end_seconds,
            'duration_seconds' => $this->duration_seconds,
            'views_count' => (int) $this->views_count,
            'likes_count' => (int) $this->likes_count,
            'comments_count' => (int) $this->comments_count,
            'bookmarks_count' => (int) $this->bookmarks_count,
            'is_liked' => $viewer ? $this->viewerHasLoadedRelation('reactions', $viewer->id, 'type', 'like') : false,
            'is_bookmarked' => $viewer ? $this->viewerHasLoadedRelation('bookmarks', $viewer->id) : false,
            'can_manage' => $this->canBeManagedBy($viewer),
            'author' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
        ];
    }

    private function viewerHasLoadedRelation(string $relation, int $viewerId, ?string $typeColumn = null, ?string $typeValue = null): bool
    {
        if ($this->relationLoaded($relation)) {
            return $this->{$relation}->contains(function ($item) use ($viewerId, $typeColumn, $typeValue): bool {
                if ((int) $item->user_id !== $viewerId) {
                    return false;
                }

                if ($typeColumn !== null) {
                    return (string) $item->{$typeColumn} === (string) $typeValue;
                }

                return true;
            });
        }

        $query = $this->{$relation}()->where('user_id', $viewerId);

        if ($typeColumn !== null) {
            $query->where($typeColumn, $typeValue);
        }

        return $query->exists();
    }
}
