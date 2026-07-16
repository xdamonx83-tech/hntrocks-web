<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamContractTemplate extends Model
{
    protected $fillable = ['key', 'name_key', 'description_key', 'category', 'metric', 'target_value', 'minimum_contributors', 'duration_days', 'is_repeatable', 'cooldown_days', 'team_xp_reward', 'rocks_reward', 'is_active', 'configuration'];

    protected function casts(): array
    {
        return [
            'target_value' => 'integer', 'minimum_contributors' => 'integer', 'duration_days' => 'integer',
            'is_repeatable' => 'boolean', 'cooldown_days' => 'integer', 'team_xp_reward' => 'integer',
            'rocks_reward' => 'integer', 'is_active' => 'boolean', 'configuration' => 'array',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(TeamContract::class, 'template_id');
    }
}
