<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamSession extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['team_id', 'creator_id', 'title', 'description', 'starts_at', 'ends_at', 'timezone', 'platform', 'region', 'game_mode', 'max_participants', 'voice_required', 'status', 'completed_at', 'cancelled_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'max_participants' => 'integer', 'voice_required' => 'boolean', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'creator_id'); }
    public function responses(): HasMany { return $this->hasMany(TeamSessionResponse::class); }
}
