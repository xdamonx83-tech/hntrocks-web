<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamParticipation extends Model
{
    protected $fillable = ['team_id', 'user_id', 'points_total', 'badge_key', 'last_activity_at'];

    protected function casts(): array { return ['points_total' => 'integer', 'last_activity_at' => 'datetime']; }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
