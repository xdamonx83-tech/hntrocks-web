<?php

namespace App\Models\Arcade;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArcadeMatchFinalization extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(ArcadeMatch::class, 'match_id');
    }
}
