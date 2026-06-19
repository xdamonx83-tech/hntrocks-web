<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FeedCommentMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $asset = $this->mediaAsset;
        $mimeType = $asset?->mime_type ?: $this->mime_type;
        $type = $asset?->type ?: (str_starts_with((string) $mimeType, 'image/') ? 'image' : 'file');

        return [
            'id' => $this->id,
            'type' => $type,
            'mime_type' => $mimeType,
            'url' => $asset ? $asset->url() : Storage::disk($this->disk)->url($this->path),
            'thumbnail_url' => $asset?->thumbnailUrl(),
            'status' => $asset?->status ?: 'ready',
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
