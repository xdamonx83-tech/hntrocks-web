<?php

namespace App\Models\Arcade;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArcadeMatchMove extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['payload' => 'array']; }
    public function match(): BelongsTo { return $this->belongsTo(ArcadeMatch::class, 'match_id'); }
    public function player(): BelongsTo { return $this->belongsTo(ArcadeMatchPlayer::class, 'match_player_id'); }
}
