<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupTeamChatMessage extends Model
{
    use HasFactory;
    use HidesBlockedUsers;

    protected $fillable = [
        'cup_team_id',
        'user_id',
        'body',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(CupTeam::class, 'cup_team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
