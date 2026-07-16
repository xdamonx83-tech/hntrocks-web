<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamContractTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name_key' => $this->name_key,
            'description_key' => $this->description_key,
            'category' => $this->category,
            'metric' => $this->metric,
            'target_value' => (int) $this->target_value,
            'minimum_contributors' => (int) $this->minimum_contributors,
            'duration_days' => $this->duration_days !== null ? (int) $this->duration_days : null,
            'repeatable' => (bool) $this->is_repeatable,
            'cooldown_days' => $this->cooldown_days !== null ? (int) $this->cooldown_days : null,
            'rewards' => ['team_xp' => (int) $this->team_xp_reward, 'rocks_per_qualified_member' => (int) $this->rocks_reward],
        ];
    }
}
