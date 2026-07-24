<?php

namespace App\Http\Resources\Api;

use App\Models\GuideMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GuideOverviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $revision = $this->publishedRevision;
        $category = $revision?->category;
        $locale = app()->getLocale() === 'en' ? 'en' : 'de';

        return [
            'id' => (int) $this->id,
            'slug' => (string) $this->slug,
            'title' => (string) ($revision?->title ?? ''),
            'summary' => (string) ($revision?->summary ?? ''),
            'cover_url' => $this->coverUrl($revision?->coverMedia),
            'category' => $category ? [
                'id' => (int) $category->id,
                'slug' => (string) $category->slug,
                'label' => $category->label($locale),
            ] : null,
            'tags' => array_values(array_filter((array) ($revision?->tags ?? []))),
            'language' => (string) ($revision?->language ?? 'de'),
            'difficulty' => (string) ($revision?->difficulty ?? 'beginner'),
            'platform' => (string) ($revision?->platform ?? 'all'),
            'reading_time_minutes' => max(1, (int) ($revision?->reading_time_minutes ?? 1)),
            'is_featured' => (bool) $this->is_featured,
            'helpful_count' => (int) $this->helpful_count,
            'bookmarks_count' => (int) $this->bookmarks_count,
            'comments_count' => (int) $this->comments_count,
            'viewer_bookmarked' => (bool) ($this->viewer_bookmarked ?? false),
            'published_at' => $this->published_at?->toISOString(),
            'author' => $this->author ? [
                'id' => (int) $this->author->id,
                'username' => (string) $this->author->username,
                'display_name' => (string) $this->author->name,
                'avatar_url' => $this->author->avatarUrl(),
            ] : null,
        ];
    }

    private function coverUrl(?GuideMedia $coverMedia): ?string
    {
        if (! $coverMedia) {
            return null;
        }

        $asset = $coverMedia->mediaAsset;
        if ($asset) {
            return $asset->thumbnailUrl();
        }

        $path = trim((string) $coverMedia->path);
        if ($path === '') {
            return null;
        }

        return Storage::disk($coverMedia->disk ?: 'public')->url($path);
    }
}
