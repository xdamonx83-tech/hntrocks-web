<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsArticleRevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'revision_number' => (int) $this->revision_number,
            'lock_version' => (int) $this->lock_version,
            'revision_type' => $this->revision_type,
            'snapshot' => $this->snapshot_json,
            'editor' => $this->whenLoaded('editor', fn () => $this->editor ? [
                'id' => $this->editor->id,
                'name' => $this->editor->name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
