<?php

namespace App\Models\Arcade;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArcadeLaunchTicket extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function game(): BelongsTo { return $this->belongsTo(ArcadeGame::class, 'game_id'); }
    public function release(): BelongsTo { return $this->belongsTo(ArcadeGameRelease::class, 'release_id'); }
    public function match(): BelongsTo { return $this->belongsTo(ArcadeMatch::class, 'match_id'); }
}
