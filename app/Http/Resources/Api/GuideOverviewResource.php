<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuideOverviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'cover_url' => $this->coverUrl(),
            'category' => $this->category,
            'tags' => array_values(array_filter((array) $this->tags)),
            'language' => $this->language,
            'difficulty' => $this->difficulty,
            'platform' => $this->platform,
            'is_featured' => (bool) $this->is_featured,
            'reading_time_minutes' => (int) $this->reading_time_minutes,
            'views_count' => (int) $this->views_count,
            'helpful_count' => (int) $this->helpful_count,
            'comments_count' => (int) $this->comments_count,
            'published_at' => $this->published_at?->toISOString(),
            'author' => $this->author ? [
                'id' => (int) $this->author->id,
                'username' => $this->author->username,
                'display_name' => $this->author->name,
                'avatar_url' => $this->author->avatarUrl(),
            ] : null,
        ];
    }
}
