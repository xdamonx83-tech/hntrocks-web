<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamContract extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['team_id', 'template_id', 'activated_by', 'status', 'name_key', 'description_key', 'category', 'metric', 'progress_value', 'target_value', 'minimum_contributors', 'contributors_count', 'team_xp_reward', 'rocks_reward', 'configuration', 'starts_at', 'ends_at', 'completed_at', 'cancelled_at', 'rewarded_at'];

    protected function casts(): array
    {
        return [
            'progress_value' => 'integer', 'target_value' => 'integer', 'minimum_contributors' => 'integer',
            'contributors_count' => 'integer', 'team_xp_reward' => 'integer', 'rocks_reward' => 'integer',
            'configuration' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime',
            'completed_at' => 'datetime', 'cancelled_at' => 'datetime', 'rewarded_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function template(): BelongsTo { return $this->belongsTo(TeamContractTemplate::class, 'template_id'); }
    public function activator(): BelongsTo { return $this->belongsTo(User::class, 'activated_by'); }
    public function contributions(): HasMany { return $this->hasMany(TeamContractContribution::class); }
    public function rewardGrants(): HasMany { return $this->hasMany(TeamRewardGrant::class); }
}
