<?php

namespace App\Models\Arcade;

use App\Enums\Arcade\ArcadeMatchPlayerResult;
use App\Enums\Arcade\ArcadeMatchPlayerStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArcadeMatchPlayer extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['status' => ArcadeMatchPlayerStatus::class, 'result' => ArcadeMatchPlayerResult::class, 'joined_at' => 'datetime', 'ready_at' => 'datetime', 'left_at' => 'datetime']; }
    public function match(): BelongsTo { return $this->belongsTo(ArcadeMatch::class, 'match_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function moves(): HasMany { return $this->hasMany(ArcadeMatchMove::class, 'match_player_id'); }
}
