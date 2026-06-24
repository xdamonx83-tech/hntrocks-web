<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLoadoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'primary_weapon' => $this->primary_weapon,
            'secondary_weapon' => $this->secondary_weapon,
            'tools' => $this->tools ?? [],
            'consumables' => $this->consumables ?? [],
            'traits' => $this->traits ?? [],
            'note' => $this->note,
            'visibility' => $this->visibility,
            'sort_order' => (int) $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
