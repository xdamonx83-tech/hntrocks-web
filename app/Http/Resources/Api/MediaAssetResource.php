<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'mime_type' => $this->mime_type,
            'url' => $this->url(),
            'thumbnail_url' => $this->thumbnailUrl(),
            'size_bytes' => (int) $this->size_bytes,
            'width' => $this->width,
            'height' => $this->height,
            'duration_seconds' => $this->duration_seconds,
            'alt_text' => $this->alt_text,
        ];
    }
}
