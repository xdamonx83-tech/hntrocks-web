<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamXpEvent extends Model
{
    protected $fillable = ['team_id', 'user_id', 'event_type', 'source_key', 'amount', 'level_before', 'level_after', 'metadata'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'level_before' => 'integer', 'level_after' => 'integer', 'metadata' => 'array'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
