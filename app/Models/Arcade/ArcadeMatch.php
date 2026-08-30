<?php

namespace App\Models\Arcade;

use App\Enums\Arcade\ArcadeMatchMode;
use App\Enums\Arcade\ArcadeMatchStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArcadeMatch extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['mode' => ArcadeMatchMode::class, 'status' => ArcadeMatchStatus::class, 'state' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'expires_at' => 'datetime']; }
    public function game(): BelongsTo { return $this->belongsTo(ArcadeGame::class, 'game_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function players(): HasMany { return $this->hasMany(ArcadeMatchPlayer::class, 'match_id')->orderBy('seat'); }
    public function moves(): HasMany { return $this->hasMany(ArcadeMatchMove::class, 'match_id')->orderBy('sequence'); }
}
