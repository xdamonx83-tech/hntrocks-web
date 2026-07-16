<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ownContribution = $this->relationLoaded('contributions')
            ? (int) $this->contributions->where('user_id', $request->user()?->id)->sum('amount')
            : 0;

        return [
            'id' => $this->id,
            'template_key' => $this->template?->key,
            'status' => $this->status,
            'name_key' => $this->name_key,
            'description_key' => $this->description_key,
            'category' => $this->category,
            'metric' => $this->metric,
            'progress_value' => (int) $this->progress_value,
            'target_value' => (int) $this->target_value,
            'progress_percent' => $this->target_value > 0 ? round(min(100, ($this->progress_value / $this->target_value) * 100), 2) : 100.0,
            'minimum_contributors' => (int) $this->minimum_contributors,
            'contributors_count' => (int) $this->contributors_count,
            'own_contribution' => $ownContribution,
            'rewards' => ['team_xp' => (int) $this->team_xp_reward, 'rocks_per_qualified_member' => (int) $this->rocks_reward],
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
        ];
    }
}
