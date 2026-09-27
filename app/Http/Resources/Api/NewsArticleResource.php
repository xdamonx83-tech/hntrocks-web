<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translations = $this->whenLoaded('translations', fn () => $this->translations
            ->mapWithKeys(fn ($translation): array => [$translation->locale => (new NewsArticleTranslationResource($translation))->toArray($request)])
            ->all());

        return [
            'id' => $this->id,
            'status' => $this->status,
            'category_key' => $this->category_key,
            'tags' => $this->tags ?? [],
            'hero_media' => $this->whenLoaded('heroMedia', fn () => $this->heroMedia ? (new MediaAssetResource($this->heroMedia))->toArray($request) : null),
            'featured' => (bool) $this->featured,
            'comments_enabled' => (bool) $this->comments_enabled,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'archived_at' => $this->archived_at?->toIso8601String(),
            'lock_version' => (int) $this->lock_version,
            'revision_count' => $this->whenCounted('revisions'),
            'translations' => $translations,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
