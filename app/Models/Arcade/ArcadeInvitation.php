<?php

namespace App\Models\Arcade;

use App\Enums\Arcade\ArcadeInvitationStatus;
use App\Enums\Arcade\ArcadeMatchMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArcadeInvitation extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['mode' => ArcadeMatchMode::class, 'status' => ArcadeInvitationStatus::class, 'expires_at' => 'datetime', 'accepted_at' => 'datetime', 'declined_at' => 'datetime', 'cancelled_at' => 'datetime']; }
    public function game(): BelongsTo { return $this->belongsTo(ArcadeGame::class, 'game_id'); }
    public function inviter(): BelongsTo { return $this->belongsTo(User::class, 'inviter_id'); }
    public function invitee(): BelongsTo { return $this->belongsTo(User::class, 'invitee_id'); }
    public function match(): BelongsTo { return $this->belongsTo(ArcadeMatch::class, 'match_id'); }
}
