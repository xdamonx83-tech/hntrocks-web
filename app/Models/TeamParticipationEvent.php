<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamParticipationEvent extends Model
{
    protected $fillable = ['team_id', 'user_id', 'event_type', 'source_key', 'points', 'metadata'];

    protected function casts(): array { return ['points' => 'integer', 'metadata' => 'array']; }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
