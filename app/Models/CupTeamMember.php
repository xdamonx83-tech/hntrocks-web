<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupTeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'cup_team_id',
        'user_id',
        'role',
        'status',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function cupTeam(): BelongsTo
    {
        return $this->belongsTo(CupTeam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'captain' => __('ui.cup_team_role_captain'),
            'participant' => __('ui.cup_team_role_participant'),
            default => __('ui.cup_team_role_member'),
        };
    }
}
