<?php

namespace App\Models\Arcade;

use App\Enums\Arcade\ArcadeMatchMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArcadeUserStat extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mode' => ArcadeMatchMode::class,
            'matches_played' => 'integer',
            'wins' => 'integer',
            'losses' => 'integer',
            'draws' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(ArcadeGame::class, 'game_id');
    }

    public function winRate(): float
    {
        if ($this->matches_played <= 0) {
            return 0.0;
        }

        return round(($this->wins / $this->matches_played) * 100, 2);
    }
}
