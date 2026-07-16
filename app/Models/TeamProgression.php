<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamProgression extends Model
{
    protected $fillable = ['team_id', 'level', 'xp_total'];

    protected function casts(): array
    {
        return ['level' => 'integer', 'xp_total' => 'integer'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
