<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MomentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $media = $this->media;
        $aspectRatioLabel = $this->aspectRatioLabel($media?->metadata, $media?->width, $media?->height);
        $aspectRatio = $media?->width && $media?->height
            ? round($media->width / $media->height, 6)
            : $this->aspectRatioValue($aspectRatioLabel);

        return [
            'id' => $this->id,
            'caption' => $this->caption,
            'description' => $this->description,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'processing_status' => $this->processing_status,
            'media_url' => $this->mediaUrl(),
            'cover_url' => $this->coverUrl(),
            'aspect_ratio_label' => $aspectRatioLabel,
            'aspect_ratio' => $aspectRatio,
            'media_width' => $media?->width,
            'media_height' => $media?->height,
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

    private function aspectRatioLabel(mixed $metadata, ?int $width, ?int $height): string
    {
        $metadata = is_array($metadata) ? $metadata : [];
        $label = trim((string) ($metadata['aspect_ratio_label'] ?? $metadata['format'] ?? ''));
        if (in_array($label, ['9:16', '16:9', '1:1'], true)) {
            return $label;
        }

        if ($width && $height) {
            $ratio = $width / $height;
            if (abs($ratio - 1.0) <= 0.08) {
                return '1:1';
            }

            if ($ratio > 1.0) {
                return '16:9';
            }
        }

        return '9:16';
    }

    private function aspectRatioValue(string $label): float
    {
        return match ($label) {
            '16:9' => round(16 / 9, 6),
            '1:1' => 1.0,
            default => round(9 / 16, 6),
        };
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
